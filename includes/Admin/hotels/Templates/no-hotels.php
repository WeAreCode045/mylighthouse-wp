<?php
/**
 * No hotels template
 *
 * @package Mylighthouse_Booker
 */

if (! defined('ABSPATH')) {
    exit;
}

?>
<div class="notice notice-info"><p><?php esc_html_e('No hotels found', 'mylighthouse-booker'); ?></p></div>
<?php
/**
 * No Hotels Empty State Template
 *
 * @package Mylighthouse_Booker
 */

if (! defined('ABSPATH')) {
	exit;
}
?>

<div class="mlb-no-hotels">
	<span class="dashicons mlb-dashicon dashicons-building"></span>
	<h3><?php esc_html_e('No hotels configured yet', 'mylighthouse-booker'); ?></h3>
	<p><?php esc_html_e('Add your first hotel to get started with the booking system.', 'mylighthouse-booker'); ?></p>
</div>
