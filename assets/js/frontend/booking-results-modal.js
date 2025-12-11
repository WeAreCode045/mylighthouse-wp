/**
 * Booking Results Modal Controller
 * Uses PHP-rendered template for booking iframe
 *
 * @package Mylighthouse_Booker
 */

window.MLB_Modal = window.MLB_Modal || {};

(function() {
    'use strict';

    const MIN_SPINNER_DURATION = 4000;
    const IFRAME_LOAD_TIMEOUT = 15000;

    /**
     * Open booking modal with iframe
     *
     * @param {string} bookingUrl Booking engine URL
     * @param {string} hotelId    Hotel ID
     * @param {string} arrival    Arrival date
     * @param {string} departure  Departure date
     * @param {string} identifier Optional identifier (room/rate)
     * @param {string} paramName  Optional parameter name
     */
    window.MLB_Modal.openBookingModal = function(bookingUrl, hotelId, arrival, departure, identifier, paramName) {
        const template = document.getElementById('mlb-booking-modal-template');
        if (!template) {
            console.error('MLB: Booking modal template not found');
            // Fallback to direct redirect
            window.location.href = bookingUrl;
            return;
        }

        // Clone template content
        const fragment = template.content.cloneNode(true);
        const overlay = fragment.querySelector('.mlb-modal-overlay');
        const modal = fragment.querySelector('.mlb-booking-modal');
        const iframe = fragment.querySelector('.mlb-booking-iframe');
        const loader = fragment.querySelector('.mlb-modal-loader');
        const closeBtn = fragment.querySelector('.mlb-modal-close');

        if (!overlay || !modal || !iframe || !loader || !closeBtn) {
            console.error('MLB: Modal template structure incomplete');
            window.location.href = bookingUrl;
            return;
        }

        // Append to body
        document.body.appendChild(fragment);

        // Get the overlay from DOM (now it's attached)
        const overlayInDom = document.body.lastElementChild;
        
        // Build complete iframe URL
        const iframeSrc = buildCompleteUrl(bookingUrl, hotelId, arrival, departure, identifier, paramName);
        
        let iframeLoaded = false;
        let minSpinnerElapsed = false;
        const startTime = Date.now();

        /**
         * Try to show iframe when conditions are met
         */
        function tryShowIframe() {
            if (minSpinnerElapsed && iframeLoaded) {
                loader.style.display = 'none';
                iframe.style.display = 'block';
                iframe.classList.add('mlb-iframe-loaded');
                
                // Set focus to close button for accessibility
                closeBtn.focus();
            }
        }

        // Iframe load event
        iframe.addEventListener('load', function() {
            iframeLoaded = true;
            tryShowIframe();
        });

        // Iframe error event
        iframe.addEventListener('error', function() {
            console.error('MLB: Error loading booking iframe');
            // Open in new tab as fallback
            window.open(iframeSrc, '_blank');
            closeModal();
        });

        // Minimum spinner duration
        setTimeout(() => {
            minSpinnerElapsed = true;
            tryShowIframe();
        }, MIN_SPINNER_DURATION);

        // Set iframe source to start loading
        iframe.src = iframeSrc;

        // Show overlay with animation
        requestAnimationFrame(() => {
            overlayInDom.classList.add('mlb-modal-show');
        });

        /**
         * Close modal
         */
        function closeModal() {
            overlayInDom.classList.remove('mlb-modal-show');
            setTimeout(() => {
                if (overlayInDom && overlayInDom.parentNode) {
                    overlayInDom.parentNode.removeChild(overlayInDom);
                }
            }, 300);
        }

        // Close button
        closeBtn.addEventListener('click', closeModal);

        // Click outside to close
        overlayInDom.addEventListener('click', (e) => {
            if (e.target === overlayInDom) {
                closeModal();
            }
        });
        
        // Escape key to close
        const escapeHandler = function(e) {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);

        // Timeout fallback - open in new tab if iframe doesn't load
        setTimeout(() => {
            if (!iframeLoaded) {
                console.warn('MLB: Iframe load timeout, opening in new tab');
                window.open(iframeSrc, '_blank');
                closeModal();
            }
        }, IFRAME_LOAD_TIMEOUT);
    };

    /**
     * Build complete booking URL
     *
     * @param {string} baseUrl    Base URL
     * @param {string} hotelId    Hotel ID
     * @param {string} arrival    Arrival date
     * @param {string} departure  Departure date
     * @param {string} identifier Optional identifier
     * @param {string} paramName  Optional parameter name
     * @return {string} Complete URL
     */
    function buildCompleteUrl(baseUrl, hotelId, arrival, departure, identifier, paramName) {
        // If baseUrl already contains query params, use it as-is
        if (baseUrl.includes('?')) {
            return baseUrl;
        }

        // Otherwise build from components
        const bookingEngineBase = window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/';
        
        // Special handling for rate bookings
        if (paramName === 'rate' && identifier) {
            const params = new URLSearchParams({
                Rate: identifier,
                Arrival: arrival,
                Departure: departure
            });
            return `${bookingEngineBase}${hotelId}/Rooms/GeneralAvailability?${params.toString()}`;
        }
        
        // Standard room selection URL
        let url = `${bookingEngineBase}${hotelId}/Rooms/Select?Arrival=${arrival}&Departure=${departure}`;
        
        if (identifier && paramName) {
            url += `&${paramName}=${identifier}`;
        }
        
        return url;
    }

})();
