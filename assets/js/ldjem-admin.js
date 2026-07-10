/**
 * LanceDesk Elementor Menu – Admin JavaScript
 *
 * Elementor editor live preview helpers (runs in the editor parent window).
 *
 * @package LDJEM
 */

(function($) {
    'use strict';

    var LDJEMAdmin = {
        init: function() {
            this.bindElementorEditor();
        },

        bindElementorEditor: function() {
            if (typeof window.elementor === 'undefined') {
                return;
            }

            var self = this;
            var boot = function() {
                if (!window.elementor.hooks) {
                    return;
                }

                window.elementor.hooks.addAction('panel/open_editor/widget', function(panel, model) {
                    if (!model || !self.isTargetWidget(model)) {
                        return;
                    }

                    self.attachWidgetListeners(model);
                    self.syncWidgetPreview(model);
                });

                if (window.elementor.on) {
                    window.elementor.on('preview:loaded', function() {
                        self.syncAllWidgetPreviews();
                    });
                }
            };

            if (window.elementor.on) {
                window.elementor.on('preview:loaded', boot);
            }

            boot();
        },

        isTargetWidget: function(model) {
            var widgetName = (window.ldjemAdmin && window.ldjemAdmin.widget_name) ? window.ldjemAdmin.widget_name : 'ldjem_menu';
            return model.get('widgetType') === widgetName;
        },

        getPreviewScope: function() {
            if (window.elementor) {
                if (elementor.$previewContents && elementor.$previewContents.length) {
                    return elementor.$previewContents;
                }

                if (elementor.$preview && elementor.$preview.length) {
                    return elementor.$preview;
                }
            }

            var iframe = document.getElementById('elementor-preview-iframe');
            if (iframe && iframe.contentDocument) {
                return $(iframe.contentDocument);
            }

            return $;
        },

        getWidgetModel: function(widgetId) {
            if (!window.elementor || typeof elementor.getPreviewView !== 'function') {
                return null;
            }

            var previewView = elementor.getPreviewView();
            if (!previewView || !previewView.collection) {
                return null;
            }

            return previewView.collection.findWhere({ id: String(widgetId) }) || null;
        },

        getWidgetSelector: function(model) {
            var widgetId = model && model.get ? model.get('id') : '';
            return widgetId ? ('.elementor-element-' + widgetId) : '';
        },

        attachWidgetListeners: function(model) {
            var self = this;

            if (model._ldjemWidgetBound) {
                return;
            }

            model._ldjemWidgetBound = true;

            model.on('change:settings:offcanvas_preset', function() {
                var presetId = model.getSetting('offcanvas_preset');
                if (!self.isPresetAutoApplyEnabled(model)) {
                    return;
                }
                self.applyPresetToModel(model, presetId);
            });

            model.on('change:settings:offcanvas_preset_auto_apply', function() {
                var autoApplyEnabled = self.isPresetAutoApplyEnabled(model);
                var presetId = model.getSetting('offcanvas_preset');

                if (!autoApplyEnabled || !presetId || presetId === 'none') {
                    return;
                }

                self.applyPresetToModel(model, presetId);
            });

            model.on('change:settings:mobile_hamburger_position', function() {
                self.applyHamburgerPositionClass(model);
            });

            model.on('change:settings:hamburger_icon', function() {
                self.updateHamburgerIconPreview(model, true);
            });

            model.on('change:settings:offcanvas_close_icon_type', function() {
                self.updateCloseButtonPreview(model, true);
            });

            model.on('change:settings:offcanvas_close_letter', function() {
                self.updateCloseButtonPreview(model, true);
            });

            model.on('change:settings:offcanvas_close_icon', function() {
                self.updateCloseButtonPreview(model, true);
            });
        },

        syncAllWidgetPreviews: function() {
            var self = this;

            if (!window.elementor || typeof elementor.getPreviewView !== 'function') {
                return;
            }

            var previewView = elementor.getPreviewView();
            if (!previewView || !previewView.collection) {
                return;
            }

            previewView.collection.each(function(model) {
                if (self.isTargetWidget(model)) {
                    self.syncWidgetPreview(model);
                }
            });
        },

        syncWidgetPreview: function(model) {
            this.applyHamburgerPositionClass(model);
            this.updateHamburgerIconPreview(model, false);
            this.updateCloseButtonPreview(model, false);
        },

        renderElementorIcon: function(iconSetting) {
            return new Promise(function(resolve) {
                if (!iconSetting || !iconSetting.value) {
                    resolve('');
                    return;
                }

                if (window.elementor && elementor.helpers && typeof elementor.helpers.renderIcon === 'function') {
                    try {
                        var rendered = elementor.helpers.renderIcon(iconSetting, { 'aria-hidden': true }, null, 'i');

                        if (rendered && typeof rendered.then === 'function') {
                            rendered.then(function(html) {
                                resolve(html || '');
                            }).catch(function() {
                                resolve(LDJEMAdmin.buildIconFallback(iconSetting));
                            });
                            return;
                        }

                        resolve(rendered || LDJEMAdmin.buildIconFallback(iconSetting));
                        return;
                    } catch (error) {
                        resolve(LDJEMAdmin.buildIconFallback(iconSetting));
                        return;
                    }
                }

                resolve(LDJEMAdmin.buildIconFallback(iconSetting));
            });
        },

        buildIconFallback: function(iconSetting) {
            if (!iconSetting || !iconSetting.value) {
                return '';
            }

            if (typeof iconSetting.value === 'string') {
                return '<i class="' + iconSetting.value + '" aria-hidden="true"></i>';
            }

            if (typeof iconSetting.value === 'object' && iconSetting.value.url) {
                return '<img src="' + iconSetting.value.url + '" alt="" aria-hidden="true" />';
            }

            return '';
        },

        isOffcanvasOpenInPreview: function(model) {
            var selector = this.getWidgetSelector(model);
            if (!selector) {
                return false;
            }

            var $wrapper = this.getPreviewScope().find(selector + ' .ldjem-menu-wrapper-offcanvas').first();
            if (!$wrapper.length) {
                return false;
            }

            return $wrapper.hasClass('ldjem-offcanvas-is-open') ||
                $wrapper.attr('data-ldjem-editor-preview-open') === 'yes';
        },

        restoreOffcanvasOpenState: function(model) {
            var self = this;
            var selector = this.getWidgetSelector(model);
            if (!selector) {
                return;
            }

            window.setTimeout(function() {
                var $scope = self.getPreviewScope();
                var $wrapper = $scope.find(selector + ' .ldjem-menu-wrapper-offcanvas').first();
                var $offcanvas = $wrapper.find('.ldjem-offcanvas-wrapper').first();
                var $hamburger = $wrapper.find('.ldjem-hamburger-btn, .ldjem-hamburger').first();

                if (!$wrapper.length || !$offcanvas.length) {
                    return;
                }

                $wrapper.addClass('ldjem-offcanvas-is-open ldjem-editor-preview-open');
                $wrapper.attr('data-ldjem-editor-preview-open', 'yes');
                $offcanvas.addClass('is-open');
                $offcanvas.attr('aria-hidden', 'false');
                $hamburger.attr('aria-expanded', 'true');
                self.getPreviewScope().find('body').addClass('ldjem-offcanvas-open');
            }, 30);
        },

        notifyPreviewFrame: function(detail) {
            var previewDocument = this.getPreviewScope().get(0);

            if (!previewDocument || !previewDocument.defaultView) {
                return;
            }

            previewDocument.defaultView.dispatchEvent(new CustomEvent('ldjem:editor-icon-sync', {
                detail: detail || {}
            }));
        },

        updateHamburgerIconPreview: function(model, preserveOffcanvasState) {
            var selector = this.getWidgetSelector(model);
            if (!selector) {
                return;
            }

            var wasOpen = preserveOffcanvasState && this.isOffcanvasOpenInPreview(model);
            var icon = model.getSetting('hamburger_icon');
            var $buttons = this.getPreviewScope().find(selector + ' .ldjem-hamburger-btn, ' + selector + ' .ldjem-hamburger');

            if (!$buttons.length) {
                return;
            }

            var self = this;

            if (!icon || !icon.value) {
                $buttons.removeClass('has-custom-icon');
                $buttons.find('i, svg, img').remove();
                if (wasOpen) {
                    this.restoreOffcanvasOpenState(model);
                }
                return;
            }

            this.renderElementorIcon(icon).then(function(html) {
                if (!html) {
                    return;
                }

                $buttons.each(function() {
                    var $button = $(this);
                    $button.find('i, svg, img').remove();
                    $button.prepend(html);
                    $button.addClass('has-custom-icon');
                });

                self.notifyPreviewFrame({
                    widgetId: model.get('id'),
                    target: 'hamburger',
                    iconHtml: html
                });

                if (wasOpen) {
                    self.restoreOffcanvasOpenState(model);
                }
            });
        },

        updateCloseButtonPreview: function(model, preserveOffcanvasState) {
            var selector = this.getWidgetSelector(model);
            if (!selector) {
                return;
            }

            var wasOpen = preserveOffcanvasState && this.isOffcanvasOpenInPreview(model);
            var closeType = model.getSetting('offcanvas_close_icon_type') || 'icon';
            var $closeButtons = this.getPreviewScope().find(selector + ' .ldjem-offcanvas-close');
            var self = this;

            if (!$closeButtons.length) {
                if (wasOpen) {
                    this.restoreOffcanvasOpenState(model);
                }
                return;
            }

            $closeButtons.each(function() {
                var $closeBtn = $(this);
                $closeBtn.removeClass('ldjem-close-type-icon ldjem-close-type-letter has-custom-icon icon-x icon-arrow icon-chevron');
                $closeBtn.find('i, svg, img, .ldjem-close-letter').remove();

                if (closeType === 'letter') {
                    var letter = model.getSetting('offcanvas_close_letter');
                    if (!letter) {
                        letter = '×';
                    }

                    $closeBtn.addClass('ldjem-close-type-letter');
                    $closeBtn.append(
                        $('<span>', {
                            'class': 'ldjem-close-letter',
                            'aria-hidden': 'true',
                            'text': letter
                        })
                    );
                    return;
                }

                var icon = model.getSetting('offcanvas_close_icon');
                if (!icon || !icon.value) {
                    return;
                }

                $closeBtn.addClass('ldjem-close-type-icon has-custom-icon');
                self.renderElementorIcon(icon).then(function(html) {
                    if (!html) {
                        return;
                    }

                    $closeBtn.find('i, svg, img').remove();
                    $closeBtn.prepend(html);

                    self.notifyPreviewFrame({
                        widgetId: model.get('id'),
                        target: 'close',
                        closeType: 'icon',
                        iconHtml: html
                    });

                    if (wasOpen) {
                        self.restoreOffcanvasOpenState(model);
                    }
                });
            });

            if (closeType === 'letter') {
                this.notifyPreviewFrame({
                    widgetId: model.get('id'),
                    target: 'close',
                    closeType: 'letter',
                    letter: model.getSetting('offcanvas_close_letter') || '×'
                });
            }

            if (closeType === 'letter' && wasOpen) {
                this.restoreOffcanvasOpenState(model);
            }
        },

        applyHamburgerPositionClass: function(model) {
            var widgetId = model && model.get ? model.get('id') : '';
            if (!widgetId) {
                return;
            }

            var position = model.getSetting('mobile_hamburger_position') || 'left';
            if (['left', 'center', 'right'].indexOf(position) === -1) {
                position = 'left';
            }

            var widgetSelector = '.elementor-element-' + widgetId;
            var $buttons = this.getPreviewScope().find(widgetSelector + ' .ldjem-hamburger-btn, ' + widgetSelector + ' .ldjem-hamburger');

            if (!$buttons.length) {
                return;
            }

            $buttons
                .removeClass('ldjem-hamburger-left ldjem-hamburger-center ldjem-hamburger-right')
                .addClass('ldjem-hamburger-' + position);
        },

        isPresetAutoApplyEnabled: function(model) {
            var settingValue = model.getSetting('offcanvas_preset_auto_apply');

            if (typeof settingValue === 'undefined' || settingValue === null || settingValue === '') {
                return true;
            }

            return settingValue === 'yes';
        },

        applyPresetToModel: function(model, presetId) {
            if (!presetId || presetId === 'none') {
                return;
            }

            var allPresetSettings = window.ldjemAdmin.preset_settings || {};
            var presetSettings = allPresetSettings[presetId];

            if (!presetSettings || typeof presetSettings !== 'object') {
                return;
            }

            Object.keys(presetSettings).forEach(function(key) {
                var value = presetSettings[key];
                if (typeof model.setSetting === 'function') {
                    model.setSetting(key, value);
                } else if (model.get('settings') && typeof model.get('settings').set === 'function') {
                    model.get('settings').set(key, value);
                }
            });
        }
    };

    $(document).ready(function() {
        LDJEMAdmin.init();
    });

})(jQuery);
