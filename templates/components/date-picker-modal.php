<?php
/**
 * Global Date Picker Modal Component
 * Reusable date picker for all booking forms
 *
 * @package Mylighthouse_Booker
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="mlb-date-picker-modal" class="mlb-modal mlb-date-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="mlb-date-modal-title">
    <div class="mlb-modal-overlay"></div>
    <div class="mlb-modal-content">
        <button type="button" class="mlb-modal-close" aria-label="<?php esc_attr_e('Close', 'mylighthouse-booker'); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        
        <h3 id="mlb-date-modal-title" class="mlb-modal-title">
            <?php esc_html_e('Select Your Dates', 'mylighthouse-booker'); ?>
        </h3>
        
        <div class="mlb-date-picker-container">
            <input 
                type="text" 
                id="mlb-global-datepicker" 
                class="mlb-date-input" 
                readonly 
                placeholder="<?php esc_attr_e('Select check-in and check-out dates', 'mylighthouse-booker'); ?>"
                aria-label="<?php esc_attr_e('Date range', 'mylighthouse-booker'); ?>"
            >
        </div>
    </div>
</div>
