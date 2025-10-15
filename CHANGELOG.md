# HumanKind Funeral Notices — Condensed Changelog

This changelog summarises the key improvements, fixes, and features added to the HumanKind Funeral Notices plugin. For full technical details, refer to the complete CHANGELOG.md file.

---

## [2.4.12] – October 2025

### New Features
- **Image Crop Tool** – Added user-controlled image cropping for funeral notice featured images. Solves portrait photo issues in grid layouts (heads being cut off).
  - Full original image displays on single funeral pages
  - Custom 4:3 cropped version (800x600) displays on grid/list pages
  - Live preview with draggable crop area
  - Side-by-side comparison view (toggled via button)
  - Click thumbnail to open crop modal after uploading

#### Technical Notes
- Added `ImageCropHandler.php` for backend crop management
- Custom image size `wfn-grid-crop` registered (800x600, 4:3 aspect ratio)
- REST API integration for attachment data loading
- Event capturing to intercept WordPress media handlers
- Physical cropped files generated in same directory as original
- Updated shortcode to use `wfn-grid-crop` size for grid/list displays
- Removed CSS `object-position` hacks from modern and elegant layouts

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

