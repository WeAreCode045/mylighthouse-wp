<?php
/**
 * Hotels List View Template
 *
 * @package Mylighthouse_Booker
 * @var array $hotels Array of hotels with rooms
 */

if (! defined('ABSPATH')) {
	exit;
}

?>

<div class="mlb-hotels-wrap mlb-hotels-layout">
	<div class="mlb-hotels-main">
		<div class="mlb-hotels-header">
			<h2 class="mlb-section-title">
				<span class="dashicons mlb-dashicon dashicons-building"></span>
				<?php esc_html_e('Hotel Management', 'mylighthouse-booker'); ?>
			</h2>
			<button type="button" id="mlb-open-add-hotel" class="mlb-btn mlb-btn-primary">
				<span class="dashicons mlb-dashicon dashicons-plus"></span>
				<?php esc_html_e('Add Hotel', 'mylighthouse-booker'); ?>
			</button>

			<!-- Add Hotel Modal -->
			<div id="mlb-add-hotel-modal" class="mlb-modal" hidden>
				<div class="mlb-modal-backdrop" data-dismiss="modal"></div>
				<div class="mlb-modal-panel" role="dialog" aria-modal="true" aria-labelledby="mlb-add-hotel-title">
					<div class="mlb-modal-header">
						<h3 id="mlb-add-hotel-title"><?php esc_html_e('Add Hotel', 'mylighthouse-booker'); ?></h3>
					</div>
					<div class="mlb-modal-body">
						<div class="mlb-field">
							<label for="mlb-add-hotel-name"><?php esc_html_e('Hotel Name', 'mylighthouse-booker'); ?></label>
							<input id="mlb-add-hotel-name" class="regular-text mlb-input" />
						</div>
						<div class="mlb-field">
							<label for="mlb-add-hotel-extid"><?php esc_html_e('External ID', 'mylighthouse-booker'); ?></label>
							<input id="mlb-add-hotel-extid" class="regular-text mlb-input" />
						</div>
					</div>
					<div class="mlb-modal-footer">
						<button type="button" class="mlb-btn mlb-btn-secondary" data-dismiss="modal"><?php esc_html_e('Cancel', 'mylighthouse-booker'); ?></button>
						<button type="button" class="mlb-btn mlb-btn-primary" id="mlb-add-hotel-save"><?php esc_html_e('Add Hotel', 'mylighthouse-booker'); ?></button>
					</div>
				</div>
			</div>
		</div>

		<div class="mlb-section-card">
			<?php
			// Visible diagnostic for debugging: show count and IDs when WP_DEBUG is enabled
			if (defined('WP_DEBUG') && WP_DEBUG === true) {
				$diag_count = is_array($hotels) ? count($hotels) : 0;
				$diag_ids = array();
				if (is_array($hotels)) {
					foreach ($hotels as $h) {
						if (is_array($h) && isset($h['id'])) $diag_ids[] = $h['id'];
						elseif (is_object($h) && isset($h->id)) $diag_ids[] = $h->id;
					}
				}
				?>
				<div class="mlb-diagnostic" style="margin-bottom:10px;padding:8px;border-left:4px solid #ccc;background:#fff">
					<?php /* translators: %d is the total number of hotels in the site (for diagnostic display). */ ?>
					<strong><?php echo esc_html(sprintf(__('Hotels count: %d', 'mylighthouse-booker'), $diag_count)); ?></strong>
					<?php if (!empty($diag_ids)) : ?>
						<div style="font-size:12px;color:#666">IDs: <?php echo esc_html(implode(', ', $diag_ids)); ?></div>
					<?php endif; ?>
				</div>
			<?php }
			?>
			<div id="hotels-container">
				<?php if (empty($hotels)) : ?>
					<?php include __DIR__ . '/no-hotels.php'; ?>
				<?php else : ?>
					<?php foreach ($hotels as $hotel) : ?>
						<?php include __DIR__ . '/hotel-row.php'; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
