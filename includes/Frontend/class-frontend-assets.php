<?php

/**
 * Frontend assets handler
 *
 * @package StandaloneTech
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Frontend_Assets
 */
class Mylighthouse_Booker_Frontend_Assets
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('init', array($this, 'register_styles'));
	}

	/**
	 * Register styles early in the loading process
	 */
	public function register_styles()
	{
		// Register FontAwesome from CDN
		wp_register_style(
			'fontawesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
			array(),
			'6.4.0',
			'all'
		);

		// Register EasePick CSS from local vendor directory with our custom modifications
		$easepick_css_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/vendor/easepick/easepick.css';
		$easepick_css_ver = file_exists($easepick_css_path) ? filemtime($easepick_css_path) : '1.2.1';
		
		wp_register_style(
			'easepick',
			plugins_url('assets/vendor/easepick/easepick.css', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(),
			$easepick_css_ver,
			'all'
		);

		// Register modular component styles (date picker, booking details, modals)
		$components_css_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/css/frontend/components.css';
		$components_css_ver = file_exists($components_css_path) ? filemtime($components_css_path) : '1.0.0';
		
		wp_register_style(
			'mylighthouse-booker-components',
			plugins_url('/assets/css/frontend/components.css', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('easepick'),
			$components_css_ver,
			'all'
		);

		// Register legacy booking form styles (for backward compatibility)
		wp_register_style(
			'mylighthouse-booker-frontend',
			plugins_url('/assets/css/frontend/booking-form.css', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('easepick'), // Depend on easepick CSS to load after it
			'1.0.0',
			'all'
		);

		// Legacy modal.css registration removed - using modular components.css instead

	}

	/**
	 * Enqueue frontend styles
	 */
	public function enqueue_styles()
	{
		// Enqueue FontAwesome
		if (!wp_style_is('fontawesome', 'enqueued')) {
			wp_enqueue_style('fontawesome');
		}

		// Enqueue EasePick CSS first
		if (!wp_style_is('easepick', 'enqueued')) {
			wp_enqueue_style('easepick');
		}

		// Enqueue new modular component styles
		if (!wp_style_is('mylighthouse-booker-components', 'enqueued')) {
			wp_enqueue_style('mylighthouse-booker-components');
		}

		// Enqueue legacy booking form styles for backward compatibility
		if (!wp_style_is('mylighthouse-booker-frontend', 'enqueued')) {
			wp_enqueue_style('mylighthouse-booker-frontend');
		}

		// Legacy modal.css removed - using modular components.css instead

		// Styling is handled by the Elementor widget; do not read legacy DB option here.
		// Styling is managed by the theme/Elementor; no inline legacy styles are emitted.
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function enqueue_scripts()
	{
		// Register EasePick datetime dependency first (in head for availability)
		wp_register_script(
			'easepick-datetime',
			'https://cdn.jsdelivr.net/npm/@easepick/datetime@1.2.1/dist/index.umd.js',
			array(),
			'1.2.1',
			false  // Load in head
		);

		// Register EasePick base plugin (depends on datetime)
		wp_register_script(
			'easepick-base-plugin',
			'https://cdn.jsdelivr.net/npm/@easepick/base-plugin@1.2.1/dist/index.umd.js',
			array('easepick-datetime'),
			'1.2.1',
			false  // Load in head
		);

		// Register EasePick core library from local vendor directory (depends on datetime)
		wp_register_script(
			'easepick-core',
			plugins_url('/assets/vendor/easepick/easepick.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('easepick-datetime', 'easepick-base-plugin'),
			'1.2.1',
			false  // Load in head to ensure availability before modal opens
		);

		// Register EasePick range plugin from local vendor (depends on core)
		wp_register_script(
			'easepick-range',
			plugins_url('/assets/vendor/easepick/easepick-range.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('easepick-core', 'easepick-base-plugin'),
			'1.2.1',
			false  // Load in head
		);

		wp_register_script(
			'easepick-lock',
			'https://cdn.jsdelivr.net/npm/@easepick/lock-plugin@1.2.1/dist/index.umd.min.js',
			array('easepick-core', 'easepick-base-plugin'),
			'1.2.1',
			true
		);

		wp_register_script(
			'easepick-wrapper',
			plugins_url('/assets/vendor/easepick/easepick-wrapper.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('easepick-core', 'easepick-range', 'easepick-lock'),
			'1.0.0',
			true
		);

        // Use file modification times for script versions to help bust caches when files change.
        $room_form_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/room-form.js';
        $special_form_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/special-form.js';
        $room_form_ver = (file_exists($room_form_path)) ? filemtime($room_form_path) : '1.0.1';
        $special_form_ver = (file_exists($special_form_path)) ? filemtime($special_form_path) : '1.0.1';

		wp_register_script(
			'mylighthouse-booker-room-form',
			plugins_url('/assets/js/frontend/room-form.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('jquery', 'easepick-wrapper', 'wp-i18n'),
			$room_form_ver,
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mylighthouse-booker-room-form', 'mylighthouse-booker', plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'languages' );
		}

		wp_register_script(
			'mylighthouse-booker-special-form',
			plugins_url('/assets/js/frontend/special-form.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('jquery', 'easepick-wrapper', 'wp-i18n'),
			$special_form_ver,
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mylighthouse-booker-special-form', 'mylighthouse-booker', plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'languages' );
		}

		wp_register_script(
			'mylighthouse-booker-booking-modal',
			plugins_url('/assets/js/frontend/booking-modal.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('wp-i18n'),
			'1.0.0',
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mylighthouse-booker-booking-modal', 'mylighthouse-booker', plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'languages' );
		}

		wp_register_script(
			'mylighthouse-booker-form',
			plugins_url('/assets/js/frontend/form.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('mylighthouse-booker-booking-modal', 'wp-i18n'),
			'1.0.0',
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mylighthouse-booker-form', 'mylighthouse-booker', plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'languages' );
		}

		// Booking form behaviors (handles inline pickers and modal dispatching)
		$booking_form_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/booking-form.js';
		$booking_form_ver = (file_exists($booking_form_path)) ? filemtime($booking_form_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-booking-form',
			plugins_url('/assets/js/frontend/booking-form.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('jquery', 'easepick-wrapper', 'mylighthouse-booker-booking-modal', 'wp-i18n'),
			$booking_form_ver,
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mylighthouse-booker-booking-form', 'mylighthouse-booker', plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'languages' );
		}

		$iframe_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/iframe.js';
		$iframe_ver = (file_exists($iframe_path)) ? filemtime($iframe_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-iframe',
			plugins_url('/assets/js/frontend/iframe.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('jquery'),
			$iframe_ver,
			true
		);

		wp_register_script(
			'mylighthouse-booker-spinner',
			plugins_url('/assets/js/frontend/spinner.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(),
			'1.0.0',
			true
		);

		wp_register_script(
			'mylighthouse-booker-room-booking',
			plugins_url('/assets/js/frontend/room-booking.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('jquery', 'mylighthouse-booker-booking-modal', 'wp-i18n'),
			'1.0.0',
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mylighthouse-booker-room-booking', 'mylighthouse-booker', plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'languages' );
		}

		wp_register_script(
			'mylighthouse-booker-special-booking',
			plugins_url('/assets/js/frontend/special-booking.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('jquery', 'mylighthouse-booker-booking-modal', 'wp-i18n'),
			'1.0.0',
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mylighthouse-booker-special-booking', 'mylighthouse-booker', plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'languages' );
		}

		// Fallback modal trigger script: ensures elements with `data-trigger-modal` open the modal
		$modal_trigger_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/modal-trigger-fallback.js';
		$modal_trigger_ver = (file_exists($modal_trigger_path)) ? filemtime($modal_trigger_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-modal-trigger',
			plugins_url('/assets/js/frontend/modal-trigger-fallback.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(),
			$modal_trigger_ver,
			true
		);

		// Register new modular component scripts
		$date_picker_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/date-picker.js';
		$date_picker_ver = file_exists($date_picker_path) ? filemtime($date_picker_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-date-picker',
			plugins_url('/assets/js/frontend/date-picker.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('easepick-wrapper'),
			$date_picker_ver,
			true
		);

		$booking_details_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/booking-details.js';
		$booking_details_ver = file_exists($booking_details_path) ? filemtime($booking_details_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-booking-details',
			plugins_url('/assets/js/frontend/booking-details.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(),
			$booking_details_ver,
			true
		);

		$booking_actions_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/booking-actions.js';
		$booking_actions_ver = file_exists($booking_actions_path) ? filemtime($booking_actions_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-booking-actions',
			plugins_url('/assets/js/frontend/booking-actions.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(),
			$booking_actions_ver,
			true
		);

		$booking_results_modal_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/booking-results-modal.js';
		$booking_results_modal_ver = file_exists($booking_results_modal_path) ? filemtime($booking_results_modal_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-booking-results-modal',
			plugins_url('/assets/js/frontend/booking-results-modal.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(),
			$booking_results_modal_ver,
			true
		);

		// Register modular widget scripts (depend on components)
		$room_widget_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/room-widget.js';
		$room_widget_ver = file_exists($room_widget_path) ? filemtime($room_widget_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-room-widget',
			plugins_url('/assets/js/frontend/room-widget.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(
				'mylighthouse-booker-date-picker',
				'mylighthouse-booker-booking-details',
				'mylighthouse-booker-booking-actions'
			),
			$room_widget_ver,
			true
		);

		$hotel_widget_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/hotel-widget.js';
		$hotel_widget_ver = file_exists($hotel_widget_path) ? filemtime($hotel_widget_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-hotel-widget',
			plugins_url('/assets/js/frontend/hotel-widget.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(
				'mylighthouse-booker-date-picker',
				'mylighthouse-booker-booking-actions'
			),
			$hotel_widget_ver,
			true
		);

		$special_widget_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/special-widget.js';
		$special_widget_ver = file_exists($special_widget_path) ? filemtime($special_widget_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-special-widget',
			plugins_url('/assets/js/frontend/special-widget.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('mylighthouse-booker-booking-actions'),
			$special_widget_ver,
			true
		);

		// Enqueue the fallback so trigger buttons work even if other front-end form scripts aren't present
		// Note: modal trigger script is registered here but will be enqueued
		// only when a form/render path requires it (Elementor widget or shortcode).
		
		// Enqueue core modular scripts globally
		wp_enqueue_script('mylighthouse-booker-date-picker');
		wp_enqueue_script('mylighthouse-booker-booking-details');
		wp_enqueue_script('mylighthouse-booker-booking-actions');
		wp_enqueue_script('mylighthouse-booker-booking-results-modal');
		
		// Enqueue widget scripts globally (they check for elements before initializing)
		wp_enqueue_script('mylighthouse-booker-room-widget');
		wp_enqueue_script('mylighthouse-booker-hotel-widget');
		wp_enqueue_script('mylighthouse-booker-special-widget');
		
		// Localize script with configuration
		wp_localize_script('mylighthouse-booker-booking-actions', 'mlbConfig', array(
			'bookingPageUrl' => get_option('mlb_booking_page_url', ''),
			'displayModeMobile' => get_option('mlb_display_mode_mobile', 'modal'),
			'displayModeTablet' => get_option('mlb_display_mode_tablet', 'modal'),
			'displayModeDesktop' => get_option('mlb_display_mode_desktop', 'modal'),
		));
		
		wp_localize_script('mylighthouse-booker-date-picker', 'MLBPluginUrl', array(
			'url' => plugins_url('/', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE)
		));
		wp_localize_script('mylighthouse-booker-booking-results-modal', 'MLBBookingEngineBase', array(
			'url' => 'https://bookingengine.mylighthouse.com/'
		));
	}

	// Legacy generate_styles removed; styling should be handled by Elementor/theme.
}
