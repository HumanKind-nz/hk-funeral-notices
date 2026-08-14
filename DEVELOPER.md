# Developer Documentation — HumanKind Funeral Notices

Technical details for extending or integrating the HumanKind Funeral Notices plugin.

---

## Table of Contents
- [Shortcodes](#shortcodes)
- [Layouts and Templates](#layouts-and-templates)
- [Hooks and Filters](#hooks-and-filters)
- [Module Overview](#module-overview)
- [Image Cropping](#image-cropping)
- [Performance](#performance)
- [Analytics and Privacy](#analytics-and-privacy)
- [Support](#support)

---

## Shortcodes

Use the `[funeral_notices]` shortcode to display notices in any layout.

```
[funeral_notices]
```

### Parameters

| Parameter | Options | Default | Description |
|-----------|---------|---------|-------------|
| `layout` | `firehawk`, `modern`, `elegant`, `minimal` | from settings | Layout style (`style` is an accepted alias) |
| `type` | `all`, `future`, `archived`, `today`, `this_week`, `this_month` | `all` | Filter by date |
| `columns` | `1`, `2`, `3`, `4` | `3` | Number of grid columns |
| `per_page` | number | `12` | Notices per page |
| `show_search` | `yes`, `no` | `yes` | Show the search form above the grid |
| `show_pagination` | `yes`, `no` | `yes` | Show pagination |
| `location` | location slug | none | Filter to a single location |
| `date_from` | `Y-m-d` | none | Filter from a date |
| `date_to` | `Y-m-d` | none | Filter to a date |
| `ids` | comma-separated post IDs | none | Show only these notices (e.g. `"123,456"`) |
| `exclude` | comma-separated post IDs | none | Exclude these notices |

### Examples

```
// Elegant layout, two columns, upcoming only, no search
[funeral_notices layout="elegant" columns="2" type="future" show_search="no"]

// Specific notices by ID
[funeral_notices ids="123,456,789" columns="3" layout="modern"]

// Everything except two posts
[funeral_notices exclude="123,456" type="future"]
```

---

## Layouts and Templates

Layout templates ship inside the plugin at `templates/modes/{mode}/` (with a `modes/default/` fallback). The four selectable layouts are `firehawk`, `modern`, `elegant`, and `minimal`.

To register a new layout, use the `hkfn_available_layouts` filter (see below). For bespoke template work beyond the built-in layouts, get in touch at **support@weave.co.nz**.

> Note: v3 loads templates from the plugin only. It does not read override templates from a theme folder.

---

## Hooks and Filters

All hooks use the `hkfn_` prefix.

```php
// Add or modify the available layouts
add_filter('hkfn_available_layouts', function($layouts) {
    $layouts['custom'] = [
        'name'        => 'Custom Layout',
        'description' => 'A unique design',
        'template'    => 'custom-template.php',
    ];
    return $layouts;
});

// Adjust the search WP_Query arguments
add_filter('hkfn_search_query_args', function($args, $search_term, $date_from, $date_to, $location) {
    return $args;
}, 10, 5);

// Filter search results before they are returned
add_filter('hkfn_search_results', function($results, $search_term) {
    return $results;
}, 10, 2);

// Show or hide the tribute button on a notice
add_filter('hkfn_show_tribute_button', function($show, $post_id) {
    return $show;
}, 10, 2);

// Replace or wrap the memorial video modal markup
add_filter('hkfn_memorial_video_modal', function($html, $post_id) {
    return $html;
}, 10, 2);

// Turn off the modern Google Maps API on the location field
add_filter('hkfn_use_modern_google_maps_api', '__return_false');

// Rewrite the "Send a tribute" link, for example to pass the notice ID
add_filter('hkfn_tribute_url', function($url, $first_name, $last_name) {
    return $url;
}, 10, 3);
```

### Names in the tribute link

Bracketed nicknames are stripped from the tribute link's query string. A notice recorded as `Firstname (Nickname) Lastname` links to `?tribute=Firstname+Lastname`, while the notice itself still shows the name exactly as entered.

This is not cosmetic. Some server security rules reject query strings containing bracketed groups, returning an error before WordPress runs. Stripping the brackets keeps the link working regardless of where the site is hosted, and needs no server-side configuration.

Templates that build their own links should use `$person['url_safe_name']` rather than `$person['full_name']` for anything going into a URL.

The plugin does not currently expose custom action hooks. For integration points, use the filters above or standard WordPress hooks (`save_post`, `wp_enqueue_scripts`, and so on).

---

## Module Overview

The plugin is built from independent modules, each with its own admin screen under the **Funeral Notices** menu. Each can be toggled on or off.

| Module | Purpose |
|--------|---------|
| **Settings** | Core configuration and URL structure |
| **Layouts** | Layout modes and grid options |
| **Search** | AJAX search and filtering |
| **Styling** | Colour, typography, and custom CSS |
| **Performance** | Caching and asset optimisation |
| **License** | Premium licence activation and status |
| **Video** | Premium memorial video slideshows (cloud hosted) |

---

## Settings Storage

The React settings screen reads and writes one consolidated option, `hkfn_settings`, exposed over `/wp/v2/settings` (`inc/settings-page.php`). The runtime reads per-module options: the `hkfn_module_settings` aggregate plus `hkfn_module_{layouts,search,styling,video}_settings`, each with a `wfn_`-prefixed fallback for sites upgraded from 2.x, and the standalone `hkfn_license_key` managed by `LicenseService`.

`inc/settings-bridge.php` keeps the two in sync: a one-off seed (flagged by `hkfn_settings_seeded`) copies the live runtime values into `hkfn_settings` so the screen shows what the site actually uses, and saves fan changed keys back out to the module options. A changed licence key triggers activation through `LicenseService`; clearing the field does not deactivate.

## Image Cropping

The plugin includes a built-in image cropper (Cropper.js). No external crop plugin is required.

Featured images can be cropped to a square (1:1) grid version used on archive and card layouts, while single notice pages keep the full image. The cropper appears in the funeral notice editor once a featured image is set.

How it works:

- A dedicated image size, `hkfn-grid-crop` (square), is registered on `after_setup_theme` and shown in the media size chooser as "Grid Crop (Square)".
- Crop coordinates are saved through the `hkfn_save_crop_coordinates` AJAX action.
- The crop is applied when WordPress generates the grid size, via `wp_generate_attachment_metadata`.
- The crop may extend past the image edges: coordinates can be negative or oversized, and the area outside the photo is filled with a blurred enlargement of the crop (`ImageCropHandler::composite_extended_crop()`).
- Crops saved under an older aspect ratio are recentred to the current ratio on regeneration rather than distorted.
- Card templates resolve images through `ImageCropHandler::grid_image_url()`, which falls back to the legacy v2.x `wfn-grid-crop` rendition and then the large/full image.

### Cache purging after crops

With page caching active, cropped images may not appear straight away on grid pages. The plugin works with the [Weave Cache Purge Helper](https://github.com/weavedigitalstudio/weave-cache-purge-helper), which clears Beaver Builder, Nginx/LiteSpeed, and object caches automatically. For other setups, purge your page cache after cropping.

---

## Performance

- **Conditional asset loading**: CSS and JS load only on pages with funeral notices or the shortcode.
- **Caching**: query caching with automatic purge when notice data changes.
- **Lazy loading**: applied to images and embeds.
- **Lean queries**: indexed lookups and minimal postmeta reads.

---

## Privacy

The plugin sends no telemetry. Notice data stays in the site's own database, and the only outbound calls are to services you configure yourself: Google Maps for the address field, and Bunny Stream if premium video is licensed.

Versions 2.3.0 to 3.0.1 included a usage-analytics module that posted anonymous monthly counts to a hosted Supabase project. It was removed in 3.0.2 along with the `hkfn_enable_analytics` filter and the `HKFN_DISABLE_ANALYTICS` constant, both of which no longer do anything.

Updating to 3.0.2 runs a one-off cleanup on the first admin request. It unschedules the `hkfn_send_monthly_analytics` and `hkfn_supabase_heartbeat` events (plus their `wfn_` predecessors, which v3.0.0 renamed without clearing) and deletes the `analytics_site_id`, `analytics_registered`, and `module_analytics_enabled` options under both prefixes. The routine lives in `inc/cleanup.php` and is guarded by the `hkfn_analytics_cleanup_done` flag.

Search analytics in the Search module is a separate, local-only feature. It records search terms in the site's own `hkfn_search_analytics` option for 30 days, is off by default, and transmits nothing.

---

## Google Maps key

The location address field uses Google Maps. Set the key under **Settings → Google Maps / Places API Key**, or define it once for a fleet:

```php
// wp-config.php or user-config.php
define('HKFN_GOOGLE_MAPS_KEY', 'AIza...');
```

The setting takes precedence; the constant is the fallback. On the Google Cloud key, enable **Maps JavaScript API**, **Places API**, and **Geocoding API**, and add an HTTP-referrer restriction for your domain. The key powers address autocomplete and the admin location map only. A Maps key is exposed in page markup by design, so restrict it by referrer rather than trying to keep it secret.

---

## Support

- Email: **support@weave.co.nz**
- Issues: [github.com/HumanKind-nz/hk-funeral-notices](https://github.com/HumanKind-nz/hk-funeral-notices)

For bug reports, please include your PHP and WordPress versions and any relevant error logs.
