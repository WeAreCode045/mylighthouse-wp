<?php

/**
 * AJAX handlers
 *
 * @package StandaloneTech
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Ajax_Handlers
 */
class Mylighthouse_Booker_Ajax_Handlers
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
			// AJAX add item (room/special)
			add_action('wp_ajax_mlb_add_item', array($this, 'ajax_add_item'));
			// AJAX delete item (room/special)
			add_action('wp_ajax_mlb_delete_item', array($this, 'ajax_delete_item'));
			// AJAX update item (room/special)
			add_action('wp_ajax_mlb_update_item', array($this, 'ajax_update_item'));
			// AJAX partial update for hotel fields (inline edits)
			add_action('wp_ajax_mlb_update_hotel_field', array($this, 'ajax_update_hotel_field'));

		add_action('wp_ajax_cqb_add_hotel', array($this, 'add_hotel'));
		add_action('wp_ajax_cqb_remove_hotel', array($this, 'remove_hotel'));
			// AJAX delete for a single hotel (SPA-friendly)
			add_action('wp_ajax_mlb_delete_hotel', array($this, 'delete_hotel'));


		// AJAX endpoint for returning admin page fragments to the dashboard loader
		add_action('wp_ajax_mlb_get_admin_fragment', array($this, 'get_admin_fragment'));
		// AJAX save for hotel (SPA-friendly)
		add_action('wp_ajax_mlb_save_hotel', array($this, 'ajax_save_hotel'));
		// AJAX save hotels order
		add_action('wp_ajax_mlb_save_hotels_order', array($this, 'ajax_save_hotels_order'));
		// Handle settings form posted to admin.php?action=mlb_save_admin_settings
		add_action('admin_post_mlb_save_admin_settings', array($this, 'handle_save_admin_settings'));
		// AJAX save for settings (SPA-friendly)
		add_action('wp_ajax_mlb_save_admin_settings', array($this, 'ajax_save_admin_settings'));

		// Legacy DB-based Text & Translations have been removed; translations
		// are now provided via gettext (.po/.mo) and wp_set_script_translations.
		// Ensure tools AJAX endpoints are available even when the Tools page class hasn't been instantiated
		add_action('wp_ajax_mlb_tools_check_tables', array($this, 'proxy_tools_check_tables'));
		add_action('wp_ajax_mlb_tools_migrate_hotels', array($this, 'proxy_tools_migrate_hotels'));
		add_action('wp_ajax_mlb_tools_update_schema', array($this, 'ajax_tools_update_schema'));
		add_action('wp_ajax_mlb_tools_export', array($this, 'proxy_tools_export'));
		add_action('wp_ajax_mlb_tools_import', array($this, 'proxy_tools_import'));
		add_action('wp_ajax_mlb_tools_preview_import', array($this, 'proxy_tools_preview_import'));
	}

	/**
	 * AJAX: Save hotels custom order
	 */
	public function ajax_save_hotels_order()
	{
		if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'mlb_admin_nonce')) {
			wp_send_json_error(array('message' => 'Security check failed'));
		}

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Insufficient permissions'));
		}

		$order = array();
		if (isset($_POST['order'])) {
			if (is_array($_POST['order'])) {
				$order = $_POST['order'];
			} elseif (is_string($_POST['order'])) {
				$decoded = json_decode(wp_unslash($_POST['order']), true);
				if (is_array($decoded)) {
					$order = $decoded;
				}
			}
		}
		if (empty($order)) {
			wp_send_json_error(array('message' => 'Missing order data'));
		}

		if (! class_exists('Mylighthouse_Booker_Hotel')) {
			wp_send_json_error(array('message' => 'Hotel model not available'));
		}

		// load legacy option map (external id => name) to create missing DB rows if needed
		$legacy_hotels = get_option('mlb_hotels', array());
		$legacy_map = array();
		if (!empty($legacy_hotels) && is_array($legacy_hotels)) {
			foreach ($legacy_hotels as $lh) {
				if (is_array($lh)) {
					$key = isset($lh['id']) ? (string)$lh['id'] : (isset($lh['hotel_id']) ? (string)$lh['hotel_id'] : '');
					if ($key !== '') $legacy_map[$key] = isset($lh['name']) ? $lh['name'] : '';
				}
			}
		}

		$failed = array();
		$position = 0;
		foreach ($order as $raw) {
			$position++;
			$raw = (string)$raw;
			$found_id = 0;

			// If numeric id provided, try direct lookup
			if (ctype_digit($raw) && intval($raw) > 0) {
				$maybe = Mylighthouse_Booker_Hotel::get_by_id(intval($raw));
				if (!empty($maybe) && isset($maybe['id'])) {
					$found_id = intval($maybe['id']);
				}
			}

			// If not found, try matching by external hotel_id
			if (empty($found_id)) {
				$maybe = Mylighthouse_Booker_Hotel::get_by_hotel_id($raw);
				if (!empty($maybe) && isset($maybe['id'])) {
					$found_id = intval($maybe['id']);
				}
			}

			// If still not found, try to create from legacy option (if available)
			if (empty($found_id) && isset($legacy_map[$raw])) {
				$name = $legacy_map[$raw];
				$new_id = Mylighthouse_Booker_Hotel::create(array('name' => $name ? $name : $raw, 'hotel_id' => $raw, 'display_order' => $position));
				if ($new_id) {
					$found_id = intval($new_id);
				}
			}

			if (empty($found_id)) {
				$failed[] = $raw;
				continue;
			}

			$ok = Mylighthouse_Booker_Hotel::update($found_id, array('display_order' => $position));
			if (empty($ok)) {
				$failed[] = $raw;
			}
		}

		if (!empty($failed)) {
			wp_send_json_error(array('message' => 'Failed to update some items', 'failed' => $failed));
		}

		wp_send_json_success(array('message' => 'Order saved'));
	}

	/**
	 * Handle saving general admin settings posted from the settings form.
	 */
	public function handle_save_admin_settings()
	{
		if (! isset($_POST['mlb_admin_settings_nonce']) || ! wp_verify_nonce($_POST['mlb_admin_settings_nonce'], 'mlb_save_admin_settings')) {
			wp_die(__('Security check failed.', 'mylighthouse-booker'));
		}

		if (! current_user_can('manage_options')) {
			wp_die(__('You do not have permission to save settings.', 'mylighthouse-booker'));
		}

		// Sanitize and save options
		$booking_page = isset($_POST['mlb_booking_page_url']) ? sanitize_text_field($_POST['mlb_booking_page_url']) : '';
		$valid_modes = array('modal','booking_page','redirect_engine');
		$display_mode_mobile = isset($_POST['mlb_display_mode_mobile']) && in_array($_POST['mlb_display_mode_mobile'], $valid_modes) ? sanitize_text_field($_POST['mlb_display_mode_mobile']) : 'modal';
		$display_mode_tablet = isset($_POST['mlb_display_mode_tablet']) && in_array($_POST['mlb_display_mode_tablet'], $valid_modes) ? sanitize_text_field($_POST['mlb_display_mode_tablet']) : 'modal';
		$display_mode_desktop = isset($_POST['mlb_display_mode_desktop']) && in_array($_POST['mlb_display_mode_desktop'], $valid_modes) ? sanitize_text_field($_POST['mlb_display_mode_desktop']) : 'modal';
		$spinner_image_url = isset($_POST['mlb_spinner_image_url']) ? esc_url_raw(trim($_POST['mlb_spinner_image_url'])) : '';

		update_option('mlb_booking_page_url', $booking_page);
		update_option('mlb_display_mode_mobile', $display_mode_mobile);
		update_option('mlb_display_mode_tablet', $display_mode_tablet);
		update_option('mlb_display_mode_desktop', $display_mode_desktop);
		update_option('mlb_spinner_image_url', $spinner_image_url);

		// Redirect back to the settings fragment inside the dashboard
		$redirect = admin_url('admin.php?page=mylighthouse-booker&content=settings&updated=1');
		wp_safe_redirect($redirect);
		exit;
	}

	/**
	 * AJAX save handler for settings form (returns JSON)
	 */
	public function ajax_save_admin_settings()
	{
		if (! isset($_POST['mlb_admin_settings_nonce']) || ! wp_verify_nonce($_POST['mlb_admin_settings_nonce'], 'mlb_save_admin_settings')) {
			wp_send_json_error(array('message' => 'Security check failed'));
		}

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Insufficient permissions'));
		}

		$booking_page = isset($_POST['mlb_booking_page_url']) ? sanitize_text_field($_POST['mlb_booking_page_url']) : '';
		$valid_modes = array('modal','booking_page','redirect_engine');
		$display_mode_mobile = isset($_POST['mlb_display_mode_mobile']) && in_array($_POST['mlb_display_mode_mobile'], $valid_modes) ? sanitize_text_field($_POST['mlb_display_mode_mobile']) : 'modal';
		$display_mode_tablet = isset($_POST['mlb_display_mode_tablet']) && in_array($_POST['mlb_display_mode_tablet'], $valid_modes) ? sanitize_text_field($_POST['mlb_display_mode_tablet']) : 'modal';
		$display_mode_desktop = isset($_POST['mlb_display_mode_desktop']) && in_array($_POST['mlb_display_mode_desktop'], $valid_modes) ? sanitize_text_field($_POST['mlb_display_mode_desktop']) : 'modal';
		$spinner_image_url = isset($_POST['mlb_spinner_image_url']) ? esc_url_raw(trim($_POST['mlb_spinner_image_url'])) : '';

		update_option('mlb_booking_page_url', $booking_page);
		update_option('mlb_display_mode_mobile', $display_mode_mobile);
		update_option('mlb_display_mode_tablet', $display_mode_tablet);
		update_option('mlb_display_mode_desktop', $display_mode_desktop);
		update_option('mlb_spinner_image_url', $spinner_image_url);

		wp_send_json_success(array('message' => 'Settings saved'));
	}

	// Texts & translations persistence removed — rely on PO/MO files and WP translations.

	/**
	 * AJAX handler to save a hotel (create/update) including rooms/specials.
	 */
	public function ajax_save_hotel()
	{
		// Accept either the specific hotel nonce or the general admin nonce
		$nonce_ok = false;
		if (isset($_POST['mlb_hotel_nonce']) && wp_verify_nonce($_POST['mlb_hotel_nonce'], 'mlb_save_hotel')) {
			$nonce_ok = true;
		} elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'mlb_admin_nonce')) {
			$nonce_ok = true;
		}
		if (! $nonce_ok) {
			wp_send_json_error(array('message' => 'Security check failed'));
		}

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Insufficient permissions'));
		}

		$hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
		$hotel_name = isset($_POST['hotel_name']) ? sanitize_text_field($_POST['hotel_name']) : '';
		$hotel_external_id = isset($_POST['hotel_external_id']) ? sanitize_text_field($_POST['hotel_external_id']) : '';

		if (empty($hotel_name) || empty($hotel_external_id)) {
			wp_send_json_error(array('message' => 'Missing required fields'));
		}

		if (! class_exists('Mylighthouse_Booker_Hotel')) {
			wp_send_json_error(array('message' => 'Hotel model not available'));
		}

		if ($hotel_id > 0) {
			$ok = Mylighthouse_Booker_Hotel::update($hotel_id, array('name' => $hotel_name, 'hotel_id' => $hotel_external_id));
			if (! $ok) wp_send_json_error(array('message' => 'Failed to update hotel'));
		} else {
			$hotel_id = Mylighthouse_Booker_Hotel::create(array('name' => $hotel_name, 'hotel_id' => $hotel_external_id));
			if (! $hotel_id) wp_send_json_error(array('message' => 'Failed to create hotel'));
		}

		// Handle rooms (if provided)
		if (isset($_POST['rooms']) && is_array($_POST['rooms'])) {
			foreach ($_POST['rooms'] as $r) {
				$r_id = isset($r['id']) ? intval($r['id']) : 0;
				$r_name = isset($r['name']) ? sanitize_text_field($r['name']) : '';
				$r_external = isset($r['room_id']) ? sanitize_text_field($r['room_id']) : '';
				if (empty($r_name) || empty($r_external)) continue;
				if ($r_id > 0 && class_exists('Mylighthouse_Booker_Room')) {
					Mylighthouse_Booker_Room::update($r_id, array('name' => $r_name, 'room_id' => $r_external, 'hotel_id' => $hotel_id));
				} elseif (class_exists('Mylighthouse_Booker_Room')) {
					Mylighthouse_Booker_Room::create(array('hotel_id' => $hotel_id, 'name' => $r_name, 'room_id' => $r_external));
				}
			}
		}

		// Handle specials (if provided)
		if (isset($_POST['specials']) && is_array($_POST['specials'])) {
			foreach ($_POST['specials'] as $s) {
				$s_id = isset($s['id']) ? intval($s['id']) : 0;
				$s_name = isset($s['name']) ? sanitize_text_field($s['name']) : '';
				$s_external = isset($s['special_id']) ? sanitize_text_field($s['special_id']) : '';
				if (empty($s_name) || empty($s_external)) continue;
				if ($s_id > 0 && class_exists('Mylighthouse_Booker_Special')) {
					Mylighthouse_Booker_Special::update($s_id, array('name' => $s_name, 'special_id' => $s_external, 'hotel_id' => $hotel_id));
				} elseif (class_exists('Mylighthouse_Booker_Special')) {
					Mylighthouse_Booker_Special::create(array('hotel_id' => $hotel_id, 'name' => $s_name, 'special_id' => $s_external));
				}
			}
		}

		// Prepare rendered hotel row HTML for in-place updates if templates are available
		$rendered_html = '';
		try {
			if (class_exists('Mylighthouse_Booker_Hotel')) {
				$hotel_full = Mylighthouse_Booker_Hotel::get_with_rooms($hotel_id);
				if ($hotel_full) {
					$template = MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Admin/hotels/Templates/hotel-row.php';
					if (file_exists($template)) {
						// expose $hotel variable for the template
						$hotel = $hotel_full;
						ob_start();
						include $template;
						$rendered_html = ob_get_clean();
					}
				}
			}
		} catch (\Throwable $e) {
			// ignore rendering errors but log when WP_DEBUG is enabled
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('mlb_ajax_save_hotel render error: ' . $e->getMessage());
			}
		}

		$response = array('message' => 'Hotel saved', 'hotel_id' => $hotel_id, 'html' => $rendered_html);
		// Include the hotel object for richer client-side updates if available
		if (! empty($hotel_full) && is_array($hotel_full)) {
			$response['hotel'] = $hotel_full;
		}

		wp_send_json_success($response);
	}

	/**
	 * AJAX handler to partially update hotel fields (name or external id) for inline edits.
	 */
	public function ajax_update_hotel_field()
	{
		$nonce_ok = false;
		if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'mlb_admin_nonce')) {
			$nonce_ok = true;
		}
		if (! $nonce_ok) {
			wp_send_json_error(array('message' => 'Security check failed'));
		}

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Insufficient permissions'));
		}

		$hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
		if (! $hotel_id) {
			wp_send_json_error(array('message' => 'Missing hotel id'));
		}

		$updates = array();
		if (isset($_POST['hotel_name'])) {
			$updates['name'] = sanitize_text_field($_POST['hotel_name']);
		}
		if (isset($_POST['hotel_external_id'])) {
			$updates['hotel_id'] = sanitize_text_field($_POST['hotel_external_id']);
		}

		if (empty($updates)) {
			wp_send_json_error(array('message' => 'No fields to update'));
		}

		if (! class_exists('Mylighthouse_Booker_Hotel')) {
			wp_send_json_error(array('message' => 'Hotel model not available'));
		}

		$ok = Mylighthouse_Booker_Hotel::update($hotel_id, $updates);
		if (! $ok) {
			wp_send_json_error(array('message' => 'Failed to update hotel'));
		}

		// Optionally render hotel row HTML for client-side replacement
		$rendered_html = '';
		try {
			$hotel_full = Mylighthouse_Booker_Hotel::get_with_rooms($hotel_id);
			if ($hotel_full) {
				$template = MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Admin/hotels/Templates/hotel-row.php';
				if (file_exists($template)) {
					$hotel = $hotel_full;
					ob_start();
					include $template;
					$rendered_html = ob_get_clean();
				}
			}
		} catch (\Throwable $e) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('mlb_ajax_update_hotel_field render error: ' . $e->getMessage());
			}
		}

		$response = array('message' => 'Hotel updated', 'hotel_id' => $hotel_id, 'html' => $rendered_html);
		wp_send_json_success($response);
	}


	/**
	 * Proxy handler to call the Tools page AJAX check when that class isn't instantiated.
	 */
	public function proxy_tools_check_tables()
	{
		if (! class_exists('Mylighthouse_Booker_Admin_Tools_Page')) {
			wp_send_json_error(array('message' => 'Tools handler not available'));
		}

		$tools = new Mylighthouse_Booker_Admin_Tools_Page();
		if (method_exists($tools, 'ajax_check_tables')) {
			return $tools->ajax_check_tables();
		}

		wp_send_json_error(array('message' => 'Tools handler not available'));
	}

	public function proxy_tools_export()
	{
		if (! class_exists('Mylighthouse_Booker_Admin_Tools_Page')) {
			wp_send_json_error(array('message' => 'Tools handler not available'));
		}

		$tools = new Mylighthouse_Booker_Admin_Tools_Page();
		if (method_exists($tools, 'ajax_export_hotels')) {
			return $tools->ajax_export_hotels();
		}

		wp_send_json_error(array('message' => 'Tools handler not available'));
	}

	public function proxy_tools_import()
	{
		if (! class_exists('Mylighthouse_Booker_Admin_Tools_Page')) {
			wp_send_json_error(array('message' => 'Tools handler not available'));
		}

		$tools = new Mylighthouse_Booker_Admin_Tools_Page();
		if (method_exists($tools, 'ajax_import_hotels')) {
			return $tools->ajax_import_hotels();
		}

		wp_send_json_error(array('message' => 'Tools handler not available'));
	}

	public function proxy_tools_preview_import()
	{
		if (! class_exists('Mylighthouse_Booker_Admin_Tools_Page')) {
			wp_send_json_error(array('message' => 'Tools handler not available'));
		}

		$tools = new Mylighthouse_Booker_Admin_Tools_Page();
		if (method_exists($tools, 'ajax_preview_import')) {
			return $tools->ajax_preview_import();
		}

		wp_send_json_error(array('message' => 'Tools handler not available'));
	}

	/**
	 * Proxy for tools migrate hotels action
	 */
	public function proxy_tools_migrate_hotels()
	{
		if (! class_exists('Mylighthouse_Booker_Database_Manager')) {
			wp_send_json_error(array('message' => 'Database manager not available'));
		}

		$db_manager = new Mylighthouse_Booker_Database_Manager();
		if (method_exists($db_manager, 'migrate_data')) {
			$db_manager->migrate_data();
			wp_send_json_success(array('message' => 'Migration completed successfully'));
		}

		wp_send_json_error(array('message' => 'Migration method not available'));
	}

	/**
	 * AJAX: Re-run dbDelta to add any new table columns without dropping data
	 */
	public function ajax_tools_update_schema()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (empty($nonce) && isset($_POST['mlb_tools_nonce'])) {
			$nonce = sanitize_text_field(wp_unslash($_POST['mlb_tools_nonce']));
		}
		if (! $nonce || ! wp_verify_nonce($nonce, 'mlb_tools_action')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'mylighthouse-booker')));
		}

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'mylighthouse-booker')));
		}

		if (! class_exists('Mylighthouse_Booker_Database_Manager')) {
			require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Database/class-database-manager.php';
		}

		$db_manager = new Mylighthouse_Booker_Database_Manager();
		// Run dbDelta via create_tables to ensure schema changes are applied
		$db_manager->create_tables();
		update_option('mlb_db_version', Mylighthouse_Booker_Database_Manager::DB_VERSION);

		wp_send_json_success(array(
			'message' => __('Database tables have been updated. New columns are available immediately.', 'mylighthouse-booker'),
			'db_version' => Mylighthouse_Booker_Database_Manager::DB_VERSION,
		));
	}



	/**
	 * Return a fragment (HTML) for a given admin page slug.
	 * This is intended for the dashboard fragment loader to fetch only the relevant markup
	 * instead of scraping the full admin page HTML.
	 */
	public function get_admin_fragment()
	{
		// capability and nonce checks
		// Accept the specific settings nonce or the general admin nonce localized to JS
		$nonce_ok = false;
		if (isset($_POST['nonce'])) {
			$nonce = $_POST['nonce'];
			if (wp_verify_nonce($nonce, 'mlb_save_admin_settings') || wp_verify_nonce($nonce, 'mlb_admin_nonce')) {
				$nonce_ok = true;
			}
		}
		if (! $nonce_ok) {
			wp_send_json_error(array('message' => 'Security check failed'));
			return;
		}

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Insufficient permissions'));
			return;
		}

		$page = isset($_POST['page']) ? sanitize_text_field($_POST['page']) : '';
		$content_param = isset($_POST['content']) ? sanitize_text_field($_POST['content']) : '';

		// Support passing edit identifiers inside the unified `content` param
		// so fragments that need $_GET['edit'] (like the hotels edit form) render correctly.
		if (! empty($content_param)) {
			// hotels-edit-123 or hotels-edit-new
			if (preg_match('/^hotels-edit-(\d+|new)$/', $content_param, $m)) {
				// Populate $_GET['edit'] for the rendered page context
				$_GET['edit'] = $m[1];
			}
		}
		if (empty($page)) {
			wp_send_json_error(array('message' => 'No page specified'));
			return;
		}

		// Buffer the page output by invoking the corresponding render function if available.
		// We attempt to load the page via the same internal classes used by the plugin.
		// Convert warnings/notices to exceptions temporarily so we can return a JSON error instead of a 500.
		$previousHandler = set_error_handler(function($severity, $message, $file, $line) {
			throw new \ErrorException($message, 0, $severity, $file, $line);
		});

		try {
			ob_start();
			// Allow callers to request content via content param while using the unified base page
			$requested = $page;
			if ($page === 'mylighthouse-booker' && ! empty($content_param)) {
				if (strpos($content_param, 'settings') === 0) {
					$requested = 'mylighthouse-booker-settings';
				} elseif (strpos($content_param, 'hotels') === 0) {
					$requested = 'mylighthouse-booker-hotels';
				} elseif (strpos($content_param, 'tools') === 0) {
					$requested = 'mylighthouse-booker-tools';
				} elseif ($content_param === 'dashboard') {
					$requested = 'mylighthouse-booker';
				}
			}
			switch ($requested) {

			case 'mylighthouse-booker-hotels':
				if (class_exists('Mylighthouse_Booker_Admin_Hotels_Page')) {
					$cls = 'Mylighthouse_Booker_Admin_Hotels_Page';
					$o = new $cls();
					if (method_exists($o, 'render_page')) {
						$o->render_page();
					}
				}
				break;
			case 'mylighthouse-booker-tools':
				if (class_exists('Mylighthouse_Booker_Admin_Tools_Page')) {
					$cls = 'Mylighthouse_Booker_Admin_Tools_Page';
					$o = new $cls();
					if (method_exists($o, 'render_page')) {
						$o->render_page();
					}
				}
				break;
			case 'mylighthouse-booker':
				// Dashboard - if available, render
				if (class_exists('Mylighthouse_Booker_Admin_Dashboard')) {
					$cls = 'Mylighthouse_Booker_Admin_Dashboard';
					$o = new $cls();
					if (method_exists($o, 'render_page')) {
						$o->render_page();
					}
				}
				break;
			default:
				// For unknown pages, try to include by firing the page via admin_menu handlers
				// as a fallback we call admin.php?page=... and capture output using WP's output buffering
				// Build an admin URL and fetch via wp_remote_get should be avoided (internally may loop back), so just return error
				break;
		}

			$content = ob_get_clean();
		} catch (\Throwable $e) {
			// Restore previous error handler before returning
			if ($previousHandler !== null) {
				set_error_handler($previousHandler);
			}
			// Only reveal detailed errors to site admins or when WP_DEBUG is enabled
			$show = defined('WP_DEBUG') && WP_DEBUG || current_user_can('manage_options');
			wp_send_json_error(array('message' => $show ? $e->getMessage() : 'Server error while rendering fragment'));
			return;
		} finally {
			// Restore previous error handler
			if ($previousHandler !== null) {
				set_error_handler($previousHandler);
			}
		}
		if (empty($content)) {
			wp_send_json_error(array('message' => 'No content generated'));
			return;
		}


		// If a deterministic fragment key was provided, try to extract that specific fragment.
		$fragmentKey = isset($_POST['fragment']) ? sanitize_text_field($_POST['fragment']) : '';
		// Use the canonical selector map from the admin assets class so server and JS remain coordinated
		if (class_exists('Mylighthouse_Booker_Admin_Assets') && method_exists('Mylighthouse_Booker_Admin_Assets', 'get_fragment_selector_map')) {
			$selectorMap = Mylighthouse_Booker_Admin_Assets::get_fragment_selector_map();
		} else {
			$selectorMap = array(
				'settings' => 'mlb-admin-sections',
				'hotels' => 'mlb-hotels-wrap',
				'settings-wrap' => 'mlb-admin-wrap',
				'tools' => 'mlb-tools-wrap',
				'dashboard' => 'mlb-dashboard-wrap',
				'wrap' => 'wrap',
			);
		}

		$fragment = '';
		if (! empty($fragmentKey) && isset($selectorMap[$fragmentKey])) {
			$targetClass = $selectorMap[$fragmentKey];
			// Prefer DOMDocument + XPath extraction when available
			if (class_exists('\DOMDocument')) {
				libxml_use_internal_errors(true);
				$doc = new \DOMDocument();
				// Ensure proper encoding
				@$doc->loadHTML('<?xml encoding="utf-8" ?>' . $content);
				$xpath = new \DOMXPath($doc);
				// Look for element by class name
				$query = "//*[contains(concat(' ', normalize-space(@class), ' '), ' " . $targetClass . " ')]";
				$nodes = $xpath->query($query);
				if ($nodes && $nodes->length > 0) {
					$node = $nodes->item(0);
					$inner = '';
					foreach ($node->childNodes as $child) {
						$inner .= $doc->saveHTML($child);
					}
					$fragment = $inner;
				}
				libxml_clear_errors();
			} else {
				// Fallback: string search for the class name and return from the closest opening tag
				$pos = strpos($content, $targetClass);
				if ($pos !== false) {
					// find the opening tag start
					$start = strrpos(substr($content, 0, $pos), '<');
					if ($start !== false) {
						$fragment = trim(substr($content, $start));
					}
				}
			}
		}

		// If we didn't find a targeted fragment, try to extract a default fragment order
		if (empty($fragment)) {
			// Try known plugin wrappers in order
			$selectors = array(
				'mlb-admin-sections',
				'mlb-hotels-wrap',
				'mlb-admin-wrap',
				'mlb-tools-wrap',
				'mlb-dashboard-wrap',
				'wrap'
			);
			foreach ($selectors as $sel) {
				$pos = strpos($content, $sel);
				if ($pos !== false) {
					$start = strrpos(substr($content, 0, $pos), '<');
					if ($start !== false) {
						$fragment = trim(substr($content, $start));
						break;
					}
				}
			}
		}

		if (empty($fragment)) {
			// Last resort: return the whole content
			$fragment = $content;
		}

		wp_send_json_success(array('html' => $fragment));
	}

	/**
	 * Add hotel via AJAX
	 */
	public function add_hotel()
	{
		// Verify nonce
		if (! wp_verify_nonce($_POST['nonce'], 'cqb_ajax_nonce')) {
			wp_die('Security check failed');
		}

		$hotels = get_option('mlb_hotels', array());

		$new_hotel = array(
			'name' => sanitize_text_field($_POST['name']),
			'id'   => sanitize_key($_POST['id']),
		);

		$hotels[] = $new_hotel;
		update_option('mlb_hotels', $hotels);

		wp_send_json_success(array('message' => 'Hotel added successfully'));
	}

	/**
	 * AJAX: Add a new room or special to an existing hotel
	 */
	public function ajax_add_item()
	{
		$nonce_ok = false;
		if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'mlb_admin_nonce')) {
			$nonce_ok = true;
		}
		if (! $nonce_ok) {
			wp_send_json_error(array('message' => 'Security check failed'));
		}

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Insufficient permissions'));
		}

		$hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
		$target = isset($_POST['target']) ? sanitize_text_field($_POST['target']) : '';
		$name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
		$ext = isset($_POST['external_id']) ? sanitize_text_field($_POST['external_id']) : '';

		if (! $hotel_id || empty($target) || empty($name)) {
			wp_send_json_error(array('message' => 'Missing required fields'));
		}

		switch ($target) {
			case 'rooms':
				if (! class_exists('Mylighthouse_Booker_Room')) {
					wp_send_json_error(array('message' => 'Room model not available'));
				}
				$new_id = Mylighthouse_Booker_Room::create(array('hotel_id' => $hotel_id, 'name' => $name, 'room_id' => $ext));
				break;
			case 'specials':
				if (! class_exists('Mylighthouse_Booker_Special')) {
					wp_send_json_error(array('message' => 'Special model not available'));
				}
				$new_id = Mylighthouse_Booker_Special::create(array('hotel_id' => $hotel_id, 'name' => $name, 'special_id' => $ext));
				break;
			default:
				wp_send_json_error(array('message' => 'Invalid target'));
		}

		if (! $new_id) {
			wp_send_json_error(array('message' => 'Failed to create item'));
		}

		// Return the newly created DB id so client can set the hidden input
		wp_send_json_success(array('message' => 'Item created', 'item_id' => $new_id));
	}

	/**
	 * AJAX: Delete a room or special
	 */
	public function ajax_delete_item()
	{
		$nonce_ok = false;
		if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'mlb_admin_nonce')) {
			$nonce_ok = true;
		}
		if (! $nonce_ok) {
			wp_send_json_error(array('message' => 'Security check failed'));
		}

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Insufficient permissions'));
		}

		$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
		$target = isset($_POST['target']) ? sanitize_text_field($_POST['target']) : '';

		if (! $item_id || empty($target)) {
			wp_send_json_error(array('message' => 'Missing required fields'));
		}

		switch ($target) {
			case 'rooms':
				if (! class_exists('Mylighthouse_Booker_Room') || ! method_exists('Mylighthouse_Booker_Room', 'delete')) {
					wp_send_json_error(array('message' => 'Room delete not supported'));
				}
				$ok = Mylighthouse_Booker_Room::delete($item_id);
				break;
			case 'specials':
				if (! class_exists('Mylighthouse_Booker_Special') || ! method_exists('Mylighthouse_Booker_Special', 'delete')) {
					wp_send_json_error(array('message' => 'Special delete not supported'));
				}
				$ok = Mylighthouse_Booker_Special::delete($item_id);
				break;
			default:
				wp_send_json_error(array('message' => 'Invalid target'));
		}

		if (empty($ok)) {
			wp_send_json_error(array('message' => 'Delete failed'));
		}

		wp_send_json_success(array('message' => 'Deleted'));
	}

	/**
	 * AJAX: Update a room or special
	 */
	public function ajax_update_item()
	{
		$nonce_ok = false;
		if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'mlb_admin_nonce')) {
			$nonce_ok = true;
		}
		if (! $nonce_ok) {
			wp_send_json_error(array('message' => 'Security check failed'));
		}

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Insufficient permissions'));
		}

		$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
		$target = isset($_POST['target']) ? sanitize_text_field($_POST['target']) : '';
		$name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
		$ext = isset($_POST['external_id']) ? sanitize_text_field($_POST['external_id']) : '';

		if (! $item_id || empty($target) || empty($name)) {
			wp_send_json_error(array('message' => 'Missing required fields'));
		}

		switch ($target) {
			case 'rooms':
				if (! class_exists('Mylighthouse_Booker_Room')) {
					wp_send_json_error(array('message' => 'Room model not available'));
				}
				$ok = Mylighthouse_Booker_Room::update($item_id, array('name' => $name, 'room_id' => $ext));
				break;
			case 'specials':
				if (! class_exists('Mylighthouse_Booker_Special')) {
					wp_send_json_error(array('message' => 'Special model not available'));
				}
				$ok = Mylighthouse_Booker_Special::update($item_id, array('name' => $name, 'special_id' => $ext));
				break;
			default:
				wp_send_json_error(array('message' => 'Invalid target'));
		}

		if (empty($ok)) {
			wp_send_json_error(array('message' => 'Update failed'));
		}

		wp_send_json_success(array('message' => 'Updated'));
	}

	/**
	 * Remove hotel via AJAX
	 */
	public function remove_hotel()
	{
		// Verify nonce
		if (! wp_verify_nonce($_POST['nonce'], 'cqb_ajax_nonce')) {
			wp_die('Security check failed');
		}

		$index  = intval($_POST['index']);
		$hotels = get_option('mlb_hotels', array());

		if (isset($hotels[$index])) {
			unset($hotels[$index]);
			$hotels = array_values($hotels); // Reindex array
			update_option('mlb_hotels', $hotels);
		}

		wp_send_json_success(array('message' => 'Hotel removed successfully'));
	}

	/**
	 * Delete a hotel via AJAX (uses the same model as admin delete)
	 */
	public function delete_hotel()
	{
		// Expect hotel_id and nonce
		$hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
		$nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';

		if (! $hotel_id) {
			wp_send_json_error(array('message' => 'Invalid hotel id'));
		}

		if (! wp_verify_nonce($nonce, 'delete-hotel-' . $hotel_id)) {
			wp_send_json_error(array('message' => 'Security check failed'));
		}

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Insufficient permissions'));
		}

		// Use the model delete method if available
		if (class_exists('Mylighthouse_Booker_Hotel') && method_exists('Mylighthouse_Booker_Hotel', 'delete')) {
			$result = Mylighthouse_Booker_Hotel::delete($hotel_id);
			if ($result) {
				wp_send_json_success(array('message' => 'Hotel deleted'));
			} else {
				wp_send_json_error(array('message' => 'Delete failed'));
			}
		} else {
			// Fallback: respond success but warn
			wp_send_json_error(array('message' => 'Delete not supported on this install'));
		}
	}


}
