<?php
/**
 * Room Edit Sidebar Partial
 * Provides the right-hand quick-edit sidebar for hotel summary (name / external id)
 * Expects `$name` and `$external_id` variables to be available in scope.
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<aside class="mlb-rooms-sidebar" aria-label="Hotel summary">
    <div class="mlb-sidebar-card">
        <h4><?php esc_html_e('Hotel', 'mylighthouse-booker'); ?></h4>
        <div class="mlb-sidebar-field">
            <label><?php esc_html_e('Name', 'mylighthouse-booker'); ?></label>
            <div class="mlb-sidebar-value" data-field="name">
                <span class="mlb-sidebar-text"><?php echo esc_html($name); ?></span>
                <button type="button" class="mlb-inline-edit mlb-btn mlb-btn-sm" data-edit="name" title="<?php esc_attr_e('Edit name', 'mylighthouse-booker'); ?>">
                    <span class="dashicons mlb-dashicon dashicons-edit" aria-hidden="true"></span>
                </button>
                <button type="button" class="mlb-inline-save mlb-btn mlb-btn-primary mlb-btn-sm" data-save="name" title="<?php esc_attr_e('Save', 'mylighthouse-booker'); ?>">
                    <span class="dashicons mlb-dashicon dashicons-yes" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        <div class="mlb-sidebar-field">
            <label><?php esc_html_e('External ID', 'mylighthouse-booker'); ?></label>
            <div class="mlb-sidebar-value" data-field="external_id">
                <span class="mlb-sidebar-text"><?php echo esc_html($external_id); ?></span>
                <button type="button" class="mlb-inline-edit mlb-btn mlb-btn-sm" data-edit="external_id" title="<?php esc_attr_e('Edit external id', 'mylighthouse-booker'); ?>">
                    <span class="dashicons mlb-dashicon dashicons-edit" aria-hidden="true"></span>
                </button>
                <button type="button" class="mlb-inline-save mlb-btn mlb-btn-primary mlb-btn-sm" data-save="external_id" title="<?php esc_attr_e('Save', 'mylighthouse-booker'); ?>">
                    <span class="dashicons mlb-dashicon dashicons-yes" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</aside>
