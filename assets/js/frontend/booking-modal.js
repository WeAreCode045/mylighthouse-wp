/**
 * Frontend Booking Modal JavaScript
 * Handles booking results modal with iframe and spinner
 */

// Create global namespace for modal functions
window.MLB_Modal = window.MLB_Modal || {};

(function() {
    'use strict';

    // JS gettext helper: prefer wp.i18n.__() when available
    function mlbGettext(str) {
        try {
            if (typeof wp !== 'undefined' && wp.i18n && typeof wp.i18n.__ === 'function') {
                return wp.i18n.__(str, 'mylighthouse-booker');
            }
        } catch (e) {}
        return str;
    }

    // ========================================================================
    // CONFIGURATION
    // ========================================================================

    const MIN_SPINNER_DURATION = 4000;
    const IFRAME_LOAD_TIMEOUT = 15000;

    // ========================================================================
    // BOOKING RESULTS MODAL
    // ========================================================================

    function applySpinnerImage(loaderEl, spinnerUrl) {
        if (!loaderEl) return;
        var box = loaderEl.querySelector ? loaderEl.querySelector('.mlb-spinner-box') : null;
        if (!box) return;

        var fallbackSpinner = box.querySelector('.mlb-spinner');
        var imageEl = box.querySelector('.mlb-spinner-image');

        if (!spinnerUrl) {
            if (imageEl) imageEl.style.display = 'none';
            if (fallbackSpinner) fallbackSpinner.style.display = '';
            return;
        }

        if (!imageEl) {
            imageEl = document.createElement('div');
            imageEl.className = 'mlb-spinner-image';
            imageEl.setAttribute('aria-hidden', 'true');
            box.insertBefore(imageEl, box.firstChild || null);
        }

        imageEl.style.display = 'block';
        imageEl.style.backgroundImage = 'url("' + spinnerUrl + '")';
        if (fallbackSpinner) fallbackSpinner.style.display = 'none';
    }

    function insertIframeStickySpacer(iframeEl) {
        if (!iframeEl) return;
        try {
            var doc = iframeEl.contentDocument || (iframeEl.contentWindow && iframeEl.contentWindow.document);
            if (!doc) return;

            var candidates = doc.querySelectorAll('header, .site-header, .sticky-header, [data-sticky], [class*="sticky"], [data-sticky-header]');
            if (!candidates || !candidates.length) return;

            var sticky = null;
            for (var i = 0; i < candidates.length; i++) {
                var el = candidates[i];
                var style = doc.defaultView ? doc.defaultView.getComputedStyle(el) : null;
                if (!style) continue;
                var pos = style.position;
                if ((pos === 'sticky' || pos === 'fixed') && parseInt(style.top || '0', 10) <= 10) {
                    sticky = el;
                    break;
                }
            }

            if (!sticky) return;
            var rect = sticky.getBoundingClientRect ? sticky.getBoundingClientRect() : null;
            var headerHeight = rect && rect.height ? rect.height : (sticky.offsetHeight || 0);
            if (!headerHeight) return;

            var spacerId = 'mlb-iframe-sticky-spacer';
            var spacer = doc.getElementById(spacerId);
            if (!spacer) {
                spacer = doc.createElement('div');
                spacer.id = spacerId;
                spacer.style.width = '100%';
                spacer.style.pointerEvents = 'none';
                sticky.parentNode.insertBefore(spacer, sticky.nextSibling);
            }
            spacer.style.height = headerHeight + 'px';
        } catch (err) {
            try { console.debug('MLB iframe sticky spacer skipped', err); } catch (e) { /* ignore */ }
        }
    }

    /**
     * Open booking results in modal overlay
     * Exposed globally for use by form.js
     */
    window.MLB_Modal.openBookingModal = function(bookingPageUrl, hotelId, arrivalISO, departureISO, identifier, paramName) {
        const activeEl = document.activeElement;

        // Normalize bookingPageUrl: accept either a URL object or a string.
        var bookingPageUrlObj = null;
        try {
            if (bookingPageUrl && typeof bookingPageUrl === 'object' && bookingPageUrl.href) {
                bookingPageUrlObj = bookingPageUrl;
            } else if (bookingPageUrl && typeof bookingPageUrl === 'string') {
                try { bookingPageUrlObj = new URL(bookingPageUrl); } catch (e) { bookingPageUrlObj = null; }
            }
        } catch (e) { bookingPageUrlObj = null; }

        // Create modal elements
        const overlay = document.createElement('div');
        overlay.className = 'mlb-modal-overlay';

        const modal = document.createElement('div');
        modal.className = 'mlb-modal';
    // Start hidden; will be revealed after the spinner minimum duration
    modal.classList.add('mlb-modal-hidden');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');

        // Try to clone a server-rendered close button template to keep markup in PHP
        var closeBtn = null;
        try {
            var closeTpl = document.getElementById('mlb-modal-close-button');
            if (closeTpl && closeTpl.content && closeTpl.content.firstElementChild) {
                closeBtn = closeTpl.content.firstElementChild.cloneNode(true);
            }
        } catch (e) { /* ignore */ }
        if (!closeBtn) {
            closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'mlb-modal-close';
            closeBtn.setAttribute('aria-label', mlbGettext('Close booking results'));
            closeBtn.textContent = '\u00d7';
        }

    const iframe = document.createElement('iframe');
    iframe.id = 'mylighthouse-booking-iframe';
    iframe.className = 'mlb-modal-iframe';
    iframe.setAttribute('title', mlbGettext('Booking results'));
    // Allow scripts, forms, popups, and same-origin access for booking engine functionality
    iframe.setAttribute('allow', 'payment; geolocation');
    iframe.setAttribute('loading', 'eager');

        let loadTimeout = null;

        // Resolve custom spinner image once so both template/fallback paths can use it
        var spinnerImageUrl = '';
        try {
            if (typeof cqb_params !== 'undefined' && cqb_params.spinner_image_url) {
                spinnerImageUrl = cqb_params.spinner_image_url;
            }
        } catch (e) { spinnerImageUrl = ''; }

        // Create spinner loader: prefer server-rendered template, fall back to built string
        var loader = null;
        try {
            var spinnerTpl = document.getElementById('mlb-modal-spinner-box');
            if (spinnerTpl && spinnerTpl.content && spinnerTpl.content.firstElementChild) {
                loader = spinnerTpl.content.firstElementChild.cloneNode(true);
                var outer = document.createElement('div');
                outer.className = 'mlb-modal-loader';
                outer.appendChild(loader);
                loader = outer;
            }
        } catch (e) { /* ignore */ }

        if (!loader) {
            loader = document.createElement('div');
            loader.className = 'mlb-modal-loader';
            var spinnerBox = document.createElement('div');
            spinnerBox.className = 'mlb-spinner-box';

            var imgDiv = document.createElement('div');
            imgDiv.className = 'mlb-spinner-image';
            imgDiv.setAttribute('aria-hidden', 'true');
            spinnerBox.appendChild(imgDiv);

            var spinnerDiv = document.createElement('div');
            spinnerDiv.className = 'mlb-spinner';
            spinnerDiv.setAttribute('aria-hidden', 'true');
            spinnerBox.appendChild(spinnerDiv);

            loader.appendChild(spinnerBox);
        }

        try {
            applySpinnerImage(loader, spinnerImageUrl);
        } catch (e) {
            console.warn('Failed to apply spinner image', e);
        }

        loader.style.display = 'flex';
        loader.style.opacity = '1';
        loader.style.pointerEvents = 'auto';
        overlay.appendChild(loader);

        // Build iframe URL (needed before wiring up "open in new tab" fallback)
        const bookingEngineBaseUrl = window.MLBBookingEngineBase || 'https://bookingengine.mylighthouse.com/';
        const pName = paramName || 'room';

        let iframeSrc = '';
        if (pName === 'rate') {
            const qs = new URLSearchParams();
            if (identifier) qs.set('Rate', identifier);
            if (arrivalISO) qs.set('Arrival', arrivalISO);
            if (departureISO) qs.set('Departure', departureISO);

            const hotelPath = hotelId ? encodeURIComponent(hotelId) + '/Rooms/GeneralAvailability' : 'Rooms/GeneralAvailability';
            iframeSrc = bookingEngineBaseUrl + hotelPath;
            const query = qs.toString();
            if (query) {
                iframeSrc += '?' + query;
            }
        } else {
            iframeSrc = bookingEngineBaseUrl + encodeURIComponent(hotelId) + '/Rooms/Select?Arrival=' + encodeURIComponent(arrivalISO) + '&Departure=' + encodeURIComponent(departureISO);

            if (identifier) {
                iframeSrc += '&' + encodeURIComponent(pName) + '=' + encodeURIComponent(identifier);
            }
        }

        // Debugging: report spinner URL and iframe src
        try { console.debug('MLB_Modal.openBookingModal', { spinnerImageUrl: spinnerImageUrl || null, iframeSrc: iframeSrc }); } catch (e) { /* ignore */ }

        // Track iframe load and coordinate showing the iframe with the spinner.
        let iframeLoaded = false;
        let minSpinnerElapsed = false;
        const startTime = Date.now();

            function tryShowIframe() {
                // Reveal the modal as soon as the minimum spinner time has elapsed.
                // The iframe will continue to load in the background and be shown when ready.
                if (minSpinnerElapsed) {
                    if (modal.classList.contains('mlb-modal-hidden')) {
                        modal.classList.remove('mlb-modal-hidden');
                    }
                    modal.classList.add('mlb-modal-expanded');
                    // Hide the loader if not already hidden
                    if (loader && loader.parentNode) {
                        loader.classList.add('mlb-modal-loader--hide');
                        // Also set inline styles so the loader is definitely hidden (inline styles may override CSS in some contexts)
                        try {
                            loader.style.opacity = '0';
                            loader.style.pointerEvents = 'none';
                            // Remove the loader from the DOM after the fade-out transition completes
                            setTimeout(function() {
                                if (loader && loader.parentNode) loader.parentNode.removeChild(loader);
                            }, 350);
                        } catch (e) {
                            // ignore
                        }
                    }
                    // If the iframe has already loaded, show it immediately
                    if (iframeLoaded) {
                        iframe.style.display = '';
                        iframe.classList.add('mlb-iframe-show');
                        closeBtn.focus();
                    }
                }
            }

        iframe.addEventListener('load', function() {
            iframe._mlb_loaded = true;
            iframeLoaded = true;
            if (loadTimeout) clearTimeout(loadTimeout);
            insertIframeStickySpacer(iframe);
            tryShowIframe();
        });

        // Start loading iframe immediately
        iframe.src = iframeSrc;

        // Ensure the spinner shows for at least MIN_SPINNER_DURATION
        setTimeout(function() {
            minSpinnerElapsed = true;
            // Hide the loader after the minimum spinner duration even if iframe hasn't loaded yet
            try {
                if (loader && loader.parentNode) {
                    loader.classList.add('mlb-modal-loader--hide');
                    loader.style.opacity = '0';
                    loader.style.pointerEvents = 'none';
                    setTimeout(function() {
                        if (loader && loader.parentNode) loader.parentNode.removeChild(loader);
                    }, 350);
                }
            } catch (e) {
                // ignore
            }
            tryShowIframe();
        }, MIN_SPINNER_DURATION);

    // Append elements and hide iframe until ready
    modal.appendChild(closeBtn);
    iframe.style.display = 'none';
    modal.appendChild(iframe);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

        // Ensure modal has a minimum height while the spinner is active so the loader is visible
        modal.style.minHeight = '360px';

        // Trigger overlay show class on next animation frame to allow CSS transitions
        requestAnimationFrame(function() {
            overlay.classList.add('mlb-modal-show');
        });

        // Focus management
        const focusableSelectors = 'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])';
        let firstFocusable = null;
        let lastFocusable = null;

        function updateFocusable() {
            const nodes = modal.querySelectorAll(focusableSelectors);
            if (nodes.length) {
                firstFocusable = nodes[0];
                lastFocusable = nodes[nodes.length - 1];
            } else {
                firstFocusable = closeBtn;
                lastFocusable = closeBtn;
            }
        }

        updateFocusable();

        function handleKey(e) {
            if (e.key === 'Escape') {
                closeModal();
                return;
            }
            if (e.key === 'Tab') {
                updateFocusable();
                if (!firstFocusable || !lastFocusable) return;
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            }
        }

        function closeModal(shouldRestore) {
            document.removeEventListener('keydown', handleKey);
            if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            if (shouldRestore !== false && activeEl && typeof activeEl.focus === 'function') {
                activeEl.focus();
            }
        }

        closeBtn.addEventListener('click', function() { closeModal(true); });
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeModal(true);
        });
        document.addEventListener('keydown', handleKey);

        // Timeout: open in new tab if iframe doesn't load
        loadTimeout = setTimeout(function() {
            if (iframe && !iframe._mlb_loaded) {
                try {
                    if (bookingPageUrlObj && bookingPageUrlObj.href) {
                        window.open(bookingPageUrlObj.href, '_blank');
                    } else if (typeof bookingPageUrl === 'string' && bookingPageUrl.length) {
                        window.open(bookingPageUrl, '_blank');
                    } else {
                        // As a last resort, open the constructed iframeSrc
                        window.open(iframeSrc, '_blank');
                    }
                } catch (e) {
                    try { window.open(iframeSrc, '_blank'); } catch (err) { /* ignore */ }
                }
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            }
        }, IFRAME_LOAD_TIMEOUT);

        closeBtn.focus();
    };

})();
