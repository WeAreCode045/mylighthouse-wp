<?php
/**
 * Minimal Admin hotels handler — template-based rendering only.
 * Keeps this file PHP-only to avoid parse errors during migration.
 *
 * @package Mylighthouse_Booker
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Mylighthouse_Booker_Admin_Hotels_Page
{
    private $template_dir;

    public function __construct()
    {
        $this->template_dir = MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Admin/hotels/Templates/';

        add_action('admin_init', array($this, 'handle_delete_hotel'));
        add_action('admin_init', array($this, 'handle_save_hotel'));
    }

    private function load_template($template, $args = array())
    {
        $file = $this->template_dir . $template . '.php';
        if (! file_exists($file)) {
            return; 
        }
        extract($args);
        include $file;
    }

    public function handle_delete_hotel()
    {
        if (! isset($_GET['action']) || $_GET['action'] !== 'delete-hotel') {
            return;
        }
        if (! isset($_GET['hotel']) || ! isset($_GET['_wpnonce'])) {
            return;
        }

        $hotel_id = intval($_GET['hotel']);
        if (! wp_verify_nonce($_GET['_wpnonce'], 'delete-hotel-' . $hotel_id)) {
            wp_die(__('Security check failed.', 'mylighthouse-booker'));
        }
        if (! current_user_can('manage_options')) {
            wp_die(__('You do not have permission to delete hotels.', 'mylighthouse-booker'));
        }

        if (class_exists('Mylighthouse_Booker_Hotel')) {
            Mylighthouse_Booker_Hotel::delete($hotel_id);
        }

        wp_redirect(admin_url('admin.php?page=mylighthouse-booker&content=hotels&deleted=true'));
        exit;
    }

    public function handle_save_hotel()
    {
        if (! isset($_POST['action']) || $_POST['action'] !== 'mlb_save_hotel') {
            return;
        }
        if (! isset($_POST['mlb_hotel_nonce']) || ! wp_verify_nonce($_POST['mlb_hotel_nonce'], 'mlb_save_hotel')) {
            wp_die(__('Security check failed.', 'mylighthouse-booker'));
        }
        if (! current_user_can('manage_options')) {
            wp_die(__('You do not have permission to save hotels.', 'mylighthouse-booker'));
        }

        // Delegates to models — keep logic in models during migration.
        $hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
        $hotel_name = isset($_POST['hotel_name']) ? sanitize_text_field($_POST['hotel_name']) : '';
        $hotel_external_id = isset($_POST['hotel_external_id']) ? sanitize_key($_POST['hotel_external_id']) : '';

        if (empty($hotel_name) || empty($hotel_external_id)) {
            wp_redirect(admin_url('admin.php?page=mylighthouse-booker&content=hotels&error=missing_fields'));
            exit;
        }

        if (class_exists('Mylighthouse_Booker_Hotel')) {
            if ($hotel_id > 0) {
                Mylighthouse_Booker_Hotel::update($hotel_id, array('name' => $hotel_name, 'hotel_id' => $hotel_external_id));
            } else {
                $hotel_id = Mylighthouse_Booker_Hotel::create(array('name' => $hotel_name, 'hotel_id' => $hotel_external_id));
            }
        }

        wp_redirect(admin_url('admin.php?page=mylighthouse-booker&content=hotels&updated=true'));
        exit;
    }

    public function render_page()
    {
        $hotels = array();
        if (class_exists('Mylighthouse_Booker_Hotel')) {
            $hotels = Mylighthouse_Booker_Hotel::get_all_with_rooms();
        }

        // Diagnostic logging: helpful when debugging duplicated list entries.
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            try {
                $count = is_array($hotels) ? count($hotels) : 0;
                $ids = array();
                if (is_array($hotels)) {
                    foreach ($hotels as $h) {
                        if (is_array($h) && isset($h['id'])) $ids[] = $h['id'];
                        elseif (is_object($h) && isset($h->id)) $ids[] = $h->id;
                    }
                }
                error_log("[MLB] hotels render_page: count={$count} ids=" . implode(',', $ids));
            } catch (Exception $e) {
                error_log("[MLB] hotels render_page: diagnostic logging failed: " . $e->getMessage());
            }
        }

        // If an edit context was requested (via the fragment loader), render the edit form
        $edit = isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : '';
        if (! empty($edit)) {
            // render the edit page (accepts 'new' or an integer id)
            $this->render_edit_page($edit);
            return;
        }

        $this->load_template('hotels-list', array('hotels' => $hotels));
    }

    private function render_edit_page($edit_id)
    {
        $hotel = null;
        if ($edit_id !== 'new' && class_exists('Mylighthouse_Booker_Hotel')) {
            $hotel = Mylighthouse_Booker_Hotel::get_with_rooms(intval($edit_id));
        }

        $this->load_template('hotels-edit', array('hotel' => $hotel, 'edit_id' => $edit_id));
    }
}
