<?php

/**
 * Booking form template
 *
 * @package StandaloneTech
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Extract variables
$hotels            = isset($args['hotels']) ? $args['hotels'] : array();
$show_hotel_select = isset($args['show_hotel_select']) ? $args['show_hotel_select'] : false;
$default_hotel_id  = isset($args['default_hotel_id']) ? $args['default_hotel_id'] : '';
$button_label      = isset($args['button_label']) ? $args['button_label'] : __('Check Availability', 'mylighthouse-booker');
$layout            = isset($args['layout']) ? $args['layout'] : 'inline';
$placement         = isset($args['placement']) ? $args['placement'] : 'after';
$booking_page_url  = isset($args['booking_page_url']) ? $args['booking_page_url'] : '';
$room_id           = isset($args['room_id']) ? $args['room_id'] : '';
$room_name         = isset($args['room_name']) ? $args['room_name'] : '';
$hotel_name        = isset($args['hotel_name']) ? $args['hotel_name'] : '';
$special_id        = isset($args['special_id']) ? $args['special_id'] : '';
// rate_name is used in older templates; prefer special_name from args then fall back
$rate_name         = isset($args['special_name']) ? $args['special_name'] : (isset($args['rate_name']) ? $args['rate_name'] : '');
$hotel_placeholder = isset($args['hotel_placeholder']) ? $args['hotel_placeholder'] : __('Choose a hotel...', 'mylighthouse-booker');
$daterange_placeholder = isset($args['daterange_placeholder']) ? $args['daterange_placeholder'] : __('Select Arrival Date ⇢ Select Departure Date', 'mylighthouse-booker');
$show_date_picker  = isset($args['show_date_picker']) ? $args['show_date_picker'] : true;
$form_type         = isset($args['form_type']) ? $args['form_type'] : 'hotel';

// Determine if this is a room-specific form
$is_room_form = !empty($room_id) || $form_type === 'room';
$is_special_form = !empty($special_id) || $form_type === 'special';

// Generate unique form ID to support multiple forms on the same page
static $form_instance_counter = 0;
$form_instance_counter++;
$form_uid = 'mlb-form-' . $form_instance_counter;
?>

<?php
// Decide which partial to render. Available partials:
// - booking-form-hotel.php
// - booking-form-room.php
// - booking-form-special.php

$partial = 'booking-form-hotel.php';
if ($form_type === 'room' || ! empty($room_id)) {
	$partial = 'booking-form-room.php';
} elseif ($form_type === 'special' || ! empty($special_id)) {
	$partial = 'booking-form-special.php';
}

include __DIR__ . '/' . $partial;

// Keep the JS clone template (special) available for legacy JS that clones it.
?>
<template id="mlb-special-form-template">
	<div class="mlb-booking-form mlb-layout-<?php echo esc_attr($layout); ?> mlb-placement-<?php echo esc_attr($placement); ?>" data-layout="<?php echo esc_attr($layout); ?>" data-button-placement="<?php echo esc_attr($placement); ?>">
		<form class="mlb-form mlb-special-form-type" method="GET" action="<?php echo esc_url($booking_page_url); ?>">
			<div class="form-field hotel-selector has-icon">
				<?php if (!empty($args['hotel_icon_html'])) { echo $args['hotel_icon_html']; } else { ?>
					<i class="fas fa-map-marker-alt mlb-field-icon mlb-location-icon" aria-hidden="true"></i>
				<?php } ?>
				<select name="hotel_id" class="mlb-hotel-select" required>
					<option value="" disabled selected><?php echo esc_html($hotel_placeholder); ?></option>
					<?php foreach ($hotels as $hotel) : 
						$hotel_option_id = isset($hotel['hotel_id']) ? $hotel['hotel_id'] : (isset($hotel['id']) ? $hotel['id'] : '');
					?>
						<option value="<?php echo esc_attr($hotel_option_id); ?>"><?php echo esc_html($hotel['name']); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="form-field daterange-field has-icon">
				<?php if (!empty($args['date_icon_html'])) { echo $args['date_icon_html']; } else { ?>
					<i class="fas fa-calendar-alt mlb-field-icon mlb-calendar-icon" aria-hidden="true"></i>
				<?php } ?>
				<input type="text" class="mlb-daterange" name="daterange" placeholder="<?php echo esc_attr($daterange_placeholder); ?>" data-arrival-text="<?php echo esc_attr(isset($args['arrival_text']) ? $args['arrival_text'] : __('Select Arrival Date', 'mylighthouse-booker')); ?>" data-departure-text="<?php echo esc_attr(isset($args['departure_text']) ? $args['departure_text'] : __('Select Departure Date', 'mylighthouse-booker')); ?>" readonly />
				<input type="hidden" class="mlb-checkin" name="Arrival" />
				<input type="hidden" class="mlb-checkout" name="Departure" />
			</div>

			<input type="hidden" name="rate" class="mlb-rate-id" value="" />
			<input type="hidden" name="rate_name" class="mlb-rate-name" value="" />
			<input type="hidden" name="hotel_name" class="mlb-hotel-name" value="" />

			<div class="form-actions">
				<button type="button" class="mlb-submit-btn mlb-book-special-btn" data-trigger-modal="true"><?php echo esc_html($button_label); ?></button>
			</div>
		</form>
	</div>
</template>
<?php
// Include modal templates used by JS (printed once per page)
// The room modal template provides `mlb-modal-template-room` for JS to clone.
if ( file_exists( __DIR__ . '/modals/modal-room.php' ) ) {
	include __DIR__ . '/modals/modal-room.php';
}
// Include special modal template
if ( file_exists( __DIR__ . '/modals/modal-special.php' ) ) {
	include __DIR__ . '/modals/modal-special.php';
}
// Include calendar modal template wrapper
if ( file_exists( __DIR__ . '/modals/modal-calendar-template.php' ) ) {
	include __DIR__ . '/modals/modal-calendar-template.php';
}
// Include small UI fragments (close button, spinner, icons)
if ( file_exists( __DIR__ . '/modals/modal-fragments.php' ) ) {
	include __DIR__ . '/modals/modal-fragments.php';
}
