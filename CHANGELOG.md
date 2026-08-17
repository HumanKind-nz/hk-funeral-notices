# HumanKind Funeral Notices — Condensed Changelog

This changelog summarises the key improvements, fixes, and features added to the HumanKind Funeral Notices plugin. For full technical details, refer to the complete CHANGELOG.md file.

---

## [3.1.0] – August 17, 2026

### Fixed: updates stopped appearing on sites

Sites had not been offered a plugin update since October 2025, and forcing a check made no difference.

The plugin asked our licence server for the latest version before it asked GitHub, and only fell back to GitHub if that first request *failed*. The licence server kept answering successfully, but with a version number that had been frozen at 2.2.15 since 1 October 2025. Every site compared its own newer version against that, decided it was already up to date, and never looked at GitHub at all.

Version checks now read a small file published to our CDN on every release, so there is no longer a step that can quietly go stale. Sites still download the update itself from GitHub, and fall back to checking GitHub directly if the CDN cannot be reached.

### Changed: video hosting no longer needs a licence key

Memorial video hosting was gated behind a premium licence key. That gate never protected anything, because the feature has always run on the site's own Bunny Stream library and API key, billed by Bunny directly to whoever owns the account.

The licence key, the Licence settings tab and the premium licence screen have all been removed. Video hosting is simply available once Bunny credentials are present. Existing sites already have those credentials, so nothing changes for them.

To set video up on a new site, add your Bunny Stream details to `wp-config.php`:

```php
define('HKFN_VIDEO_LIBRARY_ID',   'your-library-id');
define('HKFN_VIDEO_API_KEY',      'your-api-key');
define('HKFN_VIDEO_CDN_HOSTNAME', 'your-pull-zone.b-cdn.net');
```

They can also be entered on the Video settings screen. Constants take priority.

### Added: a record of every video deletion

Videos live in your Bunny library, and until now the only trace of a deletion was a line in the PHP error log. Every attempt is now recorded: what was deleted, what was refused, when, which notice it belonged to, who or what triggered it, and why.

```bash
wp hkfn video log
wp hkfn video log --outcome=deleted --format=csv > deletions.csv
```

There is also a read-only reconcile that compares the Bunny library against the videos this site still references, so orphans can be found and pruned by hand rather than by a script:

```bash
wp hkfn video reconcile
```

It reports orphaned, missing and matched videos, and deletes nothing.

### Fixed: deletion no longer proceeds when ownership cannot be verified

Before deleting, the plugin checks that a video's Bunny collection belongs to this site. If that check could not be completed because the Bunny API returned an error, the old code logged a warning and **deleted the video anyway**. A brief API outage during a bulk delete could therefore bypass every safety check, which is the shape of the October 2025 incident.

It now refuses and reports the failure. An orphaned video costs pennies and can be removed by hand. A deleted tribute cannot be recovered.

To be clear about what has not changed: there is still no scheduled cleanup, no retention policy and no orphan sweeper. Videos are only removed when someone deletes one from a notice, permanently deletes a notice, or replaces a video.

### Fixed: video size cap silently dropping to 500MB

The upload cap is 900MB, but the settings schema still declared the old 500MB default. On a site that had never manually saved the Video settings form, saving anything on the settings screen wrote 500 through to the video module and halved the cap, with nothing in the interface showing it had changed.

### Removed: calls to humankindwebsites.com

The plugin used to contact our licence server on every single page load, front-end and admin, which cost roughly 2.3 seconds per request on a cache miss. It no longer contacts that server at all.

---

## [3.0.4] – August 17, 2026

### Fixed: new notices missing from the grid

Any funeral notice created after a site updated to v3 did not appear in the notices grid, the archive, or search results. It looked completely normal in the WordPress admin list, with the name, date, time and venue all showing, which made it easy to miss.

This affected every site running v3. It was not related to hidden details, the photo, or anything staff did while creating the notice.

The cause was internal. v3 renamed the fields that store a notice's details, and the update copied every existing notice across to the new names. Notices created after that point only ever got the new names, while the grid was still looking for the old ones, so it skipped them entirely.

The plugin now keeps both names in step whenever a notice is saved, and repairs affected notices automatically on update. No action is needed from staff. Any notice that was missing will reappear once the update runs.

If notices are added by bulk import rather than the normal editor, the repair can be re-run at any time with:

```
wp eval '(new HumanKind\FuneralNotices\FieldGroups\LegacyMetaMirror())->backfill();'
```

---

## [3.0.3] – August 14, 2026

### Grid cards are now square

Grid and list cards use a square (1:1) photo instead of 4:3 landscape. Most photos supplied by families are portrait head shots, and square framing fits the whole head where the old ratio often could not. Landscape photos still fill the frame with tighter framing on the person. The full uncropped photo on the notice page is unchanged.

Existing crops keep displaying (trimmed at the sides by the browser) until the notice is re-cropped, so nothing breaks on update. Staff can tidy any current notice with **Re-crop photo**. Crops made by the previous Crop-Thumbnails-era tool on 2.x sites are also picked up again after updating, instead of being ignored.

### Crop can extend past the photo's edges

For photos that still can't fit, like a very tall scan or a wide group shot, the crop tool now lets you zoom out beyond the photo. The space around it is filled with a soft blurred enlargement of the photo, the same treatment television uses for portrait footage. No more choosing which part of a head to cut off.

### Steadier notice edit screen

The notice edit screen no longer flashes a large empty box and reshuffles itself while loading. The content editor and photo box (repositioned into the form by ACF Extended) now appear directly in their final place instead of jumping there a moment after the page opens.

### Settings screen connected and tidied

The settings screen now shows the site's real saved settings, including the premium licence key, instead of defaults, and changes made there apply to the site immediately. Entering a new licence key activates it automatically. Visually the screen gains the plugin icon in the header, a comfortable content width so the section arrows sit beside their headings, and better spacing around the save button.

### Faster crop saving on live sites

Applying a crop no longer clears the whole site's caches. Only the page cache is refreshed (so grid pages show the new photo straight away), and it happens after the editor gets its response, so the crop dialog closes immediately instead of waiting on the purge. This also stops the next save from running against a cold cache.

---

## [3.0.2] – August 14, 2026

### Fixed: "Send a tribute" link could be blocked

Where a notice recorded a nickname in round brackets, some server firewalls rejected the tribute link and families reached an error page instead of the form. Bracketed nicknames are no longer included in the link. Notices still display the full name exactly as entered.

The same fix applies to the "Request Streaming" link in the Firehawk layout. The link can now be customised with the `hkfn_tribute_url` filter.

### Usage analytics removed

The anonymous usage-analytics module has been removed. The plugin no longer sends any data externally.

The `hkfn_enable_analytics` filter and `HKFN_DISABLE_ANALYTICS` constant no longer do anything and can be removed from any site that defines them. Updating clears the old scheduled tasks and settings automatically, with nothing to do on your part.

Search analytics in the Search module is unaffected. It stays local to your site and remains off by default.

---

## [3.0.1] – August 14, 2026

### Google Maps key is now a setting, not hardcoded

The Google Maps API key for the admin location field (the ACF/ACFE address map) is no longer hardcoded in the plugin. Each site sets its own under **Settings → Google Maps / Places API Key**, or defines `HKFN_GOOGLE_MAPS_KEY` in `wp-config.php` / `user-config.php` for centrally-managed fleets. The setting takes precedence; the constant is the fallback. Enable the Maps JavaScript API, Places API, and Geocoding API on the key and restrict it to your domain.

---

## [3.0.0] – July 24, 2026

Major release. The plugin is slimmer and moves to the `hkfn_` naming used across HumanKind plugins. Upgrades from 2.x migrate automatically and keep existing content and settings.

### Built-in image cropper

Replaced the external Crop-Thumbnails plugin with a built-in cropper (Cropper.js). One less plugin to install and maintain.

### `wfn_` → `hkfn_` rename with automatic migration

Options, CSS classes, and post meta now use the `hkfn_` prefix. Upgrading sites migrate on update: notice data is carried across, and saved settings and custom CSS keep working (custom CSS that targets old `.wfn-` classes is rewritten automatically).

### Requirements

Now requires PHP 8.1 and WordPress 6.6.

---

## [2.6.7] – March 6, 2026

### Increased Video Upload Limit

Increased the maximum video upload file size from 500MB to 900MB across all validation layers, at the request of funeral home clients needing to upload larger memorial videos.

- Updated server-side validation in `BunnyStreamService` and `VideoUploadAPI`
- Updated client-side validation and UI messaging in the video upload dropzone
- Updated ACF field configuration and instructions
- Updated `VideoModule` default settings and JS-passed limits

---

## [2.6.6] – December 8, 2025

### 🐛 BUG FIX - Vimeo Privacy URL Truncation

**Fixed:** Vimeo URLs with privacy hashes were being truncated when displayed on funeral notice pages, breaking both embeds and "Open in Vimeo" buttons.

### What Changed
- **Fixed URL handling** in `StreamingDetector::generate_vimeo_embed()` to preserve privacy hashes
- **Added hash extraction** for both path-based (`/VIDEO_ID/HASH`) and query-based (`?h=HASH`) Vimeo privacy URLs
- **Preserved original URLs** in button links instead of reconstructing from video ID only
- **Enhanced iframe embeds** to include privacy hash parameter when present
- **Verified all streaming services** (YouTube, Facebook, OneRoom, iStream) for similar issues - none found

### The Problem
- URLs like `https://vimeo.com/1142878313/dc414dd32c` were saved correctly but displayed as `https://vimeo.com/1142878313`
- Privacy hash (`dc414dd32c`) was lost during URL reconstruction
- Resulted in broken embeds and non-functional "Open in Vimeo" buttons

### The Fix
- Modified method signature to accept original URL: `generate_vimeo_embed(string $video_id, string $original_url = '')`
- Extract privacy hash from URL using regex patterns
- Include hash in iframe embed: `player.vimeo.com/video/{ID}?h={HASH}`
- Use original URL for button links (preserves all parameters)

### Files Modified
- `src/Streaming/StreamingDetector.php` - Updated `generate_vimeo_embed()` method and its usage

---

## [2.6.5] – November 22, 2025

### 🔒 SECURITY - Collection-Aware Video Deletion

**Defense in depth:** After disabling all automatic cleanup (v2.6.4), this release adds collection ownership validation to prevent manual cross-site deletion.

### What Changed
- **Added collection validation** to `BunnyStreamService::delete_video()` method
- **3-layer security checks** before allowing deletion
- **Blocks cross-site deletion** if video belongs to different site's collection
- **Blocks videos without collections** (ownership cannot be verified)
- **Logs security events** when deletion attempts are blocked
- **Strict validation** - only allows deletion when collections definitively match

### How It Works
1. When deleting video, system retrieves video metadata from Bunny API
2. System performs 3 security checks before deletion:
   - **CHECK 1:** Video must have collection assigned ✅
   - **CHECK 2:** Video's collection must match site's collection ✅
   - **CHECK 3:** Site must have collection configured ✅
3. **ALL CHECKS PASS** → Deletion proceeds ✅
4. **ANY CHECK FAILS** → Deletion blocked, security event logged ❌

### Security Checks
- ❌ **No collection on video** → BLOCKED (can't verify ownership)
- ❌ **Different collection** → BLOCKED (belongs to other site)
- ❌ **Site has no collection** → BLOCKED (can't verify ownership)
- ✅ **Collections match** → ALLOWED (safe deletion)

### Impact
- ✅ **Same-site deletions work normally** (transparent to legitimate users)
- ❌ **Cross-site deletions blocked** (prevents incidents)
- ❌ **Videos without collections blocked** (ownership unverifiable)
- 📊 **Security audit trail** via error logs
- ⚠️ **Old videos without collections** require support intervention

### Performance
- Adds ~100ms overhead (one additional API call for validation)
- Acceptable for manual deletion operations

### Files Modified
- `src/Services/BunnyStreamService.php` - Added validation to delete_video() method
- `src/Services/BunnyStreamService.php` - Added get_site_collection_id() helper method

---

## [2.6.4] – November 21, 2025

### BUG FIX - Permanently Disable Automatic Video Cleanup

**Second incident:** Another video deletion occurred Nov 21, affecting Lychgate. This release permanently disables all automatic video cleanup.

### What Changed
- **Disabled 3 cleanup functions** - `cleanup_orphaned_videos()`, `run_maintenance()`, `run_scheduled_maintenance()` now return immediately and do nothing
- **Why:** Videos are irreplaceable memorial content. Automated deletion is too dangerous.
- **Safe operations preserved:** Manual delete button still works (one video at a time). Post deletion hook still works.
- **Disabled 2 more cleanup functions** in `VideoUploadAPI.php`:
- `cleanup_abandoned_uploads()` - Ran daily via cron, deleted incomplete uploads
- `cleanup_video_on_post_delete()` - Ran when posts deleted

### Breaking Changes
- ❌ Automatic video cleanup is permanently disabled
- ✅ Manual deletion via post editor still works
- ✅ Collection deletion for churned customers still available (manual only)

### Auto-Cleanup Feature
- Plugin now automatically unschedules `wfn_cleanup_abandoned_uploads` cron on every load
- No manual cron deletion needed after v2.6.4 deploymentvent delete wfn_cleanup_failed_uploads

### Files Modified
- `src/Modules/VideoModule.php` - 3 cleanup functions disabled
- `src/API/VideoUploadAPI.php` - 2 cleanup functions disabled
- `src/Plugin.php` - Auto-scheduling of cleanup cron disabled + auto-unscheduling added

---

## [2.6.2] – November 19, 2025

### New Features
- **Video Deletion UI** – Added "Remove Video" button to post edit screen for easy video management directly from the funeral notice editor
- **Graceful Video Removal** – Video deletion now follows a safe workflow: unlinks from post first, clears all metadata, then removes from BunnyStream CDN

### Bug Fixes
- **Missing Delete Handler** – Fixed non-functional delete button in admin Video Management section by implementing proper REST API endpoint and JavaScript handler

---

## [2.6.1] – November 19, 2025

### Bug Fixes
- **Social Share Escaping** – Fixed excessive backslash escaping in email/SMS share messages (e.g., "Mangan\\\\\\\\'s" now displays correctly as "Mangan's") by using `wp_unslash()` to remove WordPress-added slashes before output

---

## [2.6.0] – November 5, 2025

### New Feature
- **Social Share** – Added social sharing buttons to single funeral notice pages with support for Facebook, Email, SMS, and WhatsApp. Uses Web Share API on mobile with fallback menu on desktop.

---

## [2.5.4] – October 20, 2025

### CRITICAL Bug Fixes
- **🚨 VIDEO DELETION INCIDENT FIX** – Disabled automatic video maintenance system that caused catastrophic video deletion across all sites on 2025-10-20
  - Removed automatic weekly cron job `wfn_video_maintenance` that ran `cleanup_orphaned_videos()`
  - Removed maintenance UI from admin interface completely
  - Disabled all AJAX handlers for maintenance tasks
  - Added emergency disable constant: `WFN_DISABLE_AUTO_VIDEO_CLEANUP`
  - Root cause: Maintenance only checked current site's database for video IDs, causing videos from other sites to appear "orphaned" and be deleted from shared Bunny library

### New Tools
- **cleanup-broken-video-references.php** – Script to remove WordPress metadata for videos that no longer exist in BunnyStream
  - Safely removes broken video references without touching actual videos
  - Hides video buttons on frontend for missing videos
  - Requires confirmation before proceeding
  - Generates cleanup log and CSV export

### Breaking Changes
- **Manual cleanup only** – Video maintenance must now be triggered manually via WP-CLI scripts, not automatic cron jobs

---

## [2.5.3] – October 19, 2025

### Bug Fixes
- **Crop Preview Refresh** – Added clear user messaging that post must be saved after cropping to see updated preview due to browser caching limitations

### Enhancements
- **User Instructions** – Updated ACF field instructions and preview titles to guide users to save post after cropping
- **Preview Messaging** – Added "(Save post to see updated crop)" notice to preview title for clarity

### Technical Notes
- Event handler infrastructure in place (`cropThumbnailModalClosed`) but browser caching prevents immediate preview refresh
- Documented preview refresh limitation in technical notes
- Implemented user-friendly workaround with clear messaging instead of unreliable auto-refresh

---

## [2.5.2] – October 18, 2025

### New Features
- **Crop-Thumbnails Plugin Integration** – Replaced custom crop tool with proven third-party Crop-Thumbnails plugin (30k+ active installs) for professional image cropping interface. Users can now crop featured images to 4:3 ratio for grid/card display while keeping full images on single funeral pages.
  - Solves portrait photo issues in grid layouts (heads being cut off)
  - Professional crop editor with zoom, pan, and visual guides
  - Mobile-friendly interface with undo/redo functionality
  - No custom JavaScript bugs or coordinate calculation issues
  - Custom button text: "Crop for Grid/Cards"

### Bug Fixes
- **Grid Crop Display** – Fixed cropped images not displaying in grid layouts (modern, elegant, shortcodes)
- **Image Retrieval Method** – Changed from `get_the_post_thumbnail_url()` to `wp_get_attachment_image_url()` for proper crop size detection
- **Custom Crop Tool Issues** – Removed buggy custom zoom crop coordinate calculations that caused image distortion
- **Crop Preview Display** – Fixed side-by-side preview not showing cropped images by using PHP-generated URLs and AJAX refresh
- **Event Handler Fix** – Corrected Crop-Thumbnails event from `cropThumbnailModalClose` to `cropThumbnailModalClosed` for proper preview refresh

### Enhancements
- **Side-by-Side Preview** – Shows both full image and cropped grid version in a comparison view below the crop button for instant visual feedback
- **Improved Button Styling** – Crop-Thumbnails button now styled consistently with WordPress admin interface (blue button with proper spacing)
- **Thumbnail Click Opens Crop** – Clicking the featured image thumbnail now opens the Crop-Thumbnails modal instead of media library for better UX
- **Preview Update Messaging** – Clear instructions that users should save the post after cropping to see the updated preview (due to browser caching)
- **Automatic Cache Purging** – Image crops now trigger cache purge automatically when Weave Cache Purge Helper plugin is active
- **Crop-Thumbnails Configuration** – Added setup documentation in DEVELOPER.md for configuring which image sizes appear in crop modal (Settings → Crop-Thumbnails)
- **Cache Integration Examples** – Added cache purge hook examples for WP Rocket, W3 Total Cache, and LiteSpeed Cache

### Technical Notes
- **Custom ImageCropHandler disabled** - Initialization commented out in `src/Plugin.php` to prevent conflicts
- **Image size registration** - `wfn-grid-crop` (800x600, 4:3) now registered in `includes/class-acf.php` via `wfn_register_image_sizes()`
- Crop-Thumbnails plugin provides all cropping functionality - no custom crop modal
- Filter `crop_thumbnails_button_text` customizes button label to "Crop for Grid/Cards"
- Filter `crop_thumbnails_image_sizes` explicitly exposes `wfn-grid-crop` to Crop-Thumbnails plugin
- Filter `crop_thumbnails_size_label` provides friendly name "Grid Crop (4:3)" in crop interface
- Action `crop_thumbnails_after_crop` triggers cache purge when Weave Cache Purge Helper is active
- Generic action `wfn_after_image_crop` available for other cache plugin integrations
- AJAX endpoint `wfn_get_image_urls` retrieves proper WordPress-generated URLs for both full and cropped sizes
- Side-by-side comparison uses PHP-generated URLs passed to JavaScript for accurate image detection
- **Preview refresh limitation**: Browser caching prevents instant preview updates after cropping; users must save post to see updated crop (event handler ready but timing/caching prevents immediate update)
- User instructions updated to clarify that saving post is required to see updated preview
- ImageCropHandler class preserved in codebase for reference but not instantiated

---

## [2.5.1] – October 18, 2025

### UX Improvements
- **Media Library Tab Default** – Media uploader now respects user's last tab choice (Upload vs Library) for better workflow continuity
- **Bottom Publish Button** – Added duplicate Update/Publish button at bottom of post editor for improved user experience on long forms

---

## [2.5.0] – October 16, 2025

### Technical Improvements
- **Google Maps API Modernisation** – Updated Google Maps integration to use modern async loading pattern for improved performance while maintaining full backward compatibility with existing API keys.
- **Image Crop & Zoom Feature** – Enhanced image cropping tool with zoom functionality and coordinate calculations for precise control over featured image display

#### What Changed
- ✅ Replaced deprecated callback-based loading (`callback=initWFNGoogleMaps`) with `loading=async` parameter
- ✅ Improved page load performance with non-blocking async script loading
- ✅ Added zoom controls to image crop interface with real-time coordinate tracking

---

## [2.4.12] – October 2025

### New Features
- **Image Crop Tool** – Added user-controlled image cropping for funeral notice featured images. Solves portrait photo issues in grid layouts (heads being cut off).
  - Full original image displays on single funeral pages
  - Custom 4:3 cropped version (800x600) displays on grid/list pages
  - Live preview with draggable crop area
  - Side-by-side comparison view (toggled via button)
  - Click thumbnail to open crop modal after uploading

---

## [2.4.10] – October 2025

### Bug Fixes
- **Video Modal Display** – Fixed video modal CSS/JS assets not loading on frontend due to redundant module enabled check

### Technical Changes
- Removed `$enabled_modules['video']` check from `VideoModule::enqueue_frontend_assets()` - license validation in TemplateManager is sufficient gatekeeper
- Changed `Dashboard.php` video module to always show as enabled (controlled by license, not toggle)
- Video module now fully license-gated with no manual enable/disable toggle

---

## [2.4.7] – October 2025

### New Features
- **Shortcode IDs Parameter** – Added `ids` parameter to display specific funeral notices by post ID (e.g., `[funeral_notices ids="123,456"]`)
- **Shortcode Exclude Parameter** – Added `exclude` parameter to exclude specific posts from shortcode queries (e.g., `[funeral_notices exclude="123,456"]`)

### Improvements
- **SEOPress Columns** – Automatically remove SEOPress admin columns (noindex, nofollow, title, desc) from funeral notice list view for cleaner interface

---

## [2.4.6] – October 2025

### Bug Fixes
- **License Field Display** – Video upload field now properly hidden when no premium license is active (replaced with minimal upgrade message)
- **Timezone Accuracy** – Fixed "Last verified" timestamp on license page showing incorrect time due to timezone conversion issue

### Technical Changes
- Replaced ACF `disabled` parameter with conditional field registration in `FieldGroupManager.php`
- Added `current_time('timestamp')` to `human_time_diff()` for accurate timezone-aware comparisons in `LicenseModule.php`

---

## [2.4.4] – October 2025

### Bug Fixes
- **Video Deletion** – Fixed videos not being removed from Bunny CDN when funeral posts are deleted
- **Video Field Visibility** – Video upload field now completely hidden (not just disabled) when premium license is inactive

---

## [2.4.1] – October 2025

### Bug Fixes
- **Streaming Service Detection** – Fixed iStream URLs incorrectly showing OneRoom embed containers
- **Template Logic Simplification** – Simplified all template modes (current, modern, elegant, firehawk) to trust StreamingDetector auto-detection
- **Migration Improvements** – Enhanced TemplateManager to prefer auto-detected streaming service over stored values

#### Technical Notes
- Updated `src/Streaming/StreamingDetector.php` with improved service-specific button text
- Added "Open in YouTube/Vimeo" links to embedded videos
- Modified `src/Templates/TemplateManager.php` to prioritize auto-detected service types
- All 4 template modes now use consistent streaming detection logic

---

## [2.4.0] – October 2025


### UX & Interface Improvements
- **Date Range Picker Update** – Replaced dual date fields with a modern, accessible date range picker using Flatpickr.
Improves usability, keyboard navigation, and consistency with the plugin’s design system.
- **Load More Button** – Added AJAX “Load More” functionality to replace pagination.
Keeps search filters active, includes loading feedback, and hides automatically when all results are shown.
- **Layout & UI Refinements** – Adjusted grid spacing for all layouts, simplified search box styling, and improved overall visual balance.

#### Technical Notes
- Integrated Flatpickr v4.6.13 with lazy-loaded assets.
- Improved compatibility for date handling and hidden input fields.
- Unified CSS variables and hover states.
- Updated VideoModule configuration checks for both old and new constant names.


---

## [2.3.0 - 2.3.2] – October 2025

### Major Updates
- **🎥 Premium Video Hosting (Freemium Launch)** – Introduced cloud-hosted video slideshows for funeral pages. This premium-only feature allows uploading and streaming of memorial videos via secure CDN delivery.
- **📊 Anonymous Analytics System** – Added opt-out analytics to gather anonymous, non-personal data on plugin usage. Helps improve performance and feature development across sites.
- **📍 Default Venue Setting** – New admin option to pre-fill a default venue for new funeral notices.

### Enhancements
- Improved text labels for tributes and celebration messages.
- Smarter notice ordering by service date, with fallback to publish date.
- CSS refinements for consistent layout and hover effects.

---

## [2.2.x] – September 2025

### Video & Streaming Improvements
- Added **Premium Video Module** with licensing validation.
- Introduced **clean admin interface** for video uploads and management.
- Streaming reliability improved — Vimeo, YouTube, and OneRoom links display correctly.
- Offloaded video hosting to Bunny CDN for speed and cost efficiency.

### Performance
- Conditional loading of CSS/JS only on notice-related pages.
- Query caching, lazy loading, and asset optimisation across modules.

### UI/UX
- Refined typography and button styling for a unified design system.
- Mobile-friendly search with improved accessibility and date filtering.
- Added “Send a tribute to the family” phrasing for a more personal tone.

### Admin & Role Integration
- Enhanced compatibility with HK Funeral Suite roles (`funeral_staff`, `funeral_manager`).
- Improved permissions and visibility for editing and managing notices.

---

## [2.0.0 – 2.1.x] – 2024–2025

### Architectural Rewrite
- Complete refactor to **PHP 8+ modular architecture**.
- Core modules introduced: Settings, Layouts, Search, Styling, and Performance.
- Template hierarchy rebuilt with full theme override support.
- Integrated **ACF Pro** field groups and migration utilities.

### Functional Enhancements
- Smart ordering of funeral notices by service and publish date.
- Live AJAX search and filtering for faster navigation.
- Built-in caching and lazy-loading systems for better speed.

### Design
- Four professional layouts (Modern, Elegant, Minimal, Firehawk clone).
- Styling module with live preview and colour control.
- Accessibility and mobile UX improvements.

---

## Earlier Versions (1.x – 2023)

- Initial release with ACF integration, livestream link support, and early Firehawk Tributes compatibility.
- Added OneRoom, Vimeo, and YouTube streaming options.
- Introduced SEOPress integration for meta titles and noindex options.
- Progressive migration to modular design in preparation for 2.0.

---

### Summary of Evolution
| Version | Focus | Key Additions |
|----------|--------|---------------|
| 2.4.0 | Modern UX Enhancements | Flatpickr date picker, grid spacing, search integration |
| 2.3.0 | Freemium + Analytics | Cloud video hosting, licensing, analytics system |
| 2.2.x | Video Hosting Module | CDN integration, admin video tools |
| 2.1.x | UX Improvements | Smart ordering, tribute messaging |
| 2.0.x | Major Rewrite | Modular architecture, caching, new layouts |
| 1.x | Foundations | Livestreams, ACF fields, base templates |

---

*Developed by Weave Digital Studio for HumanKind — supporting funeral homes across New Zealand and Australia.*

