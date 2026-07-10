<?php
/**
 * LanceDesk Elementor Menu – Off-Canvas Presets
 * 
 * Provides predefined off-canvas configurations and preset utilities
 * 
 * @package     LDJEM
 * @since       1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Off-Canvas Presets Class
 * 
 * Manages configuration presets for quick menu setup
 */
class LDJEM_Presets {

    /**
     * Get all available presets
     * 
     * @return array Preset configurations keyed by preset ID
     */
    public static function get_all_presets() {
        return [
            'none' => self::get_preset_none(),
            'classic-sidebar' => self::get_preset_classic_sidebar(),
            'modern-dark' => self::get_preset_modern_dark(),
            'top-navigation' => self::get_preset_top_navigation(),
            'full-overlay' => self::get_preset_full_overlay(),
        ];
    }

    /**
     * Get preset by ID
     * 
     * @param string $preset_id Preset identifier
     * @return array Preset configuration
     */
    public static function get_preset($preset_id) {
        $presets = self::get_all_presets();
        return isset($presets[$preset_id]) ? $presets[$preset_id] : [];
    }

    /**
     * Get preset labels for dropdown
     * 
     * @return array Preset labels keyed by preset ID
     */
    public static function get_preset_labels() {
        return [
            'none' => esc_html__('Custom (No Preset)', 'lancedesk-responsive-menu-for-elementor'),
            'classic-sidebar' => esc_html__('Classic Sidebar - Left', 'lancedesk-responsive-menu-for-elementor'),
            'modern-dark' => esc_html__('Modern Dark - Right', 'lancedesk-responsive-menu-for-elementor'),
            'top-navigation' => esc_html__('Top Navigation Bar', 'lancedesk-responsive-menu-for-elementor'),
            'full-overlay' => esc_html__('Full Overlay - Left', 'lancedesk-responsive-menu-for-elementor'),
        ];
    }

    /**
     * Preset: Custom (No Preset)
     * 
     * Returns empty config - user defines all settings manually
     */
    private static function get_preset_none() {
        return [
            'name' => esc_html__('Custom', 'lancedesk-responsive-menu-for-elementor'),
            'description' => esc_html__('Define all settings manually', 'lancedesk-responsive-menu-for-elementor'),
            'settings' => [],
        ];
    }

    /**
     * Preset: Classic Sidebar (Left)
     * 
     * Traditional left-side panel menu for mobile/tablet
     * Desktop shows horizontal navigation
     */
    private static function get_preset_classic_sidebar() {
        return [
            'name' => esc_html__('Classic Sidebar', 'lancedesk-responsive-menu-for-elementor'),
            'description' => esc_html__('Left sidebar on mobile, horizontal on desktop', 'lancedesk-responsive-menu-for-elementor'),
            'settings' => [
                // General settings
                'offcanvas_enable' => 'yes',
                'offcanvas_direction' => 'left',
                
                // Sizing
                'offcanvas_panel_size' => ['size' => 300, 'unit' => 'px'],
                'offcanvas_animation_duration' => 300,
                'offcanvas_animation_easing' => 'ease-in-out',
                
                // Colors
                'offcanvas_bg_color' => '#ffffff',
                
                // Header
                'offcanvas_show_header' => 'yes',
                'offcanvas_header_text' => esc_html__('Menu', 'lancedesk-responsive-menu-for-elementor'),
                'offcanvas_header_bg_color' => '#ffffff',
                'offcanvas_show_close_btn' => 'yes',
                'offcanvas_close_icon' => [
                    'value'   => 'fas fa-times',
                    'library' => 'fa-solid',
                ],
                
                // Footer
                'offcanvas_show_footer' => 'yes',
                'offcanvas_footer_title' => esc_html__('Follow Us', 'lancedesk-responsive-menu-for-elementor'),
                'offcanvas_footer_bg_color' => '#f9f9f9',
                'offcanvas_social_icon_size' => 36,
                
                // Device behavior
                'offcanvas_on_tablet' => 'yes',
                'offcanvas_on_desktop' => '',
                'offcanvas_on_mobile' => 'yes',
                'offcanvas_direction_desktop' => 'inherit',
                'offcanvas_direction_tablet' => 'inherit',
                'offcanvas_direction_mobile' => 'left',
                'offcanvas_animation_duration_desktop' => 0,
                'offcanvas_animation_duration_tablet' => 0,
                'offcanvas_animation_duration_mobile' => 300,
            ],
        ];
    }

    /**
     * Preset: Modern Dark (Right)
     * 
     * Right-side dark menu with modern aesthetic
     * Sleek animation and premium styling
     */
    private static function get_preset_modern_dark() {
        return [
            'name' => esc_html__('Modern Dark', 'lancedesk-responsive-menu-for-elementor'),
            'description' => esc_html__('Right sidebar with dark theme, premium feel', 'lancedesk-responsive-menu-for-elementor'),
            'settings' => [
                // General settings
                'offcanvas_enable' => 'yes',
                'offcanvas_direction' => 'right',
                
                // Sizing
                'offcanvas_panel_size' => ['size' => 320, 'unit' => 'px'],
                'offcanvas_animation_duration' => 400,
                'offcanvas_animation_easing' => 'ease-out',
                
                // Colors
                'offcanvas_bg_color' => '#1a1a1a',
                
                // Header
                'offcanvas_show_header' => 'yes',
                'offcanvas_header_text' => esc_html__('Navigate', 'lancedesk-responsive-menu-for-elementor'),
                'offcanvas_header_bg_color' => '#242424',
                'offcanvas_show_close_btn' => 'yes',
                'offcanvas_close_icon' => [
                    'value'   => 'fas fa-arrow-left',
                    'library' => 'fa-solid',
                ],
                
                // Footer
                'offcanvas_show_footer' => 'yes',
                'offcanvas_footer_title' => esc_html__('Connect', 'lancedesk-responsive-menu-for-elementor'),
                'offcanvas_footer_bg_color' => '#242424',
                'offcanvas_social_icon_size' => 40,
                
                // Device behavior
                'offcanvas_on_tablet' => 'yes',
                'offcanvas_on_desktop' => '',
                'offcanvas_on_mobile' => 'yes',
                'offcanvas_direction_desktop' => 'inherit',
                'offcanvas_direction_tablet' => 'right',
                'offcanvas_direction_mobile' => 'right',
                'offcanvas_animation_duration_desktop' => 0,
                'offcanvas_animation_duration_tablet' => 350,
                'offcanvas_animation_duration_mobile' => 400,
            ],
        ];
    }

    /**
     * Preset: Top Navigation Bar
     * 
     * Full-width horizontal menu that slides down from top
     * Great for sites with limited horizontal space
     */
    private static function get_preset_top_navigation() {
        return [
            'name' => esc_html__('Top Navigation', 'lancedesk-responsive-menu-for-elementor'),
            'description' => esc_html__('Full-width menu slides down from top', 'lancedesk-responsive-menu-for-elementor'),
            'settings' => [
                // General settings
                'offcanvas_enable' => 'yes',
                'offcanvas_direction' => 'top',
                
                // Sizing
                'offcanvas_panel_size' => ['size' => 400, 'unit' => 'px'],
                'offcanvas_panel_height' => ['size' => 300, 'unit' => 'px'],
                'offcanvas_animation_duration' => 350,
                'offcanvas_animation_easing' => 'ease-in-out',
                
                // Colors
                'offcanvas_bg_color' => '#ffffff',
                
                // Header
                'offcanvas_show_header' => 'no',
                'offcanvas_show_close_btn' => 'no',
                
                // Footer
                'offcanvas_show_footer' => 'no',
                
                // Device behavior
                'offcanvas_on_tablet' => 'yes',
                'offcanvas_on_desktop' => '',
                'offcanvas_on_mobile' => 'yes',
                'offcanvas_direction_desktop' => 'inherit',
                'offcanvas_direction_tablet' => 'top',
                'offcanvas_direction_mobile' => 'top',
                'offcanvas_animation_duration_desktop' => 0,
                'offcanvas_animation_duration_tablet' => 350,
                'offcanvas_animation_duration_mobile' => 350,
            ],
        ];
    }

    /**
     * Preset: Full Overlay (Left)
     * 
     * Full-height, full-width overlay menu with dark backdrop
     * Maximum visibility for mobile users
     */
    private static function get_preset_full_overlay() {
        return [
            'name' => esc_html__('Full Overlay', 'lancedesk-responsive-menu-for-elementor'),
            'description' => esc_html__('Full-screen overlay menu for maximum mobile focus', 'lancedesk-responsive-menu-for-elementor'),
            'settings' => [
                // General settings
                'offcanvas_enable' => 'yes',
                'offcanvas_direction' => 'left',
                
                // Sizing
                'offcanvas_panel_size' => ['size' => 420, 'unit' => 'px'],
                'offcanvas_animation_duration' => 300,
                'offcanvas_animation_easing' => 'ease-in-out',
                
                // Colors
                'offcanvas_bg_color' => '#ffffff',
                
                // Header
                'offcanvas_show_header' => 'yes',
                'offcanvas_header_text' => esc_html__('Menu', 'lancedesk-responsive-menu-for-elementor'),
                'offcanvas_header_bg_color' => '#f5f5f5',
                'offcanvas_show_close_btn' => 'yes',
                'offcanvas_close_icon' => [
                    'value'   => 'fas fa-chevron-left',
                    'library' => 'fa-solid',
                ],
                
                // Footer
                'offcanvas_show_footer' => 'yes',
                'offcanvas_footer_title' => esc_html__('Social Media', 'lancedesk-responsive-menu-for-elementor'),
                'offcanvas_footer_bg_color' => '#f5f5f5',
                'offcanvas_social_icon_size' => 44,
                
                // Device behavior
                'offcanvas_on_tablet' => 'no',
                'offcanvas_on_desktop' => '',
                'offcanvas_on_mobile' => 'yes',
                'offcanvas_direction_desktop' => 'inherit',
                'offcanvas_direction_tablet' => 'inherit',
                'offcanvas_direction_mobile' => 'left',
                'offcanvas_animation_duration_desktop' => 0,
                'offcanvas_animation_duration_tablet' => 0,
                'offcanvas_animation_duration_mobile' => 300,
            ],
        ];
    }

    /**
     * Apply preset settings to widget settings array
     * 
     * @param array $current_settings Current widget settings
     * @param string $preset_id ID of preset to apply
     * @return array Merged settings (preset + existing overrides)
     */
    public static function apply_preset(&$current_settings, $preset_id) {
        $preset = self::get_preset($preset_id);
        
        if (empty($preset['settings'])) {
            return $current_settings;
        }

        // Merge preset settings with current settings
        // Current settings take precedence over preset defaults
        foreach ($preset['settings'] as $key => $value) {
            // Only apply when setting is truly missing/unset.
            // This preserves explicit user choices like switcher "off" values.
            if (!array_key_exists($key, $current_settings) || null === $current_settings[$key]) {
                $current_settings[$key] = $value;
            }
        }

        return $current_settings;
    }
}
