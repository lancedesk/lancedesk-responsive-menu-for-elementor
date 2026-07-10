<?php
/**
 * LanceDesk Elementor Menu – Frontend Handler
 * 
 * Manages frontend script and style loading
 * 
 * @package     LDJEM
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend Handler Class
 * 
 * Manages frontend assets and initialization
 */
class LDJEM_Frontend {

    /**
     * Constructor
     */
    public function __construct() {
        $this->setup_hooks();
    }

    /**
     * Setup frontend hooks
     * 
     * @return void
     */
    private function setup_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_offcanvas_styles_fallback'], 20);
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_elementor_frontend_scripts']);
    }

    /**
     * Enqueue frontend scripts and styles
     * 
     * @return void
     */
    public function enqueue_frontend_scripts() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $frontend_css_rel = 'assets/css/ldjem-frontend' . $suffix . '.css';
        $frontend_js_rel = 'assets/js/ldjem-frontend' . $suffix . '.js';

        // Fallback to non-minified assets when minified files are unavailable.
        if (!file_exists(LDJEM_PLUGIN_DIR . $frontend_css_rel)) {
            $frontend_css_rel = 'assets/css/ldjem-frontend.css';
        }
        if (!file_exists(LDJEM_PLUGIN_DIR . $frontend_js_rel)) {
            $frontend_js_rel = 'assets/js/ldjem-frontend.js';
        }
        $frontend_css_path = LDJEM_PLUGIN_DIR . $frontend_css_rel;
        $frontend_js_path = LDJEM_PLUGIN_DIR . $frontend_js_rel;
        $offcanvas_css_rel = 'assets/css/ldjem-offcanvas.css';
        $offcanvas_js_rel = 'assets/js/ldjem-offcanvas.js';
        $offcanvas_css_path = LDJEM_PLUGIN_DIR . $offcanvas_css_rel;
        $offcanvas_js_path = LDJEM_PLUGIN_DIR . $offcanvas_js_rel;

        // Use file modified time for aggressive cache-busting after plugin updates.
        $frontend_css_ver = file_exists($frontend_css_path) ? (string) filemtime($frontend_css_path) : LDJEM_VERSION;
        $frontend_js_ver = file_exists($frontend_js_path) ? (string) filemtime($frontend_js_path) : LDJEM_VERSION;
        $offcanvas_css_ver = file_exists($offcanvas_css_path) ? (string) filemtime($offcanvas_css_path) : LDJEM_VERSION;
        $offcanvas_js_ver = file_exists($offcanvas_js_path) ? (string) filemtime($offcanvas_js_path) : LDJEM_VERSION;

        // Enqueue frontend stylesheet
        wp_enqueue_style(
            LDJEM_PREFIX . '-frontend',
            LDJEM_PLUGIN_URL . $frontend_css_rel,
            [],
            $frontend_css_ver
        );

        // Enqueue frontend script
        wp_enqueue_script(
            LDJEM_PREFIX . '-frontend',
            LDJEM_PLUGIN_URL . $frontend_js_rel,
            ['jquery'],
            $frontend_js_ver,
            true
        );

        wp_enqueue_script(
            LDJEM_PREFIX . '-offcanvas',
            LDJEM_PLUGIN_URL . $offcanvas_js_rel,
            ['jquery'],
            $offcanvas_js_ver,
            true
        );

        wp_localize_script(
            LDJEM_PREFIX . '-offcanvas',
            'ldjemOffcanvas',
            [
                'breakpoints' => $this->get_breakpoints(),
            ]
        );

        // Localize script with data
        wp_localize_script(
            LDJEM_PREFIX . '-frontend',
            'ldjemFrontend',
            [
                'prefix'              => LDJEM_PREFIX,
                'text_domain'         => LDJEM_TEXT_DOMAIN,
                'breakpoints'         => $this->get_breakpoints(),
                'enable_animations'   => apply_filters(LDJEM_PREFIX . '_enable_animations', true),
            ]
        );
    }

    /**
     * Fallback off-canvas styles when Elementor frontend hook did not run.
     *
     * @return void
     */
    public function enqueue_offcanvas_styles_fallback() {
        if (wp_style_is(LDJEM_PREFIX . '-offcanvas', 'enqueued') || wp_style_is(LDJEM_PREFIX . '-offcanvas', 'done')) {
            return;
        }

        $this->enqueue_offcanvas_styles();
    }

    /**
     * Register off-canvas stylesheet with Elementor dependency when possible.
     *
     * @return void
     */
    private function enqueue_offcanvas_styles() {
        $offcanvas_css_rel = 'assets/css/ldjem-offcanvas.css';
        $offcanvas_css_path = LDJEM_PLUGIN_DIR . $offcanvas_css_rel;
        $offcanvas_css_ver = file_exists($offcanvas_css_path) ? (string) filemtime($offcanvas_css_path) : LDJEM_VERSION;
        $style_deps = [];

        if (wp_style_is('elementor-frontend', 'registered')) {
            $style_deps[] = 'elementor-frontend';
        }

        wp_enqueue_style(
            LDJEM_PREFIX . '-offcanvas',
            LDJEM_PLUGIN_URL . $offcanvas_css_rel,
            $style_deps,
            $offcanvas_css_ver
        );
    }

    /**
     * Enqueue scripts for Elementor editor/frontend
     * 
     * @return void
     */
    public function enqueue_elementor_frontend_scripts() {
        $this->enqueue_offcanvas_styles();
    }

    /**
     * Get responsive breakpoints
     * 
     * @return array
     */
    private function get_breakpoints() {
        // Get Elementor breakpoints if available
        if (class_exists('Elementor\Core\Breakpoints\Manager')) {
            try {
                $breakpoints_manager = \Elementor\Core\Breakpoints\Manager::instance();
                $breakpoints = [];

                foreach ($breakpoints_manager->get_breakpoints() as $breakpoint) {
                    $breakpoints[$breakpoint->get_name()] = $breakpoint->get_value();
                }

                return $breakpoints;
            } catch (Exception $e) {
                // Fall back to defaults if Elementor API fails
            }
        }

        // Default Elementor breakpoints (fallback)
        return [
            'mobile'  => 767,
            'tablet'  => 1024,
            'desktop' => 1920,
        ];
    }
}
