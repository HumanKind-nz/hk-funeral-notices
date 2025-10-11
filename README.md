![HumanKind Funeral Notices](https://weave-hk-github.b-cdn.net/humankind/plugin-header.png)

# HumanKind Funeral Notices

HumanKind Funeral Notices makes it simple for funeral homes to publish and share funeral service details online. Notices display beautifully on desktop and mobile, include optional livestream and tribute links, and can easily match the style of your website.

For web developers and digital agencies, the plugin is modular, well-structured, and fully compatible with the wider [HumanKind Funeral Suite](https://github.com/HumanKind-nz/hk-funeral-suite). We use it ourselves on WordPress funeral websites by [Weave Digital Studio](https://weave.co.nz) and [HumanKind Funeral Websites](https://humankindwebsites.com)

---

## Key Features

- **Easy Publishing**: Add and manage funeral notices directly in WordPress.
- **Flexible Layouts**: Multiple professional designs suited to modern funeral home websites.
- **Livestream & Tributes**: Add streaming links and tribute messages for families.
- **Search & Filters**: Visitors can find notices quickly by name, date, or location.
- **Performance Conscious**: Loads only what’s needed and includes built-in caching options.
- **Developer Friendly**: Works cleanly with ACF Pro and supports full theme overrides.
- **🎥 Video Slideshows (Premium)**: Upload and host memorial video slideshows directly on funeral pages. Videos are securely hosted in the cloud for smooth playback and permanent archiving.
- **📍 Default Venue Setting**: Set a default location for new funeral notices to save time on data entry.

---

## Requirements

| Requirement | Minimum Version | Notes |
|--------------|-----------------|--------|
| PHP | 8.0 | Required for plugin to run |
| WordPress | 6.0 | Core CMS compatibility |
| ACF Pro | Latest | Needed for data fields |
| ACF Extended | Optional | Adds advanced field types and better UI |

*The free version of ACF is not sufficient. ACF Pro is required for all field functionality.*

The free [SCF](https://wordpress.org/plugins/secure-custom-fields/) Plugin might be a ACF Pro replacement but has not been tested yet.

---

## Installation

1. Install and activate **ACF Pro**.
2. Download the HumanKind Funeral Notices plugin ZIP.
3. Upload and activate it via **Plugins > Add New > Upload Plugin**.
4. Go to **Funeral Notices > Dashboard** to set up layouts and options.

If ACF Pro is missing or requirements aren’t met, the plugin will alert you automatically.

---

## Basic Shortcodes

Display all funeral notices:
```php
[funeral_notices]
```

Show only upcoming funerals:
```php
[funeral_notices type="future"]
```

Display specific funeral notices by ID:
```php
[funeral_notices ids="123,456" columns="2"]
```

Exclude specific posts from the grid:
```php
[funeral_notices exclude="123,456"]
```

For a complete list of shortcode options and parameters, see the [Developer Documentation](./DEVELOPER.md).

---

## Compatibility & Integration

- Fully compatible with the **HumanKind Funeral Suite** CPTs, roles and permissions.
- Integrates with the **HumanKind Premium Video Hosting Service** for secure cloud-based video slideshows if required.
- Uses standard WordPress post types and taxonomies for flexibility and portability.

---

## Performance & Testing

HumanKind Funeral Notices is designed to run efficiently on modern WordPress setups. Styles and scripts only load on pages using the plugin’s shortcodes or funeral notice templates. Query caching and lazy loading further reduce page load times.

Video slideshows are hosted offsite using our cloud video platform, ensuring smooth playback and minimal impact on your website’s performance.

*Latest internal tests (September 2025) show average page loads under 250ms for standard funeral listings on a PHP 8.2 / WordPress 6.6 setup with caching enabled.*

---

## Licensing & Premium Features

HumanKind Funeral Notices is free to use with all core features.  
Premium features require a license key and are billed to cover ongoing cloud hosting and bandwidth costs.

### Free Features
- Funeral notice publishing  
- Livestream and tribute links  
- Layout and styling controls  
- Search and filters  
- Default venue setting

### Premium Features
- Cloud-hosted video slideshow uploads  
- Secure streaming with automatic optimisation  
- Monthly usage statistics and bandwidth reporting

Contact us to purchase or manage a license:  
🌐 [humankindwebsites.com](https://humankindwebsites.com) or [weave.co.nz](https://weave.co.nz)

---

## Privacy & Analytics

This plugin collects anonymous usage statistics to help improve features and provide industry insights. Data collected is **aggregated and non-personal** (counts only — e.g. number of funeral notices, percentage using livestream features).

We **never** collect names, personal details, or any content from funeral notices.

To disable analytics for a specific site:
```php
add_filter('wfn_enable_analytics', '__return_false');
```

Full privacy and analytics details are available in the [Developer Documentation](./DEVELOPER.md).

---

## Support

- Built-in contextual help within WordPress admin
- Email support: **support@weave.co.nz**
- GitHub Issues: [github.com/HumanKind-nz/hk-funeral-notices](https://github.com/HumanKind-nz/hk-funeral-notices)

Before contacting support, please:
1. Check the plugin’s help pages
2. Verify ACF Pro is installed
3. Test on a staging site

---

## Feature Availability

| Feature | Free | Premium |
|----------|------|----------|
| Funeral Notices | ✅ | – |
| Livestream Links | ✅ | – |
| Tribute Messages | ✅ | – |
| Default Venue | ✅ | – |
| Video Slideshows | – | ✅ |
| Cloud CDN Video Hosting | – | ✅ |

---

## License

Licensed under the **GPLv2 License**. See the [LICENSE](LICENSE) file for details.

---

*Developed by Weave Digital Studio for HumanKind — designed for funeral homes who serve families with care and dignity.*

