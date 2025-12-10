<?php

/**
 * Autoloader class
 *
 * @package StandaloneTech
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Autoloader
 */
class Mylighthouse_Booker_Autoloader
{

	/**
	 * Base path
	 *
	 * @var string
	 */
	private $base_path;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->base_path = MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/';
	}

	/**
	 * Register autoloader
	 */
	public function register()
	{
		spl_autoload_register(array($this, 'autoload'));
	}

	/**
	 * Autoload classes
	 *
	 * @param string $class_name Class name.
	 */
	public function autoload($class_name)
	{
		// Only handle our plugin classes.
		if (0 !== strpos($class_name, 'Mylighthouse_Booker_')) {
			return;
		}

		// Convert class name to file path.
		$file_path = $this->get_file_path($class_name);

		if (file_exists($file_path)) {
			require_once $file_path;
		} else {
			error_log("Autoloader: File not found for class {$class_name}: {$file_path}");
		}
	}

	/**
	 * Get file path for class
	 *
	 * @param string $class_name Class name.
	 * @return string
	 */
	private function get_file_path($class_name)
	{
		// Remove namespace prefix.
		$relative_class = str_replace('Mylighthouse_Booker_', '', $class_name);

		// Convert CamelCase to kebab-case.
		$file_name = $this->camel_to_kebab($relative_class);

		// Determine the directory based on the class type.
		$directory = $this->get_directory_for_class($relative_class);

		$file_path = $this->base_path . $directory . '/' . $file_name . '.php';

		error_log("Autoloader: Class {$class_name} -> relative {$relative_class} -> file {$file_name} -> dir {$directory} -> path {$file_path}");

		return $file_path;
	}

	/**
	 * Convert CamelCase to kebab-case
	 *
	 * @param string $string String to convert.
	 * @return string
	 */
	private function camel_to_kebab($string)
	{
		return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $string));
	}

	/**
	 * Get directory for class type
	 *
	 * @param string $class_name Class name without prefix.
	 * @return string
	 */
	private function get_directory_for_class($class_name)
	{
		if (strpos($class_name, 'Admin_') === 0) {
			// Admin classes: Mylighthouse_Booker_Admin_Menu -> Admin/class-admin-menu
			$sub_class = str_replace('Admin_', '', $class_name);
			return 'Admin/' . $this->get_admin_subdirectory($sub_class);
		} elseif (strpos($class_name, 'Settings_') === 0) {
			// Settings classes: Mylighthouse_Booker_Settings_Fields -> Admin/Settings/class-settings-fields
			return 'Admin/Settings';
		} elseif (strpos($class_name, 'Frontend_') === 0) {
			// Frontend classes: Mylighthouse_Booker_Frontend_Assets -> Frontend/class-frontend-assets
			return 'Frontend';
		} elseif (strpos($class_name, 'Ajax_') === 0) {
			// AJAX classes: Mylighthouse_Booker_Ajax_Handlers -> API/class-ajax-handlers
			return 'API';
		} elseif (strpos($class_name, 'Template_') === 0) {
			// Template classes: Mylighthouse_Booker_Template_Loader -> Helpers/class-template-loader
			return 'Helpers';
		}

		return ''; // Root level classes
	}

	/**
	 * Get admin subdirectory
	 *
	 * @param string $class_name Class name.
	 * @return string
	 */
	private function get_admin_subdirectory($class_name)
	{
		if (strpos($class_name, 'Settings_') !== false) {
			return 'Settings';
		} elseif (strpos($class_name, 'Hotels_') !== false) {
			return 'Hotels';
		}

		return ''; // Root Admin directory
	}
}
