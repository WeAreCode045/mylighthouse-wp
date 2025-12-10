# mylighthouse-booker

WordPress booking plugin for the MyLighthouse Booking Engine (formerly Cubilis). It lets site admins curate hotels, rooms, and specials from the MyLighthouse platform and expose booking flows through modern frontend components.

## Features
- Central "MyLighthouse Booker" admin area with dashboards, settings, and hotel management.
- Drag-and-drop hotel ordering plus per-hotel room and special management.
- Elementor widget ("Lighthouse Booking Form") with extensive layout controls for hotel, room, or special-focused forms.
- **Direct redirect to MyLighthouse booking engine** - no iframe needed, providing full functionality and better user experience.
- Customizable loading spinner/background imagery to maintain brand consistency during redirects.

## Requirements
- WordPress 6.0 or newer (tested up to 6.7).
- PHP 7.4+.
- Elementor installed and activated (for drag-and-drop form building).
- An active MyLighthouse (Cubilis) account with hotel IDs, room IDs, and optional specials you can reference.

## Installation
1. Copy the `mylighthouse-booker` folder into `wp-content/plugins/` or upload the ZIP through **Plugins → Add New → Upload Plugin**.
2. Activate **MyLighthouse Booker** from the WordPress plugins screen.
3. On activation, the plugin attempts to create the required database tables (`wp_mlb_hotels`, `wp_mlb_rooms`, `wp_mlb_specials`). If activation fails, check file permissions and database privileges.

## Initial Configuration
1. In the WordPress dashboard, open **MyLighthouse Booker → Settings**.
2. Configure the following option:
	- **Spinner Background**: Optional image URL for the loading spinner shown during redirect to the MyLighthouse booking engine.
3. Save the settings.

**Note:** The plugin now automatically redirects users directly to the MyLighthouse booking engine (bookingengine.mylighthouse.com) with the appropriate parameters, providing full booking functionality without requiring an iframe or separate booking page.

### Add Hotels
1. Go to **MyLighthouse Booker → Hotels**.
2. Click **Add Hotel** and provide:
	- **Hotel Name**: Friendly label shown to guests.
	- **External ID**: The MyLighthouse-provided hotel identifier (required for API calls and iframe URLs).
3. After saving, use the room and special repeaters inside each hotel to register individual rooms or promotional rates (IDs must match the MyLighthouse backend).
4. Reorder hotels by grabbing the drag icon beside each entry; the new order is saved automatically when you drop an item.

### Direct Booking Redirect
The plugin automatically redirects users to the MyLighthouse booking engine when they submit a booking form. No separate booking page or iframe shortcode is required. Users are taken directly to bookingengine.mylighthouse.com with their selected dates, hotel, and room/special information.

## Embedding Booking Forms

### Elementor Widget (Recommended)
1. Edit any page/post with Elementor.
2. Search for **“Lighthouse Booking Form”** under the MyLighthouse Booker or General widgets.
3. Drag the widget onto your canvas and pick a **Form Type**:
	- **Hotel**: Standard search across one or many hotels.
	- **Room**: Locks the search to a specific room (requires selecting the hotel and room ID that you configured earlier).
	- **Special**: Targets a specific promotional rate/special.
4. Configure layout (inline vs. stacked), button placement, icons, placeholders, and typography/colors from the widget controls.
5. Publish/Update the page. The widget automatically enqueues the required frontend assets.

### Theme/Shortcode Usage (Manual)
If you are not using Elementor, you can render the booking form template from a theme file via:

```php
// Inside a theme template: render the default booking form.
Mylighthouse_Booker_Template_Loader::get_template('booking-form.php');
```

Note: The legacy `[lighthouse_booking_form]` shortcode has been deprecated in favor of the Elementor widget. Use Elementor (or a custom theme template as shown above) for new implementations.

## Tips & Troubleshooting
- Ensure every MyLighthouse ID you enter (hotel, room, special) matches exactly; mismatches prevent live availability from loading.
- When using **Modal** display mode, double-check that popups are not blocked by browser extensions.
- If the iframe results stay blank, confirm that the Booking Page URL contains the `[lighthouse_booking_results]` shortcode and that the page is published.
- Clear your WordPress and CDN caches after updating the plugin so that new assets load correctly.

## Support
For implementation help or feature requests, contact the Code045 team at [https://code045.nl](https://code045.nl) or open an issue in the repository if available.
