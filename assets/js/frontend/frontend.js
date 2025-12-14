/**
 * Frontend Forms Handler
 * Handles all booking form types: hotel, room, special
 * Includes CalendarModal for date range selection
 *
 * @package Mylighthouse_Booker
 */

(function() {
    'use strict';

    // Calendar Modal Class
    class CalendarModal {
        constructor(formElement, options = {}) {
            this.form = formElement;
            this.formId = formElement.id || 'mlb-form-' + Math.random().toString(36).substr(2, 9);
            this.formType = options.formType || 'hotel';
            this.onDateSelect = options.onDateSelect || null;
            
            // Get form data
            this.hotelId = formElement.dataset.hotelId || '';
            this.hotelName = formElement.dataset.hotelName || '';
            this.roomId = formElement.dataset.roomId || '';
            this.roomName = formElement.dataset.roomName || '';
            this.rateId = formElement.dataset.rateId || formElement.dataset.specialId || '';
            
            // Hidden date inputs
            this.checkinInput = formElement.querySelector('.mlb-checkin');
            this.checkoutInput = formElement.querySelector('.mlb-checkout');
            
            this.overlay = null;
            this.picker = null;
            this.isInitialized = false;
        }

        init() {
            if (this.isInitialized) return;
            this.createModalOverlay();
            this.initializePicker();
            this.attachEventListeners();
            this.isInitialized = true;
        }

        createModalOverlay() {
            // Get template from localized data
            var template = (typeof cqb_params !== 'undefined' && cqb_params.calendar_modal_template) 
                ? cqb_params.calendar_modal_template 
                : '';
            
            if (!template) {
                console.error('MLB Calendar: Modal template not found');
                return;
            }
            
            // Create temporary container to parse template
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = template;
            
            // Get the overlay element
            this.overlay = tempDiv.querySelector('.mlb-calendar-modal-overlay');
            if (!this.overlay) {
                console.error('MLB Calendar: Invalid modal template structure');
                return;
            }
            
            // Set form ID
            this.overlay.setAttribute('data-form-id', this.formId);
            
            // Store hotel and room info for later use
            this.detailsSection = this.overlay.querySelector('.mlb-modal-details-section');
            this.calendarSection = this.overlay.querySelector('.mlb-modal-calendar-section');
            
            document.body.appendChild(this.overlay);
            
            // Store reference on form
            this.form._mlbModalOverlay = this.overlay;
        }

        initializePicker() {
            var self = this;
            var calendarDiv = this.overlay.querySelector('.mlb-modal-calendar');
            if (!calendarDiv) return;

            // Wait for EasePick to be available
            var checkEasepick = function() {
                if (!window.easepick || !window.easepick.Core) {
                    setTimeout(checkEasepick, 100);
                    return;
                }

                var Core = window.easepick.Core;
                var RangePlugin = window.easepick.RangePlugin;
                var LockPlugin = window.easepick.LockPlugin;

                // Clear any existing content
                while (calendarDiv.firstChild) {
                    calendarDiv.removeChild(calendarDiv.firstChild);
                }

                var pickerConfig = {
                    element: calendarDiv,
                    inline: true,
                    plugins: [RangePlugin, LockPlugin],
                    css: [
                        '/wp-content/plugins/mylighthouse-booker/assets/vendor/easepick/easepick.css'
                    ],
                    RangePlugin: {
                        tooltip: true,
                        locale: {
                            one: 'night',
                            other: 'nights'
                        }
                    },
                    LockPlugin: {
                        minDate: new Date()
                    },
                    setup: function(picker) {
                        picker.on('select', function(e) {
                            self.handleDateSelect(e.detail);
                        });
                    },
                    lang: 'en-GB',
                    format: 'DD MMM - DD MMM YYYY'
                };

                self.picker = new Core(pickerConfig);
            };

            checkEasepick();
        }

        handleDateSelect(detail) {
            var start = detail.start;
            var end = detail.end;
            if (!start || !end) return;

            // Update hidden inputs
            if (this.checkinInput) {
                this.checkinInput.value = this.formatDMY(start);
            }
            if (this.checkoutInput) {
                this.checkoutInput.value = this.formatDMY(end);
            }

            // Format dates for display
            var arrivalStr = start.toLocaleDateString('en-GB', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
            var departureStr = end.toLocaleDateString('en-GB', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });

            // Call callback if provided
            if (this.onDateSelect) {
                this.onDateSelect({
                    start: start,
                    end: end,
                    arrivalDMY: this.formatDMY(start),
                    departureDMY: this.formatDMY(end),
                    arrivalISO: this.toISO(this.formatDMY(start)),
                    departureISO: this.toISO(this.formatDMY(end)),
                    arrivalStr: arrivalStr,
                    departureStr: departureStr
                });
            }

            // For hotel forms, close modal immediately and update daterange input
            if (this.formType === 'hotel') {
                var daterangeInput = this.form.querySelector('.mlb-daterange');
                if (daterangeInput) {
                    daterangeInput.value = arrivalStr + ' → ' + departureStr;
                }
                this.close();
            }

            // For room forms, load and show booking details
            if (this.formType === 'room') {
                this.loadBookingDetails(arrivalStr, departureStr);
            }
        }

        loadBookingDetails(arrivalStr, departureStr) {
            var self = this;
            
            if (!this.detailsSection) {
                console.error('MLB: Details section not found');
                return;
            }
            
            // Load the details component via AJAX
            jQuery.ajax({
                url: cqb_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mlb_get_booking_details',
                    nonce: cqb_params.nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Inject the component HTML
                        self.detailsSection.innerHTML = response.data.html;
                        
                        // Populate with data
                        var hotelNameEl = self.detailsSection.querySelector('.mlb-hotel-name');
                        var roomNameEl = self.detailsSection.querySelector('.mlb-room-name');
                        var arrivalSpan = self.detailsSection.querySelector('.mlb-arrival-date');
                        var departureSpan = self.detailsSection.querySelector('.mlb-departure-date');
                        var submitBtn = self.detailsSection.querySelector('.mlb-modal-submit-btn');
                        
                        if (hotelNameEl) hotelNameEl.textContent = self.hotelName || 'Hotel';
                        if (roomNameEl) roomNameEl.textContent = self.roomName || 'Room';
                        if (arrivalSpan) arrivalSpan.textContent = arrivalStr;
                        if (departureSpan) departureSpan.textContent = departureStr;
                        if (submitBtn) submitBtn.disabled = false;
                        
                        // Attach event listeners to new elements
                        self.attachDetailsEventListeners();
                        
                        // Expand modal container
                        var modalContainer = self.overlay.querySelector('.mlb-calendar-modal-container');
                        if (modalContainer) {
                            modalContainer.classList.add('mlb-expanded');
                        }
                        
                        // Show details section with animation
                        setTimeout(function() {
                            self.detailsSection.classList.add('mlb-visible');
                            
                            // On mobile, hide calendar
                            if (window.innerWidth <= 768 && self.calendarSection) {
                                self.calendarSection.classList.add('mlb-hidden');
                            }
                        }, 50);
                    }
                },
                error: function() {
                    console.error('MLB: Failed to load booking details');
                }
            });
        }
        
        attachDetailsEventListeners() {
            var self = this;
            
            // Submit button
            var submitBtn = this.detailsSection.querySelector('.mlb-modal-submit-btn');
            if (submitBtn) {
                submitBtn.addEventListener('click', function() {
                    self.close();
                    var event = new CustomEvent('mlb-modal-submit', {
                        bubbles: true,
                        detail: {
                            arrivalDMY: self.checkinInput ? self.checkinInput.value : '',
                            departureDMY: self.checkoutInput ? self.checkoutInput.value : '',
                            arrivalISO: self.toISO(self.checkinInput ? self.checkinInput.value : ''),
                            departureISO: self.toISO(self.checkoutInput ? self.checkoutInput.value : '')
                        }
                    });
                    self.form.dispatchEvent(event);
                });
            }
            
            // Back button
            var backBtn = this.detailsSection.querySelector('.mlb-modal-back-btn');
            if (backBtn) {
                backBtn.addEventListener('click', function() {
                    self.detailsSection.classList.remove('mlb-visible');
                    if (self.calendarSection) {
                        self.calendarSection.classList.remove('mlb-hidden');
                    }
                });
            }
        }

        attachEventListeners() {
            var self = this;
            
            // Close button
            var closeBtn = this.overlay.querySelector('.mlb-calendar-modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() { self.close(); });
            }

            // Click outside to close
            this.overlay.addEventListener('click', function(e) {
                if (e.target === self.overlay) {
                    self.close();
                }
            });
        }

        open() {
            if (!this.isInitialized) {
                this.init();
            }
            this.overlay.classList.add('mlb-calendar-modal-show');
            document.body.style.overflow = 'hidden';
        }

        close() {
            this.overlay.classList.remove('mlb-calendar-modal-show');
            document.body.style.overflow = '';
        }

        formatDMY(date) {
            var dd = String(date.getDate()).padStart(2, '0');
            var mm = String(date.getMonth() + 1).padStart(2, '0');
            var yyyy = date.getFullYear();
            return dd + '-' + mm + '-' + yyyy;
        }

        toISO(dmy) {
            if (!dmy) return '';
            var parts = dmy.split('-');
            if (parts.length !== 3) return dmy;
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }
    }

    // Get booking engine base URL
    function getBookingEngineURL() {
        return (typeof cqb_params !== 'undefined' && cqb_params.booking_page_url) 
            ? cqb_params.booking_page_url 
            : (window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/');
    }

    // Show loading spinner
    function showSpinner() {
        if (typeof window.showBookingRedirectSpinner === 'function') {
            window.showBookingRedirectSpinner();
        }
    }

    // Hotel Form Handler
    class HotelForm {
        constructor(formElement) {
            this.form = formElement;
            this.hotelSelect = formElement.querySelector('.mlb-hotel-select');
            this.daterangeInput = formElement.querySelector('.mlb-daterange');
            this.checkinInput = formElement.querySelector('.mlb-checkin');
            this.checkoutInput = formElement.querySelector('.mlb-checkout');
            this.submitBtn = formElement.querySelector('.mlb-submit-btn');
            this.calendarModal = null;
            
            this.init();
        }

        init() {
            if (!this.daterangeInput || !this.submitBtn) return;
            
            // Initialize calendar modal
            this.calendarModal = new CalendarModal(this.form, {
                formType: 'hotel'
            });
            
            // Open calendar on daterange click
            this.daterangeInput.addEventListener('click', (e) => {
                e.preventDefault();
                this.calendarModal.open();
            });
            
            // Handle form submission
            this.submitBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }

        handleSubmit() {
            var hotelId = this.hotelSelect ? this.hotelSelect.value : this.form.dataset.hotelId;
            var arrivalISO = this.toISO(this.checkinInput ? this.checkinInput.value : '');
            var departureISO = this.toISO(this.checkoutInput ? this.checkoutInput.value : '');
            
            if (!hotelId) {
                alert('Please select a hotel');
                return;
            }
            
            if (!arrivalISO || !departureISO) {
                alert('Please select check-in and check-out dates');
                return;
            }
            
            var bookingEngineURL = getBookingEngineURL();
            var redirectURL = bookingEngineURL + encodeURIComponent(hotelId) + '/Rooms/Select?Arrival=' + 
                encodeURIComponent(arrivalISO) + '&Departure=' + encodeURIComponent(departureISO);
            
            showSpinner();
            window.location.href = redirectURL;
        }

        toISO(dmy) {
            if (!dmy) return '';
            var parts = dmy.split('-');
            if (parts.length !== 3) return dmy;
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }
    }

    // Room Form Handler
    class RoomForm {
        constructor(formElement) {
            this.form = formElement;
            this.hotelId = formElement.dataset.hotelId || '';
            this.roomId = formElement.dataset.roomId || '';
            this.checkinInput = formElement.querySelector('.mlb-checkin');
            this.checkoutInput = formElement.querySelector('.mlb-checkout');
            this.submitBtn = formElement.querySelector('.mlb-submit-btn, .mlb-book-room-btn, [data-trigger-modal="true"]');
            this.calendarModal = null;
            
            this.init();
        }

        init() {
            if (!this.submitBtn) return;
            
            // Initialize calendar modal
            this.calendarModal = new CalendarModal(this.form, {
                formType: 'room'
            });
            
            // Open calendar on button click
            this.submitBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.calendarModal.open();
            });
            
            // Listen for modal submit event
            this.form.addEventListener('mlb-modal-submit', (e) => {
                this.handleSubmit(e.detail);
            });
        }

        handleSubmit(detail) {
            var arrivalISO = detail.arrivalISO || this.toISO(this.checkinInput ? this.checkinInput.value : '');
            var departureISO = detail.departureISO || this.toISO(this.checkoutInput ? this.checkoutInput.value : '');
            
            if (!this.hotelId) {
                alert('Hotel ID is missing');
                return;
            }
            
            if (!this.roomId) {
                alert('Room ID is missing');
                return;
            }
            
            if (!arrivalISO || !departureISO) {
                alert('Please select check-in and check-out dates');
                return;
            }
            
            var bookingEngineURL = getBookingEngineURL();
            var redirectURL = bookingEngineURL + encodeURIComponent(this.hotelId) + '/Rooms/Select?Arrival=' + 
                encodeURIComponent(arrivalISO) + '&Departure=' + encodeURIComponent(departureISO) + 
                '&Room=' + encodeURIComponent(this.roomId);
            
            showSpinner();
            window.location.href = redirectURL;
        }

        toISO(dmy) {
            if (!dmy) return '';
            var parts = dmy.split('-');
            if (parts.length !== 3) return dmy;
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }
    }

    // Special Form Handler
    class SpecialForm {
        constructor(formElement) {
            this.form = formElement;
            this.hotelId = formElement.dataset.hotelId || '';
            this.rateId = formElement.dataset.rateId || formElement.dataset.specialId || '';
            this.submitBtn = formElement.querySelector('.mlb-submit-btn, .mlb-book-special-btn');
            
            this.init();
        }

        init() {
            if (!this.submitBtn) return;
            
            // Direct redirect on button click
            this.submitBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }

        handleSubmit() {
            if (!this.hotelId) {
                alert('Hotel ID is missing');
                return;
            }
            
            if (!this.rateId) {
                alert('Rate ID is missing');
                return;
            }
            
            var bookingEngineURL = getBookingEngineURL();
            var redirectURL = bookingEngineURL + encodeURIComponent(this.hotelId) + '/Rooms/GeneralAvailability?Rate=' + 
                encodeURIComponent(this.rateId);
            
            showSpinner();
            window.location.href = redirectURL;
        }
    }

    // Initialize all forms on page load
    function initializeForms() {
        var forms = document.querySelectorAll('.mlb-form, .mlb-booking-form form, form.mlb-booking-form');
        
        forms.forEach(function(form) {
            var formType = form.dataset.formType || form.getAttribute('data-form-type');
            
            // Skip if already initialized
            if (form._mlbInitialized) return;
            form._mlbInitialized = true;
            
            // Initialize based on form type
            if (formType === 'hotel') {
                new HotelForm(form);
            } else if (formType === 'room') {
                new RoomForm(form);
            } else if (formType === 'special') {
                new SpecialForm(form);
            } else {
                // Fallback: try to detect form type
                if (form.dataset.roomId || form.querySelector('[data-room-id]')) {
                    new RoomForm(form);
                } else if (form.dataset.rateId || form.dataset.specialId || form.querySelector('[data-rate-id], [data-special-id]')) {
                    new SpecialForm(form);
                } else {
                    new HotelForm(form);
                }
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeForms);
    } else {
        initializeForms();
    }

    // Re-initialize on Elementor preview changes
    if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
        elementorFrontend.hooks.addAction('frontend/element_ready/widget', function() {
            setTimeout(initializeForms, 100);
        });
    }

})();
