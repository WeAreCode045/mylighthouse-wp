<?php
/**
 * Modular Booking Form Template
 * Works with the new modular component system
 *
 * @package Mylighthouse_Booker
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extract variables
$form_type = isset($args['form_type']) ? $args['form_type'] : 'hotel';
$hotel_id = isset($args['default_hotel_id']) ? $args['default_hotel_id'] : '';
$room_id = isset($args['room_id']) ? $args['room_id'] : '';
$special_id = isset($args['special_id']) ? $args['special_id'] : '';
$button_label = isset($args['button_label']) ? $args['button_label'] : __('Check Availability', 'mylighthouse-booker');
$show_hotel_select = isset($args['show_hotel_select']) ? $args['show_hotel_select'] : false;
$hotels = isset($args['hotels']) ? $args['hotels'] : array();

// Generate unique form ID
static $form_instance_counter = 0;
$form_instance_counter++;
$form_uid = 'mlb-form-' . $form_instance_counter;
?>

<div class="mlb-booking-form mlb-modular-form" id="<?php echo esc_attr($form_uid); ?>">

    <?php if ($form_type === 'hotel'): ?>
        <!-- Hotel Form: Date input + Submit -->
        <form class="mlb-hotel-form-wrapper" data-mlb-hotel-form data-hotel-id="<?php echo esc_attr($hotel_id); ?>">
            
            <?php if ($show_hotel_select && !empty($hotels)): ?>
                <!-- Hotel Selection Field -->
                <div class="mlb-form-field mlb-hotel-field">
                    <label for="<?php echo esc_attr($form_uid); ?>-hotel" class="mlb-field-label">
                        <?php if (!empty($args['hotel_icon_html'])): ?>
                            <?php echo $args['hotel_icon_html']; ?>
                        <?php endif; ?>
                        <?php esc_html_e('Hotel', 'mylighthouse-booker'); ?>
                    </label>
                    <select id="<?php echo esc_attr($form_uid); ?>-hotel" class="mlb-hotel-select" data-mlb-hotel-select>
                        <?php foreach ($hotels as $hotel): ?>
                            <option value="<?php echo esc_attr($hotel['hotel_id']); ?>" 
                                <?php selected($hotel['hotel_id'], $hotel_id); ?>>
                                <?php echo esc_html($hotel['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Date Input Field -->
            <div class="mlb-form-field mlb-date-field">
                <label for="<?php echo esc_attr($form_uid); ?>-dates" class="mlb-field-label">
                    <?php if (!empty($args['date_icon_html'])): ?>
                        <?php echo $args['date_icon_html']; ?>
                    <?php endif; ?>
                    <?php esc_html_e('Dates', 'mylighthouse-booker'); ?>
                </label>
                <input 
                    type="text" 
                    id="<?php echo esc_attr($form_uid); ?>-dates"
                    class="mlb-date-input" 
                    data-mlb-date-input
                    placeholder="<?php esc_attr_e('Select your dates', 'mylighthouse-booker'); ?>"
                    readonly
                >
            </div>

            <!-- Submit Button -->
            <div class="mlb-form-field mlb-button-field">
                <button type="submit" class="mlb-button mlb-button-primary" data-mlb-submit>
                    <?php echo esc_html($button_label); ?>
                </button>
            </div>
        </form>

    <?php elseif ($form_type === 'room'): ?>
        <!-- Room Form: Book button that opens date picker -->
        <div class="mlb-room-form-wrapper">
            <div class="mlb-room-info">
                <?php if (!empty($args['room_name'])): ?>
                    <h3 class="mlb-room-name"><?php echo esc_html($args['room_name']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($args['hotel_name'])): ?>
                    <p class="mlb-hotel-name"><?php echo esc_html($args['hotel_name']); ?></p>
                <?php endif; ?>
            </div>
            
            <button 
                type="button"
                class="mlb-button mlb-button-primary mlb-book-room-btn"
                data-mlb-book-room
                data-hotel-id="<?php echo esc_attr($hotel_id); ?>"
                data-room-id="<?php echo esc_attr($room_id); ?>"
            >
                <?php echo esc_html($button_label); ?>
            </button>
        </div>

    <?php elseif ($form_type === 'special'): ?>
        <!-- Special Form: Direct booking button without dates -->
        <div class="mlb-special-form-wrapper">
            <div class="mlb-special-info">
                <?php if (!empty($args['special_name'])): ?>
                    <h3 class="mlb-special-name"><?php echo esc_html($args['special_name']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($args['hotel_name'])): ?>
                    <p class="mlb-hotel-name"><?php echo esc_html($args['hotel_name']); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Direct booking without dates -->
            <button 
                type="button"
                class="mlb-button mlb-button-primary mlb-book-special-btn"
                data-mlb-book-special
                data-hotel-id="<?php echo esc_attr($hotel_id); ?>"
                data-rate-id="<?php echo esc_attr($special_id); ?>"
            >
                <?php echo esc_html($button_label); ?>
            </button>
        </div>

    <?php endif; ?>

</div>
