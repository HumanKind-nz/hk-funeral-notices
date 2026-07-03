# CLAUDE.md — Agent Instructions for hk-funeral-notices

## What This Is

A WordPress plugin for professional funeral notice management, deployed on 40+ production funeral home sites. Built for HumanKind Funeral Websites (a Weave Digital Studio brand). Features responsive layouts, advanced search, video streaming (premium), and analytics.

This is NOT on wordpress.org — updates are delivered via a custom GitHub updater class.

For general WordPress development best practices, refer to the WordPress Agent Skills (`wp-plugin-development`, `wp-block-development`, `wp-rest-api`). This file covers project-specific conventions only.

## Architecture

- **PHP**: Hybrid system — modern namespaced classes in `src/` (modules, services, templates) + legacy classes in `includes/`
- **Namespace**: `HumanKind\FuneralNotices\` with SPL autoloader (no Composer)
- **Modules**: `ModuleInterface`/`BaseModule` pattern in `src/Modules/` — Settings, Layouts, Search, Styling, License, Video, Analytics
- **Services**: `LicenseService`, `BunnyStreamService`, `SupabaseService` in `src/Services/`
- **Templates**: 5 layout modes (modern, elegant, minimal, firehawk, classic) in `templates/modes/`
- **ACF Pro**: Required dependency — field groups registered via `src/FieldGroups/FieldGroupManager.php`
- **Build**: `@wordpress/scripts` — settings page built via `webpack.scripts.config.js`
- **Admin UI**: Moving to `@wordpress/components` React app (settings page)
- **Settings**: Moving to `register_setting` with `show_in_rest` → React reads/writes via `/wp/v2/settings`

### Data Flow

```
ACF field groups (src/FieldGroups/FieldGroupManager.php)
    ↓
Post meta (wfn_details_group_*)
    ↓
WP_Query (src/Templates/TemplateManager.php)        → frontend display
Module settings (get_option)                         → admin configuration
REST API /wp-json/hkfn/v1/ (src/API/VideoUploadAPI) → video upload
Supabase (src/Services/SupabaseService.php)          → anonymous analytics
```

### Key Directories

| Directory | Purpose |
|-----------|---------|
| `src/` | Modern namespaced PHP — modules, services, templates, shortcodes, API |
| `includes/` | Legacy integration classes — ACF, roles, query, SEOpress, licensing |
| `inc/` | Scaffold-pattern files — hooks, settings page, GitHub updater |
| `templates/` | PHP template files for 5 layout modes |
| `assets/` | CSS and JS (manually managed, no build step for frontend assets) |
| `src/js/settings/` | React settings app (built via webpack) |
| `build/` | Compiled JS/CSS output from `@wordpress/scripts` |

## Key Files to Modify

| Task | Files to Edit |
|------|---------------|
| Change module settings | `src/Modules/{Module}Module.php` |
| Change layout templates | `templates/modes/{layout}/` |
| Change search behaviour | `src/Modules/SearchModule.php`, `includes/class-query.php` |
| Change styling options | `src/Modules/StylingModule.php` |
| Add ACF fields | `src/FieldGroups/FieldGroupManager.php` |
| Change REST API | `src/API/VideoUploadAPI.php` |
| Change shortcode output | `src/Shortcodes/FuneralNoticesShortcode.php` |
| Update GitHub updater | `inc/github-updater.php` |
| Change plugin action links | `inc/hooks.php` |
| Change settings page (React) | `src/js/settings/` |
| Change settings registration | `inc/settings-page.php` |

## Build & Release

```bash
npm run build          # Build settings page (webpack)
npm run start          # Watch mode for development
npm run lint           # Lint JS and CSS
```

### Creating a Release

1. Bump version in `hk-funeral-notices.php` header and `package.json`
2. Update `CHANGELOG.md`
3. Commit: `git commit -am "Release v3.x.x"`
4. Tag: `git tag -a v3.x.x -m "Version 3.x.x"`
5. Push: `git push origin main --tags`
6. GitHub Actions creates the release automatically

## Coding Conventions

- **PHP prefix**: `hkfn_` for functions, `HKFN_` for constants, `HumanKind\FuneralNotices\` for namespaces
- **Language**: NZ English everywhere (organised, colour, licence, centre, optimised)
- **Standards**: WordPress Coding Standards (tabs, spaces inside parentheses, short arrays `[]`)
- **Escaping**: Always. `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- **Nonces**: On every admin form and AJAX handler
- **Text domain**: `hk-funeral-notices` (matches plugin slug)
- **Licence**: GPL-2.0-or-later
- **PHP minimum**: 8.1 — `declare(strict_types=1)` in all files
- **WordPress minimum**: 6.6
- **CSS classes**: `hkfn-` prefix
- **Script/style handles**: `hkfn-` prefix
- **Option keys**: `hkfn_` prefix (dual-read fallback from `wfn_` for migration)
- **Dependencies**: ACF Pro required. No Composer. No jQuery in new code.
- **Plugin action links**: "Settings" link on Plugins list screen via `inc/hooks.php`

### Option Key Migration

Options are migrating from `wfn_` to `hkfn_` prefix. Use the dual-read helper:

```php
// Reads hkfn_ first, falls back to wfn_ if not found
$value = hkfn_get_option( 'setting_name', $default );

// Writes always go to hkfn_ prefix
update_option( 'hkfn_setting_name', $value );
```

Old `wfn_` options linger harmlessly and will be cleaned up in a future version.

## Admin UI Rules

ALL new admin interfaces use `@wordpress/components`. No custom CSS. No hand-rolled HTML.

Import components like `ToggleControl`, `TabPanel`, `Card`, `Button`, `Notice` from `@wordpress/components`. The build process auto-detects these imports and adds `wp-components` as a script dependency.

**Never do this:**
- Write custom admin CSS files (components handle all styling)
- Use `<table class="form-table">` or classic admin HTML patterns
- Use Bootstrap, Tailwind, or jQuery UI for admin screens

## Development & Hosting

**Local dev:** WordPress Studio (Automattic desktop app) or blueprint.json in Playground. Edit in Nova or Cursor AI. Source in `~/Projects/`, symlink into `~/Studio/{site}/wp-content/plugins/`.

**GitHub org:** `HumanKind-nz` — repo: `hk-funeral-notices`

**Production:** NGINX via GridPane on Binary Lane VPS (NZ). Redis object caching. No Apache. PHP 8.1–8.4. BunnyCDN for static assets and plugin icons.

**Updates:** GitHub updater hooks into WordPress native update system — appears in Dashboard → Updates.

**ACF Pro:** Required on all 40+ sites. Field groups are stable and do not change. Future removal planned but not part of current work.

## What NOT to Change (Without Discussion)

- **ACF dependency** — deeply integrated across 40+ sites
- **Template system** — 5 layout modes with PHP templates are production-proven
- **Module system** — `ModuleInterface`/`BaseModule` pattern works well
- **Services layer** — `LicenseService`, `BunnyStreamService`, `SupabaseService` are well-structured
- **Shortcode system** — `[funeral_notices]` deployed across all sites
- **Post meta keys** — `wfn_details_group_*` keys are stored in 40+ databases
