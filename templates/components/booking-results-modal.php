<?php
/**
 * Booking Results Modal Component
 * Shows booking engine results in iframe
 *
 * @package Mylighthouse_Booker
 */

if (!defined('ABSPATH')) {
    exit;
}

$spinner_url = get_option('mlb_spinner_image_url', '');
?>

<template id="mlb-booking-modal-template">
    <div class="mlb-modal-overlay">
        <div class="mlb-modal mlb-booking-modal" role="dialog" aria-modal="true" aria-labelledby="mlb-booking-modal-title">
            <button type="button" class="mlb-modal-close" aria-label="<?php esc_attr_e('Close booking results', 'mylighthouse-booker'); ?>">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            
            <div class="mlb-modal-loader">
                <div class="mlb-spinner-box">
                    <?php if ($spinner_url): ?>
                        <div class="mlb-spinner-image" style="background-image: url('<?php echo esc_url($spinner_url); ?>');" aria-hidden="true"></div>
                    <?php else: ?>
                        <div class="mlb-spinner" aria-hidden="true"></div>
                    <?php endif; ?>
                    <p class="mlb-spinner-text">
                        <?php esc_html_e('Loading booking options...', 'mylighthouse-booker'); ?>
                    </p>
                </div>
            </div>
            
            <iframe 
                class="mlb-booking-iframe" 
                title="<?php esc_attr_e('Booking results', 'mylighthouse-booker'); ?>"
                allow="payment; geolocation"
                loading="eager"
                style="display: none;"
            ></iframe>
        </div>
    </div>
</template>
