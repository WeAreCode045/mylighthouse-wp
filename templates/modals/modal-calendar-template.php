<?php
/**
 * Calendar Modal Template Wrapper
 *
 * Wraps the existing calendar modal markup in a <template> so JS may clone it.
 */
if (! defined('ABSPATH')) {
    exit;
}
?>

<template id="mlb-modal-template-calendar">
<?php
// Reuse existing calendar-modal.php markup if present, otherwise attempt to
// output a minimal calendar structure.
$calendar_file = __DIR__ . '/calendar-modal.php';
if ( file_exists( $calendar_file ) ) {
    // Load the file contents and output inside the template.
    include $calendar_file;
} else {
    ?>
    <div class="mlb-calendar-modal-overlay" data-form-id="{{FORM_ID}}">
        <div class="mlb-calendar-modal-container">
            <button type="button" class="mlb-calendar-modal-close" aria-label="<?php echo esc_attr__( 'Close calendar', 'mylighthouse-booker' ); ?>">&times;</button>
            <div class="mlb-modal-content-wrapper">
                <div class="mlb-modal-calendar"></div>
                <div class="mlb-modal-right-column">
                    <div class="mlb-booking-details">
                        <h3><?php echo esc_html__( 'Booking Details', 'mylighthouse-booker' ); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
</template>
