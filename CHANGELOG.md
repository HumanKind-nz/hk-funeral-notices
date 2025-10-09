# HumanKind Funeral Notices — Condensed Changelog

This changelog summarises the key improvements, fixes, and features added to the HumanKind Funeral Notices plugin. For full technical details, refer to the complete CHANGELOG.md file.

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

