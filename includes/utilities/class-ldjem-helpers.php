<?php
/**
 * LanceDesk Elementor Menu – Helpers Utility Class
 * 
 * Provides helper functions for menu retrieval, data formatting, etc.
 * 
 * @package     LDJEM
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helpers Utility Class
 * 
 * Provides utility methods for common operations
 */
class LDJEM_Helpers {

    /**
     * Get all registered WordPress menus
     * 
     * @return array Array of menus [menu_id => menu_name]
     */
    public static function get_registered_menus() {
        $menus = get_terms([
            'taxonomy'   => 'nav_menu',
            'hide_empty' => false,
        ]);

        if (is_wp_error($menus) || empty($menus)) {
            return [];
        }

        $menu_options = [];
        foreach ($menus as $menu) {
            $menu_options[$menu->term_id] = $menu->name;
        }

        return $menu_options;
    }

    /**
     * Get menu items with hierarchy
     * 
     * @param int $menu_id Menu ID
     * @param int $depth Maximum depth to retrieve
     * @param int $start_level Start level (0 for root items)
     * @return array Array of menu items
     */
    public static function get_menu_items($menu_id, $depth = -1, $start_level = 0) {
        // Verify menu exists
        $menu = wp_get_nav_menu_object($menu_id);
        if (empty($menu)) {
            return [];
        }

        // Get menu items
        $items = wp_get_nav_menu_items($menu_id);
        if (empty($items)) {
            return [];
        }

        // wp_get_nav_menu_items() does not mark the current page — that happens
        // inside wp_nav_menu() via _wp_menu_item_classes_by_context(). Without
        // this call, $item->current stays unset and active classes never render.
        _wp_menu_item_classes_by_context($items);

        // wp_nav_menu() then runs wp_nav_menu_objects — WooCommerce hooks here to
        // mark the shop page current on is_shop() (core only matches is_singular).
        $menu_args = (object) [
            'menu'  => $menu_id,
            'depth' => $depth,
        ];
        $items = apply_filters('wp_nav_menu_objects', $items, $menu_args);

        // Build hierarchical structure
        return self::build_menu_hierarchy($items, $depth, $start_level);
    }

    /**
     * Build hierarchical menu structure from flat array
     * 
     * @param array $items Flat array of menu items from wp_get_nav_menu_items()
     * @param int $depth Maximum depth (-1 for unlimited)
     * @param int $start_level Start level to include
     * @return array Hierarchical menu structure
     */
    private static function build_menu_hierarchy($items, $depth = -1, $start_level = 0) {
        if (empty($items)) {
            return [];
        }

        // Build normalized map first to avoid PHP foreach reference pitfalls.
        $nodes = [];
        foreach ($items as $item) {
            // Check depth limit.
            if ($depth > 0 && intval($item->menu_item_parent) !== 0) {
                $parent_depth = self::get_item_depth(intval($item->menu_item_parent), $items);
                if ($parent_depth >= $depth - 1) {
                    continue;
                }
            }

            $item->children = [];
            $nodes[intval($item->ID)] = $item;
        }

        // Attach children to parents.
        foreach ($nodes as $id => $node) {
            $parent_id = intval($node->menu_item_parent);
            if ($parent_id > 0 && isset($nodes[$parent_id])) {
                $nodes[$parent_id]->children[] = $node;
            }
        }

        // Collect roots and optionally filter by requested start level.
        $roots = [];
        foreach ($nodes as $node) {
            if (intval($node->menu_item_parent) === 0) {
                $roots[] = $node;
            }
        }

        if ($start_level <= 0) {
            return $roots;
        }

        return self::collect_nodes_at_level($roots, intval($start_level), 0);
    }

    /**
     * Collect nodes for a target nesting level.
     *
     * @param array $nodes Hierarchical nodes.
     * @param int $target_level Desired level.
     * @param int $current_level Current traversal level.
     * @return array
     */
    private static function collect_nodes_at_level($nodes, $target_level, $current_level) {
        $result = [];

        foreach ($nodes as $node) {
            if ($current_level === $target_level) {
                $result[] = $node;
                continue;
            }

            if (!empty($node->children)) {
                $result = array_merge(
                    $result,
                    self::collect_nodes_at_level($node->children, $target_level, $current_level + 1)
                );
            }
        }

        return $result;
    }

    /**
     * Get depth of a menu item in the hierarchy
     * 
     * @param int $item_id Menu item ID
     * @param array $items All menu items
     * @param int $depth Current depth count
     * @return int Item depth
     */
    private static function get_item_depth($item_id, $items, $depth = 0) {
        // Find item parent
        foreach ($items as $item) {
            if ($item->ID === $item_id && $item->menu_item_parent > 0) {
                return self::get_item_depth($item->menu_item_parent, $items, $depth + 1);
            }
        }

        return $depth;
    }

    /**
     * Get responsive breakpoints
     * 
     * @return array Breakpoints [name => value]
     */
    public static function get_breakpoints() {
        $breakpoints = [
            'mobile'  => 767,
            'tablet'  => 1024,
            'desktop' => 1920,
        ];

        // Allow filtering
        return apply_filters(LDJEM_PREFIX . '_breakpoints', $breakpoints);
    }

    /**
     * Get available layout options
     * 
     * @return array Layout options
     */
    public static function get_layout_options() {
        return [
            'horizontal' => esc_html__('Horizontal', 'lancedesk-responsive-menu-for-elementor'),
            'vertical'   => esc_html__('Vertical', 'lancedesk-responsive-menu-for-elementor'),
            'grid'       => esc_html__('Grid', 'lancedesk-responsive-menu-for-elementor'),
        ];
    }

    /**
     * Get available animation options
     * 
     * @return array Animation options
     */
    public static function get_animation_options() {
        return [
            'fade'   => esc_html__('Fade', 'lancedesk-responsive-menu-for-elementor'),
            'slide'  => esc_html__('Slide', 'lancedesk-responsive-menu-for-elementor'),
            'bounce' => esc_html__('Bounce', 'lancedesk-responsive-menu-for-elementor'),
            'none'   => esc_html__('None', 'lancedesk-responsive-menu-for-elementor'),
        ];
    }

    /**
     * Get submenu trigger options
     * 
     * @return array Trigger options
     */
    public static function get_submenu_trigger_options() {
        return [
            'hover'       => esc_html__('Hover', 'lancedesk-responsive-menu-for-elementor'),
            'click'       => esc_html__('Click', 'lancedesk-responsive-menu-for-elementor'),
            'hover_click' => esc_html__('Hover & Click', 'lancedesk-responsive-menu-for-elementor'),
        ];
    }

    /**
     * Format CSS class string
     * 
     * @param array|string $classes Classes to format
     * @return string
     */
    public static function format_class($classes) {
        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }

        return trim($classes);
    }

    /**
     * Get plugin documentation URL
     * 
     * @return string
     */
    public static function get_documentation_url() {
        return 'https://lancedesk.com/docs/elementor-menu/';
    }

    /**
     * Get plugin support URL
     * 
     * @return string
     */
    public static function get_support_url() {
        return 'https://lancedesk.com/support/';
    }

    /**
     * Log debug message (only in WP_DEBUG mode)
     * 
     * @param string $message Message to log
     * @param string $context Context/category
     * @return void
     */
    public static function log_debug($message, $context = 'general') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        do_action(
            'ldjem_debug_log',
            sprintf(
                '[LDJEM_%s] %s',
                strtoupper($context),
                $message
            )
        );
    }
}
