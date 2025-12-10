<?php
if (! defined('ABSPATH')) {
    exit;
}

class Mylighthouse_Booker_Admin_Hotels
{
    /**
     * Render the hotels list by delegating to the new hotels page templates.
     */
    public function render_list()
    {
        // If the page class exists use it, otherwise include template directly
        if (class_exists('Mylighthouse_Booker_Admin_Hotels_Page')) {
            $page = new Mylighthouse_Booker_Admin_Hotels_Page();
            $page->render_page();
            return;
        }

        // Fallback: include template directly
        $hotels = array();
        if (class_exists('Mylighthouse_Booker_Hotel')) {
            $hotels = Mylighthouse_Booker_Hotel::get_all_with_rooms();
        }
        include MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Admin/hotels/Templates/hotels-list.php';
    }

    /**
     * Render the hotel edit screen. Accepts 'new' or an ID.
     *
     * @param string|int $id
     */
    public function render_edit($id = 'new')
    {
        $edit_id = $id;
        $hotel = null;
        if ($id !== 'new' && class_exists('Mylighthouse_Booker_Hotel')) {
            $hotel = Mylighthouse_Booker_Hotel::get_with_rooms(intval($id));
        }

        // Prefer template-based renderer
        $template = MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Admin/hotels/Templates/hotels-edit.php';
        if (file_exists($template)) {
            include $template;
            return;
        }

        // Minimal fallback form
        $is_new = ($id === 'new');
        $nonce = wp_create_nonce('mlb_save_hotel');
        ?>
        <div class="wrap">
            <h1><?php echo $is_new ? esc_html__('Add Hotel', 'mylighthouse-booker') : esc_html__('Edit Hotel', 'mylighthouse-booker'); ?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                <input type="hidden" name="action" value="mlb_save_hotel" />
                <input type="hidden" name="hotel_id" value="<?php echo $is_new ? 0 : intval($id); ?>" />
                <input type="hidden" name="mlb_hotel_nonce" value="<?php echo esc_attr($nonce); ?>" />
                <p><?php esc_html_e('Basic editor unavailable; template missing.', 'mylighthouse-booker'); ?></p>
            </form>
        </div>
        <?php
    }
}