<?php

/**
 * Shortcode for iframe target
 *
 * @package StandaloneTech
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Shortcode_Iframe_Target
 */
class Mylighthouse_Booker_Shortcode_Iframe_Target
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		add_action('init', array($this, 'register_shortcodes'));
	}

	/**
	 * Register shortcodes
	 */
	public function register_shortcodes()
	{
		add_shortcode('lighthouse_booking_results', array($this, 'render_shortcode'));
	}

	/**
	 * Render the iframe target shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output
	 */
	public function render_shortcode($atts)
	{
		// Enqueue iframe assets.
		wp_enqueue_script('mylighthouse-booker-iframe');
		if (! wp_script_is('mylighthouse-booker-spinner', 'enqueued')) {
			wp_enqueue_script('mylighthouse-booker-spinner');
		}

		$atts = shortcode_atts(
			array(
				'width'  => '100%',
				'height' => '90vh',
			),
			$atts,
			'lighthouse_booking_results'
		);

		$width  = esc_attr($atts['width']);
		$height = esc_attr($atts['height']);

		$spinner_image_url = esc_url(get_option('mlb_spinner_image_url', ''));
		if (empty($spinner_image_url)) {
			$spinner_image_id = intval(get_option('mlb_spinner_image_id', 0));
			if ($spinner_image_id > 0) {
				$spinner_src = wp_get_attachment_image_url($spinner_image_id, 'full');
				if ($spinner_src) {
					$spinner_image_url = esc_url($spinner_src);
				}
			}
		}

		// Get URL parameters.
		$arrival    = isset($_GET['Arrival']) ? sanitize_text_field(wp_unslash($_GET['Arrival'])) : '';
		$departure  = isset($_GET['Departure']) ? sanitize_text_field(wp_unslash($_GET['Departure'])) : '';
		$hotel_id   = isset($_GET['hotel_id']) ? sanitize_text_field(wp_unslash($_GET['hotel_id'])) : '';
		$room_id    = isset($_GET['room']) ? sanitize_text_field(wp_unslash($_GET['room'])) : '';
		$rate_param = '';
		if (isset($_GET['Rate'])) {
			$rate_param = sanitize_text_field(wp_unslash($_GET['Rate']));
		} elseif (isset($_GET['rate'])) {
			$rate_param = sanitize_text_field(wp_unslash($_GET['rate']));
		} elseif (isset($_GET['special_id'])) {
			$rate_param = sanitize_text_field(wp_unslash($_GET['special_id']));
		}

		ob_start();
?>
		<div id="mlb-iframe-container" class="mlb-iframe-container" style="position: relative; width: <?php echo esc_attr($width); ?>; height: <?php echo esc_attr($height); ?>;" data-hotel-id="<?php echo esc_attr($hotel_id); ?>" data-rate-id="<?php echo esc_attr($rate_param); ?>" data-arrival="<?php echo esc_attr($arrival); ?>" data-departure="<?php echo esc_attr($departure); ?>">
			<div id="mlb-iframe-loading" class="mlb-iframe-loading" role="status" aria-live="polite">
				<div class="mlb-spinner-box">
					<div class="mlb-spinner-image"<?php
						if ($spinner_image_url) {
							printf(' data-bg-image="%1$s" style="background-image:url(%1$s);"', esc_attr($spinner_image_url));
						}
					?> aria-hidden="true"></div>
				</div>
			</div>
			<iframe
				id="mylighthouse-booking-iframe"
				width="100%"
				height="90%"
				frameborder="0"
				allowfullscreen
				allow="payment; geolocation"
				class="mlb-iframe"
				loading="eager"
				style="border: none;">
			</iframe>
		</div>
<?php
		return ob_get_clean();
	}

	/**
	 * Build iframe src URL
	 *
	 * @param string $arrival   Arrival date.
	 * @param string $departure Departure date.
	 * @param string $hotel_id  Hotel ID.
	 * @return string
	 */
	private function build_iframe_src($arrival, $departure, $hotel_id)
	{
		$base_url = 'https://bookingengine.mylighthouse.com/';

		$params = array();

		if ($arrival) {
			$params['Arrival'] = $arrival;
		}

		if ($departure) {
			$params['Departure'] = $departure;
		}

		if ($hotel_id) {
			$params['hotel_id'] = $hotel_id;
		}

		if (! empty($params)) {
			$base_url .= '?' . http_build_query($params);
		}

		return $base_url;
	}
}
