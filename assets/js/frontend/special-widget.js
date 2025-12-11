/**
 * Special/Rate Booking Widget
 * Handles special rate booking button clicks (direct booking)
 *
 * @package Mylighthouse_Booker
 */

(function() {
    'use strict';

    /**
     * Initialize special booking widgets
     */
    function init() {
        const bookButtons = document.querySelectorAll('[data-mlb-book-special]');

        bookButtons.forEach(function(btn) {
            // Prevent multiple bindings
            if (btn.dataset.mlbBound) return;
            btn.dataset.mlbBound = 'true';

            btn.addEventListener('click', function(e) {
                e.preventDefault();

                const hotelId = btn.dataset.hotelId;
                const rateId = btn.dataset.rateId;

                if (!hotelId || !rateId) {
                    console.error('MLB: Missing hotel ID or rate ID');
                    return;
                }

                if (typeof window.MLB_BookingActions === 'undefined') {
                    console.error('MLB: Booking actions not loaded');
                    return;
                }

                // Direct booking without dates (dates not required for special rates)
                window.MLB_BookingActions.bookSpecial(
                    hotelId,
                    rateId
                );
            });
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
