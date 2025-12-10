<?php
/**
 * Rooms & Specials tabs partial
 * Expects $rooms and $specials arrays and the standard form inputs.
 */
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="mlb-section-card mlb-tabs-card">
    <div class="mlb-tabs">
        <div class="mlb-tabs-nav" role="tablist" aria-label="Rooms and Specials">
                <button type="button" class="mlb-tab-btn active" data-tab="rooms" role="tab" aria-selected="true"> 
				<i class="fas fa-bed"></i>
                <?php esc_html_e('Rooms', 'mylighthouse-booker'); ?>
            </button>
            <button type="button" class="mlb-tab-btn" data-tab="specials" role="tab" aria-selected="false">
                <i class="fas fa-star"></i>
                <?php esc_html_e('Specials', 'mylighthouse-booker'); ?>
            </button>
        </div>

        <div class="mlb-tabs-panels-with-sidebar">
            <div class="mlb-tabs-panels">
                <div class="mlb-tab-panel mlb-tab-rooms" data-tab-panel="rooms" role="tabpanel">
                <div id="rooms-repeater">
                    <?php if (!empty($rooms)) : ?>
                        <?php foreach ($rooms as $index => $room) : ?>
                            <?php include __DIR__ . '/room-card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mlb-tab-actions">
                    <button type="button" class="mlb-btn mlb-btn-primary" id="add-room">
						<span class="dashicons mlb-dashicon dashicons-plus" aria-hidden="true"></span>
                        <?php esc_html_e('Add Room', 'mylighthouse-booker'); ?>
                    </button>
                </div>

                <!-- Room Template for JS cloning -->
                <template id="room-template">
                    <?php 
                    $index = '{{INDEX}}';
                    $room = array('id' => 0, 'name' => '', 'room_id' => '');
                    include __DIR__ . '/room-card.php'; 
                    ?>
                </template>
                
                <!-- Modal for adding/editing a single room/special -->
                <div id="mlb-item-modal" class="mlb-modal" hidden>
                    <div class="mlb-modal-backdrop" data-dismiss="modal"></div>
                    <div class="mlb-modal-panel" role="dialog" aria-modal="true" aria-labelledby="mlb-modal-title">
                        <div class="mlb-modal-header">
                            <h3 id="mlb-modal-title"><?php esc_html_e('Item', 'mylighthouse-booker'); ?></h3>
                        </div>
                        <div class="mlb-modal-body">
                            <input type="hidden" id="mlb-modal-mode" value="add" />
                            <input type="hidden" id="mlb-modal-target" value="rooms" />
                            <div class="mlb-field">
                                <label for="mlb-modal-name"><?php esc_html_e('Name', 'mylighthouse-booker'); ?></label>
                                <input id="mlb-modal-name" class="regular-text mlb-input" />
                            </div>
                            <div class="mlb-field">
                                <label for="mlb-modal-extid"><?php esc_html_e('External ID', 'mylighthouse-booker'); ?></label>
                                <input id="mlb-modal-extid" class="regular-text mlb-input" />
                            </div>
                        </div>
                        <div class="mlb-modal-footer">
                            <button type="button" class="mlb-btn mlb-btn-secondary" data-dismiss="modal"><?php esc_html_e('Cancel', 'mylighthouse-booker'); ?></button>
                            <button type="button" class="mlb-btn mlb-btn-primary" id="mlb-modal-save"><?php esc_html_e('Save', 'mylighthouse-booker'); ?></button>
                        </div>
                    </div>
                </div>
                </div>

                <div class="mlb-tab-panel mlb-tab-specials" data-tab-panel="specials" role="tabpanel" hidden>
                    <div id="specials-repeater">
                        <?php if (!empty($specials)) : ?>
                            <?php foreach ($specials as $index => $special) : ?>
                                <?php include __DIR__ . '/special-card.php'; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="mlb-tab-actions">
                        <button type="button" class="mlb-btn mlb-btn-primary" id="add-special">
						<span class="dashicons mlb-dashicon dashicons-plus" aria-hidden="true"></span>
                            <?php esc_html_e('Add Special', 'mylighthouse-booker'); ?>
                        </button>
                    </div>

                    <!-- Special Template for JS cloning -->
                    <template id="special-template">
                        <?php 
                        $index = '{{INDEX}}';
                        $special = array('id' => 0, 'name' => '', 'special_id' => '');
                        include __DIR__ . '/special-card.php'; 
                        ?>
                    </template>
                </div>
            </div>

        </div>

            
        </div>
    </div>
</div>
