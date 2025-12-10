<?php
/**
 * Hotel information section partial
 * Expects `$hotel`, `$is_new`, `$hotel_id`, `$name`, `$external_id` to be available.
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="mlb-section-card">
    <div class="mlb-section-title"><h3><?php echo $is_new ? esc_html__('Hotel Details', 'mylighthouse-booker') : esc_html__('Hotel Details', 'mylighthouse-booker'); ?></h3></div>
    <table class="form-table">
        <tr>
            <th><label for="hotel_name"><?php esc_html_e('Hotel Name','mylighthouse-booker'); ?></label></th>
            <td><input type="text" id="hotel_name" name="hotel_name" value="<?php echo esc_attr($name); ?>" class="regular-text mlb-input" required /></td>
        </tr>
        <tr>
            <th><label for="hotel_external_id"><?php esc_html_e('External ID','mylighthouse-booker'); ?></label></th>
            <td><input type="text" id="hotel_external_id" name="hotel_external_id" value="<?php echo esc_attr($external_id); ?>" class="regular-text mlb-input" required /></td>
        </tr>
    </table>
</div>

