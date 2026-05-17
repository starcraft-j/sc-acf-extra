<?php
/**
 * SCN Repeater field type.
 *
 * Storage pattern is Pro-compatible:
 *   meta_key                    -> count of rows (integer)
 *   _meta_key                   -> field key reference
 *   meta_key_<i>_<subfield>     -> sub-field value
 *   _meta_key_<i>_<subfield>    -> sub-field key reference
 *
 * This means a site can later activate ACF Pro and keep all data without migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'acf_field' ) ) {
	return;
}

class SCN_ACF_Repeater extends acf_field {

	public function initialize() {
		$this->name     = 'scn_repeater';
		$this->label    = __( 'SC Repeater', 'scn-acf-extra' );
		$this->category = 'layout';
		$this->defaults = array(
			'sub_fields'    => array(),
			'min'           => 0,
			'max'           => 0,
			'layout'        => 'table',
			'button_label'  => __( '行を追加', 'scn-acf-extra' ),
		);
	}

	/**
	 * Field settings UI shown when creating/editing the field group.
	 *
	 * Adds:
	 *   - min / max row count
	 *   - Add-row button label
	 *   - sub-fields manager (label / name / type rows)
	 */
	public function render_field_settings( $field ) {
		acf_render_field_setting( $field, array(
			'label'        => __( '最小行数', 'scn-acf-extra' ),
			'name'         => 'min',
			'type'         => 'number',
			'instructions' => __( '0 で下限なし。', 'scn-acf-extra' ),
		) );
		acf_render_field_setting( $field, array(
			'label'        => __( '最大行数', 'scn-acf-extra' ),
			'name'         => 'max',
			'type'         => 'number',
			'instructions' => __( '0 で上限なし。', 'scn-acf-extra' ),
		) );
		acf_render_field_setting( $field, array(
			'label' => __( '行を追加するボタンのラベル', 'scn-acf-extra' ),
			'name'  => 'button_label',
			'type'  => 'text',
		) );

		// Sub-fields manager — rendered inside ACF's standard settings row container.
		$sub_fields = is_array( $field['sub_fields'] ) ? $field['sub_fields'] : array();
		$supported  = array(
			'text'     => __( 'テキスト', 'scn-acf-extra' ),
			'textarea' => __( 'テキストエリア', 'scn-acf-extra' ),
			'url'      => __( 'URL', 'scn-acf-extra' ),
			'number'   => __( '数値', 'scn-acf-extra' ),
			'image'    => __( '画像', 'scn-acf-extra' ),
		);
		$field_key  = $field['key'] ?? '';
		?>
		<div class="acf-field" data-name="sub_fields" data-type="scn_sub_fields">
			<div class="acf-label">
				<label><?php esc_html_e( 'サブフィールド', 'scn-acf-extra' ); ?></label>
				<p class="description"><?php esc_html_e( 'リピーターの各カラム（列）を定義します。Name は get_field() で使う配列キーになります。', 'scn-acf-extra' ); ?></p>
			</div>
			<div class="acf-input">
				<div class="scn-sub-fields" data-parent-key="<?php echo esc_attr( $field_key ); ?>">
					<table class="widefat scn-sub-fields-table">
						<thead>
							<tr>
								<th style="width:30%"><?php esc_html_e( 'ラベル', 'scn-acf-extra' ); ?></th>
								<th style="width:25%"><?php esc_html_e( '名前', 'scn-acf-extra' ); ?></th>
								<th style="width:40%"><?php esc_html_e( 'タイプ', 'scn-acf-extra' ); ?></th>
								<th style="width:5%"></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $sub_fields as $i => $sub ) : ?>
								<?php $this->render_sub_field_row( $field['prefix'], $i, $sub, $supported ); ?>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan="4">
									<button type="button" class="button scn-sub-fields-add"><?php esc_html_e( '+ サブフィールドを追加', 'scn-acf-extra' ); ?></button>
								</td>
							</tr>
						</tfoot>
					</table>
					<template class="scn-sub-fields-row-template"><?php $this->render_sub_field_row( $field['prefix'], '__INDEX__', array(), $supported ); ?></template>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one sub-field manager row inside settings UI.
	 *
	 * @param string     $prefix   ACF settings prefix (typically "acf_fields[<row id>]").
	 * @param int|string $index    Row index, or '__INDEX__' for the JS template.
	 * @param array      $sub      Existing sub-field data.
	 * @param array      $types    Allowed type slug => label.
	 */
	private function render_sub_field_row( $prefix, $index, $sub, $types ) {
		$key           = $sub['key']           ?? '';
		$label         = $sub['label']         ?? '';
		$name          = $sub['name']          ?? '';
		$type          = $sub['type']          ?? 'text';
		$return_format = $sub['return_format'] ?? 'array';
		$new_lines     = $sub['new_lines']     ?? 'wpautop';
		$base          = "{$prefix}[sub_fields][{$index}]";

		$return_formats = array(
			'array' => __( '配列 (Array)', 'scn-acf-extra' ),
			'url'   => __( 'URL', 'scn-acf-extra' ),
			'id'    => __( 'ID', 'scn-acf-extra' ),
		);

		$new_lines_opts = array(
			'wpautop' => __( '段落 + 改行 (wpautop)', 'scn-acf-extra' ),
			'br'      => __( '改行のみ (<br>)', 'scn-acf-extra' ),
			''        => __( 'なし (そのまま出力)', 'scn-acf-extra' ),
		);
		?>
		<tr class="scn-sub-fields-row" data-index="<?php echo esc_attr( (string) $index ); ?>" data-type="<?php echo esc_attr( $type ); ?>">
			<td>
				<input type="text" name="<?php echo esc_attr( "{$base}[label]" ); ?>" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( '例：タイトル', 'scn-acf-extra' ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( "{$base}[key]" ); ?>" value="<?php echo esc_attr( $key ); ?>" class="scn-sub-fields-key" />
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( "{$base}[name]" ); ?>" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( '例：title', 'scn-acf-extra' ); ?>" />
			</td>
			<td>
				<select name="<?php echo esc_attr( "{$base}[type]" ); ?>" class="scn-sub-fields-type">
					<?php foreach ( $types as $slug => $type_label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $type, $slug ); ?>><?php echo esc_html( $type_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="scn-sub-fields-image-only">
					<label class="scn-sub-fields-inline-label"><?php esc_html_e( '出力', 'scn-acf-extra' ); ?>:</label>
					<select name="<?php echo esc_attr( "{$base}[return_format]" ); ?>">
						<?php foreach ( $return_formats as $slug => $rf_label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $return_format, $slug ); ?>><?php echo esc_html( $rf_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
				<span class="scn-sub-fields-textarea-only">
					<label class="scn-sub-fields-inline-label"><?php esc_html_e( '改行', 'scn-acf-extra' ); ?>:</label>
					<select name="<?php echo esc_attr( "{$base}[new_lines]" ); ?>">
						<?php foreach ( $new_lines_opts as $slug => $nl_label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $new_lines, $slug ); ?>><?php echo esc_html( $nl_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<button type="button" class="button-link-delete scn-sub-fields-remove" aria-label="<?php esc_attr_e( 'サブフィールドを削除', 'scn-acf-extra' ); ?>">×</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Sanitise + auto-fill incoming field config on save.
	 *
	 * Ensures each sub-field has a non-empty unique key so save/load round-trip works.
	 */
	public function update_field( $field ) {
		if ( ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
			$field['sub_fields'] = array_values( array_filter( $field['sub_fields'], function ( $sub ) {
				return ! empty( $sub['name'] ) || ! empty( $sub['label'] );
			} ) );
			foreach ( $field['sub_fields'] as &$sub ) {
				if ( empty( $sub['key'] ) ) {
					$sub['key'] = 'field_' . substr( md5( $field['key'] . ( $sub['name'] ?? '' ) . microtime( true ) ), 0, 13 );
				}
				if ( empty( $sub['type'] ) ) {
					$sub['type'] = 'text';
				}
				if ( 'image' === $sub['type'] ) {
					$allowed = array( 'array', 'url', 'id' );
					if ( empty( $sub['return_format'] ) || ! in_array( $sub['return_format'], $allowed, true ) ) {
						$sub['return_format'] = 'array';
					}
					$sub['preview_size'] = $sub['preview_size'] ?? 'medium';
					$sub['library']      = $sub['library']      ?? 'all';
				}
				if ( 'textarea' === $sub['type'] ) {
					$nl_allowed = array( 'wpautop', 'br', '' );
					if ( ! isset( $sub['new_lines'] ) || ! in_array( $sub['new_lines'], $nl_allowed, true ) ) {
						$sub['new_lines'] = 'wpautop';
					}
				}
			}
		}
		return $field;
	}

	/**
	 * Render the field on edit screens.
	 */
	public function render_field( $field ) {
		$sub_fields = $field['sub_fields'];
		if ( empty( $sub_fields ) ) {
			echo '<p>' . esc_html__( 'サブフィールドが定義されていません。', 'scn-acf-extra' ) . '</p>';
			return;
		}

		$value = is_array( $field['value'] ) ? $field['value'] : array();
		$rows  = max( count( $value ), (int) $field['min'] );

		// ACF wants every input named `acf[<field_key>]...` so $_POST['acf'][KEY] arrives in update_value().
		$input_prefix = "acf[{$field['key']}]";

		$wrapper_attrs = array(
			'class'              => 'scn-repeater',
			'data-min'           => (int) $field['min'],
			'data-max'           => (int) $field['max'],
			'data-input-prefix'  => $input_prefix,
		);
		?>
		<div <?php echo acf_esc_attrs( $wrapper_attrs ); ?>>
			<table class="scn-repeater-table widefat">
				<thead>
					<tr>
						<th class="scn-repeater-handle"></th>
						<?php foreach ( $sub_fields as $sub ) : ?>
							<th><?php echo esc_html( $sub['label'] ); ?></th>
						<?php endforeach; ?>
						<th class="scn-repeater-remove"></th>
					</tr>
				</thead>
				<tbody>
					<?php for ( $i = 0; $i < $rows; $i++ ) : ?>
						<?php $this->render_row( $field, $sub_fields, $i, $value[ $i ] ?? array() ); ?>
					<?php endfor; ?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="<?php echo (int) ( count( $sub_fields ) + 2 ); ?>">
							<button type="button" class="button scn-repeater-add">
								<?php echo esc_html( $field['button_label'] ); ?>
							</button>
						</td>
					</tr>
				</tfoot>
			</table>

			<template class="scn-repeater-row-template"><?php $this->render_row( $field, $sub_fields, '__INDEX__', array() ); ?></template>
		</div>
		<?php
	}

	/**
	 * Render a single repeater row.
	 *
	 * @param array      $field      Parent field config.
	 * @param array      $sub_fields Sub-field definitions.
	 * @param int|string $index      Row index (integer for real rows, '__INDEX__' for JS template).
	 * @param array      $row_value  Existing values for this row, keyed by sub-field name.
	 */
	private function render_row( $field, $sub_fields, $index, $row_value ) {
		?>
		<tr class="scn-repeater-row" data-index="<?php echo esc_attr( (string) $index ); ?>">
			<td class="scn-repeater-handle" aria-hidden="true">≡</td>
			<?php foreach ( $sub_fields as $sub ) :
				$sub_field = wp_parse_args( $sub, array(
					'type'    => 'text',
					'name'    => '',
					'key'     => '',
					'value'   => '',
					'wrapper' => array(),
				) );
				// Use the sub-field key for the HTML input name so the round-trip
				// stays ACF-compatible. update_value() will translate key -> name
				// when persisting to postmeta.
				$sub_field['prefix'] = "acf[{$field['key']}][{$index}]";
				$sub_field['name']   = $sub_field['key'];
				$sub_field['value']  = $row_value[ $sub['name'] ] ?? '';
				?>
				<td>
					<?php acf_render_field( $sub_field ); ?>
				</td>
			<?php endforeach; ?>
			<td class="scn-repeater-remove">
				<button type="button" class="button-link-delete scn-repeater-remove-btn" aria-label="<?php esc_attr_e( '行を削除', 'scn-acf-extra' ); ?>">×</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Enqueue assets on the field-group edit screen (for the settings UI).
	 */
	public function field_group_admin_enqueue_scripts() {
		wp_enqueue_script(
			'scn-acf-field-settings',
			SCN_ACF_EXTRA_URL . 'assets/js/field-settings.js',
			array( 'jquery', 'acf-input' ),
			SCN_ACF_EXTRA_VERSION,
			true
		);
		wp_enqueue_style(
			'scn-acf-repeater',
			SCN_ACF_EXTRA_URL . 'assets/css/repeater.css',
			array(),
			SCN_ACF_EXTRA_VERSION
		);
	}

	/**
	 * Enqueue admin assets.
	 */
	public function input_admin_enqueue_scripts() {
		wp_enqueue_script(
			'scn-acf-repeater',
			SCN_ACF_EXTRA_URL . 'assets/js/repeater.js',
			array( 'jquery', 'acf-input' ),
			SCN_ACF_EXTRA_VERSION,
			true
		);
		wp_enqueue_style(
			'scn-acf-repeater',
			SCN_ACF_EXTRA_URL . 'assets/css/repeater.css',
			array(),
			SCN_ACF_EXTRA_VERSION
		);
	}

	/**
	 * Load saved value from postmeta (Pro-compatible pattern).
	 *
	 * @param mixed $value   Always empty when ACF asks us to load.
	 * @param int   $post_id Post / option / user / term id.
	 * @param array $field   Parent field config.
	 *
	 * @return array Rows, each an associative array of sub-field name => value.
	 */
	public function load_value( $value, $post_id, $field ) {
		$count = (int) acf_get_metadata( $post_id, $field['name'] );
		if ( $count < 1 || empty( $field['sub_fields'] ) ) {
			return array();
		}

		$rows = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$row = array();
			foreach ( $field['sub_fields'] as $sub ) {
				$meta_key      = "{$field['name']}_{$i}_{$sub['name']}";
				$row[ $sub['name'] ] = acf_get_metadata( $post_id, $meta_key );
			}
			$rows[] = $row;
		}
		return $rows;
	}

	/**
	 * Save submitted value to postmeta.
	 *
	 * @param mixed $value   Submitted value (array of rows or empty).
	 * @param int   $post_id Target object id.
	 * @param array $field   Parent field config.
	 *
	 * @return int Number of rows saved (also stored as the parent meta value).
	 */
	public function update_value( $value, $post_id, $field ) {
		$value = is_array( $value ) ? array_values( $value ) : array();
		$old_count = (int) acf_get_metadata( $post_id, $field['name'] );

		foreach ( $value as $i => $row ) {
			foreach ( $field['sub_fields'] as $sub ) {
				$meta_key   = "{$field['name']}_{$i}_{$sub['name']}";
				$cell_value = $row[ $sub['key'] ] ?? '';
				acf_update_value( $cell_value, $post_id, array_merge( $sub, array( 'name' => $meta_key ) ) );
			}
		}

		// Clean up leftover rows from previous saves.
		$new_count = count( $value );
		for ( $i = $new_count; $i < $old_count; $i++ ) {
			foreach ( $field['sub_fields'] as $sub ) {
				$meta_key = "{$field['name']}_{$i}_{$sub['name']}";
				acf_delete_metadata( $post_id, $meta_key );
				acf_delete_metadata( $post_id, "_{$meta_key}" );
			}
		}

		return $new_count;
	}

	/**
	 * Format value for template usage via get_field().
	 */
	public function format_value( $value, $post_id, $field ) {
		if ( ! is_array( $value ) || empty( $value ) ) {
			return array();
		}
		foreach ( $value as $i => $row ) {
			foreach ( $field['sub_fields'] as $sub ) {
				$cell = $row[ $sub['name'] ] ?? null;
				$value[ $i ][ $sub['name'] ] = acf_format_value( $cell, $post_id, $sub );
			}
		}
		return $value;
	}
}
