<?php
/**
 * SC Flexible Content field type.
 *
 * Lets the editor add instances of pre-defined "layouts" in any order, each
 * layout carrying its own sub-fields. Storage pattern is ACF Pro-compatible:
 *
 *   meta_key                          -> array of layout names in order  (e.g. ['hero','cta','hero'])
 *   _meta_key                         -> parent field key
 *   meta_key_<i>_acf_fc_layout        -> layout name for row i
 *   meta_key_<i>_<sub_in_that_layout> -> sub-field value for row i
 *
 * This mirrors ACF Pro's flexible_content data layout exactly so existing data
 * can be migrated to Pro (or vice-versa) without touching postmeta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'acf_field' ) ) {
	return;
}

class SC_ACF_Flexible extends acf_field {

	use SC_Sub_Fields_UI;

	public function initialize() {
		$this->name     = 'sc_flexible';
		$this->label    = __( 'SC Flexible Content', 'sc-acf-extra' );
		$this->category = 'layout';
		$this->defaults = array(
			'layouts'      => array(),
			'button_label' => __( 'レイアウトを追加', 'sc-acf-extra' ),
		);
	}

	/**
	 * Field-group edit screen settings UI.
	 *
	 * Renders:
	 *   - the "Add layout button" label setting
	 *   - a list of layouts; each layout has its own label / name and a nested
	 *     sub-fields manager (re-using the SC_Sub_Fields_UI trait).
	 */
	public function render_field_settings( $field ) {
		acf_render_field_setting( $field, array(
			'label' => __( 'レイアウトを追加するボタンのラベル', 'sc-acf-extra' ),
			'name'  => 'button_label',
			'type'  => 'text',
		) );

		$layouts  = is_array( $field['layouts'] ) ? $field['layouts'] : array();
		$prefix   = $field['prefix'];
		$field_key = $field['key'] ?? '';
		?>
		<div class="acf-field" data-name="layouts" data-type="sc_layouts">
			<div class="acf-label">
				<label><?php esc_html_e( 'レイアウト', 'sc-acf-extra' ); ?></label>
				<p class="description"><?php esc_html_e( '投稿編集画面で選択できるレイアウトを定義します。各レイアウトは独自のサブフィールドを持ちます。', 'sc-acf-extra' ); ?></p>
			</div>
			<div class="acf-input">
				<div class="sc-layouts" data-parent-key="<?php echo esc_attr( $field_key ); ?>" data-prefix="<?php echo esc_attr( $prefix ); ?>">
					<div class="sc-layouts-list">
						<?php foreach ( $layouts as $i => $layout ) : ?>
							<?php $this->render_layout_block( $prefix, $i, $layout, $field_key ); ?>
						<?php endforeach; ?>
					</div>

					<p>
						<button type="button" class="button button-primary sc-layouts-add"><?php esc_html_e( '+ レイアウトを追加', 'sc-acf-extra' ); ?></button>
					</p>

					<template class="sc-layouts-block-template"><?php $this->render_layout_block( $prefix, '__INDEX__', array(), $field_key ); ?></template>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one layout configuration block (label / name + nested sub-fields manager).
	 *
	 * @param string     $prefix     ACF settings prefix.
	 * @param int|string $index      Layout index (or '__INDEX__' for the JS template).
	 * @param array      $layout     Existing layout data.
	 * @param string     $parent_key Repeater/flexible field key (salt for sub-field key generation).
	 */
	private function render_layout_block( $prefix, $index, $layout, $parent_key ) {
		$key         = $layout['key']        ?? '';
		$label       = $layout['label']      ?? '';
		$name        = $layout['name']       ?? '';
		$sub_fields  = $layout['sub_fields'] ?? array();
		$base        = "{$prefix}[layouts][{$index}]";

		// The container prefix for this layout's sub-fields manager.
		// The trait will append "[sub_fields][i][...]" automatically.
		$sub_container_prefix = $base;
		?>
		<div class="sc-layout-block" data-index="<?php echo esc_attr( (string) $index ); ?>">
			<div class="sc-layout-header">
				<span class="sc-layout-index">#<?php echo esc_html( (string) ( is_numeric( $index ) ? (int) $index + 1 : '?' ) ); ?></span>
				<input type="text" name="<?php echo esc_attr( "{$base}[label]" ); ?>" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( 'ラベル（例：Hero）', 'sc-acf-extra' ); ?>" class="sc-layout-label" />
				<input type="text" name="<?php echo esc_attr( "{$base}[name]" ); ?>" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( '名前（例：hero）', 'sc-acf-extra' ); ?>" class="sc-layout-name" />
				<input type="hidden" name="<?php echo esc_attr( "{$base}[key]" ); ?>" value="<?php echo esc_attr( $key ); ?>" class="sc-layout-key" />
				<button type="button" class="button-link-delete sc-layout-remove" aria-label="<?php esc_attr_e( 'このレイアウトを削除', 'sc-acf-extra' ); ?>">×</button>
			</div>
			<div class="sc-layout-body">
				<?php
				$this->sc_render_sub_fields_block(
					$sub_container_prefix,
					$sub_fields,
					$key ?: $parent_key,
					__( 'サブフィールド', 'sc-acf-extra' ),
					__( 'このレイアウトのサブフィールド（カラム）を定義します。', 'sc-acf-extra' )
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Sanitise + auto-fill incoming field config on save.
	 *
	 * - Drops blank layouts (no name + no label).
	 * - Generates a unique key for layouts missing one.
	 * - Recursively sanitises each layout's sub-fields via the shared trait.
	 */
	public function update_field( $field ) {
		$layouts = is_array( $field['layouts'] ?? null ) ? $field['layouts'] : array();
		$layouts = array_values( array_filter( $layouts, function ( $layout ) {
			return ! empty( $layout['name'] ) || ! empty( $layout['label'] );
		} ) );
		foreach ( $layouts as &$layout ) {
			if ( empty( $layout['key'] ) ) {
				$layout['key'] = 'layout_' . substr( md5( $field['key'] . ( $layout['name'] ?? '' ) . microtime( true ) ), 0, 13 );
			}
			$layout['sub_fields'] = $this->sc_sanitize_sub_fields( $layout['sub_fields'] ?? array(), $layout['key'] );
		}
		$field['layouts'] = $layouts;
		return $field;
	}

	/**
	 * Render the field on the post-edit screen.
	 */
	public function render_field( $field ) {
		if ( empty( $field['layouts'] ) ) {
			echo '<p>' . esc_html__( 'レイアウトが定義されていません。フィールドグループ編集画面から追加してください。', 'sc-acf-extra' ) . '</p>';
			return;
		}

		$layouts         = $field['layouts'];
		$value           = is_array( $field['value'] ) ? $field['value'] : array();
		$input_prefix    = "acf[{$field['key']}]";
		$button_label    = $field['button_label'] ?: __( 'レイアウトを追加', 'sc-acf-extra' );
		$layouts_by_name = array();
		foreach ( $layouts as $l ) {
			$layouts_by_name[ $l['name'] ] = $l;
		}

		$wrapper_attrs = array(
			'class'             => 'sc-flexible',
			'data-input-prefix' => $input_prefix,
		);
		?>
		<div <?php echo acf_esc_attrs( $wrapper_attrs ); ?>>
			<input type="hidden" name="<?php echo esc_attr( $input_prefix ); ?>[acfcloneindex]" value="" />
			<div class="sc-flexible-instances">
				<?php foreach ( $value as $i => $row ) :
					$layout_name = $row['acf_fc_layout'] ?? '';
					$layout      = $layouts_by_name[ $layout_name ] ?? null;
					if ( ! $layout ) {
						continue;
					}
					$this->render_instance( $field, $layout, $i, $row );
				endforeach; ?>
			</div>

			<div class="sc-flexible-controls">
				<select class="sc-flexible-add-select">
					<option value=""><?php esc_html_e( 'レイアウトを選択...', 'sc-acf-extra' ); ?></option>
					<?php foreach ( $layouts as $l ) : ?>
						<option value="<?php echo esc_attr( $l['name'] ); ?>"><?php echo esc_html( $l['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button button-primary sc-flexible-add-btn">
					+ <?php echo esc_html( $button_label ); ?>
				</button>
			</div>

			<?php foreach ( $layouts as $l ) : ?>
				<template class="sc-flexible-layout-template" data-layout="<?php echo esc_attr( $l['name'] ); ?>"><?php $this->render_instance( $field, $l, '__INDEX__', array() ); ?></template>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render a single layout instance (one row in the flexible content).
	 *
	 * @param array      $field     Parent field config.
	 * @param array      $layout    Layout definition (name, label, sub_fields).
	 * @param int|string $index     Row index (integer or '__INDEX__' for JS template).
	 * @param array      $row_value Existing values for this row (keyed by sub-field name).
	 */
	private function render_instance( $field, $layout, $index, $row_value ) {
		$sub_fields = $layout['sub_fields'] ?? array();
		$base       = "acf[{$field['key']}][{$index}]";
		?>
		<div class="sc-flexible-instance" data-index="<?php echo esc_attr( (string) $index ); ?>" data-layout="<?php echo esc_attr( $layout['name'] ); ?>">
			<input type="hidden" name="<?php echo esc_attr( "{$base}[acf_fc_layout]" ); ?>" value="<?php echo esc_attr( $layout['name'] ); ?>" />

			<div class="sc-flexible-instance-header">
				<span class="sc-flexible-instance-title"><?php echo esc_html( $layout['label'] ?: $layout['name'] ); ?></span>
				<span class="sc-flexible-instance-actions">
					<button type="button" class="button-link sc-flexible-instance-up" aria-label="<?php esc_attr_e( '上へ移動', 'sc-acf-extra' ); ?>">↑</button>
					<button type="button" class="button-link sc-flexible-instance-down" aria-label="<?php esc_attr_e( '下へ移動', 'sc-acf-extra' ); ?>">↓</button>
					<button type="button" class="button-link-delete sc-flexible-instance-remove" aria-label="<?php esc_attr_e( 'このレイアウトを削除', 'sc-acf-extra' ); ?>">×</button>
				</span>
			</div>

			<div class="sc-flexible-instance-body">
				<?php foreach ( $sub_fields as $sub ) :
					$sub_field           = wp_parse_args( $sub, array(
						'type'    => 'text',
						'name'    => '',
						'key'     => '',
						'value'   => '',
						'wrapper' => array(),
					) );
					$sub_field['prefix'] = $base;
					$sub_field['name']   = $sub_field['key'];
					$sub_field['value']  = $row_value[ $sub['key'] ] ?? '';
					?>
					<div class="sc-flexible-instance-field">
						<?php acf_render_field_wrap( $sub_field ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Make sub-fields from all layouts visible to ACF's generic field lookup.
	 *
	 * ACF's `acf_get_sub_field()` only walks `$field['layouts']` when the type
	 * is the native `flexible_content`. For our custom `sc_flexible` we expose
	 * every layout's sub-fields as a flat `sub_fields` array so template
	 * helpers like `have_rows()`, `the_row()`, `get_sub_field()` work without
	 * patching core ACF.
	 *
	 * Caveat: if two layouts share a sub-field name, the first occurrence wins.
	 * Use unique sub-field names across layouts when in doubt.
	 */
	public function load_field( $field ) {
		return $this->merge_layouts_into_sub_fields( $field );
	}

	/**
	 * Same merge applied via the validation hook — defence in depth.
	 * Different ACF code paths call different filters (load_field vs
	 * get_valid_field), and which one fires first depends on cache state,
	 * so we register both.
	 */
	public function get_valid_field( $field ) {
		return $this->merge_layouts_into_sub_fields( $field );
	}

	private function merge_layouts_into_sub_fields( $field ) {
		if ( empty( $field['layouts'] ) || ! is_array( $field['layouts'] ) ) {
			return $field;
		}
		$merged = array();
		foreach ( $field['layouts'] as $layout ) {
			foreach ( $layout['sub_fields'] ?? array() as $sub ) {
				$merged[] = $sub;
			}
		}
		$field['sub_fields'] = $merged;
		return $field;
	}

	/**
	 * Load saved value from postmeta (Pro-compatible pattern).
	 *
	 * Parent meta stores an array of layout names per row. Each row's sub-field
	 * values are at `<field>_<i>_<sub_name>`.
	 *
	 * @return array Rows; each row is `['acf_fc_layout' => <name>, <sub_name> => <value>, ...]`.
	 */
	public function load_value( $value, $post_id, $field ) {
		$layout_order = acf_get_metadata( $post_id, $field['name'] );
		if ( ! is_array( $layout_order ) || empty( $layout_order ) ) {
			return array();
		}

		$layouts_by_name = array();
		foreach ( $field['layouts'] ?? array() as $l ) {
			$layouts_by_name[ $l['name'] ] = $l;
		}

		$rows = array();
		foreach ( $layout_order as $i => $layout_name ) {
			$row    = array( 'acf_fc_layout' => $layout_name );
			$layout = $layouts_by_name[ $layout_name ] ?? null;
			if ( $layout ) {
				foreach ( $layout['sub_fields'] ?? array() as $sub ) {
					$meta_key           = "{$field['name']}_{$i}_{$sub['name']}";
					// Row keys must be sub-field KEYS so ACF's get_sub_field()
					// internal lookup ($row[$sub_field['key']]) succeeds.
					// format_value() later translates key -> name for templates.
					$row[ $sub['key'] ] = acf_get_metadata( $post_id, $meta_key );
				}
			}
			$rows[] = $row;
		}
		return $rows;
	}

	/**
	 * Save submitted value to postmeta.
	 *
	 * Parent meta stores the ordered array of layout names. Sub-field values are
	 * written at `<field>_<i>_<sub_name>` using each row's chosen layout's sub_fields.
	 */
	public function update_value( $value, $post_id, $field ) {
		if ( is_array( $value ) ) {
			unset( $value['acfcloneindex'] );
		}
		$value = is_array( $value ) ? array_values( $value ) : array();

		$layouts_by_name = array();
		foreach ( $field['layouts'] ?? array() as $l ) {
			$layouts_by_name[ $l['name'] ] = $l;
		}

		// Pre-read the old layout order so we can clean up rows that were removed.
		$old_order = acf_get_metadata( $post_id, $field['name'] );
		$old_order = is_array( $old_order ) ? $old_order : array();
		$old_count = count( $old_order );

		$new_order = array();
		foreach ( $value as $i => $row ) {
			$layout_name = $row['acf_fc_layout'] ?? '';
			$layout      = $layouts_by_name[ $layout_name ] ?? null;
			if ( ! $layout ) {
				continue;
			}
			$new_order[] = $layout_name;

			foreach ( $layout['sub_fields'] ?? array() as $sub ) {
				$meta_key   = "{$field['name']}_{$i}_{$sub['name']}";
				$cell_value = $row[ $sub['key'] ] ?? '';
				acf_update_value( $cell_value, $post_id, array_merge( $sub, array( 'name' => $meta_key ) ) );
			}
		}

		// Clean up removed rows: anything past the new count, using the *old* layout
		// to know which sub-field meta keys to delete.
		$new_count = count( $new_order );
		for ( $i = $new_count; $i < $old_count; $i++ ) {
			$old_layout_name = $old_order[ $i ] ?? '';
			$old_layout      = $layouts_by_name[ $old_layout_name ] ?? null;
			if ( ! $old_layout ) {
				continue;
			}
			foreach ( $old_layout['sub_fields'] ?? array() as $sub ) {
				$meta_key = "{$field['name']}_{$i}_{$sub['name']}";
				acf_delete_metadata( $post_id, $meta_key );
				acf_delete_metadata( $post_id, "_{$meta_key}" );
			}
		}

		return $new_order;
	}

	/**
	 * Format value for template usage via get_field() / have_rows() / get_row_layout().
	 */
	public function format_value( $value, $post_id, $field ) {
		if ( ! is_array( $value ) || empty( $value ) ) {
			return array();
		}

		$layouts_by_name = array();
		foreach ( $field['layouts'] ?? array() as $l ) {
			$layouts_by_name[ $l['name'] ] = $l;
		}

		foreach ( $value as $i => $row ) {
			$layout = $layouts_by_name[ $row['acf_fc_layout'] ?? '' ] ?? null;
			if ( ! $layout ) {
				continue;
			}
			foreach ( $layout['sub_fields'] ?? array() as $sub ) {
				// load_value stores by key; templates expect by name. Translate.
				$cell = $row[ $sub['key'] ] ?? null;

				// Scope the sub-field name to avoid acf_format_value() cache collisions
				// with same-named sub-fields in other fields/layouts.
				$scoped_sub = array_merge( $sub, array(
					'name' => "{$field['name']}_{$i}_{$sub['name']}",
				) );
				$value[ $i ][ $sub['name'] ] = acf_format_value( $cell, $post_id, $scoped_sub );

				if ( $sub['key'] !== $sub['name'] && isset( $value[ $i ][ $sub['key'] ] ) ) {
					unset( $value[ $i ][ $sub['key'] ] );
				}
			}
		}
		return $value;
	}

	/**
	 * Field-group edit screen assets (shared with Repeater).
	 */
	public function field_group_admin_enqueue_scripts() {
		wp_enqueue_script(
			'sc-acf-field-settings',
			SC_ACF_EXTRA_URL . 'assets/js/field-settings.js',
			array( 'jquery', 'acf-input' ),
			SC_ACF_EXTRA_VERSION,
			true
		);
		wp_enqueue_style(
			'sc-acf-repeater',
			SC_ACF_EXTRA_URL . 'assets/css/repeater.css',
			array(),
			SC_ACF_EXTRA_VERSION
		);
	}

	/**
	 * Post-edit screen assets (flexible-only).
	 */
	public function input_admin_enqueue_scripts() {
		wp_enqueue_script(
			'sc-acf-flexible',
			SC_ACF_EXTRA_URL . 'assets/js/flexible.js',
			array( 'jquery', 'acf-input' ),
			SC_ACF_EXTRA_VERSION,
			true
		);
		wp_enqueue_style(
			'sc-acf-flexible',
			SC_ACF_EXTRA_URL . 'assets/css/flexible.css',
			array(),
			SC_ACF_EXTRA_VERSION
		);
	}
}
