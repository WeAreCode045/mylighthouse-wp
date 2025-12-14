<?php
/**
 * Calendar Modal Template
 * 
 * Template for the calendar modal overlay. Used by calendar.js
 * 
 * @package Mylighthouse_Booker
 */

if (! defined('ABSPATH')) {
	exit;
}
?>
<div class="mlb-calendar-modal-overlay">
	<div class="mlb-calendar-modal-container">
		<button type="button" class="mlb-calendar-modal-close" aria-label="<?php echo esc_attr__('Close calendar', 'mylighthouse-booker'); ?>">&times;</button>
		<div class="mlb-modal-content-wrapper">
			<!-- Calendar View Section -->
			<div class="mlb-modal-calendar-section">
				<div class="mlb-modal-calendar"></div>
			</div>
			<!-- Details View Section (loaded dynamically) -->
			<div class="mlb-modal-details-section">
				<!-- Content will be injected by JavaScript -->
			</div>
		</div>
	</div>
</div>

<!-- Booking Details Template (hidden, used by JS) -->
<template id="mlb-booking-details-template">
	<button type="button" class="mlb-modal-back-btn" aria-label="<?php echo esc_attr__('Back to calendar', 'mylighthouse-booker'); ?>">
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
		<?php echo esc_html__('Back', 'mylighthouse-booker'); ?>
	</button>
	<div class="mlb-booking-details">
		<h3><?php echo esc_html__('Booking Details', 'mylighthouse-booker'); ?></h3>
		<div class="mlb-booking-info">
			<div class="mlb-info-row">
				<span class="mlb-label"><?php echo esc_html__('Hotel:', 'mylighthouse-booker'); ?></span>
				<span class="mlb-hotel-name"></span>
			</div>
			<div class="mlb-info-row mlb-room-row">
				<span class="mlb-label"><?php echo esc_html__('Room:', 'mylighthouse-booker'); ?></span>
				<span class="mlb-room-name"></span>
			</div>
			<div class="mlb-info-row mlb-dates-row">
				<span class="mlb-label"><?php echo esc_html__('Arrival:', 'mylighthouse-booker'); ?></span>
				<span class="mlb-arrival-date">-</span>
			</div>
			<div class="mlb-info-row mlb-dates-row">
				<span class="mlb-label"><?php echo esc_html__('Departure:', 'mylighthouse-booker'); ?></span>
				<span class="mlb-departure-date">-</span>
			</div>
		</div>
	</div>
	<div class="mlb-modal-actions">
		<button type="button" class="mlb-modal-submit-btn" disabled>
			<span class="mlb-modal-cta-room"><?php echo esc_html__('Book This Room', 'mylighthouse-booker'); ?></span>
		</button>
	</div>
</template>

<div style="display:none;" id="mlb-calendar-modal-wrapper">
</div>
