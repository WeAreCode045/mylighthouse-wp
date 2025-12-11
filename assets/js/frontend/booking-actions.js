/**
 * Booking Actions
 * Handles all booking-related requests and redirects
 *
 * @package Mylighthouse_Booker
 */

window.MLB_BookingActions = (function() {
    'use strict';

    /**
     * Get current display mode based on screen width
     *
     * @return {string} Display mode: 'modal', 'booking_page', or 'redirect_engine'
     */
    function getDisplayMode() {
        const width = window.innerWidth;
        const config = window.mlbConfig || {};
        
        if (width <= 767) {
            return config.displayModeMobile || 'modal';
        }
        if (width <= 1024) {
            return config.displayModeTablet || 'modal';
        }
        return config.displayModeDesktop || 'modal';
    }

    /**
     * Book a room
     *
     * @param {string} hotelId   Hotel ID
     * @param {string} roomId    Room ID
     * @param {string} arrival   Arrival date YYYY-MM-DD
     * @param {string} departure Departure date YYYY-MM-DD
     */
    function bookRoom(hotelId, roomId, arrival, departure) {
        const displayMode = getDisplayMode();
        const bookingUrl = buildBookingUrl(hotelId, arrival, departure, roomId, 'room');

        handleBooking(displayMode, bookingUrl, {
            hotelId: hotelId,
            arrival: arrival,
            departure: departure,
            identifier: roomId,
            paramName: 'room'
        });
    }

    /**
     * Book a special/rate
     *
     * @param {string} hotelId   Hotel ID
     * @param {string} rateId    Rate ID
     * @param {string} arrival   Optional arrival date YYYY-MM-DD
     * @param {string} departure Optional departure date YYYY-MM-DD
     */
    function bookSpecial(hotelId, rateId, arrival, departure) {
        const displayMode = getDisplayMode();
        const bookingUrl = buildBookingUrl(hotelId, arrival, departure, rateId, 'rate');

        handleBooking(displayMode, bookingUrl, {
            hotelId: hotelId,
            arrival: arrival || '',
            departure: departure || '',
            identifier: rateId,
            paramName: 'rate'
        });
    }

    /**
     * Check hotel availability
     *
     * @param {string} hotelId   Hotel ID
     * @param {string} arrival   Arrival date YYYY-MM-DD
     * @param {string} departure Departure date YYYY-MM-DD
     */
    function checkHotelAvailability(hotelId, arrival, departure) {
        const displayMode = getDisplayMode();
        const bookingUrl = buildBookingUrl(hotelId, arrival, departure);

        handleBooking(displayMode, bookingUrl, {
            hotelId: hotelId,
            arrival: arrival,
            departure: departure
        });
    }

    /**
     * Handle booking based on display mode
     *
     * @param {string} displayMode Display mode
     * @param {string} bookingUrl  Booking URL
     * @param {Object} params      Booking parameters
     */
    function handleBooking(displayMode, bookingUrl, params) {
        switch (displayMode) {
            case 'modal':
                if (window.MLB_Modal && typeof window.MLB_Modal.openBookingModal === 'function') {
                    window.MLB_Modal.openBookingModal(
                        bookingUrl,
                        params.hotelId,
                        params.arrival,
                        params.departure,
                        params.identifier,
                        params.paramName
                    );
                } else {
                    console.error('MLB: Modal function not available, falling back to redirect');
                    window.location.href = bookingUrl;
                }
                break;

            case 'booking_page':
                const bookingPageUrl = window.mlbConfig && window.mlbConfig.bookingPageUrl;
                if (bookingPageUrl) {
                    const pageUrl = buildBookingPageUrl(
                        bookingPageUrl,
                        params.hotelId,
                        params.arrival,
                        params.departure,
                        params.identifier,
                        params.paramName
                    );
                    window.location.href = pageUrl;
                } else {
                    console.warn('MLB: Booking page URL not configured, using direct redirect');
                    window.location.href = bookingUrl;
                }
                break;

            case 'redirect_engine':
            default:
                window.location.href = bookingUrl;
                break;
        }
    }

    /**
     * Build booking engine URL
     *
     * @param {string} hotelId    Hotel ID
     * @param {string} arrival    Arrival date
     * @param {string} departure  Departure date
     * @param {string} identifier Optional identifier (room/rate)
     * @param {string} paramName  Optional parameter name
     * @return {string} Booking URL
     */
    function buildBookingUrl(hotelId, arrival, departure, identifier, paramName) {
        const baseUrl = window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/';
        
        // Special handling for rate bookings
        if (paramName === 'rate' && identifier) {
            const params = new URLSearchParams({
                Rate: identifier
            });
            // Only add dates if they are provided
            if (arrival && departure) {
                params.set('Arrival', arrival);
                params.set('Departure', departure);
            }
            return `${baseUrl}${hotelId}/Rooms/GeneralAvailability?${params.toString()}`;
        }
        
        // Standard room selection URL
        let url = `${baseUrl}${hotelId}/Rooms/Select?Arrival=${arrival}&Departure=${departure}`;
        
        if (identifier && paramName) {
            url += `&${paramName}=${identifier}`;
        }
        
        return url;
    }

    /**
     * Build booking page URL (WordPress page)
     *
     * @param {string} baseUrl    Base booking page URL
     * @param {string} hotelId    Hotel ID
     * @param {string} arrival    Arrival date
     * @param {string} departure  Departure date
     * @param {string} identifier Optional identifier
     * @param {string} paramName  Optional parameter name
     * @return {string} Booking page URL
     */
    function buildBookingPageUrl(baseUrl, hotelId, arrival, departure, identifier, paramName) {
        const params = new URLSearchParams({
            hotel_id: hotelId
        });
        
        // Only add dates if they are provided
        if (arrival && departure) {
            params.set('arrival', arrival);
            params.set('departure', departure);
        }
        
        if (identifier && paramName) {
            params.set(paramName, identifier);
        }
        
        return `${baseUrl}?${params.toString()}`;
    }

    // Public API
    return {
        bookRoom: bookRoom,
        bookSpecial: bookSpecial,
        checkHotelAvailability: checkHotelAvailability,
        getDisplayMode: getDisplayMode
    };
})();
