<?php
/**
 * LanceDesk Elementor Menu – Menu Widget
 * 
 * Core widget class extending Elementor Widget_Base
 * Implements responsive menu with device-aware layout controls
 * 
 * @package     LDJEM
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;
use Elementor\Repeater;
use Elementor\Icons_Manager;

/**
 * Menu Widget Class
 * 
 * Main widget for displaying responsive menus in Elementor
 */
class LDJEM_Menu_Widget extends Widget_Base {

    /**
     * Get widget name
     * 
     * @return string Unique widget name (no spaces, lowercase with underscore)
     */
    public function get_name() {
        return LDJEM_PREFIX . '_menu';
    }

    /**
     * Get widget title
     * 
     * @return string Display name for widget selector
     */
    public function get_title() {
        return esc_html__('LanceDesk Responsive Menu', 'lancedesk-responsive-menu-for-elementor');
    }

    /**
     * Get widget icon
     * 
     * @return string Elementor icon name
     */
    public function get_icon() {
        return 'eicon-nav-menu';
    }

    /**
     * Get widget categories
     * 
     * @return array Categories for widget grouping
     */
    public function get_categories() {
        return ['navigation'];
    }

    /**
     * Get widget keywords
     * 
     * @return array Keywords for search
     */
    public function get_keywords() {
        return ['menu', 'navigation', 'nav', 'responsive', 'hamburger', 'mobile'];
    }

    /**
     * Get custom help URL
     * 
     * @return string URL to documentation
     */
    public function get_custom_help_url() {
        return LDJEM_Helpers::get_documentation_url();
    }

    /**
     * Opt out of Elementor Element Cache.
     *
     * Current-page classes (current-menu-item / is-active) depend on the
     * request URL and must be rendered fresh on every page.
     *
     * @return bool
     */
    protected function is_dynamic_content(): bool {
        return true;
    }

    /**
     * Register widget controls
     * 
     * Called by Elementor to register all widget settings panels
     * 
     * @return void
     */
    protected function register_controls() {
        // Content Tab - Menu Selection
        $this->register_content_controls();

        // Display Mode - per-device Standard vs Off-Canvas (top of Content tab)
        $this->register_display_mode_controls();

        // Responsive layout for devices using Standard menu
        $this->register_responsive_controls();

        // Off-Canvas Menu settings (when at least one device uses off-canvas)
        $this->register_offcanvas_controls();

        // Per-device off-canvas overrides (direction, animation, panel size)
        $this->register_breakpoint_controls();

        // Style Tab - Menu Items
        $this->register_style_menu_items();

        // Style Tab - Submenus
        $this->register_style_submenus();

        // Style Tab - Off Canvas (panel, toggle, links, footer)
        $this->register_style_offcanvas();

        // Advanced Tab - Extra settings
        $this->register_advanced_controls();
    }

    /**
     * Conditions when off-canvas is enabled on at least one device.
     *
     * @return array<string, mixed>
     */
    private function get_offcanvas_active_conditions() {
        return [
            'relation' => 'and',
            'terms'    => [
                [
                    'name'     => 'offcanvas_enable',
                    'operator' => '===',
                    'value'    => 'yes',
                ],
                [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_on_desktop',
                            'operator' => '===',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_tablet',
                            'operator' => '===',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_mobile',
                            'operator' => '===',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Register display mode controls (master off-canvas toggle + per-device mode).
     *
     * @return void
     */
    private function register_display_mode_controls() {
        $menu_options = LDJEM_Helpers::get_registered_menus();
        if (empty($menu_options)) {
            $menu_options = ['' => esc_html__('No menus available', 'lancedesk-responsive-menu-for-elementor')];
        }

        $this->start_controls_section(
            'section_display_mode',
            [
                'label' => esc_html__('Display Mode', 'lancedesk-responsive-menu-for-elementor'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'display_mode_help',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__(
                    'Choose Standard Menu or Off-Canvas for each screen size. Responsive Layout controls apply only to devices set to Standard. Off-Canvas settings appear when at least one device uses Off-Canvas.',
                    'lancedesk-responsive-menu-for-elementor'
                ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $this->add_control(
            'offcanvas_enable',
            [
                'label'        => esc_html__('Enable Off-Canvas', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => esc_html__(
                    'When disabled, all devices use the standard inline menu. Responsive Layout controls apply to every device.',
                    'lancedesk-responsive-menu-for-elementor'
                ),
            ]
        );

        $device_mode_options = [
            ''    => esc_html__('Standard Menu', 'lancedesk-responsive-menu-for-elementor'),
            'yes' => esc_html__('Off-Canvas', 'lancedesk-responsive-menu-for-elementor'),
        ];

        $this->add_control(
            'offcanvas_on_desktop',
            [
                'label'     => esc_html__('Desktop (>1024px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::SELECT,
                'options'   => $device_mode_options,
                'default'   => '',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_on_tablet',
            [
                'label'     => esc_html__('Tablet (768–1024px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::SELECT,
                'options'   => $device_mode_options,
                'default'   => 'yes',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_on_mobile',
            [
                'label'     => esc_html__('Mobile (<768px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::SELECT,
                'options'   => $device_mode_options,
                'default'   => 'yes',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'display_mode_all_offcanvas_tip',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__(
                    'Tip: Set Desktop, Tablet, and Mobile to Off-Canvas to use the slide-out menu on every screen size.',
                    'lancedesk-responsive-menu-for-elementor'
                ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                'conditions'      => $this->get_offcanvas_active_conditions(),
            ]
        );

        $this->add_control(
            'offcanvas_menus_heading',
            [
                'label'      => esc_html__('Off-Canvas Menus', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::HEADING,
                'separator'  => 'before',
                'conditions' => $this->get_offcanvas_active_conditions(),
            ]
        );

        $this->add_control(
            'use_offcanvas_device_specific_menus',
            [
                'label'        => esc_html__('Use Off-Canvas Specific Menus', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => esc_html__(
                    'When enabled, off-canvas can use different menus per device. Otherwise the main menu selection is used.',
                    'lancedesk-responsive-menu-for-elementor'
                ),
                'conditions'   => $this->get_offcanvas_active_conditions(),
            ]
        );

        $this->add_control(
            'offcanvas_menu_id_desktop',
            [
                'label'       => esc_html__('Off-Canvas Desktop Menu', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $menu_options,
                'default'     => '',
                'label_block' => true,
                'conditions'  => [
                    'relation' => 'and',
                    'terms'    => array_merge(
                        $this->get_offcanvas_active_conditions()['terms'],
                        [
                            [
                                'name'     => 'use_offcanvas_device_specific_menus',
                                'operator' => '===',
                                'value'    => 'yes',
                            ],
                            [
                                'name'     => 'offcanvas_on_desktop',
                                'operator' => '===',
                                'value'    => 'yes',
                            ],
                        ]
                    ),
                ],
            ]
        );

        $this->add_control(
            'offcanvas_menu_id_tablet',
            [
                'label'       => esc_html__('Off-Canvas Tablet Menu', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $menu_options,
                'default'     => '',
                'label_block' => true,
                'conditions'  => [
                    'relation' => 'and',
                    'terms'    => array_merge(
                        $this->get_offcanvas_active_conditions()['terms'],
                        [
                            [
                                'name'     => 'use_offcanvas_device_specific_menus',
                                'operator' => '===',
                                'value'    => 'yes',
                            ],
                            [
                                'name'     => 'offcanvas_on_tablet',
                                'operator' => '===',
                                'value'    => 'yes',
                            ],
                        ]
                    ),
                ],
            ]
        );

        $this->add_control(
            'offcanvas_menu_id_mobile',
            [
                'label'       => esc_html__('Off-Canvas Mobile Menu', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $menu_options,
                'default'     => '',
                'label_block' => true,
                'conditions'  => [
                    'relation' => 'and',
                    'terms'    => array_merge(
                        $this->get_offcanvas_active_conditions()['terms'],
                        [
                            [
                                'name'     => 'use_offcanvas_device_specific_menus',
                                'operator' => '===',
                                'value'    => 'yes',
                            ],
                            [
                                'name'     => 'offcanvas_on_mobile',
                                'operator' => '===',
                                'value'    => 'yes',
                            ],
                        ]
                    ),
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register content tab controls (menu selection, depth, etc)
     * 
     * @return void
     */
    private function register_content_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'lancedesk-responsive-menu-for-elementor'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Menu Selection
        $menu_options = LDJEM_Helpers::get_registered_menus();
        if (empty($menu_options)) {
            $menu_options = ['' => esc_html__('No menus available', 'lancedesk-responsive-menu-for-elementor')];
        }

        $this->add_control(
            'menu_id',
            [
                'label'       => esc_html__('Menu', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $menu_options,
                'default'     => '',
                'label_block' => true,
                'description' => esc_html__('Select a menu to display', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        $this->add_control(
            'use_device_specific_menus',
            [
                'label'        => esc_html__('Use Device-Specific Menus', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => esc_html__('Allow desktop, tablet, and mobile to use different menus.', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        $this->add_control(
            'menu_id_desktop',
            [
                'label'       => esc_html__('Desktop Menu', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $menu_options,
                'default'     => '',
                'label_block' => true,
                'condition'   => [
                    'use_device_specific_menus' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'menu_id_tablet',
            [
                'label'       => esc_html__('Tablet Menu', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $menu_options,
                'default'     => '',
                'label_block' => true,
                'condition'   => [
                    'use_device_specific_menus' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'menu_id_mobile',
            [
                'label'       => esc_html__('Mobile Menu', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $menu_options,
                'default'     => '',
                'label_block' => true,
                'condition'   => [
                    'use_device_specific_menus' => 'yes',
                ],
            ]
        );

        // Menu Depth
        $this->add_control(
            'menu_depth',
            [
                'label'   => esc_html__('Menu Depth', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::NUMBER,
                'min'     => 1,
                'max'     => 4,
                'step'    => 1,
                'default' => 3,
                'description' => esc_html__('Maximum number of nested levels to display', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        // Start Level
        $this->add_control(
            'start_level',
            [
                'label'   => esc_html__('Start Level', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::NUMBER,
                'min'     => 0,
                'max'     => 3,
                'step'    => 1,
                'default' => 0,
                'description' => esc_html__('Begin displaying from this menu level', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        // Container Tag
        $this->add_control(
            'container_tag',
            [
                'label'   => esc_html__('Container Tag', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'nav' => 'nav',
                    'div' => 'div',
                    'ul'  => 'ul',
                ],
                'default' => 'nav',
                'description' => esc_html__('HTML tag for menu wrapper', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        $this->add_control(
            'submenu_accordion',
            [
                'label'        => esc_html__('Accordion Behavior', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('On', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('Off', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => esc_html__('When enabled, opening one submenu closes sibling submenus at the same level (standard menus, and off-canvas when set to Accordion).', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register responsive layout controls (desktop, tablet, mobile)
     * 
     * @return void
     */
    private function register_responsive_controls() {
        $this->start_controls_section(
            'section_responsive',
            [
                'label' => esc_html__('Responsive Layout', 'lancedesk-responsive-menu-for-elementor'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'responsive_layout_help',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__(
                    'Configure inline menu layout for devices set to Standard Menu in Display Mode. Devices set to Off-Canvas use the Off-Canvas Menu section instead.',
                    'lancedesk-responsive-menu-for-elementor'
                ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        // Desktop Layout
        $this->add_control(
            'heading_desktop_layout',
            [
                'label'     => esc_html__('Desktop Layout (>1024px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_desktop',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'desktop_layout_offcanvas_notice',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('Desktop off-canvas is enabled. Desktop responsive layout controls are disabled here; configure Desktop Off-Canvas settings in the Off-Canvas sections below.', 'lancedesk-responsive-menu-for-elementor'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'conditions'      => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '===',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_desktop',
                            'operator' => '===',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'desktop_layout',
            [
                'label'   => esc_html__('Layout Mode', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'horizontal' => [
                        'title' => esc_html__('Horizontal', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-h-align-stretch',
                    ],
                    'vertical'   => [
                        'title' => esc_html__('Vertical', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-v-align-stretch',
                    ],
                    'grid'       => [
                        'title' => esc_html__('Grid', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-table',
                    ],
                ],
                'default' => 'horizontal',
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_desktop',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'desktop_flex_direction',
            [
                'label'   => esc_html__('Flex Direction', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'row'    => esc_html__('Row (Left to Right)', 'lancedesk-responsive-menu-for-elementor'),
                    'column' => esc_html__('Column (Top to Bottom)', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => 'row',
                'conditions' => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'desktop_layout',
                            'operator' => '!==',
                            'value'    => 'grid',
                        ],
                        [
                            'relation' => 'or',
                            'terms'    => [
                                [
                                    'name'     => 'offcanvas_enable',
                                    'operator' => '!==',
                                    'value'    => 'yes',
                                ],
                                [
                                    'name'     => 'offcanvas_on_desktop',
                                    'operator' => '!==',
                                    'value'    => 'yes',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'desktop_align_items',
            [
                'label'   => esc_html__('Align Items', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'flex-start' => esc_html__('Start', 'lancedesk-responsive-menu-for-elementor'),
                    'center'     => esc_html__('Center', 'lancedesk-responsive-menu-for-elementor'),
                    'flex-end'   => esc_html__('End', 'lancedesk-responsive-menu-for-elementor'),
                    'stretch'    => esc_html__('Stretch', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu' => 'align-items: {{VALUE}};',
                ],
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_desktop',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'desktop_justify_content',
            [
                'label'   => esc_html__('Justify Content', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'flex-start'    => esc_html__('Start', 'lancedesk-responsive-menu-for-elementor'),
                    'center'        => esc_html__('Center', 'lancedesk-responsive-menu-for-elementor'),
                    'flex-end'      => esc_html__('End', 'lancedesk-responsive-menu-for-elementor'),
                    'space-between' => esc_html__('Space Between', 'lancedesk-responsive-menu-for-elementor'),
                    'space-around'  => esc_html__('Space Around', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu' => 'justify-content: {{VALUE}};',
                ],
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_desktop',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        // Tablet Layout
        $this->add_control(
            'heading_tablet_layout',
            [
                'label'     => esc_html__('Tablet Layout (768-1024px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_tablet',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'tablet_layout_offcanvas_notice',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('Tablet off-canvas is enabled. Tablet responsive layout controls are disabled here; configure Tablet Off-Canvas settings in the Off-Canvas sections below.', 'lancedesk-responsive-menu-for-elementor'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'conditions'      => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '===',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_tablet',
                            'operator' => '===',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'tablet_layout',
            [
                'label'   => esc_html__('Layout Mode', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'horizontal' => [
                        'title' => esc_html__('Horizontal', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-h-align-stretch',
                    ],
                    'vertical'   => [
                        'title' => esc_html__('Vertical', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-v-align-stretch',
                    ],
                    'grid'       => [
                        'title' => esc_html__('Grid', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-table',
                    ],
                ],
                'default' => 'horizontal',
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_tablet',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        // Mobile Layout
        $this->add_control(
            'heading_mobile_layout',
            [
                'label'     => esc_html__('Mobile Layout (<768px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_mobile',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'mobile_layout_offcanvas_notice',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('Mobile off-canvas is enabled. Mobile responsive layout controls are disabled here; configure Mobile Off-Canvas settings in the Off-Canvas sections below.', 'lancedesk-responsive-menu-for-elementor'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'conditions'      => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '===',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_mobile',
                            'operator' => '===',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'mobile_layout',
            [
                'label'   => esc_html__('Layout Mode', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'vertical'   => [
                        'title' => esc_html__('Vertical', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-v-align-stretch',
                    ],
                    'horizontal' => [
                        'title' => esc_html__('Horizontal', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-h-align-stretch',
                    ],
                    'grid'       => [
                        'title' => esc_html__('Grid', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-table',
                    ],
                ],
                'default' => 'vertical',
                'description' => esc_html__('Note: Vertical layout is recommended for mobile', 'lancedesk-responsive-menu-for-elementor'),
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'name'     => 'offcanvas_enable',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'offcanvas_on_mobile',
                            'operator' => '!==',
                            'value'    => 'yes',
                        ],
                    ],
                ],
            ]
        );

        // Hamburger Menu Toggle
        $this->add_control(
            'mobile_hamburger_toggle',
            [
                'label'   => esc_html__('Show Hamburger Menu', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SWITCHER,
                'label_on'  => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off' => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'default' => 'yes',
                'conditions' => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'mobile_layout',
                            'operator' => '===',
                            'value'    => 'vertical',
                        ],
                        [
                            'relation' => 'or',
                            'terms'    => [
                                [
                                    'name'     => 'offcanvas_enable',
                                    'operator' => '!==',
                                    'value'    => 'yes',
                                ],
                                [
                                    'name'     => 'offcanvas_on_mobile',
                                    'operator' => '!==',
                                    'value'    => 'yes',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        // Hamburger Position
        $this->add_control(
            'mobile_hamburger_position',
            [
                'label'   => esc_html__('Hamburger Position', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'left'  => [
                        'title' => esc_html__('Left', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-h-align-right',
                    ],
                ],
                'default' => 'left',
                // Make position live in Elementor editor without requiring full re-render.
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-wrapper:not(.ldjem-menu-wrapper-offcanvas) .ldjem-hamburger' => '{{VALUE}};',
                ],
                'selectors_dictionary' => [
                    'left' => 'margin-left: 0; margin-right: auto',
                    'center' => 'margin-left: auto; margin-right: auto',
                    'right' => 'margin-left: auto; margin-right: 0',
                ],
                'condition' => [
                    'mobile_hamburger_toggle' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'heading_vertical_layout_style',
            [
                'label'     => esc_html__('Vertical Layout Styling', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'vertical_item_alignment',
            [
                'label'   => esc_html__('Vertical Item Alignment', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'flex-start' => esc_html__('Left', 'lancedesk-responsive-menu-for-elementor'),
                    'center'     => esc_html__('Center', 'lancedesk-responsive-menu-for-elementor'),
                    'flex-end'   => esc_html__('Right', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-menu > .ldjem-menu-item > a, {{WRAPPER}} .ldjem-menu-wrapper[data-tablet-layout="vertical"] .ldjem-menu > .ldjem-menu-item > a, {{WRAPPER}} .ldjem-menu-wrapper[data-mobile-layout="vertical"] .ldjem-menu > .ldjem-menu-item > a' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'vertical_item_padding',
            [
                'label'      => esc_html__('Vertical Item Padding', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-menu > .ldjem-menu-item > a, {{WRAPPER}} .ldjem-menu-wrapper[data-tablet-layout="vertical"] .ldjem-menu > .ldjem-menu-item > a, {{WRAPPER}} .ldjem-menu-wrapper[data-mobile-layout="vertical"] .ldjem-menu > .ldjem-menu-item > a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'vertical_item_margin',
            [
                'label'      => esc_html__('Vertical Item Margin', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-tablet-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-mobile-layout="vertical"] .ldjem-menu > .ldjem-menu-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'vertical_separator_heading',
            [
                'label'     => esc_html__('Vertical Separators', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'vertical_separator_enabled',
            [
                'label'   => esc_html__('Show Separators', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'yes' => esc_html__('Show', 'lancedesk-responsive-menu-for-elementor'),
                    'no'  => esc_html__('Hide', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => 'yes',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-tablet-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-mobile-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-submenu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-tablet-layout="vertical"] .ldjem-submenu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-mobile-layout="vertical"] .ldjem-submenu > .ldjem-menu-item' => '--ldjem-vertical-item-separator-style: {{VALUE}};',
                ],
                'selectors_dictionary' => [
                    'yes' => 'solid',
                    'no'  => 'none',
                ],
            ]
        );

        $this->add_responsive_control(
            'vertical_separator_width',
            [
                'label'      => esc_html__('Separator Width', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 8,
                    ],
                ],
                'default' => [
                    'size' => 1,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-tablet-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-mobile-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-submenu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-tablet-layout="vertical"] .ldjem-submenu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-mobile-layout="vertical"] .ldjem-submenu > .ldjem-menu-item' => '--ldjem-vertical-item-separator-width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'vertical_separator_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'vertical_separator_color',
            [
                'label'     => esc_html__('Separator Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e0e0e0',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-tablet-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-mobile-layout="vertical"] .ldjem-menu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-submenu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-tablet-layout="vertical"] .ldjem-submenu > .ldjem-menu-item, {{WRAPPER}} .ldjem-menu-wrapper[data-mobile-layout="vertical"] .ldjem-submenu > .ldjem-menu-item' => '--ldjem-vertical-item-separator-color: {{VALUE}};',
                ],
                'condition' => [
                    'vertical_separator_enabled' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register style controls for menu items
     * 
     * @return void
     */
    private function register_style_menu_items() {
        $this->start_controls_section(
            'section_style_menu_items',
            [
                'label' => esc_html__('Menu Items', 'lancedesk-responsive-menu-for-elementor'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'menu_item_typography',
                'label'    => esc_html__('Typography', 'lancedesk-responsive-menu-for-elementor'),
                'selector' => '{{WRAPPER}} .ldjem-menu-item a',
            ]
        );

        // Color States
        $this->add_control(
            'menu_item_color_normal',
            [
                'label'     => esc_html__('Text Color (Normal)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-item a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_color_hover',
            [
                'label'     => esc_html__('Text Color (Hover)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-item a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_color_active',
            [
                'label'     => esc_html__('Text Color (Active)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-item.current-menu-item > a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_underline_heading',
            [
                'label'     => esc_html__('Underline', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'menu_item_underline_hover',
            [
                'label'   => esc_html__('Hover Underline', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'none'      => esc_html__('None', 'lancedesk-responsive-menu-for-elementor'),
                    'underline' => esc_html__('Underline', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => 'none',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-item > a:hover' => 'text-decoration-line: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_underline_active',
            [
                'label'   => esc_html__('Active Underline', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'none'      => esc_html__('None', 'lancedesk-responsive-menu-for-elementor'),
                    'underline' => esc_html__('Underline', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => 'none',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-item.current-menu-item > a, {{WRAPPER}} .ldjem-menu-item.current-menu-ancestor > a' => 'text-decoration-line: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_underline_color',
            [
                'label'     => esc_html__('Underline Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-item > a:hover, {{WRAPPER}} .ldjem-menu-item.current-menu-item > a, {{WRAPPER}} .ldjem-menu-item.current-menu-ancestor > a' => 'text-decoration-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'menu_item_underline_thickness',
            [
                'label'      => esc_html__('Underline Thickness', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 1,
                        'max' => 10,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-menu-item > a:hover, {{WRAPPER}} .ldjem-menu-item.current-menu-item > a, {{WRAPPER}} .ldjem-menu-item.current-menu-ancestor > a' => 'text-decoration-thickness: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'menu_item_underline_offset',
            [
                'label'      => esc_html__('Underline Offset', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 24,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-menu-item > a:hover, {{WRAPPER}} .ldjem-menu-item.current-menu-item > a, {{WRAPPER}} .ldjem-menu-item.current-menu-ancestor > a' => 'text-underline-offset: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Spacing
        $this->add_control(
            'menu_item_padding',
            [
                'label'      => esc_html__('Padding', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default'    => [
                    'top'    => '12',
                    'right'  => '16',
                    'bottom' => '12',
                    'left'   => '16',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-item a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_margin',
            [
                'label'      => esc_html__('Margin', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Border
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'menu_item_border',
                'label'    => esc_html__('Border', 'lancedesk-responsive-menu-for-elementor'),
                'selector' => '{{WRAPPER}} .ldjem-menu-item a',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register style controls for submenus
     * 
     * @return void
     */
    private function register_style_submenus() {
        $this->start_controls_section(
            'section_style_submenus',
            [
                'label'       => esc_html__('Submenus', 'lancedesk-responsive-menu-for-elementor'),
                'tab'         => Controls_Manager::TAB_STYLE,
                'description' => esc_html__('Applies to standard dropdown submenus and off-canvas nested menus.', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        // Submenu Animation
        $this->add_control(
            'submenu_animation',
            [
                'label'   => esc_html__('Animation', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => LDJEM_Helpers::get_animation_options(),
                'default' => 'fade',
            ]
        );

        $this->add_control(
            'submenu_animation_duration',
            [
                'label'      => esc_html__('Animation Duration (ms)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::NUMBER,
                'min'        => 100,
                'max'        => 1000,
                'step'       => 100,
                'default'    => 300,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-submenu, {{WRAPPER}} .ldjem-offcanvas-submenu' => 'transition-duration: {{VALUE}}ms;',
                ],
            ]
        );

        // Submenu Trigger
        $this->add_control(
            'submenu_trigger',
            [
                'label'   => esc_html__('Trigger on Desktop', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => LDJEM_Helpers::get_submenu_trigger_options(),
                'default' => 'hover',
            ]
        );

        $this->add_control(
            'submenu_indicator_icon',
            [
                'label'   => esc_html__('Submenu Toggle Icon', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::ICONS,
                'default' => [
                    'value'   => 'fas fa-chevron-down',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_responsive_control(
            'submenu_indicator_spacing',
            [
                'label'      => esc_html__('Toggle Icon Spacing', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'default' => [
                    'size' => 4,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ldjem-submenu-toggle' => '--ldjem-submenu-toggle-spacing: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'submenu_indicator_size',
            [
                'label'      => esc_html__('Toggle Icon Size', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range'      => [
                    'px' => [
                        'min' => 8,
                        'max' => 40,
                    ],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ldjem-submenu-toggle' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ldjem-submenu-toggle i, {{WRAPPER}} .ldjem-submenu-toggle svg' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'submenu_indicator_color',
            [
                'label'     => esc_html__('Toggle Icon Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-submenu-toggle' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-submenu-toggle svg, {{WRAPPER}} .ldjem-submenu-toggle svg *' => 'fill: {{VALUE}}; stroke: {{VALUE}};',
                ],
            ]
        );

        // Submenu Colors
        $this->add_control(
            'submenu_background',
            [
                'label'     => esc_html__('Background Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-submenu-bg: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-submenu, {{WRAPPER}} .ldjem-offcanvas-submenu' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'submenu_text_color',
            [
                'label'     => esc_html__('Text Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-submenu-link-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-submenu a, {{WRAPPER}} .ldjem-offcanvas-submenu a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'submenu_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'size' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ldjem-submenu, {{WRAPPER}} .ldjem-offcanvas-submenu' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'submenu_panel_padding',
            [
                'label'      => esc_html__('Submenu Panel Padding', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-submenu, {{WRAPPER}} .ldjem-offcanvas-submenu' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'submenu_item_padding',
            [
                'label'      => esc_html__('Submenu Item Padding', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-submenu .ldjem-menu-item > a, {{WRAPPER}} .ldjem-offcanvas-submenu .ldjem-offcanvas-submenu-item > a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'submenu_border',
                'label'    => esc_html__('Submenu Border', 'lancedesk-responsive-menu-for-elementor'),
                'selector' => '{{WRAPPER}} .ldjem-submenu, {{WRAPPER}} .ldjem-offcanvas-submenu',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'submenu_box_shadow',
                'label'    => esc_html__('Dropdown Box Shadow', 'lancedesk-responsive-menu-for-elementor'),
                'selector' => '{{WRAPPER}} .ldjem-submenu, {{WRAPPER}} .ldjem-offcanvas-submenu',
            ]
        );

        $this->add_control(
            'submenu_vertical_border_heading',
            [
                'label'     => esc_html__('Vertical Layout Nested Border', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'submenu_vertical_left_border_enabled',
            [
                'label'        => esc_html__('Show Left Border', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SELECT,
                'options'      => [
                    'yes' => esc_html__('Show', 'lancedesk-responsive-menu-for-elementor'),
                    'no'  => esc_html__('Hide', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default'      => 'yes',
                'selectors'    => [
                    '{{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-menu-item-parent > .ldjem-submenu, {{WRAPPER}} .ldjem-offcanvas-menu-item.has-children > .ldjem-offcanvas-submenu, {{WRAPPER}} .ldjem-offcanvas-submenu-item.has-children > .ldjem-offcanvas-submenu' => '--ldjem-vertical-submenu-border-style: {{VALUE}};',
                ],
                'selectors_dictionary' => [
                    'yes' => 'solid',
                    'no'  => 'none',
                ],
            ]
        );

        $this->add_responsive_control(
            'submenu_vertical_left_border_width',
            [
                'label'      => esc_html__('Left Border Width', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'default'    => [
                    'size' => 1,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-menu-item-parent > .ldjem-submenu, {{WRAPPER}} .ldjem-offcanvas-menu-item.has-children > .ldjem-offcanvas-submenu, {{WRAPPER}} .ldjem-offcanvas-submenu-item.has-children > .ldjem-offcanvas-submenu' => '--ldjem-vertical-submenu-border-width: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'submenu_vertical_left_border_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'submenu_vertical_left_border_color',
            [
                'label'     => esc_html__('Left Border Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e0e0e0',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-wrapper[data-desktop-layout="vertical"] .ldjem-menu-item-parent > .ldjem-submenu, {{WRAPPER}} .ldjem-offcanvas-menu-item.has-children > .ldjem-offcanvas-submenu, {{WRAPPER}} .ldjem-offcanvas-submenu-item.has-children > .ldjem-offcanvas-submenu' => '--ldjem-vertical-submenu-border-color: {{VALUE}};',
                ],
                'condition' => [
                    'submenu_vertical_left_border_enabled' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register style controls for off-canvas menu (panel, toggle, header, links, footer).
     *
     * @return void
     */
    private function register_style_offcanvas() {
        $this->start_controls_section(
            'section_style_offcanvas',
            [
                'label'      => esc_html__('Off Canvas', 'lancedesk-responsive-menu-for-elementor'),
                'tab'        => Controls_Manager::TAB_STYLE,
                'conditions' => $this->get_offcanvas_active_conditions(),
            ]
        );

        $this->add_control(
            'offcanvas_style_panel_heading',
            [
                'label' => esc_html__('Panel', 'lancedesk-responsive-menu-for-elementor'),
                'type'  => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'offcanvas_bg_color',
            [
                'label'     => esc_html__('Panel Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-wrapper-offcanvas' => '--ldjem-offcanvas-bg: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-bg: {{VALUE}}; background-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-header' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-menu-container' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-menu' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-footer' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close-standalone-wrap' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_style_toggle_heading',
            [
                'label'     => esc_html__('Toggle (Hamburger)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // Hamburger Icon styling (icon picker lives in Content > Off-Canvas Menu)
        $this->add_control(
            'hamburger_icon_size',
            [
                'label'      => esc_html__('Hamburger Icon Size', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 16,
                        'max' => 64,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 24,
                ],
                'selectors' => [
                    '{{WRAPPER}} .ldjem-hamburger' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'hamburger_icon_color',
            [
                'label'     => esc_html__('Hamburger Icon Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-hamburger, {{WRAPPER}} .ldjem-hamburger i, {{WRAPPER}} .ldjem-hamburger svg' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-hamburger svg, {{WRAPPER}} .ldjem-hamburger svg *' => 'fill: {{VALUE}}; stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'hamburger_icon_hover_color',
            [
                'label'     => esc_html__('Hamburger Icon Hover Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-hamburger:hover, {{WRAPPER}} .ldjem-hamburger:hover i, {{WRAPPER}} .ldjem-hamburger:hover svg' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-hamburger:hover svg, {{WRAPPER}} .ldjem-hamburger:hover svg *' => 'fill: {{VALUE}}; stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'hamburger_icon_active_color',
            [
                'label'     => esc_html__('Hamburger Icon Active Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-hamburger.is-open, {{WRAPPER}} .ldjem-hamburger.is-open i, {{WRAPPER}} .ldjem-hamburger.is-open svg' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-hamburger.is-open svg, {{WRAPPER}} .ldjem-hamburger.is-open svg *' => 'fill: {{VALUE}}; stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'hamburger_bg_color',
            [
                'label'     => esc_html__('Hamburger Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-hamburger' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'hamburger_bg_hover_color',
            [
                'label'     => esc_html__('Hamburger Hover Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-hamburger:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'hamburger_bg_active_color',
            [
                'label'     => esc_html__('Hamburger Active Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-hamburger.is-open' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'hamburger_padding',
            [
                'label'      => esc_html__('Hamburger Padding', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-hamburger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'hamburger_margin',
            [
                'label'      => esc_html__('Hamburger Margin', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-hamburger' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'hamburger_border_radius',
            [
                'label'      => esc_html__('Hamburger Border Radius', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-hamburger' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'hamburger_border',
                'label'    => esc_html__('Hamburger Border', 'lancedesk-responsive-menu-for-elementor'),
                'selector' => '{{WRAPPER}} .ldjem-hamburger',
            ]
        );

        $this->add_control(
            'hamburger_focus_ring',
            [
                'label'        => esc_html__('Toggle focus / tap ring', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SELECT,
                'default'      => 'hide_touch',
                'options'      => [
                    'hide_touch' => esc_html__('Hide on click/tap (keep keyboard)', 'lancedesk-responsive-menu-for-elementor'),
                    'hide_all'   => esc_html__('Hide always', 'lancedesk-responsive-menu-for-elementor'),
                    'show'       => esc_html__('Show browser default', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'description'  => esc_html__('Stops the Chrome/iOS outline that appears after tapping the hamburger. Keyboard focus can still show when set to keep keyboard.', 'lancedesk-responsive-menu-for-elementor'),
                'prefix_class' => 'ldjem-toggle-focus-',
            ]
        );

        $this->add_control(
            'offcanvas_style_header_heading',
            [
                'label'     => esc_html__('Header', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_header_bg_color',
            [
                'label'     => esc_html__('Header Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-header-bg: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-header' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_header_text_color',
            [
                'label'     => esc_html__('Header Text Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-logo-text' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_header_border_color',
            [
                'label'     => esc_html__('Header Border Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-header-border-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-header' => 'border-bottom-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_header_separator_enabled',
            [
                'label'   => esc_html__('Show Header Separator', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'yes' => esc_html__('Show', 'lancedesk-responsive-menu-for-elementor'),
                    'no'  => esc_html__('Hide', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => 'yes',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-header-border-style: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-header' => 'border-bottom-style: {{VALUE}};',
                ],
                'selectors_dictionary' => [
                    'yes' => 'solid',
                    'no'  => 'none',
                ],
                'condition' => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_header_separator_width',
            [
                'label'      => esc_html__('Header Separator Width', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 8,
                    ],
                ],
                'default'    => [
                    'size' => 1,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-header-border-width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ldjem-offcanvas-header' => 'border-bottom-width: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_header' => 'yes',
                    'offcanvas_header_separator_enabled' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'offcanvas_logo_width',
            [
                'label'      => esc_html__('Logo Width', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range'      => [
                    'px' => [
                        'min' => 16,
                        'max' => 640,
                    ],
                    '%' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                    'vw' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-logo img' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'offcanvas_logo_max_width',
            [
                'label'      => esc_html__('Logo Max Width', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range'      => [
                    'px' => [
                        'min' => 24,
                        'max' => 960,
                    ],
                    '%' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                    'vw' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-logo-max-width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ldjem-offcanvas-logo img' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'offcanvas_logo_height',
            [
                'label'      => esc_html__('Logo Height', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range'      => [
                    'px' => [
                        'min' => 12,
                        'max' => 320,
                    ],
                    'vh' => [
                        'min' => 2,
                        'max' => 60,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-logo img' => 'height: {{SIZE}}{{UNIT}}; width: auto; object-fit: contain;',
                ],
                'condition'  => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'offcanvas_logo_padding',
            [
                'label'      => esc_html__('Logo Padding', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-logo' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'offcanvas_logo_margin',
            [
                'label'      => esc_html__('Logo Margin', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-logo' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_mobile_header_style_heading',
            [
                'label'     => esc_html__('Mobile Header Overrides', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'offcanvas_mobile_header_padding',
            [
                'label'      => esc_html__('Header Padding (Mobile <=576px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-header-padding-mobile: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_mobile_header_min_height',
            [
                'label'      => esc_html__('Header Min Height (Mobile <=576px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 40,
                        'max' => 140,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-header-min-height-mobile: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_style_close_heading',
            [
                'label'     => esc_html__('Close Button', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_btn_color',
            [
                'label'     => esc_html__('Close Button Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-close-btn-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close i, {{WRAPPER}} .ldjem-offcanvas-close svg' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close svg, {{WRAPPER}} .ldjem-offcanvas-close svg *' => 'fill: {{VALUE}}; stroke: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_btn_bg',
            [
                'label'     => esc_html__('Close Button Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'transparent',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-close-btn-bg: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_btn_size',
            [
                'label'      => esc_html__('Close Button Size', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 24,
                        'max' => 72,
                    ],
                ],
                'default'    => [
                    'size' => 40,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-close-btn-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_icon_size',
            [
                'label'      => esc_html__('Close Icon Size', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 12,
                        'max' => 48,
                    ],
                ],
                'default'    => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-close-icon-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_icon_weight',
            [
                'label'      => esc_html__('Close Icon Weight', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SELECT,
                'options'    => [
                    '300' => esc_html__('Light (300)', 'lancedesk-responsive-menu-for-elementor'),
                    '400' => esc_html__('Regular (400)', 'lancedesk-responsive-menu-for-elementor'),
                    '500' => esc_html__('Medium (500)', 'lancedesk-responsive-menu-for-elementor'),
                    '600' => esc_html__('Semi Bold (600)', 'lancedesk-responsive-menu-for-elementor'),
                    '700' => esc_html__('Bold (700)', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default'    => '400',
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-close.ldjem-close-type-letter .ldjem-close-letter' => 'font-weight: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close.ldjem-close-type-icon.has-custom-icon i' => 'font-weight: {{VALUE}};',
                ],
                'condition'  => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_icon_stroke_width',
            [
                'label'      => esc_html__('Close Icon Stroke Width', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0.5,
                        'max' => 4,
                        'step' => 0.1,
                    ],
                ],
                'default'    => [
                    'size' => 1.5,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-close.ldjem-close-type-icon.has-custom-icon svg, {{WRAPPER}} .ldjem-offcanvas-close.ldjem-close-type-icon.has-custom-icon svg *' => 'stroke-width: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_btn_radius',
            [
                'label'      => esc_html__('Close Button Border Radius', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'default'    => [
                    'size' => 4,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-close-btn-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_btn_border_width',
            [
                'label'      => esc_html__('Close Button Border Width', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'default'    => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-close-btn-border-width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
                ],
                'condition'  => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_btn_border_color',
            [
                'label'     => esc_html__('Close Button Border Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'transparent',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-close-btn-border-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-close' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_btn_offset_top',
            [
                'label'     => esc_html__('Close Button Top Offset (px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 0,
                'max'       => 40,
                'step'      => 1,
                'default'   => 0,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-close-btn-offset-top: {{VALUE}}px;',
                    '{{WRAPPER}} .ldjem-offcanvas-close' => 'top: {{VALUE}}px;',
                ],
                'condition' => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_btn_offset_right',
            [
                'label'     => esc_html__('Close Button Right Offset (px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 0,
                'max'       => 40,
                'step'      => 1,
                'default'   => 0,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-close-btn-offset-right: {{VALUE}}px;',
                    '{{WRAPPER}} .ldjem-offcanvas-close' => 'right: {{VALUE}}px;',
                ],
                'condition' => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_mobile_close_btn_size',
            [
                'label'      => esc_html__('Close Button Size (Mobile <=576px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 24,
                        'max' => 72,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-close-btn-size-mobile: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_mobile_close_icon_size',
            [
                'label'      => esc_html__('Close Icon Size (Mobile <=576px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 12,
                        'max' => 48,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-close-icon-size-mobile: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_links_heading',
            [
                'label'     => esc_html__('Menu Links', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'offcanvas_link_typography',
                'selector' => '{{WRAPPER}} .ldjem-offcanvas-menu-item > a, {{WRAPPER}} .ldjem-offcanvas-submenu-item > a',
            ]
        );

        $this->add_control(
            'offcanvas_link_color',
            [
                'label'     => esc_html__('Link Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-link-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-menu-item > a, {{WRAPPER}} .ldjem-offcanvas-submenu-item > a, {{WRAPPER}} .ldjem-offcanvas-menu-item.has-children > .ldjem-submenu-toggle, {{WRAPPER}} .ldjem-offcanvas-submenu-item.has-children > .ldjem-submenu-toggle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_link_hover_color',
            [
                'label'     => esc_html__('Link Hover Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-link-hover-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-menu-item > a:hover, {{WRAPPER}} .ldjem-offcanvas-submenu-item > a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_link_active_color',
            [
                'label'     => esc_html__('Link Active Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-link-active-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-menu-item.is-active > a, {{WRAPPER}} .ldjem-offcanvas-submenu-item.is-active > a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_link_hover_bg',
            [
                'label'     => esc_html__('Link Hover Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-link-hover-bg: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-menu-item > a:hover, {{WRAPPER}} .ldjem-offcanvas-submenu-item > a:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_link_active_bg',
            [
                'label'     => esc_html__('Link Active Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-link-active-bg: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-menu-item.is-active > a, {{WRAPPER}} .ldjem-offcanvas-submenu-item.is-active > a' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_nested_submenu_bg',
            [
                'label'     => esc_html__('Nested Submenu Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-nested-submenu-bg: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-submenu-item.has-children > .ldjem-offcanvas-submenu' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'offcanvas_link_padding',
            [
                'label'      => esc_html__('Link Padding', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-menu-item > a, {{WRAPPER}} .ldjem-offcanvas-submenu-item > a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'offcanvas_item_margin',
            [
                'label'      => esc_html__('Item Margin', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-menu-item, {{WRAPPER}} .ldjem-offcanvas-submenu-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'offcanvas_nav_top_spacing',
            [
                'label'       => esc_html__('Header To Nav Spacing', 'lancedesk-responsive-menu-for-elementor'),
                'description' => esc_html__('Space between the off-canvas header (logo section) and the navigation list.', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => ['px', 'em', 'rem'],
                'range'       => [
                    'px'  => ['min' => 0, 'max' => 120],
                    'em'  => ['min' => 0, 'max' => 8],
                    'rem' => ['min' => 0, 'max' => 8],
                ],
                'selectors'   => [
                    '{{WRAPPER}} .ldjem-offcanvas-menu-container' => 'padding-top: {{SIZE}}{{UNIT}};',
                ],
                'condition'   => [
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_separator_heading',
            [
                'label'     => esc_html__('Item Separators', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'offcanvas_separator_enabled',
            [
                'label'   => esc_html__('Show Separators', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'yes' => esc_html__('Show', 'lancedesk-responsive-menu-for-elementor'),
                    'no'  => esc_html__('Hide', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => 'yes',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-separator-style: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-menu-item, {{WRAPPER}} .ldjem-offcanvas-submenu-item' => 'border-bottom-style: {{VALUE}};',
                ],
                'selectors_dictionary' => [
                    'yes' => 'solid',
                    'no'  => 'none',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_separator_width',
            [
                'label'      => esc_html__('Separator Width', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 8,
                    ],
                ],
                'default' => [
                    'size' => 1,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-separator-width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ldjem-offcanvas-menu-item, {{WRAPPER}} .ldjem-offcanvas-submenu-item' => 'border-bottom-width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'offcanvas_separator_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_separator_color',
            [
                'label'     => esc_html__('Separator Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f0f0f0',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-separator-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-menu-item, {{WRAPPER}} .ldjem-offcanvas-submenu-item' => 'border-bottom-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_separator_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_style_footer_heading',
            [
                'label'     => esc_html__('Footer & Social', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_footer_bg_color',
            [
                'label'     => esc_html__('Footer Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-footer' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_footer_border_color',
            [
                'label'     => esc_html__('Footer Border Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-footer-border-color: {{VALUE}};',
                    '{{WRAPPER}} .ldjem-offcanvas-footer' => 'border-top-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_footer_title_color',
            [
                'label'     => esc_html__('Footer Title Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-footer-title' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_social_icon_size',
            [
                'label'      => esc_html__('Social Icon Size', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 16,
                        'max' => 64,
                    ],
                ],
                'default'    => [
                    'size' => 36,
                    'unit' => 'px',
                ],
                'condition' => [
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_social_icon_bg_color',
            [
                'label'     => esc_html__('Social Icon Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-social-link' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_social_icon_color',
            [
                'label'     => esc_html__('Social Icon Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-social-link' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_social_icon_hover_bg_color',
            [
                'label'     => esc_html__('Social Icon Hover Background', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-social-link:hover' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_social_icon_hover_color',
            [
                'label'     => esc_html__('Social Icon Hover Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-social-link:hover' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_style_a11y_heading',
            [
                'label'     => esc_html__('Accessibility', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'offcanvas_focus_outline_color',
            [
                'label'     => esc_html__('Focus Outline Color', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#4A90E2',
                'selectors' => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper' => '--ldjem-offcanvas-focus-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register off-canvas menu content controls.
     *
     * @return void
     */
    private function register_offcanvas_controls() {
        $this->start_controls_section(
            'section_offcanvas_menu',
            [
                'label'      => esc_html__('Off-Canvas Menu', 'lancedesk-responsive-menu-for-elementor'),
                'tab'        => Controls_Manager::TAB_CONTENT,
                'conditions' => $this->get_offcanvas_active_conditions(),
            ]
        );

        $this->add_control(
            'offcanvas_direction',
            [
                'label'     => esc_html__('Slide Direction', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::SELECT,
                'options'   => [
                    'left'   => esc_html__('Left', 'lancedesk-responsive-menu-for-elementor'),
                    'right'  => esc_html__('Right', 'lancedesk-responsive-menu-for-elementor'),
                    'top'    => esc_html__('Top', 'lancedesk-responsive-menu-for-elementor'),
                    'bottom' => esc_html__('Bottom', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default'   => 'left',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_submenu_mode',
            [
                'label'       => esc_html__('Submenu Style', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::SELECT,
                'options'     => [
                    'drilldown' => esc_html__('Drill-down (push panels)', 'lancedesk-responsive-menu-for-elementor'),
                    'accordion' => esc_html__('Accordion (expand in place)', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default'     => 'drilldown',
                'description' => esc_html__('Drill-down opens each submenu as a full panel with a Back control—best for deep menus. Accordion expands items in place.', 'lancedesk-responsive-menu-for-elementor'),
                'condition'   => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_animation_duration',
            [
                'label'     => esc_html__('Animation Duration (ms)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 200,
                'max'       => 1000,
                'step'      => 50,
                'default'   => 300,
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_animation_easing',
            [
                'label'     => esc_html__('Animation Easing', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::SELECT,
                'options'   => [
                    'ease-in-out' => esc_html__('Ease In Out', 'lancedesk-responsive-menu-for-elementor'),
                    'ease-in'     => esc_html__('Ease In', 'lancedesk-responsive-menu-for-elementor'),
                    'ease-out'    => esc_html__('Ease Out', 'lancedesk-responsive-menu-for-elementor'),
                    'linear'      => esc_html__('Linear', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default'   => 'ease-in-out',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_panel_size',
            [
                'label'      => esc_html__('Panel Width (Left/Right, px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 220,
                        'max' => 640,
                    ],
                ],
                'default'    => [
                    'size' => 300,
                    'unit' => 'px',
                ],
                'condition'  => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_panel_height',
            [
                'label'      => esc_html__('Panel Height (Top/Bottom, px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 220,
                        'max' => 900,
                    ],
                ],
                'default'    => [
                    'size' => 400,
                    'unit' => 'px',
                ],
                'condition'  => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'offcanvas_panel_offset_top',
            [
                'label'      => esc_html__('Panel Top Offset', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 300,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ldjem-offcanvas-wrapper.direction-left, {{WRAPPER}} .ldjem-offcanvas-wrapper.direction-right' => 'top: {{SIZE}}{{UNIT}}; --ldjem-offcanvas-offset-top: {{SIZE}}{{UNIT}}; bottom: 0; height: auto; min-height: calc(100vh - {{SIZE}}{{UNIT}}); min-height: calc(100dvh - {{SIZE}}{{UNIT}}); min-height: calc(var(--ldjem-vvh, 100dvh) - {{SIZE}}{{UNIT}});',
                ],
                'condition'  => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_z_index',
            [
                'label'     => esc_html__('Panel Z-Index', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 100,
                'max'       => 99999,
                'step'      => 1,
                'default'   => 999,
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_content_toggle_heading',
            [
                'label'     => esc_html__('Toggle', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'hamburger_icon',
            [
                'label'       => esc_html__('Toggle Icon', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-bars',
                    'library' => 'fa-solid',
                ],
                'render_type' => 'none',
                'frontend_available' => true,
                'condition'   => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_toggle_alignment',
            [
                'label'   => esc_html__('Toggle Alignment', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'lancedesk-responsive-menu-for-elementor'),
                        'icon'  => 'eicon-h-align-right',
                    ],
                ],
                'default' => 'left',
                'toggle'  => false,
                'selectors' => [
                    '{{WRAPPER}} .ldjem-menu-wrapper-offcanvas .ldjem-hamburger' => '{{VALUE}};',
                ],
                'selectors_dictionary' => [
                    'left'   => 'margin-left: 0; margin-right: auto',
                    'center' => 'margin-left: auto; margin-right: auto',
                    'right'  => 'margin-left: auto; margin-right: 0',
                ],
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_content_header_heading',
            [
                'label'     => esc_html__('Header', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_show_header',
            [
                'label'        => esc_html__('Show Header', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_logo',
            [
                'label'     => esc_html__('Logo Image', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::MEDIA,
                'condition' => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_logo_alt',
            [
                'label'     => esc_html__('Logo Alt Text', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__('Logo', 'lancedesk-responsive-menu-for-elementor'),
                'condition' => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_dark_logo_enabled',
            [
                'label'        => esc_html__('Enable Dark Mode Logo', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_logo_dark',
            [
                'label'     => esc_html__('Dark Mode Logo Image', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::MEDIA,
                'condition' => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_header' => 'yes',
                    'offcanvas_dark_logo_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_logo_dark_mode_source',
            [
                'label'       => esc_html__('Dark Mode Source', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::SELECT,
                'options'     => [
                    'auto'          => esc_html__('Auto (Accessibility class or System preference)', 'lancedesk-responsive-menu-for-elementor'),
                    'accessibility' => esc_html__('Accessibility Class Only (hb-a11y-dark)', 'lancedesk-responsive-menu-for-elementor'),
                    'system'        => esc_html__('System Preference Only (prefers-color-scheme)', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default'     => 'auto',
                'description' => esc_html__('Choose what triggers the dark logo. Auto works with the accessibility plugin and also falls back to device dark preference.', 'lancedesk-responsive-menu-for-elementor'),
                'condition'   => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_header' => 'yes',
                    'offcanvas_dark_logo_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_logo_link',
            [
                'label'     => esc_html__('Logo Link', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::URL,
                'condition' => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_header_text',
            [
                'label'     => esc_html__('Header Text', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::TEXT,
                'default'   => '',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_show_header_text',
            [
                'label'        => esc_html__('Show Header Text', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_show_close_btn',
            [
                'label'        => esc_html__('Show Close Button', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => esc_html__(
                    'Works with or without the header. Use a contrasting close color if the header background is similar.',
                    'lancedesk-responsive-menu-for-elementor'
                ),
                'conditions'   => $this->get_offcanvas_active_conditions(),
            ]
        );

        $this->add_control(
            'offcanvas_close_icon_type',
            [
                'label'   => esc_html__('Close Button Type', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'icon'   => esc_html__('Icon', 'lancedesk-responsive-menu-for-elementor'),
                    'letter' => esc_html__('Letter / Character', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default'     => 'icon',
                'render_type' => 'none',
                'frontend_available' => true,
                'condition'   => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_close_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_letter',
            [
                'label'       => esc_html__('Close Letter', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::TEXT,
                'default'     => '×',
                'placeholder' => '×',
                'render_type' => 'none',
                'frontend_available' => true,
                'condition'   => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_close_btn' => 'yes',
                    'offcanvas_close_icon_type' => 'letter',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_close_icon',
            [
                'label'       => esc_html__('Close Icon', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-times',
                    'library' => 'fa-solid',
                ],
                'render_type' => 'none',
                'frontend_available' => true,
                'condition'   => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_close_btn' => 'yes',
                    'offcanvas_close_icon_type' => 'icon',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_content_footer_heading',
            [
                'label'     => esc_html__('Footer', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_show_footer',
            [
                'label'        => esc_html__('Show Social Footer', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_show_footer_title',
            [
                'label'        => esc_html__('Show Footer Title', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_footer_title',
            [
                'label'     => esc_html__('Footer Title', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__('Follow Us', 'lancedesk-responsive-menu-for-elementor'),
                'condition' => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_footer' => 'yes',
                    'offcanvas_show_footer_title' => 'yes',
                ],
            ]
        );

        $social_repeater = new Repeater();

        $social_repeater->add_control(
            'social_platform',
            [
                'label'   => esc_html__('Platform', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'facebook'  => esc_html__('Facebook', 'lancedesk-responsive-menu-for-elementor'),
                    'twitter'   => esc_html__('Twitter', 'lancedesk-responsive-menu-for-elementor'),
                    'linkedin'  => esc_html__('LinkedIn', 'lancedesk-responsive-menu-for-elementor'),
                    'instagram' => esc_html__('Instagram', 'lancedesk-responsive-menu-for-elementor'),
                    'youtube'   => esc_html__('YouTube', 'lancedesk-responsive-menu-for-elementor'),
                    'tiktok'    => esc_html__('TikTok', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => 'facebook',
            ]
        );

        $social_repeater->add_control(
            'social_url',
            [
                'label'       => esc_html__('URL', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::URL,
                'placeholder' => 'https://example.com',
            ]
        );

        $this->add_control(
            'offcanvas_social_icons',
            [
                'label'     => esc_html__('Social Media Links', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::REPEATER,
                'fields'    => $social_repeater->get_controls(),
                'default'   => [
                    [
                        'social_platform' => 'facebook',
                        'social_url'      => ['url' => 'https://facebook.com'],
                    ],
                    [
                        'social_platform' => 'instagram',
                        'social_url'      => ['url' => 'https://instagram.com'],
                    ],
                ],
                'condition' => [
                    'offcanvas_enable' => 'yes',
                    'offcanvas_show_footer' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register breakpoint behavior controls.
     *
     * @return void
     */
    private function register_breakpoint_controls() {
        $this->start_controls_section(
            'section_breakpoint_behavior',
            [
                'label'      => esc_html__('Off-Canvas Device Overrides', 'lancedesk-responsive-menu-for-elementor'),
                'tab'        => Controls_Manager::TAB_CONTENT,
                'conditions' => $this->get_offcanvas_active_conditions(),
            ]
        );

        $this->add_control(
            'offcanvas_overrides_help',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__(
                    'Optional per-device overrides for slide direction, animation, and panel size. Leave inherit/default to use the global Off-Canvas Menu settings.',
                    'lancedesk-responsive-menu-for-elementor'
                ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $direction_options = [
            'inherit' => esc_html__('Inherit Global Direction', 'lancedesk-responsive-menu-for-elementor'),
            'left'    => esc_html__('Left', 'lancedesk-responsive-menu-for-elementor'),
            'right'   => esc_html__('Right', 'lancedesk-responsive-menu-for-elementor'),
            'top'     => esc_html__('Top', 'lancedesk-responsive-menu-for-elementor'),
            'bottom'  => esc_html__('Bottom', 'lancedesk-responsive-menu-for-elementor'),
        ];

        $this->add_control(
            'offcanvas_device_heading_desktop',
            [
                'label'     => esc_html__('Desktop Overrides (>1024px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_direction_desktop',
            [
                'label'     => esc_html__('Desktop Direction', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::SELECT,
                'options'   => $direction_options,
                'default'   => 'inherit',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_animation_duration_desktop',
            [
                'label'     => esc_html__('Desktop Animation Duration (ms)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 200,
                'max'       => 1000,
                'step'      => 50,
                'default'   => 0,
                'description' => esc_html__('Set 0 to inherit global animation duration.', 'lancedesk-responsive-menu-for-elementor'),
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_panel_size_desktop',
            [
                'label'      => esc_html__('Desktop Panel Width (px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 220,
                        'max' => 900,
                    ],
                ],
                'condition'  => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_panel_height_desktop',
            [
                'label'      => esc_html__('Desktop Panel Height (px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 220,
                        'max' => 1000,
                    ],
                ],
                'condition'  => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_device_heading_tablet',
            [
                'label'     => esc_html__('Tablet Overrides (768-1024px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_direction_tablet',
            [
                'label'     => esc_html__('Tablet Direction', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::SELECT,
                'options'   => $direction_options,
                'default'   => 'inherit',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_animation_duration_tablet',
            [
                'label'     => esc_html__('Tablet Animation Duration (ms)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 200,
                'max'       => 1000,
                'step'      => 50,
                'default'   => 0,
                'description' => esc_html__('Set 0 to inherit global animation duration.', 'lancedesk-responsive-menu-for-elementor'),
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_panel_size_tablet',
            [
                'label'      => esc_html__('Tablet Panel Width (px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 220,
                        'max' => 900,
                    ],
                ],
                'condition'  => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_panel_height_tablet',
            [
                'label'      => esc_html__('Tablet Panel Height (px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 220,
                        'max' => 1000,
                    ],
                ],
                'condition'  => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_device_heading_mobile',
            [
                'label'     => esc_html__('Mobile Overrides (<768px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_direction_mobile',
            [
                'label'     => esc_html__('Mobile Direction', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::SELECT,
                'options'   => $direction_options,
                'default'   => 'inherit',
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_animation_duration_mobile',
            [
                'label'     => esc_html__('Mobile Animation Duration (ms)', 'lancedesk-responsive-menu-for-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 200,
                'max'       => 1000,
                'step'      => 50,
                'default'   => 0,
                'description' => esc_html__('Set 0 to inherit global animation duration.', 'lancedesk-responsive-menu-for-elementor'),
                'condition' => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_panel_size_mobile',
            [
                'label'      => esc_html__('Mobile Panel Width (px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 220,
                        'max' => 900,
                    ],
                ],
                'condition'  => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offcanvas_panel_height_mobile',
            [
                'label'      => esc_html__('Mobile Panel Height (px)', 'lancedesk-responsive-menu-for-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 220,
                        'max' => 1000,
                    ],
                ],
                'condition'  => [
                    'offcanvas_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register advanced controls
     * 
     * @return void
     */
    private function register_advanced_controls() {
        $this->start_controls_section(
            'section_advanced',
            [
                'label' => esc_html__('Advanced', 'lancedesk-responsive-menu-for-elementor'),
                'tab'   => Controls_Manager::TAB_ADVANCED,
            ]
        );

        // Custom CSS Class
        $this->add_control(
            'custom_css_class',
            [
                'label'       => esc_html__('Custom CSS Class', 'lancedesk-responsive-menu-for-elementor'),
                'type'        => Controls_Manager::TEXT,
                'description' => esc_html__('Add custom CSS classes (space-separated)', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        // Link Target
        $this->add_control(
            'link_target',
            [
                'label'   => esc_html__('Link Target', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    ''       => esc_html__('Default', 'lancedesk-responsive-menu-for-elementor'),
                    '_blank' => esc_html__('New Window/Tab', 'lancedesk-responsive-menu-for-elementor'),
                    '_self'  => esc_html__('Same Window', 'lancedesk-responsive-menu-for-elementor'),
                ],
                'default' => '',
            ]
        );

        // Active Menu Item Marking
        $this->add_control(
            'mark_active_item',
            [
                'label'   => esc_html__('Mark Active Menu Item', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SWITCHER,
                'label_on'  => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off' => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'default' => 'yes',
                'description' => esc_html__('Highlight current page menu item', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        // Accessibility
        $this->add_control(
            'enable_accessibility',
            [
                'label'   => esc_html__('Enable Accessibility Features', 'lancedesk-responsive-menu-for-elementor'),
                'type'    => Controls_Manager::SWITCHER,
                'label_on'  => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off' => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'default' => 'yes',
                'description' => esc_html__('Include ARIA attributes and keyboard navigation', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        $this->add_control(
            'enable_debug_output',
            [
                'label'        => esc_html__('Show Debug Output', 'lancedesk-responsive-menu-for-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'lancedesk-responsive-menu-for-elementor'),
                'label_off'    => esc_html__('No', 'lancedesk-responsive-menu-for-elementor'),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => esc_html__('When enabled, prints layout and off-canvas diagnostics under this widget in Elementor editor/preview.', 'lancedesk-responsive-menu-for-elementor'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend
     * 
     * @return void
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        // Resolve menus (global + per-device + off-canvas per-device).
        $menu_depth = LDJEM_Security::sanitize_int($settings['menu_depth'], 3);
        $start_level = LDJEM_Security::sanitize_int($settings['start_level'], 0);
        $menu_sets = $this->resolve_menu_sets($settings);
        $menu_items_map = $this->build_menu_items_map($menu_sets, $menu_depth, $start_level);

        if (empty($menu_items_map)) {
            if (current_user_can('edit_posts')) {
                echo '<div style="padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">';
                echo '<strong>' . esc_html__('Menu Not Selected', 'lancedesk-responsive-menu-for-elementor') . '</strong>';
                echo '<p>' . esc_html__('Please select at least one valid menu in widget settings.', 'lancedesk-responsive-menu-for-elementor') . '</p>';
                echo '</div>';
            }
            return;
        }

        $menu_items = $this->pick_initial_menu_items($menu_sets['standard'], $menu_items_map);

        if (empty($menu_items)) {
            if (current_user_can('edit_posts')) {
                echo '<div style="padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">';
                echo '<strong>' . esc_html__('Menu is Empty', 'lancedesk-responsive-menu-for-elementor') . '</strong>';
                echo '<p>' . esc_html__('All selected menus are empty for the current depth/start-level settings.', 'lancedesk-responsive-menu-for-elementor') . '</p>';
                echo '</div>';
            }
            return;
        }

        // Build CSS classes
        $container_classes = ['ldjem-menu-wrapper'];
        $container_classes[] = 'ldjem-layout-' . sanitize_key($settings['desktop_layout']);
        $widget_id = $this->get_id();
        $desktop_layout = !empty($settings['desktop_layout']) ? sanitize_key($settings['desktop_layout']) : 'horizontal';
        $tablet_layout = !empty($settings['tablet_layout']) ? sanitize_key($settings['tablet_layout']) : $desktop_layout;
        $mobile_layout = !empty($settings['mobile_layout']) ? sanitize_key($settings['mobile_layout']) : 'vertical';
        $offcanvas_enable = !empty($settings['offcanvas_enable']) && 'yes' === $settings['offcanvas_enable'] ? 'yes' : 'no';
        $offcanvas_on_desktop = !empty($settings['offcanvas_on_desktop']) && 'yes' === $settings['offcanvas_on_desktop'] ? 'yes' : 'no';
        $offcanvas_on_tablet = !empty($settings['offcanvas_on_tablet']) && 'yes' === $settings['offcanvas_on_tablet'] ? 'yes' : 'no';
        $offcanvas_on_mobile = !empty($settings['offcanvas_on_mobile']) && 'yes' === $settings['offcanvas_on_mobile'] ? 'yes' : 'no';
        $submenu_trigger = !empty($settings['submenu_trigger']) ? sanitize_key($settings['submenu_trigger']) : 'hover';
        $submenu_accordion = (!empty($settings['submenu_accordion']) && 'yes' === $settings['submenu_accordion']) ? 'yes' : 'no';
        if (!in_array($submenu_trigger, ['hover', 'click', 'hover_click'], true)) {
            $submenu_trigger = 'hover';
        }
        if (!empty($settings['custom_css_class'])) {
            $custom_classes = LDJEM_Security::sanitize_class($settings['custom_css_class']);
            $container_classes[] = $custom_classes;
        }

        $container_classes = $this->apply_standard_menu_suppression_classes($settings, $container_classes);
        $all_offcanvas = $this->is_all_devices_offcanvas($settings);

        if ($all_offcanvas && $this->should_render_offcanvas($settings)) {
            $offcanvas_items = $this->pick_initial_menu_items($menu_sets['offcanvas'], $menu_items_map);
            $this->render_offcanvas_layout($offcanvas_items, $settings, $menu_sets['offcanvas'], $menu_items_map);
            $this->render_debug_output($settings);
            $this->enqueue_widget_assets();
            return;
        }

        $suppress_standard_menu = $all_offcanvas;

        // Get container tag
        $container_tag = sanitize_key($settings['container_tag']);
        if (!in_array($container_tag, ['nav', 'div', 'ul'], true)) {
            $container_tag = 'nav';
        }

        // Apply filters to allow extensions
        $container_classes = apply_filters(LDJEM_PREFIX . '_menu_container_classes', $container_classes, $settings);
        $container_tag = apply_filters(LDJEM_PREFIX . '_menu_container_tag', $container_tag, $settings);

        // Open wrapper
        printf(
            '<%1$s class="%2$s" role="navigation" aria-label="%3$s" data-ldjem-id="%4$s" data-submenu-trigger="%5$s" data-submenu-accordion="%6$s" data-desktop-layout="%7$s" data-tablet-layout="%8$s" data-mobile-layout="%9$s" data-offcanvas-enabled="%10$s" data-offcanvas-desktop="%11$s" data-offcanvas-tablet="%12$s" data-offcanvas-mobile="%13$s" data-menu-id-desktop="%14$d" data-menu-id-tablet="%15$d" data-menu-id-mobile="%16$d">',
            tag_escape($container_tag),
            esc_attr(implode(' ', array_filter($container_classes))),
            esc_attr__('Main Menu', 'lancedesk-responsive-menu-for-elementor'),
            esc_attr($widget_id),
            esc_attr($submenu_trigger),
            esc_attr($submenu_accordion),
            esc_attr($desktop_layout),
            esc_attr($tablet_layout),
            esc_attr($mobile_layout),
            esc_attr($offcanvas_enable),
            esc_attr($offcanvas_on_desktop),
            esc_attr($offcanvas_on_tablet),
            esc_attr($offcanvas_on_mobile),
            intval($menu_sets['standard']['desktop']),
            intval($menu_sets['standard']['tablet']),
            intval($menu_sets['standard']['mobile'])
        );

        // Render hamburger for standard menu only.
        // Off-canvas layout renders its own toggle to avoid duplicate buttons.
        if (!empty($settings['mobile_hamburger_toggle']) && 'yes' === $settings['mobile_hamburger_toggle'] && !$this->should_render_offcanvas($settings)) {
            $this->render_hamburger_menu($settings);
        }

        // Render menu items (skip inline menu when every device uses off-canvas).
        if (!$suppress_standard_menu) {
            printf(
                '<ul class="ldjem-menu ldjem-menu-root">%s</ul>',
                LDJEM_Security::sanitize_menu_markup($this->render_menu_items($menu_items, 0, $settings))
            );
            $this->render_standard_menu_templates($menu_sets['standard'], $menu_items_map, $settings);
        }

        // Close wrapper
        printf('</%s>', tag_escape($container_tag));

        if ($this->should_render_offcanvas($settings)) {
            $offcanvas_items = $this->pick_initial_menu_items($menu_sets['offcanvas'], $menu_items_map);
            $this->render_offcanvas_layout($offcanvas_items, $settings, $menu_sets['offcanvas'], $menu_items_map);
        }

        $this->render_debug_output($settings);
        $this->enqueue_widget_assets();
    }

    /**
     * Enqueue widget frontend assets.
     *
     * @return void
     */
    private function enqueue_widget_assets() {
        wp_enqueue_script(LDJEM_PREFIX . '-frontend');
        wp_enqueue_style(LDJEM_PREFIX . '-frontend');
        wp_enqueue_script(LDJEM_PREFIX . '-offcanvas');
        wp_enqueue_style(LDJEM_PREFIX . '-offcanvas');
    }

    /**
     * Build inline style attribute for off-canvas panel variables.
     *
     * @param array $settings Widget settings.
     * @return string
     */
    private function build_offcanvas_panel_style_attr($settings, $include_surface_background = true) {
        $animation_duration = !empty($settings['offcanvas_animation_duration']) ? intval($settings['offcanvas_animation_duration']) : 300;
        $animation_easing = !empty($settings['offcanvas_animation_easing']) ? sanitize_key($settings['offcanvas_animation_easing']) : 'ease-in-out';
        $z_index = !empty($settings['offcanvas_z_index']) ? intval($settings['offcanvas_z_index']) : 999;
        $panel_size = !empty($settings['offcanvas_panel_size']['size']) ? intval($settings['offcanvas_panel_size']['size']) : 300;
        $panel_height = !empty($settings['offcanvas_panel_height']['size']) ? intval($settings['offcanvas_panel_height']['size']) : 400;

        if ($z_index < 100) {
            $z_index = 100;
        }

        $parts = [
            sprintf('--ldjem-offcanvas-animation-speed: %dms', intval($animation_duration)),
            sprintf('--ldjem-offcanvas-easing: %s', esc_attr($animation_easing)),
            sprintf('--ldjem-offcanvas-z-index: %d', intval($z_index)),
            sprintf('--ldjem-offcanvas-panel-size: %dpx', intval($panel_size)),
            sprintf('--ldjem-offcanvas-panel-height: %dpx', intval($panel_height)),
        ];

        if (!empty($settings['offcanvas_bg_color'])) {
            $panel_bg = $this->sanitize_css_color_value($settings['offcanvas_bg_color']);
            if ($panel_bg) {
                $parts[] = '--ldjem-offcanvas-bg: ' . $panel_bg;
                if ($include_surface_background) {
                    $parts[] = 'background-color: ' . $panel_bg;
                }
            }
        }

        if (!empty($settings['offcanvas_close_btn_color'])) {
            $close_color = $this->sanitize_css_color_value($settings['offcanvas_close_btn_color']);
            if ($close_color) {
                $parts[] = '--ldjem-close-btn-color: ' . $close_color;
            }
        }

        if (!empty($settings['offcanvas_close_btn_bg'])) {
            $close_bg = $this->sanitize_css_color_value($settings['offcanvas_close_btn_bg']);
            if ($close_bg) {
                $parts[] = '--ldjem-close-btn-bg: ' . $close_bg;
            }
        }

        return implode('; ', $parts);
    }

    /**
     * Sanitize a CSS color for inline styles (hex, rgb/a, CSS variables).
     *
     * @param string $color Raw color value.
     * @return string
     */
    private function sanitize_css_color_value($color) {
        $color = trim((string) $color);
        if ($color === '') {
            return '';
        }

        if (0 === strpos($color, 'var(')) {
            return preg_match('/^var\(--[a-zA-Z0-9\-_]+\)$/', $color) ? $color : '';
        }

        $hex = sanitize_hex_color($color);
        if ($hex) {
            return $hex;
        }

        if (preg_match('/^rgba?\(\s*[\d.\s%,]+\s*\)$/i', $color)) {
            return $color;
        }

        return '';
    }

    /**
     * Resolve standard/off-canvas menu IDs per device with fallbacks.
     *
     * @param array $settings Widget settings.
     * @return array
     */
    private function resolve_menu_sets($settings) {
        $base_menu_id = LDJEM_Security::sanitize_menu_id(intval($settings['menu_id']));
        if (false === $base_menu_id) {
            $base_menu_id = 0;
        }

        $use_device_specific = !empty($settings['use_device_specific_menus']) && 'yes' === $settings['use_device_specific_menus'];
        $use_offcanvas_specific = !empty($settings['use_offcanvas_device_specific_menus']) && 'yes' === $settings['use_offcanvas_device_specific_menus'];

        $standard_desktop = $base_menu_id;
        $standard_tablet = $base_menu_id;
        $standard_mobile = $base_menu_id;

        if ($use_device_specific) {
            $standard_desktop = $this->resolve_menu_id_from_setting($settings, 'menu_id_desktop', $base_menu_id);
            $standard_tablet = $this->resolve_menu_id_from_setting($settings, 'menu_id_tablet', $standard_desktop ?: $base_menu_id);
            $standard_mobile = $this->resolve_menu_id_from_setting($settings, 'menu_id_mobile', $standard_tablet ?: ($standard_desktop ?: $base_menu_id));
        }

        $offcanvas_desktop = $standard_desktop;
        $offcanvas_tablet = $standard_tablet;
        $offcanvas_mobile = $standard_mobile;

        if ($use_offcanvas_specific) {
            $offcanvas_desktop = $this->resolve_menu_id_from_setting($settings, 'offcanvas_menu_id_desktop', $standard_desktop);
            $offcanvas_tablet = $this->resolve_menu_id_from_setting($settings, 'offcanvas_menu_id_tablet', $standard_tablet);
            $offcanvas_mobile = $this->resolve_menu_id_from_setting($settings, 'offcanvas_menu_id_mobile', $standard_mobile);
        }

        return [
            'standard' => [
                'desktop' => intval($standard_desktop),
                'tablet'  => intval($standard_tablet),
                'mobile'  => intval($standard_mobile),
            ],
            'offcanvas' => [
                'desktop' => intval($offcanvas_desktop),
                'tablet'  => intval($offcanvas_tablet),
                'mobile'  => intval($offcanvas_mobile),
            ],
        ];
    }

    /**
     * Resolve single menu id setting with fallback.
     *
     * @param array  $settings Widget settings.
     * @param string $setting_key Setting key.
     * @param int    $fallback_id Fallback menu ID.
     * @return int
     */
    private function resolve_menu_id_from_setting($settings, $setting_key, $fallback_id) {
        $menu_id = 0;
        if (isset($settings[$setting_key])) {
            $sanitized = LDJEM_Security::sanitize_menu_id(intval($settings[$setting_key]));
            if (false !== $sanitized) {
                $menu_id = intval($sanitized);
            }
        }
        if ($menu_id > 0) {
            return $menu_id;
        }
        return intval($fallback_id);
    }

    /**
     * Build map of menu items keyed by menu ID.
     *
     * @param array $menu_sets Standard and off-canvas menu IDs.
     * @param int   $menu_depth Depth.
     * @param int   $start_level Start level.
     * @return array
     */
    private function build_menu_items_map($menu_sets, $menu_depth, $start_level) {
        $ids = [];
        foreach (['standard', 'offcanvas'] as $set_key) {
            foreach (['desktop', 'tablet', 'mobile'] as $device) {
                $candidate = !empty($menu_sets[$set_key][$device]) ? intval($menu_sets[$set_key][$device]) : 0;
                if ($candidate > 0) {
                    $ids[$candidate] = $candidate;
                }
            }
        }

        $items_map = [];
        foreach ($ids as $menu_id) {
            $items_map[$menu_id] = LDJEM_Helpers::get_menu_items($menu_id, $menu_depth, $start_level);
        }

        return $items_map;
    }

    /**
     * Pick first non-empty menu items from a device map.
     *
     * @param array $device_menu_ids Menu IDs keyed by device.
     * @param array $menu_items_map Items keyed by menu ID.
     * @return array
     */
    private function pick_initial_menu_items($device_menu_ids, $menu_items_map) {
        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $menu_id = !empty($device_menu_ids[$device]) ? intval($device_menu_ids[$device]) : 0;
            if ($menu_id > 0 && !empty($menu_items_map[$menu_id])) {
                return $menu_items_map[$menu_id];
            }
        }
        return [];
    }

    /**
     * Render hidden templates for device-specific standard menus.
     *
     * @param array $standard_menu_ids Standard menu IDs by device.
     * @param array $menu_items_map Items keyed by menu ID.
     * @param array $settings Widget settings.
     * @return void
     */
    private function render_standard_menu_templates($standard_menu_ids, $menu_items_map, $settings) {
        echo '<div class="ldjem-menu-device-templates" hidden aria-hidden="true">';
        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $menu_id = !empty($standard_menu_ids[$device]) ? intval($standard_menu_ids[$device]) : 0;
            $items = ($menu_id > 0 && isset($menu_items_map[$menu_id])) ? $menu_items_map[$menu_id] : [];
            printf(
                '<ul class="ldjem-menu-template ldjem-menu-template-%1$s" data-ldjem-menu-variant="standard-%1$s" data-menu-id="%2$d">%3$s</ul>',
                esc_attr($device),
                intval($menu_id),
                LDJEM_Security::sanitize_menu_markup($this->render_menu_items($items, 0, $settings))
            );
        }
        echo '</div>';
    }

    /**
     * Render editor-only debug output.
     *
     * @param array $settings Widget settings.
     * @return void
     */
    private function render_debug_output($settings) {
        if (empty($settings['enable_debug_output']) || 'yes' !== $settings['enable_debug_output']) {
            return;
        }

        if (!is_user_logged_in() || !current_user_can('edit_posts')) {
            return;
        }

        $desktop_layout = !empty($settings['desktop_layout']) ? sanitize_key($settings['desktop_layout']) : 'horizontal';
        $tablet_layout = !empty($settings['tablet_layout']) ? sanitize_key($settings['tablet_layout']) : $desktop_layout;
        $mobile_layout = !empty($settings['mobile_layout']) ? sanitize_key($settings['mobile_layout']) : 'vertical';
        $offcanvas_enable = !empty($settings['offcanvas_enable']) && 'yes' === $settings['offcanvas_enable'] ? 'yes' : 'no';
        $offcanvas_on_desktop = !empty($settings['offcanvas_on_desktop']) && 'yes' === $settings['offcanvas_on_desktop'] ? 'yes' : 'no';
        $offcanvas_on_tablet = !empty($settings['offcanvas_on_tablet']) && 'yes' === $settings['offcanvas_on_tablet'] ? 'yes' : 'no';
        $offcanvas_on_mobile = !empty($settings['offcanvas_on_mobile']) && 'yes' === $settings['offcanvas_on_mobile'] ? 'yes' : 'no';
        $menu_sets = $this->resolve_menu_sets($settings);

        $debug = [
            'widget_id' => $this->get_id(),
            'desktop_layout' => $desktop_layout,
            'tablet_layout' => $tablet_layout,
            'mobile_layout' => $mobile_layout,
            'offcanvas_enable' => $offcanvas_enable,
            'offcanvas_on_desktop' => $offcanvas_on_desktop,
            'offcanvas_on_tablet' => $offcanvas_on_tablet,
            'offcanvas_on_mobile' => $offcanvas_on_mobile,
            'use_device_specific_menus' => !empty($settings['use_device_specific_menus']) && 'yes' === $settings['use_device_specific_menus'] ? 'yes' : 'no',
            'use_offcanvas_device_specific_menus' => !empty($settings['use_offcanvas_device_specific_menus']) && 'yes' === $settings['use_offcanvas_device_specific_menus'] ? 'yes' : 'no',
            'standard_menu_ids' => $menu_sets['standard'],
            'offcanvas_menu_ids' => $menu_sets['offcanvas'],
            'offcanvas_rendered' => $this->should_render_offcanvas($settings) ? 'yes' : 'no',
            'note' => 'When offcanvas_on_<device> is yes, off-canvas behavior overrides standard horizontal/vertical for that device.',
        ];

        $widget_id = $this->get_id();
        echo '<pre class="ldjem-debug-output" data-ldjem-debug-widget="' . esc_attr($widget_id) . '" style="margin-top:10px;padding:10px;background:#101010;color:#9dff9d;font-size:12px;line-height:1.4;white-space:pre-wrap;word-break:break-word;border-radius:4px;">';
        echo esc_html(wp_json_encode($debug, JSON_PRETTY_PRINT));
        echo '</pre>';
        echo '<button type="button" class="ldjem-debug-copy-btn" data-ldjem-debug-copy="' . esc_attr($widget_id) . '" style="margin-top:8px;padding:6px 10px;border:1px solid #2b7ec9;border-radius:4px;background:#14324a;color:#9bd6ff;font-size:12px;cursor:pointer;">Copy Debug Log</button>';
        echo '<div class="ldjem-debug-runtime" data-ldjem-debug-runtime="' . esc_attr($widget_id) . '" style="margin-top:6px;padding:8px 10px;background:#1a1a1a;color:#7fd3ff;font-size:12px;line-height:1.35;border-radius:4px;"></div>';
        $debug_script = <<<'JS'
        (function () {
            var widgetId = __WIDGET_ID__;
            var runtimeEl = document.querySelector('[data-ldjem-debug-runtime="' + widgetId + '"]');
            var debugEl = document.querySelector('[data-ldjem-debug-widget="' + widgetId + '"]');
            var copyBtn = document.querySelector('[data-ldjem-debug-copy="' + widgetId + '"]');
            if (!runtimeEl || !debugEl || !copyBtn) return;

            var topDoc = null;
            try {
                topDoc = window.top && window.top.document ? window.top.document : null;
            } catch (e) {
                topDoc = null;
            }

            var getClassSummary = function (docRef) {
                if (!docRef || !docRef.documentElement) {
                    return 'n/a';
                }
                var htmlClass = docRef.documentElement.className || '';
                var bodyClass = docRef.body ? (docRef.body.className || '') : '';
                return 'html=' + htmlClass + ' | body=' + bodyClass;
            };

            var getDevice = function () {
                if (window.elementorFrontend && typeof window.elementorFrontend.getCurrentDeviceMode === 'function') {
                    var mode = window.elementorFrontend.getCurrentDeviceMode();
                    if (mode === 'mobile' || mode === 'tablet' || mode === 'desktop') return mode;
                }
                var classPool = [
                    (document.documentElement && document.documentElement.className) || '',
                    (document.body && document.body.className) || '',
                    (topDoc && topDoc.documentElement && topDoc.documentElement.className) || '',
                    (topDoc && topDoc.body && topDoc.body.className) || ''
                ].join(' ');
                if (classPool.indexOf('elementor-device-mobile') !== -1 || classPool.indexOf('elementor-editor-device-mobile') !== -1) {
                    return 'mobile';
                }
                if (classPool.indexOf('elementor-device-tablet') !== -1 || classPool.indexOf('elementor-editor-device-tablet') !== -1) {
                    return 'tablet';
                }
                if (classPool.indexOf('elementor-device-desktop') !== -1 || classPool.indexOf('elementor-editor-device-desktop') !== -1) {
                    return 'desktop';
                }
                var w = window.innerWidth || document.documentElement.clientWidth;
                if (w <= 767) return 'mobile';
                if (w <= 1024) return 'tablet';
                return 'desktop';
            };

            var refresh = function (context) {
                var wrapper = document.querySelector('.ldjem-menu-wrapper[data-ldjem-id="' + widgetId + '"]');
                if (!wrapper) {
                    runtimeEl.textContent = 'Runtime: wrapper not found';
                    return;
                }
                var device = getDevice();
                var offcanvasFlag = wrapper.getAttribute('data-offcanvas-' + device);
                var activeLayout = wrapper.getAttribute('data-' + device + '-layout') || 'n/a';
                var configuredDesktopLayout = wrapper.getAttribute('data-desktop-layout') || 'n/a';
                var configuredTabletLayout = wrapper.getAttribute('data-tablet-layout') || 'n/a';
                var configuredMobileLayout = wrapper.getAttribute('data-mobile-layout') || 'n/a';
                var offcanvasDesktop = wrapper.getAttribute('data-offcanvas-desktop') || 'no';
                var offcanvasTablet = wrapper.getAttribute('data-offcanvas-tablet') || 'no';
                var offcanvasMobile = wrapper.getAttribute('data-offcanvas-mobile') || 'no';
                var offcanvasWrapper = document.querySelector('.ldjem-menu-wrapper-offcanvas[data-ldjem-id="' + widgetId + '"] .ldjem-offcanvas-wrapper');
                var offcanvasRoot = document.querySelector('.ldjem-menu-wrapper-offcanvas[data-ldjem-id="' + widgetId + '"]');
                var standardHamburger = wrapper.querySelector('.ldjem-hamburger');
                var offcanvasHamburger = offcanvasWrapper ? offcanvasWrapper.closest('.ldjem-menu-wrapper-offcanvas').querySelector('.ldjem-hamburger') : null;
                var hamburger = offcanvasFlag === 'yes' ? offcanvasHamburger : standardHamburger;
                var standardWrapper = document.querySelector('.ldjem-menu-wrapper[data-ldjem-id="' + widgetId + '"]:not(.ldjem-menu-wrapper-offcanvas)');
                var hamburgerTargetScope = offcanvasFlag === 'yes' ? 'offcanvas' : 'standard';
                var hamburgerIcon = hamburger ? hamburger.querySelector('svg, i') : null;
                var header = offcanvasWrapper ? offcanvasWrapper.querySelector('.ldjem-offcanvas-header') : null;
                var closeBtn = offcanvasWrapper ? offcanvasWrapper.querySelector('.ldjem-offcanvas-close') : null;
                var logoImg = offcanvasWrapper ? offcanvasWrapper.querySelector('.ldjem-offcanvas-logo img') : null;
                var hamburgerCss = hamburger ? window.getComputedStyle(hamburger) : null;
                var iconCss = hamburgerIcon ? window.getComputedStyle(hamburgerIcon) : null;
                var headerCss = header ? window.getComputedStyle(header) : null;
                var closeCss = closeBtn ? window.getComputedStyle(closeBtn) : null;
                var logoCss = logoImg ? window.getComputedStyle(logoImg) : null;
                var styleDump = [
                    'Runtime (' + context + '):',
                    'active_device=' + device,
                    'configured_layouts={desktop:' + configuredDesktopLayout + ', tablet:' + configuredTabletLayout + ', mobile:' + configuredMobileLayout + '}',
                    'configured_layout_for_active_device=' + activeLayout,
                    'offcanvas_device_flags={desktop:' + offcanvasDesktop + ', tablet:' + offcanvasTablet + ', mobile:' + offcanvasMobile + '}',
                    'effective_layout=' + (offcanvasFlag === 'yes' ? 'offcanvas' : activeLayout),
                    'offcanvas_active_for_device=' + (offcanvasFlag === 'yes' ? 'yes' : 'no'),
                    'hamburger_target_scope=' + hamburgerTargetScope,
                    'dom_presence={standard_wrapper:' + (standardWrapper ? 'yes' : 'no') + ', offcanvas_wrapper:' + (offcanvasRoot ? 'yes' : 'no') + ', standard_hamburger:' + (standardHamburger ? 'yes' : 'no') + ', offcanvas_hamburger:' + (offcanvasHamburger ? 'yes' : 'no') + ', selected_hamburger:' + (hamburger ? 'yes' : 'no') + '}',
                    'iframe_classes={' + getClassSummary(document) + '}',
                    'top_classes={' + getClassSummary(topDoc) + '}',
                    'header_bg=' + (headerCss ? headerCss.backgroundColor : 'n/a'),
                    'header_bg_image=' + (headerCss ? headerCss.backgroundImage : 'n/a'),
                    'close_color=' + (closeCss ? closeCss.color : 'n/a'),
                    'logo_max_width=' + (logoCss ? logoCss.maxWidth : 'n/a'),
                    'hamburger_display=' + (hamburgerCss ? hamburgerCss.display : 'n/a'),
                    'hamburger_size=' + (hamburgerCss ? (hamburgerCss.width + 'x' + hamburgerCss.height) : 'n/a'),
                    'hamburger_color=' + (hamburgerCss ? hamburgerCss.color : 'n/a'),
                    'hamburger_has_icon=' + (hamburgerIcon ? 'yes' : 'no'),
                    'hamburger_icon_color=' + (iconCss ? iconCss.color : 'n/a'),
                    'hamburger_force_fallback=' + (hamburger && hamburger.classList.contains('ldjem-hamburger-force-fallback') ? 'yes' : 'no'),
                    'hamburger_position_class=' + (hamburger ? Array.from(hamburger.classList).filter(function (cn) { return cn.indexOf('ldjem-hamburger-') === 0; }).join(',') : 'n/a'),
                    'hamburger_scope=' + (offcanvasFlag === 'yes' ? 'offcanvas-wrapper' : 'standard-wrapper'),
                    'offcanvas_root_classes=' + (offcanvasRoot ? offcanvasRoot.className : 'n/a'),
                    'offcanvas_open_class=' + (offcanvasWrapper && offcanvasWrapper.classList.contains('is-open') ? 'yes' : 'no'),
                    'offcanvas_aria_hidden=' + (offcanvasWrapper ? (offcanvasWrapper.getAttribute('aria-hidden') || '') : 'n/a'),
                    'editor_preview_open_attr=' + (offcanvasRoot ? (offcanvasRoot.getAttribute('data-ldjem-editor-preview-open') || '') : 'n/a'),
                    '--ldjem-offcanvas-bg=' + (offcanvasWrapper ? offcanvasWrapper.style.getPropertyValue('--ldjem-offcanvas-bg') : ''),
                    '--ldjem-offcanvas-header-bg=' + (offcanvasWrapper ? offcanvasWrapper.style.getPropertyValue('--ldjem-offcanvas-header-bg') : ''),
                    '--ldjem-close-btn-color=' + (offcanvasWrapper ? offcanvasWrapper.style.getPropertyValue('--ldjem-close-btn-color') : '')
                ];
                runtimeEl.textContent = styleDump.join('\n');
                if (window.console && console.info) {
                    console.info('[LDJEM debug][' + widgetId + ']', styleDump.join(' | '));
                }
            };

            var copyText = function (text) {
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    return navigator.clipboard.writeText(text);
                }
                return new Promise(function (resolve, reject) {
                    var temp = document.createElement('textarea');
                    temp.value = text;
                    temp.setAttribute('readonly', 'readonly');
                    temp.style.position = 'fixed';
                    temp.style.opacity = '0';
                    document.body.appendChild(temp);
                    temp.select();
                    var ok = false;
                    try {
                        ok = document.execCommand('copy');
                    } catch (err) {
                        ok = false;
                    }
                    document.body.removeChild(temp);
                    if (ok) {
                        resolve();
                    } else {
                        reject(new Error('copy_failed'));
                    }
                });
            };

            copyBtn.addEventListener('click', function () {
                var payload = [
                    '[LDJEM Debug JSON]',
                    debugEl.textContent || '',
                    '',
                    '[LDJEM Runtime]',
                    runtimeEl.textContent || ''
                ].join('\n');
                var defaultLabel = 'Copy Debug Log';
                copyBtn.disabled = true;
                copyText(payload).then(function () {
                    copyBtn.textContent = 'Copied';
                }).catch(function () {
                    copyBtn.textContent = 'Copy Failed';
                }).finally(function () {
                    setTimeout(function () {
                        copyBtn.disabled = false;
                        copyBtn.textContent = defaultLabel;
                    }, 1400);
                });
            });

            if (window.jQuery) {
                window.jQuery(document).on('ldjem:offcanvas:hamburger-click ldjem:offcanvas:native-click-capture ldjem:offcanvas:toggle-attempt ldjem:offcanvas:overlay-bridge ldjem:offcanvas:opened ldjem:offcanvas:closed ldjem:offcanvas:debug', function (event, payload) {
                    if (!payload || payload.widgetId !== widgetId) {
                        return;
                    }
                    var now = new Date();
                    var hh = String(now.getHours()).padStart(2, '0');
                    var mm = String(now.getMinutes()).padStart(2, '0');
                    var ss = String(now.getSeconds()).padStart(2, '0');
                    var base = runtimeEl.textContent || '';
                    var line = '[event ' + hh + ':' + mm + ':' + ss + '] ' + event.type + ' ' + JSON.stringify(payload);
                    runtimeEl.textContent = base ? (base + '\n' + line) : line;
                });
            }

            refresh('init');
            window.addEventListener('resize', function () { refresh('resize'); });
            var observer = new MutationObserver(function () { refresh('preview-toggle'); });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            if (document.body) {
                observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
            }
            if (topDoc && topDoc.documentElement) {
                observer.observe(topDoc.documentElement, { attributes: true, attributeFilter: ['class'] });
            }
            if (topDoc && topDoc.body) {
                observer.observe(topDoc.body, { attributes: true, attributeFilter: ['class'] });
            }
        })();
JS;

        $debug_script = str_replace('__WIDGET_ID__', wp_json_encode($widget_id), $debug_script);
        wp_add_inline_script(LDJEM_PREFIX . '-frontend', $debug_script);
    }

    /**
     * Render the off-canvas layout.
     *
     * @param array $menu_items Menu items.
     * @param array $settings Widget settings.
     * @param array $offcanvas_menu_ids Off-canvas menu IDs keyed by device.
     * @param array $menu_items_map Items keyed by menu ID.
     * @return void
     */
    private function render_offcanvas_layout($menu_items, $settings, $offcanvas_menu_ids = [], $menu_items_map = []) {
        $widget_id = $this->get_id();
        $direction = !empty($settings['offcanvas_direction']) ? sanitize_key($settings['offcanvas_direction']) : 'left';

        if (!in_array($direction, ['left', 'right', 'top', 'bottom'], true)) {
            $direction = 'left';
        }

        $animation_duration = !empty($settings['offcanvas_animation_duration']) ? intval($settings['offcanvas_animation_duration']) : 300;
        $animation_easing = !empty($settings['offcanvas_animation_easing']) ? sanitize_key($settings['offcanvas_animation_easing']) : 'ease-in-out';
        $z_index = !empty($settings['offcanvas_z_index']) ? intval($settings['offcanvas_z_index']) : 999;
        $panel_size = !empty($settings['offcanvas_panel_size']['size']) ? intval($settings['offcanvas_panel_size']['size']) : 300;
        $panel_height = !empty($settings['offcanvas_panel_height']['size']) ? intval($settings['offcanvas_panel_height']['size']) : 400;
        $offcanvas_on_desktop = !empty($settings['offcanvas_on_desktop']) && 'yes' === $settings['offcanvas_on_desktop'] ? 'yes' : 'no';
        $offcanvas_on_tablet = !empty($settings['offcanvas_on_tablet']) && 'yes' === $settings['offcanvas_on_tablet'] ? 'yes' : 'no';
        $offcanvas_on_mobile = !empty($settings['offcanvas_on_mobile']) && 'yes' === $settings['offcanvas_on_mobile'] ? 'yes' : 'no';
        $direction_desktop = !empty($settings['offcanvas_direction_desktop']) ? sanitize_key($settings['offcanvas_direction_desktop']) : 'inherit';
        $direction_tablet = !empty($settings['offcanvas_direction_tablet']) ? sanitize_key($settings['offcanvas_direction_tablet']) : 'inherit';
        $direction_mobile = !empty($settings['offcanvas_direction_mobile']) ? sanitize_key($settings['offcanvas_direction_mobile']) : 'inherit';
        $animation_duration_desktop = !empty($settings['offcanvas_animation_duration_desktop']) ? intval($settings['offcanvas_animation_duration_desktop']) : 0;
        $animation_duration_tablet = !empty($settings['offcanvas_animation_duration_tablet']) ? intval($settings['offcanvas_animation_duration_tablet']) : 0;
        $animation_duration_mobile = !empty($settings['offcanvas_animation_duration_mobile']) ? intval($settings['offcanvas_animation_duration_mobile']) : 0;
        $panel_size_desktop = !empty($settings['offcanvas_panel_size_desktop']['size']) ? intval($settings['offcanvas_panel_size_desktop']['size']) : 0;
        $panel_size_tablet = !empty($settings['offcanvas_panel_size_tablet']['size']) ? intval($settings['offcanvas_panel_size_tablet']['size']) : 0;
        $panel_size_mobile = !empty($settings['offcanvas_panel_size_mobile']['size']) ? intval($settings['offcanvas_panel_size_mobile']['size']) : 0;
        $panel_height_desktop = !empty($settings['offcanvas_panel_height_desktop']['size']) ? intval($settings['offcanvas_panel_height_desktop']['size']) : 0;
        $panel_height_tablet = !empty($settings['offcanvas_panel_height_tablet']['size']) ? intval($settings['offcanvas_panel_height_tablet']['size']) : 0;
        $panel_height_mobile = !empty($settings['offcanvas_panel_height_mobile']['size']) ? intval($settings['offcanvas_panel_height_mobile']['size']) : 0;
        $desktop_layout = !empty($settings['desktop_layout']) ? sanitize_key($settings['desktop_layout']) : 'horizontal';
        $tablet_layout = !empty($settings['tablet_layout']) ? sanitize_key($settings['tablet_layout']) : $desktop_layout;
        $mobile_layout = !empty($settings['mobile_layout']) ? sanitize_key($settings['mobile_layout']) : 'vertical';
        $allowed_easing = ['ease-in-out', 'ease-in', 'ease-out', 'linear'];
        $allowed_direction = ['inherit', 'left', 'right', 'top', 'bottom'];
        $allowed_layout = ['horizontal', 'vertical', 'grid'];

        if (!in_array($animation_easing, $allowed_easing, true)) {
            $animation_easing = 'ease-in-out';
        }
        if (!in_array($direction_desktop, $allowed_direction, true)) {
            $direction_desktop = 'inherit';
        }
        if (!in_array($direction_tablet, $allowed_direction, true)) {
            $direction_tablet = 'inherit';
        }
        if (!in_array($direction_mobile, $allowed_direction, true)) {
            $direction_mobile = 'inherit';
        }
        if (!in_array($desktop_layout, $allowed_layout, true)) {
            $desktop_layout = 'horizontal';
        }
        if (!in_array($tablet_layout, $allowed_layout, true)) {
            $tablet_layout = $desktop_layout;
        }
        if (!in_array($mobile_layout, $allowed_layout, true)) {
            $mobile_layout = 'vertical';
        }

        if ($z_index < 100) {
            $z_index = 100;
        }

        $submenu_trigger = !empty($settings['submenu_trigger']) ? sanitize_key($settings['submenu_trigger']) : 'click';
        $submenu_accordion = (!empty($settings['submenu_accordion']) && 'yes' === $settings['submenu_accordion']) ? 'yes' : 'no';
        $offcanvas_submenu_mode = !empty($settings['offcanvas_submenu_mode']) ? sanitize_key($settings['offcanvas_submenu_mode']) : 'drilldown';
        if (!in_array($submenu_trigger, ['hover', 'click', 'hover_click'], true)) {
            $submenu_trigger = 'click';
        }
        if (!in_array($offcanvas_submenu_mode, ['drilldown', 'accordion'], true)) {
            $offcanvas_submenu_mode = 'drilldown';
        }

        $wrapper_style = $this->build_offcanvas_panel_style_attr($settings, false);
        $panel_style = $this->build_offcanvas_panel_style_attr($settings, true);

        printf(
            '<div class="ldjem-menu-wrapper ldjem-menu-wrapper-offcanvas" data-ldjem-id="%1$s" data-ldjem-offcanvas="true" data-offcanvas-desktop="%2$s" data-offcanvas-tablet="%3$s" data-offcanvas-mobile="%4$s" data-direction-desktop="%5$s" data-direction-tablet="%6$s" data-direction-mobile="%7$s" data-animation-duration-desktop="%8$d" data-animation-duration-tablet="%9$d" data-animation-duration-mobile="%10$d" data-panel-size-desktop="%11$d" data-panel-size-tablet="%12$d" data-panel-size-mobile="%13$d" data-panel-height-desktop="%14$d" data-panel-height-tablet="%15$d" data-panel-height-mobile="%16$d" data-desktop-layout="%17$s" data-tablet-layout="%18$s" data-mobile-layout="%19$s" data-menu-id-desktop="%20$d" data-menu-id-tablet="%21$d" data-menu-id-mobile="%22$d" data-submenu-trigger="%23$s" data-submenu-accordion="%24$s" data-offcanvas-submenu-mode="%25$s" style="%26$s">',
            esc_attr($widget_id),
            esc_attr($offcanvas_on_desktop),
            esc_attr($offcanvas_on_tablet),
            esc_attr($offcanvas_on_mobile),
            esc_attr($direction_desktop),
            esc_attr($direction_tablet),
            esc_attr($direction_mobile),
            intval($animation_duration_desktop),
            intval($animation_duration_tablet),
            intval($animation_duration_mobile),
            intval($panel_size_desktop),
            intval($panel_size_tablet),
            intval($panel_size_mobile),
            intval($panel_height_desktop),
            intval($panel_height_tablet),
            intval($panel_height_mobile),
            esc_attr($desktop_layout),
            esc_attr($tablet_layout),
            esc_attr($mobile_layout),
            !empty($offcanvas_menu_ids['desktop']) ? intval($offcanvas_menu_ids['desktop']) : 0,
            !empty($offcanvas_menu_ids['tablet']) ? intval($offcanvas_menu_ids['tablet']) : 0,
            !empty($offcanvas_menu_ids['mobile']) ? intval($offcanvas_menu_ids['mobile']) : 0,
            esc_attr($submenu_trigger),
            esc_attr($submenu_accordion),
            esc_attr($offcanvas_submenu_mode),
            esc_attr($wrapper_style)
        );

        $this->render_hamburger_menu($settings, true);

        printf(
            '<div class="ldjem-offcanvas-wrapper direction-%1$s" data-ldjem-id="%2$s" role="dialog" aria-modal="true" aria-hidden="true" aria-label="%3$s" style="%4$s">',
            esc_attr($direction),
            esc_attr($widget_id),
            esc_attr__('Main Menu', 'lancedesk-responsive-menu-for-elementor'),
            esc_attr($panel_style)
        );

        if (!empty($settings['offcanvas_show_header']) && 'yes' === $settings['offcanvas_show_header']) {
            echo '<div class="ldjem-offcanvas-header">';
            $this->render_offcanvas_header($settings);
            $this->render_offcanvas_close_button($settings);
            echo '</div>';
        } elseif (!empty($settings['offcanvas_show_close_btn']) && 'yes' === $settings['offcanvas_show_close_btn']) {
            echo '<div class="ldjem-offcanvas-close-standalone-wrap">';
            $this->render_offcanvas_close_button($settings);
            echo '</div>';
        }

        echo '<div class="ldjem-offcanvas-menu-container">';
        echo '<ul class="ldjem-offcanvas-menu">' . LDJEM_Security::sanitize_menu_markup($this->render_offcanvas_menu_items($menu_items, 0, $settings)) . '</ul>';
        echo '<div class="ldjem-offcanvas-device-templates" hidden aria-hidden="true">';
        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $menu_id = !empty($offcanvas_menu_ids[$device]) ? intval($offcanvas_menu_ids[$device]) : 0;
            $items = ($menu_id > 0 && isset($menu_items_map[$menu_id])) ? $menu_items_map[$menu_id] : [];
            printf(
                '<ul class="ldjem-offcanvas-menu-template ldjem-offcanvas-menu-template-%1$s" data-ldjem-menu-variant="offcanvas-%1$s" data-menu-id="%2$d">%3$s</ul>',
                esc_attr($device),
                intval($menu_id),
                LDJEM_Security::sanitize_menu_markup($this->render_offcanvas_menu_items($items, 0, $settings))
            );
        }
        echo '</div>';
        echo '</div>';

        if (!empty($settings['offcanvas_show_footer']) && 'yes' === $settings['offcanvas_show_footer']) {
            echo '<div class="ldjem-offcanvas-footer">';
            $this->render_offcanvas_footer($settings);
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Determine whether the off-canvas layer should be rendered.
     *
     * @param array $settings Widget settings.
     * @return bool
     */
    private function should_render_offcanvas($settings) {
        if (empty($settings['offcanvas_enable']) || 'yes' !== $settings['offcanvas_enable']) {
            return false;
        }

        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            if ($this->is_device_offcanvas_enabled($settings, $device)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether off-canvas is enabled for a specific device.
     *
     * @param array  $settings Widget settings.
     * @param string $device   desktop|tablet|mobile
     * @return bool
     */
    private function is_device_offcanvas_enabled($settings, $device) {
        if (empty($settings['offcanvas_enable']) || 'yes' !== $settings['offcanvas_enable']) {
            return false;
        }

        $key = 'offcanvas_on_' . sanitize_key($device);
        return !empty($settings[$key]) && 'yes' === $settings[$key];
    }

    /**
     * Whether every breakpoint uses off-canvas.
     *
     * @param array $settings Widget settings.
     * @return bool
     */
    private function is_all_devices_offcanvas($settings) {
        return $this->is_device_offcanvas_enabled($settings, 'desktop')
            && $this->is_device_offcanvas_enabled($settings, 'tablet')
            && $this->is_device_offcanvas_enabled($settings, 'mobile');
    }

    /**
     * Add CSS classes that suppress the standard menu per device.
     *
     * @param array $settings           Widget settings.
     * @param array $container_classes  Existing wrapper classes.
     * @return array
     */
    private function apply_standard_menu_suppression_classes($settings, $container_classes) {
        if (!$this->should_render_offcanvas($settings)) {
            return $container_classes;
        }

        if ($this->is_device_offcanvas_enabled($settings, 'desktop')) {
            $container_classes[] = 'ldjem-offcanvas-replaces-desktop';
        }
        if ($this->is_device_offcanvas_enabled($settings, 'tablet')) {
            $container_classes[] = 'ldjem-offcanvas-replaces-tablet';
        }
        if ($this->is_device_offcanvas_enabled($settings, 'mobile')) {
            $container_classes[] = 'ldjem-offcanvas-replaces-mobile';
        }
        if ($this->is_all_devices_offcanvas($settings)) {
            $container_classes[] = 'ldjem-offcanvas-replaces-all';
        }

        return $container_classes;
    }

    /**
     * Render off-canvas close button markup.
     *
     * @param array $settings Widget settings.
     * @return void
     */
    private function render_offcanvas_close_button($settings) {
        if (empty($settings['offcanvas_show_close_btn']) || 'yes' !== $settings['offcanvas_show_close_btn']) {
            return;
        }

        $close_type = !empty($settings['offcanvas_close_icon_type']) ? sanitize_key($settings['offcanvas_close_icon_type']) : 'icon';
        if (!in_array($close_type, ['icon', 'letter'], true)) {
            $close_type = 'icon';
        }

        if ('letter' === $close_type) {
            $letter = isset($settings['offcanvas_close_letter']) ? (string) $settings['offcanvas_close_letter'] : '×';
            if ('' === trim($letter)) {
                $letter = '×';
            }

            printf(
                '<button class="ldjem-offcanvas-close ldjem-close-type-letter" type="button" aria-label="%1$s"><span class="ldjem-close-letter" aria-hidden="true">%2$s</span></button>',
                esc_attr__('Close menu', 'lancedesk-responsive-menu-for-elementor'),
                esc_html($letter)
            );
            return;
        }

        $close_icon = $this->resolve_offcanvas_close_icon_for_render($settings);

        printf(
            '<button class="ldjem-offcanvas-close ldjem-close-type-icon has-custom-icon" type="button" aria-label="%1$s">',
            esc_attr__('Close menu', 'lancedesk-responsive-menu-for-elementor')
        );

        Icons_Manager::render_icon($close_icon, ['aria-hidden' => 'true']);

        echo '</button>';
    }

    /**
     * Render off-canvas header.
     *
     * @param array $settings Widget settings.
     * @return void
     */
    private function render_offcanvas_header($settings) {
        $logo = !empty($settings['offcanvas_logo']) ? $settings['offcanvas_logo'] : [];
        $logo_dark = !empty($settings['offcanvas_logo_dark']) ? $settings['offcanvas_logo_dark'] : [];
        $header_text = !empty($settings['offcanvas_header_text']) ? $settings['offcanvas_header_text'] : '';
        $logo_alt = !empty($settings['offcanvas_logo_alt']) ? $settings['offcanvas_logo_alt'] : esc_html__('Logo', 'lancedesk-responsive-menu-for-elementor');
        $dark_logo_enabled = !empty($settings['offcanvas_dark_logo_enabled']) && 'yes' === $settings['offcanvas_dark_logo_enabled'];
        $dark_mode_source = !empty($settings['offcanvas_logo_dark_mode_source']) ? sanitize_key($settings['offcanvas_logo_dark_mode_source']) : 'auto';
        if (!in_array($dark_mode_source, ['auto', 'accessibility', 'system'], true)) {
            $dark_mode_source = 'auto';
        }
        $has_dark_logo = $dark_logo_enabled && !empty($logo_dark['url']);

        $logo_wrapper_classes = 'ldjem-offcanvas-logo';
        if ($has_dark_logo) {
            $logo_wrapper_classes .= ' ldjem-offcanvas-logo-dual ldjem-logo-dark-mode-' . $dark_mode_source;
        }
        echo '<div class="' . esc_attr($logo_wrapper_classes) . '">';

        if (!empty($logo['url'])) {
            $logo_link = !empty($settings['offcanvas_logo_link']['url']) ? $settings['offcanvas_logo_link']['url'] : '';

            if (!empty($logo_link)) {
                printf('<a href="%s">', esc_url($logo_link));
            }

            if ($has_dark_logo) {
                printf(
                    '<span class="ldjem-offcanvas-logo-image ldjem-offcanvas-logo-image-light"><img src="%1$s" alt="%2$s"></span><span class="ldjem-offcanvas-logo-image ldjem-offcanvas-logo-image-dark"><img src="%3$s" alt="%2$s"></span>',
                    esc_url($logo['url']),
                    esc_attr($logo_alt),
                    esc_url($logo_dark['url'])
                );
            } else {
                printf(
                    '<img src="%1$s" alt="%2$s">',
                    esc_url($logo['url']),
                    esc_attr($logo_alt)
                );
            }

            if (!empty($logo_link)) {
                echo '</a>';
            }
        }

        if (!empty($settings['offcanvas_show_header_text']) && 'yes' === $settings['offcanvas_show_header_text'] && '' !== trim($header_text)) {
            printf('<span class="ldjem-offcanvas-logo-text">%s</span>', esc_html($header_text));
        }
        echo '</div>';
    }

    /**
     * Resolve off-canvas close icon for render (Elementor icons + legacy icon strings).
     *
     * @param array $settings Widget settings.
     * @return array Elementor icon setting.
     */
    private function resolve_offcanvas_close_icon_for_render($settings) {
        $default_icon = [
            'value'   => 'fas fa-times',
            'library' => 'fa-solid',
        ];
        $legacy_icon_map = [
            'x'       => $default_icon,
            'arrow'   => [
                'value'   => 'fas fa-arrow-left',
                'library' => 'fa-solid',
            ],
            'chevron' => [
                'value'   => 'fas fa-chevron-left',
                'library' => 'fa-solid',
            ],
        ];

        $close_icon = isset($settings['offcanvas_close_icon']) ? $settings['offcanvas_close_icon'] : null;

        if (is_string($close_icon)) {
            return isset($legacy_icon_map[$close_icon]) ? $legacy_icon_map[$close_icon] : $default_icon;
        }

        if (is_array($close_icon)) {
            $value = isset($close_icon['value']) ? $close_icon['value'] : '';
            $library = isset($close_icon['library']) ? trim((string) $close_icon['library']) : '';

            if (is_string($value)) {
                $legacy_key = trim($value);
                if ('' !== $legacy_key && in_array($legacy_key, ['x', 'arrow', 'chevron'], true) && '' === $library) {
                    return $legacy_icon_map[$legacy_key];
                }
            }

            if ($this->is_valid_elementor_icon_setting($close_icon)) {
                return $close_icon;
            }
        }

        return $default_icon;
    }

    /**
     * Check whether an Elementor Icons control value is usable for render.
     *
     * @param mixed $icon_setting Icon control value.
     * @return bool
     */
    private function is_valid_elementor_icon_setting($icon_setting) {
        if (!is_array($icon_setting) || !isset($icon_setting['value'])) {
            return false;
        }

        $value = $icon_setting['value'];

        if (is_string($value)) {
            return '' !== trim($value);
        }

        if (is_array($value)) {
            return !empty($value['url']) || !empty($value['id']);
        }

        return false;
    }

    /**
     * Render off-canvas footer.
     *
     * @param array $settings Widget settings.
     * @return void
     */
    private function render_offcanvas_footer($settings) {
        if (!empty($settings['offcanvas_show_footer_title']) && 'yes' === $settings['offcanvas_show_footer_title'] && !empty($settings['offcanvas_footer_title'])) {
            printf('<div class="ldjem-offcanvas-footer-title">%s</div>', esc_html($settings['offcanvas_footer_title']));
        }

        if (empty($settings['offcanvas_social_icons']) || !is_array($settings['offcanvas_social_icons'])) {
            return;
        }

        $icon_size = !empty($settings['offcanvas_social_icon_size']['size']) ? intval($settings['offcanvas_social_icon_size']['size']) : 36;
        echo '<ul class="ldjem-offcanvas-social">';

        foreach ($settings['offcanvas_social_icons'] as $social_item) {
            $platform = !empty($social_item['social_platform']) ? sanitize_key($social_item['social_platform']) : 'facebook';
            $url = !empty($social_item['social_url']['url']) ? esc_url($social_item['social_url']['url']) : '';

            if (empty($url)) {
                continue;
            }

            printf(
                '<li class="ldjem-offcanvas-social-item"><a class="ldjem-offcanvas-social-link" href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%2$s" style="width: %3$dpx; height: %3$dpx;"><span aria-hidden="true">%4$s</span></a></li>',
                esc_url($url),
                esc_attr(ucfirst($platform)),
                intval($icon_size),
                esc_html($this->get_social_label($platform))
            );
        }

        echo '</ul>';
    }

    /**
     * Render off-canvas menu items.
     *
     * @param array $items Menu items.
     * @param int $level Current nesting level.
     * @param array $settings Widget settings.
     * @return string
     */
    /**
     * CSS animation class for submenu panels from Style > Submenus.
     *
     * @param array $settings Widget settings.
     * @return string
     */
    private function get_submenu_animation_class($settings) {
        $animation = !empty($settings['submenu_animation']) ? sanitize_key($settings['submenu_animation']) : 'fade';

        if ('none' === $animation || empty($animation)) {
            return '';
        }

        return 'animation-' . $animation;
    }

    /**
     * Render submenu toggle button markup shared by standard and off-canvas menus.
     *
     * @param array $settings Widget settings.
     * @return string
     */
    private function render_submenu_toggle_markup($settings) {
        $html = '<button class="ldjem-submenu-toggle" type="button" aria-label="' . esc_attr__('Toggle submenu', 'lancedesk-responsive-menu-for-elementor') . '" aria-expanded="false">';

        $icon_html = '';
        if (!empty($settings['submenu_indicator_icon']) && !empty($settings['submenu_indicator_icon']['value'])) {
            ob_start();
            Icons_Manager::render_icon($settings['submenu_indicator_icon'], ['aria-hidden' => 'true']);
            $icon_html = trim(ob_get_clean());
        }

        if ('' !== $icon_html) {
            $html .= $icon_html;
        } else {
            $html .= '<span class="ldjem-submenu-toggle-fallback" aria-hidden="true">&#9662;</span>';
        }

        $html .= '</button>';

        return $html;
    }

    /**
     * Whether off-canvas submenus use drill-down panels.
     *
     * @param array $settings Widget settings.
     * @return bool
     */
    private function is_offcanvas_drilldown_mode($settings) {
        $mode = !empty($settings['offcanvas_submenu_mode']) ? sanitize_key($settings['offcanvas_submenu_mode']) : 'drilldown';
        return 'drilldown' === $mode;
    }

    private function render_offcanvas_menu_items($items, $level = 0, $settings = []) {
        if (empty($items)) {
            return '';
        }

        $html = '';

        foreach ($items as $item) {
            $classes = [$level === 0 ? 'ldjem-offcanvas-menu-item' : 'ldjem-offcanvas-submenu-item'];
            $item_classes = isset($item->classes) && is_array($item->classes) ? $item->classes : [];
            $item_classes = array_filter(array_map('sanitize_html_class', $item_classes));
            if (!empty($item_classes)) {
                $classes = array_merge($classes, $item_classes);
            }

            if (!empty($settings['mark_active_item']) && 'yes' === $settings['mark_active_item']) {
                if (!empty($item->current)) {
                    $classes[] = 'is-active';
                    $classes[] = 'is-current';
                }

                if (!empty($item->current_item_ancestor)) {
                    $classes[] = 'is-active';
                }
            }

            if (!empty($item->children)) {
                $classes[] = 'has-children';
            }

            $classes[] = 'level-' . intval($level);

            $html .= sprintf(
                '<li class="%1$s">',
                esc_attr(implode(' ', $classes))
            );

            $html .= sprintf(
                '<a href="%1$s"%2$s%3$s>%4$s</a>',
                esc_url($item->url),
                !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : ((!empty($settings['link_target']) && in_array($settings['link_target'], ['_blank', '_self'], true)) ? ' target="' . esc_attr($settings['link_target']) . '"' : ''),
                !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '',
                esc_html($item->title)
            );

            if (!empty($item->children)) {
                $html .= $this->render_submenu_toggle_markup($settings);

                $animation_class = esc_attr($this->get_submenu_animation_class($settings));
                $submenu_inner = '';

                if ($this->is_offcanvas_drilldown_mode($settings)) {
                    $submenu_inner .= sprintf(
                        '<li class="ldjem-offcanvas-drill-back"><button type="button" class="ldjem-offcanvas-drill-back-btn" aria-label="%1$s"><span class="ldjem-offcanvas-drill-back-icon" aria-hidden="true"></span><span class="ldjem-offcanvas-drill-back-label">%2$s</span></button></li>',
                        esc_attr(
                            sprintf(
                                /* translators: %s: parent menu item title */
                                __('Back to %s', 'lancedesk-responsive-menu-for-elementor'),
                                $item->title
                            )
                        ),
                        esc_html($item->title)
                    );
                }

                $submenu_inner .= $this->render_offcanvas_menu_items($item->children, $level + 1, $settings);

                $html .= sprintf(
                    '<ul class="ldjem-offcanvas-submenu ldjem-offcanvas-submenu-level-%1$d%2$s" aria-hidden="true">%3$s</ul>',
                    intval($level + 1),
                    $animation_class ? ' ' . $animation_class : '',
                    LDJEM_Security::sanitize_menu_markup($submenu_inner)
                );
            }

            $html .= '</li>';
        }

        return $html;
    }

    /**
     * Short label for social platform icon.
     *
     * @param string $platform Platform key.
     * @return string
     */
    private function get_social_label($platform) {
        $labels = [
            'facebook'  => 'F',
            'twitter'   => 'X',
            'linkedin'  => 'in',
            'instagram' => 'IG',
            'youtube'   => 'YT',
            'tiktok'    => 'TT',
        ];

        return isset($labels[$platform]) ? $labels[$platform] : strtoupper(substr($platform, 0, 2));
    }

    /**
     * Resolve hamburger alignment for standard or off-canvas toggle.
     *
     * @param array $settings Widget settings.
     * @param bool  $for_offcanvas Whether the toggle is for off-canvas mode.
     * @return string left|center|right
     */
    private function resolve_hamburger_position($settings, $for_offcanvas = false) {
        if ($for_offcanvas) {
            $position = sanitize_key($settings['offcanvas_toggle_alignment'] ?? '');
            if ($position === '' || !in_array($position, ['left', 'center', 'right'], true)) {
                $position = sanitize_key($settings['mobile_hamburger_position'] ?? 'left');
            }
        } else {
            $position = sanitize_key($settings['mobile_hamburger_position'] ?? 'left');
        }

        if (!in_array($position, ['left', 'center', 'right'], true)) {
            $position = 'left';
        }

        return $position;
    }

    /**
     * Render hamburger menu button
     * 
     * @param array $settings Widget settings
     * @param bool  $for_offcanvas Whether rendering the off-canvas toggle.
     * @return void
     */
    private function render_hamburger_menu($settings, $for_offcanvas = false) {
        $position = $this->resolve_hamburger_position($settings, $for_offcanvas);

        $has_custom_icon = !empty($settings['hamburger_icon']) && !empty($settings['hamburger_icon']['value']);
        $button_classes = 'ldjem-hamburger ldjem-hamburger-btn ldjem-hamburger-' . esc_attr($position);
        if ($has_custom_icon) {
            $button_classes .= ' has-custom-icon';
        }

        echo '<button class="' . esc_attr($button_classes) . '" type="button" aria-label="' . esc_attr__('Toggle Menu', 'lancedesk-responsive-menu-for-elementor') . '" aria-expanded="false">';
        if ($has_custom_icon) {
            Icons_Manager::render_icon($settings['hamburger_icon'], ['aria-hidden' => 'true']);
        }
        echo '<span class="ldjem-hamburger-fallback-bar" aria-hidden="true"></span><span class="ldjem-hamburger-fallback-bar" aria-hidden="true"></span><span class="ldjem-hamburger-fallback-bar" aria-hidden="true"></span>';
        echo '</button>';
    }

    /**
     * Recursively render menu items
     * 
     * @param array  $items Menu items to render
     * @param int    $level Current nesting level
     * @param array  $settings Widget settings
     * @return string HTML of menu items
     */
    private function render_menu_items($items, $level = 0, $settings = []) {
        if (empty($items)) {
            return '';
        }

        $html = '';

        foreach ($items as $item) {
            $classes = ['ldjem-menu-item'];
            $item_classes = isset($item->classes) && is_array($item->classes) ? $item->classes : [];
            $item_classes = array_filter(array_map('sanitize_html_class', $item_classes));
            if (!empty($item_classes)) {
                $classes = array_merge($classes, $item_classes);
            }

            // Add active class if current page
            if (!empty($settings['mark_active_item']) && 'yes' === $settings['mark_active_item']) {
                if (!empty($item->current)) {
                    $classes[] = 'current-menu-item';
                }
                if (!empty($item->current_item_ancestor)) {
                    $classes[] = 'current-menu-ancestor';
                }
            }

            // Add parent class if has children
            if (!empty($item->children)) {
                $classes[] = 'ldjem-menu-item-parent';
            }

            // Add level class
            $classes[] = 'ldjem-menu-level-' . $level;

            // Render menu item
            $html .= sprintf(
                '<li class="%1$s"><a href="%2$s"%3$s%4$s>%5$s</a>',
                esc_attr(implode(' ', $classes)),
                LDJEM_Security::escape_url($item->url),
                !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : ((!empty($settings['link_target']) && in_array($settings['link_target'], ['_blank', '_self'], true)) ? ' target="' . esc_attr($settings['link_target']) . '"' : ''),
                !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '',
                LDJEM_Security::escape_html($item->title)
            );

            // Render submenu if has children
            if (!empty($item->children)) {
                $html .= $this->render_submenu_toggle_markup($settings);

                $animation_class = esc_attr($this->get_submenu_animation_class($settings));
                $html .= sprintf(
                    '<ul class="ldjem-submenu ldjem-submenu-level-%d%s">%s</ul>',
                    $level + 1,
                    $animation_class ? ' ' . $animation_class : '',
                    LDJEM_Security::sanitize_menu_markup($this->render_menu_items($item->children, $level + 1, $settings))
                );
            }

            $html .= '</li>';
        }

        return $html;
    }

    /**
     * Render widget in plain text (for RSS, etc)
     * 
     * @return void
     */
    public function render_plain_content() {
        echo esc_html__('LanceDesk Responsive Menu', 'lancedesk-responsive-menu-for-elementor');
    }

    /**
     * Get script dependencies
     * 
     * @return array Script handles to load before widget
     */
    public function get_script_depends() {
        return [LDJEM_PREFIX . '-frontend', LDJEM_PREFIX . '-offcanvas'];
    }

    /**
     * Get style dependencies
     * 
     * @return array Style handles to load before widget
     */
    public function get_style_depends() {
        return [LDJEM_PREFIX . '-frontend', LDJEM_PREFIX . '-offcanvas'];
    }
}
