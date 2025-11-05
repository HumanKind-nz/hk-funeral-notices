<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices;

use WeaveStudios\FuneralNotices\Templates\TemplateManager;
use WeaveStudios\FuneralNotices\Shortcodes\FuneralNoticesShortcode;
use WeaveStudios\FuneralNotices\Admin\Dashboard;
use WeaveStudios\FuneralNotices\Admin\AdminColumns;
use WeaveStudios\FuneralNotices\Admin\ImageCropHandler;
use WeaveStudios\FuneralNotices\FieldGroups\FieldGroupManager;
use WeaveStudios\FuneralNotices\Modules\SettingsModule;
use WeaveStudios\FuneralNotices\Modules\LayoutsModule;
use WeaveStudios\FuneralNotices\Modules\SearchModule;
use WeaveStudios\FuneralNotices\Modules\StylingModule;
use WeaveStudios\FuneralNotices\Modules\LicenseModule;
use WeaveStudios\FuneralNotices\Modules\VideoModule;
use WeaveStudios\FuneralNotices\Modules\AnalyticsModule;
use WeaveStudios\FuneralNotices\Fields\GoogleMapsField;
use WeaveStudios\FuneralNotices\AJAX\LoadMoreHandler;
use WeaveStudios\FuneralNotices\API\VideoUploadAPI;

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
    private VideoUploadAPI $video_upload_api;
    // private ImageCropHandler $image_crop_handler; // Disabled in favor of Crop-Thumbnails plugin
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

        // Initialize Load More AJAX handler
        new LoadMoreHandler();

        // Initialize Video Upload API (needed for deletion hooks, not just REST routes)
        $this->video_upload_api = new VideoUploadAPI();

        // Initialize Image Crop Handler - DISABLED in favor of Crop-Thumbnails plugin (v2.5.2+)
        // Custom crop tool had coordinate calculation bugs with zoom feature
        // See DEVELOPER.md for Crop-Thumbnails setup instructions
        // $this->image_crop_handler = new ImageCropHandler();

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

        // Register REST API routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Schedule upload cleanup cron
        if (!wp_next_scheduled('wfn_cleanup_abandoned_uploads')) {
            wp_schedule_event(time(), 'daily', 'wfn_cleanup_abandoned_uploads');
        }

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
     * Register REST API routes
     */
    public function register_rest_routes(): void {
        // Use the already-instantiated VideoUploadAPI instance
        $this->video_upload_api->register_routes();
    }

    /**
     * Enqueue modern assets when shortcodes are used
     */
    public function enqueue_modern_assets(): void {
        // Enqueue Load More assets on archive pages
        if (is_post_type_archive('funeral-notice') || is_tax('funeral-location')) {
            wp_enqueue_style(
                'wfn-load-more',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/load-more.css',
                [],
                '2.4.0'
            );

            wp_enqueue_script(
                'wfn-load-more',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/load-more.js',
                ['jquery'],
                '2.4.0',
                true
            );

            wp_localize_script('wfn-load-more', 'wfnLoadMore', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wfn_load_more_nonce')
            ]);
        }

        // Enqueue Flatpickr date range picker only where search forms appear
        // Check for archive pages, taxonomy pages, or shortcode presence
        $should_load_flatpickr = false;

        if (!is_admin()) {
            // Always load on archive and taxonomy pages (search forms present)
            if (is_post_type_archive('funeral-notice') || is_tax('funeral-location')) {
                $should_load_flatpickr = true;
            }

            // Check for shortcode in current post/page content
            global $post;
            if ($post && has_shortcode($post->post_content, 'funeral_notices')) {
                $should_load_flatpickr = true;
            }

            // Fallback: Check all posts in current query (for page builders)
            if (!$should_load_flatpickr) {
                global $wp_query;
                if (isset($wp_query->posts) && is_array($wp_query->posts)) {
                    foreach ($wp_query->posts as $query_post) {
                        if (isset($query_post->post_content) && has_shortcode($query_post->post_content, 'funeral_notices')) {
                            $should_load_flatpickr = true;
                            break;
                        }
                    }
                }
            }
        }

        // Only enqueue if search functionality is present
        if ($should_load_flatpickr) {
            // Enqueue custom Flatpickr theme
            wp_enqueue_style(
                'wfn-flatpickr-custom',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/flatpickr-custom.css',
                [],
                '2.4.0'
            );

            // Enqueue initialization script (handles lazy loading of Flatpickr library)
            wp_enqueue_script(
                'wfn-flatpickr-init',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/flatpickr-init.js',
                [],
                '2.4.0',
                true
            );

            // Get WordPress date format
            $wp_date_format = get_option('date_format', 'Y-m-d');
            $flatpickr_format = $this->convert_php_to_flatpickr_format($wp_date_format);

            // Localize script with URLs for lazy loading and settings
            wp_localize_script('wfn-flatpickr-init', 'wfnFlatpickr', [
                'jsUrl' => plugin_dir_url(dirname(__FILE__)) . 'assets/js/vendor/flatpickr.min.js',
                'cssUrl' => plugin_dir_url(dirname(__FILE__)) . 'assets/css/vendor/flatpickr.min.css',
                'dateFormat' => $flatpickr_format,
                'placeholder' => 'Select date range...'
            ]);
        }

        // Enqueue social share assets on single funeral notice pages
        if (is_singular('funeral-notice')) {
            wp_enqueue_style(
                'wfn-social-share',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/social-share.css',
                [],
                '2.4.0'
            );

            wp_enqueue_script(
                'wfn-social-share',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/social-share.js',
                [],
                '2.4.0',
                true
            );
        }

        // Other assets are enqueued conditionally by individual layout renderers
        // This keeps the system lightweight
    }

    /**
     * Convert PHP date format to Flatpickr date format
     */
    private function convert_php_to_flatpickr_format(string $php_format): string {
        $replacements = [
            'Y' => 'Y',    // 4-digit year
            'y' => 'y',    // 2-digit year
            'm' => 'm',    // Month with leading zero
            'n' => 'n',    // Month without leading zero
            'M' => 'M',    // Short month name
            'F' => 'F',    // Full month name
            'd' => 'd',    // Day with leading zero
            'j' => 'j',    // Day without leading zero
            'D' => 'D',    // Short day name
            'l' => 'l',    // Full day name
        ];

        $flatpickr_format = $php_format;
        foreach ($replacements as $php => $flatpickr) {
            $flatpickr_format = str_replace($php, $flatpickr, $flatpickr_format);
        }

        // Default to Y-m-d if conversion fails
        return !empty($flatpickr_format) ? $flatpickr_format : 'Y-m-d';
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
            'styling' => new StylingModule(),
            'license' => new LicenseModule(),
            'video' => new VideoModule(),
            'analytics' => new AnalyticsModule()
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
