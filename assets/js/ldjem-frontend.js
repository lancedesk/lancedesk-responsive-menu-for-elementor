/**
 * LanceDesk Elementor Menu – Frontend JavaScript
 * 
 * Mobile menu interaction, responsive behavior, and accessibility
 * Handles hamburger toggle, submenu expansion, keyboard navigation
 * 
 * @package LDJEM
 */

(function ($) {
    'use strict';

    window.__LDJEM_BUILD = 'ldjem-frontend-2026-04-29-0154';

    /**
     * LanceDeskMenu - Main menu controller
     */
    var LanceDeskMenu = {
        settings: {
            menuSelector: '.ldjem-menu-wrapper',
            menuRootSelector: '.ldjem-menu-root',
            hamburgerSelector: '.ldjem-hamburger',
            overlaySelector: '.ldjem-menu-overlay',
            submenuSelector: '.ldjem-submenu',
            menuItemSelector: '.ldjem-menu-item',
            menuItemParentClass: 'ldjem-menu-item-parent',
            menuOpenClass: 'is-open',
            menuExpandedClass: 'is-expanded',
            bodyOpenClass: 'ldjem-menu-open',
            mobileBreakpoint: 768,
            focusTrapEnabled: true,
        },

        /**
         * Initialize menu controller
         */
        init: function () {
            this.cacheSelectors();
            if (!this.$wrapper.length) {
                return;
            }
            this.bindEvents();
            this.setupAccessibility();
            this.debugLayoutState('init');
            this.applyResponsiveLayout('init');
        },

        /**
         * Cache jQuery selectors
         */
        cacheSelectors: function () {
            this.$wrapper = $(this.settings.menuSelector + '[data-ldjem-id]').not('[data-ldjem-offcanvas="true"]');
            this.$menu = this.$wrapper.find(this.settings.menuRootSelector);
            this.$hamburger = this.$wrapper.find(this.settings.hamburgerSelector);
            this.$overlay = this.$wrapper.find(this.settings.overlaySelector);
            this.$menuItems = this.$wrapper.find(this.settings.menuItemSelector);
            this.$submenuParents = this.$wrapper.find('.' + this.settings.menuItemParentClass);
            this.$submenuToggles = this.$wrapper.find('.ldjem-submenu-toggle');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            var self = this;

            // Hamburger click
            this.$hamburger.on('click', function (e) {
                e.preventDefault();
                self.toggleMenu();
            });

            // Overlay click
            this.$overlay.on('click', function () {
                self.closeMenu();
            });

            // Submenu toggle button click (desktop/tablet/mobile)
            this.$wrapper.on('click', '.ldjem-submenu-toggle', function (e) {
                e.preventDefault();
                var $toggle = $(this);
                var $parent = $toggle.closest('.' + self.settings.menuItemParentClass);
                var $localWrapper = $toggle.closest('.ldjem-menu-wrapper');
                var isExpanded = $parent.hasClass(self.settings.menuExpandedClass);
                var triggerMode = $localWrapper.attr('data-submenu-trigger') || 'hover';
                var isAccordion = ($localWrapper.attr('data-submenu-accordion') || 'yes') === 'yes';

                // Accordion behavior: opening one item closes siblings at same level.
                if (isAccordion && !isExpanded) {
                    var $siblings = $parent.siblings('.' + self.settings.menuItemParentClass + '.' + self.settings.menuExpandedClass);
                    $siblings.removeClass(self.settings.menuExpandedClass);
                    $siblings.find('> .ldjem-submenu-toggle').attr('aria-expanded', 'false');
                    $siblings.find('> a').attr('aria-expanded', 'false');
                }

                if (isExpanded) {
                    $parent.removeClass(self.settings.menuExpandedClass);
                    $toggle.attr('aria-expanded', 'false');
                    $parent.find('> a').attr('aria-expanded', 'false');
                } else {
                    $parent.addClass(self.settings.menuExpandedClass);
                    $toggle.attr('aria-expanded', 'true');
                    $parent.find('> a').attr('aria-expanded', 'true');
                }

            });

            // Close menu on regular link click
            this.$menu.on('click', 'a:not(.' + self.settings.menuItemParentClass + ' a)', function () {
                if (self.isMobile()) {
                    self.closeMenu();
                }
            });

            // Keyboard events
            $(document).on('keydown', function (e) {
                // Escape key closes menu
                if (e.keyCode === 27) {
                    self.closeMenu();
                }

                // Arrow keys for navigation
                if (e.keyCode === 39 || e.keyCode === 40) {
                    // Right/Down arrow
                    self.focusNextMenuItem();
                }
                if (e.keyCode === 37 || e.keyCode === 38) {
                    // Left/Up arrow
                    self.focusPreviousMenuItem();
                }
            });

            // Window resize - handle breakpoint changes
            $(window).on('resize', function () {
                self.handleResize();
                self.debugLayoutState('resize');
                self.applyResponsiveLayout('resize');
            });
        },

        /**
         * Determine current preview device.
         */
        getCurrentDevice: function () {
            var topDoc = null;
            try {
                topDoc = window.top && window.top.document ? window.top.document : null;
            } catch (e) {
                topDoc = null;
            }

            var hasClassToken = function (docRef, token) {
                if (!docRef || !docRef.documentElement || !docRef.documentElement.classList) {
                    return false;
                }
                if (docRef.documentElement.classList.contains(token)) {
                    return true;
                }
                return !!(docRef.body && docRef.body.classList && docRef.body.classList.contains(token));
            };

            var hasMobileClass =
                hasClassToken(document, 'elementor-device-mobile') ||
                hasClassToken(document, 'elementor-editor-device-mobile') ||
                hasClassToken(topDoc, 'elementor-device-mobile') ||
                hasClassToken(topDoc, 'elementor-editor-device-mobile');

            var hasTabletClass =
                hasClassToken(document, 'elementor-device-tablet') ||
                hasClassToken(document, 'elementor-editor-device-tablet') ||
                hasClassToken(topDoc, 'elementor-device-tablet') ||
                hasClassToken(topDoc, 'elementor-editor-device-tablet');

            if (window.elementorFrontend && typeof window.elementorFrontend.getCurrentDeviceMode === 'function') {
                var mode = window.elementorFrontend.getCurrentDeviceMode();
                if (mode === 'mobile' || mode === 'tablet' || mode === 'desktop') {
                    return mode;
                }
            }

            if (hasMobileClass) {
                return 'mobile';
            }
            if (hasTabletClass) {
                return 'tablet';
            }

            var width = window.innerWidth || document.documentElement.clientWidth;
            if (width <= 767) {
                return 'mobile';
            }
            if (width <= 1024) {
                return 'tablet';
            }
            return 'desktop';
        },

        /**
         * Force selected responsive layout on menu root.
         */
        applyResponsiveLayout: function (context) {
            var self = this;
            this.$wrapper.each(function () {
                var $wrapper = $(this);
                var $menu = $wrapper.find('.ldjem-menu').first();
                if (!$menu.length) {
                    return;
                }

                var device = self.getCurrentDevice();
                var layout = $wrapper.attr('data-' + device + '-layout') || 'horizontal';

                // Reset previously forced inline layout properties first.
                $menu.css({
                    display: '',
                    flexDirection: '',
                    alignItems: '',
                    width: '',
                    flexWrap: '',
                    overflowX: '',
                    gridTemplateColumns: ''
                });

                if (layout === 'vertical') {
                    $menu.css({
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'flex-start',
                        width: '100%',
                        flexWrap: 'nowrap',
                        overflowX: 'hidden'
                    });
                } else if (layout === 'grid') {
                    $menu.css({
                        display: 'grid',
                        gridTemplateColumns: device === 'desktop' ? 'repeat(auto-fit, minmax(160px, 1fr))' : 'repeat(2, minmax(120px, 1fr))',
                        width: '100%'
                    });
                } else {
                    $menu.css({
                        display: 'flex',
                        flexDirection: 'row',
                        alignItems: 'center',
                        width: 'auto'
                    });
                }

                self.syncDeviceMenuTemplate($wrapper, device);

            });
        },

        /**
         * Swap menu HTML from hidden per-device templates.
         */
        syncDeviceMenuTemplate: function ($wrapper, device) {
            var $menuRoot = $wrapper.find('.ldjem-menu.ldjem-menu-root').first();
            if (!$menuRoot.length) {
                return;
            }

            var variant = 'standard-' + device;
            var $template = $wrapper.find('.ldjem-menu-device-templates [data-ldjem-menu-variant="' + variant + '"]').first();
            if (!$template.length) {
                return;
            }

            var html = $template.html() || '';
            if ($menuRoot.data('ldjemRenderedVariant') === variant && $menuRoot.data('ldjemRenderedHtml') === html) {
                return;
            }

            $menuRoot.html(html);
            $menuRoot.data('ldjemRenderedVariant', variant);
            $menuRoot.data('ldjemRenderedHtml', html);
            $wrapper.attr('data-active-standard-menu-variant', variant);
            $wrapper.attr('data-active-standard-menu-id', $template.attr('data-menu-id') || '');
        },

        /**
         * Debug active responsive layout decisions.
         */
        debugLayoutState: function (context) {
            this.$wrapper.each(function () {
                var $wrapper = $(this);
                var width = window.innerWidth || document.documentElement.clientWidth;
                var activeDevice = width <= 767 ? 'mobile' : (width <= 1024 ? 'tablet' : 'desktop');
                var selectedLayout = $wrapper.attr('data-' + activeDevice + '-layout') || 'horizontal';
                var $menu = $wrapper.find('.ldjem-menu').first();

                if (!$menu.length) {
                    return;
                }

                var computed = window.getComputedStyle($menu.get(0));
            });
        },

        /**
         * Toggle menu open/close
         */
        toggleMenu: function () {
            if (this.$menu.hasClass(this.settings.menuOpenClass)) {
                this.closeMenu();
            } else {
                this.openMenu();
            }
        },

        /**
         * Open menu
         */
        openMenu: function () {
            if (!this.isMobile()) {
                return;
            }

            this.$menu.addClass(this.settings.menuOpenClass);
            this.$hamburger.addClass(this.settings.menuOpenClass);
            this.$overlay.addClass(this.settings.menuOpenClass);
            $('body').addClass(this.settings.bodyOpenClass);

            // Update hamburger ARIA
            this.$hamburger.attr('aria-expanded', 'true');

            // Dispatch event
            this.$wrapper.trigger('ldjem:menu:opened');
        },

        /**
         * Close menu
         */
        closeMenu: function () {
            this.$menu.removeClass(this.settings.menuOpenClass);
            this.$hamburger.removeClass(this.settings.menuOpenClass);
            this.$overlay.removeClass(this.settings.menuOpenClass);
            $('body').removeClass(this.settings.bodyOpenClass);

            // Update hamburger ARIA
            this.$hamburger.attr('aria-expanded', 'false');

            // Collapse all submenus
            this.$submenuParents.removeClass(this.settings.menuExpandedClass);

            // Dispatch event
            this.$wrapper.trigger('ldjem:menu:closed');
        },

        /**
         * Check if viewport is mobile
         */
        isMobile: function () {
            if (this.getCurrentDevice() === 'mobile') {
                return true;
            }
            if (this.getCurrentDevice() === 'tablet') {
                return false;
            }
            if (document.body.classList.contains('elementor-device-mobile')) {
                return true;
            }
            if (document.body.classList.contains('elementor-device-tablet')) {
                return false;
            }
            return $(window).width() <= this.settings.mobileBreakpoint;
        },

        /**
         * Handle window resize - close menu if resized to desktop
         */
        handleResize: function () {
            if (!this.isMobile() && this.$menu.hasClass(this.settings.menuOpenClass)) {
                this.closeMenu();
            }
        },

        /**
         * Setup accessibility features
         */
        setupAccessibility: function () {
            var self = this;

            // Add ARIA attributes to parent items
            this.$submenuParents.each(function () {
                var $link = $(this).find('> a');
                $link.attr('aria-expanded', 'false');
                $link.attr('aria-haspopup', 'true');
            });

            this.$submenuToggles.each(function () {
                $(this).attr('aria-expanded', 'false');
                $(this).attr('aria-haspopup', 'true');
            });

            // Add ARIA to submenus
            this.$menu.find(this.settings.submenuSelector).attr('role', 'menu');

            // Focus trap on mobile (if enabled)
            if (this.settings.focusTrapEnabled) {
                this.setupFocusTrap();
            }
        },

        /**
         * Setup focus trap for mobile menu
         */
        setupFocusTrap: function () {
            var self = this;

            this.$wrapper.on('keydown', function (e) {
                if (e.keyCode !== 9 || !self.isMobile()) {
                    return;
                }

                var $focusable = $(self.settings.menuSelector + ' a, ' + self.settings.hamburgerSelector);
                var $firstFocusable = $focusable.first();
                var $lastFocusable = $focusable.last();
                var $focused = $(':focus');

                // Shift + Tab on first element - move to last
                if (e.shiftKey && $focused[0] === $firstFocusable[0]) {
                    e.preventDefault();
                    $lastFocusable.focus();
                }
                // Tab on last element - move to first
                else if (!e.shiftKey && $focused[0] === $lastFocusable[0]) {
                    e.preventDefault();
                    $firstFocusable.focus();
                }
            });
        },

        /**
         * Focus next menu item
         */
        focusNextMenuItem: function () {
            var $focused = $(':focus');
            var $menuItems = this.$wrapper.find(this.settings.menuItemSelector + ' a');
            var currentIndex = $menuItems.index($focused);
            var nextIndex = currentIndex + 1;

            if (nextIndex >= $menuItems.length) {
                nextIndex = 0;
            }

            $menuItems.eq(nextIndex).focus();
        },

        /**
         * Focus previous menu item
         */
        focusPreviousMenuItem: function () {
            var $focused = $(':focus');
            var $menuItems = this.$wrapper.find(this.settings.menuItemSelector + ' a');
            var currentIndex = $menuItems.index($focused);
            var prevIndex = currentIndex - 1;

            if (prevIndex < 0) {
                prevIndex = $menuItems.length - 1;
            }

            $menuItems.eq(prevIndex).focus();
        },

        /**
         * Update menu layout based on window size
         */
        updateLayout: function () {
            if (this.isMobile()) {
                this.$menu.addClass('ldjem-menu-mobile');
                this.$menu.removeClass('ldjem-menu-desktop');
            } else {
                this.$menu.addClass('ldjem-menu-desktop');
                this.$menu.removeClass('ldjem-menu-mobile');
                this.closeMenu(); // Ensure menu is closed on desktop
            }
        },

        /**
         * Reinitialize menu (useful after content updates)
         */
        reinit: function () {
            this.cacheSelectors();
            this.bindEvents();
            this.setupAccessibility();
        },
    };

    /**
     * jQuery Plugin wrapper
     */
    $.fn.ldjemMenu = function (method) {
        return this.each(function () {
            if (!$(this).data('ldjemMenu')) {
                LanceDeskMenu.init();
                $(this).data('ldjemMenu', LanceDeskMenu);
            }

            if (method && LanceDeskMenu[method]) {
                LanceDeskMenu[method]();
            }
        });
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function () {
        $('.ldjem-menu-wrapper').ldjemMenu();

        var ensureHamburgerFallback = function () {
            document.querySelectorAll('.ldjem-menu-wrapper[data-ldjem-id] .ldjem-hamburger.has-custom-icon').forEach(function (btn) {
                var iconEl = btn.querySelector('svg, i');
                var forceFallback = false;

                if (!iconEl) {
                    forceFallback = true;
                } else {
                    if (iconEl.tagName && iconEl.tagName.toLowerCase() === 'i') {
                        var pseudo = window.getComputedStyle(iconEl, '::before').content;
                        if (!pseudo || pseudo === 'none' || pseudo === 'normal' || pseudo === '""') {
                            forceFallback = true;
                        }
                    }
                }

                btn.classList.toggle('ldjem-hamburger-force-fallback', forceFallback);
            });
        };

        ensureHamburgerFallback();

        // Also support manual initialization
        if (typeof ldjemFrontend !== 'undefined') {
            // Available for custom scripts to hook into
            window.LanceDeskMenu = LanceDeskMenu;
        }

        // Hard delegated fallback for submenu toggles.
        // This still works when Elementor re-renders widgets after init.
        document.removeEventListener('click', window.__ldjemSubmenuFallbackHandler || function () {}, true);
        window.__ldjemSubmenuFallbackHandler = function (evt) {
            function isOffcanvasWrapper(wrapper) {
                if (!wrapper) {
                    return false;
                }
                return wrapper.classList.contains('ldjem-menu-wrapper-offcanvas') ||
                    wrapper.getAttribute('data-ldjem-offcanvas') === 'true';
            }

            // Close click-triggered submenus when clicking outside any widget.
            var wrapperTarget = evt.target.closest('.ldjem-menu-wrapper[data-ldjem-id]');
            if (!wrapperTarget) {
                document.querySelectorAll('.ldjem-menu-wrapper[data-submenu-trigger="click"] .ldjem-menu-item-parent.is-expanded, .ldjem-menu-wrapper[data-submenu-trigger="hover_click"] .ldjem-menu-item-parent.is-expanded').forEach(function (parent) {
                    parent.classList.remove('is-expanded');
                    var btn = parent.querySelector(':scope > .ldjem-submenu-toggle');
                    var link = parent.querySelector(':scope > a');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                    if (link) {
                        link.setAttribute('aria-expanded', 'false');
                    }
                });
                return;
            }

            var toggle = evt.target.closest('.ldjem-menu-wrapper[data-ldjem-id] .ldjem-submenu-toggle');
            var parentLinkClick = evt.target.closest('.ldjem-menu-wrapper[data-ldjem-id] .ldjem-menu-item-parent > a');
            if (!toggle && !parentLinkClick) {
                return;
            }

            var sourceEl = toggle || parentLinkClick;
            var wrapper = sourceEl.closest('.ldjem-menu-wrapper[data-ldjem-id]');
            if (!wrapper) {
                return;
            }

            // Off-canvas submenus are handled by ldjem-offcanvas.js.
            if (isOffcanvasWrapper(wrapper)) {
                return;
            }

            var triggerMode = wrapper.getAttribute('data-submenu-trigger') || 'hover';
            var clickEnabled = (triggerMode === 'click' || triggerMode === 'hover_click');
            var isAccordion = (wrapper.getAttribute('data-submenu-accordion') || 'yes') === 'yes';
            var isDesktop = (window.innerWidth || document.documentElement.clientWidth) > 1024;
            var shouldToggleFromLink = !!parentLinkClick && (clickEnabled || !isDesktop);

            // Always toggle when clicking icon button.
            // For parent-link clicks, toggle in click mode/mobile/tablet.
            if (!toggle && !shouldToggleFromLink) {
                return;
            }

            evt.preventDefault();
            evt.stopPropagation();

            var parent = sourceEl.closest('.ldjem-menu-item-parent');
            if (!parent) {
                return;
            }

            var isExpanded = parent.classList.contains('is-expanded');
            var parentLink = parent.querySelector(':scope > a');
            var siblings;

            if (isExpanded) {
                parent.classList.remove('is-expanded');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
                if (parentLink) {
                    parentLink.setAttribute('aria-expanded', 'false');
                }
            } else {
                // Accordion behavior: close expanded siblings on same nesting level.
                if (isAccordion) {
                    siblings = Array.prototype.filter.call(parent.parentElement ? parent.parentElement.children : [], function (child) {
                        return child !== parent && child.classList && child.classList.contains('ldjem-menu-item-parent') && child.classList.contains('is-expanded');
                    });
                    siblings.forEach(function (sibling) {
                        var siblingToggle = sibling.querySelector(':scope > .ldjem-submenu-toggle');
                        var siblingLink = sibling.querySelector(':scope > a');
                        sibling.classList.remove('is-expanded');
                        if (siblingToggle) {
                            siblingToggle.setAttribute('aria-expanded', 'false');
                        }
                        if (siblingLink) {
                            siblingLink.setAttribute('aria-expanded', 'false');
                        }
                    });
                }

                parent.classList.add('is-expanded');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                }
                if (parentLink) {
                    parentLink.setAttribute('aria-expanded', 'true');
                }
            }

        };
        document.addEventListener('click', window.__ldjemSubmenuFallbackHandler, true);

        // Elementor preview mode class toggles do not always emit window resize.
        // Re-apply layout when html/body class changes.
        var observer = new MutationObserver(function () {
            if (window.LanceDeskMenu) {
                window.LanceDeskMenu.cacheSelectors();
                window.LanceDeskMenu.applyResponsiveLayout('elementor-preview-toggle');
                window.LanceDeskMenu.debugLayoutState('elementor-preview-toggle');
                ensureHamburgerFallback();
            }
        });
        if (document.documentElement) {
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        }
        if (document.body) {
            observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        }
        try {
            if (window.top && window.top.document) {
                if (window.top.document.documentElement) {
                    observer.observe(window.top.document.documentElement, { attributes: true, attributeFilter: ['class'] });
                }
                if (window.top.document.body) {
                    observer.observe(window.top.document.body, { attributes: true, attributeFilter: ['class'] });
                }
            }
        } catch (e) {
            // Ignore cross-frame access errors.
        }

        // Fallback watcher: Elementor preview mode can change without reliable mutation events.
        var lastDeviceMode = null;
        setInterval(function () {
            if (!window.LanceDeskMenu) {
                return;
            }
            var device = window.LanceDeskMenu.getCurrentDevice();
            if (device !== lastDeviceMode) {
                lastDeviceMode = device;
                window.LanceDeskMenu.cacheSelectors();
                window.LanceDeskMenu.applyResponsiveLayout('device-poll');
                window.LanceDeskMenu.debugLayoutState('device-poll');
                ensureHamburgerFallback();
            }
        }, 700);
    });

    /**
     * Elementor Editor Preview support
     */
    if (typeof window.elementor !== 'undefined') {
        window.elementor.on('document:loaded', function () {
            $('.ldjem-menu-wrapper').ldjemMenu('reinit');
        });
    }

})(jQuery);

