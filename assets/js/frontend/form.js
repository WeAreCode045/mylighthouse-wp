/**
 * Frontend Form JavaScript
 * Handles form submission, validation, hotel select dropdown, and icon colors
 */

document.addEventListener('DOMContentLoaded', function() {
    // JS gettext helper: prefer wp.i18n.__() when available
    function mlbGettext(str) {
        try {
            if (typeof wp !== 'undefined' && wp.i18n && typeof wp.i18n.__ === 'function') {
                return wp.i18n.__(str, 'mylighthouse-booker');
            }
        } catch (e) {}
        return str;
    }
    // ========================================================================
    // CONFIGURATION
    // ========================================================================

    if (typeof window.MLBBookingEngineBase === 'undefined') {
        window.MLBBookingEngineBase = 'https://bookingengine.mylighthouse.com/';
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    const bookingForms = document.querySelectorAll('.mlb-form');
    var cachedSpinnerImageUrl = null;
    var redirectSpinnerShown = false;

    function getSpinnerImageUrl() {
        if (cachedSpinnerImageUrl !== null) {
            return cachedSpinnerImageUrl;
        }
        cachedSpinnerImageUrl = '';
        try {
            if (typeof cqb_params !== 'undefined' && cqb_params && cqb_params.spinner_image_url) {
                cachedSpinnerImageUrl = cqb_params.spinner_image_url;
            }
        } catch (e) {
            cachedSpinnerImageUrl = '';
        }
        if (!cachedSpinnerImageUrl && typeof window.mlb_spinner_image_url === 'string') {
            cachedSpinnerImageUrl = window.mlb_spinner_image_url;
        }
        return cachedSpinnerImageUrl;
    }

    function createSpinnerBox() {
        var spinnerBox = document.createElement('div');
        spinnerBox.className = 'mlb-spinner-box';

        var imgDiv = document.createElement('div');
        imgDiv.className = 'mlb-spinner-image';
        var spinnerUrl = getSpinnerImageUrl();
        if (spinnerUrl) {
            imgDiv.dataset.bgImage = spinnerUrl;
            try {
                imgDiv.style.backgroundImage = 'url(' + spinnerUrl + ')';
            } catch (e) {
                /* ignore */
            }
        }
        spinnerBox.appendChild(imgDiv);

        return spinnerBox;
    }

    function showBookingRedirectSpinner(message) {
        if (redirectSpinnerShown) {
            return;
        }
        redirectSpinnerShown = true;

        var overlay = document.createElement('div');
        overlay.className = 'mlb-redirect-overlay';
        overlay.setAttribute('role', 'status');
        overlay.setAttribute('aria-live', 'polite');

        try {
            overlay.appendChild(createSpinnerBox());
        } catch (e) {
            console.warn('Failed to build spinner box for redirect overlay', e);
        }

        document.body.appendChild(overlay);
        requestAnimationFrame(function() {
            overlay.classList.add('is-visible');
        });
    }

    if (bookingForms.length) {
        bookingForms.forEach(function(formEl) {
            try { initCustomHotelSelect(formEl); } catch (e) {}

            // Decide which frontend modules to initialize per form type
            try {
                var isRoom = formEl.classList.contains('mlb-room-form-type') || formEl.classList.contains('mlb-room-form') || formEl.dataset.roomId;
                var isSpecial = formEl.classList.contains('mlb-special-form-type') || formEl.dataset.rateId || formEl.dataset.specialId;

                if (isRoom) {
                    if (typeof window.initRoomForm === 'function') {
                        try { window.initRoomForm(formEl); } catch (e) { console.error('initRoomForm failed', e); }
                    }
                    if (window.MLB_RoomBooking && typeof window.MLB_RoomBooking.initForm === 'function') {
                        try { window.MLB_RoomBooking.initForm(formEl); } catch (e) { console.error('MLB_RoomBooking.initForm failed', e); }
                    }
                } else if (isSpecial) {
                    if (typeof window.initSpecialForm === 'function') {
                        try { window.initSpecialForm(formEl); } catch (e) { console.error('initSpecialForm failed', e); }
                    }
                    if (window.MLB_SpecialBooking && typeof window.MLB_SpecialBooking.initForm === 'function') {
                        try { window.MLB_SpecialBooking.initForm(formEl); } catch (e) { console.error('MLB_SpecialBooking.initForm failed', e); }
                    }
                } else {
                    // Hotel form: initialize booking-form inline picker behavior
                    if (typeof window.initBookingForm === 'function') {
                        try { window.initBookingForm(formEl); } catch (e) { console.error('initBookingForm failed', e); }
                    }
                }
            } catch (e) {
                console.error('Per-form initializer error', e);
            }
        });
    }

    // Also check for forms that might be added later (e.g., via AJAX or dynamic content)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    // Check if the added node is a form or contains forms
                    const forms = node.classList && node.classList.contains('mlb-form') ? [node] : node.querySelectorAll ? node.querySelectorAll('.mlb-form') : [];
                    forms.forEach(initCustomHotelSelect);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================

    /**
     * Convert d-m-Y string or Date to ISO format (YYYY-MM-DD)
     */
    function toISO(v) {
        if (!v) return '';
        if (v instanceof Date) {
            const yyyy = v.getFullYear();
            const mm = String(v.getMonth() + 1).padStart(2, '0');
            const dd = String(v.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        }
        // Parse d-m-Y format
        const parts = v.split('-').map(function(p) { return parseInt(p, 10); });
        if (parts.length === 3) {
            const d = new Date(parts[2], parts[1] - 1, parts[0]);
            if (!isNaN(d.getTime())) {
                return toISO(d);
            }
        }
        return v;
    }

    // ========================================================================
    // FORM SUBMISSION HANDLING
    // ========================================================================

    /**
     * Handle form submission logic
     * Direct redirect naar MyLighthouse booking engine
     */
    function handleFormSubmission(bookingForm) {
        const hotelSelect = bookingForm.querySelector('.mlb-hotel-select');
        const hotelId = hotelSelect ? hotelSelect.value : bookingForm.dataset.hotelId;

        if (!hotelId) {
            alert( mlbGettext('Error: Hotel ID could not be found. Please check plugin settings or shortcode.') );
            return;
        }

        const arrival = bookingForm.querySelector('.mlb-checkin').value;
        const departure = bookingForm.querySelector('.mlb-checkout').value;
        const arrivalISO = toISO(arrival);
        const departureISO = toISO(departure);

        // Build direct MyLighthouse booking engine URL
        const bookingEngineBase = window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/';
        
        // Check for rate/special ID
        const rateId = bookingForm.dataset.rateId || bookingForm.querySelector('input[name="rate"]')?.value;
        
        let bookingUrl;
        if (rateId) {
            // Special rate booking
            const qs = new URLSearchParams();
            qs.set('Rate', rateId);
            if (arrivalISO) qs.set('Arrival', arrivalISO);
            if (departureISO) qs.set('Departure', departureISO);
            bookingUrl = `${bookingEngineBase}${encodeURIComponent(hotelId)}/Rooms/GeneralAvailability`;
            const query = qs.toString();
            if (query) {
                bookingUrl += `?${query}`;
            }
        } else {
            // Regular room booking
            bookingUrl = `${bookingEngineBase}${encodeURIComponent(hotelId)}/Rooms/Select?Arrival=${encodeURIComponent(arrivalISO)}&Departure=${encodeURIComponent(departureISO)}`;
            
            const roomId = bookingForm.dataset.roomId;
            if (roomId) {
                bookingUrl += `&room=${encodeURIComponent(roomId)}`;
            }
        }

        // Show redirect spinner and redirect
        try {
            showBookingRedirectSpinner();
        } catch (e) { /* ignore */ }
        
        window.location.href = bookingUrl;
    }

    // Set up form submission handlers
    bookingForms.forEach(function(bookingForm) {
        // Skip special and room forms: they have their own dedicated handlers (special-form.js, room-form.js, etc.)
        if (bookingForm.classList.contains('mlb-special-form-type') || bookingForm.classList.contains('mlb-room-form-type')) {
            return;
        }

        const submitBtn = bookingForm.querySelector('.mlb-submit-btn');

        if (submitBtn) {
            // If this button is marked as a modal trigger, don't attach the
            // default submission handler here — modal-handling scripts (room/special)
            // will manage the click. Attach the handler only when the button is
            // not a modal trigger at bind time.
            try {
                var isModalTrigger = submitBtn.getAttribute && submitBtn.getAttribute('data-trigger-modal') === 'true';
            } catch (e) {
                isModalTrigger = false;
            }

            if (!isModalTrigger) {
                submitBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    handleFormSubmission(bookingForm);
                });
            }
        }
    });

    // ========================================================================
    // CUSTOM HOTEL SELECT DROPDOWN
    // ========================================================================

    /**
     * Initialize custom-styled hotel select dropdown
     */
    function initCustomHotelSelect(bookingForm) {
        const native = bookingForm.querySelector('.mlb-hotel-select');
        if (!native || native.dataset.mlbEnhanced === '1') return;

        // Check if a custom select wrapper already exists
        if (bookingForm.querySelector('.mlb-custom-select')) return;

        native.dataset.mlbEnhanced = '1';

        const wrapper = document.createElement('div');
        wrapper.className = 'mlb-custom-select';

        const toggle = document.createElement('div');
        toggle.className = 'mlb-custom-select__toggle';
        toggle.setAttribute('role', 'button');
        toggle.setAttribute('tabindex', '0');
        toggle.setAttribute('aria-haspopup', 'listbox');
        toggle.setAttribute('aria-expanded', 'false');

        const labelSpan = document.createElement('span');
        labelSpan.className = 'mlb-custom-select__label';
        labelSpan.textContent = native.options[native.selectedIndex] ? native.options[native.selectedIndex].text : '';

        const arrow = document.createElement('span');
        arrow.className = 'mlb-custom-select__arrow';
        try {
            var arrowTpl = document.getElementById('mlb-icon-arrow-down');
            if (arrowTpl && arrowTpl.content && arrowTpl.content.firstElementChild) {
                arrow.appendChild(arrowTpl.content.firstElementChild.cloneNode(true));
            } else {
                try {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString('<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>', 'image/svg+xml');
                    if (doc && doc.documentElement) arrow.appendChild(doc.documentElement);
                } catch (err) {
                    arrow.innerText = '';
                }
            }
        } catch (e) {
            try {
                var parser2 = new DOMParser();
                var doc2 = parser2.parseFromString('<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>', 'image/svg+xml');
                if (doc2 && doc2.documentElement) arrow.appendChild(doc2.documentElement);
            } catch (err2) {
                arrow.innerText = '';
            }
        }

        toggle.appendChild(labelSpan);
        toggle.appendChild(arrow);

        const list = document.createElement('div');
        list.className = 'mlb-custom-select__list';
        list.setAttribute('role', 'listbox');
        list.setAttribute('tabindex', '-1');

        // Populate dropdown items
        Array.prototype.forEach.call(native.options, function(opt, idx) {
            if (opt.disabled) return;

            const item = document.createElement('div');
            item.className = 'mlb-custom-select__item';
            item.setAttribute('role', 'option');
            item.setAttribute('data-value', opt.value);
            item.setAttribute('data-index', idx);
            item.textContent = opt.text;

            if (opt.selected) {
                item.classList.add('mlb-selected');
                item.setAttribute('aria-selected', 'true');
            } else {
                item.setAttribute('aria-selected', 'false');
            }

            item.addEventListener('click', function() {
                native.selectedIndex = idx;
                native.dispatchEvent(new Event('change', { bubbles: true }));
                labelSpan.textContent = opt.text;

                const prev = list.querySelector('.mlb-selected');
                if (prev) prev.classList.remove('mlb-selected');
                item.classList.add('mlb-selected');
                item.setAttribute('aria-selected', 'true');

                closeList();
            });
            list.appendChild(item);
        });

        native.classList.add('mlb-native-select-hidden');
        native.parentNode.insertBefore(wrapper, native);
        wrapper.appendChild(native);
        wrapper.appendChild(toggle);
        wrapper.appendChild(list);

        list.style.display = 'none';

        function openList() {
            list.style.display = 'block';
            // Decide drop direction based on viewport space so bottom-placed forms open upward
            try {
                const toggleRect = toggle.getBoundingClientRect();
                const listHeight = list.scrollHeight || list.offsetHeight || 0;
                const spaceBelow = window.innerHeight - toggleRect.bottom;
                const spaceAbove = toggleRect.top;
                const shouldDropUp = listHeight > spaceBelow && spaceAbove > spaceBelow;
                if (shouldDropUp) {
                    list.style.top = 'auto';
                    list.style.bottom = '100%';
                    wrapper.classList.add('mlb-custom-select--dropup');
                } else {
                    list.style.bottom = 'auto';
                    list.style.top = '';
                    wrapper.classList.remove('mlb-custom-select--dropup');
                }
            } catch (e) { wrapper.classList.remove('mlb-custom-select--dropup'); }
            toggle.setAttribute('aria-expanded', 'true');
            document.addEventListener('click', outsideClick);
        }

        function closeList() {
            list.style.display = 'none';
            toggle.setAttribute('aria-expanded', 'false');
            wrapper.classList.remove('mlb-custom-select--dropup');
            document.removeEventListener('click', outsideClick);
        }

        function outsideClick(e) {
            if (!wrapper.contains(e.target)) closeList();
        }

        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (toggle.getAttribute('aria-expanded') === 'true') {
                closeList();
            } else {
                openList();
            }
        });

        toggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (toggle.getAttribute('aria-expanded') === 'true') {
                    closeList();
                } else {
                    openList();
                }
            }
        });

        native.addEventListener('change', function() {
            const opt = native.options[native.selectedIndex];
            labelSpan.textContent = opt ? opt.text : '';
            const prev = list.querySelector('.mlb-selected');
            if (prev) prev.classList.remove('mlb-selected');
            const newItem = list.querySelector('[data-index="' + native.selectedIndex + '"]');
            if (newItem) newItem.classList.add('mlb-selected');
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeList();
        });
    }
});
