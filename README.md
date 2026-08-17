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

## Features

Everything in the plugin is available to everyone. There is no licence key.

- Funeral notice publishing
- Livestream and tribute links
- Layout and styling controls
- Search and filters
- Default venue setting
- Memorial video slideshows, hosted on your own Bunny Stream account

---

## Video hosting setup

Memorial videos are hosted on [Bunny Stream](https://bunny.net/stream/). You use your own Bunny account, so you control the library and pay Bunny directly for storage and bandwidth. The plugin does not proxy video through us and there is nothing to activate.

1. Create a Bunny Stream video library in the [Bunny dashboard](https://dash.bunny.net/stream).
2. Copy the **Library ID**, an **API key** with access to it, and the library's **CDN hostname** (the `*.b-cdn.net` pull zone).
3. Add them to `wp-config.php`:

```php
define('HKFN_VIDEO_LIBRARY_ID',   'your-library-id');
define('HKFN_VIDEO_API_KEY',      'your-api-key');
define('HKFN_VIDEO_CDN_HOSTNAME', 'your-pull-zone.b-cdn.net');
```

The video upload field appears on funeral notices as soon as a library ID and API key are present. There is no licence key and nothing to activate.

There is deliberately no admin screen for entering these. Keys stay out of the database and out of anything that gets exported with a site. If you cannot edit `wp-config.php`, they can be set as options instead:

```bash
wp option update hkfn_bunny_library_id "your-library-id"
wp option update hkfn_bunny_api_key "your-api-key"
wp option update hkfn_bunny_cdn_hostname "your-zone.b-cdn.net"
```

Credentials are resolved in this order, so existing installations keep working without changes:

1. `HKFN_BUNNYSTREAM_LIBRARY_ID` / `HKFN_BUNNYSTREAM_API_KEY` (also accepted with the older `WFN_` prefix)
2. `HKFN_VIDEO_LIBRARY_ID` / `HKFN_VIDEO_API_KEY` (also accepted with `WFN_`)
3. The `hkfn_bunny_*` options above

The **Video Slideshows** screen, under Funeral Notices in the admin menu, controls upload limits and encoding options rather than credentials: maximum file size, maximum duration, allowed formats, quality preset and thumbnails.

Uploads are capped at 900MB by default and typically take up to 10 minutes to encode. Change the cap under **Video Slideshows → Maximum File Size**, anywhere from 50MB to 2000MB.

### What deletes a video, and what never does

Videos are stored in your Bunny library, not ours, so it matters that you can predict what the plugin will and will not remove.

**A video is deleted only when:**

- You delete it yourself from the funeral notice editor
- A funeral notice is **permanently** deleted (moving one to trash does nothing)
- You replace an existing video on a notice with a new one

**Nothing else prunes.** There is no scheduled cleanup, no retention policy, and no orphan sweeper. An earlier version had automatic cleanup and it deleted an entire library on 20 October 2025, so it was removed rather than fixed. If a video stops being referenced it simply stays in Bunny until you remove it by hand.

**Before any deletion**, the plugin checks the video's Bunny collection against this site's collection. If they do not match, if the video has no collection, if the site has no collection, or if the Bunny API cannot be reached to check, the deletion is refused. Ownership has to be proven, not assumed. This is what stops one site in a multi-site estate from deleting another's videos, and what stops a network blip during a bulk delete from wiping anything.

The plugin is quiet in normal use. It does not write to the log on every page load or every save. Deletion attempts are recorded to the database, and only deletion attempts, so there is something to report against later:

```bash
wp hkfn video log                  # everything, newest first
wp hkfn video log --outcome=deleted
wp hkfn video log --format=csv > deletions.csv
```

Set `WP_DEBUG` if you want the verbose developer trace as well.

### Finding videos to prune

```bash
wp hkfn video reconcile            # read-only, deletes nothing
wp hkfn video reconcile --show=all
```

`reconcile` reports **orphaned** (in Bunny, nothing points at it), **missing** (a notice points at a video that is gone, worth checking the log for), and **matched**. It never deletes anything. Pruning is a deliberate manual step in the Bunny dashboard.

**One Bunny library can be shared by many sites**, each in its own collection. `reconcile` is therefore scoped to this site's own collection by default, and if the site has no collection it refuses to run rather than guess. Anything outside your collection belongs to someone else, and treating a whole-library listing as a prune list is how you delete another site's videos. `--scope=library` exists for inspection and warns loudly.

For a fleet-wide prune you need every site's references before you can judge any single video. Export each site's inventory, then compare the union against the library:

```bash
# one file per site
for d in $(ls /var/www); do
  wp --path="/var/www/$d/htdocs" hkfn video inventory --allow-root > "/tmp/inv-$d.json" 2>/dev/null
done
```

Each file records the site URL, its Bunny collection, and the video IDs it references. Any video in the library that appears in no inventory, or sits in a collection whose site no longer exists, is a genuine candidate for removal.

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
- Integrates with Bunny Stream for memorial video slideshows, using your own account
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
