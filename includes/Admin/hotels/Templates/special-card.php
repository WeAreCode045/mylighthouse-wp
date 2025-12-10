<?php
/**
 * Special Card Template
 *
 * @package Mylighthouse_Booker
 * @var int $index Special index
 * @var array $special Special data
 */

if (! defined('ABSPATH')) {
    exit;
}

$special_db_id = $special['id'] ?? 0;
$special_name = $special['name'] ?? '';
$special_external_id = $special['special_id'] ?? '';
?>

<div class="mlb-list-item mlb-repeater-item mlb-special-card" data-index="<?php echo esc_attr($index); ?>">
    <div class="mlb-item-summary">
        <span class="dashicons mlb-dashicon dashicons-star-filled" aria-hidden="true"></span>
        <strong class="mlb-item-name-text"><?php echo esc_html($special_name); ?></strong>
        <span class="mlb-item-id-text"><?php echo sprintf(esc_html__('ID: %s', 'mylighthouse-booker'), esc_html($special_external_id)); ?></span>
    </div>

    <div class="mlb-item-actions">
        <button type="button" class="mlb-edit-special-btn mlb-action-btn mlb-action-btn--primary" title="<?php esc_attr_e('Edit special', 'mylighthouse-booker'); ?>">
            <span class="dashicons mlb-dashicon dashicons-edit" aria-hidden="true"></span>
        </button>
        <button type="button" class="mlb-remove-special-btn mlb-remove-hotel mlb-action-btn mlb-action-btn--danger" title="<?php esc_attr_e('Delete special', 'mylighthouse-booker'); ?>" aria-label="<?php esc_attr_e('Delete special', 'mylighthouse-booker'); ?>">
            <span class="dashicons mlb-dashicon dashicons-trash" aria-hidden="true"></span>
        </button>
    </div>

    <input type="hidden" name="specials[<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr($special_db_id); ?>" />
    <input type="hidden" class="mlb-input-name" name="specials[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($special_name); ?>" />
    <input type="hidden" class="mlb-input-extid" name="specials[<?php echo esc_attr($index); ?>][special_id]" value="<?php echo esc_attr($special_external_id); ?>" />
</div>
