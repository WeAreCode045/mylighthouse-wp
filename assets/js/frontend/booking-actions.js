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
     * @param {string} arrival    Arrival date (YYYY-MM-DD or dd-mm-yyyy)
     * @param {string} departure  Departure date (YYYY-MM-DD or dd-mm-yyyy)
     * @param {string} identifier Optional identifier (room/rate)
     * @param {string} paramName  Optional parameter name
     * @return {string} Booking URL
     */
    function buildBookingUrl(hotelId, arrival, departure, identifier, paramName) {
        const baseUrl = (window.MLBBookingEngineBase && window.MLBBookingEngineBase.url) || 'https://bookingengine.mylighthouse.com/';
        
        // Convert dates to YYYY-MM-DD format
        const arrivalYMD = convertToYMD(arrival);
        const departureYMD = convertToYMD(departure);
        
        // Special handling for rate bookings
        if (paramName === 'rate' && identifier) {
            const params = new URLSearchParams({
                Rate: identifier
            });
            // Only add dates if they are provided
            if (arrivalYMD && departureYMD) {
                params.set('Arrival', arrivalYMD);
                params.set('Departure', departureYMD);
            }
            return `${baseUrl}${hotelId}/Rooms/GeneralAvailability?${params.toString()}`;
        }
        
        // Standard room selection URL
        let url = `${baseUrl}${hotelId}/Rooms/Select?Arrival=${arrivalYMD}&Departure=${departureYMD}`;
        
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
     * @param {string} arrival    Arrival date (YYYY-MM-DD or dd-mm-yyyy)
     * @param {string} departure  Departure date (YYYY-MM-DD or dd-mm-yyyy)
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
            // Convert dates to YYYY-MM-DD format if needed
            params.set('Arrival', convertToYMD(arrival));
            params.set('Departure', convertToYMD(departure));
        }
        
        if (identifier && paramName) {
            params.set(paramName, identifier);
        }
        
        return `${baseUrl}?${params.toString()}`;
    }

    /**
     * Convert date string to YYYY-MM-DD format
     *
     * @param {string} dateStr Date string in YYYY-MM-DD or dd-mm-yyyy format
     * @return {string} Date in YYYY-MM-DD format
     */
    function convertToYMD(dateStr) {
        if (!dateStr) return '';
        
        // Check if already in YYYY-MM-DD format
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
            return dateStr;
        }
        
        // Convert from dd-mm-yyyy to YYYY-MM-DD
        if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) {
            const [day, month, year] = dateStr.split('-');
            return `${year}-${month}-${day}`;
        }
        
        return dateStr;
    }

    // Public API
    return {
        bookRoom: bookRoom,
        bookSpecial: bookSpecial,
        checkHotelAvailability: checkHotelAvailability,
        getDisplayMode: getDisplayMode
    };
})();
