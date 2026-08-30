/**
 * LanceDesk Elementor Menu - Off-Canvas (Slideout) Menu JavaScript
 * Premium feature: Beautiful off-canvas menu replacing WordPress fullwidth dropdowns
 * 
 * @package LDJEM
 * @since 1.0.0
 */

(function ($) {
  'use strict';

  window.__LDJEM_OFFCANVAS_BUILD = 'ldjem-offcanvas-2026-08-30-vvh-fill';

  function syncVisualViewportHeight(target) {
    var h = window.innerHeight || document.documentElement.clientHeight || 0;
    if (window.visualViewport && window.visualViewport.height) {
      h = Math.max(h, Math.round(window.visualViewport.height));
    }
    // Elementor mobile device mode: prefer the visible preview frame height.
    try {
      var frame = document.documentElement;
      if (
        frame &&
        (frame.classList.contains('elementor-editor-active') ||
          frame.classList.contains('elementor-device-mobile') ||
          (document.body && document.body.classList.contains('elementor-editor-active')))
      ) {
        var rectH = Math.round((document.documentElement && document.documentElement.clientHeight) || 0);
        if (rectH > 0) {
          h = Math.max(h, rectH);
        }
      }
    } catch (e) {
      /* ignore */
    }
    var value = (h > 0 ? h : 0) + 'px';
    var nodes = target ? [target] : document.querySelectorAll('.ldjem-offcanvas-wrapper');
    nodes.forEach(function (el) {
      if (el && el.style) {
        el.style.setProperty('--ldjem-vvh', value);
      }
    });
    if (document.documentElement && document.documentElement.style) {
      document.documentElement.style.setProperty('--ldjem-vvh', value);
    }
    return h;
  }

  function applyPanelFill($panel) {
    if (!$panel || !$panel.length) {
      return;
    }
    var el = $panel.get(0);
    var h = syncVisualViewportHeight(el);
    var offsetRaw = window.getComputedStyle(el).getPropertyValue('--ldjem-offcanvas-offset-top') || '0px';
    var offset = parseFloat(offsetRaw) || 0;
    var fill = Math.max(0, h - offset);
    if (fill > 0 && ($panel.hasClass('direction-left') || $panel.hasClass('direction-right'))) {
      el.style.setProperty('min-height', fill + 'px');
      el.style.setProperty('bottom', '0px');
      el.style.setProperty('height', 'auto');
    }
  }

  function getBreakpoint(name, fallback) {
    if (window.ldjemOffcanvas && window.ldjemOffcanvas.breakpoints && window.ldjemOffcanvas.breakpoints[name]) {
      return parseInt(window.ldjemOffcanvas.breakpoints[name], 10);
    }
    if (window.ldjemFrontend && window.ldjemFrontend.breakpoints && window.ldjemFrontend.breakpoints[name]) {
      return parseInt(window.ldjemFrontend.breakpoints[name], 10);
    }
    return fallback;
  }

  function resolveDeviceFromWidth(width) {
    const mobileMax = getBreakpoint('mobile', 767);
    const tabletMax = getBreakpoint('tablet', 1024);

    if (width <= mobileMax) {
      return 'mobile';
    }
    if (width <= tabletMax) {
      return 'tablet';
    }
    return 'desktop';
  }

  /**
   * Off-Canvas Menu Handler
   */
  window.LDJEMOffCanvas = window.LDJEMOffCanvas || {};
  const LDJEMOffCanvas = window.LDJEMOffCanvas;

  /**
   * Initialize off-canvas menu for a specific widget
   */
  LDJEMOffCanvas.init = function (widgetId, settings) {
    const self = this;
    const $hamburger = $(`[data-ldjem-id="${widgetId}"] .ldjem-hamburger-btn`);
    const $offcanvas = $(`[data-ldjem-id="${widgetId}"] .ldjem-offcanvas-wrapper`);
    const $closeBtn = $(`[data-ldjem-id="${widgetId}"] .ldjem-offcanvas-close`);
    const $menuItems = $(`[data-ldjem-id="${widgetId}"] .ldjem-offcanvas-menu-item`);
    const $wrapper = $(`[data-ldjem-id="${widgetId}"].ldjem-menu-wrapper-offcanvas`);
    let state = null;

    function emitDebug(stage, extra) {
      $(document).trigger('ldjem:offcanvas:debug', [{
        widgetId: widgetId,
        stage: stage,
        device: getCurrentDevice(),
        enabledForDevice: $wrapper.attr(`data-offcanvas-${getCurrentDevice()}`) === 'yes' ? 'yes' : 'no',
        hamburgerCount: $hamburger.length,
        offcanvasCount: $offcanvas.length,
        isOpen: state ? (state.isOpen ? 'yes' : 'no') : 'no',
        extra: extra || {}
      }]);
    }

    emitDebug('init-selectors');

    if (!$offcanvas.length) {
      emitDebug('init-no-offcanvas');
      return;
    }

    // Store state
    state = {
      isOpen: false,
      widgetId: widgetId,
      settings: settings || {},
      focusTrap: null,
      drillStack: [],
    };

    function logLayoutDebug(context, device, enabled) {
      return;
    }

    function isEditorActive() {
      return !!(
        (document.body && document.body.classList.contains('elementor-editor-active')) ||
        (document.documentElement && document.documentElement.classList.contains('elementor-editor-active'))
      );
    }

    function getCurrentDevice() {
      let topDoc = null;
      try {
        topDoc = window.top && window.top.document ? window.top.document : null;
      } catch (e) {
        topDoc = null;
      }

      function hasClassToken(docRef, token) {
        if (!docRef || !docRef.documentElement || !docRef.documentElement.classList) {
          return false;
        }
        if (docRef.documentElement.classList.contains(token)) {
          return true;
        }
        return !!(docRef.body && docRef.body.classList && docRef.body.classList.contains(token));
      }

      const hasMobileClass =
        hasClassToken(document, 'elementor-device-mobile') ||
        hasClassToken(document, 'elementor-editor-device-mobile') ||
        hasClassToken(topDoc, 'elementor-device-mobile') ||
        hasClassToken(topDoc, 'elementor-editor-device-mobile');

      const hasTabletClass =
        hasClassToken(document, 'elementor-device-tablet') ||
        hasClassToken(document, 'elementor-editor-device-tablet') ||
        hasClassToken(topDoc, 'elementor-device-tablet') ||
        hasClassToken(topDoc, 'elementor-editor-device-tablet');

      // Elementor editor preview mode is the source of truth when available.
      if (window.elementorFrontend && typeof window.elementorFrontend.getCurrentDeviceMode === 'function') {
        const mode = window.elementorFrontend.getCurrentDeviceMode();
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

      if (document.body.classList.contains('elementor-device-mobile')) {
        return 'mobile';
      }
      if (document.body.classList.contains('elementor-device-tablet')) {
        return 'tablet';
      }
      const width = window.innerWidth || document.documentElement.clientWidth;
      return resolveDeviceFromWidth(width);
    }

    function isOffcanvasEnabledForCurrentDevice() {
      const device = getCurrentDevice();
      const attr = $wrapper.attr(`data-offcanvas-${device}`);
      return attr === 'yes';
    }

    function syncDeviceState(context) {
      const device = getCurrentDevice();
      const enabled = isOffcanvasEnabledForCurrentDevice();
      const $standardWrapper = $(`[data-ldjem-id="${widgetId}"].ldjem-menu-wrapper`).not('.ldjem-menu-wrapper-offcanvas');
      const $localHamburger = $wrapper.find('.ldjem-hamburger');
      const $fallbackMenu = $wrapper.find('.ldjem-menu-fallback');
      $wrapper
        .removeClass('ldjem-device-desktop ldjem-device-tablet ldjem-device-mobile')
        .addClass(`ldjem-device-${device}`)
        .toggleClass('ldjem-offcanvas-disabled-device', !enabled)
        .toggleClass('ldjem-offcanvas-enabled-device', enabled);
      $standardWrapper
        .toggleClass('ldjem-offcanvas-hide-standard', enabled)
        .attr('data-offcanvas-active-device', enabled ? 'yes' : 'no');
      applyDeviceOverrides(device);
      syncOffcanvasMenuTemplate(device);
      logLayoutDebug(context || 'sync', device, enabled);

      // Keep preview-open marker aligned with real open state only.
      const showEditorPreview = isEditorActive() && enabled && !!state.isOpen;
      $wrapper.toggleClass('ldjem-editor-preview-open', showEditorPreview);
      $wrapper.attr('data-ldjem-editor-preview-open', showEditorPreview ? 'yes' : 'no');

      // Enforce visibility as a JS fallback in editor/runtime when CSS state can lag.
      $localHamburger.css('display', '');
      $fallbackMenu.css('display', '');
      if (enabled) {
        if (state.isOpen) {
          $localHamburger.css('display', 'none');
        } else {
          $localHamburger.css('display', 'flex');
        }
        $fallbackMenu.css('display', 'none');
      } else {
        $localHamburger.css('display', 'none');
        $fallbackMenu.css('display', 'flex');
      }

      if (!enabled && state.isOpen) {
        closeMenu();
      }
    }

    function getDataInt(attrName) {
      const raw = parseInt($wrapper.attr(attrName), 10);
      return Number.isFinite(raw) ? raw : 0;
    }

    function applyDirection(direction) {
      if (!direction || direction === 'inherit') return;
      if (!['left', 'right', 'top', 'bottom'].includes(direction)) return;

      $offcanvas.removeClass('direction-left direction-right direction-top direction-bottom');
      $offcanvas.addClass(`direction-${direction}`);
    }

    function applyDeviceOverrides(device) {
      const direction = $wrapper.attr(`data-direction-${device}`) || 'inherit';
      const duration = getDataInt(`data-animation-duration-${device}`);
      const panelSize = getDataInt(`data-panel-size-${device}`);
      const panelHeight = getDataInt(`data-panel-height-${device}`);

      applyDirection(direction);

      if (duration > 0) {
        $offcanvas.css('--ldjem-offcanvas-animation-speed', `${duration}ms`);
      }

      if (panelSize > 0) {
        $offcanvas.css('--ldjem-offcanvas-panel-size', `${panelSize}px`);
      }

      if (panelHeight > 0) {
        $offcanvas.css('--ldjem-offcanvas-panel-height', `${panelHeight}px`);
      }
    }

    function syncOffcanvasMenuTemplate(device) {
      const variant = `offcanvas-${device}`;
      const $menuRoot = $wrapper.find('.ldjem-offcanvas-menu').first();
      const $template = $wrapper.find(`.ldjem-offcanvas-device-templates [data-ldjem-menu-variant="${variant}"]`).first();
      if (!$menuRoot.length || !$template.length) {
        return;
      }

      const html = $template.html() || '';
      if ($menuRoot.data('ldjemRenderedVariant') === variant && $menuRoot.data('ldjemRenderedHtml') === html) {
        return;
      }

      $menuRoot.html(html);
      $menuRoot.data('ldjemRenderedVariant', variant);
      $menuRoot.data('ldjemRenderedHtml', html);
      $wrapper.attr('data-active-offcanvas-menu-variant', variant);
      $wrapper.attr('data-active-offcanvas-menu-id', $template.attr('data-menu-id') || '');
      resetDrilldown();
      emitDebug('offcanvas-menu-template-sync', {
        variant: variant,
        menuId: $template.attr('data-menu-id') || ''
      });
    }

    function isDrilldownMode() {
      // Missing attr = legacy markup → keep accordion behavior.
      return ($wrapper.attr('data-offcanvas-submenu-mode') || 'accordion') === 'drilldown';
    }

    function resetDrilldown() {
      state.drillStack = [];
      $offcanvas.find('.is-drill-panel-open').removeClass('is-drill-panel-open').css('z-index', '');
      $offcanvas.find('.is-expanded').removeClass('is-expanded');
      $offcanvas.find('.ldjem-submenu-toggle').attr('aria-expanded', 'false');
      $offcanvas.find('.ldjem-offcanvas-submenu').attr('aria-hidden', 'true').css('max-height', '');
      $offcanvas.removeClass('ldjem-offcanvas-is-drilled');
      $wrapper.removeClass('ldjem-offcanvas-is-drilled');
    }

    function drillInto($parent, $toggleBtn) {
      const $submenu = $parent.children('.ldjem-offcanvas-submenu');
      if (!$submenu.length || $submenu.hasClass('is-drill-panel-open')) {
        return;
      }

      const depth = state.drillStack.length + 1;
      $parent.addClass('is-expanded');
      if ($toggleBtn && $toggleBtn.length) {
        $toggleBtn.attr('aria-expanded', 'true');
      }
      $submenu
        .addClass('is-drill-panel-open')
        .attr('aria-hidden', 'false')
        .css('z-index', 10 + depth);

      state.drillStack.push($submenu);
      $offcanvas.addClass('ldjem-offcanvas-is-drilled');
      $wrapper.addClass('ldjem-offcanvas-is-drilled');

      const $backBtn = $submenu.children('.ldjem-offcanvas-drill-back').find('.ldjem-offcanvas-drill-back-btn').first();
      window.requestAnimationFrame(function () {
        if ($backBtn.length) {
          $backBtn.focus();
        } else {
          const $firstLink = $submenu.find('> .ldjem-offcanvas-submenu-item > a').first();
          if ($firstLink.length) {
            $firstLink.focus();
          }
        }
      });

      $(document).trigger('ldjem:submenu:drilled', {
        element: $toggleBtn,
        depth: depth,
        level: $parent.parents('.ldjem-offcanvas-submenu').length
      });
    }

    function drillBack() {
      if (!state.drillStack.length) {
        return false;
      }

      const $submenu = state.drillStack.pop();
      const $parent = $submenu.parent('li');
      const $toggleBtn = $parent.children('.ldjem-submenu-toggle');

      $submenu.removeClass('is-drill-panel-open').attr('aria-hidden', 'true').css('z-index', '');
      $parent.removeClass('is-expanded');
      $toggleBtn.attr('aria-expanded', 'false');

      if (!state.drillStack.length) {
        $offcanvas.removeClass('ldjem-offcanvas-is-drilled');
        $wrapper.removeClass('ldjem-offcanvas-is-drilled');
      }

      window.requestAnimationFrame(function () {
        if ($toggleBtn.length) {
          $toggleBtn.focus();
        } else {
          $parent.children('a').first().focus();
        }
      });

      $(document).trigger('ldjem:submenu:drillback', {
        depth: state.drillStack.length
      });

      return true;
    }

    /**
     * Open off-canvas menu
     */
    function openMenu() {
      emitDebug('open-request', {
        source: 'openMenu'
      });
      if (state.isOpen) return;
      if (!isOffcanvasEnabledForCurrentDevice()) {
        emitDebug('open-blocked', {
          reason: 'offcanvas_disabled_for_device',
          source: 'openMenu'
        });
        $(document).trigger('ldjem:offcanvas:toggle-attempt', [{
          widgetId: widgetId,
          action: 'open',
          allowed: false,
          reason: 'offcanvas_disabled_for_device',
          device: getCurrentDevice(),
          source: 'openMenu'
        }]);
        return;
      }

      state.isOpen = true;

      if (isEditorActive()) {
        $wrapper.attr('data-ldjem-user-closed-preview', 'no');
      }

      syncVisualViewportHeight($offcanvas.get(0));
      applyPanelFill($offcanvas);

      $offcanvas.addClass('is-open');
      $offcanvas.attr('aria-hidden', 'false');
      $hamburger.attr('aria-expanded', 'true');
      $wrapper.addClass('ldjem-offcanvas-is-open ldjem-editor-preview-open').attr('data-ldjem-editor-preview-open', 'yes');
      emitDebug('open-applied', {
        offcanvasHasOpenClass: $offcanvas.hasClass('is-open') ? 'yes' : 'no',
        offcanvasAriaHidden: $offcanvas.attr('aria-hidden') || '',
        hamburgerAriaExpanded: $hamburger.attr('aria-expanded') || ''
      });
      setTimeout(function () {
        syncVisualViewportHeight($offcanvas.get(0));
        applyPanelFill($offcanvas);
        emitDebug('open-post-frame', {
          offcanvasHasOpenClass: $offcanvas.hasClass('is-open') ? 'yes' : 'no',
          offcanvasAriaHidden: $offcanvas.attr('aria-hidden') || '',
          hamburgerAriaExpanded: $hamburger.attr('aria-expanded') || ''
        });
      }, 40);

      // Prevent body scroll
      $('body').addClass('ldjem-offcanvas-open');

      // Setup focus trap
      self.setupFocusTrap($offcanvas, state);

      // Trigger custom event
      $(document).trigger('ldjem:offcanvas:opened', [widgetId]);
      $(document).trigger('ldjem:offcanvas:toggle-attempt', [{
        widgetId: widgetId,
        action: 'open',
        allowed: true,
        reason: 'opened',
        device: getCurrentDevice(),
        source: 'openMenu'
      }]);
    }

    /**
     * Close off-canvas menu
     */
    function closeMenu() {
      emitDebug('close-request', {
        source: 'closeMenu'
      });
      if (!state.isOpen) return;

      state.isOpen = false;

      if (isEditorActive()) {
        $wrapper.attr('data-ldjem-user-closed-preview', 'yes');
      }

      $offcanvas.removeClass('is-open');
      $offcanvas.attr('aria-hidden', 'true');
      $hamburger.attr('aria-expanded', 'false');
      $wrapper.removeClass('ldjem-offcanvas-is-open ldjem-editor-preview-open').attr('data-ldjem-editor-preview-open', 'no');
      emitDebug('close-applied', {
        offcanvasHasOpenClass: $offcanvas.hasClass('is-open') ? 'yes' : 'no',
        offcanvasAriaHidden: $offcanvas.attr('aria-hidden') || '',
        hamburgerAriaExpanded: $hamburger.attr('aria-expanded') || ''
      });

      // Restore body scroll
      $('body').removeClass('ldjem-offcanvas-open');

      // Return focus for keyboard users; blur on touch so Chrome/iOS does not leave a sticky ring.
      var focusMode = 'hide_touch';
      var $host = $wrapper.closest('.elementor-element');
      if ($host.hasClass('ldjem-toggle-focus-show')) {
        focusMode = 'show';
      } else if ($host.hasClass('ldjem-toggle-focus-hide_all')) {
        focusMode = 'hide_all';
      }
      var coarsePointer = window.matchMedia && window.matchMedia('(hover: none), (pointer: coarse)').matches;
      if (focusMode !== 'show' && coarsePointer) {
        $hamburger.trigger('blur');
      } else {
        $hamburger.focus();
      }

      resetDrilldown();

      // Trigger custom event
      $(document).trigger('ldjem:offcanvas:closed', [widgetId]);
      $(document).trigger('ldjem:offcanvas:toggle-attempt', [{
        widgetId: widgetId,
        action: 'close',
        allowed: true,
        reason: 'closed',
        device: getCurrentDevice(),
        source: 'closeMenu'
      }]);
    }

    /**
     * Toggle off-canvas menu
     */
    function toggleMenu() {
      emitDebug('toggle-called', {
        stateWasOpen: state.isOpen ? 'yes' : 'no'
      });
      if (state.isOpen) {
        closeMenu();
      } else {
        openMenu();
      }
    }

    // Event: Hamburger click (delegated so it survives Elementor re-renders)
    $(document).off('click.ldjem-hamburger-' + widgetId);
    $(document).on('click.ldjem-hamburger-' + widgetId, `[data-ldjem-id="${widgetId}"] .ldjem-hamburger-btn`, function (e) {
      emitDebug('hamburger-click-received', {
        targetTag: e && e.target && e.target.tagName ? e.target.tagName.toLowerCase() : 'unknown',
        source: 'hamburger-handler'
      });
      e.preventDefault();
      $(document).trigger('ldjem:offcanvas:hamburger-click', [{
        widgetId: widgetId,
        device: getCurrentDevice(),
        enabledForDevice: isOffcanvasEnabledForCurrentDevice() ? 'yes' : 'no',
        source: 'hamburger'
      }]);
      toggleMenu();
    });

    // Capture-phase tracer to confirm whether clicks reach DOM target in editor.
    if (isEditorActive() && !window.__ldjemNativeClickTraceBound) {
      window.__ldjemNativeClickTraceBound = true;
      document.addEventListener('click', function (evt) {
        const target = evt.target && evt.target.closest ? evt.target.closest('.ldjem-hamburger-btn') : null;
        if (!target) {
          return;
        }
        const holder = target.closest('[data-ldjem-id]');
        const tracedWidgetId = holder ? holder.getAttribute('data-ldjem-id') : '';
        $(document).trigger('ldjem:offcanvas:native-click-capture', [{
          widgetId: tracedWidgetId,
          source: 'native-capture',
          targetClass: target.className || '',
          defaultPrevented: evt.defaultPrevented ? 'yes' : 'no'
        }]);
      }, true);
    }
    emitDebug('handlers-bound', {
      closeButtonCount: $closeBtn.length,
      menuItemCount: $menuItems.length
    });

    // Event: Close button click (delegated for Elementor editor re-renders)
    $(document).off('click.ldjem-close-' + widgetId);
    $(document).on('click.ldjem-close-' + widgetId, `[data-ldjem-id="${widgetId}"] .ldjem-offcanvas-close`, function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeMenu();
    });

    // Clear prior instance handlers before binding (Elementor force re-init)
    $offcanvas.off('.ldjem');

    // Legacy direct binding fallback
    $closeBtn.off('click.ldjem').on('click.ldjem', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeMenu();
    });

    // Event: Escape key — pop drill level first, then close drawer
    $(document).off('keydown.ldjem-' + widgetId);
    $(document).on('keydown.ldjem-' + widgetId, function (e) {
      if (e.key !== 'Escape' || !state.isOpen) {
        return;
      }
      if (isDrilldownMode() && drillBack()) {
        e.preventDefault();
        return;
      }
      closeMenu();
    });

    // Event: Menu item click (close on navigation)
    $offcanvas.on('click.ldjem', '.ldjem-offcanvas-menu-item:not(.has-children) > a, .ldjem-offcanvas-submenu-item:not(.has-children) > a', function () {
      closeMenu();
    });

    // Drill-down: placeholder parent links (#) open the panel instead of navigating
    $offcanvas.on('click.ldjem', '.ldjem-offcanvas-menu-item.has-children > a, .ldjem-offcanvas-submenu-item.has-children > a', function (e) {
      if (!isDrilldownMode()) {
        return;
      }
      const href = ($(this).attr('href') || '').trim();
      const isPlaceholder = !href || href === '#' || href.indexOf('javascript:') === 0;
      if (!isPlaceholder) {
        return;
      }
      e.preventDefault();
      const $parent = $(this).closest('li');
      const $toggleBtn = $parent.children('.ldjem-submenu-toggle');
      drillInto($parent, $toggleBtn);
    });

    function toggleOffcanvasSubmenu($parent, $toggleBtn) {
      if (isDrilldownMode()) {
        if ($parent.hasClass('is-expanded') && $parent.children('.ldjem-offcanvas-submenu').hasClass('is-drill-panel-open')) {
          // Already open as the active panel — ignore (back button handles return)
          return;
        }
        drillInto($parent, $toggleBtn);
        return;
      }

      const isAccordion = ($wrapper.attr('data-submenu-accordion') || 'yes') === 'yes';
      const isCurrentlyExpanded = $parent.hasClass('is-expanded');
      const $submenu = $parent.children('.ldjem-offcanvas-submenu');

      if (isAccordion && !isCurrentlyExpanded) {
        $parent.siblings('.is-expanded').each(function () {
          const $sibling = $(this);
          $sibling.removeClass('is-expanded');
          $sibling.children('.ldjem-submenu-toggle').attr('aria-expanded', 'false');
          $sibling.children('.ldjem-offcanvas-submenu').attr('aria-hidden', 'true').css('max-height', '');
        });
      }

      $parent.toggleClass('is-expanded');
      const isExpanded = $parent.hasClass('is-expanded');
      $toggleBtn.attr('aria-expanded', isExpanded ? 'true' : 'false');

      if ($submenu.length) {
        $submenu.attr('aria-hidden', isExpanded ? 'false' : 'true');
        if (isExpanded) {
          $submenu.css('max-height', $submenu[0].scrollHeight + 'px');
          // Keep bottom-of-drawer opens visible in accordion mode
          window.requestAnimationFrame(function () {
            if ($parent[0] && typeof $parent[0].scrollIntoView === 'function') {
              $parent[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
          });
        } else {
          $submenu.css('max-height', '');
        }
      }

      $(document).trigger('ldjem:submenu:toggled', {
        element: $toggleBtn,
        isExpanded: isExpanded,
        level: $parent.parents('.ldjem-offcanvas-submenu').length
      });
    }

    // Event: Submenu toggle button (delegated so it survives menu template swaps)
    $(document).off('click.ldjem-offcanvas-submenu-' + widgetId);
    $(document).on('click.ldjem-offcanvas-submenu-' + widgetId, `[data-ldjem-id="${widgetId}"] .ldjem-offcanvas-wrapper .ldjem-submenu-toggle`, function (e) {
      e.preventDefault();
      e.stopPropagation();
      const $toggleBtn = $(this);
      const $parent = $toggleBtn.closest('li');
      toggleOffcanvasSubmenu($parent, $toggleBtn);
    });

    // Event: Drill-down back button
    $(document).off('click.ldjem-offcanvas-drillback-' + widgetId);
    $(document).on('click.ldjem-offcanvas-drillback-' + widgetId, `[data-ldjem-id="${widgetId}"] .ldjem-offcanvas-wrapper .ldjem-offcanvas-drill-back-btn`, function (e) {
      e.preventDefault();
      e.stopPropagation();
      drillBack();
    });

    // Event: Keyboard navigation in menu - Enhanced for nested items
    $offcanvas.on('keydown.ldjem', '.ldjem-offcanvas-menu-item a, .ldjem-offcanvas-submenu-item a, .ldjem-offcanvas-menu-item .ldjem-submenu-toggle, .ldjem-offcanvas-submenu-item .ldjem-submenu-toggle, .ldjem-offcanvas-drill-back-btn', function (e) {
      const $this = $(this);
      const $parent = $this.closest('li');
      const $activePanel = state.drillStack.length
        ? state.drillStack[state.drillStack.length - 1]
        : $offcanvas.find('.ldjem-offcanvas-menu').first();
      const $allFocusableItems = isDrilldownMode()
        ? $activePanel.find('a, .ldjem-submenu-toggle, .ldjem-offcanvas-drill-back-btn').filter(':visible')
        : $offcanvas.find('a[role], a:not([role="presentation"]), .ldjem-submenu-toggle');
      const currentIndex = $allFocusableItems.index($this);

      switch (e.key) {
        case 'ArrowDown':
          e.preventDefault();
          if (currentIndex < $allFocusableItems.length - 1) {
            $allFocusableItems.eq(currentIndex + 1).focus();
          }
          break;

        case 'ArrowUp':
          e.preventDefault();
          if (currentIndex > 0) {
            $allFocusableItems.eq(currentIndex - 1).focus();
          }
          break;

        case 'ArrowRight':
          e.preventDefault();
          if ($parent.hasClass('has-children')) {
            const $toggleBtn = $parent.children('.ldjem-submenu-toggle');
            if (isDrilldownMode()) {
              drillInto($parent, $toggleBtn);
            } else if (!$parent.hasClass('is-expanded')) {
              toggleOffcanvasSubmenu($parent, $toggleBtn);
            } else {
              const $firstChild = $parent.find('> .ldjem-offcanvas-submenu > li:first-child a');
              if ($firstChild.length) {
                $firstChild.focus();
              }
            }
          }
          break;

        case 'ArrowLeft':
          e.preventDefault();
          if (isDrilldownMode()) {
            if ($this.hasClass('ldjem-offcanvas-drill-back-btn') || state.drillStack.length) {
              drillBack();
            }
          } else if ($parent.hasClass('has-children') && $parent.hasClass('is-expanded')) {
            const $toggleBtn = $parent.children('.ldjem-submenu-toggle');
            toggleOffcanvasSubmenu($parent, $toggleBtn);
          } else {
            const $parentMenuItem = $parent.closest('.ldjem-offcanvas-submenu').closest('li').children('a, .ldjem-submenu-toggle').first();
            if ($parentMenuItem.length) {
              $parentMenuItem.focus();
            }
          }
          break;

        case 'Enter':
        case ' ':
          if ($this.hasClass('ldjem-offcanvas-drill-back-btn')) {
            e.preventDefault();
            drillBack();
          } else if ($this.hasClass('ldjem-submenu-toggle')) {
            e.preventDefault();
            toggleOffcanvasSubmenu($parent, $this);
          } else if ($parent.hasClass('has-children') && !$parent.hasClass('is-expanded')) {
            e.preventDefault();
            const $toggleBtn = $parent.children('.ldjem-submenu-toggle');
            toggleOffcanvasSubmenu($parent, $toggleBtn);
          }
          break;
      }
    });

    // Event: Social icons (open in new tab)
    $offcanvas.on('click.ldjem', '.ldjem-offcanvas-social-link', function (e) {
      const href = $(this).attr('href');
      if (href) {
        window.open(href, '_blank');
        e.preventDefault();
      }
    });

    // Store functions on state for external access
    state.openMenu = openMenu;
    state.closeMenu = closeMenu;
    state.toggleMenu = toggleMenu;
    state.syncDeviceState = syncDeviceState;

    // Expose state
    $offcanvas.data('ldjem-offcanvas-state', state);
    syncDeviceState('init');

    return state;
  };

  /**
   * Setup focus trap for accessibility
   */
  LDJEMOffCanvas.setupFocusTrap = function ($container, state) {
    const focusableElements = $container.find(
      'a, button, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements.first();
    const lastElement = focusableElements.last();

    $container.on('keydown.ldjem-focus-trap', function (e) {
      if (e.key !== 'Tab') return;

      if (e.shiftKey) {
        // Shift + Tab
        if ($(document.activeElement).is(firstElement)) {
          e.preventDefault();
          lastElement.focus();
        }
      } else {
        // Tab
        if ($(document.activeElement).is(lastElement)) {
          e.preventDefault();
          firstElement.focus();
        }
      }
    });

    state.focusTrap = {
      $container: $container,
      firstElement: firstElement,
      lastElement: lastElement,
    };
  };

  /**
   * jQuery plugin for off-canvas menu
   */
  $.fn.ldjemOffCanvas = function (options) {
    const settings = $.extend({}, options);
    const forceInit = !!settings.force;

    return this.each(function () {
      const $this = $(this);
      const widgetId = $this.data('ldjem-id');

      if (!widgetId) {
        return;
      }

       if (!forceInit && $this.data('ldjem-offcanvas-state')) {
        return;
      }

      LDJEMOffCanvas.init(widgetId, settings);
    });
  };

  function initAllOffcanvas(context, force) {
    $('.ldjem-offcanvas-wrapper').each(function () {
      const $this = $(this);
      const widgetId = $this.data('ldjem-id');
      if (!widgetId) {
        return;
      }
      $this.ldjemOffCanvas({ force: !!force });
      const state = $this.data('ldjem-offcanvas-state');
      if (state && state.syncDeviceState) {
        state.syncDeviceState(context || 'init-scan');
      }
    });
  }
  LDJEMOffCanvas.initAllOffcanvas = initAllOffcanvas;

  /**
   * Auto-initialize off-canvas menus on page load
   */
  $(document).ready(function () {
    initAllOffcanvas('dom-ready', true);
    // Elementor editor may inject widget DOM slightly later.
    setTimeout(function () { initAllOffcanvas('dom-ready-100'); }, 100);
    setTimeout(function () { initAllOffcanvas('dom-ready-500'); }, 500);
    setTimeout(function () { initAllOffcanvas('dom-ready-1200'); }, 1200);
  });

  /**
   * Handle responsive behavior - close menu on resize
   */
  let resizeTimer;
  $(window).on('resize.ldjem', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      syncVisualViewportHeight();
      $('.ldjem-offcanvas-wrapper.is-open').each(function () {
        applyPanelFill($(this));
      });
      // Sync all off-canvas instances on resize
      $('.ldjem-offcanvas-wrapper').each(function () {
        const state = $(this).data('ldjem-offcanvas-state');
        if (state && state.syncDeviceState) {
          state.syncDeviceState('resize');
        }
      });
    }, 250);
  });

  if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', function () {
      syncVisualViewportHeight();
    });
  }

  syncVisualViewportHeight();

  /**
   * Destroy/cleanup off-canvas menu
   */
  LDJEMOffCanvas.destroy = function (widgetId) {
    const $offcanvas = $(`[data-ldjem-id="${widgetId}"] .ldjem-offcanvas-wrapper`);
    const $hamburger = $(`[data-ldjem-id="${widgetId}"] .ldjem-hamburger-btn`);

    if ($offcanvas.length) {
      $offcanvas.off('.ldjem').off('.ldjem-focus-trap').removeData('ldjem-offcanvas-state');
      $hamburger.off('.ldjem');
      $(document).off('.ldjem-' + widgetId);
      $(document).off('click.ldjem-offcanvas-submenu-' + widgetId);
      $(document).off('click.ldjem-offcanvas-drillback-' + widgetId);
    }
  };

  /**
   * Public API
   */
  window.LDJEMOffCanvas = LDJEMOffCanvas;

})(jQuery);

/* Elementor Editor Support - Reinitialize on widget update */
if (window.jQuery) {
  (function ($) {
    function bindElementorHooks() {
      if (!window.elementorFrontend || !window.elementorFrontend.hooks || window.__ldjemElementorHookBound) {
        return false;
      }
      window.__ldjemElementorHookBound = true;
      elementorFrontend.hooks.addAction(
        'frontend/element_ready/ldjem_menu.default',
        function ($scope) {
          $scope.find('.ldjem-offcanvas-wrapper').ldjemOffCanvas({ force: true });
          // Keep preview responsive modes in sync.
          setTimeout(function () {
            $scope.find('.ldjem-offcanvas-wrapper').each(function () {
              const state = $(this).data('ldjem-offcanvas-state');
              if (state && state.syncDeviceState) {
                state.syncDeviceState('elementor-ready');
              }
            });
          }, 100);
        }
      );

      if (!window.__ldjemEditorIconSyncBound) {
        window.__ldjemEditorIconSyncBound = true;

        document.addEventListener('ldjem:editor-icon-sync', function (event) {
          const detail = event && event.detail ? event.detail : {};
          const widgetId = detail.widgetId;

          if (!widgetId) {
            return;
          }

          const widgetRoot = document.querySelector('.elementor-element-' + widgetId);
          if (!widgetRoot) {
            return;
          }

          if (detail.target === 'hamburger' && detail.iconHtml) {
            widgetRoot.querySelectorAll('.ldjem-hamburger-btn, .ldjem-hamburger').forEach(function (button) {
              button.querySelectorAll('i, svg, img').forEach(function (node) {
                node.remove();
              });
              button.insertAdjacentHTML('afterbegin', detail.iconHtml);
              button.classList.add('has-custom-icon');
            });
            return;
          }

          if (detail.target !== 'close') {
            return;
          }

          widgetRoot.querySelectorAll('.ldjem-offcanvas-close').forEach(function (button) {
            button.classList.remove('ldjem-close-type-icon', 'ldjem-close-type-letter', 'has-custom-icon', 'icon-x', 'icon-arrow', 'icon-chevron');
            button.querySelectorAll('i, svg, img, .ldjem-close-letter').forEach(function (node) {
              node.remove();
            });

            if (detail.closeType === 'letter') {
              button.classList.add('ldjem-close-type-letter');
              const letterNode = document.createElement('span');
              letterNode.className = 'ldjem-close-letter';
              letterNode.setAttribute('aria-hidden', 'true');
              letterNode.textContent = detail.letter || '×';
              button.appendChild(letterNode);
              return;
            }

            if (detail.iconHtml) {
              button.classList.add('ldjem-close-type-icon', 'has-custom-icon');
              button.insertAdjacentHTML('afterbegin', detail.iconHtml);
            }
          });
        });
      }

      return true;
    }

    bindElementorHooks();
    // Elementor runtime can appear after this script in editor iframe.
    setTimeout(bindElementorHooks, 200);
    setTimeout(bindElementorHooks, 700);
    setTimeout(bindElementorHooks, 1500);

    // Elementor device preview toggles do not always fire window resize.
    // Observe class changes and force sync/log when preview mode changes.
    const previewObserver = new MutationObserver(function () {
      jQuery('.ldjem-offcanvas-wrapper').each(function () {
        const state = jQuery(this).data('ldjem-offcanvas-state');
        if (state && state.syncDeviceState) {
          state.syncDeviceState('elementor-preview-toggle');
        }
      });
    });

    if (document.documentElement) {
      previewObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    }
    if (document.body) {
      previewObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    }
    try {
      if (window.top && window.top.document) {
        if (window.top.document.documentElement) {
          previewObserver.observe(window.top.document.documentElement, { attributes: true, attributeFilter: ['class'] });
        }
        if (window.top.document.body) {
          previewObserver.observe(window.top.document.body, { attributes: true, attributeFilter: ['class'] });
        }
      }
    } catch (e) {
      // Ignore cross-frame access errors.
    }

    // Fallback watcher: periodic sync avoids stale editor preview states.
    setInterval(function () {
      jQuery('.ldjem-offcanvas-wrapper').each(function () {
        const state = jQuery(this).data('ldjem-offcanvas-state');
        if (state && state.syncDeviceState) {
          state.syncDeviceState('device-poll');
        }
      });
      if (document.body && document.body.classList.contains('elementor-editor-active') && window.LDJEMOffCanvas && window.LDJEMOffCanvas.initAllOffcanvas) {
        window.LDJEMOffCanvas.initAllOffcanvas('editor-poll', false);
      }
    }, 700);

  // Elementor overlay can intercept clicks in edit mode.
  // Bridge overlay-clicks that land on top of the hamburger area.
    if (!window.__ldjemEditorOverlayBridgeBound) {
      window.__ldjemEditorOverlayBridgeBound = true;
      document.addEventListener('click', function (evt) {
      if (!document.body || !document.body.classList.contains('elementor-editor-active')) {
        return;
      }
      const overlay = evt.target.closest('.elementor-widget-ldjem_menu .elementor-element-overlay, .elementor-element.elementor-widget-ldjem_menu > .elementor-element-overlay');
      if (!overlay) {
        return;
      }

      const widget = overlay.closest('.elementor-widget-ldjem_menu, .elementor-element.elementor-widget-ldjem_menu');
      if (!widget) {
        return;
      }

      const widgetIdClass = Array.from(widget.classList).find(function (cls) {
        return cls.indexOf('elementor-element-') === 0 && cls !== 'elementor-element-edit-mode';
      });
      const widgetId = widgetIdClass ? widgetIdClass.replace('elementor-element-', '') : '';
      const hamburger = widget.querySelector('.ldjem-menu-wrapper-offcanvas .ldjem-hamburger-btn');
      const closeButton = widget.querySelector('.ldjem-menu-wrapper-offcanvas .ldjem-offcanvas-close');
      const x = evt.clientX;
      const y = evt.clientY;

      if (closeButton) {
        const closeRect = closeButton.getBoundingClientRect();
        const hitClose = x >= closeRect.left && x <= closeRect.right && y >= closeRect.top && y <= closeRect.bottom;
        if (hitClose) {
          evt.preventDefault();
          evt.stopPropagation();
          $(document).trigger('ldjem:offcanvas:overlay-bridge', [{
            widgetId: widgetId,
            source: 'elementor-overlay-close',
            clickX: x,
            clickY: y
          }]);
          closeButton.click();
          return;
        }
      }

      if (!hamburger) {
        return;
      }

      const rect = hamburger.getBoundingClientRect();
      const hitHamburger = x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
      if (!hitHamburger) {
        return;
      }

      evt.preventDefault();
      evt.stopPropagation();
      $(document).trigger('ldjem:offcanvas:overlay-bridge', [{
        widgetId: widgetId,
        source: 'elementor-overlay',
        clickX: x,
        clickY: y
      }]);
        hamburger.click();
      }, true);
    }
  })(window.jQuery);
}
