/**
 * Fallback handler for elements with `data-trigger-modal="true"`.
 *
 * If the main form scripts aren't present, this will attempt to initialize
 * or open the date modal for the closest `.mlb-form` when a trigger button
 * is clicked.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('[data-trigger-modal="true"]');
        if (!btn) return;

        // Prevent double-handling if other scripts already processed this click
        if (btn._mlbHandled) return;
        btn._mlbHandled = true;

        e.preventDefault();

        var form = btn.closest && btn.closest('.mlb-form');
        if (!form) return;

        // Try to find an existing overlay for this form
        var formId = form.id || '';
        var overlay = form._mlbModalOverlay || document.querySelector('.mlb-calendar-modal-overlay[data-form-id="' + formId + '"]');
        if (overlay) {
            overlay.classList.add('mlb-calendar-modal-show');
            return;
        }

        // If not present, ask other scripts to initialize the modal for this form
        try {
            var evt = new CustomEvent('mlb-maybe-init-modal', { detail: { form: form } });
            document.dispatchEvent(evt);
        } catch (err) {
            // ignore
        }

        // After a short delay try to open it (gives init handlers time to create overlay)
        setTimeout(function () {
            try {
                var overlay2 = form._mlbModalOverlay || document.querySelector('.mlb-calendar-modal-overlay[data-form-id="' + formId + '"]');
                if (overlay2) {
                    overlay2.classList.add('mlb-calendar-modal-show');
                    return;
                }

                // As a final fallback, dispatch the open event directly on the form
                var openEvt = new CustomEvent('mlb-open-calendar', { bubbles: true });
                try { form.dispatchEvent(openEvt); } catch (err) {}
                try { if (window.jQuery) jQuery(form).trigger('mlb-open-calendar'); } catch (err) {}
            } catch (err) {
                // swallow errors in fallback
                console.debug('mlb modal-trigger-fallback error', err);
            }
        }, 120);
    }, false);

})();
