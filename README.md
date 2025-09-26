![HumanKind Funeral Notices](https://weave-hk-github.b-cdn.net/humankind/plugin-header.png)

# HumanKind Funeral Notices

A modern WordPress plugin for funeral homes to publish and display funeral notices with beautiful layouts, reliable streaming support, and a simple admin experience.

---

### Latest Updates (September 2025)
- **🎥 Vide Upload for Slidehows**: Hosted Videos added to funeral notices for Premium License Holder
- **📝 Tribute Text Update**: Changed to "Send a tribute to the family" for more personal messaging
- **🎉 Celebration Text**: Beautiful tribute message feature with customizable templates
- **📅 Smart Ordering**: Funeral notices sorted by service date with publish date fallback
- **📺 Better Streaming**: "View in new window" links for all video embeds
- **🎨 Layout Fixes**: Centered single pages, consistent button hovers, proper CSS usage
- **⚡ Performance**: Optimized CSS loading and removed legacy code

### New Features
- **4 Professional Layout Options**: From minimal to elegant funeral styling
- **Advanced Search & Filtering**: Real-time AJAX search with date range filtering
- **Visual Styling Controls**: Custom colour picker with alpha support
- **Performance Optimisation**: Caching system, asset optimisation, and lazy loading
- **Enhanced Shortcode System**: Powerful shortcodes with extensive customisation options

---

## 📋 Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Migration from v1.x](#migration-from-v1x)
- [Module Overview](#module-overview)
- [Shortcode Usage](#shortcode-usage)
- [Styling & Customisation](#styling--customisation)
- [Template System](#template-system)
- [Developer Documentation](#developer-documentation)
- [Support](#support)

---

## ✨ Features

### 🎨 **Professional Layouts**
- **5 Layout Options**: Current, Firehawk, Modern, Elegant, Minimal
- **4 Card Styles**: Standard, Elevated, Outlined, Minimal
- **Responsive Grid System**: 1-4 columns with intelligent breakpoints
- **Mobile-First Design**: Optimised for all screen sizes

### 🔍 **Advanced Search & Filtering**
- **Real-time AJAX Search**: Instant results without page refresh
- **Date Range Filtering**: Custom date ranges with calendar picker (hidden on mobile for better UX)
- **Text Search**: Names, content, and ACF field searching
- **Location Filtering**: Integration with funeral location taxonomy
- **Autocomplete Suggestions**: Enhanced user experience

### 🎨 **Visual Styling System**
- **Custom Colour Picker**: Alpha transparency support for advanced theming
- **Typography Controls**: 10+ professional font options including Google Fonts
- **Layout Customisation**: Spacing, borders, shadows, and aspect ratios
- **Live CSS Generation**: Real-time preview of styling changes

### ⚡ **Performance Optimisation**
- **Smart Caching System**: Query caching with automatic purge
- **Asset Optimisation**: CSS/JS minification and deferring
- **Lazy Loading**: Images and embeds load on demand
- **Database Optimisation**: Indexed queries and cleanup tools
- **Performance Monitoring**: Built-in performance testing tools

### 🔧 **Admin Excellence**
- **Professional Dashboard**: Module management with toggle controls
- **Individual Module Settings**: Dedicated configuration pages
- **Contextual Help**: Descriptions, examples, and best practices
- **Status Indicators**: Clear active/inactive states for all features
- **Modern UI**: Professional styling matching premium plugins

---

## 📦 Installation

### System Requirements

**Critical Dependencies:**
- 🔧 **PHP 8.0+** - Modern PHP features and strict typing
- 🏠 **WordPress 6.0+** - Latest WordPress functionality
- 💎 **Advanced Custom Fields PRO** - Essential for all field functionality
- 🔗 **ACF Extended** (optional) - Enhanced field types and features

**Important:** The free ACF plugin is **not sufficient**. ACF Pro is required for:
- ✅ Group fields (funeral data organization)
- ✅ Google Maps fields (location mapping)  
- ✅ File upload fields (service sheets)
- ✅ Advanced field layouts
- ✅ ACF Extended integration (enhanced field types)

### Fresh Installation

1. **Install ACF Pro** first - [Purchase here](https://www.advancedcustomfields.com/pro/)
2. **Download** the HumanKind Funeral Notices plugin ZIP file
3. **Upload** to WordPress via Plugins > Add New > Upload
4. **Activate** the plugin
5. **Visit** Funeral Notices > Dashboard to configure modules

### Requirements Check
The plugin automatically verifies:
- ✅ PHP 8.0 or higher
- ✅ WordPress 6.0 or higher  
- ✅ ACF Pro plugin installed and active
- ⚠️ **Admin notice displayed if ACF Pro is missing**

---


## 🏗️ Module Overview

The plugin's functionality is organized into 5 professional modules:

### 1. 🔧 **Settings Module**
- **Purpose**: Core plugin configuration
- **Features**: Display mode configuration, URL structure management, shortcode documentation
- **Admin Page**: `Funeral Notices > Settings`

### 2. 🎨 **Layouts Module** 
- **Purpose**: Template and layout management
- **Features**: 5 layout options, responsive grid settings, card style variants
- **Admin Page**: `Funeral Notices > Layouts`

### 3. 🔍 **Search Module**
- **Purpose**: Advanced search and filtering
- **Features**: AJAX search, date filtering, autocomplete, location filtering
- **Admin Page**: `Funeral Notices > Search`

### 4. 🎨 **Styling Module**
- **Purpose**: Visual customisation and theming
- **Features**: Colour schemes, typography controls, custom CSS, layout spacing
- **Admin Page**: `Funeral Notices > Styling`

### 5. ⚡ **Performance Module**
- **Purpose**: Speed optimisation and caching
- **Features**: Query caching, asset optimisation, lazy loading, database cleanup
- **Admin Page**: `Funeral Notices > Performance`

Each module can be enabled/disabled independently and includes comprehensive settings and documentation.

---

## 📝 Shortcode Usage

### Basic Usage
```php
// Display all funeral notices with default settings
[funeral_notices]

// Modern layout with 3 columns (layout and style parameters work identically)
[funeral_notices layout="modern" columns="3"]
[funeral_notices style="modern" columns="3"]

// Other layout options
[funeral_notices layout="elegant" columns="3"]
[funeral_notices layout="firehawk" columns="3"]
[funeral_notices layout="minimal" columns="1"]

// Show only future funerals with search
[funeral_notices type="future" show_search="yes"]
```

### Available Parameters

| Parameter | Options | Default | Description |
|-----------|---------|---------|-------------|
|| `layout`/`style` | `firehawk`, `modern`, `elegant`, `minimal` | `modern` | Layout template |
| `type` | `all`, `future`, `archived`, `today`, `this_week`, `this_month` | `all` | Filter by date |
| `columns` | `1`, `2`, `3`, `4` | `3` | Number of columns |
| `per_page` | number | `12` | Items per page |
| `show_search` | `yes`, `no` | `yes` | Show search form |
| `card_style` | `standard`, `elevated`, `outlined`, `minimal` | `standard` | Card appearance |
| `show_pagination` | `yes`, `no` | `yes` | Show pagination |

### Advanced Examples

```php
// Elegant memorial gallery
[funeral_notices layout="elegant" columns="2" card_style="elevated" type="all"]

// Minimal list view for archives
[funeral_notices layout="minimal" columns="1" show_search="no" per_page="20"]

// Firehawk-compatible display
[funeral_notices layout="firehawk" columns="3" show_pagination="yes"]
```

---

## 🎨 Styling & Customisation

### CSS Customisation

Add custom CSS in the Styling Module:

```css
/* Custom funeral notice styling */
.wfn-funeral-card {
	border-left: 4px solid #1f4b8f;
}

.wfn-funeral-card:hover {
	transform: scale(1.02);
}

/* Mobile-specific styles */
@media (max-width: 768px) {
	.wfn-layouts-grid {
		gap: 15px;
	}
}
```

---

## 🔧 Template System

### Template Hierarchy

The plugin uses a flexible template system:

```
templates/
├── modes/
│   ├── current/          # Beaver Builder compatibility
│   ├── firehawk/         # Firehawk Tributes compatible
│   ├── modern/           # Contemporary memorial design
│   ├── elegant/          # Formal funeral styling
│   └── minimal/          # Clean list view
└── partials/
	├── search-form.php   # Advanced search form
	├── funeral-card.php  # Reusable card component
	└── pagination.php    # Modern pagination
```

### Custom Templates

Override plugin templates in your theme:

1. Create `funeral-notices/` folder in your theme
2. Copy template files from plugin
3. Modify as needed

Example theme structure:
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

## 👨‍💻 Developer Documentation

### Hooks & Filters

```php
// Modify available layouts
add_filter('wfn_available_layouts', function($layouts) {
	$layouts['custom'] = [
		'name' => 'Custom Layout',
		'description' => 'My custom layout',
		'template' => 'custom-template.php'
	];
	return $layouts;
});

// Add custom search filters
add_filter('wfn_search_meta_query', function($meta_query, $search_params) {
	// Add custom search logic
	return $meta_query;
}, 10, 2);

// Modify card data before rendering
add_filter('wfn_funeral_card_data', function($data, $post_id) {
	// Customise card data
	return $data;
}, 10, 2);
```

### Action Hooks

```php
// Before funeral notice display
add_action('wfn_before_funeral_display', function($post_id) {
	// Custom logic before display
});

// After search form render
add_action('wfn_after_search_form', function() {
	// Add custom search elements
});

// Module activation
add_action('wfn_module_activated', function($module_id) {
	// Handle module activation
});
```

### Database Schema

The plugin uses WordPress native tables plus:

```sql
-- ACF fields are stored in standard wp_postmeta
-- Custom taxonomy for locations in wp_terms/wp_term_taxonomy
-- Plugin settings in wp_options with 'wfn_' prefix
```

### Performance Considerations

- **Caching**: Smart query caching with auto-purge on data changes
- **Asset Loading**: CSS/JS loaded only when needed
- **Database**: Optimised queries with proper indexing
- **Images**: Lazy loading and responsive image support

---

## 🛠️ Troubleshooting

### Common Issues

**Plugin not loading modules**
- Check PHP version (8.0+ required)
- Verify ACF Pro is active
- Check for plugin conflicts

**Styling not applying**
- Clear any caching plugins
- Check CSS loading in browser developer tools
- Verify module is enabled in dashboard

**Search not working**
- Check AJAX errors in browser console
- Verify Relevanssi plugin compatibility
- Check WordPress REST API functionality

**Performance issues**
- Enable Performance Module caching
- Optimise images and compress assets
- Check for plugin conflicts

### Debug Mode

Enable debug logging by adding to wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WFN_DEBUG', true);
```

---

## 🆘 Support

### Documentation
- **Plugin Dashboard**: Comprehensive help within WordPress admin
- **Shortcode Generator**: Built-in examples and documentation
- **Module Help**: Contextual help on each module page

### Getting Help
- **Email Support**: [support@weave.co.nz]
- **Documentation**: [https://github.com/HumanKind-nz/hk-funeral-notices]
- **GitHub Issues**: [https://github.com/HumanKind-nz/hk-funeral-notices]

### Before Contacting Support
1. Check the built-in documentation
2. Review this README
3. Check for plugin conflicts
4. Test on staging site
5. Gather error logs and screenshots

---

## 🔄 Changelog

See [CHANGELOG.md](CHANGELOG.md)

---

## 📄 License

This plugin is licensed under the GPLv2 License. See the [LICENSE](LICENSE) file for details.

---

## 🙏 Credits

**Development**: Weave Digital Studio  
**Special Thanks**: The WordPress community and ACF developers

---

*Built with ❤️ for funeral homes who serve families with compassion and dignity.*
