/**
 * Room Booking Widget
 * Handles room booking button clicks
 *
 * @package Mylighthouse_Booker
 */

(function() {
    'use strict';

    /**
     * Initialize room booking widgets
     */
    function init() {
        const bookButtons = document.querySelectorAll('[data-mlb-book-room]');

        bookButtons.forEach(function(btn) {
            // Prevent multiple bindings
            if (btn.dataset.mlbBound) return;
            btn.dataset.mlbBound = 'true';

            btn.addEventListener('click', function(e) {
                e.preventDefault();

                const hotelId = btn.dataset.hotelId;
                const roomId = btn.dataset.roomId;

                if (!hotelId || !roomId) {
                    console.error('MLB: Missing hotel ID or room ID');
                    return;
                }

                // Check if date picker is available
                if (typeof window.MLB_DatePicker === 'undefined') {
                    console.error('MLB: Date picker not loaded');
                    return;
                }

                // Open date picker
                window.MLB_DatePicker.open(function(dates) {
                    handleDateSelection(hotelId, roomId, dates);
                });
            });
        });
    }

    /**
     * Handle date selection
     *
     * @param {string} hotelId Hotel ID
     * @param {string} roomId  Room ID
     * @param {Object} dates   Selected dates object
     */
    function handleDateSelection(hotelId, roomId, dates) {
        // Show booking details
        if (typeof window.MLB_BookingDetails === 'undefined') {
            console.error('MLB: Booking details component not loaded');
            return;
        }

        window.MLB_BookingDetails.show({
            arrival: dates.arrival,
            departure: dates.departure,
            hotelId: hotelId,
            roomId: roomId,
            onChangeDates: function() {
                // Re-open date picker for changes
                window.MLB_DatePicker.open(function(newDates) {
                    handleDateSelection(hotelId, roomId, newDates);
                });
            },
            onCheckAvailability: function(data) {
                // Book the room
                if (typeof window.MLB_BookingActions === 'undefined') {
                    console.error('MLB: Booking actions not loaded');
                    return;
                }

                window.MLB_BookingActions.bookRoom(
                    data.hotelId,
                    data.roomId,
                    data.arrival,
                    data.departure
                );
            }
        });
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
