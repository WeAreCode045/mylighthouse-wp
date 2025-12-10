<?php
/**
 * Room Card Template
 *
 * @package Mylighthouse_Booker
 * @var int $index Room index
 * @var array $room Room data
 */

if (! defined('ABSPATH')) {
	exit;
}

$room_db_id = $room['id'] ?? 0;
$room_name = $room['name'] ?? '';
$room_external_id = $room['room_id'] ?? '';
?>

<div class="mlb-list-item mlb-repeater-item mlb-room-card" data-index="<?php echo esc_attr($index); ?>">
	<div class="mlb-item-summary">
		<span class="dashicons mlb-dashicon dashicons-admin-home" aria-hidden="true"></span>
		<strong class="mlb-item-name-text"><?php echo esc_html($room_name); ?></strong>
		<span class="mlb-item-id-text"><?php echo sprintf(esc_html__('ID: %s', 'mylighthouse-booker'), esc_html($room_external_id)); ?></span>
	</div>

	<div class="mlb-item-actions">
		<button type="button" class="mlb-edit-room-btn mlb-action-btn mlb-action-btn--primary" title="<?php esc_attr_e('Edit room', 'mylighthouse-booker'); ?>">
			<span class="dashicons mlb-dashicon dashicons-edit" aria-hidden="true"></span>
		</button>
		<button type="button" class="mlb-remove-room-btn mlb-remove-hotel mlb-action-btn mlb-action-btn--danger" title="<?php esc_attr_e('Delete room', 'mylighthouse-booker'); ?>" aria-label="<?php esc_attr_e('Delete room', 'mylighthouse-booker'); ?>">
			<span class="dashicons mlb-dashicon dashicons-trash" aria-hidden="true"></span>
		</button>
	</div>

	<input type="hidden" name="rooms[<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr($room_db_id); ?>" />
	<input type="hidden" class="mlb-input-name" name="rooms[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($room_name); ?>" />
	<input type="hidden" class="mlb-input-extid" name="rooms[<?php echo esc_attr($index); ?>][room_id]" value="<?php echo esc_attr($room_external_id); ?>" />
</div>
