<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Modules;

/**
 * Base Module Class
 * 
 * Abstract base class providing common functionality for all modules.
 * Implements the ModuleInterface with standard behavior that can be
 * extended by individual modules.
 * 
 * @since 2.0.0
 */
abstract class BaseModule implements ModuleInterface {
    
    protected string $module_id;
    protected string $module_name;
    protected string $module_description;
    protected string $module_version;
    protected array $default_settings = [];
    
    /**
     * Constructor
     * 
     * @param string $module_id Unique module identifier
     * @param string $module_name Human-readable module name
     * @param string $module_description Brief module description
     * @param string $module_version Module version number
     */
    public function __construct(
        string $module_id,
        string $module_name,
        string $module_description,
        string $module_version
    ) {
        $this->module_id = $module_id;
        $this->module_name = $module_name;
        $this->module_description = $module_description;
        $this->module_version = $module_version;
    }
    
    /**
     * Initialize the module
     * 
     * Default implementation registers admin hooks.
     * Override in child classes for custom initialization.
     */
    public function init(): void {
        // Always register admin pages in admin area
        if (is_admin()) {
            add_action('admin_menu', [$this, 'register_admin_page']);
            add_action('admin_init', [$this, 'process_admin_form']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        }
        
        // Only initialize frontend functionality if enabled
        if ($this->is_enabled()) {
            $this->init_frontend();
        }
    }
    
    /**
     * Initialize frontend functionality
     * 
     * Override in child classes for frontend initialization.
     * Only called when module is enabled.
     */
    protected function init_frontend(): void {
        // Override in child classes
    }
    
    /**
     * Get module ID
     */
    public function get_id(): string {
        return $this->module_id;
    }
    
    /**
     * Get module name
     */
    public function get_name(): string {
        return $this->module_name;
    }
    
    /**
     * Get module description
     */
    public function get_description(): string {
        return $this->module_description;
    }
    
    /**
     * Get module version
     *
     * Returns the PLUGIN version, not the per-module version: every caller
     * uses this as the asset cache-buster, and stale per-module versions
     * left browsers serving year-cached v2 CSS against v3 markup after the
     * wfn_ -> hkfn_ selector rename.
     */
    public function get_version(): string {
        return defined('HKFN_VERSION') ? HKFN_VERSION : $this->module_version;
    }
    
    /**
     * Check if module is enabled
     */
    public function is_enabled(): bool {
        // Default to enabled for core modules
        $default_enabled = in_array($this->module_id, ['settings', 'layouts', 'search', 'styling']);
        return (bool) get_option($this->get_enabled_option_name(), $default_enabled);
    }
    
    /**
     * Enable the module
     */
    public function enable(): bool {
        return update_option($this->get_enabled_option_name(), true);
    }
    
    /**
     * Disable the module
     */
    public function disable(): bool {
        return update_option($this->get_enabled_option_name(), false);
    }
    
    /**
     * Get module settings
     */
    public function get_settings(): array {
        $option_name = $this->get_settings_option_name();
        $settings    = get_option($option_name, null);

        // Dual-read fallback for sites upgrading from v2.x, whose settings are
        // still stored under the wfn_ prefix. Without this, upgraded sites lose
        // all saved module configuration (custom CSS, colours, layout) and
        // silently revert to defaults. Writes still go to the hkfn_ key.
        if ($settings === null) {
            $legacy_name = preg_replace('/^hkfn_/', 'wfn_', $option_name);
            $settings    = get_option($legacy_name, []);
        }

        if (!is_array($settings)) {
            $settings = [];
        }

        return array_merge($this->get_default_settings(), $settings);
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings(): array {
        return $this->default_settings;
    }
    
    /**
     * Update module settings
     */
    public function update_settings(array $settings): bool {
        $sanitized_settings = $this->sanitize_settings($settings);
        $option_name = $this->get_settings_option_name();
        $current = get_option($option_name, []);
        
        // Treat re-saving the same values as success (avoid false negative from update_option)
        if ($current == $sanitized_settings) { // intentionally loose compare to ignore type differences
            return true;
        }
        
        return update_option($option_name, $sanitized_settings);
    }
    
    /**
     * Get admin page URL
     */
    public function get_admin_url(): string {
        return admin_url('admin.php?page=hkfn-module-' . $this->module_id);
    }
    
    /**
     * Get module features
     * 
     * Override in child classes to provide specific features.
     */
    public function get_features(): array {
        return [];
    }
    
    /**
     * Reset module settings
     */
    public function reset_settings(): bool {
        return update_option($this->get_settings_option_name(), $this->get_default_settings());
    }
    
    /**
     * Register admin page
     * 
     * Registers the module's admin page as a submenu item.
     */
    public function register_admin_page(): void {
        add_submenu_page(
            'edit.php?post_type=funeral-notice',
            $this->module_name . ' Settings',
            $this->module_name,
            'manage_options',
            'hkfn-module-' . $this->module_id,
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Process admin form
     * 
     * Handles form submission with nonce verification.
     */
    public function process_admin_form(): void {
        if (!isset($_POST['hkfn_module_' . $this->module_id . '_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['hkfn_module_' . $this->module_id . '_nonce'], 'hkfn_module_' . $this->module_id)) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $this->handle_form_submission();
    }
    
    /**
     * Enqueue admin assets for module pages
     * 
     * Loads shared module CSS and allows child classes to enqueue specific assets
     */
    public function enqueue_admin_assets($hook): void {
        // Only load on this module's admin page
        if (!str_contains($hook, 'hkfn-module-' . $this->module_id)) {
            return;
        }
        
        // First load dashboard CSS for consistent styling (especially toggles)
        wp_enqueue_style(
            'hkfn-admin-dashboard',
            HKFN_PLUGIN_URL . 'assets/css/admin/dashboard.css',
            [],
            $this->get_version() . '-' . time()
        );

        // Then load module-specific CSS
        wp_enqueue_style(
            'hkfn-modules-admin',
            HKFN_PLUGIN_URL . 'assets/css/admin/modules.css',
            ['hkfn-admin-dashboard'], // Depends on dashboard CSS
            $this->get_version()
        );
        
        // Allow child classes to enqueue specific assets
        $this->enqueue_module_assets($hook);
    }
    
    /**
     * Enqueue module-specific assets
     * 
     * Override in child classes to load module-specific CSS/JS
     */
    protected function enqueue_module_assets($hook): void {
        // Override in child classes
    }
    
    /**
     * Render admin page wrapper
     * 
     * Provides consistent admin page structure.
     */
    public function render_admin_page(): void {
        ?>
        <div class="wrap hkfn-module-admin">
            <div class="hkfn-module-header">
                <div class="hkfn-header-content">
                    <div class="hkfn-header-text">
                        <h1><?php echo esc_html($this->module_name); ?> Settings</h1>
                        <p class="hkfn-header-description"><?php echo esc_html($this->module_description); ?></p>
                        <div class="hkfn-back-to-dashboard">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=hk-funeral-notices-dashboard')); ?>" class="button button-secondary">
                                <span class="dashicons dashicons-arrow-left-alt2"></span> Back to Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="hkfn-plugin-logo">
                        <img src="<?php echo esc_url(HKFN_PLUGIN_URL . 'assets/images/hkfn-logo.png'); ?>" alt="WFN Logo" class="hkfn-logo-image">
                    </div>
                </div>
            </div>
            
            <?php $this->render_admin_notices(); ?>
            
            <div class="hkfn-module-content">
                <?php $this->render_module_admin_content(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render admin notices
     * 
     * Displays success/error messages after form submission.
     */
    protected function render_admin_notices(): void {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>Settings saved successfully!</p>
            </div>
            <?php
        }
        
        if (isset($_GET['settings-error'])) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>Error saving settings. Please try again.</p>
            </div>
            <?php
        }
    }
    
    /**
     * Render module admin content
     * 
     * Abstract method that child classes must implement
     * to provide their specific admin interface.
     */
    abstract protected function render_module_admin_content(): void;
    
    /**
     * Handle form submission
     * 
     * Default implementation processes standard form fields.
     * Override in child classes for custom processing.
     */
    public function handle_form_submission(): bool {
        if (!isset($_POST['hkfn_module_settings'])) {
            return false;
        }
        
        $settings = $_POST['hkfn_module_settings'];
        $success = $this->update_settings($settings);
        
        if ($success) {
            wp_redirect(add_query_arg('settings-updated', 'true', $this->get_admin_url()));
        } else {
            wp_redirect(add_query_arg('settings-error', 'true', $this->get_admin_url()));
        }
        
        exit;
    }
    
    /**
     * Sanitize settings
     * 
     * Basic sanitization. Override in child classes for specific needs.
     */
    protected function sanitize_settings(array $settings): array {
        $sanitized = [];
        
        foreach ($settings as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get enabled option name
     */
    protected function get_enabled_option_name(): string {
        return 'hkfn_module_' . $this->module_id . '_enabled';
    }
    
    /**
     * Get settings option name
     */
    protected function get_settings_option_name(): string {
        return 'hkfn_module_' . $this->module_id . '_settings';
    }
    
    /**
     * Render form nonce field
     */
    protected function render_nonce_field(): void {
        wp_nonce_field('hkfn_module_' . $this->module_id, 'hkfn_module_' . $this->module_id . '_nonce');
    }
    
    /**
     * Render submit button
     */
    protected function render_submit_button(): void {
        ?>
        <div class="hkfn-form-actions">
            <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
            <a href="<?php echo esc_url(add_query_arg('reset', 'true', $this->get_admin_url())); ?>"
               class="button button-secondary"
               onclick="return confirm('Are you sure you want to reset all settings to defaults?')">
                Reset to Defaults
            </a>
        </div>
        <?php
    }
}