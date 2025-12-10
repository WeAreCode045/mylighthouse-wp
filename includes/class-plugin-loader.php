<?php

/**
 * Plugin loader class
 *
 * @package StandaloneTech
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Plugin_Loader
 */
class Mylighthouse_Booker_Plugin_Loader
{
	/**
	 * Default locale for the plugin when WordPress falls back to en_US.
	 *
	 * @var string
	 */
	private $default_locale = 'nl_NL';

	/**
	 * Cached Dutch translations sourced from the packaged l10n file.
	 *
	 * @var array|null
	 */
	private $dutch_strings = null;

	/**
	 * Components
	 *
	 * @var array
	 */
	private $components = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->init_autoloader();
		$this->init_components();
		$this->init_hooks();
	}

	/**
	 * Initialize autoloader
	 */
	private function init_autoloader()
	{
		require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/class-autoloader.php';
		$autoloader = new Mylighthouse_Booker_Autoloader();
		$autoloader->register();

		// Also manually include key classes to ensure they load
		$this->include_key_classes();
	}

	/**
	 * Include key classes manually
	 */
	private function include_key_classes()
	{
		// Database classes
		require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Database/class-database-manager.php';
		
				require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Admin/class-admin-dashboard.php';
		// Admin assets (styles & scripts)
		require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Admin/class-admin-assets.php';

		// Ensure hotels page class is available for fragment rendering
		if (file_exists(MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Admin/hotels/class-hotels-page.php')) {
			require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Admin/hotels/class-hotels-page.php';
		}

		// Models (load explicitly to ensure availability for admin rendering)
		if (file_exists(MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Models/class-hotel.php')) {
			require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Models/class-hotel.php';
		}
		if (file_exists(MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Models/class-room.php')) {
			require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Models/class-room.php';
		}
		if (file_exists(MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Models/class-special.php')) {
			require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Models/class-special.php';
		}


		// Frontend classes
		require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Frontend/class-frontend-assets.php';
		require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Frontend/class-shortcode-booking-form.php';
		require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Frontend/class-shortcode-iframe-target.php';

		// Elementor integration (optional if Elementor is active)
		require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Elementor/class-elementor-loader.php';

		// API classes
		require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/API/class-ajax-handlers.php';

		// Helpers
		require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Helpers/class-template-loader.php';
	}

	/**
	 * Initialize components
	 */
	private function init_components()
	{
		// Database manager (always load)
		$this->components[] = new Mylighthouse_Booker_Database_Manager();


		// Frontend components should register even in admin so shortcodes render in
		// previews and builders (actual hooks like wp_enqueue_scripts only fire on
		// the frontend, so instantiating here is safe in both contexts).
		$this->components[] = new Mylighthouse_Booker_Frontend_Assets();
		$this->components[] = new Mylighthouse_Booker_Shortcode_Booking_Form();
		$this->components[] = new Mylighthouse_Booker_Shortcode_Iframe_Target();

		// AJAX components (always load for both admin and frontend)
		$this->components[] = new Mylighthouse_Booker_Ajax_Handlers();

		// Elementor components (load regardless of admin/frontend; widget will self-guard)
		$this->components[] = new Mylighthouse_Booker_Elementor_Loader();

		// Helper components
		Mylighthouse_Booker_Template_Loader::init();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks()
	{
		// Force the plugin to prefer Dutch strings unless the site selected another locale.
		add_filter('plugin_locale', array($this, 'filter_plugin_locale'), 10, 2);
		add_filter('gettext', array($this, 'force_dutch_strings'), 10, 3);
		add_filter('gettext_with_context', array($this, 'force_dutch_strings_with_context'), 10, 4);
		add_filter('ngettext', array($this, 'force_dutch_ngettext'), 10, 5);

		// Load plugin textdomain so PHP gettext wrappers resolve translations from /languages
		add_action('plugins_loaded', array($this, 'load_textdomain'));
	}

	/**
	 * Ensure Dutch translations load by default when no explicit locale override is set.
	 *
	 * @param string $locale Current locale being loaded.
	 * @param string $domain Textdomain requesting a locale.
	 * @return string
	 */
	public function filter_plugin_locale($locale, $domain)
	{
		if ($domain !== 'mylighthouse-booker') {
			return $locale;
		}

		$normalized = str_replace('-', '_', $locale);
		if (empty($normalized) || $normalized === 'nl_NL') {
			return $this->default_locale;
		}

		return $normalized;
	}

	/**
	 * Force singular strings to use Dutch translations regardless of site locale.
	 *
	 * @param string $translated Existing translation text.
	 * @param string $text Original string.
	 * @param string $domain Text domain.
	 * @return string
	 */
	public function force_dutch_strings($translated, $text, $domain)
	{
		if ($domain !== 'mylighthouse-booker') {
			return $translated;
		}

		if (! $this->is_dutch_locale_context()) {
			return $translated;
		}

		$map = $this->get_dutch_messages();
		if (! isset($map[$text]) || $map[$text] === '') {
			return $translated;
		}

		$value = $map[$text];
		if (! is_string($value) || $value === '') {
			return $translated;
		}

		if (strpos($value, "\0") !== false) {
			$parts = explode("\0", $value);
			return $parts[0];
		}

		return $value;
	}

	/**
	 * Apply Dutch translations even when context is present.
	 */
	public function force_dutch_strings_with_context($translated, $text, $context, $domain)
	{
		return $this->force_dutch_strings($translated, $text, $domain);
	}

	/**
	 * Force pluralized strings to use the Dutch plural forms.
	 */
	public function force_dutch_ngettext($translated, $single, $plural, $number, $domain)
	{
		if ($domain !== 'mylighthouse-booker') {
			return $translated;
		}

		if (! $this->is_dutch_locale_context()) {
			return $translated;
		}
		$map = $this->get_dutch_messages();
		if (! isset($map[$single]) || $map[$single] === '') {
			return $translated;
		}

		$value = $map[$single];
		if (! is_string($value) || $value === '') {
			return $translated;
		}

		$parts = explode("\0", $value);
		if (count($parts) === 1) {
			return $parts[0];
		}

		$index = ($number == 1) ? 0 : min(1, count($parts) - 1);
		return $parts[$index];
	}

	/**
	 * Load Dutch translations from the packaged l10n PHP file.
	 *
	 * @return array
	 */
	private function get_dutch_messages()
	{
		if (is_array($this->dutch_strings)) {
			return $this->dutch_strings;
		}

		$file = MYLIGHTHOUSE_BOOKER_ABSPATH . 'languages/mylighthouse-booker-nl_NL.l10n.php';
		$messages = array();
		if (file_exists($file)) {
			$data = include $file;
			if (is_array($data) && isset($data['messages']) && is_array($data['messages'])) {
				$messages = $data['messages'];
			}
		}

		$this->dutch_strings = $messages;
		return $this->dutch_strings;
	}

	/**
	 * Determine if the current locale should receive forced Dutch strings.
	 *
	 * @return bool
	 */
	private function is_dutch_locale_context()
	{
		$locale = '';
		if (function_exists('determine_locale')) {
			$locale = determine_locale();
		} elseif (function_exists('get_locale')) {
			$locale = get_locale();
		}

		if (empty($locale)) {
			return false;
		}

		$normalized = strtolower(str_replace('-', '_', $locale));
		return ($normalized === 'nl_nl' || strpos($normalized, 'nl_') === 0 || $normalized === 'nl');
	}
	/**
	 * Load plugin textdomain for translations
	 */
	public function load_textdomain()
	{
		// Use the plugin's main file basename so the languages path resolves to
		// `mylighthouse-booker/languages` (not `includes/languages`).
		load_plugin_textdomain(
			'mylighthouse-booker',
			false,
			dirname(plugin_basename(MYLIGHTHOUSE_BOOKER_PLUGIN_FILE)) . '/languages'
		);
	}



}
