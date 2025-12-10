<?php

/**
 * Elementor integration loader
 *
 * @package StandaloneTech
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Elementor_Loader
 */
class Mylighthouse_Booker_Elementor_Loader
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Defer until plugins are loaded to check for Elementor.
		add_action('plugins_loaded', array($this, 'maybe_bootstrap'));
	}

	/**
	 * Bootstrap Elementor hooks if Elementor is available
	 */
	public function maybe_bootstrap()
	{
		if (! did_action('elementor/loaded')) {
			return; // Elementor not active
		}

		// Register category (optional) and the widget
		add_action('elementor/widgets/register', array($this, 'register_widgets'));
		add_action('elementor/elements/categories_registered', array($this, 'register_category'));
	}

	/**
	 * Register a custom category for Mylighthouse Booker widgets
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
	 */
	public function register_category($elements_manager)
	{
		// Only add if not already
		$elements_manager->add_category(
			'mylighthouse-booker',
			array(
				'title' => __('Mylighthouse Booker', 'mylighthouse-booker'),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Register widgets
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 */
	public function register_widgets($widgets_manager)
	{
		// Ensure widget class file exists
		$widget_file = MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Elementor/class-elementor-widget-booking-form.php';
		if (file_exists($widget_file)) {
			require_once $widget_file;
			$widgets_manager->register(new \Mylighthouse_Booker_Elementor_Widget_Booking_Form());
		}
	}
}
