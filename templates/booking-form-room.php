<?php
/**
 * Booking form - Room variant
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="mlb-booking-form mlb-layout-<?php echo esc_attr($layout); ?> mlb-placement-<?php echo esc_attr($placement); ?> mlb-room-form" data-layout="<?php echo esc_attr($layout); ?>" data-button-placement="<?php echo esc_attr($placement); ?>" data-single-button="true">
    <form id="<?php echo esc_attr($form_uid); ?>" class="mlb-form" data-hotel-id="<?php echo esc_attr($default_hotel_id); ?>" data-room-id="<?php echo esc_attr($room_id); ?>" data-hotel-name="<?php echo esc_attr($hotel_name); ?>" data-room-name="<?php echo esc_attr($room_name); ?>" data-form-type="room">
        <input type="hidden" name="hotel_id" value="<?php echo esc_attr($default_hotel_id); ?>" />
        <input type="hidden" name="room_id" value="<?php echo esc_attr($room_id); ?>" />
        <input type="hidden" name="hotel_name" value="<?php echo esc_attr($hotel_name); ?>" />
        <input type="hidden" name="room_name" value="<?php echo esc_attr($room_name); ?>" />

        <!-- Dates are selected in the modal. Keep hidden inputs for submission. -->
        <input type="hidden" id="<?php echo esc_attr($form_uid); ?>-checkin" class="mlb-checkin" name="Arrival" />
        <input type="hidden" id="<?php echo esc_attr($form_uid); ?>-checkout" class="mlb-checkout" name="Departure" />

        <div class="form-actions">
            <button type="button" class="mlb-submit-btn mlb-book-room-btn mlb-btn-primary" data-trigger-modal="true">
                <?php echo esc_html($button_label); ?>
            </button>
        </div>
    </form>
</div>
