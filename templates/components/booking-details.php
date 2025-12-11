<?php
/**
 * Booking Details Component
 * Shows selected dates and availability check button
 *
 * @package Mylighthouse_Booker
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="mlb-booking-details" class="mlb-booking-details" style="display: none;">
    <div class="mlb-booking-details-content">
        <div class="mlb-booking-dates">
            <div class="mlb-date-item">
                <label class="mlb-date-label">
                    <?php esc_html_e('Check-in', 'mylighthouse-booker'); ?>
                </label>
                <span class="mlb-date-value mlb-arrival-date" data-date="">-</span>
            </div>
            
            <div class="mlb-date-separator" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            
            <div class="mlb-date-item">
                <label class="mlb-date-label">
                    <?php esc_html_e('Check-out', 'mylighthouse-booker'); ?>
                </label>
                <span class="mlb-date-value mlb-departure-date" data-date="">-</span>
            </div>
            
            <button type="button" class="mlb-button mlb-button-link mlb-change-dates" aria-label="<?php esc_attr_e('Change dates', 'mylighthouse-booker'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?php esc_html_e('Change', 'mylighthouse-booker'); ?>
            </button>
        </div>
        
        <div class="mlb-booking-details-meta">
            <span class="mlb-nights-count">
                <strong data-nights="0">0</strong> <?php esc_html_e('nights', 'mylighthouse-booker'); ?>
            </span>
        </div>
        
        <button type="button" class="mlb-button mlb-button-primary mlb-button-full mlb-check-availability">
            <?php esc_html_e('Check Availability', 'mylighthouse-booker'); ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
</div>
