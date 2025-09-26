# Changelog

All notable changes to the HumanKind Funeral Notices plugin will be documented in this file.

## [2.2.4] - 2025-09-26

### 🚀 **Premium Video Hosting**

#### **Added**
- 📺 **Premium Licensing System** - Integrated professional license management for video streaming features
  - Secure license validation with humankindwebsites.com
  - Dynamic token management for secure updates
  - Automatic cleanup of expired licenses
- 🎥 **Video Upload Protection** - Video slideshow uploads now require valid premium license
  - Clean admin interface with upgrade prompts for unlicensed users
  - CSS-based hiding of upload interface when unlicensed
  - Professional error messaging and upgrade paths

#### **Changed**
- 🔧 **Update System** - Switched to dynamic token-based updates for licensed installations
- 🎛️ **Admin Interface** - Premium modules (Video Slideshows) hidden when unlicensed
- ⚙️ **Video Module** - Notification settings now default to disabled

#### **Removed**
- 🧹 **Development Bypasses** - Removed development license bypass functionality
- 🔗 **GitHub Links** - Removed GitHub link from plugin action links

## [2.1.3] - 2025-09-24

### 🎯 **Text Update**

#### **Changed**
- 📝 **Tribute Link Text** - Updated all "Send a Tribute" links to "Send a tribute to the family"
  - More personal and family-focused messaging
  - Applied consistently across all single templates (Current, Modern, Elegant, Firehawk)
  - Includes both active links and placeholder text when URL not configured

## [2.1.0] - 2025-09-23

### 🎨 **UI Refinements**
- 📺 **Streaming Icons** - Improved streaming icon styling and positioning:
  - Refined icon sizes and spacing across all grid layouts
  - Enhanced visibility and positioning of streaming indicators
  - Consistent icon styling when date/time/venue are hidden

## [2.1.1] - 2025-09-24

### 🎨 **UI/UX Improvements**

- 📝 **Location Field Labels** - Improved clarity and user-friendliness of location selection fields in ACF:
  - "No location" → "Display No Location"
  - "Use saved location" → "Use one of our saved Venues"
  - "Enter custom address" → "Enter a Custom Location/Address"

## [2.1.0] - 2025-09-23

### 🎯 **Performance, Streaming & User Experience Update**

#### **Added**
- 🎉 **Celebration Text Feature** - Restored from v1 with enhancements
  - Default: "Please join us in celebrating [First Name] [Last Name]'s life"
  - New dedicated CSS file for consistent presentation

- 📅 **Smart Ordering** - Funeral notices now intelligently sorted
  - Primary sort by service date (furthest dates appear first)
  - Secondary sort by publish date for notices without service dates
  - Applies to both grid layouts and archive pages
  - Ensures upcoming services are more prominent

- 📺 **Streaming Improvements**
  - Added "View in new window" link for all Vimeo/YouTube embeds across all templates
  - Helps when videos have embedding disabled in their sharing settings
  - Consistent with OneRoom streaming which already had external links

#### **Enhanced**
- ⚡ **CSS Performance Optimisation** - Styles now load conditionally
  - CSS only loads on pages with funeral notices (not site-wide)
  - Detects: single funeral notices, shortcode pages, archives, taxonomy pages
  - Significantly reduces unnecessary CSS loading across the site
  - Fixed broken CSS paths that were missing plugin directory name
  - Removed duplicate CSS loading and legacy unused styles
  
- 🎨 **Simplified Color Management** - Streamlined Visual Styling module
  - Removed preset color schemes (Professional Blue, Elegant Navy, etc.)
  - Focus on custom colors that integrate with theme
  - Cleaner, less overwhelming admin interface
  - Retained full custom color control and CSS variable system

#### **Fixed**
- 🔗 **CSS Path Issues** - Corrected broken paths in SearchModule using WFN_PLUGIN_URL constant
- 🏷️ **Field Naming Clarity** - Renamed ACF fields for better understanding:
  - `intro_text` → `memorial_header` (top of page text)
  - New `celebration_text` field for the celebration message
- 🎨 **Styling Consistency**
  - Fixed button hover colors - all now use `var(--wfn-color-secondary)` for consistency
  - Removed CSS override that was forcing service date/time to wrong color in modern grid
  - Service info now correctly displays in primary color (green) instead of text-secondary (gray)
- 🐛 **Streaming Display** - Fixed Vimeo streams not showing when incorrectly marked as private

#### **Technical**
- Added `should_load_styles()` helper method in all style-loading modules
- Consistent conditional loading pattern across LayoutsModule, SearchModule, and StylingModule
- Improved code organization and reduced complexity
- Enhanced content detection logic for better layout decisions
- Removed problematic CSS generation from StylingModule that was overriding layout styles

## [2.0.9] - 2025-09-22

### 🎉 **Feature-Rich Update**

#### **Added**
- 🎆 **Customizable Introduction Text** - Replace "In loving memory of" with any custom text
  - Site-wide default setting in Content Settings
  - Per-notice override capability
  - Leave blank to hide intro text entirely
- 📝 **ACF Field Improvements**
  - Clearer field descriptions and instructions
  - Updated streaming instructions to reflect actual functionality
  - Death year now defaults to current year (2025)

#### **Enhanced**
- 🔍 **Search Form Improvements**
  - Date fields hidden on mobile devices (≤768px) for cleaner UX
  - Better touch targets and input sizing
  - iOS zoom prevention with 16px font size
- 🔐 **Permission System Updates**
  - Main menu now accessible to funeral_staff users
  - Dashboard redirects non-admin users to All Funeral Notices
  - Settings pages remain admin-only for security

#### **Fixed**
- 🐛 **Menu Access Issues** - funeral_staff can now properly access funeral notices
- 🎯 **Template Updates** - All single templates now use new intro_text field structure
- 💾 **Settings Save Issue** - Default intro text now properly saves in settings
- 🔧 **Intro Text Fallback** - Fixed templates to properly use site-wide default when individual notice intro text is empty

## [2.0.8] - 2025-09-22

### 👥 **User Role Integration**

#### **Added**
- 👥 **Role Integration** - Added seamless integration with HK Funeral Suite's `funeral_staff` and `funeral_manager` roles
- 🔐 **Permission Management** - Staff can manage their own notices, managers can manage all notices
- 🛡️ **Fallback Roles** - Creates basic roles if HK Funeral Suite is not installed
- 📋 **Menu Visibility** - Settings hidden from basic staff, visible to managers

---

## [2.0.7] - 2025-09-22

### 🚨 **Critical Bug Fix**

#### **Fixed**
- 🔥 **Fatal Error** - Removed reference to non-existent `includes/migrations.php` file that was causing plugin to crash
- ✅ **Plugin Activation** - Plugin now loads correctly without fatal errors

#### **Removed**
- 🔧 **WP-CLI Commands** - Removed redundant WP-CLI migration commands (now handled by dedicated migration plugin)
- 🧹 **Code Cleanup** - Removed unnecessary includes for cleaner codebase

---

## [2.0.6] - 2025-09-22

### 📱 **Mobile UX Improvements**

#### **Enhanced**
- 🔍 **Mobile Search Experience** - Date range fields now hidden on mobile devices (≤768px) for cleaner interface
- 📱 **Touch Optimization** - Improved touch targets and input sizing for mobile devices
- 🎯 **Focused Search** - Mobile users get streamlined name-only search for faster access
- ⚡ **Performance** - Reduced form complexity on mobile for better performance

#### **Fixed**
- 🐛 **iOS Zoom Issue** - Input font size set to 16px to prevent unwanted zoom on focus
- 📐 **Layout Issues** - Fixed cramped search form layout on small screens

---

## [2.0.5] - 2025-09-22

### 🗑️ **Code Cleanup & Optimization**

#### **Removed**
- ❌ **Gallery Layout** - Completely removed unused gallery card view and all related code
- 🧹 **Cleaned Files** - Removed `gallery-grid.css` and all gallery-related CSS/JS
- 📦 **Reduced Size** - Smaller plugin footprint with removal of unused components

#### **Enhanced**
- ✨ **Cleaner Codebase** - Removed gallery references from:
  - LayoutsModule.php
  - FuneralNoticesShortcode.php
  - StylingModule.php
  - SettingsModule.php
  - Archive templates
- 🎯 **Streamlined Options** - Simplified layout choices to: Current, Firehawk, Modern, Elegant, Minimal

---

## [2.0.4] - 2025-09-21

### ✨ **Feature Enhancement**

#### **Added**
- 📺 **Streaming Icon Indicator** - Modern grid layout now displays a streaming icon when live streaming is available for a funeral service
- 🎨 **Visual Enhancement** - Icon uses primary theme color and includes hover effects for better user interaction
- ♿ **Accessibility** - Streaming icon includes descriptive title attribute for screen readers

---

## [2.0.3] - 2025-09-21

### 🐛 **Layout & Styling Fixes**

#### **Fixed**
- 🎯 **Grid Layout Alignment** - Fixed modern, elegant, and gallery grid layouts not aligning with search bar edges
- 📏 **Grid Spacing** - Reduced excessive gaps between cards from 1.5rem to 1rem for more polished appearance
- 🎨 **Card Padding** - Optimized internal card spacing and reduced bottom margins after service times
- 🏷️ **Header Element Conflicts** - Changed `<header>` to `<div>` in current template to prevent duplicate header styling issues
- ⚡ **CSS Override Conflicts** - Fixed StylingModule generating CSS with `!important` declarations that overrode layout spacing controls

#### **Enhanced**
- 📱 **Responsive Design** - Improved spacing across all breakpoints (desktop, tablet, mobile)
- 🎛️ **Visual Styling Module** - Layout CSS now works harmoniously with color scheme controls
- ✨ **Professional Polish** - All grid layouts now have consistent, proportional spacing

---

## [2.0.2] - 2025-09-21

### 🐛 **Bug Fixes & Improvements**

#### **Fixed**
- 🔧 **Modern Grid Button Visibility** - Fixed "View Details" button having same color for text and background
- 🎨 **Theme Integration** - Removed hardcoded color from `.wfn-current-name` to allow theme h1 styling
- 📂 **Code Organization** - Moved migration code from main plugin file to dedicated `includes/migrations.php`
- 🗑️ **Admin Menu Cleanup** - Removed unused Address Migration menu item

#### **Enhanced**
- ♿ **Accessibility Improvements**:
  - Enhanced search form with proper labels, ARIA attributes, and fieldset structure
  - Improved grid navigation with better semantic HTML
  - Removed redundant "View Details" text for cleaner screen reader experience
- 🏷️ **Admin Interface** - Updated plugin menu name from "Funeral Notices" to "HK Funeral Notices"
- 💾 **Migration Tools** - Added comprehensive site-to-site migration documentation with SQL scripts
- ✅ **Custom CSS Functionality** - Verified Visual Styling custom CSS feature works correctly

#### **Documentation**
- 📖 **Complete Migration Guide** - New `FUNERAL-NOTICES-SITE-MIGRATION-GUIDE.md` with step-by-step instructions
- 🔧 **WP All Import Helper** - Updated v2 PHP script for complex data migrations
- 🗂️ **File Organization** - Better separation of migration tools and documentation

---

## [2.0.0] - 2025-09-19

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
