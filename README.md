# LanceDesk Responsive Menu for Elementor

Responsive Elementor menu widget for WordPress with per-device layout controls, flexible submenu triggers, and cleaner mobile navigation behavior.

![Version](https://img.shields.io/badge/version-1.0.13-blue)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)
![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue)
![PHP](https://img.shields.io/badge/php-7.4%2B-blue)
![Elementor](https://img.shields.io/badge/elementor-3.0%2B-blue)

![LanceDesk Responsive Menu for Elementor](.github/readme-banner.png)

## Why This Plugin

Most Elementor menu workflows require duplicated widgets and breakpoint visibility hacks.
LanceDesk Responsive Menu for Elementor is designed for modern WordPress builds where you want one menu widget that adapts cleanly across desktop, tablet, and mobile.

## Key Features

- Single widget instance for responsive WordPress navigation menus
- Device-aware layout control: horizontal, vertical, grid
- Submenu trigger modes: `Hover`, `Click`, `Hover & Click`
- Off-canvas submenu styles: **Drill-down** (push panels) or Accordion
- Optional accordion behavior for nested submenu UX
- Off-canvas support with device-level enable/disable rules
- Device-specific menu selection for both standard and off-canvas modes
- Submenu spacing, border, and nested vertical styling controls
- Improved Elementor editor preview parity for mobile/off-canvas interactions
- Accessibility-focused ARIA and keyboard interaction support

## Why It Matters

This plugin is built for WordPress + Elementor navigation workflows.

- Build one responsive menu instead of duplicating multiple widgets per breakpoint
- Keep navigation behavior consistent across desktop, tablet, and mobile
- Improve usability with configurable submenu triggers and accordion behavior
- Reduce maintenance overhead when updating menus and template layouts

Clear, consistent navigation improves user experience and can support better engagement across your site.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Elementor 3.0+

## Installation

1. Upload the plugin folder to `/wp-content/plugins/lancedesk-responsive-menu-for-elementor/` or install the zip in wp-admin.
2. Activate the plugin.
3. Open Elementor and drag `LanceDesk Responsive Menu` into your layout.
4. Pick a WordPress menu and configure responsive layout behavior.

## Changelog

### 1.0.13

- Add off-canvas **Drill-down** submenu mode (push panels with Back)—default for new off-canvas menus
- Keep Accordion as an optional off-canvas submenu style
- Accordion mode now scrolls opened items into view when near the bottom of the drawer
- Escape key steps back one drill level before closing the drawer

### 1.0.12

- Add **Toggle Alignment** control (left, center, right) under Content > Off-Canvas Menu > Toggle
- Fix panel background no longer bleeding onto the off-canvas hamburger row wrapper
- Remove Off-Canvas Presets section and unused preset infrastructure

### 1.0.11

- Reorganize widget controls: new **Display Mode** section at the top with per-device Standard Menu / Off-Canvas selectors
- Hide Off-Canvas content and style controls when no device uses off-canvas
- Fix close button not rendering unless the off-canvas header was enabled
- Fix standard horizontal menu still appearing on the frontend when all devices use off-canvas
- Fix off-canvas toggle visibility on desktop frontend (CSS no longer requires JavaScript classes)
- Fix panel background and close color applying reliably via inline styles and broader selectors
- Fix Elementor editor close button not dismissing the preview panel
- Fix panel colors when Elementor uses rgb/rgba or CSS variable values
- Fix panel background incorrectly painting the hamburger row wrapper on the frontend
- Declare WordPress 7.0 compatibility (`Tested up to: 7.0`)

### 1.0.10

- Add close button type toggle: Elementor icon or custom letter/character
- Fix close button style controls updating live in the Elementor editor (size, radius, border, weight, stroke)
- Fix close icon not previewing live in the editor; keep the off-canvas panel open when changing icons
- Fix mobile off-canvas styling overrides for menu links, separators, and submenu colors
- Improve editor icon sync via preview iframe bridge for toggle and close icons

### 1.0.9

- Reorganize off-canvas styling under **Style > Off Canvas** and migrate the close icon to the Elementor Icons library (with legacy fallback)
- Align **Style > Submenus** controls with off-canvas nested menus (toggle icon, spacing, colors, accordion, animation)
- Fix off-canvas submenu chevrons not appearing (menu sanitization was stripping icon markup)
- Fix off-canvas submenu toggles not expanding (standard menu click handler no longer blocks off-canvas)
- Fix hamburger button staying visible over the open off-canvas panel
- Add close icon weight and stroke width styling controls; lighter default close character

### 1.0.8

- Add optional off-canvas dark-mode logo with selectable trigger source (`hb-a11y-dark`, system preference, or auto)
- Add responsive off-canvas logo controls for width, max width, height, padding, and margin
- Add responsive hamburger margin control for mobile menu positioning

### 1.0.7

- Preserve WordPress menu item CSS classes on frontend `<li>` output for standard and off-canvas renderers
- Respect per-menu-item link target (`target`) from WordPress menu settings instead of forcing widget-level target only
- Include menu item relationship (`rel`/XFN) values when set in WordPress menu item options

### 1.0.6

- Renamed plugin branding and slug/text domain to `lancedesk-responsive-menu-for-elementor` for WordPress.org trademark compliance
- Replaced inline debug script output with `wp_add_inline_script()` on the frontend handle
- Hardened rendered menu HTML output escaping in standard and off-canvas render paths

### 1.0.5

- Added translators comments for placeholder-based i18n strings
- Hardened request action sanitization and cleanup routines for Plugin Check compliance
- Removed discouraged debug/textdomain patterns and aligned metadata for WordPress.org scanning

### 1.0.4

- Fixed duplicate Elementor control registration for menu underline controls
- Fixed missing `mobile_hamburger_position` setting fallback to avoid PHP warnings
- Reduced noisy PHP notices in widget render/control initialization paths

### 1.0.3

- Added device-specific menu ID mapping for standard and off-canvas render variants
- Improved off-canvas behavior in Elementor editor preview (toggle/open/close reliability)
- Fixed hamburger alignment control responsiveness in editor preview
- Improved editor/runtime device sync for desktop, tablet, and mobile states
- Expanded debug tooling for support diagnostics while keeping debug mode opt-in

### 1.0.1

- Improved submenu interaction behavior for click and hover/click modes
- Added configurable submenu accordion behavior
- Added submenu spacing and border customization controls
- Improved editor/frontend consistency for submenu behavior
- Added WordPress.org-compatible `readme.txt`

### 1.0.0

- Initial release

## License

GPL-2.0-or-later. See `LICENSE`.
