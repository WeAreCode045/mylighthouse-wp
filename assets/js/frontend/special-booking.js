/**
 * Special Booking Component
 * Handles "Book This Special" button functionality and special-specific form interactions
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

    class SpecialBooking {
        constructor(formElement) {
            this.form = formElement;
            this.skipDates = (formElement.getAttribute('data-skip-dates') === '1') || (formElement.getAttribute('data-skip-dates') === 'true') || (formElement.dataset.skipDates === true);
            this.bookBtn = formElement.querySelector('.mlb-book-room-btn, .mlb-submit-btn');
            if (!this.bookBtn) {
                this.bookBtn = formElement.querySelector('[data-trigger-modal="true"]');
            }
            try { this.hotelId = window.mlbGetFormValue(this.form, ['hotelId','hotel_id','hotel-id','hotel']) || formElement.dataset.hotelId || ''; } catch (e) { this.hotelId = formElement.dataset.hotelId || ''; }
            try { this.rateId = window.mlbGetFormValue(this.form, ['rateId','rate_id','rate-id','specialId','special_id','special-id','rate']) || formElement.dataset.rateId || formElement.dataset.specialId || ''; } catch (e) { this.rateId = formElement.dataset.rateId || formElement.dataset.specialId || ''; }
            try { this.hotelName = window.mlbGetFormValue(this.form, ['hotelName','hotel_name','hotel']) || formElement.dataset.hotelName || this.hotelId || 'Hotel'; } catch (e) { this.hotelName = formElement.dataset.hotelName || this.hotelId || 'Hotel'; }
            try { this.rateName = window.mlbGetFormValue(this.form, ['rateName','rate_name','rate','specialName','special_name']) || formElement.dataset.rateName || this.rateId || 'Rate'; } catch (e) { this.rateName = formElement.dataset.rateName || this.rateId || 'Rate'; }

            this.init();
        }

        init() {
            if (!this.bookBtn) return;
            this.attachEventListeners();
        }

        attachEventListeners() {
            this.bookBtn.addEventListener('click', (e) => {
                if (this.bookBtn.getAttribute('data-trigger-modal') === 'true') {
                    e.preventDefault();
                    return;
                }
                e.preventDefault();
                this.openDatePickerModal();
            });

            this.form.addEventListener('mlb-submit', (e) => {
                this.handleSubmit(e);
            });
        }

        openDatePickerModal() {
            const event = new CustomEvent('mlb-open-calendar', {
                bubbles: true,
                detail: {
                    formElement: this.form,
                    isSpecialForm: true
                }
            });
            this.form.dispatchEvent(event);
        }

        handleSubmit(e) {
            if (e && e.detail) {
                e.detail.__handled = true;
            }
            // Prefer values passed in the event detail
            var arrivalISO = '';
            var departureISO = '';
            var explicitRate = '';
            if (e && e.detail) {
                arrivalISO = e.detail.arrivalISO || e.detail.arrival || '';
                departureISO = e.detail.departureISO || e.detail.departure || '';
                explicitRate = e.detail.rate || e.detail.room || '';
            }

            const checkinInput = this.form.querySelector('.mlb-checkin');
            const checkoutInput = this.form.querySelector('.mlb-checkout');
            if (!arrivalISO && checkinInput && checkinInput.value) arrivalISO = this.parseToISO(checkinInput.value);
            if (!departureISO && checkoutInput && checkoutInput.value) departureISO = this.parseToISO(checkoutInput.value);

            // Direct redirect naar MyLighthouse booking engine
            this.redirectToBookingPage('', arrivalISO, departureISO);
        }

        parseToISO(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }

        openBookingModal(bookingPageUrl, arrivalISO, departureISO, identifier, paramName) {
            if (typeof window.MLB_Modal === 'undefined' || typeof window.MLB_Modal.openBookingModal !== 'function') {
                console.error('Booking modal not available');
                this.redirectToBookingPage(bookingPageUrl, arrivalISO, departureISO);
                return;
            }

            // Pass rate id and param name 'rate'
            window.MLB_Modal.openBookingModal(
                bookingPageUrl,
                this.hotelId,
                arrivalISO,
                departureISO,
                identifier || this.rateId,
                paramName || 'rate'
            );
        }

        redirectToBookingPage(bookingPageUrl, arrivalISO, departureISO) {
            // Build direct MyLighthouse booking engine URL
            const bookingEngineBase = window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/';
            
            let bookingUrl;
            if (this.rateId) {
                // Special rate booking
                const qs = new URLSearchParams();
                qs.set('Rate', this.rateId);
                if (arrivalISO) qs.set('Arrival', arrivalISO);
                if (departureISO) qs.set('Departure', departureISO);
                bookingUrl = `${bookingEngineBase}${encodeURIComponent(this.hotelId)}/Rooms/GeneralAvailability`;
                const query = qs.toString();
                if (query) {
                    bookingUrl += `?${query}`;
                }
            } else {
                // Regular room booking (fallback)
                bookingUrl = `${bookingEngineBase}${encodeURIComponent(this.hotelId)}/Rooms/Select`;
                if (arrivalISO && departureISO) {
                    bookingUrl += `?Arrival=${encodeURIComponent(arrivalISO)}&Departure=${encodeURIComponent(departureISO)}`;
                }
            }

            window.location.href = bookingUrl;
        }

    // Export class and per-form initializer for external use
    window.MLB_SpecialBooking = window.MLB_SpecialBooking || {};
    window.MLB_SpecialBooking.SpecialBooking = SpecialBooking;

    window.MLB_SpecialBooking.initForm = window.MLB_SpecialBooking.initForm || function(formElement) {
        try {
            if (!formElement) return null;
            var el = (formElement.jquery && formElement.length) ? formElement[0] : (formElement.nodeType ? formElement : null);
            if (!el) return null;
            window.MLB_SpecialBooking.instances = window.MLB_SpecialBooking.instances || [];
            const instance = new SpecialBooking(el);
            window.MLB_SpecialBooking.instances.push(instance);
            return instance;
        } catch (e) { console.error('MLB_SpecialBooking.initForm error', e); return null; }
    };

})();
