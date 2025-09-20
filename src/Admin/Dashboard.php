<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Admin;

/**
 * Admin Dashboard - FCRM-Style Interface
 * 
 * Modern admin dashboard matching FCRM Enhancement Suite design patterns
 * with Weave Funeral Notices branding and module management
 * 
 * @package WeaveStudios\FuneralNotices\Admin
 * @since 2.0.0
 */
class Dashboard {

    private string $plugin_name;
    private string $version;
    private array $modules;

    public function __construct(string $plugin_name, string $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->modules = $this->get_available_modules();
    }

    /**
     * Initialize admin dashboard
     */
    public function init(): void {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_menu', [$this, 'modify_menu_structure'], 999); // Run after menu is built
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_wfn_toggle_module', [$this, 'handle_module_toggle']);
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu(): void {
        // Main menu page - Dashboard with proper icon
        add_menu_page(
            'HumanKind Funeral Notices Dashboard',
            'HK Funeral Notices',
            'manage_options',
            'hk-funeral-notices',
            [$this, 'render_dashboard'],
            'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0iI2ZmZiIgdmlld0JveD0iMCAwIDEyMiAxMDYiPjxwYXRoIGZpbGw9IiNmZmYiIGQ9Ik02LjQgMTguN2MwIDE1LjUgNi41IDI5LjUgMTcgMzkuNSAyLTEuNiA0LjItMyA2LjQtNC40YTQ2LjcgNDYuNyAwIDAgMS0xNS44LTM1aDYuNWE0MC40IDQwLjQgMCAwIDAgMjIgMzYgNTEuOCA1MS44IDAgMCAwLTE5LjEgMTEuOGMtMTAuNSA5LjktMTcgMjQtMTcgMzkuNEgwYzAtMTcuMSA3LjEtMzIuNiAxOC41LTQzLjZBNjAuNiA2MC42IDAgMCAxIDAgMTguN2g2LjRabTEwMS42IDBhNDYuNyA0Ni43IDAgMCAxLTQ3IDQ2LjkgNDAuMiA0MC4yIDAgMCAwLTI1IDguNkE0MC40IDQwLjQgMCAwIDAgMjAuNSAxMDZIMTRhNDYuNyA0Ni43IDAgMCAxIDQ3LTQ2LjggNDAuMiA0MC4yIDAgMCAwIDI1LTguNyA0MC4xIDQwLjEgMCAwIDAgMTUuNS0zMS44aDYuNVpNNjEgNzMuMmM1LjkgMCAxMS40IDEuNSAxNi4xIDQuMmguMUEzMi45IDMyLjkgMCAwIDEgOTQgMTA2aC02LjVhMjYuNSAyNi41IDAgMCAwLTUzIDBoLTYuNEEzMi44IDMyLjggMCAwIDEgNjEgNzMuMVptNjEtNTQuNWMwIDE3LjEtNy4xIDMyLjYtMTguNSA0My43QTYwLjYgNjAuNiAwIDAgMSAxMjIgMTA2aC02LjRhNTQuMyA1NC4zIDAgMCAwLTIyLTQzLjYgNTQuMyA1NC4zIDAgMCAwIDIyLTQzLjZoNi40Wm0tMzUuNCA0OEE0Ni43IDQ2LjcgMCAwIDEgMTA4IDEwNmgtNi40YTQwLjQgNDAuNCAwIDAgMC0yMi0zNmMyLjQtLjggNC44LTIgNy4xLTMuMVptLTUyLjEtNDhhMjYuNSAyNi41IDAgMCAwIDUzIDBoNi40YTMyLjggMzIuOCAwIDAgMS00OSAyOC42aC0uMUEzMi45IDMyLjkgMCAwIDEgMjggMTguN2g2LjVaTTYxIDBhMTcuMiAxNy4yIDAgMSAxIDAgMzQuNEExNy4yIDE3LjIgMCAwIDEgNjEgMFptMCA2LjRhMTAuOCAxMC44IDAgMSAwIDEwLjggMTAuOGMwLTYtNC45LTEwLjgtMTAuOC0xMC44WiIvPjwvc3ZnPgo=',
            30
        );

        // Add New Funeral Notice submenu (first item)
        add_submenu_page(
            'hk-funeral-notices',
            'Add New Funeral Notice',
            'Add New Funeral Notice',
            'edit_posts',
            'post-new.php?post_type=funeral-notice'
        );

        // Add All Funeral Notices submenu (second item)
        add_submenu_page(
            'hk-funeral-notices',
            'All Funeral Notices',
            'All Funeral Notices',
            'edit_posts',
            'edit.php?post_type=funeral-notice'
        );

        // Add Venues submenu (third item, renamed from Locations)
        add_submenu_page(
            'hk-funeral-notices',
            'Funeral Venues',
            'Venues',
            'manage_options',
            'edit-tags.php?taxonomy=funeral-location&post_type=funeral-notice'
        );

        // Add Dashboard Settings at the bottom
        add_submenu_page(
            'hk-funeral-notices',
            'Dashboard Settings',
            'Dashboard Settings',
            'manage_options',
            'hk-funeral-notices-dashboard',
            [$this, 'render_dashboard']
        );
    }

    /**
     * Modify menu structure to remove duplicate and reorder
     */
    public function modify_menu_structure(): void {
        global $submenu;
        
        // Remove the first submenu item (duplicate of main menu)
        if (isset($submenu['hk-funeral-notices'])) {
            // Remove the first item which is automatically created by WordPress
            // This removes "Funeral Notices" from the submenu
            foreach ($submenu['hk-funeral-notices'] as $key => $item) {
                if ($item[2] === 'hk-funeral-notices') {
                    unset($submenu['hk-funeral-notices'][$key]);
                    break;
                }
            }
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook): void {
        // Only load on our admin pages
        if (!str_contains($hook, 'hk-funeral-notices') && !str_contains($hook, 'hkfn-')) {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'wfn-admin',
            plugin_dir_url(__FILE__) . '../../assets/css/admin/dashboard.css',
            [],
            $this->version
        );

        // Admin JavaScript
        wp_enqueue_script(
            'wfn-admin',
            WFN_PLUGIN_URL . 'assets/js/admin/dashboard.js',
            ['jquery'],
            $this->version,
            true
        );

        // Localize script for AJAX
        wp_localize_script('wfn-admin', 'wfnAdmin', [
            'nonce' => wp_create_nonce('wfn_admin_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            // Provide camelCase alias for compatibility with any legacy scripts
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'strings' => [
                'saving' => __('Saving...', 'weave-funeral-notices'),
                'saved' => __('Settings saved!', 'weave-funeral-notices'),
                'error' => __('Error saving settings.', 'weave-funeral-notices'),
            ]
        ]);
    }

    /**
     * Render main dashboard page
     */
    public function render_dashboard(): void {
        // Handle form submissions
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['wfn_nonce'], 'wfn_dashboard_action')) {
            $this->handle_dashboard_form();
        }

        ?>
        <div class="wfn-admin-dashboard">
            <?php $this->render_header(); ?>
            
            <div class="wfn-dashboard-content">
                <div class="wfn-container">
                    
                    <!-- Welcome Section -->
                    <div class="wfn-welcome-section">
                        <h2>Welcome to HumanKind Funeral Notices</h2>
                        <p>Manage your funeral notices with professional layouts, advanced search, and customizable styling. Enable the modules you need below.</p>
                    </div>

                    <!-- Modules Grid -->
                    <div class="wfn-modules-grid">
                        <?php foreach ($this->modules as $module_id => $module): ?>
                            <?php $this->render_module_card($module_id, $module); ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Quick Stats -->
                    <div class="wfn-stats-section">
                        <h3>Quick Statistics</h3>
                        <div class="wfn-stats-grid">
                            <div class="wfn-stat-card">
                                <div class="stat-number"><?php echo wp_count_posts('funeral-notice')->publish; ?></div>
                                <div class="stat-label">Published Notices</div>
                            </div>
                            <div class="wfn-stat-card">
                                <div class="stat-number"><?php echo wp_count_posts('funeral-notice')->draft; ?></div>
                                <div class="stat-label">Draft Notices</div>
                            </div>
                        </div>
                    </div>

                    <!-- Help Section -->
                    <div class="wfn-help-section">
                        <h3>Need Help?</h3>
                        <div class="wfn-help-grid">
                            <div class="wfn-help-card">
                                <div class="wfn-help-icon">
                                    <span class="dashicons dashicons-book-alt"></span>
                                </div>
                                <div class="wfn-help-content">
                                    <h4>Documentation</h4>
                                    <p>Comprehensive guides for setting up and customizing your funeral notices.</p>
                                    <span class="button button-secondary button-disabled">Coming Soon</span>
                                </div>
                            </div>
                            <div class="wfn-help-card">
                                <div class="wfn-help-icon">
                                    <span class="dashicons dashicons-sos"></span>
                                </div>
                                <div class="wfn-help-content">
                                    <h4>Support</h4>
                                    <p>Get help with technical issues, customization, or feature requests.</p>
                                    <a href="https://weave.com.au/support" target="_blank" class="button button-secondary">Get Support</a>
                                </div>
                            </div>
                            <div class="wfn-help-card">
                                <div class="wfn-help-icon">
                                    <span class="dashicons dashicons-video-alt3"></span>
                                </div>
                                <div class="wfn-help-content">
                                    <h4>Video Tutorials</h4>
                                    <p>Step-by-step video guides for common tasks and advanced features.</p>
                                    <span class="button button-secondary button-disabled">Coming Soon</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render admin header with FCRM-style design
     */
    private function render_header(): void {
        ?>
        <div class="wfn-admin-header">
            <div class="wfn-header-content">
                <div class="wfn-header-text">
                    <h1>HumanKind Funeral Notices</h1>
                    <p class="wfn-header-description">Professional funeral notice management for WordPress</p>
                    <div class="wfn-header-version">Version <?php echo esc_html($this->version); ?></div>
                </div>
                <div class="wfn-header-banner">
                    <div class="wfn-plugin-logo">
                        <img src="<?php echo plugin_dir_url(__FILE__) . '../../assets/images/wfn-logo.png'; ?>"
                              alt="Weave Funeral Notices" 
                              class="wfn-logo-image" />
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render individual module card
     */
    private function render_module_card(string $module_id, array $module): void {
        $is_enabled = $module['enabled'];
        $status_class = $is_enabled ? 'active' : 'inactive';
        $status_text = $is_enabled ? 'Active' : 'Inactive';
        
        ?>
        <div class="wfn-module-card" data-module="<?php echo esc_attr($module_id); ?>">
            <div class="wfn-module-header">
                <div class="wfn-module-icon" style="background: <?php echo esc_attr($module['icon_color']); ?>;">
                    <span class="dashicons <?php echo esc_attr($module['icon']); ?>"></span>
                </div>
                <div class="wfn-module-status">
                    <span class="wfn-status-indicator <?php echo esc_attr($status_class); ?>">
                        <?php echo esc_html($status_text); ?>
                    </span>
                </div>
            </div>
            
            <div class="wfn-module-content">
                <h3><?php echo esc_html($module['name']); ?></h3>
                <p><?php echo esc_html($module['description']); ?></p>
                
                <?php if (!empty($module['features'])): ?>
                <ul class="wfn-module-features">
                    <?php foreach ($module['features'] as $feature): ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            
            <div class="wfn-module-actions">
                <?php if (!empty($module['always_active'])): ?>
                    <!-- Always active modules - show empty space for cleaner UI -->
                    <div class="wfn-always-active-spacer"></div>
                <?php else: ?>
                    <label class="wfn-toggle-switch">
                        <input type="checkbox" 
                               <?php checked($is_enabled); ?>
                               data-module="<?php echo esc_attr($module_id); ?>"
                               class="wfn-module-toggle">
                        <span class="wfn-toggle-slider"></span>
                        <span class="wfn-toggle-label"><?php echo $is_enabled ? 'Enabled' : 'Disabled'; ?></span>
                    </label>
                <?php endif; ?>
                
                <?php if ($is_enabled && !empty($module['settings_page'])): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . $module['settings_page'])); ?>" 
                   class="button button-secondary">
                    Configure
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get available modules configuration
     */
    private function get_available_modules(): array {
        $enabled_modules = get_option('wfn_enabled_modules', [
            'layouts' => true,
            'styling' => false,
            'search' => true,
            'settings' => true
        ]);

        return [
            'layouts' => [
                'name' => 'Layout Manager',
                'description' => 'Manage funeral notice display layouts including Firehawk, Modern, Elegant, and Enhancement Suite styles.',
                'icon' => 'dashicons-layout',
                'icon_color' => '#1f4b8f',
                'enabled' => $enabled_modules['layouts'] ?? true,
                'settings_page' => 'hkfn-module-layouts',
                'features' => [
                    '8 Professional Layout Styles',
                    'Responsive Grid System',
                    'Customizable Columns',
                    'Mobile Optimization'
                ]
            ],
            'styling' => [
                'name' => 'Visual Styling',
                'description' => 'Customize colors, typography, spacing, and visual appearance of your funeral notices.',
                'icon' => 'dashicons-art',
                'icon_color' => '#8e44ad',
                'enabled' => $enabled_modules['styling'] ?? false,
                'settings_page' => 'hkfn-module-styling',
                'features' => [
                    'Color Scheme Control',
                    'Typography Settings',
                    'Spacing Adjustments',
                    'Live Preview'
                ]
            ],
            'search' => [
                'name' => 'Advanced Search',
                'description' => 'Enhanced search functionality with date ranges, location filtering, and name search.',
                'icon' => 'dashicons-search',
                'icon_color' => '#27ae60',
                'enabled' => $enabled_modules['search'] ?? true,
                'settings_page' => 'hkfn-module-search',
                'features' => [
                    'Name Search',
                    'Date Range Filtering',
                    'Location Search',
                    'Enhanced UI'
                ]
            ],
            'settings' => [
                'name' => 'General Settings',
                'description' => 'Configure default options, fallback images, and global plugin settings.',
                'icon' => 'dashicons-admin-generic',
                'icon_color' => '#34495e',
                'enabled' => true,
                'always_active' => true,
                'settings_page' => 'hkfn-module-settings',
                'features' => [
                    'Default Layout Settings',
                    'Fallback Image Configuration',
                    'Archive Page Options'
                ]
            ]
        ];
    }

    /**
     * Handle AJAX module toggle
     */
    public function handle_module_toggle(): void {
        if (!wp_verify_nonce($_POST['nonce'], 'wfn_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $module_id = sanitize_text_field($_POST['module_id']);
        $enabled = filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN);

        $enabled_modules = get_option('wfn_enabled_modules', []);
        $enabled_modules[$module_id] = $enabled;
        
        $updated = update_option('wfn_enabled_modules', $enabled_modules);

        wp_send_json_success([
            'message' => $enabled ? 'Module enabled successfully' : 'Module disabled successfully',
            'enabled' => $enabled
        ]);
    }

    /**
     * Handle dashboard form submission
     */
    private function handle_dashboard_form(): void {
        // Handle any dashboard-specific form actions
        if (isset($_POST['action'])) {
            $action = sanitize_text_field($_POST['action']);
            
            switch ($action) {
                case 'reset_settings':
                    delete_option('wfn_enabled_modules');
                    echo '<div class="notice notice-success"><p>Settings reset successfully!</p></div>';
                    break;
            }
        }
    }


}
