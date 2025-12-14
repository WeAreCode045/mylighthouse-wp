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
		add_filter('script_loader_tag', array($this, 'add_script_attributes'), 10, 3);
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
			plugins_url('assets/vendor/easepick/easepick.css', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
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

		// Enqueue easepick override CSS LAST to ensure it overrides CDN styles
		if (!wp_style_is('mylighthouse-booker-easepick-override', 'enqueued')) {
			wp_enqueue_style('mylighthouse-booker-easepick-override');
		}

		// Styling is handled by the Elementor widget; do not read legacy DB option here.
		// Styling is managed by the theme/Elementor; no inline legacy styles are emitted.
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function enqueue_scripts()
	{
		// Ensure jQuery is available (WordPress includes it by default)
		wp_enqueue_script('jquery');

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

		// Register EasePick core library from local vendor directory (depends on datetime and base-plugin)
		wp_register_script(
			'easepick-core',
			plugins_url('/assets/vendor/easepick/easepick.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('easepick-datetime', 'easepick-base-plugin'),
			'1.2.1',
			false  // Load in head to ensure availability before modal opens
		);

		// Register EasePick range plugin from local vendor (MUST load after core and base-plugin)
		wp_register_script(
			'easepick-range',
			plugins_url('/assets/vendor/easepick/easepick-range.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('easepick-datetime', 'easepick-base-plugin', 'easepick-core'),
			'1.2.1',
			false  // Load in head
		);

		// Register EasePick lock plugin (depends on core and base-plugin)
		wp_register_script(
			'easepick-lock',
			'https://cdn.jsdelivr.net/npm/@easepick/lock-plugin@1.2.1/dist/index.umd.min.js',
			array('easepick-datetime', 'easepick-base-plugin', 'easepick-core'),
			'1.2.1',
			false  // Load in head for consistency
		);

		// Register EasePick wrapper (depends on all plugins)
		wp_register_script(
			'easepick-wrapper',
			plugins_url('/assets/vendor/easepick/easepick-wrapper.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('easepick-datetime', 'easepick-base-plugin', 'easepick-core', 'easepick-range', 'easepick-lock'),
			'1.0.0',
			false  // Load in head to ensure it's ready
		);

		// Register spinner utility
		wp_register_script(
			'mylighthouse-booker-spinner',
			plugins_url('/assets/js/frontend/spinner.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array(),
			'1.0.0',
			true
		);

		// Register Frontend - unified form handlers with calendar modal (hotel, room, special)
		$frontend_path = plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'assets/js/frontend/frontend.js';
		$frontend_ver = (file_exists($frontend_path)) ? filemtime($frontend_path) : '1.0.0';
		wp_register_script(
			'mylighthouse-booker-frontend',
			plugins_url('/assets/js/frontend/frontend.js', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
			array('jquery', 'easepick-datetime', 'easepick-base-plugin', 'easepick-core', 'easepick-range', 'easepick-lock', 'mylighthouse-booker-spinner'),
			$frontend_ver,
			true
		);

		// Enqueue the fallback so trigger buttons work even if other front-end form scripts aren't present
		// Note: modal trigger script is registered here but will be enqueued
		// only when a form/render path requires it (Elementor widget or shortcode).
	}

	// Legacy generate_styles removed; styling should be handled by Elementor/theme.

	/**
	 * Add attributes to script tags for CSP compatibility
	 * Adds data-cfasync="false" to prevent issues with inline scripts
	 *
	 * @param string $tag    The script tag
	 * @param string $handle The script handle
	 * @param string $src    The script source URL
	 * @return string Modified script tag
	 */
	public function add_script_attributes($tag, $handle, $src)
	{
		// List of our plugin script handles
		$plugin_handles = array(
			'mylighthouse-booker-frontend',
			'mylighthouse-booker-spinner',
			'easepick-core',
			'easepick-range',
			'easepick-wrapper'
		);

		// Add data-cfasync="false" to our scripts to prevent CSP issues
		if (in_array($handle, $plugin_handles, true)) {
			$tag = str_replace(' src=', ' data-cfasync="false" src=', $tag);
		}

		return $tag;
	}
}
