/**
 * Hotel Form Widget
 * Handles hotel availability form submissions
 *
 * @package Mylighthouse_Booker
 */

(function() {
    'use strict';

    /**
     * Initialize hotel form widgets
     */
    function init() {
        const forms = document.querySelectorAll('[data-mlb-hotel-form]');

        forms.forEach(function(form) {
            // Prevent multiple bindings
            if (form.dataset.mlbBound) return;
            form.dataset.mlbBound = 'true';

            const dateInput = form.querySelector('[data-mlb-date-input]');
            const submitBtn = form.querySelector('[data-mlb-submit]');
            const hotelSelect = form.querySelector('[data-mlb-hotel-select]');
            
            let selectedDates = null;
            
            /**
             * Get current hotel ID from select or form data attribute
             */
            function getCurrentHotelId() {
                if (hotelSelect) {
                    return hotelSelect.value;
                }
                return form.dataset.hotelId || '';
            }

            // Open date picker on input click
            if (dateInput) {
                dateInput.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (typeof window.MLB_DatePicker === 'undefined') {
                        console.error('MLB: Date picker not loaded');
                        return;
                    }
                    
                    window.MLB_DatePicker.open(function(dates) {
                        selectedDates = dates;
                        dateInput.value = formatDateRange(dates.arrival, dates.departure);
                        
                        // Update hidden input fields for form submission
                        const checkinInput = form.querySelector('.mlb-checkin');
                        const checkoutInput = form.querySelector('.mlb-checkout');
                        if (checkinInput) checkinInput.value = dates.arrival;
                        if (checkoutInput) checkoutInput.value = dates.departure;
                        
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('mlb-button-disabled');
                        }
                    });
                });

                // Make readonly to prevent manual input
                dateInput.readOnly = true;
            }

            // Handle form submission validation
            if (submitBtn) {
                // Disable initially
                submitBtn.disabled = true;
                submitBtn.classList.add('mlb-button-disabled');
            }

            // Validate form before submission
            form.addEventListener('submit', function(e) {
                if (!selectedDates) {
                    e.preventDefault();
                    alert('Please select your dates');
                    return false;
                }
                
                const hotelId = getCurrentHotelId();
                if (!hotelId) {
                    e.preventDefault();
                    alert('Please select a hotel');
                    return false;
                }
                
                // Form will submit via POST to booking page
                console.log('MLB: Form submitting via POST - arrival:', selectedDates.arrival, 'departure:', selectedDates.departure);
                return true;
            });
        });
    }

    /**
     * Format date range for display
     *
     * @param {string} arrival   Arrival date (YYYY-MM-DD format)
     * @param {string} departure Departure date (YYYY-MM-DD format)
     * @return {string} Formatted date range
     */
    function formatDateRange(arrival, departure) {
        try {
            // Parse YYYY-MM-DD format dates (from date-picker.js)
            const [aYear, aMonth, aDay] = arrival.split('-');
            const [dYear, dMonth, dDay] = departure.split('-');
            
            const arrivalDate = new Date(aYear, aMonth - 1, aDay);
            const departureDate = new Date(dYear, dMonth - 1, dDay);
            
            const arrivalStr = arrivalDate.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric'
            });
            
            const departureStr = departureDate.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric',
                year: 'numeric'
            });
            
            return `${arrivalStr} → ${departureStr}`;
        } catch (error) {
            console.error('MLB: Error formatting date range:', error);
            return `${arrival} - ${departure}`;
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-initialize on dynamic content (Elementor preview, AJAX)
    if (window.elementorFrontend) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/global', init);
    }

})();
