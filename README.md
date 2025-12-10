# mylighthouse-booker

WordPress booking plugin for the MyLighthouse Booking Engine (formerly Cubilis). It lets site admins curate hotels, rooms, and specials from the MyLighthouse platform and expose booking flows through modern frontend components.

## Features
- Central “MyLighthouse Booker” admin area with dashboards, settings, and hotel management.
- Drag-and-drop hotel ordering plus per-hotel room and special management.
- Elementor widget (“Lighthouse Booking Form”) with extensive layout controls for hotel, room, or special-focused forms.
- Booking results shortcode (`[lighthouse_booking_results]`) to host iframe-based search results on any page.
- Flexible display modes: inline modal overlay or redirect to a dedicated booking/results page.
- Customizable loading spinner/background imagery to maintain brand consistency.

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
2. Fill in the following options:
	- **Booking Page URL**: The page slug or absolute URL where full booking results should load (e.g., `/book-now/`).
	- **Display Mode**: Choose **Modal** (display availability results in a popup overlay) or **Booking Page** (redirect to the configured page with query parameters).
	- **Spinner Background**: Optional image URL for the loading spinner shown while fetching live availability.
3. Save the settings.

### Add Hotels
1. Go to **MyLighthouse Booker → Hotels**.
2. Click **Add Hotel** and provide:
	- **Hotel Name**: Friendly label shown to guests.
	- **External ID**: The MyLighthouse-provided hotel identifier (required for API calls and iframe URLs).
3. After saving, use the room and special repeaters inside each hotel to register individual rooms or promotional rates (IDs must match the MyLighthouse backend).
4. Reorder hotels by grabbing the drag icon beside each entry; the new order is saved automatically when you drop an item.

### Booking Page / Results Shortcode
1. Create or edit the page whose URL matches the **Booking Page URL** you configured.
2. Add the shortcode:
	```
	[lighthouse_booking_results width="100%" height="100vh"]
	```
	Adjust `width` or `height` as needed. This shortcode renders the iframe target that receives search parameters from the booking forms.

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
