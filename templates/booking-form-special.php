<?php
/**
 * Booking form - Special variant
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="mlb-booking-form mlb-layout-<?php echo esc_attr($layout); ?> mlb-placement-<?php echo esc_attr($placement); ?> mlb-special-form" data-layout="<?php echo esc_attr($layout); ?>" data-button-placement="<?php echo esc_attr($placement); ?>" data-single-button="true">
    <form id="<?php echo esc_attr($form_uid); ?>" class="mlb-form mlb-special-form-type" method="GET" action="<?php echo esc_url(add_query_arg(array('Rate' => $special_id), $booking_page_url)); ?>" data-hotel-id="<?php echo esc_attr($default_hotel_id); ?>" data-rate-id="<?php echo esc_attr($special_id); ?>" data-special-id="<?php echo esc_attr($special_id); ?>" data-hotel-name="<?php echo esc_attr($hotel_name); ?>" data-rate-name="<?php echo esc_attr(isset($rate_name) ? $rate_name : ''); ?>"<?php echo $show_date_picker ? '' : ' data-skip-dates="1"'; ?>>
        <input type="hidden" name="hotel_id" value="<?php echo esc_attr($default_hotel_id); ?>" />
        <input type="hidden" name="special_id" value="<?php echo esc_attr($special_id); ?>" />
        <input type="hidden" name="Rate" value="<?php echo esc_attr($special_id); ?>" />
        <input type="hidden" name="hotel_name" value="<?php echo esc_attr($hotel_name); ?>" />
        <input type="hidden" name="rate_name" value="<?php echo esc_attr(isset($rate_name) ? $rate_name : ''); ?>" />

        <!-- Dates are provided by the modal. Keep only hidden fields for submission. -->
        <input type="hidden" id="<?php echo esc_attr($form_uid); ?>-checkin" class="mlb-checkin" name="Arrival" />
        <input type="hidden" id="<?php echo esc_attr($form_uid); ?>-checkout" class="mlb-checkout" name="Departure" />

        <div class="form-actions">
            <button type="button" class="mlb-submit-btn mlb-book-special-btn mlb-btn-primary"<?php echo $show_date_picker ? ' data-trigger-modal="true"' : ''; ?>>
                <?php echo esc_html($button_label); ?>
            </button>
        </div>
    </form>
</div>
