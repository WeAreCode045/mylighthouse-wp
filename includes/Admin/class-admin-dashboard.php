<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Mylighthouse_Booker_Admin_Dashboard {
    public function register_admin_menu() {
        add_menu_page( 'MyLighthouse Booker', 'MyLighthouse', 'manage_options', 'mylighthouse-booker', array( $this, 'render_dashboard' ), 'dashicons-admin-home', 26 );
    }

    /**
     * Render the unified dashboard page.
     * The main content area will contain a server-rendered fragment for the
     * requested section (defaults to 'hotels' for the first load) so the
     * dashboard is usable even before the JS fragment loader runs.
     */
    public function render_dashboard() {
        $requested = isset($_GET['content']) ? sanitize_text_field($_GET['content']) : ''; 
        // Normalize keys like 'settings-general' -> 'settings'
        if (strpos($requested, 'settings') === 0) {
            $requested = 'settings';
        } elseif (strpos($requested, 'hotels') === 0) {
            $requested = 'hotels';
        } elseif (strpos($requested, 'tools') === 0) {
            $requested = 'tools';
        }

        if (empty($requested)) {
            // Default to hotels listing for first visit
            $requested = 'hotels';
        }

        ?>
        <div class="mlb-admin-wrap">
            <div class="mlb-admin-layout">
                <aside class="mlb-admin-sidebar">
                    <nav class="mlb-sidebar-nav">
                        <div class="mlb-sidebar-item">
                            <button type="button" class="mlb-sidebar-link <?php echo $requested === 'hotels' ? 'active' : ''; ?>" data-page="mylighthouse-booker" data-content="hotels">
                                <?php esc_html_e('Hotels','mylighthouse-booker'); ?>
                            </button>
                        </div>
                        <div class="mlb-sidebar-item">
                            <button type="button" class="mlb-sidebar-link <?php echo $requested === 'settings' ? 'active' : ''; ?>" data-page="mylighthouse-booker" data-content="settings-general">
                                <?php esc_html_e('Settings','mylighthouse-booker'); ?>
                            </button>
                        </div>
                        <!-- Text & Translations removed — translations now come from PO/MO files -->
                        <div class="mlb-sidebar-item">
                            <button type="button" class="mlb-sidebar-link <?php echo $requested === 'tools' ? 'active' : ''; ?>" data-page="mylighthouse-booker" data-content="tools">
                                <?php esc_html_e('Tools','mylighthouse-booker'); ?>
                            </button>
                        </div>
                    </nav>
                </aside>

                <main class="mlb-admin-main">
                    <div id="mlb-dashboard-main" class="mlb-admin-sections">
                        <?php
                        // Server-side initial render of the requested section so the page is useful without extra round-trips.
                        switch ($requested) {
                            case 'hotels':
                                if (class_exists('Mylighthouse_Booker_Admin_Hotels_Page')) {
                                    $p = new Mylighthouse_Booker_Admin_Hotels_Page();
                                    $p->render_page();
                                } else {
                                    echo '<div class="mlb-dashboard-placeholder"><p>' . esc_html__('Hotels page unavailable.', 'mylighthouse-booker') . '</p></div>';
                                }
                                break;
                            case 'tools':
                                if (class_exists('Mylighthouse_Booker_Admin_Tools_Page')) {
                                    $p = new Mylighthouse_Booker_Admin_Tools_Page();
                                    $p->render_page();
                                } elseif (class_exists('Mylighthouse_Booker_Admin_Tools') && method_exists('Mylighthouse_Booker_Admin_Tools', 'render_page')) {
                                    $t = new Mylighthouse_Booker_Admin_Tools();
                                    $t->render_page();
                                } else {
                                    echo '<div class="mlb-dashboard-placeholder"><p>' . esc_html__('Tools page unavailable.', 'mylighthouse-booker') . '</p></div>';
                                }
                                break;
                            case 'settings':
                                if (class_exists('Mylighthouse_Booker_Admin_Settings_Page')) {
                                    $p = new Mylighthouse_Booker_Admin_Settings_Page();
                                    $p->render_page();
                                } elseif (class_exists('Mylighthouse_Booker_Admin_Settings') && method_exists('Mylighthouse_Booker_Admin_Settings', 'render_page')) {
                                    $s = new Mylighthouse_Booker_Admin_Settings();
                                    $s->render_page();
                                } else {
                                    echo '<div class="mlb-dashboard-placeholder"><p>' . esc_html__('Settings page unavailable.', 'mylighthouse-booker') . '</p></div>';
                                }
                                break;
                            default:
                                echo '<div class="mlb-dashboard-placeholder"><p>' . esc_html__('Select a section from the sidebar.', 'mylighthouse-booker') . '</p></div>';
                                break;
                        }
                        ?>
                    </div>
                </main>
            </div>
        </div>
        <?php
        // Small admin-side templates for JS to clone (not visible by default)
        ?>
        <template id="mlb-admin-notice-template">
            <div class="mlb-admin-notice"><p></p></div>
        </template>
        <?php
    }
}

?>