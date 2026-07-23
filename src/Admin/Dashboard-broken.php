<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Admin;

/**
 * Admin Dashboard
 * Modern modular admin interface inspired by FCRM-style design
 *
 * @since 2.0.0
 */
class Dashboard {

    private array $modules = [];
    private string $version;

    public function __construct(string $version) {
        $this->version = $version;
    }

    /**
     * Initialize the dashboard
     */
    public function init(array $modules): void {
        $this->modules = $modules;

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_hkfn_toggle_module', [$this, 'handle_module_toggle']);
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu(): void {
        // Settings Dashboard as submenu under existing Funeral Notices post type
        add_submenu_page(
            'edit.php?post_type=funeral-notice',
            'Settings Dashboard',
            'Settings Dashboard',
            'manage_options',
            'weave-funeral-notices',
            [$this, 'render_dashboard']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook): void {
        // Only enqueue on our dashboard page
        if ($hook !== 'funeral-notice_page_weave-funeral-notices') {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'hkfn-admin-dashboard',
            plugin_dir_url(dirname(__FILE__, 2)) . 'assets/css/admin/dashboard.css',
            [],
            $this->version
        );

        // Enqueue admin JavaScript
        wp_enqueue_script(
            'hkfn-admin-dashboard',
            plugin_dir_url(dirname(__FILE__, 2)) . 'assets/js/admin/dashboard.js',
            ['jquery'],
            $this->version,
            true
        );

        // Localize script for AJAX
        wp_localize_script('hkfn-admin-dashboard', 'hkfnAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hkfn_admin_nonce'),
            'strings' => [
                'saving' => __('Saving...', 'weave-funeral-notices'),
                'saved' => __('Saved!', 'weave-funeral-notices'),
                'error' => __('Error saving settings', 'weave-funeral-notices'),
            ]
        ]);
    }

    /**
     * Handle module toggle AJAX request
     */
    public function handle_module_toggle(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hkfn_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $module_id = sanitize_text_field($_POST['module_id'] ?? '');
        $enabled = (bool) ($_POST['enabled'] ?? false);

        if (empty($module_id)) {
            wp_send_json_error('Invalid module ID');
        }

        // Update module status
        $options = hkfn_get_option('module_status', []);
        $options[$module_id] = $enabled;
        update_option('hkfn_module_status', $options);

        wp_send_json_success([
            'module_id' => $module_id,
            'enabled' => $enabled
        ]);
    }

    /**
     * Render the main dashboard
     */
    public function render_dashboard(): void {
        ?>
        <div class="wrap hkfn-admin-dashboard">
            <!-- Header -->
            <div class="hkfn-dashboard-header">
                <div class="hkfn-header-content">
                    <div class="hkfn-header-logo">
                        <img src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__, 2)) . 'assets/images/hkfn-logo.png'); ?>" alt="Weave Funeral Notices" />
                        <div class="hkfn-header-text">
                            <h1>Weave Funeral Notices</h1>
                            <span class="hkfn-version">Professional funeral notice management for WordPress</span>
                            <div class="hkfn-version-tag">VERSION <?php echo esc_html($this->version); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="hkfn-dashboard-content">
                <!-- Welcome Section -->
                <div class="hkfn-welcome-section">
                    <h2>Welcome to Weave Funeral Notices</h2>
                    <p>Manage your funeral notices with professional layouts, advanced search, and customizable styling. Enable the modules you need below.</p>
                </div>

                <!-- Modules Grid -->
                <div class="hkfn-modules-grid">
                    <?php foreach ($this->modules as $module): ?>
                        <?php $this->render_module_card($module); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render a module card
     */
    private function render_module_card($module): void {
        $module_data = $this->get_module_data($module->get_id());
        $is_enabled = $module->is_enabled();

        ?>
        <div class="hkfn-module-card <?php echo $is_enabled ? 'enabled' : 'disabled'; ?>" data-module="<?php echo esc_attr($module->get_id()); ?>">
            <div class="hkfn-module-header">
                <div class="hkfn-module-icon">
                    <i class="<?php echo esc_attr($module_data['icon']); ?>"></i>
                </div>
                <div class="hkfn-module-status">
                    <span class="hkfn-status-badge <?php echo $is_enabled ? 'active' : 'inactive'; ?>">
                        <?php echo $is_enabled ? 'ACTIVE' : 'INACTIVE'; ?>
                    </span>
                </div>
            </div>

            <div class="hkfn-module-content">
                <h3><?php echo esc_html($module_data['name']); ?></h3>
                <p><?php echo esc_html($module_data['description']); ?></p>

                <?php if (!empty($module_data['features'])): ?>
                    <ul class="hkfn-module-features">
                        <?php foreach ($module_data['features'] as $feature): ?>
                            <li>✓ <?php echo esc_html($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="hkfn-module-footer">
                <div class="hkfn-module-toggle">
                    <label class="hkfn-toggle-switch">
                        <input type="checkbox"
                               class="hkfn-module-checkbox"
                               data-module="<?php echo esc_attr($module->get_id()); ?>"
                               <?php checked($is_enabled); ?>>
                        <span class="hkfn-toggle-slider"></span>
                        <span class="hkfn-toggle-label">
                            <?php echo $is_enabled ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </label>
                </div>

                <?php if ($module->has_admin_page()): ?>
                    <div class="hkfn-module-actions">
                        <a href="<?php echo esc_url($module->get_admin_url()); ?>"
                           class="hkfn-btn <?php echo $is_enabled ? 'hkfn-btn-primary' : 'hkfn-btn-secondary'; ?>">
                            Configure
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get module metadata for display
     */
    private function get_module_data(string $module_id): array {
        $modules_data = [
            'settings' => [
                'name' => 'Core Settings',
                'description' => 'Essential plugin configuration and global settings.',
                'icon' => 'dashicons-admin-generic',
                'features' => [
                    'Default Layout Configuration',
                    'Posts Per Page Control',
                    'Grid Column Settings',
                    'Search Form Toggle',
                    'Default Person Image',
                    'Pagination Control'
                ]
            ],
            'layouts' => [
                'name' => 'Layout Manager',
                'description' => 'Manage funeral notice display layouts including Firehawk, Modern, Elegant and Enhancement Suite styles.',
                'icon' => 'dashicons-layout',
                'features' => [
                    '6 Professional Layout Styles',
                    'Responsive Grid System',
                    'Mobile Optimization',
                    'Customizable Columns'
                ]
            ],
            'visual-styling' => [
                'name' => 'Visual Styling',
                'description' => 'Customize colors, typography, spacing, and visual appearance of funeral notices.',
                'icon' => 'dashicons-art',
                'features' => [
                    'Color Scheme Control',
                    'Typography Settings',
                    'Spacing Adjustments',
                    'Live Preview'
                ]
            ],
            'advanced-search' => [
                'name' => 'Advanced Search',
                'description' => 'Enhance search functionality with date range filtering, name search, and location search.',
                'icon' => 'dashicons-search',
                'features' => [
                    'Name Search',
                    'Date Range Filtering',
                    'Location Search',
                    'Enhanced UI'
                ]
            ],
            'general-settings' => [
                'name' => 'General Settings',
                'description' => 'Configure default options, fallback images, and global plugin settings.',
                'icon' => 'dashicons-admin-settings',
                'features' => [
                    'Default Layout Settings',
                    'Fallback Image Configuration',
                    'Archive Page Options',
                    'Performance Settings'
                ]
            ],
            'performance' => [
                'name' => 'Performance',
                'description' => 'Optimize loading times, enable caching, and improve site performance.',
                'icon' => 'dashicons-performance',
                'features' => [
                    'CSS Optimization',
                    'Image Lazy Loading',
                    'Query Optimization',
                    'Caching Support'
                ]
            ]
        ];

        return $modules_data[$module_id] ?? [
            'name' => ucfirst(str_replace('-', ' ', $module_id)),
            'description' => 'Module configuration and settings.',
            'icon' => 'dashicons-admin-generic',
            'features' => []
        ];
    }
}