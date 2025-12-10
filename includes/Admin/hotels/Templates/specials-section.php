<?php
/**
 * Specials section partial
 * @var array $specials
 */
if (! defined('ABSPATH')) {
    exit;
}
?>

<div class="mlb-specials-section">
    <div id="specials-repeater">
        <?php if (! empty($specials)) : foreach ((array) $specials as $index => $special) : ?>
            <?php include __DIR__ . '/special-card.php'; ?>
        <?php endforeach; endif; ?>
    </div>

    <button type="button" class="mlb-btn mlb-btn-primary" id="add-special">
        <span class="dashicons mlb-dashicon dashicons-plus"></span>
        <?php esc_html_e('Add Special', 'mylighthouse-booker'); ?>
    </button>

    <template id="special-template">
        <?php
        // blank special used by JS cloning
        $special = array('id' => 0, 'name' => '', 'special_id' => '');
        include __DIR__ . '/special-card.php';
        ?>
    </template>
</div>
