<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Mylighthouse_Booker_Admin_Settings {
    /**
     * Render a lightweight settings fragment for the dashboard loader.
     * The wrapper class matches the fragment extractor mapping: `mlb-admin-sections`.
     */
    public function render_page() {
        $sub = isset($_GET['content']) ? sanitize_text_field($_GET['content']) : '';
        if (strpos($sub, 'settings-') === 0) {
            $sub = substr($sub, strlen('settings-')) ?: 'general';
        } else {
            $sub = 'general';
        }
        ?>
        <div class="mlb-admin-sections">
            <div class="mlb-section-card">
                <nav class="mlb-settings-subnav">
                    <button type="button" class="mlb-subnav-link active" data-content="settings-general"><?php esc_html_e('General', 'mylighthouse-booker'); ?></button>
                </nav>

                <h2 class="mlb-section-title"><?php esc_html_e('General Settings', 'mylighthouse-booker'); ?></h2>
                <p><?php esc_html_e('Configure plugin options here.', 'mylighthouse-booker'); ?></p>
                <?php
                // Load current options
                $booking_page = get_option('mlb_booking_page_url', '');
                $display_mode = get_option('mlb_display_mode', 'modal');
                $spinner_image_url = get_option('mlb_spinner_image_url', '');
                if (empty($spinner_image_url)) {
                    $legacy_spinner_id = get_option('mlb_spinner_image_id', '');
                    if (!empty($legacy_spinner_id)) {
                        $fallback = wp_get_attachment_image_url($legacy_spinner_id, 'full');
                        if ($fallback) {
                            $spinner_image_url = $fallback;
                        }
                    }
                }
                ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                    <?php if ($sub === 'general') : ?>
                        <?php wp_nonce_field('mlb_save_admin_settings', 'mlb_admin_settings_nonce'); ?>
                        <input type="hidden" name="action" value="mlb_save_admin_settings" />

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Booking Redirect', 'mylighthouse-booker'); ?></th>
                                <td>
                                    <p class="description" style="margin-top:0;">
                                        <strong><?php esc_html_e('Direct Redirect Mode Active', 'mylighthouse-booker'); ?></strong><br>
                                        <?php esc_html_e('The plugin now redirects directly to the MyLighthouse booking engine (bookingengine.mylighthouse.com) instead of using an iframe. This provides a better user experience with full functionality.', 'mylighthouse-booker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Spinner Background', 'mylighthouse-booker'); ?></th>
                                <td>
                                    <input type="url" name="mlb_spinner_image_url" id="mlb_spinner_image_url" value="<?php echo esc_attr($spinner_image_url); ?>" class="regular-text mlb-input" placeholder="https://example.com/spinner-background.jpg" />
                                    <?php if (!empty($spinner_image_url)) : ?>
                                        <div style="margin-top:10px;">
                                            <img src="<?php echo esc_url($spinner_image_url); ?>" alt="<?php esc_attr_e('Spinner preview', 'mylighthouse-booker'); ?>" style="max-width:200px;height:auto;border:1px solid #ccd0d4;padding:4px;border-radius:4px;" />
                                        </div>
                                    <?php endif; ?>
                                    <p class="description"><?php esc_html_e('Enter the full URL to the image that should appear behind the spinner in the booking modal.', 'mylighthouse-booker'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <p><button class="mlb-btn mlb-btn-primary" type="submit"><?php esc_html_e('Save General Settings', 'mylighthouse-booker'); ?></button></p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php
    }
}
?>