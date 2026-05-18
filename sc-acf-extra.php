<?php
/**
 * Plugin Name: SC ACF Extra
 * Plugin URI:  https://github.com/starcraft-j/sc-acf-extra
 * Description: ACF 無料版に Repeater など Pro 相当のフィールドを追加する拡張プラグイン。Pro 互換のメタ保存形式で、後から ACF Pro へ無断データ移行可能。
 * Version:     0.5.1
 * Author:      starcraft-n
 * Author URI:  https://starcraft-n.co.jp
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sc-acf-extra
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SC_ACF_EXTRA_VERSION', '0.5.1' );
define( 'SC_ACF_EXTRA_PATH', plugin_dir_path( __FILE__ ) );
define( 'SC_ACF_EXTRA_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load translations.
 */
add_action( 'init', static function () {
	load_plugin_textdomain( 'sc-acf-extra', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

/**
 * Bail out (with admin notice) if ACF is not active.
 */
add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'ACF' ) ) {
		add_action( 'admin_notices', static function () {
			$msg = esc_html__( 'SC ACF Extra を動作させるには Advanced Custom Fields (無料版または Pro) が有効化されている必要があります。', 'sc-acf-extra' );
			echo '<div class="notice notice-error"><p>' . $msg . '</p></div>';
		} );
		return;
	}
	require_once SC_ACF_EXTRA_PATH . 'includes/trait-sub-fields-ui.php';
	require_once SC_ACF_EXTRA_PATH . 'fields/class-sc-repeater.php';
	require_once SC_ACF_EXTRA_PATH . 'fields/class-sc-flexible.php';
} );

/**
 * Register field types with ACF.
 *
 * ACF fires `acf/include_field_types` after the core field types are loaded.
 * We hook in there so our fields are available everywhere `acf_get_field_type()` is used.
 */
add_action( 'acf/include_field_types', static function ( $version = 0 ) {
	if ( class_exists( 'SC_ACF_Repeater' ) ) {
		new SC_ACF_Repeater();
	}
	if ( class_exists( 'SC_ACF_Flexible' ) ) {
		new SC_ACF_Flexible();
	}
} );
