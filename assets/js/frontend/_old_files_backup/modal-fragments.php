<?php
/**
 * Modal fragments and small templates
 *
 * Contains small <template> fragments reused by frontend JS so markup and
 * translatable strings live in PHP templates.
 */
if (! defined('ABSPATH')) {
    exit;
}
?>

<template id="mlb-modal-close-button">
    <button type="button" class="mlb-modal-close" aria-label="<?php echo esc_attr( 'Close booking results' ); ?>">&times;</button>
</template>

<template id="mlb-modal-spinner-box">
    <div class="mlb-spinner-box">
        <div class="mlb-spinner" aria-hidden="true"></div>
    </div>
</template>

<template id="mlb-icon-arrow-down">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
</template>

<template id="mlb-modal-backdrop">
    <div class="mlb-calendar-backdrop" role="presentation"></div>
</template>
