// Global admin bootstrap for the plugin
( function( window, document, $ ) {
    'use strict';
    window.mlb_admin_params = window.mlb_admin_params || {};

    $( function(){
        // global utilities
        window.MLBAdmin = {
            init: function() {
                // common init, expose util methods
                console.debug('MLBAdmin.init');
            }
        };

        MLBAdmin.init();
    });

    // Defensive: ensure Add Hotel button works even if feature script didn't bind.
    $(document).on('click', '#mlb-open-add-hotel', function(e){
        try{
            e.preventDefault();
            var $modal = $('#mlb-add-hotel-modal');
            if(!$modal.length){ console.debug('mlb-open-add-hotel clicked but modal not found'); return; }
            $modal.removeAttr('hidden');
            console.debug('mlb-open-add-hotel: modal shown by dashboard fallback');
        }catch(err){ console.error('dashboard add-hotel fallback error', err); }
    });
})( window, document, jQuery );

(function($){
    $(document).ready(function(){
        // JS gettext helper for admin scripts (exposed globally so other admin files can call it)
        window.mlbGettext = function mlbGettext(str) {
            try {
                if (typeof wp !== 'undefined' && wp.i18n && typeof wp.i18n.__ === 'function') {
                    return wp.i18n.__(str, 'mylighthouse-booker');
                }
            } catch (e) {}
            return str;
        };

        // Simple sprintf wrapper that uses wp.i18n.sprintf when available (exposed globally)
        window.mlbSprintf = function mlbSprintf(fmt) {
            try {
                if (typeof wp !== 'undefined' && wp.i18n && typeof wp.i18n.sprintf === 'function') {
                    var args = Array.prototype.slice.call(arguments, 1);
                    return wp.i18n.sprintf(fmt, args.length ? args[0] : '');
                }
            } catch (e) {}
            // fallback: replace first %s occurrences sequentially
            var out = fmt;
            for (var i = 1; i < arguments.length; i++) {
                out = out.replace('%s', arguments[i]);
            }
            return out;
        };
        // Helper: render admin-style notice safely into a host element
        function renderAdminNotice(hostEl, type, message, dismissible) {
                try {
                if (!hostEl) return;
                // Clear existing content using safe helper
                safeSetInnerHTML(hostEl, '');
                var wrapper = document.createElement('div');
                var noticeClass = 'notice notice-' + (type || 'info');
                if (dismissible) noticeClass += ' is-dismissible';
                wrapper.className = noticeClass;
                var p = document.createElement('p');
                p.textContent = message || '';
                wrapper.appendChild(p);
                hostEl.appendChild(wrapper);
            } catch (e) {
                try {
                    // Fallback: attempt to build the same structure via DOM APIs
                    var wrapperFallback = document.createElement('div');
                    var noticeClassFallback = 'notice notice-' + (type || 'info');
                    if (dismissible) noticeClassFallback += ' is-dismissible';
                    wrapperFallback.className = noticeClassFallback;
                    var pFallback = document.createElement('p');
                    pFallback.textContent = message || '';
                    wrapperFallback.appendChild(pFallback);
                    hostEl.appendChild(wrapperFallback);
                } catch (err) { /* ignore all */ }
            }
        }

        // Create and prepend an admin notice by cloning the server-provided template
        // Returns a jQuery-wrapped element for chaining. durationMs: optional auto-dismiss timeout.
        window.mlbCreateAdminNotice = function(message, type, durationMs) {
            try {
                var tpl = document.getElementById('mlb-admin-notice-template');
                var host = document.getElementById('mlb-dashboard-main');
                if (!host) {
                    // fallback: alert
                    if (durationMs === 0) alert(message); 
                    return null;
                }
                if (tpl && tpl.content && tpl.content.firstElementChild) {
                    var node = tpl.content.firstElementChild.cloneNode(true);
                    var cls = 'notice notice-' + (type || 'success');
                    if (typeof durationMs === 'number' && durationMs > 0) cls += ' is-dismissible';
                    node.className = cls;
                    var p = node.querySelector('p'); if (p) p.textContent = message || '';
                    host.insertBefore(node, host.firstChild);
                    var $node = $(node);
                    if (typeof durationMs === 'number' && durationMs > 0) {
                        setTimeout(function(){ $node.fadeOut(400, function(){ $node.remove(); }); }, durationMs);
                    }
                    return $node;
                }
            } catch (e){ /* fall through */ }
            // Fallback to jQuery-built notice
            var $note = $('<div class="notice notice-' + (type || 'success') + ' is-dismissible"><p>' + (message || '') + '</p></div>');
            var $main = $('#mlb-dashboard-main');
            if($main.length) $main.prepend($note);
            if (typeof durationMs === 'number' && durationMs > 0) setTimeout(function(){ $note.fadeOut(400, function(){ $note.remove(); }); }, durationMs);
            return $note;
        };

            // Safely set inner HTML of a host element by parsing and appending nodes.
            // Falls back to direct assignment if parsing fails.
            function safeSetInnerHTML(hostEl, htmlString){
                if(!hostEl) return;
                try{
                    var parser = new DOMParser();
                    var doc = parser.parseFromString('<div>' + (htmlString || '') + '</div>', 'text/html');
                    var container = doc.body.firstChild;
                    while(hostEl.firstChild){ hostEl.removeChild(hostEl.firstChild); }
                    while(container && container.firstChild){ hostEl.appendChild(container.firstChild); }
                    return;
                }catch(e){
                    try{ hostEl.innerHTML = htmlString; }catch(err){}
                }
            }

        // Global safety-net: intercept form submissions for known AJAX actions
        // If a fragment-specific manager is available and has already bound the form,
        // prefer the manager; otherwise handle the submit via fetch so the page
        // doesn't perform a full reload.
        $(document).on('submit', 'form', function(e){
            try{
                var $form = $(this);
                if($form.find('input[name="action"][value="mlb_save_hotel"]').length){
                    // If a hotels manager exists and the form is inside its root, let it handle it
                    if(window.mlb_hotels_manager && $form.closest(window.mlb_hotels_manager.root).length){
                        return; // manager will handle
                    }
                    // Otherwise intercept and perform AJAX save (mirrors hotels manager logic)
                    e.preventDefault();
                    var btn = $form.find('button[type=submit]').first();
                    var origText = btn ? btn.text() : '';
                    if(btn){ btn.prop('disabled', true); btn.text( mlbGettext('Saving...') ); }
                    var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                    if(!ajaxUrl){ if(btn){ btn.prop('disabled', false); btn.text(origText); } alert( mlbGettext('AJAX URL not available') ); return false; }
                    var fd = new FormData($form[0]);
                    fetch(ajaxUrl, {method:'POST', credentials:'same-origin', body: fd}).then(function(r){ return r.json(); }).then(function(json){
                        if(!json || !json.success){ alert((json && json.data && json.data.message) ? json.data.message : mlbGettext('Save failed')); return; }
                        var msg = (json.data && json.data.message) ? json.data.message : mlbGettext('Hotel saved');
                        var $main = $('#mlb-dashboard-main');
                        if($main.length){ mlbCreateAdminNotice(msg, 'success', 3000); } else { alert(msg); }
                        if (json.data && json.data.html) {
                            try {
                                var html = json.data.html;
                                var $node = $(html);
                                var hid = json.data.hotel_id ? String(json.data.hotel_id) : null;
                                var $container = $('#hotels-container');
                                if (hid && $container.length) {
                                    var $existing = $container.find('.mlb-hotel-item[data-id="' + hid + '"]');
                                    if ($existing.length) { $existing.replaceWith($node); } else { $container.prepend($node); }
                                }
                            } catch (e) { console.error('Insert/replace hotel row failed', e); }
                        } else {
                            try{ $('.mlb-sidebar-link[data-content="hotels"]').trigger('click'); }catch(e){}
                        }
                    }).catch(function(err){ console.error('mlb_save_hotel fetch failed', err); alert( mlbGettext('Save request failed') ); }).finally(function(){ if(btn){ btn.prop('disabled', false); btn.text(origText); } });
                    return false;
                }

                if($form.find('input[name="action"][value="mlb_save_admin_settings"]').length){
                    // If settings manager bound this form, prefer it
                    if($form.data('mlb-bound')) return;
                    e.preventDefault();
                    var btn = $form.find('button[type=submit]').first();
                    var originalText = btn ? btn.text() : '';
                    if(btn) { btn.prop('disabled', true); btn.text( mlbGettext('Saving...') ); }
                    var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                        if(!ajaxUrl){ if(btn){ btn.prop('disabled', false); btn.text(originalText); } alert( mlbGettext('AJAX URL not available') ); return; }
                    var fd = new FormData($form[0]);
                    fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                        .then(function(r){ return r.json(); })
                        .then(function(json){
                            if(!json || !json.success){ var msg = (json && json.data && json.data.message) ? json.data.message : mlbGettext('Save failed'); var $main = $('#mlb-dashboard-main'); if($main.length){ $main.prepend('<div class="notice notice-error is-dismissible"><p>' + msg + '</p></div>'); } else { alert(msg); } }
                                else { var msg = (json.data && json.data.message) ? json.data.message : mlbGettext('Settings saved'); var $main = $('#mlb-dashboard-main'); if($main.length){ mlbCreateAdminNotice(msg, 'success', 3500); } else { alert(msg); } try{ $('.mlb-sidebar-link[data-content^="settings"]').trigger('click'); }catch(e){} }
                        })
                        .catch(function(err){ console.error('mlb_save_admin_settings failed', err); alert( mlbGettext('Save request failed') ); })
                        .finally(function(){ if(btn){ btn.prop('disabled', false); btn.text(originalText); } });
                    return false;
                }
            }catch(e){ console.error('global form submit interceptor error', e); }
        });
        function loadDashboardPage(slug, callback, section, subsection, contentKey){
            var target = $('#mlb-dashboard-main');
            // Reusable safe injection helper available to the whole loader
            function safeInject($target, htmlString){
                if(!$target || !$target.length) return;
                var host = $target[0];
                try{
                    var parser = new DOMParser();
                    var doc = parser.parseFromString('<div>' + htmlString + '</div>', 'text/html');
                    var container = doc.body.firstChild;
                    while(host.firstChild){ host.removeChild(host.firstChild); }
                    while(container.firstChild){ host.appendChild(container.firstChild); }
                    return true;
                }catch(e){
                    console.error('safeInject failed', e);
                    try{ $target.html(htmlString); return true; }catch(err){ throw e; }
                }
            }
            // Helper to render the tools status table (mirrors server-side inline script)
            function renderTable(tableStatus){
                if(!tableStatus) return '';
                    var html = '<table class="widefat fixed mlb-table mlb-tools-table"><thead><tr><th>Table</th><th>Status</th><th>Missing Columns</th></tr></thead><tbody>';
                Object.keys(tableStatus).forEach(function(tableLabel){
                    var info = tableStatus[tableLabel];
                    var status = (info && info.status) ? info.status : info;
                    var label;
                    if(status === 'exists') label = 'Exists';
                    else if(status === 'created') label = 'Created';
                    else if(status === 'failed') label = 'Failed';
                    else label = status || 'Unknown';
                    var missing = (info && info.missing && info.missing.length) ? info.missing.join(', ') : '&mdash;';
                    html += '<tr><td>' + tableLabel + '</td><td>' + label + '</td><td>' + missing + '</td></tr>';
                });
                html += '</tbody></table>';
                return html;
            }
            if(!slug) return;
            target.html('<div class="mlb-loading">Loading…</div>');
            // Prefer server-side fragment endpoint for robustness
            var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
            var nonce = (window.mlb_admin_params && mlb_admin_params.nonce) ? mlb_admin_params.nonce : '';

            if (ajaxUrl) {
                // Use POST to admin-ajax to request only the fragment
                // Determine a fragment key from the slug to request a smaller deterministic fragment
                var fragKey = 'wrap';
                // If a contentKey was provided (unified base page model), prefer that for granularity
                if (contentKey) {
                    if (contentKey.indexOf('settings') === 0) fragKey = 'settings';
                    else if (contentKey.indexOf('hotels') === 0) fragKey = 'hotels';
                    else if (contentKey.indexOf('tools') === 0) fragKey = 'tools';
                    else if (contentKey === 'dashboard') fragKey = 'dashboard';
                } else {
                    // Prefer the unified dashboard slug; allow server to configure fragment key names
                        var fk = (window.mlb_admin_params && mlb_admin_params.fragment_keys) ? mlb_admin_params.fragment_keys : {};
                        if (slug === 'mylighthouse-booker') {
                            fragKey = fk.dashboard || 'dashboard';
                        } else if (typeof slug === 'string') {
                            var s = slug.toLowerCase();
                            if (s.indexOf('settings') !== -1) fragKey = fk.settings || 'settings';
                            else if (s.indexOf('hotels') !== -1) fragKey = fk.hotels || 'hotels';
                            else if (s.indexOf('tools') !== -1) fragKey = fk.tools || 'tools';
                        }
                }

                fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                        body: 'action=mlb_get_admin_fragment&nonce=' + encodeURIComponent(nonce) + '&page=' + encodeURIComponent(slug) + '&fragment=' + encodeURIComponent(fragKey) + (contentKey ? '&content=' + encodeURIComponent(contentKey) : '')
                })
                .then(function(res){
                    if (!res.ok) {
                        // Try to read body as text for debugging
                        return res.text().then(function(txt){
                            console.error('AJAX fragment endpoint error', res.status, txt);
                            // fallback to full fetch
                            fallbackFetch();
                            throw new Error('AJAX fragment endpoint returned HTTP ' + res.status);
                        });
                    }
                    // If OK, try to parse JSON safely
                    return res.text().then(function(text){
                            try {
                                return JSON.parse(text);
                            } catch (err) {
                                // If the endpoint returned raw HTML (some servers/plugins may do this),
                                // attempt to detect and inject a fragment directly instead of falling
                                // back to a full page fetch which may 403 when subpages are unregistered.
                                var trimmed = text.trim();
                                if (trimmed.charAt(0) === '<') {
                                    // Try to extract a known plugin wrapper from the returned HTML
                                    try {
                                        var parser = new DOMParser();
                                        var doc = parser.parseFromString(text, 'text/html');
                                        var temp = doc.body || doc;
                                        // Prefer server-provided fragment selectors so client-side extraction
                                        // is guaranteed to match the server's extraction logic.
                                        var selectors = (window.mlb_admin_params && mlb_admin_params.fragment_selectors) ? (function(o){ var a=[]; for(var k in o){ if(Object.prototype.hasOwnProperty.call(o,k)) a.push(o[k]); } return a; })(mlb_admin_params.fragment_selectors) : [
                                            '.mlb-admin-sections',
                                            '.mlb-hotels-wrap',
                                            '.mlb-admin-wrap',
                                            '.mlb-tools-wrap',
                                            '.mlb-dashboard-wrap',
                                            '.wrap'
                                        ];
                                        var found = null;
                                        for (var i = 0; i < selectors.length; i++) {
                                            try {
                                                var node = temp.querySelector(selectors[i]);
                                                if (node) { found = node; break; }
                                            } catch (qerr) {
                                                // ignore invalid selector syntax and continue
                                                console.debug('Invalid selector while attempting to extract fragment:', selectors[i], qerr);
                                            }
                                        }
                                        if (found) {
                                            // Return an object shaped like the successful JSON response
                                            return { __raw_html: true, html: found.innerHTML };
                                        }
                                    } catch (e) {
                                        console.error('Error extracting fragment from raw HTML response', e);
                                    }
                                }

                                console.error('Failed to parse JSON from fragment endpoint:', err, text);
                                // fallback to full page fetch as a last resort
                                fallbackFetch();
                                // Throw to break the current promise chain
                                throw err;
                            }
                        });
                })
                    .then(function(json){
                        // Safe HTML injection helper: parse HTML string into nodes and append
                        function safeInject($target, htmlString){
                            if(!$target || !$target.length) return;
                            var host = $target[0];
                            try{
                                // Use DOMParser to avoid jQuery internals attempting invalid append operations
                                var parser = new DOMParser();
                                // Wrap in a container to ensure a single root
                                var doc = parser.parseFromString('<div>' + htmlString + '</div>', 'text/html');
                                var container = doc.body.firstChild;
                                // Clear existing content
                                while(host.firstChild){ host.removeChild(host.firstChild); }
                                // Move nodes from parsed container into the target host
                                while(container.firstChild){ host.appendChild(container.firstChild); }
                                return true;
                            }catch(injectErr){
                                console.error('safeInject failed', injectErr);
                                // Try a last-resort jQuery .html as fallback
                                try{ $target.html(htmlString); return true; }catch(e){ throw injectErr; }
                            }
                        }

                        // Handle the case where we returned a synthetic object for raw HTML
                                if (json && json.__raw_html && json.html) {
                                    try {
                                        safeInject(target, json.html);
                                    } catch (domErr) {
                                        console.error('Error injecting raw fragment HTML into dashboard:', domErr);
                                        try{ safeInject(target, '<div class="mlb-error">Error rendering fragment. Check the console for details.</div>'); }catch(e){ console.error(e); }
                                        var pre = document.createElement('pre');
                                        pre.style.whiteSpace = 'pre-wrap';
                                        pre.style.maxHeight = '300px';
                                        pre.style.overflow = 'auto';
                                        pre.textContent = json.html;
                                        if (target && target.length && target[0]) target[0].appendChild(pre);
                                        return;
                                    }
                            if (typeof callback === 'function') {
                                try { callback(section, subsection); } catch (e) { console.error(e); }
                            }
                            // Initialize any dynamic behaviors in the injected fragment
                            try { initToolsFragment(); } catch (e) { /* ignore */ }
                            try { initHotelsFragment(); } catch (e) { /* ignore */ }
                            try { initSettingsFragment(); } catch (e) { /* ignore */ }
                            return;
                        }

                        if (json && json.success && json.data && json.data.html) {
                            try {
                                safeInject(target, json.data.html);
                        } catch (domErr) {
                            console.error('Error injecting fragment HTML into dashboard:', domErr);
                            // Show a visible error message and a safe pre with the HTML for debugging
                            try{ safeInject(target, '<div class="mlb-error">Error rendering fragment. Check the console for details.</div>'); }catch(e){ console.error(e); }
                            var pre = document.createElement('pre');
                            pre.style.whiteSpace = 'pre-wrap';
                            pre.style.maxHeight = '300px';
                            pre.style.overflow = 'auto';
                            pre.textContent = json.data.html;
                            if (target && target.length && target[0]) target[0].appendChild(pre);
                            return;
                        }
                        if(typeof callback === 'function'){
                            try{ callback(section, subsection); }catch(e){ console.error(e); }
                            // Initialize behaviors for known fragments
                            try { initToolsFragment(); } catch (e) { /* ignore */ }
                            try { initHotelsFragment(); } catch (e) { /* ignore */ }
                            try { initSettingsFragment(); } catch (e) { /* ignore */ }
                            } else {
                            // If no callback was provided (top-level clicks), still attempt to show the requested section
                            try {
                                // If there's no manager bound or it doesn't expose showSection, try to instantiate a fresh one
                                if ((!window.mlb_settings_manager || typeof window.mlb_settings_manager.showSection !== 'function') && typeof window.MLBSettingsManager === 'function') {
                                    try { window.mlb_settings_manager = new window.MLBSettingsManager(); } catch (err) { console.error('Failed to instantiate MLBSettingsManager', err); }
                                }

                                if (section && window.mlb_settings_manager && typeof window.mlb_settings_manager.showSection === 'function') {
                                    window.mlb_settings_manager.showSection(section);
                                }
                            } catch (e) { console.error(e); }
                        }
                        return;
                    }
                    // Otherwise fall back to the previous fetch approach
                    console.warn('AJAX fragment endpoint did not return HTML, falling back to full page fetch.', json);
                    fallbackFetch();
                })
                .catch(function(err){
                    console.warn('AJAX fragment fetch failed, falling back to full page fetch.', err);
                    // If fallbackFetch hasn't already been called, call it now
                    try { fallbackFetch(); } catch (e) { console.error(e); }
                });
            } else {
                // No ajaxUrl available; use fallback
                fallbackFetch();
            }

        // Initialize tools fragment behaviors (run after fragment is injected)
        function initToolsFragment(){
            try{
                function escapeHtml(str){
                    var div = document.createElement('div');
                    div.textContent = (str === null || str === undefined) ? '' : String(str);
                    return div.innerHTML;
                }

                function escapeAttr(str){
                    if(str === null || str === undefined) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                }

                var settingsLabels = {
                    mlb_booking_page_url: 'Booking Page URL',
                    mlb_display_mode: 'Display Mode',
                    mlb_spinner_image_url: 'Spinner Background'
                };

                function shouldSkipExisting(){
                    var el = document.getElementById('mlb-import-skip-existing');
                    if(!el) return true;
                    return !!el.checked;
                }

                function buildSelectionList(data){
                    if(!data || !data.hotels || !data.hotels.length){
                        return '<p>' + mlbGettext('No hotels detected in this file.') + '</p>';
                    }
                    var html = '<div class="mlb-import-selection">';
                    html += '<div class="mlb-import-selection-controls">'
                        + '<label class="mlb-checkbox"><input type="checkbox" id="mlb-preview-select-all" class="mlb-import-checkbox" checked /> '
                        + mlbGettext('Select All') + '</label>'
                        + '</div>';
                    data.hotels.forEach(function(h){
                        var badge = '<span class="mlb-badge ' + (h.action === 'create' ? 'created' : 'updated') + '">' + escapeHtml(h.action) + '</span>';
                        var skipTag = h.will_skip ? '<span class="mlb-badge warning">' + mlbGettext('Will skip (existing)') + '</span>' : '';
                        var label = h.name ? (h.name + ' (' + h.hotel_id + ')') : h.hotel_id;
                        html += '<div class="mlb-import-select-group">';
                        html += '<label class="mlb-checkbox mlb-import-checkbox-wrapper">'
                            + '<input type="checkbox" class="mlb-import-checkbox mlb-import-checkbox-hotel" data-entity="hotel" data-hotel="' + escapeAttr(h.hotel_id) + '" checked /> '
                            + '<span class="mlb-import-label">' + escapeHtml(label) + '</span>'
                            + ' ' + badge + ' ' + skipTag
                            + '</label>';
                        if(h.rooms && h.rooms.length){
                            html += '<div class="mlb-import-sublist"><strong>' + mlbGettext('Rooms') + '</strong><ul>';
                            h.rooms.forEach(function(r){
                                var roomBadge = '<span class="mlb-badge ' + (r.action === 'create' ? 'created' : 'updated') + '">' + escapeHtml(r.action) + '</span>';
                                var roomSkip = r.will_skip ? '<span class="mlb-badge warning">' + mlbGettext('Will skip (existing)') + '</span>' : '';
                                html += '<li><label class="mlb-checkbox"><input type="checkbox" class="mlb-import-checkbox mlb-import-checkbox-room" data-entity="room" data-hotel="' + escapeAttr(h.hotel_id) + '" data-room="' + escapeAttr(r.room_id) + '" checked /> '
                                    + escapeHtml(r.room_id) + ' ' + roomBadge + ' ' + roomSkip + '</label></li>';
                            });
                            html += '</ul></div>';
                        }
                        if(h.specials && h.specials.length){
                            html += '<div class="mlb-import-sublist"><strong>' + mlbGettext('Specials') + '</strong><ul>';
                            h.specials.forEach(function(s){
                                var specialBadge = '<span class="mlb-badge ' + (s.action === 'create' ? 'created' : 'updated') + '">' + escapeHtml(s.action) + '</span>';
                                var specialSkip = s.will_skip ? '<span class="mlb-badge warning">' + mlbGettext('Will skip (existing)') + '</span>' : '';
                                html += '<li><label class="mlb-checkbox"><input type="checkbox" class="mlb-import-checkbox mlb-import-checkbox-special" data-entity="special" data-hotel="' + escapeAttr(h.hotel_id) + '" data-special="' + escapeAttr(s.special_id) + '" checked /> '
                                    + escapeHtml(s.special_id) + ' ' + specialBadge + ' ' + specialSkip + '</label></li>';
                            });
                            html += '</ul></div>';
                        }
                        html += '</div>';
                    });
                    html += '</div>';
                    return html;
                }

                function bindImportSelectionHandlers(root){
                    if(!root) return;
                    var container = root.querySelector('.mlb-import-selection');
                    if(!container) return;

                    var selectAll = container.querySelector('#mlb-preview-select-all');
                    if(selectAll && !selectAll.dataset.mlbInit){
                        selectAll.addEventListener('change', function(){
                            var boxes = container.querySelectorAll('input.mlb-import-checkbox');
                            boxes.forEach(function(box){ box.checked = !!selectAll.checked; });
                        });
                        selectAll.dataset.mlbInit = '1';
                    }

                    container.addEventListener('change', function(e){
                        var target = e.target;
                        if(!target || !target.classList || !target.classList.contains('mlb-import-checkbox')) return;
                        var hotelId = target.getAttribute('data-hotel');
                        if(target.classList.contains('mlb-import-checkbox-hotel')){
                            var state = target.checked;
                            container.querySelectorAll('.mlb-import-checkbox-room, .mlb-import-checkbox-special').forEach(function(box){
                                if(box.getAttribute('data-hotel') === hotelId){ box.checked = state; }
                            });
                        } else if(hotelId){
                            var parent = null;
                            container.querySelectorAll('.mlb-import-checkbox-hotel').forEach(function(box){
                                if(box.getAttribute('data-hotel') === hotelId){ parent = box; }
                            });
                            if(!parent) return;
                            if(target.checked){
                                parent.checked = true;
                                return;
                            }
                            var anyChecked = false;
                            container.querySelectorAll('.mlb-import-checkbox-room, .mlb-import-checkbox-special').forEach(function(box){
                                if(box.getAttribute('data-hotel') === hotelId && box.checked){ anyChecked = true; }
                            });
                            if(!anyChecked){ parent.checked = false; }
                        }
                    });
                }

                function collectImportSelection(){
                    var host = document.getElementById('mlb-import-result');
                    if(!host) return null;
                    var hotelBoxes = host.querySelectorAll('.mlb-import-checkbox-hotel');
                    if(!hotelBoxes.length) return null;
                    var selection = { hotels: {} };
                    hotelBoxes.forEach(function(box){
                        var hotelId = box.getAttribute('data-hotel');
                        if(!hotelId) return;
                        var hotelEntry = { include: !!box.checked, rooms: {}, specials: {} };
                        host.querySelectorAll('.mlb-import-checkbox-room').forEach(function(roomBox){
                            if(roomBox.getAttribute('data-hotel') === hotelId){
                                var roomId = roomBox.getAttribute('data-room');
                                if(roomId){ hotelEntry.rooms[roomId] = !!roomBox.checked; }
                            }
                        });
                        host.querySelectorAll('.mlb-import-checkbox-special').forEach(function(specialBox){
                            if(specialBox.getAttribute('data-hotel') === hotelId){
                                var specialId = specialBox.getAttribute('data-special');
                                if(specialId){ hotelEntry.specials[specialId] = !!specialBox.checked; }
                            }
                        });
                        selection.hotels[hotelId] = hotelEntry;
                    });
                    return selection;
                }

                var ajaxBtn = document.getElementById('mlb-tools-ajax-btn');
                if(ajaxBtn && !ajaxBtn.dataset.mlbInit){
                    ajaxBtn.addEventListener('click', function(e){
                        e.preventDefault();
                        // reuse the generic form submit handler below by triggering the form if present
                        var form = ajaxBtn.closest('form');
                        if(form){ form.dispatchEvent(new Event('submit', {cancelable:true})); return; }

                        var result = document.getElementById('mlb-tools-ajax-result');
                        var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                        var ajaxNonce = ajaxBtn.getAttribute('data-ajax-nonce') || '';
                        ajaxBtn.disabled = true;
                        ajaxBtn.textContent = (window.mlb_admin_params && mlb_admin_params.msg_running) ? mlb_admin_params.msg_running : mlbGettext('Running...');
                        if(result) safeSetInnerHTML(result, '');

                        var fd = new FormData();
                        fd.append('action', 'mlb_tools_check_tables');
                        fd.append('nonce', ajaxNonce);
                        // Debug: log form data entries
                        try{
                            var entries = [];
                            fd.forEach(function(v,k){ entries.push(k+"="+(v && v.name ? '[file:'+v.name+']' : v)); });
                            console.debug('mlb_tools_check_tables: POST', ajaxUrl, entries);
                        }catch(e){ console.debug('mlb_tools_check_tables: unable to enumerate fd', e); }

                        fetch(ajaxUrl, {method:'POST', credentials:'same-origin', body: fd}).then(function(resp){
                            return resp.text().then(function(text){
                                console.debug('mlb_tools_check_tables response', resp.status, text);
                                if(!resp.ok){
                                    try{ var parsed = JSON.parse(text); }catch(e){ var parsed = null; }
                                    if(result) renderAdminNotice(result, 'error', (parsed && parsed.data && parsed.data.message) ? parsed.data.message : ('Server returned HTTP ' + resp.status), true);
                                    throw new Error('HTTP ' + resp.status);
                                }

                                try{ var json = JSON.parse(text); }catch(e){
                                    console.error('mlb_tools_check_tables: invalid JSON', e, text);
                                    if(result) renderAdminNotice(result, 'error', 'Invalid server response', true);
                                    throw e;
                                }

                                if(!json || !json.success){
                                    if(result) renderAdminNotice(result, 'error', (json && json.data && json.data.message) ? json.data.message : mlbGettext('An error occurred'), true);
                                } else {
                                    var data = json.data || {};
                                    var html = '<p><strong>' + (data.message || 'Table check completed') + '</strong></p>';
                                    html += renderTable ? renderTable(data.tables || {}) : '';
                                    if(result) safeSetInnerHTML(result, html);
                                }
                            });
                        }).catch(function(err){
                            console.error('mlb_tools_check_tables fetch failed', err);
                            if(result) renderAdminNotice(result, 'error', mlbGettext('AJAX request failed'), true);
                        }).finally(function(){
                            ajaxBtn.disabled = false;
                            ajaxBtn.textContent = (window.mlb_admin_params && mlb_admin_params.msg_run_ajax_check) ? mlb_admin_params.msg_run_ajax_check : mlbGettext('Run AJAX Table Check');
                        });
                    });
                    ajaxBtn.dataset.mlbInit = '1';
                }

                if(!document.body.dataset.mlbToolsAjaxBound){
                    document.addEventListener('submit', function(e){
                        var form = e.target;
                        if(!form || !form.matches || !form.matches('form[data-mlb-tools-ajax="1"]')) return;
                        e.preventDefault();
                        var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                        var actionInput = form.querySelector('input[name="action"][value^="mlb_tools_"]');
                        var actionName = actionInput ? actionInput.value : '';
                        if(!actionName) return;
                        var resultSelector = form.getAttribute('data-result-target');
                        var result = resultSelector ? document.querySelector(resultSelector) : form.querySelector('.mlb-tools-result') || document.getElementById('mlb-tools-ajax-result');
                        if(result) safeSetInnerHTML(result, '');
                        var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                        var originalText = submitBtn && submitBtn.tagName === 'BUTTON' ? submitBtn.textContent : null;
                        if(submitBtn){
                            submitBtn.disabled = true;
                            if(originalText) submitBtn.textContent = mlbGettext('Running...');
                        }
                        var fd = new FormData(form);
                        fd.set('action', actionName);
                        if(!fd.has('nonce')){
                            var nonceInput = form.querySelector('input[name="mlb_tools_nonce"], input[name="_wpnonce"]');
                            if(nonceInput) fd.set('nonce', nonceInput.value || '');
                        }
                        fetch(ajaxUrl, {method:'POST', credentials:'same-origin', body: fd}).then(function(r){ return r.json(); }).then(function(json){
                            if(!json || !json.success){
                                if(result) renderAdminNotice(result, 'error', ((json && json.data && json.data.message) ? json.data.message : mlbGettext('An error occurred')), true);
                            } else {
                                var data = json.data || {};
                                var html = '<p><strong>' + (data.message || mlbGettext('Action completed.')) + '</strong></p>';
                                if(actionName === 'mlb_tools_check_tables' && data.tables) {
                                    html += renderTable ? renderTable(data.tables) : '';
                                }
                                if(result) safeSetInnerHTML(result, html);
                            }
                        }).catch(function(err){
                            console.error('tools form ajax failed', err);
                            if(result) renderAdminNotice(result, 'error', mlbGettext('AJAX request failed'), true);
                        }).finally(function(){
                            if(submitBtn){
                                submitBtn.disabled = false;
                                if(originalText) submitBtn.textContent = originalText;
                            }
                        });
                    });
                    document.body.dataset.mlbToolsAjaxBound = '1';
                }

                // Wire export form for reliable downloads without relying on fetch blobs
                var exportForm = document.getElementById('mlb-export-form');
                var exportBtn = document.getElementById('mlb-export-btn');
                var exportFrame = document.getElementById('mlb-export-frame');
                if(exportForm && exportBtn && !exportForm.dataset.mlbInit){
                    var defaultLabel = exportBtn.textContent;
                    var resetTimer = null;

                    function resetExportButton(message, type){
                        if(resetTimer){ clearTimeout(resetTimer); resetTimer = null; }
                        exportBtn.disabled = false;
                        exportBtn.textContent = defaultLabel;
                        exportForm.dataset.submitting = '';
                        if(message && window.mlbCreateAdminNotice){
                            mlbCreateAdminNotice(message, type || 'success', 4000);
                        }
                    }

                    exportForm.addEventListener('submit', function(){
                        exportBtn.disabled = true;
                        exportBtn.textContent = (window.mlb_admin_params && mlb_admin_params.msg_preparing) ? mlb_admin_params.msg_preparing : mlbGettext('Preparing...');
                        exportForm.dataset.submitting = '1';
                        if(resetTimer){ clearTimeout(resetTimer); }
                        resetTimer = setTimeout(function(){
                            resetExportButton(mlbGettext('Export request sent. If no download started, please check your popup blocker.'), 'warning');
                        }, 20000);
                    });

                    if(exportFrame){
                        exportFrame.dataset.ready = '0';
                        exportFrame.addEventListener('load', function(){
                            // First load is the initial about:blank, ignore it
                            if(exportFrame.dataset.ready !== '1'){
                                exportFrame.dataset.ready = '1';
                                return;
                            }
                            if(!exportForm.dataset.submitting){
                                return; // nothing in-flight
                            }
                            var iframeDoc = exportFrame.contentDocument || exportFrame.contentWindow && exportFrame.contentWindow.document;
                            var message = (window.mlb_admin_params && mlb_admin_params.msg_export_ready) ? mlb_admin_params.msg_export_ready : mlbGettext('Export complete.');
                            var type = 'success';
                            if(iframeDoc && iframeDoc.body){
                                var bodyText = iframeDoc.body.textContent || '';
                                if(bodyText.trim() && bodyText.trim() !== '0'){
                                    try{
                                        var parsed = JSON.parse(bodyText);
                                        if(parsed && parsed.data && parsed.data.message){ message = parsed.data.message; }
                                        if(parsed && parsed.success === false){ type = 'error'; }
                                    }catch(err){
                                        // Non-JSON response likely means WordPress printed an error
                                        if(bodyText.length < 300){ message = bodyText.trim(); type = 'error'; }
                                    }
                                }
                            }
                            resetExportButton(message, type);
                        });
                    }

                    exportForm.dataset.mlbInit = '1';
                }

                var importBtn = document.getElementById('mlb-import-btn');
                var previewBtn = document.getElementById('mlb-preview-btn');
                var importFile = document.getElementById('mlb-import-file');
                var importResult = document.getElementById('mlb-import-result');
                var importApplySettings = document.getElementById('mlb-import-apply-settings');

                function shouldApplySettings(){
                    return !importApplySettings || importApplySettings.checked;
                }

                if(importBtn && !importBtn.dataset.mlbInit){
                    importBtn.addEventListener('click', function(e){
                        e.preventDefault();
                        if(!importFile || !importFile.files || !importFile.files.length){ if(importResult) renderAdminNotice(importResult, 'error', ((window.mlb_admin_params && mlb_admin_params.msg_select_json_import) ? mlb_admin_params.msg_select_json_import : 'Please select a JSON file to import.'), true); return; }
                        var file = importFile.files[0]; var reader = new FileReader();
                        reader.onload = function(evt){
                            try{ var json = JSON.parse(evt.target.result); } catch(err){ if(importResult) renderAdminNotice(importResult, 'error', ((window.mlb_admin_params && mlb_admin_params.msg_invalid_json) ? mlb_admin_params.msg_invalid_json : 'Invalid JSON file.'), true); return; }
                                previewBtn.disabled = true; previewBtn.textContent = (window.mlb_admin_params && mlb_admin_params.msg_previewing) ? mlb_admin_params.msg_previewing : mlbGettext('Previewing...');
                            var fd = new FormData();
                            fd.append('action','mlb_tools_import');
                            fd.append('payload', JSON.stringify(json));
                            fd.append('nonce', importBtn.getAttribute('data-import-nonce') || '');
                            fd.append('apply_settings', shouldApplySettings() ? '1' : '0');
                            fd.append('skip_existing', shouldSkipExisting() ? '1' : '0');
                            var selectionPayload = collectImportSelection();
                            if(selectionPayload){
                                try{ fd.append('selection', JSON.stringify(selectionPayload)); }catch(selErr){ console.warn('Failed to serialize selection', selErr); }
                            }
                            var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                            fetch(ajaxUrl, {method:'POST', credentials:'same-origin', body: fd}).then(function(r){ return r.json(); }).then(function(json){
                                if(!json || !json.success){ if(importResult) renderAdminNotice(importResult, 'error', (json && json.data && json.data.message) ? json.data.message : ((window.mlb_admin_params && mlb_admin_params.msg_import_failed) ? mlb_admin_params.msg_import_failed : 'Import failed'), true); }
                                else { var resp = json.data || {}; var html = '<div class="notice notice-success"><p>' + (resp.message || ((window.mlb_admin_params && mlb_admin_params.msg_import_succeeded) ? mlb_admin_params.msg_import_succeeded : 'Import succeeded')) + '</p></div>'; if(resp.settings && Object.keys(resp.settings).length){ html += '<div class="mlb-settings-applied"><h4>' + mlbGettext('General Settings Updated') + '</h4><ul>'; Object.keys(resp.settings).forEach(function(key){ var label = settingsLabels[key] || key; html += '<li><strong>' + escapeHtml(label) + ':</strong> ' + escapeHtml(resp.settings[key] || '') + '</li>'; }); html += '</ul></div>'; } if(resp.log){ html += '<div class="mlb-import-log"><h4>Import Log</h4><pre>' + JSON.stringify(resp.log, null, 2) + '</pre></div>'; } if(importResult) safeSetInnerHTML(importResult, html); }
                            }).catch(function(){ if(importResult) renderAdminNotice(importResult, 'error', mlbGettext('Import request failed'), true); }).finally(function(){ importBtn.disabled=false; importBtn.textContent=(window.mlb_admin_params && mlb_admin_params.msg_import_json) ? mlb_admin_params.msg_import_json : mlbGettext('Import JSON'); });
                        };
                        reader.readAsText(file);
                    });
                    importBtn.dataset.mlbInit = '1';
                }

                if(previewBtn && !previewBtn.dataset.mlbInit){
                    previewBtn.addEventListener('click', function(e){
                        e.preventDefault();
                        if(!importFile || !importFile.files || !importFile.files.length){ if(importResult) renderAdminNotice(importResult, 'error', ((window.mlb_admin_params && mlb_admin_params.msg_select_json_import) ? mlb_admin_params.msg_select_json_import : 'Please select a JSON file to preview.'), true); return; }
                        var file = importFile.files[0]; var reader = new FileReader();
                        reader.onload = function(evt){
                            try{ var json = JSON.parse(evt.target.result); } catch(err){ if(importResult) renderAdminNotice(importResult, 'error', ((window.mlb_admin_params && mlb_admin_params.msg_invalid_json) ? mlb_admin_params.msg_invalid_json : 'Invalid JSON file.'), true); return; }
                            previewBtn.disabled = true; previewBtn.textContent = (window.mlb_admin_params && mlb_admin_params.msg_previewing) ? mlb_admin_params.msg_previewing : mlbGettext('Previewing...');
                            var fd = new FormData();
                            fd.append('action','mlb_tools_preview_import');
                            fd.append('payload', JSON.stringify(json));
                            fd.append('nonce', previewBtn.getAttribute('data-preview-nonce') || '');
                            fd.append('apply_settings', shouldApplySettings() ? '1' : '0');
                            fd.append('skip_existing', shouldSkipExisting() ? '1' : '0');
                            var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                            fetch(ajaxUrl, {method:'POST', credentials:'same-origin', body: fd}).then(function(r){ return r.json(); }).then(function(json){
                                if(!json || !json.success){ if(importResult) renderAdminNotice(importResult, 'error', (json && json.data && json.data.message) ? json.data.message : 'Preview failed', true); }
                                else { var data = json.data || {}; var html = '<h3>Import Preview</h3>'; html += '<p>' + (data.message || '') + '</p>'; if(data.hotels && data.hotels.length){ html += '<h4>Hotels</h4><div class="mlb-import-preview"><table class="mlb-table mlb-preview-table"><thead><tr><th>Hotel ID</th><th>Action</th><th>Details</th></tr></thead><tbody>'; data.hotels.forEach(function(h){ var details = ''; if(h.rooms && h.rooms.length) details += '<div>Rooms: ' + h.rooms.map(function(r){ return escapeHtml(r.room_id) + ' (' + escapeHtml(r.action) + ')'; }).join(', ') + '</div>'; if(h.specials && h.specials.length) details += '<div>Specials: ' + h.specials.map(function(s){ return escapeHtml(s.special_id) + ' (' + escapeHtml(s.action) + ')'; }).join(', ') + '</div>'; html += '<tr><td>' + escapeHtml(h.hotel_id) + '</td><td><span class="mlb-badge ' + (h.action === 'create' ? 'created' : 'updated') + '">' + escapeHtml(h.action) + '</span></td><td>' + details + '</td></tr>'; }); html += '</tbody></table></div>'; }
                                    if(data.settings && data.settings.values && Object.keys(data.settings.values).length){ html += '<h4>' + mlbGettext('General Settings') + '</h4>'; html += '<p>' + (data.settings.apply ? mlbGettext('These settings will be applied.') : mlbGettext('Settings detected but checkbox is disabled.')) + '</p>'; html += '<ul class="mlb-settings-preview">'; Object.keys(data.settings.values).forEach(function(key){ var label = settingsLabels[key] || key; html += '<li><strong>' + escapeHtml(label) + ':</strong> ' + escapeHtml(data.settings.values[key] || '') + '</li>'; }); html += '</ul>'; }
                                        if(importResult) safeSetInnerHTML(importResult, html); }
                            }).catch(function(){ if(importResult) renderAdminNotice(importResult, 'error', ((window.mlb_admin_params && mlb_admin_params.msg_preview_request_failed) ? mlb_admin_params.msg_preview_request_failed : 'Preview request failed'), true); }).finally(function(){ previewBtn.disabled=false; previewBtn.textContent=(window.mlb_admin_params && mlb_admin_params.msg_preview_import) ? mlb_admin_params.msg_preview_import : 'Preview Import'; });
                        };
                        reader.readAsText(file);
                    });
                    previewBtn.dataset.mlbInit = '1';
                }
            }catch(e){ console.error('initToolsFragment error', e); }
        }

        // Initialize hotels fragment behaviors (run after hotels fragment is injected)
        function initHotelsFragment(){
            try{
                console.debug('initHotelsFragment: entry', { hasManager: !!window.mlb_hotels_manager, time: Date.now() });
                var attempts = 0;
                var maxAttempts = 12; // retry for ~6 seconds (500ms interval)
                var waitForManager = setInterval(function(){
                    attempts++;
                    if(typeof window.MLBHotelsManager === 'function'){
                        try{
                            if (!window.mlb_hotels_manager) {
                                console.debug('initHotelsFragment: creating MLBHotelsManager');
                                window.mlb_hotels_manager = new window.MLBHotelsManager('#mlb-dashboard-main');
                            } else {
                                console.debug('initHotelsFragment: MLBHotelsManager already exists');
                            }
                            if(typeof window.mlb_hotels_manager.init === 'function') {
                                console.debug('initHotelsFragment: calling init on manager');
                                window.mlb_hotels_manager.init();
                            }
                        }catch(e){ console.error('initHotelsFragment error', e); }
                        clearInterval(waitForManager);
                        return;
                    }
                    if(attempts >= maxAttempts){
                        console.warn('initHotelsFragment: MLBHotelsManager not available after retries');
                        clearInterval(waitForManager);
                    }
                }, 500);
                // Attach any delegated handlers that rely on static selectors inside the injected fragment
            }catch(e){ console.error('initHotelsFragment error', e); }
        }

        // Initialize settings fragment behaviors (run after settings fragment is injected)
        function initSettingsFragment(){
            try{
                if(typeof window.MLBSettingsManager === 'function'){
                    try{
                        // Always create a fresh instance to ensure event handlers rebind
                        window.mlb_settings_manager = new window.MLBSettingsManager();
                        if(typeof window.mlb_settings_manager.init === 'function') {
                            window.mlb_settings_manager.init();
                            console.debug('Settings manager initialized');
                        }
                    }catch(e){ console.error('initSettingsFragment error', e); }
                }
            }catch(e){ console.error('initSettingsFragment error', e); }
        }

        // Ensure tool forms on initial page load get bound even before AJAX fragments run
        initToolsFragment();

            function fallbackFetch(){
                    var base = (window.mlb_admin_params && mlb_admin_params.admin_url) ? mlb_admin_params.admin_url : '';
                    // Instead of requesting per-feature admin slugs (which may not be registered
                    // and can return 403), always request the unified dashboard page and pass
                    // the `content` query param so the server-side fragment logic can render
                    // the requested section. This avoids permission/registration mismatches.
                    var unifiedPage = 'mylighthouse-booker';
                    var url = base + 'admin.php?page=' + encodeURIComponent(unifiedPage) + (contentKey ? '&content=' + encodeURIComponent(contentKey) : '');
                    console.debug('fallbackFetch requesting URL', url);
                // Fetch the page HTML and extract the most relevant plugin fragment
                fetch(url, {credentials: 'same-origin'})
                    .then(function(res){ return res.text(); })
                    .then(function(html){
                        var parser = new DOMParser();
                        var doc = parser.parseFromString(html, 'text/html');
                        var temp = doc.body || doc;

                        // Try known plugin wrappers in order
                        var selectors = [
                            '.mlb-admin-sections',
                            '.mlb-hotels-wrap',
                            '.mlb-admin-wrap',
                            '.mlb-tools-wrap',
                            '.mlb-dashboard-wrap'
                        ];

                        var found = null;
                        for(var i=0;i<selectors.length;i++){
                            var node = temp.querySelector(selectors[i]);
                            if(node){ found = node; break; }
                        }

                        if(found){
                            try{ safeInject(target, found.innerHTML); }catch(e){ console.error('fallback inject failed', e); target.html(found.innerHTML); }
                        } else {
                            // Fallback: look for .wrap with our plugin headings
                            var wrap = temp.querySelector('.wrap');
                            if(wrap){
                                try{ safeInject(target, wrap.innerHTML); }catch(e){ console.error('fallback inject failed', e); target.html(wrap.innerHTML); }
                            } else {
                                try{ safeInject(target, html); }catch(e){ console.error('fallback inject failed', e); target.html(html); }
                            }
                        }
                        // Call callback after content injected (if provided)
                        if(typeof callback === 'function'){
                            try{ callback(section, subsection); }catch(e){ console.error(e); }
                        }
                    })
                    .catch(function(err){
                        try{ safeInject(target, '<div class="mlb-error">Error loading page.</div>'); }catch(e){ target.html('<div class="mlb-error">Error loading page.</div>'); }
                        console.error(err);
                    });
            }
        }

        // Helper: update admin URL query params (page & content) without reloading
        function updateAdminUrl(page, contentKey){
            try{
                // Build a query string with page and content
                var url = new URL(window.location.href);
                var params = url.searchParams;
                if(page){ params.set('page', page); }
                if(contentKey){ params.set('content', contentKey); } else { params.delete('content'); }
                // Replace state to avoid adding history entries on every click
                var newUrl = url.pathname + '?' + params.toString() + (url.hash || '');
                history.replaceState(null, null, newUrl);
            }catch(err){ console.warn('Failed to update admin URL', err); }
        }

        $(document).on('click', '.mlb-sidebar-link', function(e){
            e.preventDefault();
            // Set active state for top-level links
            $('.mlb-sidebar-link').removeClass('active');
            $(this).addClass('active');

            var page = $(this).attr('data-page');
            var contentKey = $(this).attr('data-content') || null;
            var section = null;
            var subsection = null;
            if(contentKey){
                var parts = contentKey.split('-');
                if(parts[0] === 'settings'){
                    section = parts[1] || 'general';
                }
            }

            // Update the admin URL query param so the current content is shareable
            try{
                updateAdminUrl(page, contentKey);
            }catch(err){ console.warn(err); }

            // Load the page content regardless (submenus may rely on the loaded content)
            // Pass the clicked section so the settings manager can show the correct subsection
                loadDashboardPage(page, null, section, null, contentKey);
        });

        // Intercept hotel edit links to load edit form via dashboard fragment loader (SPA)
        $(document).on('click', '.mlb-edit-hotel', function(e){
            // Only intercept left-clicks without modifier keys
            if (e.which && e.which !== 1) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            e.preventDefault();

            var href = $(this).attr('href') || '';
            var contentKey = null;
            try {
                var tempUrl = new URL(href, window.location.href);
                contentKey = tempUrl.searchParams.get('content');
            } catch (err) {
                var m = href.match(/content=([^&]+)/);
                if (m) contentKey = decodeURIComponent(m[1]);
            }

            if (!contentKey) {
                // fallback to original navigation if we couldn't parse content
                window.location.href = href;
                return;
            }

            // mark Hotels top-level link active
            $('.mlb-sidebar-link').removeClass('active');
            $('.mlb-sidebar-link[data-content="hotels"]').addClass('active');

            // update URL and load fragment via loader
            try { updateAdminUrl('mylighthouse-booker', contentKey); } catch (err) { /* ignore */ }
            loadDashboardPage('mylighthouse-booker', null, null, null, contentKey);
        });

        // Intercept plugin back links to stay within the dashboard (e.g., back to hotels list)
        $(document).on('click', '.mlb-back-link', function(e){
            // Only intercept left-clicks without modifier keys
            if (e.which && e.which !== 1) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            e.preventDefault();

            var href = $(this).attr('href') || '';
            // prefer loading the hotels list fragment
            try { updateAdminUrl('mylighthouse-booker', 'hotels'); } catch (err) { /* ignore */ }
            loadDashboardPage('mylighthouse-booker', null, null, null, 'hotels');
        });

        // Make entire hotel list items clickable to open the edit fragment (but allow inner controls to work)
        $(document).on('click', '.mlb-hotel-item', function(e){
            // If the click started on an interactive element, don't hijack it
            var $target = $(e.target);
            if($target.closest('a, button, input, select, textarea, label').length) return;

            var $item = $(this);
            var contentKey = null;

            // Prefer an explicit edit link inside the item
            var $edit = $item.find('.mlb-edit-hotel').first();
            if($edit && $edit.length){
                var href = $edit.attr('href') || '';
                try {
                    var tempUrl = new URL(href, window.location.href);
                    contentKey = tempUrl.searchParams.get('content');
                } catch(err) {
                    var m = href.match(/content=([^&]+)/);
                    if(m) contentKey = decodeURIComponent(m[1]);
                }
            }

            // Fallback: check data attributes on the item itself
            if(!contentKey){
                contentKey = $item.data('content') || $item.attr('data-content') || null;
            }

            // Fallback: build content key from hotel id attribute if present
            if(!contentKey){
                var hid = $item.data('hotel-id') || $item.attr('data-hotel-id');
                if(hid) contentKey = 'hotels-edit-' + hid;
            }

            if(!contentKey) return; // nothing to do

            e.preventDefault();

            // mark Hotels top-level link active
            $('.mlb-sidebar-link').removeClass('active');
            $('.mlb-sidebar-link[data-content="hotels"]').addClass('active');

            try { updateAdminUrl('mylighthouse-booker', contentKey); } catch (err) { /* ignore */ }
            loadDashboardPage('mylighthouse-booker', null, null, null, contentKey);
        });

        // Ajax-delete a hotel (in-dashboard removal)
        $(document).on('click', '.mlb-ajax-delete-hotel', function(e){
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            var hotelId = $btn.attr('data-hotel-id') || $btn.data('hotel-id');
            var hotelName = $btn.attr('data-hotel-name') || $btn.data('hotel-name') || 'this hotel';
            var nonce = $btn.attr('data-delete-nonce') || '';

            if (!hotelId) {
                alert( mlbGettext('Invalid hotel id') );
                return;
            }

            if (!confirm( mlbSprintf( mlbGettext('Are you sure you want to delete "%s"?'), hotelName ) )) {
                return;
            }

            var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
            if (!ajaxUrl) {
                // fallback to link navigation
                window.location.href = $btn.attr('href');
                return;
            }

            $btn.prop('disabled', true);

            var fd = new FormData();
            fd.append('action', 'mlb_delete_hotel');
            fd.append('hotel_id', hotelId);
            fd.append('nonce', nonce);

            fetch(ajaxUrl, {method: 'POST', credentials: 'same-origin', body: fd}).then(function(r){
                return r.json();
            }).then(function(json){
                if (json && json.success) {
                    // remove the hotel row from DOM
                    $btn.closest('.mlb-hotel-item').fadeOut(220, function(){ $(this).remove(); });
                    // show a transient notice at top of dashboard
                    var $main = $('#mlb-dashboard-main');
                    if ($main.length) {
                        mlbCreateAdminNotice((json.data && json.data.message ? json.data.message : 'Hotel deleted'), 'success', 3500);
                    }
                } else {
                    alert((json && json.data && json.data.message) ? json.data.message : 'Delete failed');
                    $btn.prop('disabled', false);
                }
            }).catch(function(err){
                console.error('mlb_delete_hotel failed', err);
                alert( mlbGettext('Delete request failed') );
                $btn.prop('disabled', false);
            });
        });

        // On first load, trigger the active button
        var active = $('.mlb-sidebar-link.active');
        if(active.length){
            active.trigger('click');
        }
    });
})(jQuery);
