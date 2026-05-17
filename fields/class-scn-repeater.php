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
		$this->label    = __( 'SCN Repeater', 'scn-acf-extra' );
		$this->category = 'layout';
		$this->defaults = array(
			'sub_fields'    => array(),
			'min'           => 0,
			'max'           => 0,
			'layout'        => 'table',
			'button_label'  => __( 'Add Row', 'scn-acf-extra' ),
		);
	}

	/**
	 * Render the field on edit screens.
	 */
	public function render_field( $field ) {
		$sub_fields = $field['sub_fields'];
		if ( empty( $sub_fields ) ) {
			echo '<p>' . esc_html__( 'No sub-fields configured.', 'scn-acf-extra' ) . '</p>';
			return;
		}

		$value = is_array( $field['value'] ) ? $field['value'] : array();
		$rows  = max( count( $value ), (int) $field['min'] );

		$wrapper_attrs = array(
			'class'         => 'scn-repeater',
			'data-min'      => (int) $field['min'],
			'data-max'      => (int) $field['max'],
			'data-name'     => $field['name'],
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
				$sub_field['name']  = "{$field['name']}[{$index}][{$sub_field['name']}]";
				$sub_field['value'] = $row_value[ $sub['name'] ] ?? '';
				$sub_field['prefix'] = $field['prefix'];
				?>
				<td>
					<?php acf_render_field( $sub_field ); ?>
				</td>
			<?php endforeach; ?>
			<td class="scn-repeater-remove">
				<button type="button" class="button-link-delete scn-repeater-remove-btn" aria-label="<?php esc_attr_e( 'Remove row', 'scn-acf-extra' ); ?>">×</button>
			</td>
		</tr>
		<?php
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
				$cell_value = $row[ $sub['name'] ] ?? '';
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
