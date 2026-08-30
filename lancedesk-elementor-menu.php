<?php
/**
 * Plugin Name: LanceDesk Responsive Menu for Elementor
 * Description: Responsive Elementor menu widget with device-aware layout control. Build responsive menus in Elementor with per-device layout settings (horizontal/vertical) without creating multiple widget instances.
 * Version: 1.0.18
 * Author: Lance Desk
 * Author URI: https://lancedesk.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lancedesk-responsive-menu-for-elementor
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Requires Plugins: elementor
 */

/**
 * LanceDesk Responsive Menu for Elementor Plugin
 *
 * @package     LDJEM
 * @author      Lance Desk <hello@lancedesk.com>
 * @license     GPL-2.0+
 * @link        https://lancedesk.com
 * @copyright   2026 Lance Desk
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define Plugin Constants
 */
define('LDJEM_VERSION', '1.0.18');
define('LDJEM_PLUGIN_FILE', __FILE__);
define('LDJEM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LDJEM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LDJEM_PLUGIN_SLUG', 'lancedesk-responsive-menu-for-elementor');
define('LDJEM_TEXT_DOMAIN', 'lancedesk-responsive-menu-for-elementor');
define('LDJEM_PREFIX', 'ldjem');
define('LDJEM_CLASS_PREFIX', 'LDJEM_');

/**
 * Main Plugin Class Initialization
 * 
 * Verify WordPress and Elementor compatibility, then load plugin
 */
function ldjem_init_plugin() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        add_action('admin_notices', function() {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('LanceDesk Responsive Menu for Elementor requires PHP 7.4 or higher.', 'lancedesk-responsive-menu-for-elementor')
            );
        });
        return;
    }

    // Check WordPress version
    if (!function_exists('get_wordpress_version')) {
        global $wp_version;
        $wordpress_version = $wp_version;
    } else {
        $wordpress_version = get_wordpress_version();
    }

    if (version_compare($wordpress_version, '5.0', '<')) {
        add_action('admin_notices', function() {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('LanceDesk Responsive Menu for Elementor requires WordPress 5.0 or higher.', 'lancedesk-responsive-menu-for-elementor')
            );
        });
        return;
    }

    // Check Elementor plugin
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', function() {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    /* translators: %s: Required plugin name. */
                    esc_html__('LanceDesk Responsive Menu for Elementor requires %s to be installed and activated.', 'lancedesk-responsive-menu-for-elementor'),
                    '<strong>Elementor</strong>'
                )
            );
        });
        return;
    }

    // Elementor version check
    if (!defined('ELEMENTOR_VERSION') || version_compare(ELEMENTOR_VERSION, '3.0', '<')) {
        add_action('admin_notices', function() {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('LanceDesk Responsive Menu for Elementor requires Elementor 3.0 or higher.', 'lancedesk-responsive-menu-for-elementor')
            );
        });
        return;
    }

    // Load plugin autoloader and bootstrap
    require_once LDJEM_PLUGIN_DIR . 'includes/class-ldjem-autoloader.php';
    require_once LDJEM_PLUGIN_DIR . 'includes/class-ldjem-plugin.php';

    // Get plugin instance and run
    LDJEM_Plugin::instance();
}

// Hook into plugins_loaded to ensure Elementor is loaded first
add_action('plugins_loaded', 'ldjem_init_plugin', 15);

/**
 * Plugin Activation Hook
 */
register_activation_hook(LDJEM_PLUGIN_FILE, function() {
    require_once LDJEM_PLUGIN_DIR . 'includes/class-ldjem-activator.php';
    LDJEM_Activator::activate();
});

/**
 * Plugin Deactivation Hook
 */
register_deactivation_hook(LDJEM_PLUGIN_FILE, function() {
    require_once LDJEM_PLUGIN_DIR . 'includes/class-ldjem-deactivator.php';
    LDJEM_Deactivator::deactivate();
});

/**
 * Plugin Uninstall Hook
 * 
 * WordPress will look for uninstall.php automatically
 */
// See: uninstall.php file in plugin root
