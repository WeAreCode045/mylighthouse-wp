<?php
/**
 * Modular Booking Form Template
 * Preserves original layout and styling while adding modular component system
 *
 * @package Mylighthouse_Booker
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extract variables (matching original booking-form.php structure)
$hotels            = isset($args['hotels']) ? $args['hotels'] : array();
$show_hotel_select = isset($args['show_hotel_select']) ? $args['show_hotel_select'] : false;
$hotel_id          = isset($args['default_hotel_id']) ? $args['default_hotel_id'] : '';
$button_label      = isset($args['button_label']) ? $args['button_label'] : __('Check Availability', 'mylighthouse-booker');
$layout            = isset($args['layout']) ? $args['layout'] : 'inline';
$placement         = isset($args['placement']) ? $args['placement'] : 'after';
$booking_page_url  = isset($args['booking_page_url']) ? $args['booking_page_url'] : '';
$room_id           = isset($args['room_id']) ? $args['room_id'] : '';
$room_name         = isset($args['room_name']) ? $args['room_name'] : '';
$hotel_name        = isset($args['hotel_name']) ? $args['hotel_name'] : '';
$special_id        = isset($args['special_id']) ? $args['special_id'] : '';
$special_name      = isset($args['special_name']) ? $args['special_name'] : '';
$hotel_placeholder = isset($args['hotel_placeholder']) ? $args['hotel_placeholder'] : __('Choose a hotel...', 'mylighthouse-booker');
$daterange_placeholder = isset($args['daterange_placeholder']) ? $args['daterange_placeholder'] : __('Select Arrival Date ⇢ Select Departure Date', 'mylighthouse-booker');
$form_type         = isset($args['form_type']) ? $args['form_type'] : 'hotel';

// Generate unique form ID
static $form_instance_counter = 0;
$form_instance_counter++;
$form_uid = 'mlb-form-' . $form_instance_counter;
?>

<?php if ($form_type === 'hotel'): ?>
    <!-- Hotel Form: Preserves original layout with modular data attributes -->
    <div class="mlb-booking-form mlb-layout-<?php echo esc_attr($layout); ?> mlb-placement-<?php echo esc_attr($placement); ?>" data-layout="<?php echo esc_attr($layout); ?>" data-button-placement="<?php echo esc_attr($placement); ?>">
        <form id="<?php echo esc_attr($form_uid); ?>" class="mlb-form" method="GET" action="<?php echo esc_url($booking_page_url); ?>" data-mlb-hotel-form data-hotel-id="<?php echo esc_attr($hotel_id); ?>" data-hotel-name="<?php echo esc_attr($hotel_name); ?>">

            <?php if ($show_hotel_select) : ?>
                <div class="form-field hotel-selector has-icon">
                    <?php if (!empty($args['hotel_icon_html'])) { echo $args['hotel_icon_html']; } else { ?>
                        <i class="fas fa-map-marker-alt mlb-field-icon mlb-location-icon" aria-hidden="true"></i>
                    <?php } ?>
                    <select name="hotel_id" id="<?php echo esc_attr($form_uid); ?>-hotel-select" class="mlb-hotel-select" data-mlb-hotel-select required>
                        <option value="" disabled <?php echo empty($hotel_id) ? 'selected' : ''; ?>><?php echo esc_html($hotel_placeholder); ?></option>
                        <?php foreach ($hotels as $hotel) : 
                            $hotel_option_id = isset($hotel['hotel_id']) ? $hotel['hotel_id'] : (isset($hotel['id']) ? $hotel['id'] : '');
                        ?>
                            <option value="<?php echo esc_attr($hotel_option_id); ?>" <?php selected($hotel_id, $hotel_option_id); ?>>
                                <?php echo esc_html($hotel['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else : ?>
                <input type="hidden" name="hotel_id" value="<?php echo esc_attr($hotel_id); ?>" />
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
                    data-mlb-date-input
                    placeholder="<?php echo esc_attr($daterange_placeholder); ?>"
                    data-arrival-text="<?php echo esc_attr(isset($args['arrival_text']) ? $args['arrival_text'] : __('Select Arrival Date', 'mylighthouse-booker')); ?>"
                    data-departure-text="<?php echo esc_attr(isset($args['departure_text']) ? $args['departure_text'] : __('Select Departure Date', 'mylighthouse-booker')); ?>"
                    required
                    readonly />
                <!-- Hidden fields for JavaScript only - no name attribute to prevent form submission -->
                <input type="hidden" id="<?php echo esc_attr($form_uid); ?>-checkin" class="mlb-checkin" />
                <input type="hidden" id="<?php echo esc_attr($form_uid); ?>-checkout" class="mlb-checkout" />
            </div>

            <div class="form-actions">
                <button type="submit" class="mlb-submit-btn" data-mlb-submit>
                    <?php echo esc_html($button_label); ?>
                </button>
            </div>
        </form>
    </div>

<?php elseif ($form_type === 'room'): ?>
    <!-- Room Form: Preserves original layout with modular data attributes -->
    <div class="mlb-booking-form mlb-layout-<?php echo esc_attr($layout); ?> mlb-placement-<?php echo esc_attr($placement); ?> mlb-room-form" data-layout="<?php echo esc_attr($layout); ?>" data-button-placement="<?php echo esc_attr($placement); ?>" data-single-button="true">
        <form id="<?php echo esc_attr($form_uid); ?>" class="mlb-form" method="GET" action="<?php echo esc_url($booking_page_url); ?>" data-hotel-id="<?php echo esc_attr($hotel_id); ?>" data-room-id="<?php echo esc_attr($room_id); ?>" data-hotel-name="<?php echo esc_attr($hotel_name); ?>" data-room-name="<?php echo esc_attr($room_name); ?>">
            <input type="hidden" name="hotel_id" value="<?php echo esc_attr($hotel_id); ?>" />
            <input type="hidden" name="room_id" value="<?php echo esc_attr($room_id); ?>" />
            <input type="hidden" name="hotel_name" value="<?php echo esc_attr($hotel_name); ?>" />
            <input type="hidden" name="room_name" value="<?php echo esc_attr($room_name); ?>" />

            <!-- Dates are selected in the modal. Keep hidden inputs for submission. -->
            <input type="hidden" id="<?php echo esc_attr($form_uid); ?>-checkin" class="mlb-checkin" name="Arrival" />
            <input type="hidden" id="<?php echo esc_attr($form_uid); ?>-checkout" class="mlb-checkout" name="Departure" />

            <div class="form-actions">
                <button type="button" class="mlb-submit-btn mlb-book-room-btn mlb-btn-primary" data-mlb-book-room data-hotel-id="<?php echo esc_attr($hotel_id); ?>" data-room-id="<?php echo esc_attr($room_id); ?>">
                    <?php echo esc_html($button_label); ?>
                </button>
            </div>
        </form>
    </div>

<?php elseif ($form_type === 'special'): ?>
    <!-- Special Form: Preserves original layout with modular data attributes -->
    <div class="mlb-booking-form mlb-layout-<?php echo esc_attr($layout); ?> mlb-placement-<?php echo esc_attr($placement); ?>" data-layout="<?php echo esc_attr($layout); ?>" data-button-placement="<?php echo esc_attr($placement); ?>" data-single-button="true">
        <form id="<?php echo esc_attr($form_uid); ?>" class="mlb-form mlb-special-form-type" method="GET" action="<?php echo esc_url(add_query_arg(array('Rate' => $special_id), $booking_page_url)); ?>" data-hotel-id="<?php echo esc_attr($hotel_id); ?>" data-rate-id="<?php echo esc_attr($special_id); ?>" data-special-id="<?php echo esc_attr($special_id); ?>" data-hotel-name="<?php echo esc_attr($hotel_name); ?>" data-rate-name="<?php echo esc_attr($special_name); ?>">
            <input type="hidden" name="hotel_id" value="<?php echo esc_attr($hotel_id); ?>" />
            <input type="hidden" name="special_id" value="<?php echo esc_attr($special_id); ?>" />
            <input type="hidden" name="Rate" value="<?php echo esc_attr($special_id); ?>" />
            <input type="hidden" name="hotel_name" value="<?php echo esc_attr($hotel_name); ?>" />
            <input type="hidden" name="rate_name" value="<?php echo esc_attr($special_name); ?>" />

            <div class="form-actions">
                <button type="button" class="mlb-submit-btn mlb-book-special-btn mlb-btn-primary" data-mlb-book-special data-hotel-id="<?php echo esc_attr($hotel_id); ?>" data-rate-id="<?php echo esc_attr($special_id); ?>">
                    <?php echo esc_html($button_label); ?>
                </button>
            </div>
        </form>
    </div>

<?php endif; ?>
