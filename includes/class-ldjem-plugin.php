<?php
/**
 * LanceDesk Elementor Menu – Main Plugin Class
 * 
 * Core plugin orchestration using Singleton pattern
 * 
 * @package     LDJEM
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class (Singleton)
 * 
 * Orchestrates plugin initialization, module loading, and hook management
 */
class LDJEM_Plugin {

    /**
     * Singleton instance
     * 
     * @var LDJEM_Plugin|null
     */
    private static $instance = null;

    /**
     * Flag for initialization state
     * 
     * @var bool
     */
    private $initialized = false;

    /**
     * Loaded modules
     * 
     * @var array
     */
    private $modules = [];

    /**
     * Get singleton instance
     * 
     * @return LDJEM_Plugin
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor (private for singleton)
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize plugin
     * 
     * @return void
     */
    private function init() {
        if ($this->initialized) {
            return;
        }

        // Load dependencies
        $this->load_dependencies();

        // Initialize modules
        $this->setup_modules();

        // Setup hooks
        $this->setup_hooks();

        $this->initialized = true;
    }

    /**
     * Load plugin dependencies
     * 
     * @return void
     */
    private function load_dependencies() {
        // Load utility classes
        require_once LDJEM_PLUGIN_DIR . 'includes/utilities/class-ldjem-security.php';
        require_once LDJEM_PLUGIN_DIR . 'includes/utilities/class-ldjem-helpers.php';
        require_once LDJEM_PLUGIN_DIR . 'includes/utilities/class-ldjem-validator.php';

        // Load core classes
        require_once LDJEM_PLUGIN_DIR . 'includes/class-ldjem-admin.php';
        require_once LDJEM_PLUGIN_DIR . 'includes/class-ldjem-frontend.php';
    }

    /**
     * Setup plugin modules
     * 
     * @return void
     */
    private function setup_modules() {
        // Initialize admin module
        $this->modules['admin'] = new LDJEM_Admin();

        // Initialize frontend module
        $this->modules['frontend'] = new LDJEM_Frontend();
    }

    /**
     * Setup WordPress hooks
     * 
     * @return void
     */
    private function setup_hooks() {
        // Register widget with Elementor
        add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);

        // Admin initialization
        add_action('admin_init', [$this, 'admin_init']);
    }

    /**
     * Register custom widgets with Elementor
     * 
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager
     * @return void
     */
    public function register_widgets($widgets_manager) {
        // Check if widget class exists and load it
        if (!class_exists('LDJEM_Menu_Widget')) {
            require_once LDJEM_PLUGIN_DIR . 'includes/widgets/class-ldjem-menu-widget.php';
        }

        // Register the widget
        try {
            $widgets_manager->register(new LDJEM_Menu_Widget());
        } catch (Exception $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            // Swallow registration failure to avoid hard-failing wp-admin.
        }
    }

    /**
     * Admin initialization
     * 
     * @return void
     */
    public function admin_init() {
        // Any admin-specific initialization
    }

    /**
     * Get module instance
     * 
     * @param string $module_name Module name
     * @return object|null Module instance or null if not found
     */
    public function get_module($module_name) {
        return isset($this->modules[$module_name]) ? $this->modules[$module_name] : null;
    }

    /**
     * Check if module is loaded
     * 
     * @param string $module_name Module name
     * @return bool
     */
    public function has_module($module_name) {
        return isset($this->modules[$module_name]);
    }

    /**
     * Prevent cloning
     */
    public function __clone() {
        _doing_it_wrong(__FUNCTION__, esc_html__('Cloning is not allowed for this singleton.', 'lancedesk-responsive-menu-for-elementor'), esc_html(LDJEM_VERSION));
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, esc_html__('Unserializing is not allowed for this singleton.', 'lancedesk-responsive-menu-for-elementor'), esc_html(LDJEM_VERSION));
    }
}
