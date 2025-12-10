<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Mylighthouse_Booker_Admin_Assets {
    /**
     * Return the canonical fragment selector map used by the admin fragment endpoint
     * keys are logical fragment identifiers, values are the class names to look for
     * (without the leading dot). Centralizing here keeps server and localized JS in sync.
     *
     * @return array
     */
    public static function get_fragment_selector_map() {
        return array(
            'settings' => 'mlb-admin-sections',
            'hotels' => 'mlb-hotels-wrap',
            'settings-wrap' => 'mlb-admin-wrap',
            'tools' => 'mlb-tools-wrap',
            'dashboard' => 'mlb-dashboard-wrap',
            'wrap' => 'wrap',
        );
    }
    public function __construct() {}
    public function enqueue_admin_assets( $hook ) {
        // Only load assets on our plugin admin pages. $hook for the top-level
        // menu page is typically 'toplevel_page_mylighthouse-booker'. If this
        // enqueue method is called on other admin screens, bail early to avoid
        // leaking styles into unrelated admin pages.
        if ( isset( $hook ) && $hook !== 'toplevel_page_mylighthouse-booker' && strpos( $hook, 'mylighthouse-booker' ) === false ) {
            return;
        }
        // Prefer a single canonical admin stylesheet for the plugin
        $admin_style = MYLIGHTHOUSE_BOOKER_PLUGIN_URL . 'assets/css/backend/admin-style.css';
        // Register and enqueue the canonical admin-style only
        wp_register_style( 'mlb-admin-style', $admin_style, array(), MYLIGHTHOUSE_BOOKER_VERSION );
        wp_enqueue_style( 'mlb-admin-style' );

        // Main dashboard loader (uses dashboard.js which contains the fragment loader)
        wp_register_script( 'mlb-admin-dashboard-js', MYLIGHTHOUSE_BOOKER_PLUGIN_URL . 'assets/js/backend/dashboard.js', array( 'jquery' ), '1.0.0', true );
        wp_enqueue_script( 'mlb-admin-dashboard-js' );

        // per-feature scripts
        wp_register_script( 'mlb-hotels-js', MYLIGHTHOUSE_BOOKER_PLUGIN_URL . 'assets/js/backend/hotels.js', array( 'jquery' ), '1.0.0', true );
        wp_enqueue_script( 'mlb-hotels-js' );

        wp_register_script( 'mlb-tools-js', MYLIGHTHOUSE_BOOKER_PLUGIN_URL . 'assets/js/backend/tools.js', array( 'jquery' ), '1.0.0', true );
        wp_enqueue_script( 'mlb-tools-js' );

        wp_register_script( 'mlb-settings-js', MYLIGHTHOUSE_BOOKER_PLUGIN_URL . 'assets/js/backend/settings.js', array( 'jquery' ), '1.0.0', true );
        wp_enqueue_script( 'mlb-settings-js' );

        // Ensure WordPress media scripts are available for the settings upload control
        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }

        // Settings Translations UI has been removed; translations are provided
        // via gettext (.po/.mo). No admin translation assets are enqueued.

        // Build fragment mapping from the canonical selector map so PHP and JS stay coordinated.
        $selector_map = self::get_fragment_selector_map();
        $fragment_keys = array();
        $fragment_selectors = array();
        foreach ($selector_map as $k => $class) {
            $fragment_keys[ $k ] = $k;
            // Expose a full CSS selector for client-side DOM extraction (prefix with dot)
            $fragment_selectors[ $k ] = '.' . $class;
        }

        wp_localize_script( 'mlb-admin-dashboard-js', 'mlb_admin_params', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'admin_url' => admin_url(),
            'nonce' => wp_create_nonce( 'mlb_admin_nonce' ),
            // Logical fragment keys the AJAX endpoint understands
            'fragment_keys' => $fragment_keys,
            // CSS selectors derived from the same selector map for client-side extraction
            'fragment_selectors' => $fragment_selectors,
            // Admin UI messages (translatable)
            'msg_preparing' => __('Preparing...', 'mylighthouse-booker'),
            'msg_export_failed' => __('Export failed', 'mylighthouse-booker'),
            'msg_export_request_failed' => __('Export request failed', 'mylighthouse-booker'),
            'msg_download_export' => __('Download Export', 'mylighthouse-booker'),
            'msg_export_ready' => __('Export complete.', 'mylighthouse-booker'),
            'msg_select_json_import' => __('Please select a JSON file to import.', 'mylighthouse-booker'),
            'msg_invalid_json' => __('Invalid JSON file.', 'mylighthouse-booker'),
            'msg_import_failed' => __('Import failed', 'mylighthouse-booker'),
            'msg_importing' => __('Importing...', 'mylighthouse-booker'),
            'msg_import_json' => __('Import JSON', 'mylighthouse-booker'),
            'msg_import_request_failed' => __('Import request failed', 'mylighthouse-booker'),
            'msg_previewing' => __('Previewing...', 'mylighthouse-booker'),
            'msg_preview_failed' => __('Preview failed', 'mylighthouse-booker'),
            'msg_preview_request_failed' => __('Preview request failed', 'mylighthouse-booker'),
            'msg_preview_import' => __('Preview Import', 'mylighthouse-booker'),
        ) );
    }
}
?>