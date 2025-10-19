# Developer Documentation — HumanKind Funeral Notices

This document provides technical and developer-focused details for extending or integrating the HumanKind Funeral Notices plugin.

---

## Table of Contents
- [Shortcodes](#shortcodes)
- [Template Overrides](#template-overrides)
- [Hooks and Filters](#hooks-and-filters)
- [Module Overview](#module-overview)
- [Performance Details](#performance-details)
- [Analytics & Privacy](#analytics--privacy)
- [Support](#support)

---

## Shortcodes

The plugin provides the `[funeral_notices]` shortcode to display funeral notices in various layouts.

### Basic Example
```php
[funeral_notices]
```

### Common Parameters
| Parameter | Options | Default | Description |
|------------|----------|----------|--------------|
| `layout` | `firehawk`, `modern`, `elegant`, `minimal` | `modern` | Selects layout style |
| `type` | `all`, `future`, `archived`, `today`, `this_week`, `this_month` | `all` | Filters by date |
| `columns` | `1`, `2`, `3`, `4` | `3` | Number of grid columns |
| `per_page` | number | `12` | Notices per page |
| `show_search` | `yes`, `no` | `yes` | Enables search bar |
| `card_style` | `standard`, `elevated`, `outlined`, `minimal` | `standard` | Card appearance |
| `ids` | Comma-separated post IDs | none | Display only specific funeral notices (e.g., `"123,456,789"`) |
| `exclude` | Comma-separated post IDs | none | Exclude specific posts from the query (e.g., `"123,456"`) |

### Advanced Examples
```php
// Elegant layout with elevated cards
[funeral_notices layout="elegant" columns="2" card_style="elevated" type="future" show_search="no"]

// Display specific funeral notices by ID
[funeral_notices ids="123,456,789" columns="3" layout="modern"]

// Exclude specific posts from the main query
[funeral_notices exclude="123,456" type="future"]
```

---

## Template Overrides

To override templates in your theme:

1. Create a folder named `funeral-notices/` inside your active theme.
2. Copy the desired template from the plugin’s `/templates/` directory.
3. Edit as needed — the plugin will automatically use your version.

Example structure:
```
your-theme/
└── funeral-notices/
    ├── archive-funeral-notice.php
    ├── single-funeral-notice.php
    └── modes/
        └── custom/
            ├── archive.php
            └── single.php
```

---

## Hooks and Filters

### Filters
```php
// Add a custom layout
a dd_filter('wfn_available_layouts', function($layouts) {
  $layouts['custom'] = [
    'name' => 'Custom Layout',
    'description' => 'A unique design',
    'template' => 'custom-template.php'
  ];
  return $layouts;
});

// Modify search query
add_filter('wfn_search_meta_query', function($meta_query, $params) {
  return $meta_query;
}, 10, 2);

// Alter card data before render
add_filter('wfn_funeral_card_data', function($data, $post_id) {
  return $data;
}, 10, 2);
```

### Actions
```php
add_action('wfn_before_funeral_display', function($post_id) {
  // Code before each funeral notice output
});

add_action('wfn_after_search_form', function() {
  // Add custom content below search form
});

add_action('wfn_module_activated', function($module_id) {
  // Triggered when module is activated
});
```

---

## Module Overview

| Module | Purpose | Admin Page |
|---------|----------|-------------|
| **Settings** | Core configuration and URL structure | Funeral Notices → Settings |
| **Layouts** | Layouts and grid options | Funeral Notices → Layouts |
| **Search** | AJAX search and filters | Funeral Notices → Search |
| **Styling** | Colour and typography controls | Funeral Notices → Styling |
| **Performance** | Caching and optimisation | Funeral Notices → Performance |

Each module can be toggled independently.

---

## Performance Details

- **Conditional Asset Loading**: CSS and JS only load on pages with funeral notices or relevant shortcodes.
- **Caching**: Query caching with auto-purge when data changes.
- **Lazy Loading**: Applies to images and embeds.
- **Database**: Indexed queries and lean postmeta lookups.

Average test results (PHP 8.2 / WP 6.6):
- Initial page render: ~240–260ms
- Cached repeat render: ~120ms

---

## Image Cropping Setup

### Crop-Thumbnails Plugin Integration (v2.5.2+)

The plugin integrates with the popular **Crop-Thumbnails** plugin (30k+ active installs) to provide professional image cropping for grid/card layouts. This allows users to crop featured images to 4:3 ratio for better display on archive pages while keeping full images on single funeral pages.

**Setup Instructions:**

1. **Install Crop-Thumbnails Plugin**
   - Install from WordPress plugin repository or [download here](https://wordpress.org/plugins/crop-thumbnails/)
   - Activate the plugin

2. **Configure Crop-Thumbnails Settings**
   - Navigate to: Settings → Crop-Thumbnails
   - Find the "Funerals" section
   - **Uncheck** "Thumbnail" and "Funeral Image"
   - **Check only** "Grid Crop (4:3)(wfn-grid-crop)"
   - This ensures users only see the relevant crop size in the modal

3. **How Users Crop Images**
   - Edit any funeral notice
   - Set featured image as normal
   - Click "Crop for Grid/Cards" button below the featured image
   - Crop the 4:3 ratio for grid display
   - Original full image remains for single funeral pages

**Benefits:**
- Professional, proven cropping interface
- No custom JavaScript bugs
- Works with all standard WordPress image sizes
- Mobile-friendly crop editor
- Undo/redo crop functionality

### Cache Purging After Crops

When users crop images, **page caching** may prevent the changes from appearing immediately on grid pages.

**Automatic Cache Purging:**

The plugin triggers cache purges automatically if your site uses the [Weave Cache Purge Helper](https://github.com/weavedigitalstudio/weave-cache-purge-helper) plugin, which clears:
- Beaver Builder template cache
- Nginx/LiteSpeed cache
- WordPress object cache

**Manual Integration:**

For other caching setups, add this to your theme's functions.php:

```php
// Clear cache after image crop
add_action('crop_thumbnails_after_save_new_thumb', function($attachment_id, $size_name) {
    // Only trigger for funeral notice crops
    if ($size_name === 'wfn-grid-crop') {
        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }

        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        // LiteSpeed Cache
        if (class_exists('LiteSpeed_Cache_API')) {
            LiteSpeed_Cache_API::purge_all();
        }
    }
}, 10, 2);
```

**Note:** Image crops are infrequent (1-2 times per funeral notice), so cache purging is lightweight and won't impact performance.

---

## Analytics & Privacy

The plugin includes optional anonymous analytics:
- Tracks number of notices created and livestream usage percentage
- Includes plugin, PHP, and WordPress versions
- Does *not* collect names, content, or personal data

To disable analytics globally:
```php
add_filter('wfn_enable_analytics', '__return_false');
```

For deployment-level control (wp-config.php):
```php
define('WFN_DISABLE_ANALYTICS', true);
```

Data is stored securely and complies with New Zealand and Australian privacy standards.

---

## Support

- Email: **support@weave.co.nz**
- Documentation: [https://github.com/HumanKind-nz/hk-funeral-notices](https://github.com/HumanKind-nz/hk-funeral-notices)

For bug reports, please include PHP and WordPress versions, and any relevant error logs.

