<?php
/**
 * Booking form - Hotel variant
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="mlb-booking-form mlb-layout-<?php echo esc_attr($layout); ?> mlb-placement-<?php echo esc_attr($placement); ?>" data-layout="<?php echo esc_attr($layout); ?>" data-button-placement="<?php echo esc_attr($placement); ?>">
    <form id="<?php echo esc_attr($form_uid); ?>" class="mlb-form" data-hotel-name="<?php echo esc_attr($hotel_name); ?>" data-form-type="hotel">

        <?php if ($show_hotel_select) : ?>
            <div class="form-field hotel-selector has-icon">
                <?php if (!empty($args['hotel_icon_html'])) { echo $args['hotel_icon_html']; } else { ?>
                    <i class="fas fa-map-marker-alt mlb-field-icon mlb-location-icon" aria-hidden="true"></i>
                <?php } ?>
                <select name="hotel_id" id="<?php echo esc_attr($form_uid); ?>-hotel-select" class="mlb-hotel-select" required>
                    <option value="" disabled <?php echo empty($default_hotel_id) ? 'selected' : ''; ?>><?php echo esc_html($hotel_placeholder); ?></option>
                    <?php foreach ($hotels as $hotel) : 
                        $hotel_option_id = isset($hotel['hotel_id']) ? $hotel['hotel_id'] : (isset($hotel['id']) ? $hotel['id'] : '');
                    ?>
                        <option value="<?php echo esc_attr($hotel_option_id); ?>" <?php selected($default_hotel_id, $hotel_option_id); ?>>
                            <?php echo esc_html($hotel['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else : ?>
            <input type="hidden" name="hotel_id" value="<?php echo esc_attr($default_hotel_id); ?>" />
            <input type="hidden" name="hotel_name" value="<?php echo esc_attr($hotel_name); ?>" />
        <?php endif; ?>

        <div class="form-field daterange-field has-icon">
            <?php if (!empty($args['date_icon_html'])) { echo $args['date_icon_html']; } else { ?>
                <i class="fas fa-calendar-alt mlb-field-icon mlb-calendar-icon" aria-hidden="true"></i>
            <?php } ?>
            <input
                type="text"
                id="<?php echo esc_attr($form_uid); ?>-daterange"
                class="mlb-daterange"
                name="daterange"
                placeholder="<?php echo esc_attr($daterange_placeholder); ?>"
                data-arrival-text="<?php echo esc_attr(isset($args['arrival_text']) ? $args['arrival_text'] : __('Select Arrival Date', 'mylighthouse-booker')); ?>"
                data-departure-text="<?php echo esc_attr(isset($args['departure_text']) ? $args['departure_text'] : __('Select Departure Date', 'mylighthouse-booker')); ?>"
                required
                readonly />
            <input type="hidden" id="<?php echo esc_attr($form_uid); ?>-checkin" class="mlb-checkin" name="Arrival" />
            <input type="hidden" id="<?php echo esc_attr($form_uid); ?>-checkout" class="mlb-checkout" name="Departure" />
        </div>

        <div class="form-actions">
            <button type="submit" class="mlb-submit-btn">
                <?php echo esc_html($button_label); ?>
            </button>
        </div>
    </form>
</div>
