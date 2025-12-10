/**
 * Frontend Spinner Handler
 * Manages loading spinner display and background images
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle spinner background images across all spinner instances
    var spinnerImgs = document.querySelectorAll('.mlb-spinner-image');
    if (!spinnerImgs.length) {
        return;
    }

    spinnerImgs.forEach(function(spinnerImg) {
        var bg = spinnerImg.dataset ? spinnerImg.dataset.bgImage : null;
        if (bg) {
            spinnerImg.style.backgroundImage = 'url(' + bg + ')';
        }
    });
});
