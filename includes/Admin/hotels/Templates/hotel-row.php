<?php
/**
 * Hotel Row Template
 *
 * @package Mylighthouse_Booker
 * @var array $hotel Hotel data with rooms
 */

if (! defined('ABSPATH')) {
    exit;
}

$hotel_id = isset($hotel['id']) ? $hotel['id'] : (isset($hotel->id) ? $hotel->id : 0);
$name = isset($hotel['name']) ? $hotel['name'] : (isset($hotel->name) ? $hotel->name : '');
$external_id = isset($hotel['hotel_id']) ? $hotel['hotel_id'] : (isset($hotel->hotel_id) ? $hotel->hotel_id : '');
$room_count = isset($hotel['rooms']) ? count((array) $hotel['rooms']) : 0;

$edit_url = admin_url('admin.php?page=mylighthouse-booker&content=hotels-edit-' . $hotel_id);
$delete_url = wp_nonce_url(
    admin_url('admin.php?page=mylighthouse-booker&action=delete-hotel&hotel=' . $hotel_id),
    'delete-hotel-' . $hotel_id
);
?>

<?php
// Prefer emitting a real DB id as data-id when available. If not, fall back
// to the external supplier `hotel_id` so client-side ordering can send a
// meaningful identifier instead of a literal 0 which previously caused
// the server to receive many zeros and fail to resolve them.
$data_id_attr = '';
if (! empty($hotel_id) && intval($hotel_id) > 0) {
    $data_id_attr = esc_attr($hotel_id);
} elseif (! empty($external_id)) {
    $data_id_attr = esc_attr($external_id);
}

$hotel_data_attrs = array();
if ($data_id_attr) {
    $hotel_data_attrs[] = 'data-id="' . $data_id_attr . '"';
}
if (! empty($hotel_id)) {
    $hotel_data_attrs[] = 'data-hotel-id="' . esc_attr($hotel_id) . '"';
}
// Expose the edit fragment key so JS can open the editor without parsing URLs.
$content_attr = ! empty($hotel_id) ? 'hotels-edit-' . intval($hotel_id) : 'hotels-edit-new';
$hotel_data_attrs[] = 'data-content="' . esc_attr($content_attr) . '"';
?>
<div class="mlb-hotel-item" <?php echo implode(' ', $hotel_data_attrs); ?>>
    <div class="mlb-hotel-header" data-external-id="<?php echo esc_attr($external_id); ?>">
        <div class="mlb-hotel-order-controls" role="group" aria-label="<?php esc_attr_e('Change hotel order', 'mylighthouse-booker'); ?>">
            <button type="button" class="mlb-order-btn mlb-move-up" title="<?php esc_attr_e('Move hotel up', 'mylighthouse-booker'); ?>" aria-label="<?php esc_attr_e('Move hotel up', 'mylighthouse-booker'); ?>">
                <span class="dashicons mlb-dashicon dashicons-arrow-up-alt2" aria-hidden="true"></span>
            </button>
            <button type="button" class="mlb-order-btn mlb-move-down" title="<?php esc_attr_e('Move hotel down', 'mylighthouse-booker'); ?>" aria-label="<?php esc_attr_e('Move hotel down', 'mylighthouse-booker'); ?>">
                <span class="dashicons mlb-dashicon dashicons-arrow-down-alt2" aria-hidden="true"></span>
            </button>
        </div>
        <div class="mlb-hotel-summary">
            <span class="dashicons mlb-dashicon dashicons-building"></span>
            <strong class="mlb-hotel-name-text"><?php echo esc_html($name); ?></strong>
            <span class="mlb-hotel-id-text">
                <?php /* translators: %s is the external hotel identifier (hotel ID) from the supplier/API. */ ?>
                <?php echo sprintf(esc_html__('ID: %s', 'mylighthouse-booker'), esc_html($external_id)); ?>
            </span>
            <?php if ($room_count > 0) : ?>
                <span class="mlb-hotel-room-count">
                    <?php /* translators: %d is the number of rooms for this hotel. */ ?>
                    <?php echo sprintf(esc_html(_n('%d room', '%d rooms', $room_count, 'mylighthouse-booker')), $room_count); ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="mlb-hotel-actions">
            <a href="<?php echo esc_url($edit_url); ?>" class="mlb-edit-hotel mlb-action-btn mlb-action-btn--primary" title="<?php esc_attr_e('Edit', 'mylighthouse-booker'); ?>">
                <span class="dashicons mlb-dashicon dashicons-edit"></span>
            </a>
            <a href="<?php echo esc_url($delete_url); ?>" class="mlb-ajax-delete-hotel mlb-remove-hotel mlb-action-btn mlb-action-btn--danger" data-hotel-name="<?php echo esc_attr($name); ?>" data-hotel-id="<?php echo esc_attr($hotel_id); ?>" data-delete-nonce="<?php echo esc_attr(wp_create_nonce('delete-hotel-' . $hotel_id)); ?>" title="<?php esc_attr_e('Delete', 'mylighthouse-booker'); ?>">
                <span class="dashicons mlb-dashicon dashicons-trash"></span>
            </a>
        </div>
    </div>
</div>

