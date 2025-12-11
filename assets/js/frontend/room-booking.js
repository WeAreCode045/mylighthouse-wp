/**
 * Room Booking Component
 * Handles "Book This Room" button functionality and room-specific form interactions
 */

(function() {
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

    /**
     * Room Booking Class
     * Encapsulates all room booking functionality
     */
    class RoomBooking {
        constructor(formElement) {
            this.form = formElement;
            this.bookRoomBtn = formElement.querySelector('.mlb-book-room-btn');
            // Also check for buttons with data-trigger-modal attribute
            if (!this.bookRoomBtn) {
                this.bookRoomBtn = formElement.querySelector('[data-trigger-modal="true"]');
            }
            try { this.hotelId = window.mlbGetFormValue(this.form, ['hotelId','hotel_id','hotel-id','hotel']) || formElement.dataset.hotelId || ''; } catch (e) { this.hotelId = formElement.dataset.hotelId || ''; }
            try { this.roomId = window.mlbGetFormValue(this.form, ['roomId','room_id','room-id','room']) || formElement.dataset.roomId || ''; } catch (e) { this.roomId = formElement.dataset.roomId || ''; }
            try { this.hotelName = window.mlbGetFormValue(this.form, ['hotelName','hotel_name','hotel']) || formElement.dataset.hotelName || this.hotelId || 'Hotel'; } catch (e) { this.hotelName = formElement.dataset.hotelName || this.hotelId || 'Hotel'; }
            try { this.roomName = window.mlbGetFormValue(this.form, ['roomName','room_name','room']) || formElement.dataset.roomName || this.roomId || 'Room'; } catch (e) { this.roomName = formElement.dataset.roomName || this.roomId || 'Room'; }

            this.init();
        }

        /**
         * Initialize room booking functionality
         */
        init() {
            if (!this.bookRoomBtn) {
                return;
            }

            this.attachEventListeners();
        }

        /**
         * Attach event listeners
         */
        attachEventListeners() {
            // Listen for the "Book This Room" button click
            // Note: If button has data-trigger-modal, the main booking-form.js handles the modal opening
            this.bookRoomBtn.addEventListener('click', (e) => {
                // If the button has data-trigger-modal, let the main handler deal with it
                if (this.bookRoomBtn.getAttribute('data-trigger-modal') === 'true') {
                    // Just prevent default, the booking-form.js will handle opening the modal
                    e.preventDefault();
                    return;
                }
                
                // Otherwise, handle it here (legacy behavior)
                e.preventDefault();
                this.openDatePickerModal();
            });

            // Listen for custom submit event from datepicker
            this.form.addEventListener('mlb-submit', (e) => {
                this.handleSubmit(e);
            });
        }

        /**
         * Open the date picker modal
         */
        openDatePickerModal() {
            // Trigger calendar modal opening
            const event = new CustomEvent('mlb-open-calendar', {
                bubbles: true,
                detail: {
                    formElement: this.form,
                    isRoomForm: true
                }
            });
            this.form.dispatchEvent(event);
        }

        /**
         * Handle room booking form submission
         */
        handleSubmit(e) {
            // Prefer explicit details passed via event (dispatched by modal submit)
            var arrivalISO = '';
            var departureISO = '';
            var explicitRate = '';
            if (e && e.detail) {
                arrivalISO = e.detail.arrivalISO || e.detail.arrival || '';
                departureISO = e.detail.departureISO || e.detail.departure || '';
                explicitRate = e.detail.rate || e.detail.room || '';
            }

            // Fallback to hidden inputs if event detail not provided
            const checkinInput = this.form.querySelector('.mlb-checkin');
            const checkoutInput = this.form.querySelector('.mlb-checkout');
            if (!arrivalISO && checkinInput && checkinInput.value) arrivalISO = this.parseToISO(checkinInput.value);
            if (!departureISO && checkoutInput && checkoutInput.value) departureISO = this.parseToISO(checkoutInput.value);

            // Get booking page URL and result target from localized params
            const bookingPageUrl = (typeof cqb_params !== 'undefined' && cqb_params.booking_page_url)
                ? cqb_params.booking_page_url
                : '';
            // Use device-specific display mode if available
            const resultTarget = (typeof window.mlbGetDisplayModeForDevice === 'function')
                ? window.mlbGetDisplayModeForDevice()
                : ((typeof cqb_params !== 'undefined' && cqb_params.result_target) ? cqb_params.result_target : 'redirect');

            if (!bookingPageUrl && resultTarget !== 'redirect_engine') {
                console.error('Booking page URL not configured');
                return;
            }

            if (resultTarget === 'modal') {
                // Prefer rate if present in event detail, otherwise dataset or input
                var rateId = explicitRate || this.form.dataset.rateId || this.form.querySelector('input[name="rate"]')?.value || '';
                if (rateId) {
                    this.openBookingModal(bookingPageUrl, arrivalISO, departureISO, rateId, 'rate');
                } else {
                    // Use room id if available
                    var roomId = explicitRate || this.roomId || this.form.dataset.roomId;
                    this.openBookingModal(bookingPageUrl, arrivalISO, departureISO, roomId, 'room');
                }
            } else if (resultTarget === 'redirect_engine') {
                this.redirectToBookingEngine(arrivalISO, departureISO);
            } else {
                this.redirectToBookingPage(bookingPageUrl, arrivalISO, departureISO);
            }
        }

        /**
         * Parse date from d-m-Y to ISO Y-m-d format
         */
        parseToISO(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }

        /**
         * Open booking modal with booking engine
         */
        openBookingModal(bookingPageUrl, arrivalISO, departureISO, identifier, paramName) {
            if (typeof window.MLB_Modal === 'undefined' || typeof window.MLB_Modal.openBookingModal !== 'function') {
                console.error('Booking modal not available');
                this.redirectToBookingPage(bookingPageUrl, arrivalISO, departureISO);
                return;
            }

            // Use provided identifier/paramName if present (e.g., rate for specials), otherwise default to room
            const idToUse = identifier || this.roomId;
            const pName = paramName || 'room';

            window.MLB_Modal.openBookingModal(
                bookingPageUrl,
                this.hotelId,
                arrivalISO,
                departureISO,
                idToUse,
                pName
            );
        }

        /**
         * Redirect to booking page with parameters
         */
        redirectToBookingPage(bookingPageUrl, arrivalISO, departureISO) {
            const url = new URL(bookingPageUrl);
            url.searchParams.set('Arrival', arrivalISO);
            url.searchParams.set('Departure', departureISO);
            url.searchParams.set('hotel_id', this.hotelId);

            if (this.roomId) {
                url.searchParams.set('room', this.roomId);
            }

            window.location.href = url.toString();
        }

        /**
         * Redirect directly to booking engine with parameters
         */
        redirectToBookingEngine(arrivalISO, departureISO) {
            const bookingEngineBaseUrl = (window.MLBBookingEngineBase && window.MLBBookingEngineBase.url) || window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/';
            let engineUrl = bookingEngineBaseUrl + encodeURIComponent(this.hotelId) + '/Rooms/Select?Arrival=' + encodeURIComponent(arrivalISO) + '&Departure=' + encodeURIComponent(departureISO);
            
            if (this.roomId) {
                engineUrl += '&room=' + encodeURIComponent(this.roomId);
            }

            window.location.href = engineUrl;
        }

        /**
         * Redirect directly to booking engine with parameters
         */
        redirectToBookingEngine(arrivalISO, departureISO) {
            const bookingEngineBaseUrl = (window.MLBBookingEngineBase && window.MLBBookingEngineBase.url) || window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/';
            let engineUrl = bookingEngineBaseUrl + encodeURIComponent(this.hotelId) + '/Rooms/Select?Arrival=' + encodeURIComponent(arrivalISO) + '&Departure=' + encodeURIComponent(departureISO);
            
            if (this.roomId) {
                engineUrl += '&room=' + encodeURIComponent(this.roomId);
            }

            window.location.href = engineUrl;
        }

        /**
         * Check if form is a room-specific booking form
         */
        static isRoomForm(formElement) {
            return formElement.classList.contains('mlb-room-form-type');
        }

        /**
         * Get booking button text
         */
        getBookingButtonText() {
            return this.bookRoomBtn ? this.bookRoomBtn.textContent : mlbGettext('Book This Room');
        }

        /**
         * Enable/disable booking button
         */
        setButtonState(enabled) {
            if (this.bookRoomBtn) {
                this.bookRoomBtn.disabled = !enabled;
            }
        }
    }

    /**
     * Initialize all room booking forms on page load
     */
    // Export class and per-form initializer for external use
    window.MLB_RoomBooking = window.MLB_RoomBooking || {};
    window.MLB_RoomBooking.RoomBooking = RoomBooking;

    // Initialize a single form element with RoomBooking
    window.MLB_RoomBooking.initForm = window.MLB_RoomBooking.initForm || function(formElement) {
        try {
            if (!formElement) return null;
            // Accept either jQuery-wrapped or raw DOM element
            var el = (formElement.jquery && formElement.length) ? formElement[0] : (formElement.nodeType ? formElement : null);
            if (!el) return null;
            window.MLB_RoomBooking.instances = window.MLB_RoomBooking.instances || [];
            const instance = new RoomBooking(el);
            window.MLB_RoomBooking.instances.push(instance);
            return instance;
        } catch (e) { console.error('MLB_RoomBooking.initForm error', e); return null; }
    };

})();
