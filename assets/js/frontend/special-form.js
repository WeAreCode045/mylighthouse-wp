/**
 * Frontend JavaScript for special booking forms with modal date picker
 */

(function($) {
    'use strict';

    // JS gettext helper: prefer wp.i18n.__() when available
    function mlbGettext(str) {
        try {
            if (typeof wp !== 'undefined' && wp.i18n && typeof wp.i18n.__ === 'function') {
                return wp.i18n.__(str, 'mylighthouse-booker');
            }
        } catch (e) {}
        return str;
    }

    function handleSpecialFallback($form, detail, bookingPageUrl) {
        try {
            var target = (typeof cqb_params !== 'undefined' && cqb_params && cqb_params.result_target)
                ? cqb_params.result_target
                : 'booking_page';
            var hotelId = detail && detail.hotel
                ? detail.hotel
                : ($form.data('hotel-id') || $form.find('input[name="hotel_id"]').val() || '');
            var rateId = detail && detail.rate
                ? detail.rate
                : ($form.data('rate-id') || $form.data('special-id') || $form.find('input[name="special_id"]').val() || $form.find('input[name="Rate"]').val() || $form.find('input[name="rate_id"]').val() || '');

            // If still empty, attempt to read Rate from the form action URL.
            if (!rateId) {
                try {
                    var actionUrl = $form.attr('action') || '';
                    if (actionUrl) {
                        var actionParams = new URL(actionUrl, window.location.origin).searchParams;
                        rateId = actionParams.get('Rate') || actionParams.get('rate') || actionParams.get('special_id') || '';
                    }
                } catch (ignore) {}
            }

            console.log('[handleSpecialFallback] hotelId:', hotelId, 'rateId:', rateId, 'bookingPageUrl:', bookingPageUrl, 'target:', target);

            if (!bookingPageUrl) {
                try { $form[0].submit(); } catch (err) {}
                return;
            }

            if (target === 'modal' && typeof window.MLB_Modal !== 'undefined' && typeof window.MLB_Modal.openBookingModal === 'function') {
                window.MLB_Modal.openBookingModal(bookingPageUrl, hotelId, detail && detail.arrivalISO, detail && detail.departureISO, rateId, 'rate');
                return;
            }

            try {
                // Build direct MyLighthouse booking engine URL
                const bookingEngineBase = window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/';
                
                let bookingUrl;
                if (rateId) {
                    // Special rate booking
                    const qs = new URLSearchParams();
                    qs.set('Rate', rateId);
                    if (detail && detail.arrivalISO) qs.set('Arrival', detail.arrivalISO);
                    if (detail && detail.departureISO) qs.set('Departure', detail.departureISO);
                    bookingUrl = `${bookingEngineBase}${encodeURIComponent(hotelId)}/Rooms/GeneralAvailability`;
                    const query = qs.toString();
                    if (query) {
                        bookingUrl += `?${query}`;
                    }
                } else {
                    // Fallback to general availability
                    bookingUrl = `${bookingEngineBase}${encodeURIComponent(hotelId)}/Rooms/GeneralAvailability`;
                }
                
                console.log('[handleSpecialFallback] Direct redirect to:', bookingUrl);
                window.location.href = bookingUrl;
            } catch (err) {
                console.error('[handleSpecialFallback] Error:', err);
                // Fallback to general availability page
                const bookingEngineBase = window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/';
                window.location.href = `${bookingEngineBase}${encodeURIComponent(hotelId)}/Rooms/GeneralAvailability`;
            }
        } catch (fallbackErr) {
            try { $form[0].submit(); } catch (ignore) {}
        }
    }

    // Expose init function for a single special form element.
    window.initSpecialForm = window.initSpecialForm || function(formEl) {
        try {
            var $form = (formEl && formEl.jquery) ? formEl : (typeof jQuery !== 'undefined' ? jQuery(formEl) : null);
            if (!$form || !$form.length) {
                if (formEl && formEl.nodeType === Node.ELEMENT_NODE && typeof jQuery !== 'undefined') {
                    $form = jQuery(formEl);
                } else {
                    return;
                }
            }

            // For special forms that have a rate but missing dataset hotel id,
            // infer the hotel, hide selector and switch to modal flow.
            try { if ($form && $form.length && typeof window.mlbEnsureFormId === 'function') { window.mlbEnsureFormId($form[0]); } } catch (e) {}
            try{
                var preselected = $form.data('hotel-id') || '';
                var isSpecial = $form.hasClass('mlb-special-form-type');
                var rateId = $form.data('rate-id') || $form.data('special-id') || '';
                if((!preselected || preselected === '') && isSpecial && rateId){
                    var native = $form.find('.mlb-hotel-select')[0];
                    if(native){
                        var selOpt = native.querySelector('option[selected][value]');
                        if(!selOpt){
                            var opts = Array.from(native.options).filter(function(o){ return o.value && !o.disabled; });
                            if(opts.length === 1) selOpt = opts[0];
                        }
                        if(selOpt && selOpt.value){
                            preselected = selOpt.value;
                            try{ $form[0].dataset.hotelId = preselected; }catch(e){}
                            var existingHidden = $form.find('input[name="hotel_id"]');
                            if(!existingHidden.length){
                                var h = document.createElement('input'); h.type = 'hidden'; h.name = 'hotel_id'; h.value = preselected; $form[0].appendChild(h);
                            } else { existingHidden.val(preselected); }
                            $($form.find('.mlb-hotel-select')).hide();
                        }
                    }
                        if(preselected){
                        var dr = $form.find('.mlb-daterange'); if(dr.length) dr.hide();
                        var btn = $form.find('.mlb-submit-btn'); if(btn.length) btn.attr('data-trigger-modal','true');
                        if(typeof window.initSpecialModalDatePicker === 'function') window.initSpecialModalDatePicker($form);
                    }
                }
            }catch(e){ console.error('preselect hotel inference error', e); }

            const isSpecialForm = $form.hasClass('mlb-special-form-type') || $form.find('[data-trigger-modal="true"]').length > 0;
            const skipDates = ($form.data('skip-dates') === true) || ($form.attr('data-skip-dates') === '1') || ($form.attr('data-skip-dates') === 'true');
            if (isSpecialForm) {
                if (skipDates) {
                    try {
                        var btns = $form.find('.mlb-book-special-btn, [data-trigger-modal]');
                        btns.attr('data-trigger-modal','false');
                        btns.off('click.mlbSkip').on('click.mlbSkip', function(e){
                            e.preventDefault();
                            var hotelId = $form.data('hotel-id') || $form.find('input[name="hotel_id"]').val() || '';
                            var rateId = $form.data('rate-id') || $form.data('special-id') || $form.find('input[name="special_id"]').val() || $form.find('input[name="Rate"]').val() || $form.find('input[name="rate_id"]').val() || '';
                            // Debug: ensure we have the rate ID
                            console.log('[MLB Skip-Dates] hotelId:', hotelId, 'rateId:', rateId);
                            console.log('[MLB Skip-Dates] Form HTML:', $form[0].outerHTML);
                            console.log('[MLB Skip-Dates] Form Data attrs:', {hotelid: $form.data('hotel-id'), rateid: $form.data('rate-id'), specialid: $form.data('special-id')});
                            console.log('[MLB Skip-Dates] Form Input values:', {hotel_id: $form.find('input[name="hotel_id"]').val(), special_id: $form.find('input[name="special_id"]').val()});
                            // Store special-specific markers to avoid polluting hotel/room forms
                            try {
                                if (rateId) {
                                    window.sessionStorage.setItem('mlb_special_rate', rateId);
                                    window.localStorage.setItem('mlb_special_rate', rateId);
                                }
                                if (hotelId) {
                                    window.sessionStorage.setItem('mlb_special_hotel', hotelId);
                                    window.localStorage.setItem('mlb_special_hotel', hotelId);
                                }
                            } catch (storageErr) {
                                /* ignore */
                            }
                            var detail = { arrivalISO:'', departureISO:'', hotel: hotelId, rate: rateId, __handled:false };
                            try {
                                var evt = new CustomEvent('mlb-submit', { bubbles:true, detail: detail });
                                $form[0].dispatchEvent(evt);
                            } catch (err) {
                                try { var legacy = document.createEvent('CustomEvent'); legacy.initCustomEvent('mlb-submit', true, true, detail); $form[0].dispatchEvent(legacy); } catch (err2) {}
                            }
                            if (!detail.__handled) {
                                var bookingPageUrl = (typeof cqb_params !== 'undefined' && cqb_params && cqb_params.booking_page_url) ? cqb_params.booking_page_url : '';
                                handleSpecialFallback($form, detail, bookingPageUrl);
                            }
                        });
                    } catch (e) { console.error('skipDates binding failed', e); }
                } else {
                    // Specials use modal date picker
                    if(typeof window.initSpecialModalDatePicker === 'function') window.initSpecialModalDatePicker($form);
                }
            } else {
                // Hotel forms use inline date picker
                initInlineDatePicker($form);
            }

            if (isSpecialForm && skipDates) {
                return;
            }

            // Form validation and submission handler specific to this form
            try {
                var submitBtns = $form.find('.mlb-submit-btn');
                submitBtns.each(function() {
                    var $button = jQuery(this);
                    try { var isModalTrigger = $button.attr && $button.attr('data-trigger-modal') === 'true'; } catch (e) { isModalTrigger = false; }
                    if (!isModalTrigger) {
                        $button.off('click.mlbSubmit').on('click.mlbSubmit', function(e) { e.preventDefault(); e.stopPropagation(); handleFormSubmission($form); });
                    }
                });
            } catch (e) { /* ignore */ }
        } catch (err) { console.error('initSpecialForm error', err); }
    };

    // Allow external code to request modal picker init for a specific form
    document.addEventListener('mlb-maybe-init-modal', function(e){
        try{
            var form = e && e.detail && e.detail.form;
            if(!form) return;
            if(typeof window.initSpecialModalDatePicker === 'function'){
                window.initSpecialModalDatePicker($(form));
            } else if (typeof initModalDatePicker === 'function') {
                initModalDatePicker($(form));
            }
        }catch(err){ console.error('mlb-maybe-init-modal handler error', err); }
    });

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================

    function formatDMY(d) {
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        return dd + '-' + mm + '-' + yyyy;
    }

    function copyCSSVariables(source, target, variables) {
        const computedStyle = getComputedStyle(source);
        variables.forEach(function(varName) {
            const value = computedStyle.getPropertyValue(varName);
            if (value) {
                target.style.setProperty(varName, value);
            }
        });
    }

    function initInlineDatePicker($form) {
        const formId = $form.attr('id');
        console.debug('[MLB Inline Picker] initInlineDatePicker called for form:', formId);
        const $daterangeInput = $form.find('.mlb-daterange');
        const $checkinHidden = $form.find('.mlb-checkin');
        const $checkoutHidden = $form.find('.mlb-checkout');

        if (!$daterangeInput.length || !$checkinHidden.length || !$checkoutHidden.length) {
            console.error('[MLB Inline Picker] Missing required inputs for form:', formId);
            return;
        }

        if ($form.hasClass('mlb-inline-picker-init')) {
            return;
        }
        $form.addClass('mlb-inline-picker-init');

        let attempts = 0;

        function initPicker() {
            attempts++;
            const easepickRef = window.easepick;

            if (!easepickRef || !easepickRef.Core) {
                if (attempts > 50) {
                    console.error('[MLB Inline Picker] EasePick failed to load after 50 attempts');
                    return;
                }
                setTimeout(initPicker, 100);
                return;
            }

            const CoreClass = easepickRef.Core || (easepickRef.easepick && easepickRef.easepick.Core) || easepickRef.create;
            if (!CoreClass) {
                console.error('[MLB Inline Picker] CoreClass not found');
                return;
            }

            try {
                let $backdrop = $('.mlb-calendar-backdrop[data-form-id="' + formId + '"]');
                if (!$backdrop.length) {
                    try {
                        var tpl = document.getElementById('mlb-modal-backdrop');
                        if (tpl && tpl.content && tpl.content.firstElementChild) {
                            var clone = tpl.content.firstElementChild.cloneNode(true);
                            clone.setAttribute('data-form-id', formId);
                            document.body.appendChild(clone);
                            $backdrop = $('.mlb-calendar-backdrop[data-form-id="' + formId + '"]');
                        } else {
                            var d = document.createElement('div');
                            d.className = 'mlb-calendar-backdrop';
                            d.setAttribute('data-form-id', formId);
                            document.body.appendChild(d);
                            $backdrop = $('.mlb-calendar-backdrop[data-form-id="' + formId + '"]');
                        }
                    } catch (e) {
                        var d = document.createElement('div');
                        d.className = 'mlb-calendar-backdrop';
                        d.setAttribute('data-form-id', formId);
                        document.body.appendChild(d);
                        $backdrop = $('.mlb-calendar-backdrop[data-form-id="' + formId + '"]');
                    }
                    $backdrop.on('click.mlbDatepicker', function() {
                        if (window.mlbInlinePickers && window.mlbInlinePickers[formId]) {
                            window.mlbInlinePickers[formId].hide();
                        }
                    });
                }

                const pickerConfig = {
                    element: $daterangeInput[0],
                    css: [
                        'https://cdn.jsdelivr.net/npm/@easepick/core@1.2.1/dist/index.css',
                        '/wp-content/plugins/mylighthouse-booker/assets/vendor/easepick/easepick.css'
                    ],
                    plugins: [easepickRef.RangePlugin, easepickRef.LockPlugin],
                    RangePlugin: {
                        tooltip: true,
                        locale: {
                            one: 'night',
                            other: 'nights'
                        }
                    },
                    LockPlugin: {
                        minDate: new Date(),
                    },
                    setup(picker) {
                        if (!$daterangeInput.val() || $daterangeInput.val() === '') {
                            const arrivalTxt = $daterangeInput.data('arrival-text') || mlbGettext('Select Arrival Date');
                            const departureTxt = $daterangeInput.data('departure-text') || mlbGettext('Select Departure Date');
                            $daterangeInput.val(arrivalTxt + ' ⇢ ' + departureTxt);
                        }

                        picker.on('show', () => { $backdrop.addClass('show'); });
                        picker.on('hide', () => { $backdrop.removeClass('show'); });

                        picker.on('select', (e) => {
                            const { start, end } = e.detail;
                            if (!start || !end) return;
                            $checkinHidden.val(formatDMY(start));
                            $checkoutHidden.val(formatDMY(end));
                            const startStr = start.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
                            const endStr = end.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                            $daterangeInput.val(`${startStr} → ${endStr}`);
                        });
                    },
                    lang: 'en-GB',
                    format: 'DD MMM - DD MMM YYYY',
                };

                const picker = new CoreClass(pickerConfig);
                if (!window.mlbInlinePickers) window.mlbInlinePickers = {};
                window.mlbInlinePickers[formId] = picker;

                    console.log('[MLB Inline Picker] Initialized inline picker for form:', formId);
            } catch (error) {
                console.error('[MLB Inline Picker] Error initializing:', error);
            }
        }

        initPicker();
    }

    /**
     * Initialize modal date picker for special forms
     */
    function initSpecialModalDatePicker($form) {
        const formId = $form.attr('id');
        console.debug('[MLB Modal Picker] initSpecialModalDatePicker called for form:', formId);
        const $checkinHidden = $form.find('.mlb-checkin');
        const $checkoutHidden = $form.find('.mlb-checkout');
        const $daterangeInput = $form.find('.mlb-daterange');
        const $bookSpecialBtn = $form.find('.mlb-book-special-btn, [data-trigger-modal="true"]');

        if (!$checkinHidden.length || !$checkoutHidden.length) {
            console.error('[MLB Modal Picker] Missing hidden inputs');
            return;
        }
        if (!$bookSpecialBtn.length) {
            console.error('[MLB Modal Picker] No special booking button found');
            return;
        }
        if ($form.hasClass('mlb-modal-picker-init')) return;
        $form.addClass('mlb-modal-picker-init');

        let attempts = 0;

        function initPicker() {
            attempts++;
            const easepickRef = window.easepick;
            if (!easepickRef || !easepickRef.Core) {
                if (attempts > 50) return setTimeout(initPicker, 100);
                setTimeout(initPicker, 100); return;
            }

            const CoreClass = easepickRef.Core || (easepickRef.easepick && easepickRef.easepick.Core) || easepickRef.create;
            if (!CoreClass) { console.error('[MLB Modal Picker] CoreClass not found'); return; }

            try {
                // Prefer a server-rendered <template id="mlb-modal-template-special">; fall back to cqb_params.modal_template
                let modalOverlay = null;
                try {
                    const tpl = document.getElementById('mlb-modal-template-special');
                    if (tpl && tpl.content && tpl.content.firstElementChild) {
                        modalOverlay = tpl.content.firstElementChild.cloneNode(true);
                    } else if (typeof cqb_params !== 'undefined' && cqb_params.modal_template) {
                        try {
                            var parser = new DOMParser();
                            var doc = parser.parseFromString(cqb_params.modal_template || '', 'text/html');
                            modalOverlay = doc.body ? doc.body.firstElementChild : doc.firstElementChild;
                        } catch (err) {
                            console.error('[MLB Datepicker] modal_template parse error', err);
                        }
                    }
                } catch (err) {
                    console.error('[MLB Datepicker] template clone error', err);
                }

                if (!modalOverlay) {
                    console.error('[MLB Datepicker] Modal template is empty or missing');
                    return;
                }
                modalOverlay.setAttribute('data-form-id', formId);

                const calendarDiv = modalOverlay.querySelector('.mlb-modal-calendar');
                const closeBtn = modalOverlay.querySelector('.mlb-calendar-modal-close');
                const bookingDetailsDiv = modalOverlay.querySelector('.mlb-booking-details');
                const rightColumn = modalOverlay.querySelector('.mlb-modal-right-column');
                const modalSubmitBtn = modalOverlay.querySelector('.mlb-modal-submit-btn');

                const formWrapper = $form.closest('.mylighthouse-booking-form');
                if (formWrapper.length) {
                    copyCSSVariables(formWrapper[0], modalOverlay, [
                        '--mlb-btn-bg','--mlb-btn-text','--mlb-btn-bg-hover','--mlb-btn-text-hover','--mlb-btn-radius','--mlb-button-padding-vertical','--mlb-button-padding-horizontal','--mlb-button-font-size','--mlb-button-font-weight','--mlb-button-text-transform','--mlb-calendar-startend-bg','--mlb-calendar-startend-color','--mlb-calendar-inrange-bg'
                    ]);
                }

                    if (modalOverlay) {
                    const contentWrapper = modalOverlay.querySelector('.mlb-modal-content-wrapper');
                    if (contentWrapper) contentWrapper.classList.add('special-form-modal');
                    var hotelName = '';
                    var specialName = '';
                    try { hotelName = window.mlbGetFormValue($form, ['hotelName','hotel_name','hotel-id','hotel_id','hotel']); } catch (e) {}
                    try { specialName = window.mlbGetFormValue($form, ['specialName','special_name','rate-name','rate_name','special-id','special_id','rate']); } catch (e) {}
                    if (!hotelName) hotelName = $form.data('hotel-name') || $form.data('hotel-id') || 'Hotel';
                    if (!specialName) specialName = $form.data('special-name') || $form.data('rate-name') || 'Special';
                    const hotelNameSpan = modalOverlay.querySelector('.mlb-hotel-name');
                    const specialNameSpan = modalOverlay.querySelector('.mlb-special-name');
                    if (hotelNameSpan) hotelNameSpan.textContent = hotelName;
                    if (specialNameSpan) {
                        specialNameSpan.textContent = specialName;
                        // show special row and hide room row if present
                        const specialRow = modalOverlay.querySelector('.mlb-special-row');
                        const roomRow = modalOverlay.querySelector('.mlb-room-row');
                        if (specialRow) specialRow.style.display = '';
                        if (roomRow) roomRow.style.display = 'none';
                    }
                    // Debug: log resolved names when modal is built
                    try { console.debug('[MLB Modal Picker] resolved names on build', { formId: formId, hotelName: hotelName, specialName: specialName, data: $form[0].dataset }); } catch (e) {}
                    // If specialName looks like an ID or is empty, try explicit hidden inputs/dataset
                    try {
                        var rateIdLocalTop = $form.data('rate-id') || $form.data('special-id') || $form.find('input[name="special_id"]').val() || $form.find('input[name="rate_id"]').val() || '';
                        if (!specialName || specialName === '' || specialName === rateIdLocalTop || /^[0-9]+$/.test(String(specialName))) {
                            var rnTop = $form.find('input[name="rate_name"]'); if (rnTop.length && rnTop.val()) specialName = rnTop.val();
                            var snTop = $form.find('input[name="special_name"]'); if ((!specialName || specialName === '') && snTop.length && snTop.val()) specialName = snTop.val();
                            if ((!specialName || specialName === '') && $form.data('rate-name')) specialName = $form.data('rate-name');
                            if ((!specialName || specialName === '') && $form.data('special-name')) specialName = $form.data('special-name');
                                        if ((!specialName || specialName === '') && rateIdLocalTop) specialName = 'Special' + ' (ID: ' + rateIdLocalTop + ')';
                            if (specialNameSpan) specialNameSpan.textContent = specialName;
                        }
                    } catch (e) {}
                    // Ensure CTA label shows special text
                    try {
                        const ctaRoom = modalOverlay.querySelector('.mlb-modal-cta-room');
                        const ctaSpecial = modalOverlay.querySelector('.mlb-modal-cta-special');
                        if (ctaRoom) ctaRoom.style.display = 'none';
                        if (ctaSpecial) ctaSpecial.style.display = '';
                    } catch (e) {}
                }

                const existingOverlay = document.querySelector('.mlb-calendar-modal-overlay[data-form-id="' + formId + '"]');
                if (existingOverlay && existingOverlay.parentNode) existingOverlay.parentNode.removeChild(existingOverlay);

                document.body.appendChild(modalOverlay);
                console.debug('[MLB Modal Picker] modalOverlay appended for form:', formId, modalOverlay);
                try { if ($form && $form.length) $form[0]._mlbModalOverlay = modalOverlay; } catch (e) {}

                // If the form already has selected dates (e.g., re-open), enable CTA accordingly
                try {
                    var existingArrival = $checkinHidden.val();
                    var existingDeparture = $checkoutHidden.val();
                    if (existingArrival && existingDeparture) {
                        try { if (modalSubmitBtn) modalSubmitBtn.disabled = false; } catch (e) {}
                        try { if (rightColumn) rightColumn.classList.add('mlb-expanded'); } catch (e) {}
                    }
                } catch (e) {}

                function closeModal(){
                    try {
                        // hide overlay
                        modalOverlay.classList.remove('mlb-calendar-modal-show');

                        // reset hidden inputs and visible daterange
                        try { $checkinHidden.val(''); } catch (e) {}
                        try { $checkoutHidden.val(''); } catch (e) {}
                        try {
                            if ($daterangeInput && $daterangeInput.length) {
                                    const arrivalTxt = $daterangeInput.data('arrival-text') || mlbGettext('Select Arrival Date');
                                    const departureTxt = $daterangeInput.data('departure-text') || mlbGettext('Select Departure Date');
                                    $daterangeInput.val(arrivalTxt + ' ⇢ ' + departureTxt);
                                }
                        } catch (e) {}

                        // reset booking details UI
                        try {
                            if (bookingDetailsDiv) {
                                const arrivalSpan = bookingDetailsDiv.querySelector('.mlb-arrival-date');
                                const departureSpan = bookingDetailsDiv.querySelector('.mlb-departure-date');
                                if (arrivalSpan) arrivalSpan.textContent = '';
                                if (departureSpan) departureSpan.textContent = '';
                            }
                        } catch (e) {}

                        // collapse right column and disable submit
                        try { if (rightColumn) rightColumn.classList.remove('mlb-expanded'); } catch (e) {}
                        try { if (modalSubmitBtn) modalSubmitBtn.disabled = true; } catch (e) {}

                        // attempt to clear picker selection safely
                        try {
                            const picker = $form.data('picker') || (window.mlbInlinePickers && window.mlbInlinePickers[formId]);
                            if (picker) {
                                if (typeof picker.clearSelection === 'function') picker.clearSelection();
                                else if (typeof picker.clear === 'function') picker.clear();
                                else if (typeof picker.setDateRange === 'function') picker.setDateRange();
                                else if (typeof picker.remove === 'function') picker.remove();
                                else if (typeof picker.destroy === 'function') picker.destroy();
                                else if (typeof picker.setDate === 'function') picker.setDate();
                                try { const trig = modalOverlay.querySelector('.mlb-picker-trigger-input'); if (trig) trig.value = ''; } catch (e) {}
                            }
                        } catch (e) {}
                    } catch(err){ console.error('closeModal reset error', err); }
                }
                modalOverlay.addEventListener('click', function(e){ if (e.target === modalOverlay) closeModal(); });
                if (closeBtn) closeBtn.addEventListener('click', closeModal);

                const escapeHandler = function(e){ if (e.key === 'Escape' && modalOverlay.classList.contains('mlb-calendar-modal-show')) closeModal(); };
                document.addEventListener('keydown', escapeHandler);
                modalOverlay._escapeHandler = escapeHandler;

                $form.on('mlb-open-calendar', function(e){ e.preventDefault(); modalOverlay.classList.add('mlb-calendar-modal-show'); });

                if ($bookSpecialBtn.length) {
                    $bookSpecialBtn.on('click', function(e){
                        e.preventDefault();
                        console.log('[MLB Modal Picker] Book special button clicked for form:', formId);
                        // Debug: log dataset and hidden inputs to help diagnose missing names
                        try {
                            var inputs = Array.from($form.find('input')).map(function(i){ return { name: i.name, value: i.value }; });
                            console.debug('[MLB Modal Picker] form dataset:', $form[0].dataset, 'hidden inputs:', inputs);
                        } catch (e) { console.debug('[MLB Modal Picker] debug gather failed', e); }

                        if (modalOverlay) {
                            console.log('[MLB Modal Picker] Showing modalOverlay for form:', formId);
                            modalOverlay.classList.add('mlb-calendar-modal-show');
                        } else {
                            console.warn('[MLB Modal Picker] modalOverlay not present when click fired for form:', formId);
                        }
                    });
                }

                const calendarColors = (typeof cqb_params !== 'undefined' && cqb_params.calendar_colors) ? cqb_params.calendar_colors : { startend_bg:'#007cba', startend_color:'#fff', inrange_bg:'#e1f0f7' };

                let pickerElement = calendarDiv;
                if (calendarDiv) {
                    try{ const triggerInput = document.createElement('input'); triggerInput.type='hidden'; triggerInput.className='mlb-picker-trigger-input'; calendarDiv.appendChild(triggerInput); pickerElement = triggerInput; }catch(e){ pickerElement = calendarDiv; }
                }

                const pickerConfig = {
                    element: pickerElement,
                    css: ['https://cdn.jsdelivr.net/npm/@easepick/core@1.2.1/dist/index.css','/wp-content/plugins/mylighthouse-booker/assets/vendor/easepick/easepick.css'],
                    inline: true,
                    plugins: [easepickRef.RangePlugin, easepickRef.LockPlugin],
                    RangePlugin: { tooltip:true, locale:{ one:'night', other:'nights' } },
                    LockPlugin: { minDate: new Date() },
                    setup(picker){
                        setTimeout(function(){ const headerEl = calendarDiv.querySelector('.header'); if (headerEl) headerEl.remove(); }, 0);
                        picker.on('select',(e)=>{
                            const { start, end } = e.detail; if(!start || !end) return; $checkinHidden.val(formatDMY(start)); $checkoutHidden.val(formatDMY(end));
                            const dateSelectedEvent = new CustomEvent('mlb-dates-selected', { bubbles:true, detail:{ arrivalDate:start, departureDate:end, bookingDetailsDiv: bookingDetailsDiv, rightColumn: rightColumn } });
                            $form[0].dispatchEvent(dateSelectedEvent);
                            if (bookingDetailsDiv) {
                                const arrivalStr = start.toLocaleDateString('en-GB',{ day:'numeric', month:'short', year:'numeric' });
                                const departureStr = end.toLocaleDateString('en-GB',{ day:'numeric', month:'short', year:'numeric' });
                                const arrivalSpan = bookingDetailsDiv.querySelector('.mlb-arrival-date'); const departureSpan = bookingDetailsDiv.querySelector('.mlb-departure-date'); if (arrivalSpan) arrivalSpan.textContent = arrivalStr; if (departureSpan) departureSpan.textContent = departureStr;
                                var hotelName = '';
                                var specialName = '';
                                try { hotelName = window.mlbGetFormValue($form, ['hotelName','hotel_name','hotel-id','hotel_id','hotel']) || $form.data('hotel-name') || $form.data('hotel-id') || 'Hotel'; } catch (e) { hotelName = $form.data('hotel-name') || $form.data('hotel-id') || 'Hotel'; }
                                try { specialName = window.mlbGetFormValue($form, ['specialName','special_name','rate-name','rate_name','special-id','special_id','rate']) || $form.data('special-name') || $form.data('rate-name') || ''; } catch (e) { specialName = $form.data('special-name') || $form.data('rate-name') || ''; }
                                try {
                                    var rateIdLocal = $form.data('rate-id') || $form.data('special-id') || $form.find('input[name="special_id"]').val() || $form.find('input[name="rate_id"]').val() || '';
                                    if (!specialName || specialName === '' || specialName === rateIdLocal || /^[0-9]+$/.test(String(specialName))) {
                                        var rn = $form.find('input[name="rate_name"]'); if (rn.length && rn.val()) specialName = rn.val();
                                        var sn = $form.find('input[name="special_name"]'); if ((!specialName || specialName === '') && sn.length && sn.val()) specialName = sn.val();
                                        if ((!specialName || specialName === '') && $form.data('rate-name')) specialName = $form.data('rate-name');
                                        if ((!specialName || specialName === '') && $form.data('special-name')) specialName = $form.data('special-name');
                                        if ((!specialName || specialName === '') && rateIdLocal) specialName = 'Special' + ' (ID: ' + rateIdLocal + ')';
                                    }
                                } catch (e) {}
                                const hotelNameSpan = bookingDetailsDiv.querySelector('.mlb-hotel-name'); const specialNameSpan = bookingDetailsDiv.querySelector('.mlb-special-name'); if (hotelNameSpan) hotelNameSpan.textContent = hotelName; if (specialNameSpan) specialNameSpan.textContent = specialName;
                                try { console.debug('[MLB Modal Picker] resolved names on select', { formId: formId, hotelName: hotelName, specialName: specialName, rateIdLocal: rateIdLocal, dataset: $form[0].dataset, inputs: Array.from($form.find('input')).map(function(i){return {name:i.name,value:i.value}}) }); } catch (e) {}
                                setTimeout(function(){ if (rightColumn) rightColumn.classList.add('mlb-expanded'); },50);
                                try { if (modalSubmitBtn) modalSubmitBtn.disabled = false; } catch (e) {}
                            }
                        });
                        if (modalSubmitBtn) modalSubmitBtn.disabled = false;
                    }
                };

                const picker = new CoreClass(pickerConfig);

                setTimeout(function(){ const shadowHost = calendarDiv.querySelector('.container'); if (shadowHost && shadowHost.shadowRoot){ const shadowRoot = shadowHost.shadowRoot; const customStyle = document.createElement('style'); customStyle.textContent = ` .container.range-plugin .calendar > .days-grid > .day.start, .container.range-plugin .calendar > .days-grid > .day.end { background-color: ${calendarColors.startend_bg} !important; color: ${calendarColors.startend_color} !important; } .container.range-plugin .calendar > .days-grid > .day.in-range { background-color: ${calendarColors.inrange_bg} !important; } .container.range-plugin .calendar > .days-grid > .day.start::after, .container.range-plugin .calendar > .days-grid > .day.end::after { background-color: ${calendarColors.startend_bg} !important; } `; shadowRoot.appendChild(customStyle); } },100);

                $form.data('picker', picker);
                if ($daterangeInput && $daterangeInput.length) $daterangeInput.data('picker', picker);

                // Update modal submit button for specials and wire submit behavior
                try {
                    if (modalSubmitBtn) {
                        modalSubmitBtn.textContent = mlbGettext('Book Special');
                        // Allow specials to proceed without preselecting dates
                        modalSubmitBtn.disabled = false;
                        modalSubmitBtn.addEventListener('click', function() {
                            var arrivalDMY = $checkinHidden.val();
                            var departureDMY = $checkoutHidden.val();

                            function toISO(dmy) { if (!dmy) return ''; var parts = dmy.split('-'); if (parts.length === 3) return parts[2] + '-' + parts[1] + '-' + parts[0]; return dmy; }

                            var arrivalISO = toISO(arrivalDMY);
                            var departureISO = toISO(departureDMY);
                            var hotelId = $form.data('hotel-id') || $form.find('input[name="hotel_id"]').val() || '';
                            var rateId = $form.data('rate-id') || $form.data('special-id') || $form.find('input[name="special_id"]').val() || $form.find('input[name="rate_id"]').val() || '';

                            var submitDetail = {
                                arrivalISO: arrivalISO,
                                departureISO: departureISO,
                                hotel: hotelId,
                                rate: rateId,
                                __handled: false
                            };

                            try {
                                var submitEvent = new CustomEvent('mlb-submit', { bubbles: true, detail: submitDetail });
                                $form[0].dispatchEvent(submitEvent);
                            } catch (evtErr) {
                                try {
                                    var legacyEvent = document.createEvent('CustomEvent');
                                    legacyEvent.initCustomEvent('mlb-submit', true, true, submitDetail);
                                    $form[0].dispatchEvent(legacyEvent);
                                } catch (dispatchErr) {
                                    submitDetail.__handled = false;
                                }
                            }

                            if (!submitDetail.__handled) {
                                var bookingPageUrl = (typeof cqb_params !== 'undefined' && cqb_params && cqb_params.booking_page_url) ? cqb_params.booking_page_url : '';
                                handleSpecialFallback($form, submitDetail, bookingPageUrl);
                            }

                            try { closeModal(); } catch (err) {}
                        });
                    }
                } catch (err) {
                    console.error('Error wiring modal submit for special form', err);
                }

            } catch(e){ console.error('[MLB Datepicker] Error during initialization:', e); }
        }

        initPicker();
    }

    // Expose initializer globally so modal dispatcher can call it
    try { window.initSpecialModalDatePicker = window.initSpecialModalDatePicker || initSpecialModalDatePicker; } catch (e) {}

    function handleFormSubmission($form) {
        // Allow submission even without dates for specials
        $form[0].submit();
    }

})(jQuery);
