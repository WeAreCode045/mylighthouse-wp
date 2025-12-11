<?php

/**
 * Plugin Name: mylighthouse-booker
 * Plugin URI: https://code045.nl/
 * Description:Wordpress Booking Plugin for MyLighthouse Booking Engine (formely Cubilis). Integrate the Booking engine into your Wordpress Website. 
 * Author URI: https://code045.nl/
 * Version: 1.2.2
 * Requires at least: 6.0
 * Tested up to: 6.7
 *
 * Text Domain: mylighthouse-booker
 * Domain Path: /languages/
 *
 * @package Code045_Mylighthouse_Booker
 */

if (! defined('ABSPATH')) {
	exit;
}

// Define MYLIGHTHOUSE-BOOKER_PLUGIN_FILE.
if (! defined('MYLIGHTHOUSE_BOOKER_PLUGIN_FILE')) {
	define('MYLIGHTHOUSE_BOOKER_PLUGIN_FILE', __FILE__);
}

// Define MYLIGHTHOUSE-BOOKER_ABSPATH.
if (! defined('MYLIGHTHOUSE_BOOKER_ABSPATH')) {
	define('MYLIGHTHOUSE_BOOKER_ABSPATH', dirname(__FILE__) . '/');
}

// Define MYLIGHTHOUSE_BOOKER_PLUGIN_URL.
if (! defined('MYLIGHTHOUSE_BOOKER_PLUGIN_URL')) {
	define('MYLIGHTHOUSE_BOOKER_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Define MYLIGHTHOUSE_BOOKER_VERSION.
if (! defined('MYLIGHTHOUSE_BOOKER_VERSION')) {
	define('MYLIGHTHOUSE_BOOKER_VERSION', '1.2.1');
}

// Note: plugin translations removed — strings are rendered as static English.

/**
 * Retrieve a translated text for the plugin.
 *
 * Uses gettext first (via the provided default string), then falls back to
 * an optional DB override stored in the `mlb_texts` option (preserves
 * backwards-compatibility). This lets translations live in .po/.mo files
 * while still allowing site-specific overrides if needed.
 *
 * @param string $key A stable key for the text (used by the old DB option).
 * @param string $default The English/default string used as the translation msgid.
 * @return string
 */
// Translation helper removed; strings are rendered directly in templates.
// Include the plugin loader.
if (! class_exists('Mylighthouse_Booker_Plugin_Loader')) {
	include_once dirname(__FILE__) . '/includes/class-plugin-loader.php';
}

// Initialize the plugin.
if (! function_exists('mylighthouse_booker')) {
	/**
	 * Returns the main instance of the plugin.
	 *
	 * @since  1.0.0
	 * @return Mylighthouse_Booker_Plugin_Loader
	 */
	function mylighthouse_booker()
	{
		return new Mylighthouse_Booker_Plugin_Loader();
	}
}

mylighthouse_booker();

require_once __DIR__ . '/includes/Admin/class-admin-assets.php';
require_once __DIR__ . '/includes/Admin/class-admin-dashboard.php';
require_once __DIR__ . '/includes/Admin/class-admin-hotels.php';
require_once __DIR__ . '/includes/Admin/class-admin-tools.php';
require_once __DIR__ . '/includes/Admin/class-admin-settings.php';
require_once __DIR__ . '/includes/Admin/hotels/class-admin-hotel-edit.php';
require_once __DIR__ . '/includes/Admin/hotels/class-admin-hotel-rooms.php';
require_once __DIR__ . '/includes/Admin/hotels/class-admin-hotel-specials.php';

add_action( 'admin_menu', function() {
    $dash = new Mylighthouse_Booker_Admin_Dashboard();
    $dash->register_admin_menu();
} );

add_action( 'admin_enqueue_scripts', function( $hook ) {
    $assets = new Mylighthouse_Booker_Admin_Assets();
    $assets->enqueue_admin_assets( $hook );
} );

/**
 * Activation hook: ensure database tables exist and create them if missing.
 */
function mylighthouse_booker_activate()
{
	global $wpdb;

	// Include database manager if not loaded
	if (! class_exists('Mylighthouse_Booker_Database_Manager')) {
		require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Database/class-database-manager.php';
	}

	$required_tables = array(
		$wpdb->prefix . 'mlb_hotels',
		$wpdb->prefix . 'mlb_rooms',
		$wpdb->prefix . 'mlb_specials',
	);

	$missing = false;
	foreach ($required_tables as $table) {
		$res = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
		if ($res === null || $res === '') {
			$missing = true;
			break;
		}
	}

	if ($missing) {
		$manager = new Mylighthouse_Booker_Database_Manager();
		// create_tables will use dbDelta and is safe to call multiple times
		$manager->create_tables();
	}
}

register_activation_hook(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE, 'mylighthouse_booker_activate');
