![HumanKind Funeral Notices](https://weave-hk-github.b-cdn.net/humankind/plugin-header.png)

# HumanKind Funeral Notices

Publish and share funeral service details online. Notices look right on desktop and mobile, support optional livestream and tribute links, and match the style of your website.

Built by [Weave Digital Studio](https://weave.co.nz) and [HumanKind Funeral Websites](https://humankindwebsites.com), and used on our own funeral home sites. For developers, it is modular, works cleanly with ACF Pro, and sits alongside the [HumanKind Funeral Suite](https://github.com/HumanKind-nz/hk-funeral-suite).

---

## What it does

- Add and manage funeral notices from within WordPress
- Choose from several layouts built for funeral home websites
- Add livestream and tribute links for families
- Let visitors search and filter notices by name, date, or location
- Set a default venue to speed up data entry
- Load styles and scripts only on pages where notices appear

---

## Free and premium

Core features are free to use. Premium features need a licence key and cover ongoing cloud hosting and bandwidth.

**Free**
- Funeral notice publishing
- Livestream and tribute links
- Layout and styling controls
- Search and filters
- Default venue setting

**Premium**
- Cloud-hosted memorial video slideshows
- Secure streaming with automatic optimisation

To purchase or manage a licence, contact [humankindwebsites.com](https://humankindwebsites.com) or [weave.co.nz](https://weave.co.nz).

---

## Requirements

| Requirement | Minimum |
|-------------|---------|
| WordPress | 6.6 |
| PHP | 8.1 |
| ACF Pro | Latest |

ACF Pro is required; the free version of ACF is not sufficient. If ACF Pro is missing or a requirement is not met, the plugin alerts you on activation.

---

## Getting started

1. Install and activate **ACF Pro**.
2. Upload the plugin ZIP via **Plugins > Add New > Upload Plugin**, then activate it.
3. Go to **Funeral Notices > Dashboard** to set up layouts and options.

Display notices anywhere with a shortcode:

```
[funeral_notices]                           // all notices
[funeral_notices type="future"]             // upcoming only
[funeral_notices ids="123,456" columns="2"] // specific notices
```

Full shortcode options and filters are in the [Developer Documentation](./DEVELOPER.md).

---

## Compatibility

- Works with the HumanKind Funeral Suite CPTs, roles, and permissions
- Integrates with our premium cloud video hosting for memorial slideshows
- Uses standard WordPress post types and taxonomies, so your content stays portable

---

## Privacy

The plugin sends no data anywhere. Everything stays in your own WordPress database. The only outside calls are to services you set up yourself, such as Google Maps for the address field, or our cloud video hosting if you use it.

Anonymous usage reporting was removed in 3.0.2. Updating clears anything it left behind.

---

## Support

- Built-in contextual help in the WordPress admin
- Email: **support@weave.co.nz**
- Issues: [github.com/HumanKind-nz/hk-funeral-notices](https://github.com/HumanKind-nz/hk-funeral-notices)

Before contacting us, check the plugin help pages, confirm ACF Pro is active, and test on a staging site.

---

## Licence

GPL-2.0-or-later. See the [LICENSE](LICENSE) file.

*Built by Weave Digital Studio for HumanKind, for funeral homes who serve families with care and dignity.*
