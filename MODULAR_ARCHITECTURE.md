# Modular Booking System Documentation

## Overview

The MyLighthouse Booker plugin now uses a modular component-based architecture that separates concerns, reduces code duplication, and improves maintainability.

## Architecture

### Core Components (PHP Templates)

Located in `templates/components/`:

1. **date-picker-modal.php** - Global reusable date picker modal
2. **booking-details.php** - Booking summary with date display and check availability button
3. **booking-results-modal.php** - Iframe modal for booking engine results

These templates are loaded once in the footer by the Template_Loader class.

### JavaScript Controllers

Located in `assets/js/frontend/`:

#### Core Components

1. **date-picker.js** (`MLB_DatePicker`)
   - Controls the global date picker modal
   - Single instance used by all forms
   - Handles date selection with callback pattern

2. **booking-details.js** (`MLB_BookingDetails`)
   - Controls the booking details component
   - Shows selected dates and nights count
   - Manages change dates and check availability actions

3. **booking-actions.js** (`MLB_BookingActions`)
   - Centralized booking logic
   - Handles all booking types (room, special, hotel)
   - Determines display mode based on device width
   - Manages redirects to modal/booking page/engine

4. **booking-results-modal.js** (`MLB_Modal`)
   - Controls the iframe booking results modal
   - Handles spinner, timeout, and fallbacks

#### Widget Scripts

1. **room-widget.js**
   - Handles `[data-mlb-book-room]` buttons
   - Opens date picker → shows booking details → books room

2. **hotel-widget.js**
   - Handles `[data-mlb-hotel-form]` forms
   - Opens date picker on input click
   - Submits to check availability

3. **special-widget.js**
   - Handles `[data-mlb-book-special]` buttons
   - Direct booking without date picker (dates in widget data)

### CSS

**components.css** - Styles for all modular components:
- Date picker modal
- Booking details bar
- Buttons and animations
- Responsive breakpoints

## Usage for Elementor Widgets

### Room Booking Widget

```php
// In widget render method
echo '<button 
    data-mlb-book-room 
    data-hotel-id="' . esc_attr($hotel_id) . '"
    data-room-id="' . esc_attr($room_id) . '"
    class="mlb-book-room-btn">
    Book Room
</button>';
```

**Flow:**
1. User clicks button
2. Date picker modal opens
3. User selects dates
4. Booking details component shows with selected dates
5. User can change dates or check availability
6. Check availability triggers booking action based on display mode

### Hotel Availability Form Widget

```php
// In widget render method
echo '<form data-mlb-hotel-form data-hotel-id="' . esc_attr($hotel_id) . '">
    <input type="text" 
        data-mlb-date-input 
        placeholder="Select dates" 
        readonly>
    <button type="submit" data-mlb-submit>Check Availability</button>
</form>';
```

**Flow:**
1. User clicks date input
2. Date picker modal opens
3. User selects dates
4. Input shows formatted date range
5. Submit button becomes enabled
6. User clicks submit → booking action triggered

### Special/Rate Booking Widget

```php
// In widget render method
echo '<button 
    data-mlb-book-special 
    data-hotel-id="' . esc_attr($hotel_id) . '"
    data-rate-id="' . esc_attr($rate_id) . '"
    data-arrival="' . esc_attr($arrival) . '"
    data-departure="' . esc_attr($departure) . '"
    class="mlb-book-special-btn">
    Book Special
</button>';
```

**Flow:**
1. User clicks button
2. Direct booking (no date picker needed)
3. Booking action triggered immediately with preset dates

## Display Modes

Configured via Settings → Display Options:

- **modal** - Opens booking results in iframe modal
- **booking_page** - Redirects to WordPress booking page with params
- **redirect_engine** - Direct redirect to booking engine

Display modes are device-specific:
- Mobile: ≤767px
- Tablet: 768-1024px
- Desktop: >1024px

## Benefits

### For Developers

1. **Single Date Picker** - No need to initialize easepick in each widget
2. **Modular** - Each component has one responsibility
3. **Reusable** - Components work across all booking types
4. **Maintainable** - Changes in one place affect all uses
5. **Testable** - Components can be tested independently

### For Performance

1. **Less JavaScript** - Easepick loaded once globally
2. **Less HTML** - Templates rendered once in footer
3. **Better Caching** - Static templates cache well
4. **Faster Rendering** - No runtime HTML generation

### For UX

1. **Consistent** - Same date picker everywhere
2. **Accessible** - ARIA labels from server-side
3. **i18n Ready** - WordPress translation functions
4. **Responsive** - Mobile-first design

## Configuration

### PHP Localization

Scripts receive configuration via `wp_localize_script`:

```php
// In class-frontend-assets.php
wp_localize_script('mylighthouse-booker-booking-actions', 'mlbConfig', array(
    'bookingPageUrl' => get_option('mlb_booking_page_url', ''),
    'displayModeMobile' => get_option('mlb_display_mode_mobile', 'modal'),
    'displayModeTablet' => get_option('mlb_display_mode_tablet', 'modal'),
    'displayModeDesktop' => get_option('mlb_display_mode_desktop', 'modal'),
));
```

### JavaScript Access

```javascript
// Access configuration
const config = window.mlbConfig;
const displayMode = MLB_BookingActions.getDisplayMode();
```

## Backwards Compatibility

Legacy scripts (`booking-form.js`, `room-form.js`, `special-form.js`) remain for backwards compatibility but should be phased out. New development should use the modular widgets.

## Migration Guide

### Old Way (Deprecated)

```javascript
// Each widget initialized its own picker
const picker = new easepick.create({
    element: input,
    // ... config
});
```

### New Way (Recommended)

```javascript
// Use global date picker
MLB_DatePicker.open(function(dates) {
    // Handle selected dates
});
```

## Troubleshooting

### Date Picker Not Opening

Check console for errors. Ensure:
1. `easepick-wrapper` script is loaded
2. Template loaded in footer (check HTML source)
3. No JavaScript errors before initialization

### Booking Details Not Showing

Check:
1. `MLB_BookingDetails` is defined
2. Template exists in DOM (`#mlb-booking-details`)
3. Component CSS is loaded

### Wrong Display Mode

Check:
1. Settings configured correctly
2. `mlbConfig` object exists in window
3. Device width detection working (check `getDisplayMode()`)

## File Structure

```
mylighthouse-booker/
├── templates/
│   └── components/
│       ├── date-picker-modal.php
│       ├── booking-details.php
│       └── booking-results-modal.php
├── includes/
│   └── Frontend/
│       ├── class-template-loader.php
│       └── class-frontend-assets.php
├── assets/
│   ├── js/frontend/
│   │   ├── date-picker.js
│   │   ├── booking-details.js
│   │   ├── booking-actions.js
│   │   ├── booking-results-modal.js
│   │   ├── room-widget.js
│   │   ├── hotel-widget.js
│   │   └── special-widget.js
│   └── css/frontend/
│       └── components.css
```

## Future Enhancements

1. **AJAX Validation** - Validate dates before booking
2. **Loading States** - Better feedback during bookings
3. **Error Handling** - User-friendly error messages
4. **Analytics** - Track booking funnel
5. **A/B Testing** - Test different flows

## Support

For issues or questions:
- Check browser console for errors
- Verify templates loaded in footer
- Ensure all dependencies registered and enqueued
- Check device display mode configuration
