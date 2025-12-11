<?php
/**
 * Template Loader
 * Loads and manages component templates
 *
 * @package Mylighthouse_Booker
 */

namespace Mylighthouse_Booker\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

class Template_Loader {
    
    private static $instance = null;
    private $templates_loaded = false;
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('wp_footer', [$this, 'load_global_components'], 1);
    }
    
    /**
     * Load all global component templates once in footer
     */
    public function load_global_components() {
        if ($this->templates_loaded) {
            return;
        }
        
        $components = [
            'date-picker-modal',
            'booking-details',
            'booking-results-modal'
        ];
        
        foreach ($components as $component) {
            $this->load_component($component);
        }
        
        $this->templates_loaded = true;
    }
    
    /**
     * Load a component template
     *
     * @param string $component_name Component name
     * @param array  $args          Arguments to pass to template
     */
    public function load_component($component_name, $args = []) {
        $template_path = $this->get_component_path($component_name);
        
        if (!file_exists($template_path)) {
            return;
        }
        
        // Extract args to make them available in template
        if (!empty($args)) {
            extract($args);
        }
        
        include $template_path;
    }
    
    /**
     * Get component template path
     *
     * @param string $component_name Component name
     * @return string Template path
     */
    private function get_component_path($component_name) {
        return MYLIGHTHOUSE_BOOKER_ABSPATH . 'templates/components/' . $component_name . '.php';
    }
    
    /**
     * Load widget template
     *
     * @param string $widget_name Widget name
     * @param array  $args        Arguments to pass to template
     * @return string Widget HTML
     */
    public function load_widget($widget_name, $args = []) {
        $template_path = MYLIGHTHOUSE_BOOKER_ABSPATH . 'templates/widgets/' . $widget_name . '.php';
        
        if (!file_exists($template_path)) {
            return '';
        }
        
        if (!empty($args)) {
            extract($args);
        }
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Check if a component template exists
     *
     * @param string $component_name Component name
     * @return bool
     */
    public function component_exists($component_name) {
        return file_exists($this->get_component_path($component_name));
    }
}
