<?php

/**
 * Template loader helper
 *
 * @package StandaloneTech
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Template_Loader
 */
class Mylighthouse_Booker_Template_Loader
{

	/**
	 * Templates directory
	 *
	 * @var string
	 */
	private static $templates_dir;

	/**
	 * Initialize
	 */
	public static function init()
	{
		self::$templates_dir = MYLIGHTHOUSE_BOOKER_ABSPATH . 'templates/';
	}

	/**
	 * Get template
	 *
	 * @param string $template_name Template name.
	 * @param array  $args          Arguments.
	 * @param bool   $return        Whether to return or echo.
	 * @return string|void
	 */
	public static function get_template($template_name, $args = array(), $return = false)
	{
		$template_path = self::locate_template($template_name);

		if (! $template_path) {
			return;
		}

		// Extract variables.
		if (! empty($args) && is_array($args)) {
			extract($args, EXTR_SKIP); // phpcs:ignore
		}

		if ($return) {
			ob_start();
			include $template_path;
			return ob_get_clean();
		}

		include $template_path;
	}

	/**
	 * Locate template
	 *
	 * @param string $template_name Template name.
	 * @return string|bool
	 */
	public static function locate_template($template_name)
	{
		$template_path = self::$templates_dir . $template_name;

		if (file_exists($template_path)) {
			return $template_path;
		}

		// Check theme override.
		$theme_template = locate_template('mylighthouse-booker/' . $template_name);
		if ($theme_template) {
			return $theme_template;
		}

		return false;
	}
}
