# Changelog

All notable changes to the HumanKind Funeral Notices plugin will be documented in this file.

## [2.0.0] - 2025-10-19

### 🚀 **Major Plugin Rewrite - PHP 8.0+ Architecture**

Complete architectural overhaul with dual-system compatibility (legacy + modern) for seamless transitions on production sites.

#### **Added**
- ✨ **Modern PHP 8.0+ Architecture** with strict typing and namespaces (`WeaveStudios\FuneralNotices`)
- 🏗️ **Modular System** - 4 core modules (Settings, Layouts, Search, Styling) extending `BaseModule`
- 🎨 **6 Template Modes** - Current, Firehawk, Modern, Elegant, Gallery, Minimal layouts
- 🔍 **Advanced Search Module** with AJAX functionality and filtering
- 🎛️ **Professional Admin Dashboard** with FCRM-style interface
- 📱 **Dynamic Address System** - Auto-detects ACFE Pro vs native Google Maps
- 🔄 **Field Group Migration System** - Handles internal plugin upgrades automatically
- 📊 **Statistics Dashboard** - Post counts, status breakdown, recent activity
- ⚡ **Performance Optimizations** - Lazy asset loading, intelligent caching
- 🎨 **Advanced Styling Controls** - Alpha color pickers, live preview
- 📋 **Enhanced Shortcode System** with extensive parameters and layout options
- 🛠️ **Developer Tools** - Debug logging, migration utilities, validation systems

#### **Architecture**
- **Dual-System Design** - Legacy and modern systems run simultaneously
- **Singleton Pattern** - Main plugin coordinator (`src/Plugin.php`)
- **Module-Based** - Each feature as independent, toggleable module
- **Template Hierarchy** - Flexible template system with theme override support
- **Field Group Management** - Programmatic ACF field registration via code

#### **Compatibility**
- ✅ **Backward Compatible** - All existing data and templates work unchanged
- ✅ **WordPress 6.0+** and **PHP 8.0+** requirement
- ✅ **ACF Pro Integration** - Enhanced field types and conditional logic
- ✅ **Production Ready** - Deployed on 40+ funeral home websites

#### **Migration & Upgrade**
- 🔄 **Automatic Migration** - Seamless upgrade from v1.x with data preservation
- 📋 **Migration Guide** - Comprehensive documentation for site transfers
- ✅ **Data Validation** - Built-in tools to verify migration success
- 🛡️ **Rollback Support** - Safety mechanisms for upgrade issues

---

## [1.4.2] - 2024-01-27

#### Changed
- Updated json configuration

## [1.4.0] - 2024-01-20

#### Changed
- Updated funeral date for 2025

#### Added
- Added Vimeo Pro stream option

#### 1.3.2 / 2024-01-10

- Updated funeral date to 2024
- Streaming icon update

#### 1.3.0 / 2023-11-17

- FIXED Post Ordering Queries for Future Dates & Times on Funeral Notices Page

#### 1.2.2 / 2023-10-24

- IMPROVED Meta description tags for Funeral Notice Archive & SEOPress

#### 1.2.1 / 2023-10-12

- Removed potential of Funeral Notices from XML sitemap

#### 1.2.0 / 2023-10-12

- NEW Ability to schedule funerals
- NEW Meta Description and Title for Funeral Notice Archives
- NEW Location name setting box for meta title tag
- Updated no-index to just single Funeral Notices not archive

#### 1.1.3 / 2023-09-12

- FIX Wording for OneRoom link field
- Adjust spacing for streaming boxes

#### 1.1.1 / 2023-08-29

- NEW Added 'Other' stream option for links.

#### 1.0.5 / 2023-08-28

- FIXED: Error when ACF not installed. Added field check.

#### 1.0.3 / 2023-08-13

- Updated Streaming links & options
- Updated admin layout for streaming

#### 1.0.2 / 2023-06-23

- Updated Links to help documents

#### 1.0.0 / 2023-06-01

- Release candidate

#### 0.9.9

- Updated OneRoom Settings
- Fallback image option added to settings
- Replace image crop library
- Moved from ACF image to WP featured image
- Moved Funeral Notice from ACF to WP content field
- Field groupings updated
- Misc field admin style changes
- New conditional logic for streaming service
- Updated streaming logos
- Styling of Add Image button
- Preparation for 1.0 release

#### 0.9.5 / 2023-05-04

- Minor update to push updater

#### 0.9.4

- Added NoIndex with SEOPress

#### 0.9.3

- Added CSS for instruction videos / lightboxes

#### 0.9.2

- Added new setting page for Global settings
- Added noindexing for Funeral Notices
- Removed login redirect for Directors

#### 0.9.1 / 2023-05-01

- Added Option for OneRoom links as well as API/iFrame.
- Added 'x days from funeral' in Global settings
- Added Query loop to include funerals for x days after they have been
- Tidied up ACF admin CSS files
- Removed location ACF field code to use JS like others.

#### 0.9.0 / 2023-04-19

- Added Option to hide date/time.
- Private stream no longer show any stream info
- Added option to remove Intro to funeral notice
- Extra styling for radio button fields in backend

#### 0.8.8 / 2023-04-13

- New Fields Added and re-arranged

#### 0.8.6 / 2023-04-13

- Added ACF Fields

#### 0.7.1 / 2023-03-13

- Moved to github
