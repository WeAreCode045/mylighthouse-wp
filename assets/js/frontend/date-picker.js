/**
 * Global Date Picker Controller
 * Controls the date picker modal rendered by PHP
 *
 * @package Mylighthouse_Booker
 */

window.MLB_DatePicker = (function() {
    'use strict';

    let modal = null;
    let picker = null;
    let currentCallback = null;
    let initialized = false;

    /**
     * Initialize date picker
     */
    function init() {
        if (initialized) return;

        modal = document.getElementById('mlb-date-picker-modal');
        if (!modal) {
            console.error('MLB: Date picker modal not found in DOM');
            return;
        }

        const dateInput = document.getElementById('mlb-global-datepicker');
        if (!dateInput) {
            console.error('MLB: Date picker input not found');
            return;
        }

        // Check if easepick is loaded
        if (typeof easepick === 'undefined' || typeof easepick.create !== 'function') {
            console.error('MLB: Easepick library not loaded');
            return;
        }

        // Initialize easepick
        try {
            picker = new easepick.create({
                element: dateInput,
                css: [(window.MLBPluginUrl && window.MLBPluginUrl.url || '') + 'assets/vendor/easepick/easepick.css'],
                inline: true,
                grid: 1,
                calendars: 1,
                lang: 'nl-NL',
                format: 'YYYY-MM-DD',
                plugins: ['RangePlugin', 'LockPlugin'],
                RangePlugin: {
                    tooltipNumber(num) { 
                        return num - 1; 
                    },
                    locale: {
                        one: 'nacht',
                        other: 'nachten'
                    }
                },
                LockPlugin: {
                    minDate: new Date(),
                    minDays: 1
                },
                setup(picker) {
                    picker.on('select', function(e) {
                        // Auto-submit when both dates are selected
                        if (picker.getStartDate() && picker.getEndDate()) {
                            // Delay to allow visual feedback and ensure dates are set
                            setTimeout(function() {
                                if (currentCallback && picker.getStartDate() && picker.getEndDate()) {
                                    // Format dates using native JavaScript to ensure YYYY-MM-DD format
                                    const startDate = new Date(picker.getStartDate());
                                    const endDate = new Date(picker.getEndDate());
                                    
                                    const arrival = startDate.getFullYear() + '-' + 
                                        String(startDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                        String(startDate.getDate()).padStart(2, '0');
                                    
                                    const departure = endDate.getFullYear() + '-' + 
                                        String(endDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                        String(endDate.getDate()).padStart(2, '0');
                                    
                                    const dates = {
                                        arrival: arrival,
                                        departure: departure
                                    };
                                    currentCallback(dates);
                                    close();
                                }
                            }, 500);
                        }
                    });
                }
            });

            wireControls();
            initialized = true;
        } catch (error) {
            console.error('MLB: Error initializing easepick:', error);
        }
    }

    /**
     * Wire up modal controls
     */
    function wireControls() {
        const closeBtn = modal.querySelector('.mlb-modal-close');
        const cancelBtn = modal.querySelector('.mlb-date-cancel');
        const confirmBtn = modal.querySelector('.mlb-date-confirm');
        const overlay = modal.querySelector('.mlb-modal-overlay');

        /**
         * Close modal
         */
        function close() {
            modal.classList.remove('mlb-modal-show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            
            if (picker) {
                picker.clear();
            }
            currentCallback = null;
            
            const confirmBtn = modal.querySelector('.mlb-date-confirm');
            if (confirmBtn) confirmBtn.disabled = true;
        }

        // Close button
        if (closeBtn) {
            closeBtn.addEventListener('click', close);
        }

        // Cancel button
        if (cancelBtn) {
            cancelBtn.addEventListener('click', close);
        }
        
        // Confirm button
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                if (!picker || !picker.getStartDate() || !picker.getEndDate()) {
                    return;
                }
                
                const dates = {
                    arrival: picker.getStartDate().format('YYYY-MM-DD'),
                    departure: picker.getEndDate().format('YYYY-MM-DD')
                };

                if (currentCallback) {
                    currentCallback(dates);
                }
                close();
            });
        }

        // Click outside to close
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    close();
                }
            });
        }

        // Escape key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('mlb-modal-show')) {
                close();
            }
        });
    }

    /**
     * Open date picker modal
     *
     * @param {Function} callback Callback function to receive selected dates
     */
    function open(callback) {
        if (!initialized) {
            init();
        }

        if (!modal || !picker) {
            console.error('MLB: Date picker not properly initialized');
            return;
        }

        currentCallback = callback;
        
        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            modal.classList.add('mlb-modal-show');
        });
        
        if (picker) {
            picker.show();
        }
        
        const confirmBtn = modal.querySelector('.mlb-date-confirm');
        if (confirmBtn) {
            confirmBtn.disabled = true;
        }
    }

    // Public API
    return {
        open: open
    };
})();

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // Initialization will happen on first open()
    });
}
