/**
 * Booking Details Controller
 * Controls the booking details component rendered by PHP
 *
 * @package Mylighthouse_Booker
 */

window.MLB_BookingDetails = (function() {
    'use strict';

    let container = null;
    let currentData = null;
    let initialized = false;

    /**
     * Initialize booking details component
     */
    function init() {
        if (initialized) return;

        container = document.getElementById('mlb-booking-details');
        if (!container) {
            console.error('MLB: Booking details container not found');
            return;
        }

        wireControls();
        initialized = true;
    }

    /**
     * Wire up controls
     */
    function wireControls() {
        const changeDatesBtn = container.querySelector('.mlb-change-dates');
        const checkBtn = container.querySelector('.mlb-check-availability');

        // Change dates button
        if (changeDatesBtn) {
            changeDatesBtn.addEventListener('click', function() {
                if (currentData && typeof currentData.onChangeDates === 'function') {
                    currentData.onChangeDates();
                }
            });
        }

        // Check availability button
        if (checkBtn) {
            checkBtn.addEventListener('click', function() {
                if (currentData && typeof currentData.onCheckAvailability === 'function') {
                    currentData.onCheckAvailability(currentData);
                }
            });
        }
    }

    /**
     * Show booking details
     *
     * @param {Object} data Booking data with arrival, departure, callbacks
     */
    function show(data) {
        if (!initialized) {
            init();
        }

        if (!container) {
            console.error('MLB: Booking details container not available');
            return;
        }

        currentData = data;
        
        const arrivalEl = container.querySelector('.mlb-arrival-date');
        const departureEl = container.querySelector('.mlb-departure-date');
        const nightsEl = container.querySelector('[data-nights]');
        
        // Update arrival date
        if (arrivalEl && data.arrival) {
            arrivalEl.textContent = formatDate(data.arrival);
            arrivalEl.dataset.date = data.arrival;
        }
        
        // Update departure date
        if (departureEl && data.departure) {
            departureEl.textContent = formatDate(data.departure);
            departureEl.dataset.date = data.departure;
        }
        
        // Update nights count
        if (nightsEl && data.arrival && data.departure) {
            const nights = calculateNights(data.arrival, data.departure);
            nightsEl.textContent = nights;
            nightsEl.dataset.nights = nights;
        }
        
        // Show with animation
        container.style.display = 'block';
        requestAnimationFrame(() => {
            container.classList.add('mlb-booking-details-show');
        });
    }

    /**
     * Hide booking details
     */
    function hide() {
        if (!container) return;
        
        container.classList.remove('mlb-booking-details-show');
        setTimeout(() => {
            container.style.display = 'none';
            currentData = null;
        }, 300);
    }

    /**
     * Format date for display
     *
     * @param {string} dateStr Date string in YYYY-MM-DD format
     * @return {string} Formatted date
     */
    function formatDate(dateStr) {
        try {
            const date = new Date(dateStr + 'T00:00:00');
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
        } catch (error) {
            console.error('MLB: Error formatting date:', error);
            return dateStr;
        }
    }

    /**
     * Calculate number of nights between dates
     *
     * @param {string} arrival   Arrival date in YYYY-MM-DD format
     * @param {string} departure Departure date in YYYY-MM-DD format
     * @return {number} Number of nights
     */
    function calculateNights(arrival, departure) {
        try {
            const start = new Date(arrival + 'T00:00:00');
            const end = new Date(departure + 'T00:00:00');
            const diff = Math.abs(end - start);
            return Math.ceil(diff / (1000 * 60 * 60 * 24));
        } catch (error) {
            console.error('MLB: Error calculating nights:', error);
            return 0;
        }
    }

    // Public API
    return {
        show: show,
        hide: hide
    };
})();

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // Initialization will happen on first show()
    });
}
