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

// Turn off anonymous analytics
add_filter('hkfn_enable_analytics', '__return_false');
```

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
| **Analytics** | Anonymous, aggregated usage statistics |

---

## Image Cropping

The plugin includes a built-in image cropper (Cropper.js). No external crop plugin is required.

Featured images can be cropped to a 4:3 grid version used on archive and card layouts, while single notice pages keep the full image. The cropper appears in the funeral notice editor once a featured image is set.

How it works:

- A dedicated image size, `hkfn-grid-crop` (4:3), is registered on `after_setup_theme` and shown in the media size chooser as "Grid Crop (4:3)".
- Crop coordinates are saved through the `hkfn_save_crop_coordinates` AJAX action.
- The crop is applied when WordPress generates the grid size, via `wp_generate_attachment_metadata`.

### Cache purging after crops

With page caching active, cropped images may not appear straight away on grid pages. The plugin works with the [Weave Cache Purge Helper](https://github.com/weavedigitalstudio/weave-cache-purge-helper), which clears Beaver Builder, Nginx/LiteSpeed, and object caches automatically. For other setups, purge your page cache after cropping.

---

## Performance

- **Conditional asset loading**: CSS and JS load only on pages with funeral notices or the shortcode.
- **Caching**: query caching with automatic purge when notice data changes.
- **Lazy loading**: applied to images and embeds.
- **Lean queries**: indexed lookups and minimal postmeta reads.

---

## Analytics and Privacy

The plugin includes optional anonymous analytics:

- Counts only: number of notices, and the percentage using livestream links
- Includes plugin, PHP, and WordPress versions
- Never collects names, notice content, or personal data

Turn analytics off with a filter:

```php
add_filter('hkfn_enable_analytics', '__return_false');
```

Or at deployment level in `wp-config.php`:

```php
define('HKFN_DISABLE_ANALYTICS', true);
```

Data is aggregated and handled in line with New Zealand and Australian privacy standards.

---

## Support

- Email: **support@weave.co.nz**
- Issues: [github.com/HumanKind-nz/hk-funeral-notices](https://github.com/HumanKind-nz/hk-funeral-notices)

For bug reports, please include your PHP and WordPress versions and any relevant error logs.
