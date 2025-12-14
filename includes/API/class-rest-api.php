<?php
/**
 * REST API Handler
 * 
 * Provides REST endpoints for frontend data to avoid CSP issues with inline scripts
 * 
 * @package Mylighthouse_Booker
 */

if (! defined('ABSPATH')) {
	exit;
}

class Mylighthouse_Booker_REST_API
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		add_action('rest_api_init', array($this, 'register_routes'));
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes()
	{
		register_rest_route('mylighthouse-booker/v1', '/config', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_config'),
			'permission_callback' => '__return_true', // Public endpoint
		));

		register_rest_route('mylighthouse-booker/v1', '/modal-template', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_modal_template'),
			'permission_callback' => '__return_true', // Public endpoint
		));
	}

	/**
	 * Get configuration data
	 * Replaces wp_localize_script data
	 */
	public function get_config()
	{
		// Get booking page URL from settings
		$booking_page_url = get_option('mlb_booking_page_url', 'https://bookingengine.mylighthouse.com/');
		
		// Ensure trailing slash
		if (substr($booking_page_url, -1) !== '/') {
			$booking_page_url .= '/';
		}

		return rest_ensure_response(array(
			'booking_page_url' => $booking_page_url,
			'ajax_url' => admin_url('admin-ajax.php'),
			'site_url' => get_site_url(),
			'plugin_url' => plugins_url('', MYLIGHTHOUSE_BOOKER_PLUGIN_FILE),
		));
	}

	/**
	 * Get modal template HTML
	 * Returns the booking modal template
	 */
	public function get_modal_template()
	{
		ob_start();
		include plugin_dir_path(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE) . 'templates/modals/booking-modal.php';
		$template = ob_get_clean();

		return rest_ensure_response(array(
			'html' => $template,
		));
	}
}
