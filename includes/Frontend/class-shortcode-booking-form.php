<?php

/**
 * Shortcode for booking form
 *
 * @package StandaloneTech
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Shortcode_Booking_Form
 */
class Mylighthouse_Booker_Shortcode_Booking_Form
{

	/**
	 * Hotels data
	 *
	 * @var array
	 */
	private $hotels;

	/**
	 * Booking page URL
	 *
	 * @var string
	 */
	private $booking_page_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->hotels           = get_option('mlb_hotels', array());
		$this->booking_page_url = get_option('mlb_booking_page_url');

		add_action('init', array($this, 'register_shortcodes'));
	}

	/**
	 * Register shortcodes
	 */
	public function register_shortcodes()
	{
		// Legacy shortcode removed: booking forms should be added via Elementor
		// or the iframe target. This function intentionally does not register
		// the legacy `[lighthouse_booking_form]` shortcode.
	}

	/**
	 * Render the booking form shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output
	 */
	public function render_shortcode($atts)
	{
		// Only enqueue assets if not already enqueued
		if (!wp_style_is('mylighthouse-booker-frontend', 'enqueued')) {
			wp_enqueue_style('mylighthouse-booker-frontend');
		}

		// Determine which frontend form script to enqueue: special if rate provided, otherwise room-form for general/hotel/room
		$rate_id = isset($atts['rate']) ? sanitize_text_field($atts['rate']) : '';
		$script_handle = !empty($rate_id) ? 'mylighthouse-booker-special-form' : 'mylighthouse-booker-room-form';
		if (!wp_script_is($script_handle, 'enqueued')) {
			wp_enqueue_script($script_handle);
		}
		if (!wp_script_is('mylighthouse-booker-form', 'enqueued')) {
			wp_enqueue_script('mylighthouse-booker-form');
		}
		if (!wp_script_is('mylighthouse-booker-spinner', 'enqueued')) {
			wp_enqueue_script('mylighthouse-booker-spinner');
		}

		// Enqueue room booking script if room is specified
		// Enqueue room booking script if room is specified
		if (!empty($room_id) && !wp_script_is('mylighthouse-booker-room-booking', 'enqueued')) {
			wp_enqueue_script('mylighthouse-booker-room-booking');
		}

		// Enqueue special booking script if a rate (special) is specified
		$rate_id = isset($atts['rate']) ? sanitize_text_field($atts['rate']) : '';
		if (!empty($rate_id) && !wp_script_is('mylighthouse-booker-special-booking', 'enqueued')) {
			wp_enqueue_script('mylighthouse-booker-special-booking');
		}

		// Only enqueue easepick if not already enqueued
		if (!wp_style_is('easepick', 'enqueued')) {
			wp_enqueue_style('easepick');
		}
		if (!wp_script_is('easepick-wrapper', 'enqueued')) {
			wp_enqueue_script('easepick-wrapper');
		}

		if (empty($this->hotels) || empty($this->booking_page_url)) {
			if (current_user_can('manage_options')) {
				return $this->get_admin_notice(
					'error',
					'The plugin is not configured. Please add hotels and set the Booking Page URL in Settings -> Mylighthouse Booker.'
				);
			}
			return '';
		}

		$atts = shortcode_atts(
			array(
				'id'          => null,
				'hotel'       => null,
				'hotelselect' => null,
				'room'        => null,
			),
			$atts,
			'lighthouse_booking_form'
		);

		$specific_hotel_index = $atts['id'];
		$preselect_hotel_index = $atts['hotel'];
		$hotelselect_param    = $atts['hotelselect'];
		$room_id              = $atts['room'];
		$display_hotels       = array();
		$form_data_id         = '';
		$explicit_hotel_id    = '';

		if ($specific_hotel_index) {
			$hotels_array  = array_values($this->hotels);
			$hotel_index   = intval($specific_hotel_index) - 1;

			if (isset($hotels_array[$hotel_index])) {
				$display_hotels[]   = $hotels_array[$hotel_index];
				$form_data_id       = $hotels_array[$hotel_index]['id'];
				$explicit_hotel_id  = $hotels_array[$hotel_index]['id'];
			} elseif (current_user_can('manage_options')) {
				return $this->get_admin_notice(
					'warning',
					'The specified hotel number "' . esc_html($specific_hotel_index) . '" was not found. You have ' . count($hotels_array) . ' hotel(s) configured.'
				);
			}
		} else {
			$display_hotels = $this->hotels;
		}

		// Styling handled by Elementor widget; do not read legacy DB option here.
		$style_opts   = array();
		$button_label = 'Check Availability';

		$layout     = 'inline';
		$placement  = 'after';

		// Get device-specific display mode settings
		$display_mode_mobile = get_option('mlb_display_mode_mobile', get_option('mlb_display_mode', 'modal'));
		$display_mode_tablet = get_option('mlb_display_mode_tablet', get_option('mlb_display_mode', 'modal'));
		$display_mode_desktop = get_option('mlb_display_mode_desktop', get_option('mlb_display_mode', 'modal'));
		// Legacy result_target for backwards compatibility (use desktop as default)
		$result_target = ($display_mode_desktop === 'modal') ? 'modal' : 'booking_page';

		$show_hotel_select = false;
		$default_hotel_id  = '';

		if ($preselect_hotel_index) {
			$hotels_array = array_values($this->hotels);
			$hotel_index  = intval($preselect_hotel_index) - 1;

			if (isset($hotels_array[$hotel_index])) {
				$default_hotel_id = $hotels_array[$hotel_index]['id'];
			}
		}

		// Handle explicit hotelselect parameter
		if ($hotelselect_param === 'no') {
			$show_hotel_select = false;
		} elseif ($hotelselect_param === 'true' || count($display_hotels) > 1) {
			$show_hotel_select = true;
		}

		if (! $show_hotel_select && count($display_hotels) === 1) {
			$default_hotel_id = $display_hotels[0]['id'];
		}

		$form_data = array(
			'hotels'            => $display_hotels,
			'show_hotel_select' => $show_hotel_select,
			'default_hotel_id'  => $default_hotel_id,
			'form_data_id'      => $form_data_id,
			'explicit_hotel_id' => $explicit_hotel_id,
			'button_label'      => $button_label,
			'layout'            => $layout,
			'placement'         => $placement,
			'booking_page_url'  => $this->booking_page_url,
			'room_id'           => $room_id,
			'room_name'         => $this->get_room_name($room_id, $default_hotel_id),
			'hotel_name'        => $this->get_hotel_name($default_hotel_id),
		);

		// Load modal template
		ob_start();
		include MYLIGHTHOUSE_BOOKER_ABSPATH . 'templates/modals/calendar-modal.php';
		$modal_template = ob_get_clean();

		// Localize script to the appropriate handle (room or special)
		// Prefer style option (if present) otherwise fallback to admin setting
		$spinner_image = '';
		if (!empty($style_opts) && isset($style_opts['calendar']['spinner_image_url'])) {
			$spinner_image = esc_url($style_opts['calendar']['spinner_image_url']);
		}
		if (empty($spinner_image)) {
			$global_spinner_url = esc_url( get_option('mlb_spinner_image_url', '') );
			if (!empty($global_spinner_url)) {
				$spinner_image = $global_spinner_url;
			}
		}
		if (empty($spinner_image)) {
			$spinner_image_id = intval( get_option('mlb_spinner_image_id', 0) );
			if ($spinner_image_id > 0) {
				$spinner_image = esc_url( wp_get_attachment_image_url($spinner_image_id, 'full') );
			}
		}
		// Localize minimal runtime params — translations are handled via gettext.
		wp_localize_script($script_handle, 'cqb_params', array(
			'booking_page_url' => $this->booking_page_url,
			'modal_template' => $modal_template,
			'result_target' => $result_target,
			'display_mode_mobile' => $display_mode_mobile,
			'display_mode_tablet' => $display_mode_tablet,
			'display_mode_desktop' => $display_mode_desktop,
			'spinner_image_url' => $spinner_image,
		));

		// Render form.
		ob_start();
		Mylighthouse_Booker_Template_Loader::get_template('booking-form.php', $form_data);
		return ob_get_clean();
	}

	/**
	 * Get admin notice
	 *
	 * @param string $type    Notice type.
	 * @param string $message Message.
	 * @return string HTML.
	 */
	private function get_admin_notice($type, $message)
	{
		return '<div class="notice notice-' . esc_attr($type) . '"><p>' . esc_html($message) . '</p></div>';
	}

	/**
	 * Get hotel name by hotel ID
	 *
	 * @param string $hotel_id Hotel ID.
	 * @return string Hotel name or empty string if not found.
	 */
	private function get_hotel_name($hotel_id)
	{
		if (empty($hotel_id)) {
			return '';
		}

		foreach ($this->hotels as $hotel) {
			if ($hotel['id'] === $hotel_id) {
				return $hotel['name'];
			}
		}

		return '';
	}

	/**
	 * Get room name by room ID and hotel ID
	 * Note: This is a placeholder - room names would typically come from a room database
	 * or be passed as shortcode attributes. For now, return a generic name.
	 *
	 * @param string $room_id   Room ID.
	 * @param string $hotel_id  Hotel ID.
	 * @return string Room name or empty string if not found.
	 */
	private function get_room_name($room_id, $hotel_id)
	{
		if (empty($room_id)) {
			return '';
		}

		// Placeholder logic - in a real implementation, this would query a room database
		// For now, return a generic room name based on the room ID
		return 'Room ' . $room_id;
	}
}
