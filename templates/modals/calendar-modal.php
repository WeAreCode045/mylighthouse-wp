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
			<div class="mlb-modal-calendar"></div>
			<div class="mlb-modal-right-column">
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
						<div class="mlb-info-row mlb-dates-row" style="display:none;">
							<span class="mlb-label"><?php echo esc_html__('Arrival:', 'mylighthouse-booker'); ?></span>
							<span class="mlb-arrival-date">-</span>
						</div>
						<div class="mlb-info-row mlb-dates-row" style="display:none;">
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
			</div>
		</div>
	</div>
</div>
