<?php
/**
 * Special Modal Template
 *
 * A <template> that the frontend JS will clone to render the modal-driven
 * date picker for special booking forms.
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<template id="mlb-modal-template-special">
    <div class="mlb-calendar-modal-overlay" data-form-id="{{FORM_ID}}">
        <div class="mlb-calendar-modal-container">
            <button type="button" class="mlb-calendar-modal-close" aria-label="<?php echo esc_attr( 'Close calendar' ); ?>">&times;</button>
            <div class="mlb-modal-content-wrapper">
                <div class="mlb-modal-calendar"></div>
                <div class="mlb-modal-right-column">
                    <div class="mlb-booking-details">
                        <h3><?php echo esc_html( 'Booking Details' ); ?></h3>
                        <div class="mlb-booking-info">
                            <div class="mlb-info-row">
                                <span class="mlb-label"><?php echo esc_html( 'Hotel:' ); ?></span>
                                <span class="mlb-hotel-name">Hotel Name</span>
                            </div>
                            <div class="mlb-info-row mlb-room-row" style="display:none;">
                                <span class="mlb-label"><?php echo esc_html( 'Room:' ); ?></span>
                                <span class="mlb-room-name">Room Name</span>
                            </div>
                            <div class="mlb-info-row mlb-special-row">
                                <span class="mlb-label"><?php echo esc_html( 'Special:' ); ?></span>
                                <span class="mlb-special-name">Special Name</span>
                            </div>
                            <div class="mlb-info-row">
                                <span class="mlb-label"><?php echo esc_html( 'Check-in:' ); ?></span>
                                <span class="mlb-arrival-date"><?php echo esc_html( 'Select dates' ); ?></span>
                            </div>
                            <div class="mlb-info-row">
                                <span class="mlb-label"><?php echo esc_html( 'Check-out:' ); ?></span>
                                <span class="mlb-departure-date"><?php echo esc_html( 'Select dates' ); ?></span>
                            </div>
                        </div>
                        <div class="mlb-modal-actions">
                            <button type="button" class="mlb-modal-submit-btn" disabled>
                                <span class="mlb-modal-cta-room" style="display:none;"><?php echo esc_html( 'Book This Room' ); ?></span>
                                <span class="mlb-modal-cta-special"><?php echo esc_html( 'Book Special' ); ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
