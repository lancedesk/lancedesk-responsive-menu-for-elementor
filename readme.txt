=== LanceDesk Responsive Menu for Elementor ===
Contributors: lancedesk
Tags: elementor, menu, responsive menu, mobile menu, navigation
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.13
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build responsive Elementor navigation menus with one widget. Control desktop, tablet, and mobile layouts without duplicate menu widgets.

== Description ==

LanceDesk Responsive Menu for Elementor is a WordPress plugin for Elementor that helps you build flexible, responsive navigation menus using a single widget instance.

Instead of duplicating multiple menu widgets and hiding them per breakpoint, you can configure one menu for desktop, tablet, and mobile behavior directly inside Elementor.

= Why this plugin =

* One Elementor menu widget across devices
* Device-specific layout controls (desktop/tablet/mobile)
* Submenu trigger options: Hover, Click, Hover & Click
* Off-canvas submenu styles: Drill-down (push panels) or Accordion
* Optional accordion behavior for cleaner submenu interaction
* Off-canvas support with responsive controls and editor-preview reliability
* Device-specific menu selection for standard and off-canvas modes
* Accessibility-focused markup and keyboard support
* WordPress coding standards and GPL-compatible licensing

= Ideal for =

* Business websites using Elementor
* Landing pages needing custom responsive navigation
* Agencies building reusable Elementor workflows
* Sites where mobile navigation usability is critical

== Installation ==

1. Upload the `lancedesk-responsive-menu-for-elementor` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Ensure Elementor is installed and activated.
4. Edit a page with Elementor and search for `LanceDesk Responsive Menu`.
5. Select your WordPress menu and configure responsive settings.

== Frequently Asked Questions ==

= Does this plugin require Elementor? =

Yes. Elementor must be active for this widget to appear.

= Can I use different menu behaviors on desktop and mobile? =

Yes. You can configure responsive layout and submenu behavior for different devices.

= Can I use both hover and click for submenus? =

Yes. Choose `Hover & Click` in the submenu trigger setting.

= Is this plugin accessible? =

The widget includes keyboard navigation support and ARIA attributes for submenu controls.

== Screenshots ==

1. Elementor widget panel for LanceDesk Responsive Menu.
2. Desktop navigation with submenu controls.
3. Tablet responsive layout example.
4. Mobile/off-canvas menu interaction.
5. Submenu styling and trigger options.

== Changelog ==

= 1.0.13 =
* Add off-canvas Drill-down submenu mode (push panels with Back)—default for new off-canvas menus.
* Keep Accordion as an optional off-canvas submenu style.
* Accordion mode now scrolls opened items into view when near the bottom of the drawer.
* Escape key steps back one drill level before closing the drawer.

= 1.0.12 =
* Add Toggle Alignment control (left, center, right) under Content > Off-Canvas Menu > Toggle.
* Fix panel background no longer bleeding onto the off-canvas hamburger row wrapper.
* Remove Off-Canvas Presets section and unused preset infrastructure.

= 1.0.11 =
* Reorganize widget controls: new Display Mode section at the top with per-device Standard Menu / Off-Canvas selectors.
* Hide Off-Canvas content and style controls when no device uses off-canvas.
* Fix close button not rendering unless the off-canvas header was enabled.
* Fix standard horizontal menu still appearing on the frontend when all devices use off-canvas.
* Fix off-canvas toggle visibility on desktop frontend (CSS no longer requires JavaScript classes).
* Fix panel background and close color applying reliably via inline styles and broader selectors.
* Fix Elementor editor close button not dismissing the preview panel.
* Fix panel colors when Elementor uses rgb/rgba or CSS variable values.
* Fix panel background incorrectly painting the hamburger row wrapper on the frontend.
* Declare WordPress 7.0 compatibility (Tested up to: 7.0).

= 1.0.10 =
* Add close button type toggle: Elementor icon or custom letter/character.
* Fix close button style controls updating live in the Elementor editor (size, radius, border, weight, stroke).
* Fix close icon not previewing live in the editor; keep the off-canvas panel open when changing icons.
* Fix mobile off-canvas styling overrides for menu links, separators, and submenu colors.
* Improve editor icon sync via preview iframe bridge for toggle and close icons.

= 1.0.9 =
* Reorganize off-canvas styling under Style > Off Canvas and migrate the close icon to the Elementor Icons library (with legacy fallback).
* Align Style > Submenus controls with off-canvas nested menus: toggle icon, spacing, colors, accordion, and animation.
* Fix off-canvas submenu chevrons not appearing (menu sanitization was stripping icon markup).
* Fix off-canvas submenu toggles not expanding (standard menu click handler no longer blocks off-canvas).
* Fix hamburger button staying visible over the open off-canvas panel.
* Add close icon weight and stroke width styling controls; use a lighter default close character.

= 1.0.8 =
* Add optional off-canvas dark-mode logo with selectable trigger source: accessibility class, system preference, or auto.
* Add responsive off-canvas logo controls for width, max width, height, padding, and margin.
* Add responsive hamburger margin control for mobile menu placement.

= 1.0.7 =
* Preserve WordPress menu item CSS classes on frontend `<li>` output for standard and off-canvas renderers.
* Respect per-menu-item link target (`target`) from WordPress menu settings instead of forcing widget-level target only.
* Include menu item relationship (`rel`/XFN) values when set in WordPress menu item options.

= 1.0.6 =
* Renamed plugin branding and slug/text domain to `lancedesk-responsive-menu-for-elementor` for WordPress.org trademark compliance.
* Replaced inline debug script output with `wp_add_inline_script()` on the frontend handle.
* Hardened rendered menu HTML output escaping in standard and off-canvas paths.

= 1.0.5 =
* Added translators comments for placeholder-based i18n strings.
* Hardened request action sanitization and cleanup routines for Plugin Check compliance.
* Removed discouraged debug/textdomain patterns and aligned metadata for WordPress.org scanning.

= 1.0.4 =
* Fixed duplicate Elementor control declarations that triggered control redeclare notices.
* Added safe fallback for missing `mobile_hamburger_position` widget setting.
* Reduced PHP warning/notice noise in menu widget render paths.

= 1.0.3 =
* Added device-specific menu selection mapping for standard and off-canvas output.
* Improved Elementor editor behavior for off-canvas toggle/open/close interactions.
* Fixed hamburger alignment control responsiveness in editor preview.
* Improved device-mode synchronization across desktop/tablet/mobile previews.
* Added richer opt-in debug diagnostics for support and QA workflows.

= 1.0.1 =
* Improved submenu interaction logic for click and hover/click modes.
* Added submenu accordion behavior control.
* Added submenu spacing and border styling controls.
* Improved editor/frontend behavior consistency.
* Added WordPress.org readme and publishing metadata updates.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.13 =
Recommended update: off-canvas Drill-down submenus keep nested items visible near the bottom of the drawer.

= 1.0.12 =
Adds off-canvas toggle alignment control and removes the non-functional presets section.

= 1.0.11 =
Clearer Display Mode controls per device, smarter off-canvas field visibility, and WordPress 7.0 compatibility.

= 1.0.10 =
Recommended update for close button icon/letter controls, live editor preview fixes, and mobile off-canvas styling.

= 1.0.9 =
Recommended update for off-canvas submenu toggles, Style > Off Canvas reorganization, and close icon styling controls.

= 1.0.8 =
Recommended update for dual light/dark off-canvas logos and responsive logo/hamburger spacing controls.

= 1.0.7 =
Recommended update to keep WordPress menu item classes and link attributes (target/rel) in frontend output.

= 1.0.6 =
Recommended trademark-compliance and security-hardening update for WordPress.org review approval.

= 1.0.5 =
Recommended compliance update to satisfy WordPress.org Plugin Check and submission scanner requirements.

= 1.0.4 =
Recommended maintenance update to eliminate Elementor control redeclare notices and undefined setting warnings.

= 1.0.3 =
Recommended update for stable mobile/off-canvas editor previews and device-specific menu selection support.

= 1.0.1 =
Recommended update with submenu behavior improvements and new styling controls.
