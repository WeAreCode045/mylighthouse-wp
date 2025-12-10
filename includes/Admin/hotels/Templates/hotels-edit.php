<?php
/**
 * Hotel Edit Form Template
 *
 * @package Mylighthouse_Booker
 * @var array|null $hotel Hotel data (null for new hotel)
 * @var string|int $edit_id 'new' or numeric id
 */

if (! defined('ABSPATH')) {
    exit;
}

$is_new = (isset($edit_id) && $edit_id === 'new') || empty($hotel);
$hotel_id = $is_new ? 0 : (isset($hotel['id']) ? $hotel['id'] : (isset($hotel->id) ? $hotel->id : 0));
$name = $is_new ? '' : (isset($hotel['name']) ? $hotel['name'] : (isset($hotel->name) ? $hotel->name : ''));
$external_id = $is_new ? '' : (isset($hotel['hotel_id']) ? $hotel['hotel_id'] : (isset($hotel->hotel_id) ? $hotel->hotel_id : ''));
$rooms = $is_new ? array() : (isset($hotel['rooms']) ? $hotel['rooms'] : (isset($hotel->rooms) ? $hotel->rooms : array()));
$specials = $is_new ? array() : (isset($hotel['specials']) ? $hotel['specials'] : (isset($hotel->specials) ? $hotel->specials : array()));

 /* translators: %s is the hotel name shown in the admin title when editing a hotel. */
 $page_title = $is_new ? __('Add New Hotel', 'mylighthouse-booker') : sprintf(__('Edit: %s', 'mylighthouse-booker'), $name);
$back_url = admin_url('admin.php?page=mylighthouse-booker&content=hotels');
?>

<div class="mlb-hotels-layout">
    <div class="mlb-hotels-main">
        <div class="mlb-hotels-header">
            <h2 class="mlb-section-title">
                <a href="<?php echo esc_url($back_url); ?>" class="mlb-back-link">
                    <span class="dashicons mlb-dashicon dashicons-arrow-left-alt2"></span>
                </a>
                <span class="dashicons mlb-dashicon dashicons-building"></span>
                <?php echo esc_html($page_title); ?>
            </h2>
        </div>

        <?php if (defined('WP_DEBUG') && WP_DEBUG === true) :
            $rooms_count = is_array($rooms) ? count($rooms) : 0;
            $specials_count = is_array($specials) ? count($specials) : 0;
        ?>
            <div class="mlb-diagnostic" style="margin-bottom:10px;padding:8px;border-left:4px solid #ccc;background:#fff">
                <?php /* translators: %s is the internal WP hotel id (numeric). */ ?>
                <strong><?php echo esc_html(sprintf(__('Hotel ID: %s', 'mylighthouse-booker'), $hotel_id)); ?></strong>
                <?php /* translators: First %1$d is the number of rooms, second %2$d is the number of specials for this hotel. Use ordered placeholders if reordering is required for your language. */ ?>
                <div style="font-size:12px;color:#666"><?php echo esc_html(sprintf(__('Rooms: %1$d — Specials: %2$d', 'mylighthouse-booker'), $rooms_count, $specials_count)); ?></div>
            </div>
        <?php endif; ?>
        <div class="mlb-hotels-content">
            <!-- Rooms & Specials: horizontal tabs with right sidebar for hotel summary -->
            <div class="mlb-section-row mlb-section-row--tabs">
                <form method="post" id="mlb-hotel-form" data-hotel-id="<?php echo esc_attr($hotel_id); ?>">
                    <?php wp_nonce_field('mlb_save_hotel', 'mlb_hotel_nonce'); ?>
                    <input type="hidden" name="action" value="mlb_save_hotel" />
                    <input type="hidden" name="hotel_id" value="<?php echo esc_attr($hotel_id); ?>" />

                    <?php include __DIR__ . '/rooms-tabs.php'; ?>
                </form>

                <?php include __DIR__ . '/room-edit-sidebar.php'; ?>

            </div>
        </div>
    </div>
</div>
