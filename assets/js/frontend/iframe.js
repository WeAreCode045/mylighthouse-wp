/**
 * Frontend Iframe Handler
 * Handles the booking engine iframe loading and spinner management
 */

document.addEventListener('DOMContentLoaded', function() {
    const iframeContainer = document.getElementById('mlb-iframe-container');
    const iframe = document.getElementById('mylighthouse-booking-iframe');
    const loadingMessage = document.getElementById('mlb-iframe-loading');

    function getText(str) {
        try {
            if (typeof mlbGettext === 'function') {
                return mlbGettext(str);
            }
        } catch (e) {}
        try {
            if (typeof wp !== 'undefined' && wp.i18n && typeof wp.i18n.__ === 'function') {
                return wp.i18n.__(str, 'mylighthouse-booker');
            }
        } catch (err) {}
        return str;
    }

    if (!iframe || !iframeContainer) {
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const hashParams = new URLSearchParams(window.location.hash.replace(/^#/, ''));
    let arrival = params.get('Arrival');
    let departure = params.get('Departure');
    let hotelId = params.get('hotel_id');
    let roomId = params.get('room');
    let rateCode = params.get('Rate') || params.get('rate') || params.get('special_id') || hashParams.get('Rate') || hashParams.get('rate') || hashParams.get('special_id');

    // Fallback: read from container data attributes (server-rendered)
    if (iframeContainer && iframeContainer.dataset) {
        if (!hotelId && iframeContainer.dataset.hotelId) hotelId = iframeContainer.dataset.hotelId;
        if (!arrival && iframeContainer.dataset.arrival) arrival = iframeContainer.dataset.arrival;
        if (!departure && iframeContainer.dataset.departure) departure = iframeContainer.dataset.departure;
        if (!rateCode && iframeContainer.dataset.rateId) rateCode = iframeContainer.dataset.rateId;
    }

    // Fallback: pull from storage only if this was a special form redirect (no dates + missing rate)
    // To prevent hotel/room bookings from picking up old rate values, only restore for special forms
    try {
        if (!rateCode && !arrival && !departure) {
            // Only for special forms that lack dates and Rate: restore from storage
            rateCode = window.sessionStorage.getItem('mlb_special_rate') || window.localStorage.getItem('mlb_special_rate') || rateCode;
        }
        if (!hotelId && !arrival && !departure) {
            hotelId = window.sessionStorage.getItem('mlb_special_hotel') || window.localStorage.getItem('mlb_special_hotel') || hotelId;
        }
    } catch (e) { /* ignore */ }

    // Clear special-rate storage when leaving the booking page (prevents contamination)
    // This fires when user navigates away or closes the tab
    try {
        window.addEventListener('beforeunload', function() {
            window.sessionStorage.removeItem('mlb_special_rate');
            window.sessionStorage.removeItem('mlb_special_hotel');
            window.localStorage.removeItem('mlb_special_rate');
            window.localStorage.removeItem('mlb_special_hotel');
        });
    } catch (e) { /* ignore */ }

    // If we recovered rate/hotel from storage/hash, normalize URL to keep only Rate (no reload)
    try {
        const url = new URL(window.location.href);
        let mutated = false;
        if (rateCode) {
            if (url.searchParams.get('Rate') !== rateCode) { url.searchParams.set('Rate', rateCode); mutated = true; }
            if (url.searchParams.has('rate')) { url.searchParams.delete('rate'); mutated = true; }
            if (url.searchParams.has('special_id')) { url.searchParams.delete('special_id'); mutated = true; }
        }
        if (hotelId && !url.searchParams.get('hotel_id')) { url.searchParams.set('hotel_id', hotelId); mutated = true; }
        // Clean empty Arrival/Departure
        if (url.searchParams.get('Arrival') === '') { url.searchParams.delete('Arrival'); mutated = true; }
        if (url.searchParams.get('Departure') === '') { url.searchParams.delete('Departure'); mutated = true; }
        if (mutated && window.history && window.history.replaceState) {
            window.history.replaceState({}, '', url.toString());
        }
    } catch (e) { /* ignore */ }
    let keepSpinnerDebug = params.get('mlb_debug_spinner') === '1';

    if (keepSpinnerDebug) {
        try {
            document.body.classList.add('mlb-debug-spinner');
            if (loadingMessage) {
                loadingMessage.classList.add('mlb-iframe-loading--debug');
            }
            console.info('MLB iframe spinner debug mode enabled. Call window.mlbReleaseIframeSpinner() when ready.');
        } catch (e) {
            /* ignore */
        }
        window.mlbReleaseIframeSpinner = function() {
            keepSpinnerDebug = false;
            hideLoader(true);
        };
    }

    function hideLoader(force) {
        if (keepSpinnerDebug && !force) {
            if (loadingMessage) {
                loadingMessage.classList.remove('mlb-iframe-loading--hidden');
            }
            iframeContainer.classList.remove('mlb-iframe-ready');
            return;
        }
        if (iframeContainer.classList.contains('mlb-iframe-ready')) {
            return;
        }
        if (loadingMessage) {
            loadingMessage.classList.add('mlb-iframe-loading--hidden');
        }
        iframeContainer.classList.add('mlb-iframe-ready');
    }

    function showErrorMessage(message) {
        if (!loadingMessage) return;
        const textNode = loadingMessage.querySelector('p') || loadingMessage;
        textNode.textContent = message;
        const spinnerBox = loadingMessage.querySelector('.mlb-spinner-box');
        if (spinnerBox) {
            spinnerBox.style.display = 'none';
        }
        loadingMessage.classList.remove('mlb-iframe-loading--hidden');
    }

    const bookingEngineBaseUrl = window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/';

    let iframeSrc = '';
    if (hotelId && rateCode) {
        const qs = new URLSearchParams();
        qs.set('Rate', rateCode);
        if (arrival) qs.set('Arrival', arrival);
        if (departure) qs.set('Departure', departure);
        iframeSrc = `${bookingEngineBaseUrl}${encodeURIComponent(hotelId)}/Rooms/GeneralAvailability`;
        const query = qs.toString();
        if (query) {
            iframeSrc += `?${query}`;
        }
    } else if (hotelId && arrival && departure) {
        iframeSrc = `${bookingEngineBaseUrl}${encodeURIComponent(hotelId)}/Rooms/Select?Arrival=${encodeURIComponent(arrival)}&Departure=${encodeURIComponent(departure)}`;

        if (roomId) {
            iframeSrc += `&room=${encodeURIComponent(roomId)}`;
        }
    }

    if (iframeSrc) {
        iframeContainer.classList.remove('mlb-iframe-ready');
        iframe.src = iframeSrc;

        iframe.onload = function() {
            function checkIframeSpinner() {
                try {
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    const spinner = iframeDoc.getElementById('pnlAvailabilityLoader');

                    if (spinner) {
                        const spinnerStyle = window.getComputedStyle(spinner);
                        if (spinnerStyle.display === 'none' || spinnerStyle.visibility === 'hidden') {
                            hideLoader();
                        } else {
                            setTimeout(checkIframeSpinner, 120);
                        }
                    } else {
                        hideLoader();
                    }
                } catch (e) {
                    setTimeout(function() {
                        hideLoader();
                    }, 1000);
                }
            }

            checkIframeSpinner();
        };

        iframe.addEventListener('error', function() {
            showErrorMessage(getText('We could not load the booking engine. Please refresh and try again.'));
        }, { once: true });
    } else {
        showErrorMessage(getText('Booking information is missing. Please start your search again from the homepage.'));
    }
});
