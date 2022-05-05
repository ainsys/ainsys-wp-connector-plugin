<?php
/**
 * @wordpress-plugin
 * Plugin Name:       AINSYS connector
 * Plugin URI: https://app.ainsys.com/
 * Description: AINSYS connector MVP version.
 * Version:           3.0.0
 * Author:            AINSYS
 * Author URI:        https://app.ainsys.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}

define( 'AINSYS_CONNECTOR_BASENAME', plugin_basename( __FILE__ ) );

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( version_compare( PHP_VERSION, '7.2.0' ) < 0 ) {
	add_action( 'admin_notices', 'ainsys_connector_error' );
	deactivate_plugins( AINSYS_CONNECTOR_BASENAME );
}

define( 'AINSYS_CONNECTOR_VERSION', '3.0' );
define( 'AINSYS_CONNECTOR_PLUGIN', __FILE__ );
define( 'AINSYS_CONNECTOR_PLUGIN_DIR', untrailingslashit( dirname( AINSYS_CONNECTOR_PLUGIN ) ) );
define( 'AINSYS_CONNECTOR_TEXTDOMAIN', 'ainsys_connector' );
define( 'AINSYS_CONNECTOR_URL', plugin_dir_url( __FILE__ ) );


include_once AINSYS_CONNECTOR_PLUGIN_DIR . '/includes/ainsys_settings.php';
include_once AINSYS_CONNECTOR_PLUGIN_DIR . '/includes/ainsys_html.php';
include_once AINSYS_CONNECTOR_PLUGIN_DIR . '/includes/ainsys_core.php';
include_once AINSYS_CONNECTOR_PLUGIN_DIR . '/includes/ainsys_webhook_listener.php';
include_once AINSYS_CONNECTOR_PLUGIN_DIR . '/includes/utm_hendler.php';

add_action( 'plugins_loaded', function () {
	require_once AINSYS_CONNECTOR_PLUGIN_DIR . '/includes/wpcf7_service.php';
} );

//////////////////////
add_action( 'init', 'ainsys_connector_load_textdomain' );
function ainsys_connector_load_textdomain() {
	$locale = apply_filters( 'plugin_locale', get_locale(), AINSYS_CONNECTOR_TEXTDOMAIN );
	unload_textdomain( AINSYS_CONNECTOR_TEXTDOMAIN );
	load_textdomain( AINSYS_CONNECTOR_TEXTDOMAIN, WP_LANG_DIR . '/plugins/ainsys_connector-' . $locale . '.mo' );
	load_plugin_textdomain( AINSYS_CONNECTOR_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function ainsys_connector_error() {
	$class    = 'notice notice-error is-dismissible';
	$message1 = __( 'Upgrade your PHP version. Minimum version - 7.2+. Your PHP version ', AINSYS_CONNECTOR_TEXTDOMAIN );
	$message2 = __( '! If you don\'t know how to upgrade PHP version, just ask in your hosting provider! If you can\'t upgrade - delete this plugin!', AINSYS_CONNECTOR_TEXTDOMAIN );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message1 . PHP_VERSION . $message2 ) );
}
