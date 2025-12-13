/**
 * Calendar Modal Class
 * Handles date range picker modal with EasePick
 *
 * @package Mylighthouse_Booker
 */

(function() {
    'use strict';

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
            
            // Show/hide booking details panel based on form type
            var rightColumn = this.overlay.querySelector('.mlb-modal-right-column');
            if (rightColumn) {
                if (this.formType === 'room') {
                    rightColumn.style.display = 'block';
                    
                    // Populate hotel and room names
                    var hotelNameEl = this.overlay.querySelector('.mlb-hotel-name');
                    var roomNameEl = this.overlay.querySelector('.mlb-room-name');
                    if (hotelNameEl) hotelNameEl.textContent = this.hotelName || 'Hotel';
                    if (roomNameEl) roomNameEl.textContent = this.roomName || 'Room';
                } else {
                    rightColumn.style.display = 'none';
                }
            }
            
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

                // Get calendar colors from CSS variables
                var formWrapper = self.form.closest('.mlb-booking-form') || self.form;
                var computedStyle = getComputedStyle(formWrapper);
                var startendBg = computedStyle.getPropertyValue('--mlb-calendar-startend-bg') || '#dc143c';
                var startendColor = computedStyle.getPropertyValue('--mlb-calendar-startend-color') || '#ffffff';
                var inrangeBg = computedStyle.getPropertyValue('--mlb-calendar-inrange-bg') || 'rgba(220, 20, 60, 0.1)';

                var pickerConfig = {
                    element: calendarDiv,
                    inline: true,
                    css: [
                        'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css'
                    ],
                    plugins: [RangePlugin, LockPlugin],
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

                // Inject custom CSS into Shadow DOM
                setTimeout(function() {
                    var shadowHost = calendarDiv.querySelector('.container');
                    if (shadowHost && shadowHost.shadowRoot) {
                        var shadowRoot = shadowHost.shadowRoot;
                        var customStyle = document.createElement('style');
                        customStyle.textContent = '.container.range-plugin .calendar > .days-grid > .day.start, ' +
                            '.container.range-plugin .calendar > .days-grid > .day.end { ' +
                            'background-color: ' + startendBg + ' !important; ' +
                            'color: ' + startendColor + ' !important; } ' +
                            '.container.range-plugin .calendar > .days-grid > .day.in-range { ' +
                            'background-color: ' + inrangeBg + ' !important; } ' +
                            '.container.range-plugin .calendar > .days-grid > .day.start::after, ' +
                            '.container.range-plugin .calendar > .days-grid > .day.end::after { ' +
                            'background-color: ' + startendBg + ' !important; }';
                        shadowRoot.appendChild(customStyle);
                    }
                }, 100);
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

            // For room forms, show booking details
            if (this.formType === 'room') {
                var arrivalSpan = this.overlay.querySelector('.mlb-arrival-date');
                var departureSpan = this.overlay.querySelector('.mlb-departure-date');
                var datesRows = this.overlay.querySelectorAll('.mlb-dates-row');
                var submitBtn = this.overlay.querySelector('.mlb-modal-submit-btn');
                var rightColumn = this.overlay.querySelector('.mlb-modal-right-column');

                if (arrivalSpan) arrivalSpan.textContent = arrivalStr;
                if (departureSpan) departureSpan.textContent = departureStr;
                datesRows.forEach(function(row) { row.style.display = 'flex'; });
                if (submitBtn) submitBtn.disabled = false;
                if (rightColumn) {
                    setTimeout(function() { rightColumn.classList.add('mlb-expanded'); }, 50);
                }
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

            // Submit button for room forms
            if (this.formType === 'room') {
                var submitBtn = this.overlay.querySelector('.mlb-modal-submit-btn');
                if (submitBtn) {
                    submitBtn.addEventListener('click', function() {
                        self.close();
                        // Trigger form submission
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
            }
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

    // Export to global scope
    window.MLB_CalendarModal = CalendarModal;

})();
