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
				<?php include plugin_dir_path(__FILE__) . 'modal-calendar.php'; ?>
			</div>
			<!-- Details View Section (loaded dynamically) -->
			<div class="mlb-modal-details-section"></div>
		</div>
	</div>
</div>
