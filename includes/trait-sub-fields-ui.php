<?php
/**
 * Shared sub-fields manager UI.
 *
 * Used by both `sc_repeater` and (upcoming) `sc_flexible` to render the
 * "Sub Fields" table inside the field-group edit screen, and to validate
 * incoming sub-field configuration on save.
 *
 * The trait operates over a sub-fields list located at:
 *   <container_prefix>[sub_fields][i]
 *
 * For Repeater the container is the field itself (`acf_fields[<id>]`).
 * For Flexible each layout is its own container
 * (`acf_fields[<id>][layouts][L]`), so the same trait renders a separate
 * sub-fields table per layout without modification.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait SC_Sub_Fields_UI {

	/**
	 * Sub-field types we currently support inside a Repeater or Flexible layout.
	 *
	 * @return array<string,string> Slug => translated label.
	 */
	protected function sc_get_supported_sub_field_types() {
		return array(
			'text'     => __( 'テキスト', 'sc-acf-extra' ),
			'textarea' => __( 'テキストエリア', 'sc-acf-extra' ),
			'url'      => __( 'URL', 'sc-acf-extra' ),
			'number'   => __( '数値', 'sc-acf-extra' ),
			'image'    => __( '画像', 'sc-acf-extra' ),
		);
	}

	/**
	 * Render the sub-fields manager block: heading + table + add-row button + JS template.
	 *
	 * Form input names are `${container_prefix}[sub_fields][i][...]` so PHP receives them as a nested array.
	 *
	 * @param string $container_prefix Path leading up to (but not including) the sub_fields container.
	 * @param array  $sub_fields       Existing sub-fields config (may be empty).
	 * @param string $parent_key       Field key of the immediate parent (for sub-field key generation).
	 * @param string $label_text       Section heading text shown above the table.
	 * @param string $description      Helper text shown under the heading.
	 */
	protected function sc_render_sub_fields_block( $container_prefix, $sub_fields, $parent_key, $label_text, $description ) {
		$types      = $this->sc_get_supported_sub_field_types();
		$sub_fields = is_array( $sub_fields ) ? $sub_fields : array();
		?>
		<div class="acf-field" data-name="sub_fields" data-type="sc_sub_fields">
			<div class="acf-label">
				<label><?php echo esc_html( $label_text ); ?></label>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			</div>
			<div class="acf-input">
				<div class="sc-sub-fields" data-parent-key="<?php echo esc_attr( $parent_key ); ?>">
					<table class="widefat sc-sub-fields-table">
						<thead>
							<tr>
								<th style="width:30%"><?php esc_html_e( 'ラベル', 'sc-acf-extra' ); ?></th>
								<th style="width:25%"><?php esc_html_e( '名前', 'sc-acf-extra' ); ?></th>
								<th style="width:40%"><?php esc_html_e( 'タイプ', 'sc-acf-extra' ); ?></th>
								<th style="width:5%"></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $sub_fields as $i => $sub ) : ?>
								<?php $this->sc_render_sub_field_row( $container_prefix, $i, $sub, $types ); ?>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan="4">
									<button type="button" class="button sc-sub-fields-add"><?php esc_html_e( '+ サブフィールドを追加', 'sc-acf-extra' ); ?></button>
								</td>
							</tr>
						</tfoot>
					</table>
					<template class="sc-sub-fields-row-template"><?php $this->sc_render_sub_field_row( $container_prefix, '__INDEX__', array(), $types ); ?></template>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one row of the sub-fields manager (label / name / type / type-conditional options / remove).
	 *
	 * @param string     $container_prefix Path leading up to (but not including) the sub_fields container.
	 * @param int|string $index            Row index, or '__INDEX__' for the JS template.
	 * @param array      $sub              Existing sub-field data.
	 * @param array      $types            Slug => label of selectable types.
	 */
	protected function sc_render_sub_field_row( $container_prefix, $index, $sub, $types ) {
		$key           = $sub['key']           ?? '';
		$label         = $sub['label']         ?? '';
		$name          = $sub['name']          ?? '';
		$type          = $sub['type']          ?? 'text';
		$return_format = $sub['return_format'] ?? 'array';
		$new_lines     = $sub['new_lines']     ?? 'wpautop';
		$base          = "{$container_prefix}[sub_fields][{$index}]";

		$return_formats = array(
			'array' => __( '配列 (Array)', 'sc-acf-extra' ),
			'url'   => __( 'URL', 'sc-acf-extra' ),
			'id'    => __( 'ID', 'sc-acf-extra' ),
		);

		$new_lines_opts = array(
			'wpautop' => __( '段落 + 改行 (wpautop)', 'sc-acf-extra' ),
			'br'      => __( '改行のみ (<br>)', 'sc-acf-extra' ),
			''        => __( 'なし (そのまま出力)', 'sc-acf-extra' ),
		);
		?>
		<tr class="sc-sub-fields-row" data-index="<?php echo esc_attr( (string) $index ); ?>" data-type="<?php echo esc_attr( $type ); ?>">
			<td>
				<input type="text" name="<?php echo esc_attr( "{$base}[label]" ); ?>" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( '例：タイトル', 'sc-acf-extra' ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( "{$base}[key]" ); ?>" value="<?php echo esc_attr( $key ); ?>" class="sc-sub-fields-key" />
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( "{$base}[name]" ); ?>" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( '例：title', 'sc-acf-extra' ); ?>" />
			</td>
			<td>
				<select name="<?php echo esc_attr( "{$base}[type]" ); ?>" class="sc-sub-fields-type">
					<?php foreach ( $types as $slug => $type_label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $type, $slug ); ?>><?php echo esc_html( $type_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="sc-sub-fields-image-only">
					<label class="sc-sub-fields-inline-label"><?php esc_html_e( '出力', 'sc-acf-extra' ); ?>:</label>
					<select name="<?php echo esc_attr( "{$base}[return_format]" ); ?>">
						<?php foreach ( $return_formats as $slug => $rf_label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $return_format, $slug ); ?>><?php echo esc_html( $rf_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
				<span class="sc-sub-fields-textarea-only">
					<label class="sc-sub-fields-inline-label"><?php esc_html_e( '改行', 'sc-acf-extra' ); ?>:</label>
					<select name="<?php echo esc_attr( "{$base}[new_lines]" ); ?>">
						<?php foreach ( $new_lines_opts as $slug => $nl_label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $new_lines, $slug ); ?>><?php echo esc_html( $nl_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<button type="button" class="button-link-delete sc-sub-fields-remove" aria-label="<?php esc_attr_e( 'サブフィールドを削除', 'sc-acf-extra' ); ?>">×</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Validate + auto-fill an incoming sub-fields list (called from update_field).
	 *
	 * - Drops blank rows (no name + no label).
	 * - Generates a unique key for any sub-field missing one.
	 * - Falls back to type=text when type is empty.
	 * - Applies type-specific option defaults (image: return_format/preview_size/library; textarea: new_lines).
	 *
	 * @param array  $sub_fields Raw incoming sub-fields.
	 * @param string $parent_key Salt for generated sub-field keys (typically the parent field's key).
	 *
	 * @return array Cleaned sub-fields, re-indexed.
	 */
	protected function sc_sanitize_sub_fields( $sub_fields, $parent_key ) {
		if ( ! is_array( $sub_fields ) || empty( $sub_fields ) ) {
			return array();
		}
		$sub_fields = array_values( array_filter( $sub_fields, function ( $sub ) {
			return ! empty( $sub['name'] ) || ! empty( $sub['label'] );
		} ) );
		foreach ( $sub_fields as &$sub ) {
			if ( empty( $sub['key'] ) ) {
				$sub['key'] = 'field_' . substr( md5( $parent_key . ( $sub['name'] ?? '' ) . microtime( true ) ), 0, 13 );
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
		return $sub_fields;
	}
}
