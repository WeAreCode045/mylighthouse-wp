<?php
/**
 * Booking form - Special variant
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="mlb-booking-form mlb-layout-<?php echo esc_attr($layout); ?> mlb-placement-<?php echo esc_attr($placement); ?> mlb-special-form" data-layout="<?php echo esc_attr($layout); ?>" data-button-placement="<?php echo esc_attr($placement); ?>" data-single-button="true">
    <form id="<?php echo esc_attr($form_uid); ?>" class="mlb-form mlb-special-form-type" data-hotel-id="<?php echo esc_attr($default_hotel_id); ?>" data-rate-id="<?php echo esc_attr($special_id); ?>" data-special-id="<?php echo esc_attr($special_id); ?>" data-hotel-name="<?php echo esc_attr($hotel_name); ?>" data-rate-name="<?php echo esc_attr(isset($rate_name) ? $rate_name : ''); ?>" data-form-type="special">
        <input type="hidden" name="hotel_id" value="<?php echo esc_attr($default_hotel_id); ?>" />
        <input type="hidden" name="special_id" value="<?php echo esc_attr($special_id); ?>" />
        <input type="hidden" name="Rate" value="<?php echo esc_attr($special_id); ?>" />
        <input type="hidden" name="hotel_name" value="<?php echo esc_attr($hotel_name); ?>" />
        <input type="hidden" name="rate_name" value="<?php echo esc_attr(isset($rate_name) ? $rate_name : ''); ?>" />

        <div class="form-actions">
            <button type="button" class="mlb-submit-btn mlb-book-special-btn mlb-btn-primary">
                <?php echo esc_html($button_label); ?>
            </button>
        </div>
    </form>
</div>
