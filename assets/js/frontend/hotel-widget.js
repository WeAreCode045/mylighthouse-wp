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
            const hotelId = form.dataset.hotelId;

            if (!hotelId) {
                console.error('MLB: Missing hotel ID in form');
                return;
            }

            let selectedDates = null;

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
                        
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('mlb-button-disabled');
                        }
                    });
                });

                // Make readonly to prevent manual input
                dateInput.readOnly = true;
            }

            // Handle form submission
            if (submitBtn) {
                // Disable initially
                submitBtn.disabled = true;
                submitBtn.classList.add('mlb-button-disabled');

                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (!selectedDates) {
                        alert('Please select your dates');
                        return;
                    }

                    if (typeof window.MLB_BookingActions === 'undefined') {
                        console.error('MLB: Booking actions not loaded');
                        return;
                    }

                    window.MLB_BookingActions.checkHotelAvailability(
                        hotelId,
                        selectedDates.arrival,
                        selectedDates.departure
                    );
                });
            }

            // Handle direct form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (submitBtn) {
                    submitBtn.click();
                }
            });
        });
    }

    /**
     * Format date range for display
     *
     * @param {string} arrival   Arrival date
     * @param {string} departure Departure date
     * @return {string} Formatted date range
     */
    function formatDateRange(arrival, departure) {
        try {
            const arrivalDate = new Date(arrival + 'T00:00:00');
            const departureDate = new Date(departure + 'T00:00:00');
            
            const arrivalStr = arrivalDate.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric'
            });
            
            const departureStr = departureDate.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric',
                year: 'numeric'
            });
            
            return `${arrivalStr} - ${departureStr}`;
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
