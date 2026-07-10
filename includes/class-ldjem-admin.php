<?php
/**
 * LanceDesk Elementor Menu – Admin Handler
 * 
 * Manages admin functionality, settings, and notices
 * 
 * @package     LDJEM
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Handler Class
 * 
 * Manages admin panel, settings, and user guidance
 */
class LDJEM_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        $this->setup_hooks();
    }

    /**
     * Setup admin hooks
     * 
     * @return void
     */
    private function setup_hooks() {
        // Enqueue admin styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueue_editor_scripts']);

        // Show activation notice
        add_action('admin_notices', [$this, 'show_activation_notice']);

        // Check for Elementor on admin load
        add_action('admin_init', [$this, 'check_elementor_dependency']);
    }

    /**
     * Enqueue admin scripts and styles
     * 
     * @return void
     */
    public function enqueue_admin_scripts() {
        if (!$this->is_elementor_admin_page()) {
            return;
        }

        $this->register_editor_assets(false);
    }

    /**
     * Enqueue editor scripts from Elementor's editor hook.
     *
     * @return void
     */
    public function enqueue_editor_scripts() {
        $this->register_editor_assets(true);
    }

    /**
     * Register admin/editor assets.
     *
     * @param bool $force When true, skip the admin screen check.
     * @return void
     */
    private function register_editor_assets($force = false) {
        if (!$force && !$this->is_elementor_admin_page()) {
            return;
        }

        if (wp_script_is(LDJEM_PREFIX . '-admin', 'enqueued') || wp_script_is(LDJEM_PREFIX . '-admin', 'done')) {
            return;
        }

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $admin_css_rel = 'assets/css/ldjem-admin' . $suffix . '.css';
        $admin_js_rel = 'assets/js/ldjem-admin' . $suffix . '.js';

        if (!file_exists(LDJEM_PLUGIN_DIR . $admin_css_rel)) {
            $admin_css_rel = 'assets/css/ldjem-admin.css';
        }
        if (!file_exists(LDJEM_PLUGIN_DIR . $admin_js_rel)) {
            $admin_js_rel = 'assets/js/ldjem-admin.js';
        }

        $admin_js_path = LDJEM_PLUGIN_DIR . $admin_js_rel;
        $admin_js_ver = file_exists($admin_js_path) ? (string) filemtime($admin_js_path) : LDJEM_VERSION;
        $script_deps = ['jquery'];

        if (wp_script_is('elementor-editor', 'registered')) {
            $script_deps[] = 'elementor-editor';
        }

        // Enqueue admin stylesheet
        wp_enqueue_style(
            LDJEM_PREFIX . '-admin',
            LDJEM_PLUGIN_URL . $admin_css_rel,
            [],
            LDJEM_VERSION
        );

        // Enqueue admin script
        wp_enqueue_script(
            LDJEM_PREFIX . '-admin',
            LDJEM_PLUGIN_URL . $admin_js_rel,
            $script_deps,
            $admin_js_ver,
            true
        );

        // Localize script with data
        wp_localize_script(
            LDJEM_PREFIX . '-admin',
            'ldjemAdmin',
            [
                'prefix'      => LDJEM_PREFIX,
                'text_domain' => LDJEM_TEXT_DOMAIN,
                'widget_name' => LDJEM_PREFIX . '_menu',
                'preset_settings' => $this->get_preset_settings_for_editor(),
            ]
        );
    }

    /**
     * Prepare preset settings map for Elementor editor JS.
     *
     * @return array
     */
    private function get_preset_settings_for_editor() {
        if (!class_exists('LDJEM_Presets')) {
            return [];
        }

        $presets = LDJEM_Presets::get_all_presets();
        $settings_map = [];

        foreach ($presets as $preset_id => $preset_data) {
            $settings_map[$preset_id] = !empty($preset_data['settings']) && is_array($preset_data['settings'])
                ? $preset_data['settings']
                : [];
        }

        return $settings_map;
    }

    /**
     * Check if current page is Elementor admin page
     * 
     * @return bool
     */
    private function is_elementor_admin_page() {
        // Check if on Elementor editor or settings
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }

        // Check for elementor in screen
        return false !== strpos($screen->id, 'elementor');
    }

    /**
     * Show activation notice
     * 
     * @return void
     */
    public function show_activation_notice() {
        if (!get_transient(LDJEM_PREFIX . '_activated')) {
            return;
        }

        // Only show to admin users
        if (!current_user_can('manage_options')) {
            return;
        }

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            sprintf(
                /* translators: %s: Plugin name. */
                esc_html__('%s successfully activated! Start creating responsive menus in Elementor.', 'lancedesk-responsive-menu-for-elementor'),
                '<strong>LanceDesk Responsive Menu for Elementor</strong>'
            )
        );

        // Remove transient after display
        delete_transient(LDJEM_PREFIX . '_activated');
    }

    /**
     * Check Elementor dependency
     * 
     * @return void
     */
    public function check_elementor_dependency() {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Check if Elementor is still active
        if (!is_plugin_active('elementor/elementor.php')) {
            if (current_user_can('manage_options')) {
                add_action('admin_notices', function() {
                    printf(
                        '<div class="notice notice-warning"><p>%s</p></div>',
                        esc_html__('LanceDesk Responsive Menu for Elementor requires Elementor plugin to be active.', 'lancedesk-responsive-menu-for-elementor')
                    );
                });
            }
        }
    }
}
