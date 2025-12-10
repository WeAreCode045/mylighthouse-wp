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
		// Use file modification time for version to bust cache when we update the file
		$easepick_css_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/vendor/easepick/easepick.css';
		$easepick_css_ver = (file_exists($easepick_css_path)) ? filemtime($easepick_css_path) : '1.2.1';
		wp_register_style(
			'easepick',
			plugins_url('/assets/vendor/easepick/easepick.css', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(),
			$easepick_css_ver,
			'all'
		);

		// Register plugin styles
		wp_register_style(
			'mylighthouse-booker-frontend',
			plugins_url('/assets/css/frontend/booking-form.css', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('easepick'), // Depend on easepick CSS to load after it
			'1.0.0',
			'all'
		);

		wp_register_style(
			'mylighthouse-booker-modal',
			plugins_url('/assets/css/frontend/modal.css', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(),
			'1.0.0',
			'all'
		);
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

		// Enqueue styles (booking-form.css depends on easepick)
		if (!wp_style_is('mylighthouse-booker-frontend', 'enqueued')) {
			wp_enqueue_style('mylighthouse-booker-frontend');
		}

		if (!wp_style_is('mylighthouse-booker-modal', 'enqueued')) {
			wp_enqueue_style('mylighthouse-booker-modal');
		}

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

		// Enqueue the fallback so trigger buttons work even if other front-end form scripts aren't present
		// Note: modal trigger script is registered here but will be enqueued
		// only when a form/render path requires it (Elementor widget or shortcode).
	}

	// Legacy generate_styles removed; styling should be handled by Elementor/theme.
}
