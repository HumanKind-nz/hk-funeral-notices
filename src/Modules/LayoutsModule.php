<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Modules;

/**
 * Layouts Module
 * 
 * Professional responsive layouts and grid systems management.
 * Handles template selection, card styles, responsive settings,
 * and layout-specific configurations.
 * 
 * @since 2.0.0
 */
class LayoutsModule extends BaseModule {
    
    protected array $default_settings = [
        'enable_archive_templates' => false,
        'default_archive_layout' => 'modern',
'default_single_layout' => 'current',
        'default_card_style' => 'standard',
        'responsive_breakpoints' => [
            'mobile' => 768,
            'tablet' => 1024,
            'desktop' => 1200
        ],
        'grid_settings' => [
            'mobile_columns' => 1,
            'tablet_columns' => 2,
            'desktop_columns' => 3
        ],
        'card_spacing' => 20,
        'image_aspect_ratio' => '4:3',
        'show_layout_previews' => true,
        'enable_hover_effects' => true,
        'enable_animations' => true,
        'enable_layout_switching' => false
    ];
    
    private array $available_card_layouts = [
        'firehawk' => [
            'name' => 'Firehawk Compatible',
            'description' => 'Grid layout matching Firehawk CRM styling standards',
            'features' => ['CRM Integration', 'Professional Grid', 'Firehawk Styling'],
            'template' => 'firehawk',
            'css_file' => 'firehawk-compat.css',
            'preview_image' => 'firehawk-preview.jpg'
        ],
        'modern' => [
            'name' => 'Modern Memorial Grid',
            'description' => 'Clean, contemporary funeral notice cards with modern styling',
            'features' => ['Responsive Grid', 'Modern Design', 'Card Hover Effects', 'Mobile Optimized'],
            'template' => 'modern-grid',
            'css_file' => 'layouts/modern-grid.css',
            'preview_image' => 'modern-preview.jpg'
        ],
        'elegant' => [
            'name' => 'Elegant Funeral Grid',
            'description' => 'Formal, traditional memorial styling with sophisticated design',
            'features' => ['Traditional Styling', 'Formal Design', 'Portrait Focus', 'Elegant Typography'],
            'template' => 'elegant-grid',
            'css_file' => 'layouts/elegant-grid.css',
            'preview_image' => 'elegant-preview.jpg'
        ],
        'minimal' => [
            'name' => 'Simple Memorial List',
            'description' => 'Clean, minimal list layout for understated presentation',
            'features' => ['Minimal Design', 'List View', 'Clean Typography', 'Fast Loading'],
            'template' => 'minimal',
            'css_file' => 'layouts/minimal.css',
            'preview_image' => 'minimal-preview.jpg'
        ]
    ];

    private array $available_single_templates = [
        'current' => [
            'name' => 'Current/Default Layout',
            'description' => 'Clean, responsive single page layout suitable for most funeral homes',
            'features' => ['Responsive Design', 'Clean Layout', 'Easy to Read', 'Default Choice'],
            'template_file' => 'current/single.php',
            'css_file' => 'current.css'
        ],
        'firehawk' => [
            'name' => 'Firehawk Layout',
            'description' => 'Professional CRM-style layout matching Firehawk standards',
            'features' => ['CRM Integration', 'Professional Design', 'Action Buttons Row', 'Registration Forms'],
            'template_file' => 'firehawk/single.php',
            'css_file' => 'firehawk.css'
        ],
        'elegant' => [
            'name' => 'Elegant Layout',
            'description' => 'Refined memorial styling with sophisticated typography and formal presentation',
            'features' => ['Formal Design', 'Traditional Styling', 'Elegant Typography', 'Memorial Focus'],
            'template_file' => 'elegant/single.php',
            'css_file' => 'elegant.css'
        ]
    ];

    private array $card_styles = [
        'standard' => [
            'name' => 'Standard Card',
            'description' => 'Default card style with subtle borders and padding',
            'css_class' => 'hkfn-card-standard'
        ],
        'elevated' => [
            'name' => 'Elevated Card',
            'description' => 'Raised card with shadow for depth and emphasis',
            'css_class' => 'hkfn-card-elevated'
        ],
        'outlined' => [
            'name' => 'Outlined Card',
            'description' => 'Clean card with border outline, no shadow',
            'css_class' => 'hkfn-card-outlined'
        ],
        'minimal' => [
            'name' => 'Minimal Card',
            'description' => 'Borderless card with minimal styling',
            'css_class' => 'hkfn-card-minimal'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Debug logging removed for production
        parent::__construct(
            'layouts',
            'Modern Layouts',
            'Professional responsive layouts and grid systems management',
            '2.0.1'
        );
        // Debug logging removed for production
    }
    
    /**
     * Initialize the module
     */
    public function init(): void {
        parent::init();
        
        // When layouts settings are saved, sync WP options for template manager
        add_action('update_option_' . $this->get_settings_option_name(), function($old_value, $new_value) {
            // Ensure our settings are available before syncing
            $this->sync_archive_layout_setting();
        }, 10, 2);
    }
    
    /**
     * Initialize frontend functionality
     */
    protected function init_frontend(): void {
        // Enqueue layout-specific assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_layout_assets']);
        
        // Add layout body classes
        add_filter('body_class', [$this, 'add_layout_body_classes']);
        
        // Register layout template filters
        add_filter('hkfn_available_layouts', [$this, 'filter_available_layouts']);
        add_filter('hkfn_layout_settings', [$this, 'get_layout_settings']);
        
        // Sync archive layout setting with WordPress option
        add_action('init', [$this, 'sync_archive_layout_setting']);
    }
    
    /**
     * Get module features
     */
    public function get_features(): array {
        return [
            '5 Professional Layout Options',
            '4 Card Style Variants (Standard, Elevated, Outlined, Minimal)',
            'Responsive Grid System (3-4 columns)',
            'Mobile-Optimized Design',
            'Template Preview System',
            'Layout-Specific Settings',
            'CSS Asset Management',
            'Hover Effects & Animations'
        ];
    }
    
    /**
     * Check if current page should load funeral notice styles
     */
    private function should_load_styles(): bool {
        // Always load on single funeral notice pages
        if (is_singular('funeral-notice')) {
            return true;
        }
        
        // Check if current page/post has the funeral_notices shortcode
        global $post;
        if ($post && has_shortcode($post->post_content, 'funeral_notices')) {
            return true;
        }
        
        // Check if it's the archive page for funeral notices
        if (is_post_type_archive('funeral-notice')) {
            return true;
        }
        
        // Check if it's a funeral location taxonomy page
        if (is_tax('funeral-location')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Enqueue layout-specific assets
     */
    public function enqueue_layout_assets(): void {
        if (!$this->is_enabled()) {
            return;
        }
        
        // Only load styles on relevant pages
        if (!$this->should_load_styles()) {
            return;
        }
        
        $settings = $this->get_settings();
        
        // Enqueue shared base styles
        wp_enqueue_style(
            'hkfn-layouts-base',
            HKFN_PLUGIN_URL . 'assets/css/layouts/shared-base.css',
            [],
            $this->get_version()
        );
        
        // For single funeral notice pages, load template-specific CSS
        if (is_singular('funeral-notice')) {
            // Get the template manager to determine active mode
            $template_manager = new \HumanKind\FuneralNotices\Templates\TemplateManager();
            $active_mode = $template_manager->get_single_mode();
            
            // Map modes to their CSS files
            $css_files = [
                'current' => 'current.css',
                'modern' => 'modern.css',
                'elegant' => 'elegant.css',
                'firehawk' => 'firehawk-compat.css'
            ];
            
            // Load mode-specific CSS if available
            if (isset($css_files[$active_mode])) {
                wp_enqueue_style(
                    'hkfn-template-' . $active_mode,
                    HKFN_PLUGIN_URL . 'assets/css/' . $css_files[$active_mode],
                    ['hkfn-layouts-base'],
                    $this->get_version()
                );
            }
            
            // Also load celebration text styles
            wp_enqueue_style(
                'hkfn-celebration-text',
                HKFN_PLUGIN_URL . 'assets/css/celebration-text.css',
                ['hkfn-layouts-base'],
                $this->get_version()
            );
        }
        
        // Register all card layout styles (loaded on demand)
        foreach ($this->available_card_layouts as $layout_id => $layout) {
            wp_register_style(
                'hkfn-layout-' . $layout_id,
                HKFN_PLUGIN_URL . 'assets/css/' . $layout['css_file'],
                ['hkfn-layouts-base'],
                $this->get_version()
            );
        }
        
        // Enqueue layout management script
        wp_enqueue_script(
            'hkfn-layouts',
            HKFN_PLUGIN_URL . 'assets/js/frontend/layouts.js',
            ['jquery'],
            $this->get_version(),
            true
        );
        
        // Pass settings to JavaScript
        wp_localize_script('hkfn-layouts', 'hkfnLayouts', [
            'settings' => $settings,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hkfn_layouts_nonce')
        ]);
    }
    
    /**
     * Add layout body classes
     */
    public function add_layout_body_classes(array $classes): array {
        if (!$this->is_enabled()) {
            return $classes;
        }
        
        $settings = $this->get_settings();
        
        // Add layout-specific classes
        $classes[] = 'hkfn-layouts-enabled';
        $classes[] = 'hkfn-card-' . $settings['default_card_style'];
        
        if ($settings['enable_hover_effects']) {
            $classes[] = 'hkfn-hover-effects';
        }
        
        if ($settings['enable_animations']) {
            $classes[] = 'hkfn-animations-enabled';
        }
        
        return $classes;
    }
    
    /**
     * Filter available layouts
     */
    public function filter_available_layouts(array $layouts): array {
        if (!$this->is_enabled()) {
            return $layouts;
        }

        return $this->available_card_layouts;
    }
    
    /**
     * Get layout settings for templates
     */
    public function get_layout_settings(): array {
        return $this->get_settings();
    }
    
    /**
     * Sync layout settings with WordPress options
     */
    public function sync_archive_layout_setting(): void {
        if (!$this->is_enabled()) {
            return;
        }
        
        $settings = $this->get_settings();
        
        // Always sync single layout setting (independent of archive templates)
        $current_single_wp_option = hkfn_get_option('single_display_mode', 'current');
        if ($current_single_wp_option !== $settings['default_single_layout']) {
            update_option('hkfn_single_display_mode', $settings['default_single_layout']);
        }
        
        // Only sync archive templates if enabled
        if ($settings['enable_archive_templates']) {
            $current_wp_option = hkfn_get_option('archive_display_mode', '');
            
            // Only update if different to avoid unnecessary writes
            if ($current_wp_option !== $settings['default_archive_layout']) {
                update_option('hkfn_archive_display_mode', $settings['default_archive_layout']);
            }
        } else {
            // Clear the option if archive templates are disabled
            delete_option('hkfn_archive_display_mode');
        }
    }
    
    /**
     * Render module admin content
     */
    protected function render_module_admin_content(): void {
        $settings = $this->get_settings();
        
        // Ensure default values are present
        $settings = array_merge($this->default_settings, $settings);
        ?>
        <form method="post" action="">
            <?php $this->render_nonce_field(); ?>
            
            <div class="hkfn-layouts-admin">
                <div class="hkfn-admin-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#card-layouts" class="nav-tab nav-tab-active">Card Layouts</a>
                        <a href="#single-templates" class="nav-tab">Single Post Templates</a>
                        <a href="#grid" class="nav-tab">Grid Settings</a>
                        <a href="#advanced" class="nav-tab">Advanced</a>
                    </nav>
                    
                    <!-- Card Layouts Tab -->
                    <div id="card-layouts" class="tab-content active">
                        <h3>Card Layouts</h3>
                        <p>Choose from professional card layout options for shortcodes and archive pages. These control how funeral notice cards appear in grids.</p>
                        
                        <div class="hkfn-shortcode-example">
                            <strong>Shortcode Usage:</strong>
                            <code>[funeral_notices layout="modern" columns="3"]</code>
                            <span class="hkfn-shortcode-note">Replace "modern" with any layout ID from the cards below</span>
                        </div>
                        
                        <div class="hkfn-layouts-grid">
                            <?php foreach ($this->available_card_layouts as $layout_id => $layout): ?>
                                <div class="hkfn-layout-card">
                                    <div class="hkfn-layout-preview">
                                        <img src="<?php echo esc_url(HKFN_PLUGIN_URL . 'assets/images/previews/' . $layout['preview_image']); ?>"
                                             alt="<?php echo esc_attr($layout['name']); ?> Preview" 
                                             onerror="this.style.display='none'">
                                    </div>
                                    
                                    <div class="hkfn-layout-info">
                                        <div class="hkfn-layout-header">
                                            <h4><?php echo esc_html($layout['name']); ?></h4>
                                            <div class="hkfn-layout-id">
                                                <strong>Shortcode ID:</strong> <code><?php echo esc_html($layout_id); ?></code>
                                            </div>
                                        </div>
                                        
                                        <p class="hkfn-layout-description">
                                            <?php echo esc_html($layout['description']); ?>
                                        </p>
                                        
                                        <div class="hkfn-layout-features">
                                            <?php foreach ($layout['features'] as $feature): ?>
                                                <span class="hkfn-feature-tag"><?php echo esc_html($feature); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Single Post Templates Tab -->
                    <div id="single-templates" class="tab-content">
                        <h3>Single Post Templates</h3>
                        <p>Choose the template for individual funeral notice pages. These control the layout and styling of single funeral notice posts.</p>

                        <div class="hkfn-form-group">
                            <label for="default_single_layout">Default Single Post Template</label>
                            <select name="hkfn_module_settings[default_single_layout]" id="default_single_layout">
                                <?php foreach ($this->available_single_templates as $layout_id => $layout): ?>
                                    <option value="<?php echo esc_attr($layout_id); ?>"
                                            <?php selected($settings['default_single_layout'], $layout_id); ?>>
                                        <?php echo esc_html($layout['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hkfn-form-description">Template used for individual funeral notice pages.</p>
                        </div>

                        <div class="hkfn-single-templates-grid">
                            <?php foreach ($this->available_single_templates as $template_id => $template): ?>
                                <div class="hkfn-template-card">
                                    <div class="hkfn-template-info">
                                        <div class="hkfn-template-header">
                                            <h4><?php echo esc_html($template['name']); ?></h4>
                                            <div class="hkfn-template-id">
                                                <strong>Template ID:</strong> <code><?php echo esc_html($template_id); ?></code>
                                            </div>
                                        </div>

                                        <p class="hkfn-template-description">
                                            <?php echo esc_html($template['description']); ?>
                                        </p>

                                        <div class="hkfn-template-features">
                                            <?php foreach ($template['features'] as $feature): ?>
                                                <span class="hkfn-feature-tag"><?php echo esc_html($feature); ?></span>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="hkfn-template-files">
                                            <div class="hkfn-file-info">
                                                <strong>Template:</strong> <code><?php echo esc_html($template['template_file']); ?></code>
                                            </div>
                                            <div class="hkfn-file-info">
                                                <strong>CSS:</strong> <code><?php echo esc_html($template['css_file']); ?></code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Grid Settings Tab -->
                    <div id="grid" class="tab-content">
                        <h3>Grid Settings</h3>
                        
                        <!-- DEBUG: Archive controls should show here. Enable value: <?php echo $settings['enable_archive_templates'] ? 'true' : 'false'; ?> -->
                        <div class="hkfn-form-group">
                            <label class="hkfn-toggle-switch">
                                <input type="checkbox"
                                       name="hkfn_module_settings[enable_archive_templates]"
                                       id="enable_archive_templates"
                                       value="1"
                                       <?php checked($settings['enable_archive_templates']); ?>>
                                <span class="hkfn-toggle-slider"></span>
                                <span class="hkfn-toggle-label">Enable Archive Page Templates</span>
                            </label>
                            <p class="hkfn-form-description">Enable plugin templates for the main funeral notices archive page. Disable if using Beaver Themer or custom theme templates.</p>
                        </div>
                        
                        <div class="hkfn-form-group hkfn-archive-dependent" style="<?php echo $settings['enable_archive_templates'] ? '' : 'display:none;opacity:0.5;'; ?>">
                            <label for="default_archive_layout">Default Archive Layout</label>
                            <select name="hkfn_module_settings[default_archive_layout]" id="default_archive_layout" <?php disabled(!$settings['enable_archive_templates']); ?>>
                                <?php foreach ($this->available_card_layouts as $layout_id => $layout): ?>
                                    <option value="<?php echo esc_attr($layout_id); ?>"
                                            <?php selected($settings['default_archive_layout'], $layout_id); ?>>
                                        <?php echo esc_html($layout['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hkfn-form-description">Layout used for the main funeral notices archive page when archive templates are enabled.</p>
                        </div>

                        <div class="hkfn-form-group">
                            <label for="card_spacing">Card Spacing (px)</label>
                            <input type="number" 
                                   name="hkfn_module_settings[card_spacing]" 
                                   id="card_spacing" 
                                   value="<?php echo esc_attr($settings['card_spacing']); ?>" 
                                   min="0" 
                                   max="50">
                            <p class="hkfn-form-description">Space between funeral notice cards in pixels.</p>
                        </div>
                        
                        <div class="hkfn-form-group">
                            <label for="image_aspect_ratio">Image Aspect Ratio</label>
                            <select name="hkfn_module_settings[image_aspect_ratio]" id="image_aspect_ratio">
                                <option value="1:1" <?php selected($settings['image_aspect_ratio'], '1:1'); ?>>1:1 (Square)</option>
                                <option value="4:3" <?php selected($settings['image_aspect_ratio'], '4:3'); ?>>4:3 (Standard)</option>
                                <option value="16:9" <?php selected($settings['image_aspect_ratio'], '16:9'); ?>>16:9 (Widescreen)</option>
                                <option value="3:4" <?php selected($settings['image_aspect_ratio'], '3:4'); ?>>3:4 (Portrait)</option>
                            </select>
                            <p class="hkfn-form-description">Aspect ratio for featured images in cards.</p>
                        </div>
                        
                        <h4>Responsive Breakpoints</h4>
                        <div class="hkfn-breakpoints-grid">
                            <div class="hkfn-form-group">
                                <label for="mobile_breakpoint">Mobile Breakpoint (px)</label>
                                <input type="number" 
                                       name="hkfn_module_settings[responsive_breakpoints][mobile]" 
                                       id="mobile_breakpoint" 
                                       value="<?php echo esc_attr($settings['responsive_breakpoints']['mobile']); ?>" 
                                       min="320" 
                                       max="1024">
                            </div>
                            
                            <div class="hkfn-form-group">
                                <label for="tablet_breakpoint">Tablet Breakpoint (px)</label>
                                <input type="number" 
                                       name="hkfn_module_settings[responsive_breakpoints][tablet]" 
                                       id="tablet_breakpoint" 
                                       value="<?php echo esc_attr($settings['responsive_breakpoints']['tablet']); ?>" 
                                       min="768" 
                                       max="1200">
                            </div>
                            
                            <div class="hkfn-form-group">
                                <label for="desktop_breakpoint">Desktop Breakpoint (px)</label>
                                <input type="number" 
                                       name="hkfn_module_settings[responsive_breakpoints][desktop]" 
                                       id="desktop_breakpoint" 
                                       value="<?php echo esc_attr($settings['responsive_breakpoints']['desktop']); ?>" 
                                       min="1024" 
                                       max="1920">
                            </div>
                        </div>
                        
                        <h4>Grid Columns</h4>
                        <div class="hkfn-breakpoints-grid">
                            <div class="hkfn-form-group">
                                <label for="mobile_columns">Mobile Columns</label>
                                <select name="hkfn_module_settings[grid_settings][mobile_columns]" id="mobile_columns">
                                    <option value="1" <?php selected($settings['grid_settings']['mobile_columns'], 1); ?>>1 Column</option>
                                    <option value="2" <?php selected($settings['grid_settings']['mobile_columns'], 2); ?>>2 Columns</option>
                                </select>
                            </div>
                            
                            <div class="hkfn-form-group">
                                <label for="tablet_columns">Tablet Columns</label>
                                <select name="hkfn_module_settings[grid_settings][tablet_columns]" id="tablet_columns">
                                    <option value="1" <?php selected($settings['grid_settings']['tablet_columns'], 1); ?>>1 Column</option>
                                    <option value="2" <?php selected($settings['grid_settings']['tablet_columns'], 2); ?>>2 Columns</option>
                                    <option value="3" <?php selected($settings['grid_settings']['tablet_columns'], 3); ?>>3 Columns</option>
                                </select>
                            </div>
                            
                            <div class="hkfn-form-group">
                                <label for="desktop_columns">Desktop Columns</label>
                                <select name="hkfn_module_settings[grid_settings][desktop_columns]" id="desktop_columns">
                                    <option value="2" <?php selected($settings['grid_settings']['desktop_columns'], 2); ?>>2 Columns</option>
                                    <option value="3" <?php selected($settings['grid_settings']['desktop_columns'], 3); ?>>3 Columns</option>
                                    <option value="4" <?php selected($settings['grid_settings']['desktop_columns'], 4); ?>>4 Columns</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Styles Tab -->
                    <div id="cards" class="tab-content">
                        <h3>Card Styles</h3>
                        
                        <div class="hkfn-form-group">
                            <label for="default_card_style">Default Card Style</label>
                            <select name="hkfn_module_settings[default_card_style]" id="default_card_style">
                                <?php foreach ($this->card_styles as $style_id => $style): ?>
                                    <option value="<?php echo esc_attr($style_id); ?>" 
                                            <?php selected($settings['default_card_style'], $style_id); ?>>
                                        <?php echo esc_html($style['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hkfn-form-description">Default card style when none is specified in shortcode.</p>
                        </div>
                        
                        <div class="hkfn-card-styles-preview">
                            <?php foreach ($this->card_styles as $style_id => $style): ?>
                                <div class="hkfn-card-style-demo">
                                    <div class="hkfn-card-preview-large <?php echo esc_attr($style['css_class']); ?>">
                                        <div class="hkfn-card-image"></div>
                                        <div class="hkfn-card-content">
                                            <h4>John Smith</h4>
                                            <p>January 15, 2025</p>
                                            <p class="hkfn-card-date">Service: 10:00 AM</p>
                                        </div>
                                    </div>
                                    <div class="hkfn-card-style-info">
                                        <h4><?php echo esc_html($style['name']); ?></h4>
                                        <p><?php echo esc_html($style['description']); ?></p>
                                        <code class="hkfn-style-code">card_style="<?php echo esc_html($style_id); ?>"</code>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Advanced Tab -->
                    <div id="advanced" class="tab-content">
                        <h3>Advanced Layout Options</h3>
                        <p class="hkfn-tab-description">Visual enhancement and layout behavior options. For performance settings like lazy loading and optimization, use the Performance module.</p>
                        
                        <div class="hkfn-advanced-grid">
                            <div class="hkfn-advanced-section">
                                <h4>👁️ Visual Enhancements</h4>
                                <div class="hkfn-form-group">
                                    <label class="hkfn-toggle-switch">
                                        <input type="checkbox"
                                               name="hkfn_module_settings[show_layout_previews]"
                                               id="show_layout_previews"
                                               value="1"
                                               <?php checked($settings['show_layout_previews']); ?>>
                                        <span class="hkfn-toggle-slider"></span>
                                        <span class="hkfn-toggle-label">Show Layout Previews</span>
                                    </label>
                                    <p class="hkfn-form-description">Display preview images for layouts in admin interface.</p>
                                </div>
                                
                                <div class="hkfn-form-group">
                                    <label class="hkfn-toggle-switch">
                                        <input type="checkbox"
                                               name="hkfn_module_settings[enable_hover_effects]"
                                               id="enable_hover_effects"
                                               value="1"
                                               <?php checked($settings['enable_hover_effects']); ?>>
                                        <span class="hkfn-toggle-slider"></span>
                                        <span class="hkfn-toggle-label">Enable Hover Effects</span>
                                    </label>
                                    <p class="hkfn-form-description">Add subtle hover effects to funeral notice cards.</p>
                                </div>
                                
                                <div class="hkfn-form-group">
                                    <label class="hkfn-toggle-switch">
                                        <input type="checkbox"
                                               name="hkfn_module_settings[enable_animations]"
                                               id="enable_animations"
                                               value="1"
                                               <?php checked($settings['enable_animations']); ?>>
                                        <span class="hkfn-toggle-slider"></span>
                                        <span class="hkfn-toggle-label">Enable Animations</span>
                                    </label>
                                    <p class="hkfn-form-description">Add smooth transitions and animations to layouts.</p>
                                </div>
                            </div>
                            
                            
                            <div class="hkfn-advanced-section">
                                <h4>🔧 Layout Features</h4>
                                <div class="hkfn-form-group">
                                    <label class="hkfn-toggle-switch">
                                        <input type="checkbox"
                                               name="hkfn_module_settings[enable_layout_switching]"
                                               id="enable_layout_switching"
                                               value="1"
                                               <?php checked($settings['enable_layout_switching']); ?>>
                                        <span class="hkfn-toggle-slider"></span>
                                        <span class="hkfn-toggle-label">Enable Layout Switching</span>
                                    </label>
                                    <p class="hkfn-form-description">Allow users to switch between layouts on the frontend.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php $this->render_submit_button(); ?>
        </form>
        
        <style>
            /* Override base module background */
            .hkfn-module-content {
                background: transparent !important;
            }
            
            .hkfn-layouts-admin {
                margin-top: 20px;
            }
            
            .hkfn-admin-tabs {
                background: transparent;
                border-radius: 12px;
                overflow: hidden;
            }
            
            .nav-tab-wrapper {
                margin-bottom: 0;
                border-bottom: none;
                background: transparent;
                padding-left: 0;
            }
            
            .nav-tab {
                margin-bottom: -1px;
                border-bottom: none;
                background: #e2e8f0;
                border: 1px solid #cbd5e1;
                border-radius: 12px 12px 0 0;
                margin-right: 4px;
                margin-left: 0;
                padding-left: 16px;
                color: #64748b;
                font-weight: 500;
                transition: all 0.3s ease;
                position: relative;
                z-index: 1;
            }
            
            .nav-tab:first-child {
                margin-left: 0;
            }
            
            .nav-tab:hover {
                background: #f1f5f9;
                color: #475569;
            }
            
            .nav-tab.nav-tab-active {
                background: #f8fafc;
                color: #334155;
                border-color: #cbd5e1;
                border-bottom-color: #f8fafc;
                font-weight: 600;
                z-index: 2;
            }
            
            .tab-content {
                display: none;
                padding: 24px;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                position: relative;
                z-index: 1;
            }
            
            .tab-content.active {
                display: block;
                border-top-left-radius: 0;
            }
            
            
            .tab-content h3 {
                margin-top: 0;
                margin-bottom: 15px;
                color: #333;
                font-size: 18px;
            }
            
            .hkfn-layouts-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            
            .hkfn-layout-card {
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                overflow: hidden;
                background: #fff;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
            }
            
            .hkfn-layout-card:hover {
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
                transform: translateY(-2px);
            }
            
            .hkfn-layout-preview {
                height: 150px;
                background: #f5f5f5;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            
            .hkfn-layout-preview img {
                max-width: 100%;
                max-height: 100%;
                object-fit: cover;
            }
            
            .hkfn-layout-info {
                padding: 15px;
            }
            
            .hkfn-layout-header {
                margin-bottom: 10px;
            }
            
            .hkfn-layout-header input {
                margin-right: 8px;
            }
            
            .hkfn-layout-description {
                color: #666;
                font-size: 14px;
                margin-bottom: 10px;
            }
            
            .hkfn-layout-features {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .hkfn-feature-tag {
                background: #e3f2fd;
                color: #1976d2;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500;
            }
            
            .hkfn-breakpoints-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 15px;
            }
            
            .hkfn-card-styles-preview {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }
            
            .hkfn-card-style-demo {
                text-align: center;
            }
            
            .hkfn-card-preview {
                width: 200px;
                margin: 0 auto 15px;
                border-radius: 8px;
                overflow: hidden;
                background: #fff;
            }
            
            .hkfn-card-preview-large {
                width: 280px;
                margin: 0 auto 15px;
                border-radius: 12px;
                overflow: hidden;
                background: #fff;
            }
            
            .hkfn-card-standard {
                border: 1px solid #ddd;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .hkfn-card-elevated {
                border: none;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            .hkfn-card-outlined {
                border: 2px solid #ddd;
                box-shadow: none;
            }
            
            .hkfn-card-minimal {
                border: none;
                box-shadow: none;
                background: #f9f9f9;
            }
            
            .hkfn-card-image {
                height: 120px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            
            .hkfn-card-preview-large .hkfn-card-image {
                height: 160px;
            }
            
            .hkfn-card-content {
                padding: 15px;
            }
            
            .hkfn-card-content h4 {
                margin: 0 0 5px 0;
                font-size: 16px;
            }
            
            .hkfn-card-content p {
                margin: 0 0 5px 0;
                color: #666;
                font-size: 14px;
            }
            
            .hkfn-card-date {
                color: #888 !important;
                font-size: 12px !important;
            }
            
            .hkfn-card-style-info h4 {
                margin-bottom: 5px;
            }
            
            .hkfn-card-style-info p {
                color: #666;
                font-size: 13px;
            }
            
            .hkfn-tab-description {
                background: #f8fafc;
                border-left: 4px solid #667eea;
                border-radius: 0 8px 8px 0;
                padding: 16px 20px;
                margin-bottom: 24px;
                font-size: 14px;
                color: #475569;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }
            
            .hkfn-shortcode-example {
                background: #f0f6fc;
                border: 1px solid #c9def7;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 24px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }
            
            .hkfn-shortcode-example strong {
                color: #0969da;
                display: block;
                margin-bottom: 8px;
            }
            
            .hkfn-shortcode-example code {
                background: #fff;
                border: 1px solid #d1d9e0;
                border-radius: 8px;
                padding: 10px 16px;
                font-family: 'Courier New', monospace;
                font-size: 13px;
                display: inline-block;
                margin-right: 12px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            }
            
            .hkfn-shortcode-note {
                color: #656d76;
                font-size: 12px;
                font-style: italic;
            }
            
            .hkfn-layout-id {
                margin-top: 8px;
                padding: 8px 12px;
                background: #f0f6fc;
                border-radius: 8px;
                border: 1px solid #c9def7;
            }
            
            .hkfn-layout-id code {
                background: #fff;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: 'Courier New', monospace;
                font-size: 12px;
                color: #0969da;
            }
            
            .hkfn-style-code {
                display: inline-block;
                background: #f6f8fa;
                border: 1px solid #d1d9e0;
                border-radius: 6px;
                padding: 6px 10px;
                font-family: 'Courier New', monospace;
                font-size: 11px;
                color: #24292f;
                margin-top: 8px;
            }
            
            .hkfn-advanced-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 15px;
            }
            
            .hkfn-advanced-section {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
            }
            
            .hkfn-advanced-section h4 {
                margin: 0 0 15px 0;
                color: #2c3e50;
                font-size: 16px;
                font-weight: 600;
            }
            
            /* Form Group Styling */
            .hkfn-form-group {
                margin-bottom: 20px;
            }
            
            .hkfn-form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #374151;
            }
            
            .hkfn-form-group select,
            .hkfn-form-group input[type="number"] {
                width: 100%;
                max-width: 300px;
                padding: 8px 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                font-size: 14px;
                transition: border-color 0.3s ease;
            }
            
            .hkfn-form-group select:focus,
            .hkfn-form-group input[type="number"]:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            
            .hkfn-form-description {
                margin-top: 6px;
                font-size: 13px;
                color: #6b7280;
                line-height: 1.4;
            }
            
            /* Toggle Switch Styling - Now handled by dashboard.css */
            
            /* Improved form button styling */
            .hkfn-admin-tabs + .submit {
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                margin: 0 -24px -24px -24px;
                padding: 20px 24px;
                border-radius: 0 0 12px 12px;
                text-align: left;
            }
            
            .submit .button-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                color: #fff;
                padding: 12px 24px;
                font-size: 14px;
                font-weight: 600;
                border-radius: 8px;
                margin-right: 12px;
                box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
                transition: all 0.3s ease;
                cursor: pointer;
            }
            
            .submit .button-primary:hover {
                background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            }
            
            .submit .button-secondary {
                background: #e2e8f0;
                border: 1px solid #cbd5e1;
                color: #475569;
                padding: 12px 24px;
                font-size: 14px;
                font-weight: 500;
                border-radius: 8px;
                transition: all 0.3s ease;
                cursor: pointer;
            }
            
            .submit .button-secondary:hover {
                background: #cbd5e1;
                border-color: #94a3b8;
                color: #334155;
                transform: translateY(-1px);
            }
        </style>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.nav-tab');
                const contents = document.querySelectorAll('.tab-content');
                
                tabs.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        tabs.forEach(t => t.classList.remove('nav-tab-active'));
                        contents.forEach(c => c.classList.remove('active'));
                        
                        this.classList.add('nav-tab-active');
                        
                        const target = this.getAttribute('href').substring(1);
                        document.getElementById(target).classList.add('active');
                    });
                });
                
                // Card style preview functionality
                const cardStyleSelect = document.getElementById('default_card_style');
                if (cardStyleSelect) {
                    cardStyleSelect.addEventListener('change', function() {
                        const selectedStyle = this.value;
                        const previews = document.querySelectorAll('.hkfn-card-preview, .hkfn-card-preview-large');
                        
                        previews.forEach(preview => {
                            // Remove all style classes
                            preview.classList.remove('hkfn-card-standard', 'hkfn-card-elevated', 'hkfn-card-outlined', 'hkfn-card-minimal');
                            // Add the selected style class
                            preview.classList.add('hkfn-card-' + selectedStyle);
                        });
                    });
                }
                
                // Archive templates toggle functionality
                const archiveToggle = document.getElementById('enable_archive_templates');
                if (archiveToggle) {
                    archiveToggle.addEventListener('change', function() {
                        const dependentElements = document.querySelectorAll('.hkfn-archive-dependent');
                        const archiveSelect = document.getElementById('default_archive_layout');
                        
                        dependentElements.forEach(element => {
                            if (this.checked) {
                                element.style.display = '';
                                element.style.opacity = '1';
                                if (archiveSelect) archiveSelect.disabled = false;
                            } else {
                                element.style.display = 'none';
                                element.style.opacity = '0.5';
                                if (archiveSelect) archiveSelect.disabled = true;
                            }
                        });
                    });
                }
            });
        </script>
        <?php
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submission(): bool {
        $result = parent::handle_form_submission();
        
        // Sync archive layout setting after saving
        if ($result) {
            $this->sync_archive_layout_setting();
        }
        
        return $result;
    }
    
    /**
     * Sanitize settings with specific validation
     */
    protected function sanitize_settings(array $settings): array {
        $sanitized = [];
        
        
        // Archive templates enable/disable
        $sanitized['enable_archive_templates'] = !empty($settings['enable_archive_templates']);
        
        // Archive layout validation
        $valid_card_layouts = array_keys($this->available_card_layouts);
        $sanitized['default_archive_layout'] = in_array($settings['default_archive_layout'] ?? '', $valid_card_layouts)
            ? $settings['default_archive_layout']
            : 'modern';

        // Single layout validation
        $valid_single_templates = array_keys($this->available_single_templates);
        $sanitized['default_single_layout'] = in_array($settings['default_single_layout'] ?? '', $valid_single_templates)
            ? $settings['default_single_layout']
            : 'current';
        
        // Card style validation
        $valid_card_styles = array_keys($this->card_styles);
        $sanitized['default_card_style'] = in_array($settings['default_card_style'] ?? '', $valid_card_styles) 
            ? $settings['default_card_style'] 
            : 'standard';
        
        // Numeric validations
        $sanitized['card_spacing'] = max(0, min(50, (int) ($settings['card_spacing'] ?? 20)));
        
        // Responsive breakpoints
        $sanitized['responsive_breakpoints'] = [
            'mobile' => max(320, min(1024, (int) ($settings['responsive_breakpoints']['mobile'] ?? 768))),
            'tablet' => max(768, min(1200, (int) ($settings['responsive_breakpoints']['tablet'] ?? 1024))),
            'desktop' => max(1024, min(1920, (int) ($settings['responsive_breakpoints']['desktop'] ?? 1200)))
        ];
        
        // Grid settings
        $sanitized['grid_settings'] = [
            'mobile_columns' => in_array((int) ($settings['grid_settings']['mobile_columns'] ?? 1), [1, 2]) 
                ? (int) $settings['grid_settings']['mobile_columns'] 
                : 1,
            'tablet_columns' => in_array((int) ($settings['grid_settings']['tablet_columns'] ?? 2), [1, 2, 3]) 
                ? (int) $settings['grid_settings']['tablet_columns'] 
                : 2,
            'desktop_columns' => in_array((int) ($settings['grid_settings']['desktop_columns'] ?? 3), [2, 3, 4]) 
                ? (int) $settings['grid_settings']['desktop_columns'] 
                : 3
        ];
        
        // Image aspect ratio
        $valid_ratios = ['1:1', '4:3', '16:9', '3:4'];
        $sanitized['image_aspect_ratio'] = in_array($settings['image_aspect_ratio'] ?? '', $valid_ratios) 
            ? $settings['image_aspect_ratio'] 
            : '4:3';
        
        // Boolean settings
        $boolean_settings = [
            'show_layout_previews', 'enable_hover_effects', 'enable_animations',
            'enable_layout_switching'
        ];
        
        foreach ($boolean_settings as $setting) {
            $sanitized[$setting] = !empty($settings[$setting]);
        }
        
        return $sanitized;
    }
}
