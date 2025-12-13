<?php
/**
 * Room Modal Template
 *
 * This file provides a <template> that the frontend JS will clone to
 * render the modal-driven date picker for room booking forms.
 */

// Security
if (! defined('ABSPATH')) {
    exit;
}
?>

<template id="mlb-modal-template-room">
    <div class="mlb-calendar-modal-overlay" data-form-id="{{FORM_ID}}">
        <div class="mlb-calendar-modal-container">
            <button type="button" class="mlb-calendar-modal-close" aria-label="<?php echo esc_attr__( 'Close calendar', 'mylighthouse-booker' ); ?>">&times;</button>
            <div class="mlb-modal-content-wrapper">
                <div class="mlb-modal-calendar"></div>
                <div class="mlb-modal-right-column">
                    <div class="mlb-booking-details">
                        <h3><?php echo esc_html__( 'Booking Details', 'mylighthouse-booker' ); ?></h3>
                        <div class="mlb-booking-info">
                            <div class="mlb-info-row">
                                <span class="mlb-label"><?php echo esc_html__( 'Hotel:', 'mylighthouse-booker' ); ?></span>
                                <span class="mlb-hotel-name">Hotel Name</span>
                            </div>
                            <div class="mlb-info-row mlb-room-row" style="display:none;">
                                <span class="mlb-label"><?php echo esc_html__( 'Room:', 'mylighthouse-booker' ); ?></span>
                                <span class="mlb-room-name">Room Name</span>
                            </div>
                            <div class="mlb-info-row mlb-special-row" style="display:none;">
                                <span class="mlb-label"><?php echo esc_html__( 'Special:', 'mylighthouse-booker' ); ?></span>
                                <span class="mlb-special-name">Special Name</span>
                            </div>
                            <div class="mlb-info-row">
                                <span class="mlb-label"><?php echo esc_html__( 'Check-in:', 'mylighthouse-booker' ); ?></span>
                                <span class="mlb-arrival-date"><?php echo esc_html__( 'Select dates', 'mylighthouse-booker' ); ?></span>
                            </div>
                            <div class="mlb-info-row">
                                <span class="mlb-label"><?php echo esc_html__( 'Check-out:', 'mylighthouse-booker' ); ?></span>
                                <span class="mlb-departure-date"><?php echo esc_html__( 'Select dates', 'mylighthouse-booker' ); ?></span>
                            </div>
                        </div>
                        <div class="mlb-modal-actions">
                            <button type="button" class="mlb-modal-submit-btn" disabled>
                                <span class="mlb-modal-cta-room"><?php echo esc_html__( 'Book This Room', 'mylighthouse-booker' ); ?></span>
                                <span class="mlb-modal-cta-special" style="display:none;"><?php echo esc_html__( 'Book Special', 'mylighthouse-booker' ); ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
