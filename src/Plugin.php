<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices;

use WeaveStudios\FuneralNotices\Templates\TemplateManager;
use WeaveStudios\FuneralNotices\Shortcodes\FuneralNoticesShortcode;
use WeaveStudios\FuneralNotices\Admin\Dashboard;
use WeaveStudios\FuneralNotices\Admin\AdminColumns;
use WeaveStudios\FuneralNotices\FieldGroups\FieldGroupManager;
use WeaveStudios\FuneralNotices\Modules\SettingsModule;
use WeaveStudios\FuneralNotices\Modules\LayoutsModule;
use WeaveStudios\FuneralNotices\Modules\SearchModule;
use WeaveStudios\FuneralNotices\Modules\StylingModule;
use WeaveStudios\FuneralNotices\Fields\GoogleMapsField;

/**
 * Main Plugin Class
 * Simplified modern plugin coordinator that works alongside legacy system
 * 
 * @since 2.0.0
 */
class Plugin {

    private static ?self $instance = null;
    private TemplateManager $template_manager;
    private FuneralNoticesShortcode $shortcode_handler;
    private Dashboard $admin_dashboard;
    private AdminColumns $admin_columns;
    private FieldGroupManager $field_group_manager;
    private array $modules = [];

    /**
     * Singleton instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to enforce singleton
     */
    private function __construct() {
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Initialize simple components
     */
    private function init_components(): void {
        $this->template_manager = new TemplateManager();
        $this->shortcode_handler = new FuneralNoticesShortcode($this->template_manager);
        
        // Initialize ACF field groups (critical for admin interface)
        $this->field_group_manager = new FieldGroupManager();
        $this->field_group_manager->register();
        
        // Register custom ACF field types
        add_action('acf/include_field_types', [$this, 'register_custom_fields']);
        
        // Initialize modules
        $this->init_modules();
        
        // Initialize admin dashboard (only in admin area)
        if (is_admin()) {
            $this->admin_dashboard = new Dashboard('hk-funeral-notices', '2.0.0');
            $this->admin_columns = new AdminColumns();
        }
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        // Initialize modern shortcodes as additional options
        add_action('init', [$this, 'register_modern_shortcodes']);
        
        // Enqueue modern assets when needed
        add_action('wp_enqueue_scripts', [$this, 'enqueue_modern_assets']);
        
        // Initialize admin dashboard
        if (is_admin()) {
            add_action('init', [$this, 'init_admin_dashboard']);
        }
        
        // Initialize modules
        add_action('init', [$this, 'init_module_hooks']);

        // Register template loading hooks
        add_filter('single_template', [$this, 'load_single_template']);
        add_filter('archive_template', [$this, 'load_archive_template']);
        
        // Customize archive query ordering
        add_action('pre_get_posts', [$this, 'customize_archive_query']);
    }

    /**
     * Register modern shortcodes (in addition to legacy shortcode)
     */
    public function register_modern_shortcodes(): void {
        $this->shortcode_handler->register();
    }

    /**
     * Enqueue modern assets when shortcodes are used
     */
    public function enqueue_modern_assets(): void {
        // Assets are enqueued conditionally by individual layout renderers
        // This keeps the system lightweight
    }

    /**
     * Initialize admin dashboard
     */
    public function init_admin_dashboard(): void {
        if (isset($this->admin_dashboard)) {
            $this->admin_dashboard->init($this->modules);
        }
    }
    
    /**
     * Initialize modules
     */
    private function init_modules(): void {
        // Initialize all modules
        $this->modules = [
            'settings' => new SettingsModule(),
            'layouts' => new LayoutsModule(),
            'search' => new SearchModule(),
            'styling' => new StylingModule()
        ];
    }
    
    /**
     * Initialize module hooks
     */
    public function init_module_hooks(): void {
        foreach ($this->modules as $module) {
            // Always initialize modules (admin pages need to be available)
            $module->init();
        }
    }
    
    /**
     * Get module instance
     */
    public function get_module(string $module_id): ?object {
        return $this->modules[$module_id] ?? null;
    }
    
    /**
     * Get all modules
     */
    public function get_modules(): array {
        return $this->modules;
    }
    
    /**
     * Register custom ACF field types
     */
    public function register_custom_fields(): void {
        // Debug: Log that we're registering custom fields
        // Debug logging removed for production
        
        // Register Google Maps field
        new GoogleMapsField();
        
        // Debug logging removed for production
    }

    /**
     * Load custom single template for funeral notices
     */
    public function load_single_template(string $template): string {
        if (is_singular('funeral-notice')) {
            $custom_template = plugin_dir_path(__FILE__) . '../templates/single-funeral-notice.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }

    /**
     * Load custom archive template for funeral notices
     */
    public function load_archive_template(string $template): string {
        if (is_post_type_archive('funeral-notice')) {
            $custom_template = plugin_dir_path(__FILE__) . '../templates/archive-funeral-notice.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }
    
    /**
     * Customize archive query to order by funeral date
     */
    public function customize_archive_query(\WP_Query $query): void {
        // Only modify main query on frontend archive pages
        if (!is_admin() && $query->is_main_query() && is_post_type_archive('funeral-notice')) {
            // Order by funeral date first (furthest dates first), then by publish date
            $query->set('meta_key', 'wfn_details_group_funeral_date');
            $query->set('meta_type', 'DATE');
            $query->set('orderby', [
                'meta_value' => 'DESC', // furthest dates first
                'date'       => 'DESC', // for notices without dates, newest published first
            ]);
        }
    }
}
