// Settings manager: handles AJAX save of the settings form
(function( window, document, $ ) {
    'use strict';

    function MLBSettingsManager() {}

    MLBSettingsManager.prototype.init = function() {
        console.debug('MLBSettingsManager.init');

        try {
            var $form = $('input[name="action"][value="mlb_save_admin_settings"]').closest('form');
            if (!$form || !$form.length) {
                return;
            }

            if ($form.data('mlb-bound')) {
                return;
            }
            $form.data('mlb-bound', 1);

            $form.on('submit', function(e) {
                e.preventDefault();

                var $btn = $form.find('button[type=submit]').first();
                var originalText = $btn ? $btn.text() : '';
                if ($btn && $btn.length) {
                    $btn.prop('disabled', true);
                    $btn.text( mlbGettext('Saving...') );
                }

                var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                if (!ajaxUrl) {
                    if ($btn && $btn.length) {
                        $btn.prop('disabled', false);
                        $btn.text(originalText);
                    }
                    alert( mlbGettext('AJAX URL not available') );
                    return;
                }

                var fd = new FormData($form[0]);
                fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                    .then(function(response) { return response.json(); })
                    .then(function(json) {
                        var $main = $('#mlb-dashboard-main');
                        if (!json || !json.success) {
                            var errorMsg = (json && json.data && json.data.message) ? json.data.message : mlbGettext('Save failed');
                            if ($main.length) {
                                mlbCreateAdminNotice(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                            return;
                        }

                        var successMsg = (json.data && json.data.message) ? json.data.message : mlbGettext('Settings saved');
                        if ($main.length) {
                            mlbCreateAdminNotice(successMsg, 'success', 3500);
                        } else {
                            alert(successMsg);
                        }

                        try {
                            $('.mlb-sidebar-link[data-content^="settings"]').trigger('click');
                        } catch (refreshErr) {
                            console.warn('Failed to refresh settings fragment', refreshErr);
                        }
                    })
                    .catch(function(err) {
                        console.error('mlb_save_admin_settings failed', err);
                        alert( mlbGettext('Save request failed') );
                    })
                    .finally(function() {
                        if ($btn && $btn.length) {
                            $btn.prop('disabled', false);
                            $btn.text(originalText);
                        }
                    });
            });
        } catch (err) {
            console.error('MLBSettingsManager.init error', err);
        }
    };

    window.MLBSettingsManager = MLBSettingsManager;

    $(function() {
        try {
            window.mlb_settings_manager = new MLBSettingsManager();
            window.mlb_settings_manager.init();
        } catch (err) {
            console.error('Auto-init settings manager failed', err);
        }
    });
})( window, document, jQuery );
