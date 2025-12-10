// Hotel-specific behavior
( function( window, document, $ ) {
    'use strict';

    function MLBHotelsManager( rootSelector ) {
        this.root = $(rootSelector || document);
    }

    MLBHotelsManager.prototype.init = function(){
        var self = this;
        console.debug('MLBHotelsManager.init');

        var orderSaveTimer = null;

        function getHotelsContainer(){
            return $('#hotels-container');
        }

        function collectHotelOrder(){
            var order = [];
            var $container = getHotelsContainer();
            if(!$container.length) return order;
            $container.find('.mlb-hotel-item').each(function(){
                var $item = $(this);
                var id = $item.attr('data-id') || '';
                if(!id || id === '0') {
                    var ext = $item.attr('data-external-id') || $item.find('.mlb-hotel-header').attr('data-external-id') || '';
                    if(!ext){
                        var txt = $item.find('.mlb-hotel-id-text').text() || '';
                        ext = String(txt).replace(/^ID:\s*/i, '').trim();
                    }
                    order.push(ext || id);
                } else {
                    order.push(id);
                }
            });
            return order;
        }

        function saveHotelOrder(){
            var order = collectHotelOrder();
            if(!order.length) return;
            var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
            if(!ajaxUrl){ console.warn('AJAX URL not available for saving hotel order'); return; }
            var fd = new FormData();
            fd.append('action', 'mlb_save_hotels_order');
            fd.append('nonce', (window.mlb_admin_params && mlb_admin_params.nonce) ? mlb_admin_params.nonce : '');
            fd.append('order', JSON.stringify(order));
            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd }).then(function(r){ return r.json(); }).then(function(json){
                if(!json || !json.success){ console.warn('Save order failed', json); mlbCreateAdminNotice( mlbGettext('Failed to save order'), 'error', 4000 ); return; }
                mlbCreateAdminNotice( mlbGettext('Order saved'), 'success', 2000 );
            }).catch(function(err){ console.error('Save order request failed', err); mlbCreateAdminNotice( mlbGettext('Save request failed'), 'error', 4000 ); });
        }

        function scheduleOrderSave(){
            if(orderSaveTimer){ clearTimeout(orderSaveTimer); }
            orderSaveTimer = setTimeout(function(){
                orderSaveTimer = null;
                saveHotelOrder();
            }, 300);
        }

        function moveHotelItem($item, direction){
            if(!$item || !$item.length) return;
            if(direction === 'up'){
                var $prev = $item.prev('.mlb-hotel-item');
                if(!$prev.length) return;
                $item.insertBefore($prev);
            } else {
                var $next = $item.next('.mlb-hotel-item');
                if(!$next.length) return;
                $item.insertAfter($next);
            }
            updateOrderButtons();
            scheduleOrderSave();
        }

        function updateOrderButtons(){
            var $items = getHotelsContainer().find('.mlb-hotel-item');
            if(!$items.length) return;
            var lastIndex = $items.length - 1;
            $items.each(function(index){
                var $el = $(this);
                $el.find('.mlb-move-up').prop('disabled', index === 0);
                $el.find('.mlb-move-down').prop('disabled', index === lastIndex);
            });
        }

        self.updateOrderButtons = updateOrderButtons;

        this.root.off('click.mlb.moveUp', '.mlb-move-up').on('click.mlb.moveUp', '.mlb-move-up', function(e){
            e.preventDefault();
            e.stopPropagation();
            var $item = $(this).closest('.mlb-hotel-item');
            moveHotelItem($item, 'up');
        });

        this.root.off('click.mlb.moveDown', '.mlb-move-down').on('click.mlb.moveDown', '.mlb-move-down', function(e){
            e.preventDefault();
            e.stopPropagation();
            var $item = $(this).closest('.mlb-hotel-item');
            moveHotelItem($item, 'down');
        });

        updateOrderButtons();

        // Tab switching inside hotel edit (rooms / specials)
        this.root.off('click.mlb.tabs', '.mlb-tab-btn').on('click.mlb.tabs', '.mlb-tab-btn', function(e){
            e.preventDefault();
            var $btn = $(this);
            var tab = $btn.attr('data-tab');
            console.debug('MLBHotelsManager: tab click', tab, $btn);
            var $panel = self.root.find('[data-tab-panel="' + tab + '"]');
            if(!$panel.length) return;
            // deactivate siblings and update aria attributes
            $btn.closest('.mlb-tabs-nav').find('.mlb-tab-btn').removeClass('active').attr('aria-selected','false');
            $btn.addClass('active').attr('aria-selected','true');
            // hide other panels
            $btn.closest('.mlb-tabs').find('.mlb-tab-panel').attr('hidden', true).attr('aria-hidden','true');
            $panel.removeAttr('hidden').attr('aria-hidden','false');
        });
            // mark any tabs inside the manager root as managed so early clicks can be ignored
            try { self.root.find('.mlb-tabs').attr('data-mlb-tabs-managed', '1'); } catch(e){ /* ignore */ }

        // Add room: open modal to collect name/id and then create item
        this.root.off('click.mlb.addroom', '#add-room').on('click.mlb.addroom', '#add-room', function(e){
            e.preventDefault();
            openItemModal('add', 'rooms', null);
        });

        // Remove room (delegated) — server-delete if persisted otherwise remove client-side
        this.root.off('click.mlb.removeroom', '.mlb-remove-room-btn').on('click.mlb.removeroom', '.mlb-remove-room-btn', function(e){
            e.preventDefault();
            var $btn = $(this);
            var $card = $btn.closest('.mlb-room-card');
            if(!$card.length) return;
            var idInput = $card.find('input[type=hidden][name$="[id]"]');
            var dbId = parseInt(idInput.val() || 0, 10);
            if(dbId > 0){
                if(!confirm( mlbGettext('Are you sure you want to delete this room?') )) return;
                var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                if(!ajaxUrl){ alert( mlbGettext('AJAX URL not available') ); return; }
                $btn.prop('disabled', true);
                var fd = new FormData(); fd.append('action','mlb_delete_item'); fd.append('nonce', (window.mlb_admin_params && mlb_admin_params.nonce) ? mlb_admin_params.nonce : ''); fd.append('item_id', dbId); fd.append('target','rooms');
                fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd }).then(function(r){ return r.json(); }).then(function(json){
                    if(!json || !json.success){ alert((json && json.data && json.data.message) ? json.data.message : 'Delete failed'); $btn.prop('disabled', false); return; }
                    $card.fadeOut(180, function(){ $card.remove(); reindexRepeater('#rooms-repeater','rooms'); });
                }).catch(function(err){ console.error('Delete room failed', err); alert( mlbGettext('Delete request failed') ); $btn.prop('disabled', false); });
            } else {
                $card.fadeOut(180, function(){ $card.remove(); reindexRepeater('#rooms-repeater','rooms'); });
            }
        });

        // Add special: open modal to collect name/id and then create item
        this.root.off('click.mlb.addspecial', '#add-special').on('click.mlb.addspecial', '#add-special', function(e){
            e.preventDefault();
            openItemModal('add', 'specials', null);
        });

        // Remove special (delegated) — server-delete if persisted otherwise remove client-side
        this.root.off('click.mlb.removespecial', '.mlb-remove-special-btn').on('click.mlb.removespecial', '.mlb-remove-special-btn', function(e){
            e.preventDefault();
            var $btn = $(this);
            var $card = $btn.closest('.mlb-special-card');
            if(!$card.length) return;
            var idInput = $card.find('input[type=hidden][name$="[id]"]');
            var dbId = parseInt(idInput.val() || 0, 10);
            if(dbId > 0){
                if(!confirm( mlbGettext('Are you sure you want to delete this special?') )) return;
                var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                if(!ajaxUrl){ alert( mlbGettext('AJAX URL not available') ); return; }
                $btn.prop('disabled', true);
                var fd = new FormData(); fd.append('action','mlb_delete_item'); fd.append('nonce', (window.mlb_admin_params && mlb_admin_params.nonce) ? mlb_admin_params.nonce : ''); fd.append('item_id', dbId); fd.append('target','specials');
                fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd }).then(function(r){ return r.json(); }).then(function(json){
                    if(!json || !json.success){ alert((json && json.data && json.data.message) ? json.data.message : 'Delete failed'); $btn.prop('disabled', false); return; }
                    $card.fadeOut(180, function(){ $card.remove(); reindexRepeater('#specials-repeater','specials'); });
                }).catch(function(err){ console.error('Delete special failed', err); alert('Delete request failed'); $btn.prop('disabled', false); });
            } else {
                $card.fadeOut(180, function(){ $card.remove(); reindexRepeater('#specials-repeater','specials'); });
            }
        });

        // Edit room (open modal)
        this.root.off('click.mlb.editroom', '.mlb-edit-room-btn').on('click.mlb.editroom', '.mlb-edit-room-btn', function(e){
            e.preventDefault();
            var $card = $(this).closest('.mlb-room-card');
            if(!$card.length) return;
            openItemModal('edit', 'rooms', $card);
        });

        // Edit special (open modal)
        this.root.off('click.mlb.editspecial', '.mlb-edit-special-btn').on('click.mlb.editspecial', '.mlb-edit-special-btn', function(e){
            e.preventDefault();
            var $card = $(this).closest('.mlb-special-card');
            if(!$card.length) return;
            openItemModal('edit', 'specials', $card);
        });

        // Simple client-side validation before submit and AJAX form submission
        this.root.off('submit.mlb.form', '#mlb-hotel-form').on('submit.mlb.form', '#mlb-hotel-form', function(e){
            var $form = $(this);
            var valid = true;
            // require hotel name and external id
            var name = $form.find('input[name="hotel_name"]').val() || $form.find('input[name="hotel_name"]', $form).val();
            var ext = $form.find('input[name="hotel_external_id"]').val() || $form.find('input[name="hotel_external_id"]', $form).val();
            // fallback to common field names used in templates
            if(!$form.find('input[name="hotel_name"]').length){ name = $form.find('input[name="name"]').val() || ''; }
            if(!$form.find('input[name="hotel_external_id"]').length){ ext = $form.find('input[name="hotel_external_id"]').val() || $form.find('input[name="hotel_id"]').val() || ''; }

            if(!name || !name.trim()){
                valid = false;
                alert('Please provide a hotel name.');
            }
            if(!ext || !ext.trim()){
                valid = false;
                alert('Please provide an external hotel id.');
            }

            if(!valid){ e.preventDefault(); return false; }

            // Perform AJAX submit instead of normal post
            e.preventDefault();
            var btn = $form.find('button[type=submit]').first();
            var origText = btn ? btn.text() : '';
            if(btn){ btn.prop('disabled', true); btn.text('Saving...'); }

            var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
            if(!ajaxUrl){ if(btn){ btn.prop('disabled', false); btn.text(origText); } alert('AJAX URL not available'); return false; }

            var fd = new FormData($form[0]);
            // If rooms/specials are represented with indexed names, FormData will preserve them for PHP parsing
            fetch(ajaxUrl, {method: 'POST', credentials: 'same-origin', body: fd}).then(function(r){ return r.json(); }).then(function(json){
                if(!json || !json.success){
                    alert((json && json.data && json.data.message) ? json.data.message : 'Save failed');
                    if(btn){ btn.prop('disabled', false); btn.text(origText); }
                    return;
                }

                var msg = (json.data && json.data.message) ? json.data.message : 'Hotel saved';
                var $main = $('#mlb-dashboard-main');
                if($main.length){ mlbCreateAdminNotice(msg, 'success', 3000); } else { alert(msg); }

                // If server returned rendered HTML for the saved hotel row, replace or insert it in-place
                if (json.data && json.data.html) {
                    try {
                        var html = json.data.html;
                        var $node = $(html);
                        var hid = json.data.hotel_id ? String(json.data.hotel_id) : null;
                        var $container = $('#hotels-container');
                        if (hid && $container.length) {
                            var $existing = $container.find('.mlb-hotel-item[data-id="' + hid + '"]');
                            if ($existing.length) {
                                $existing.replaceWith($node);
                            } else {
                                // prepend new hotel to top of list
                                $container.prepend($node);
                            }
                            if(window.mlb_hotels_manager && typeof window.mlb_hotels_manager.updateOrderButtons === 'function'){
                                window.mlb_hotels_manager.updateOrderButtons();
                            }
                        }
                    } catch (e) { console.error('Insert/replace hotel row failed', e); }
                } else {
                    // Fallback: refresh hotels list fragment
                    try{ $('.mlb-sidebar-link[data-content="hotels"]').trigger('click'); }catch(e){ /* ignore */ }
                }

                // UX: scroll to and briefly highlight the inserted/updated row
                try{
                    var targetId = json.data && json.data.hotel_id ? String(json.data.hotel_id) : null;
                    if(targetId){
                        var $target = $('#hotels-container').find('.mlb-hotel-item[data-id="' + targetId + '"]');
                        if($target && $target.length){
                            // Ensure highlight CSS exists once
                            if(!document.getElementById('mlb-flash-style')){
                                var st = document.createElement('style'); st.id = 'mlb-flash-style';
                                st.textContent = '.mlb-flash{background-color:#fff7cc;transition:background-color 1.2s ease;} .mlb-flash-remove{background-color:transparent;transition:background-color 1.2s ease;}';
                                document.head.appendChild(st);
                            }
                            $target.addClass('mlb-flash');
                            // Scroll into view (centered) and remove highlight after a short delay
                            try{ $target[0].scrollIntoView({behavior:'smooth', block:'center'}); }catch(e){}
                            setTimeout(function(){ $target.removeClass('mlb-flash'); }, 1400);
                        }
                    }
                }catch(e){ console.error('Post-insert highlight failed', e); }
            }).catch(function(err){
                console.error('mlb_save_hotel failed', err);
                alert( mlbGettext('Save request failed') );
            }).finally(function(){ if(btn){ btn.prop('disabled', false); btn.text(origText); } });

            return false;
        });
    };

    /* Helper: reindex repeater inputs so `rooms[0].. rooms[1]..` stay sequential */
    function reindexRepeater(selector, prefix){
        var $wrap = $(selector);
        if(!$wrap.length) return;
        $wrap.children().each(function(i, el){
            var $el = $(el);
            $el.attr('data-index', i);
            // update hidden inputs
            $el.find('.mlb-input-name').each(function(){
                var name = prefix + '[' + i + '][name]';
                $(this).attr('name', name);
            });
            $el.find('.mlb-input-extid').each(function(){
                var key = (prefix === 'rooms') ? 'room_id' : 'special_id';
                var name = prefix + '[' + i + '][' + key + ']';
                $(this).attr('name', name);
            });
            $el.find('input[type=hidden][name$="[id]"]').each(function(){
                var name = prefix + '[' + i + '][id]';
                $(this).attr('name', name);
            });
        });
    }

    /* Modal helpers */
    function openItemModal(mode, target, $item){
        try{
            var $modal = $('#mlb-item-modal');
            if(!$modal.length){ console.debug('openItemModal: modal not found'); return; }
            console.debug('openItemModal: initial hidden attr =', $modal.attr('hidden'));
            $modal.find('#mlb-modal-mode').val(mode);
            $modal.find('#mlb-modal-target').val(target);
            $modal.data('editItem', $item || null);
        // prefill
        if(mode === 'edit' && $item){
            var name = $item.find('.mlb-input-name').val() || $item.find('.mlb-item-name-text').text() || '';
            var ext = $item.find('.mlb-input-extid').val() || '';
            $modal.find('#mlb-modal-name').val(name);
            $modal.find('#mlb-modal-extid').val(ext);
            $modal.find('#mlb-modal-title').text((target==='specials')? 'Edit Special' : 'Edit Room');
        } else {
            $modal.find('#mlb-modal-name').val('');
            $modal.find('#mlb-modal-extid').val('');
            $modal.find('#mlb-modal-title').text((target==='specials')? 'Add Special' : 'Add Room');
        }
        // Ensure modal is not inside a hidden tab panel — move it to document.body so CSS positioning works
        try{
            if($modal.length && $modal[0].parentNode !== document.body){
                $(document.body).append($modal);
                console.debug('openItemModal: moved modal to document.body');
            }
        }catch(e){ console.debug('openItemModal: move to body failed', e); }

        $modal.removeAttr('hidden');
        console.debug('openItemModal: after removeAttr hidden attr =', $modal.attr('hidden'), 'visible:', $modal.is(':visible'));
        }catch(e){ console.error('openItemModal error', e); }
    }

    function closeItemModal(){
        try{
            var $modal = $('#mlb-item-modal'); if(!$modal.length) { console.debug('closeItemModal: modal not found'); return; }
            console.debug('closeItemModal: hiding modal, current hidden attr =', $modal.attr('hidden'), 'visible:', $modal.is(':visible'));
            $modal.attr('hidden','hidden');
            $modal.data('editItem', null);
        }catch(e){ console.error('closeItemModal error', e); }
    }

    // Modal dismiss handlers
    $(document).on('click', '#mlb-item-modal [data-dismiss="modal"]', function(e){ e.preventDefault(); closeItemModal(); });
    $(document).on('click', '#mlb-item-modal .mlb-modal-footer .mlb-btn.mlb-btn-secondary', function(e){ e.preventDefault(); closeItemModal(); });
    // close on ESC
    $(document).on('keydown', function(e){ if(e.key === 'Escape'){ var $m = $('#mlb-item-modal'); if($m.length && !$m.attr('hidden')) closeItemModal(); } });

    // Save modal
    $(document).on('click', '#mlb-modal-save', function(e){
        e.preventDefault();
        var $modal = $('#mlb-item-modal');
        var mode = $modal.find('#mlb-modal-mode').val();
        var target = $modal.find('#mlb-modal-target').val();
        var name = $modal.find('#mlb-modal-name').val() || '';
        var ext = $modal.find('#mlb-modal-extid').val() || '';
        var $editItem = $modal.data('editItem');
            if(mode === 'add'){
                // Require hotel to exist in DB before creating a child record
                var hotelId = parseInt($('#mlb-hotel-form').attr('data-hotel-id') || $('#mlb-hotel-form input[name="hotel_id"]').val() || 0, 10);
                if(!hotelId){
                    alert( mlbGettext('Please save the hotel before adding rooms or specials.') );
                    return;
                }

                // Send AJAX request to create the item server-side, then append rendered client node
                var payload = new FormData();
                payload.append('action', 'mlb_add_item');
                payload.append('nonce', (window.mlb_admin_params && mlb_admin_params.nonce) ? mlb_admin_params.nonce : '');
                payload.append('hotel_id', hotelId);
                payload.append('target', target);
                payload.append('name', name);
                payload.append('external_id', ext);

                var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                if(!ajaxUrl){ alert('AJAX URL not available'); return; }

                var $saveBtn = $('#mlb-modal-save');
                $saveBtn.prop('disabled', true);

                fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: payload }).then(function(r){ return r.json(); }).then(function(json){
                    if(!json || !json.success){ alert((json && json.data && json.data.message) ? json.data.message : mlbGettext('Create failed')); return; }
                    var newId = json.data && json.data.item_id ? json.data.item_id : 0;

                    var tpl = document.getElementById(target.slice(0,-1) + '-template');
                    if(!tpl){ closeItemModal(); return; }
                    var html = tpl.innerHTML;
                    var idx = $('#' + target + '-repeater').children().length;
                    html = html.replace(/\{\{INDEX\}\}/g, idx);
                    var $node = $(html);
                    // set values
                    $node.find('.mlb-input-name').val(name);
                    $node.find('.mlb-input-extid').val(ext);
                    $node.find('.mlb-item-name-text').text(name);
                    $node.find('.mlb-item-id-text').text('ID: ' + ext);
                    // set returned DB id into hidden id input
                    try{ $node.find('input[type=hidden][name$="[id]"]').val(newId); }catch(e){}
                    $('#' + target + '-repeater').append($node);
                    reindexRepeater('#' + target + '-repeater', target);
                }).catch(function(err){ console.error('Create item failed', err); alert( mlbGettext('Create request failed') ); }).finally(function(){ $saveBtn.prop('disabled', false); closeItemModal(); });
            } else if(mode === 'edit' && $editItem){
                var idInput = $editItem.find('input[type=hidden][name$="[id]"]');
                var dbId = parseInt(idInput.val() || 0, 10);
                // If item persists in DB, perform server-side update
                if(dbId > 0){
                    var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
                    if(!ajaxUrl){ alert('AJAX URL not available'); return; }
                    var $saveBtn = $('#mlb-modal-save');
                    $saveBtn.prop('disabled', true);
                    var fd = new FormData(); fd.append('action','mlb_update_item'); fd.append('nonce', (window.mlb_admin_params && mlb_admin_params.nonce) ? mlb_admin_params.nonce : ''); fd.append('item_id', dbId); fd.append('target', target); fd.append('name', name); fd.append('external_id', ext);
                    fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd }).then(function(r){ return r.json(); }).then(function(json){
                        if(!json || !json.success){ alert((json && json.data && json.data.message) ? json.data.message : mlbGettext('Update failed')); return; }
                        // update UI on success
                        $editItem.find('.mlb-input-name').val(name);
                        $editItem.find('.mlb-input-extid').val(ext);
                        $editItem.find('.mlb-item-name-text').text(name);
                        $editItem.find('.mlb-item-id-text').text('ID: ' + ext);
                        var wrap = (target === 'specials') ? '#specials-repeater' : '#rooms-repeater';
                        reindexRepeater(wrap, target);
                    }).catch(function(err){ console.error('Update item failed', err); alert( mlbGettext('Update request failed') ); }).finally(function(){ $saveBtn.prop('disabled', false); closeItemModal(); });
                } else {
                    // local-only item: update client-side fields
                    $editItem.find('.mlb-input-name').val(name);
                    $editItem.find('.mlb-input-extid').val(ext);
                    $editItem.find('.mlb-item-name-text').text(name);
                    $editItem.find('.mlb-item-id-text').text('ID: ' + ext);
                    var wrap = (target === 'specials') ? '#specials-repeater' : '#rooms-repeater';
                    reindexRepeater(wrap, target);
                    closeItemModal();
                }
            }
    });

    /* Inline edit handlers for hotel name / external id in sidebar */
    $(document).on('click', '.mlb-inline-edit', function(e){
        e.preventDefault();
        var $btn = $(this);
        var field = $btn.attr('data-edit');
        var $valueWrap = $btn.closest('.mlb-sidebar-value');
        var current = $valueWrap.find('.mlb-sidebar-text').text() || '';
        // replace text with input (create via DOM APIs then wrap with jQuery)
        var inputEl = document.createElement('input');
        inputEl.type = 'text';
        inputEl.className = 'mlb-inline-input regular-text mlb-input';
        inputEl.value = current.replace(/^ID:\s*/,'').trim();
        var $input = $(inputEl);
        $valueWrap.find('.mlb-sidebar-text').hide();
        $btn.hide();
        $valueWrap.append($input);
        // mark as editing so CSS shows the save button
        $valueWrap.addClass('editing');
        $input.focus();
    });

    $(document).on('click', '.mlb-inline-save', function(e){
        e.preventDefault();
        var $btn = $(this);
        var field = $btn.attr('data-save');
        var $valueWrap = $btn.closest('.mlb-sidebar-value');
        var $input = $valueWrap.find('.mlb-inline-input');
        if(!$input.length) return;
        var val = $input.val() || '';
        // prepare payload to save hotel via AJAX
        var hotelId = $('#mlb-hotel-form').attr('data-hotel-id') || 0;
        // read current sidebar values so we always submit both required fields
        var currentName = $('.mlb-sidebar-value[data-field="name"]').find('.mlb-sidebar-text').text() || '';
        var currentExtRaw = $('.mlb-sidebar-value[data-field="external_id"]').find('.mlb-sidebar-text').text() || '';
        var currentExt = String(currentExtRaw).replace(/^ID:\s*/,'').trim();
        var payload = new FormData();
        payload.append('action', 'mlb_update_hotel_field');
        payload.append('nonce', (window.mlb_admin_params && mlb_admin_params.nonce) ? mlb_admin_params.nonce : '');
        payload.append('hotel_id', hotelId);
        if(field === 'name'){
            payload.append('hotel_name', val);
        }
        if(field === 'external_id'){
            payload.append('hotel_external_id', val);
        }

        var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
        if(!ajaxUrl) { alert('AJAX URL not available'); return; }
        $btn.prop('disabled', true);
        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: payload }).then(function(r){ return r.json(); }).then(function(json){
            if(!json || !json.success){ alert((json && json.data && json.data.message) ? json.data.message : 'Save failed'); return; }
            // update UI
            if(field === 'name') $valueWrap.find('.mlb-sidebar-text').text(val);
            if(field === 'external_id') $valueWrap.find('.mlb-sidebar-text').text('ID: ' + val);
            // cleanup input
            $valueWrap.find('.mlb-inline-input').remove();
            $valueWrap.find('.mlb-sidebar-text').show();
            $valueWrap.find('.mlb-inline-edit').show();
            // remove editing state so the save button is hidden
            $valueWrap.removeClass('editing');
            // if server returned updated hotel row HTML, replace in hotels list
            if(json.data && json.data.html){
                try{ var $node = $(json.data.html); var hid = json.data.hotel_id ? String(json.data.hotel_id) : null; if(hid){ var $existing = $('#hotels-container').find('.mlb-hotel-item[data-id="' + hid + '"]'); if($existing.length) $existing.replaceWith($node); else $('#hotels-container').prepend($node); } }catch(e){console.error(e);} 
            }
        }).catch(function(err){ console.error('Inline hotel save failed', err); alert( mlbGettext('Save failed') ); }).finally(function(){ $btn.prop('disabled', false); });
    });

    /* Add Hotel modal handlers in hotels list */
    $(document).on('click', '#mlb-open-add-hotel', function(e){ e.preventDefault(); $('#mlb-add-hotel-modal').removeAttr('hidden'); });
    $(document).on('click', '#mlb-add-hotel-modal [data-dismiss="modal"]', function(e){ e.preventDefault(); $('#mlb-add-hotel-modal').attr('hidden','hidden'); });
    $(document).on('click', '#mlb-add-hotel-save', function(e){
        e.preventDefault();
        var name = $('#mlb-add-hotel-name').val() || '';
        var ext = $('#mlb-add-hotel-extid').val() || '';
        if(!name || !ext){ alert( mlbGettext('Please provide both name and external id') ); return; }
        var payload = new FormData();
        payload.append('action','mlb_save_hotel');
        payload.append('nonce', (window.mlb_admin_params && mlb_admin_params.nonce) ? mlb_admin_params.nonce : '');
        payload.append('hotel_name', name);
        payload.append('hotel_external_id', ext);
        var $btn = $(this); $btn.prop('disabled', true).text('Saving...');
        var ajaxUrl = (window.mlb_admin_params && mlb_admin_params.ajax_url) ? mlb_admin_params.ajax_url : (window.ajaxurl || '');
        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: payload }).then(function(r){ return r.json(); }).then(function(json){
            if(!json || !json.success){ alert((json && json.data && json.data.message) ? json.data.message : 'Save failed'); return; }
            // insert returned hotel row HTML
            if(json.data && json.data.html){
                try{ var $node = $(json.data.html); $('#hotels-container').prepend($node); if(window.mlb_hotels_manager && typeof window.mlb_hotels_manager.updateOrderButtons === 'function'){ window.mlb_hotels_manager.updateOrderButtons(); } }
                catch(e){ console.error('Insert hotel failed', e); }
            } else {
                // fallback: reload page or navigate to hotels list
                location.reload();
            }
            $('#mlb-add-hotel-modal').attr('hidden','hidden');
        }).catch(function(err){ console.error('Add hotel failed', err); alert( mlbGettext('Save failed') ); }).finally(function(){ $btn.prop('disabled', false).text( mlbGettext('Add Hotel') ); });
    });

    // Expose manager
    window.MLBHotelsManager = MLBHotelsManager;

    // Auto-init guard: if dashboard loader doesn't instantiate the manager for some reason,
    // attempt a defensive self-init. This will retry for a few seconds and will not override
    // a manager already created by the dashboard loader.
    (function(){
        var attempts = 0;
        var maxAttempts = 12; // retry for ~6 seconds
        var interval = setInterval(function(){
            attempts++;
            try{
                if(window.mlb_hotels_manager){
                    // already created elsewhere (dashboard loader) — stop
                    clearInterval(interval);
                    return;
                }
                // prefer the dashboard main root if present
                var rootSelector = '#mlb-dashboard-main';
                var rootEl = document.querySelector(rootSelector);
                if(!rootEl){
                    // fallback to any hotels wrapper the fragment might render
                    rootEl = document.querySelector('.mlb-hotels-wrap') || document.querySelector('.mlb-admin-wrap') || document.querySelector('#mlb-hotel-form');
                }
                if(rootEl && typeof window.MLBHotelsManager === 'function'){
                    try{
                        // Pass the actual element (or selector) that exists so the manager's
                        // jQuery root is non-empty and delegated handlers will bind.
                        window.mlb_hotels_manager = new window.MLBHotelsManager(rootEl);
                        if(typeof window.mlb_hotels_manager.init === 'function'){
                            window.mlb_hotels_manager.init();
                            console.debug('MLBHotelsManager: auto-initialized by hotels.js on', rootEl);
                        }
                    }catch(e){ console.error('MLBHotelsManager auto-init failed', e); }
                    clearInterval(interval);
                    return;
                }
            }catch(e){ console.error('Auto-init guard error', e); }
            if(attempts >= maxAttempts){ clearInterval(interval); }
        }, 500);
    })();

// Fallback: handle early tab clicks before the hotels manager has bound event handlers.
// This ensures UX works even if a user clicks quickly before the fragment-specific
// manager initialization completes. It will not act if the tabs element has been
// marked as managed by the MLBHotelsManager.
$(document).on('click', '.mlb-tab-btn', function(e){
    try{
        var $btn = $(this);
        var $tabs = $btn.closest('.mlb-tabs');
        if($tabs.length && $tabs.attr('data-mlb-tabs-managed')) return; // manager will handle
        e.preventDefault();
        var tab = $btn.attr('data-tab');
        console.debug('Fallback tab handler: switching to', tab);
        // toggle active class and panels within this .mlb-tabs
        $btn.closest('.mlb-tabs-nav').find('.mlb-tab-btn').removeClass('active').attr('aria-selected','false');
        $btn.addClass('active').attr('aria-selected','true');
        $btn.closest('.mlb-tabs').find('.mlb-tab-panel').attr('hidden', true).attr('aria-hidden','true');
        var $panel = $btn.closest('.mlb-tabs').find('[data-tab-panel="' + tab + '"]');
        if($panel.length){ $panel.removeAttr('hidden').attr('aria-hidden','false'); }
    }catch(e){ console.error('Fallback tab handler error', e); }
});

// Document-level fallback for add buttons in case the manager didn't bind yet.
// Only open the modal if it exists and is currently hidden to avoid duplicates.
$(document).on('click', '#add-room', function(e){
    console.debug('fallback #add-room click');
    var $modal = $('#mlb-item-modal');
    if(!$modal.length) return;
    var hidden = $modal.attr('hidden') !== undefined && $modal.attr('hidden') !== false;
    if(!hidden) return; // already visible
    e.preventDefault();
    openItemModal('add', 'rooms', null);
});

$(document).on('click', '#add-special', function(e){
    console.debug('fallback #add-special click');
    var $modal = $('#mlb-item-modal');
    if(!$modal.length) return;
    var hidden = $modal.attr('hidden') !== undefined && $modal.attr('hidden') !== false;
    if(!hidden) return; // already visible
    e.preventDefault();
    openItemModal('add', 'specials', null);
});

// Capture-phase native listener as a stronger fallback in case other handlers stop propagation.
document.addEventListener('click', function(ev){
    try{
        var el = ev.target.closest ? ev.target.closest('#add-special, #add-room') : null;
        if(!el) return;
        var id = el.id || '';
        var $modal = $('#mlb-item-modal');
        if(!$modal.length) return;
        var hidden = $modal.attr('hidden') !== undefined && $modal.attr('hidden') !== false;
        if(!hidden) return; // already visible
        // Prevent duplicate handling: allow jQuery handlers to process first if they will
        ev.preventDefault();
        console.debug('capture handler caught click for', id);
        if(id === 'add-special') openItemModal('add', 'specials', null);
        else if(id === 'add-room') openItemModal('add', 'rooms', null);
    }catch(e){ console.debug('capture add button handler error', e); }
}, true);

})( window, document, jQuery );