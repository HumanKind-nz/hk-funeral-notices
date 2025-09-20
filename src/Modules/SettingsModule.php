<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Modules;

use WeaveStudios\FuneralNotices\Address\AddressFieldManager;

/**
 * Settings Module
 * 
 * Core plugin configuration and settings management.
 * Handles display modes, pagination, shortcode documentation,
 * and essential plugin settings.
 * 
 * @since 2.0.0
 */
class SettingsModule extends BaseModule {
    
    protected array $default_settings = [
        'default_layout' => 'modern',
        'posts_per_page' => 12,
        'columns' => 3,
        'show_search' => true,
        'show_pagination' => true,
        'excerpt_length' => 150,
        'date_format' => 'F j, Y',
        'time_format' => 'g:i a',
        'show_featured_image' => true,
        'image_size' => 'medium',
        'enable_streaming' => true,
        'cache_duration' => 3600,
        'enable_seo' => true,
        'meta_description_length' => 160,
        'single_slug' => 'funeral-notice',
        'address_field_mode' => 'auto',
        'google_places_api_key' => '',
        'tribute_form_url' => '',
        'default_person_image' => '',
        'location_name' => ''
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'settings',
            'Core Configuration',
            'Core plugin configuration and settings management',
            '2.0.0'
        );
    }
    
    /**
     * Initialize the module
     */
    public function init(): void {
        parent::init();
        
        // Register settings with WordPress
        add_action('admin_init', [$this, 'register_settings']);
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_hk-funeral-notices/weave-funeral-notices.php', [$this, 'add_settings_link']);
        
        // Hook into settings save to flush rewrite rules if slug changed
        add_action('update_option_' . $this->get_settings_option_name(), [$this, 'handle_slug_change'], 10, 2);
    }
    
    /**
     * Get module features
     */
    public function get_features(): array {
        return [
            'Display Mode Configuration (5 layout options)',
            'URL Structure Management',
            'Pagination & Search Controls',
            'Shortcode Documentation & Usage',
            'Legacy Settings Migration',
            'SEO & Meta Settings',
            'Image & Content Settings',
            'Performance Configuration'
        ];
    }
    
    /**
     * Get settings option name
     *
     * Override to use simpler option name for core settings
     */
    protected function get_settings_option_name(): string {
        return 'wfn_module_settings';
    }

    /**
     * Register settings with WordPress
     */
    public function register_settings(): void {
        register_setting(
            'wfn_settings_group',
            $this->get_settings_option_name(),
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings']
            ]
        );
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link(array $links): array {
        $settings_link = '<a href="' . $this->get_admin_url() . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Render module admin content
     */
    protected function render_module_admin_content(): void {
        $settings = $this->get_settings();
        ?>
        <form method="post" action="">
            <?php $this->render_nonce_field(); ?>
            
            <div class="wfn-settings-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active">General</a>
                    <a href="#display" class="nav-tab">Display</a>
                    <a href="#content" class="nav-tab">Content</a>
                    <a href="#advanced" class="nav-tab">Advanced</a>
                </nav>
                
                <!-- General Settings -->
                <div id="general" class="tab-content active">
                    <h3>General Settings</h3>
                    
                    <div class="wfn-form-group">
                        <label for="default_layout">Default Layout</label>
                        <select name="wfn_module_settings[default_layout]" id="default_layout">
                            <option value="firehawk" <?php selected($settings['default_layout'], 'firehawk'); ?>>Firehawk Compatible</option>
                            <option value="modern" <?php selected($settings['default_layout'], 'modern'); ?>>Modern Memorial Grid</option>
                            <option value="elegant" <?php selected($settings['default_layout'], 'elegant'); ?>>Elegant Funeral Grid</option>
                            <option value="gallery" <?php selected($settings['default_layout'], 'gallery'); ?>>Memorial Photo Gallery</option>
                            <option value="minimal" <?php selected($settings['default_layout'], 'minimal'); ?>>Simple Memorial List</option>
                        </select>
                        <p class="wfn-form-description">Default layout for funeral notices when no layout is specified in shortcode.</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label for="posts_per_page">Posts Per Page</label>
                        <input type="number" 
                               name="wfn_module_settings[posts_per_page]" 
                               id="posts_per_page" 
                               value="<?php echo esc_attr($settings['posts_per_page']); ?>" 
                               min="1" 
                               max="50">
                        <p class="wfn-form-description">Number of funeral notices to display per page.</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label for="columns">Grid Columns</label>
                        <select name="wfn_module_settings[columns]" id="columns">
                            <option value="2" <?php selected($settings['columns'], 2); ?>>2 Columns</option>
                            <option value="3" <?php selected($settings['columns'], 3); ?>>3 Columns</option>
                            <option value="4" <?php selected($settings['columns'], 4); ?>>4 Columns</option>
                        </select>
                        <p class="wfn-form-description">Default number of columns for grid layouts.</p>
                    </div>

                    <div class="wfn-form-group">
                        <label for="default_person_image">Default Person Image</label>
                        <div class="wfn-image-upload">
                            <input type="hidden"
                                   name="wfn_module_settings[default_person_image]"
                                   id="default_person_image"
                                   value="<?php echo esc_attr($settings['default_person_image']); ?>">
                            <div class="wfn-image-preview">
                                <?php if (!empty($settings['default_person_image'])): ?>
                                    <img src="<?php echo esc_url($settings['default_person_image']); ?>" alt="Default person image preview" style="max-width: 150px; height: auto;">
                                    <button type="button" class="wfn-remove-image" style="display: block;">Remove Image</button>
                                <?php else: ?>
                                    <p>No image selected</p>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="wfn-upload-image button">Choose Image</button>
                        </div>
                        <p class="wfn-form-description">Default image to show when no person image is uploaded. This appears in listings and single pages.</p>
                    </div>

                    <div class="wfn-form-group">
                        <label class="wfn-toggle-switch">
                            <input type="checkbox"
                                   name="wfn_module_settings[show_search]"
                                   id="show_search"
                                   value="1"
                                   <?php checked($settings['show_search']); ?>>
                            <span class="wfn-toggle-slider"></span>
                            <span class="wfn-toggle-label">Enable Search Form</span>
                        </label>
                        <p class="wfn-form-description">Display search form above funeral notices.</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label class="wfn-toggle-switch">
                            <input type="checkbox"
                                   name="wfn_module_settings[show_pagination]"
                                   id="show_pagination"
                                   value="1"
                                   <?php checked($settings['show_pagination']); ?>>
                            <span class="wfn-toggle-slider"></span>
                            <span class="wfn-toggle-label">Enable Pagination</span>
                        </label>
                        <p class="wfn-form-description">Display pagination controls below funeral notices.</p>
                    </div>
                    
                    <!-- Address Field Settings -->
                    <h4 style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">Address Field Settings</h4>
                    
                    <?php $this->render_address_field_status(); ?>
                    
                    <div class="wfn-form-group">
                        <label for="address_field_mode">Address Field Mode</label>
                        <select name="wfn_module_settings[address_field_mode]" id="address_field_mode">
                            <option value="auto" <?php selected($settings['address_field_mode'], 'auto'); ?>>Auto-detect (Recommended)</option>
                            <option value="acfe" <?php selected($settings['address_field_mode'], 'acfe'); ?>>Force ACFE Pro (requires ACFE Pro plugin)</option>
                            <option value="custom" <?php selected($settings['address_field_mode'], 'custom'); ?>>Force Native Fields (no dependencies)</option>
                        </select>
                        <p class="wfn-form-description">Choose which address field system to use. Auto-detect will use ACFE Pro if available, otherwise use native fields.</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label for="google_places_api_key">Google Places API Key</label>
                        <input type="password" 
                               name="wfn_module_settings[google_places_api_key]" 
                               id="google_places_api_key" 
                               value="<?php echo esc_attr($settings['google_places_api_key']); ?>" 
                               placeholder="AIzaSyD..."
                               style="width: 100%; max-width: 400px;">
                        <p class="wfn-form-description">
                            Required for both ACFE Pro and Native address fields. 
                            <a href="https://developers.google.com/maps/documentation/places/web-service/get-api-key" target="_blank">Get API Key</a>
                            <?php if (!empty($settings['google_places_api_key'])): ?>
                                <br><span style="color: #00a32a;">✓ API Key configured</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <!-- Display Settings -->
                <div id="display" class="tab-content">
                    <h3>Display Settings</h3>
                    
                    <div class="wfn-form-group">
                        <label for="date_format">Date Format</label>
                        <select name="wfn_module_settings[date_format]" id="date_format">
                            <option value="F j, Y" <?php selected($settings['date_format'], 'F j, Y'); ?>>January 1, 2025</option>
                            <option value="j F Y" <?php selected($settings['date_format'], 'j F Y'); ?>>1 January 2025</option>
                            <option value="Y-m-d" <?php selected($settings['date_format'], 'Y-m-d'); ?>>2025-01-01</option>
                            <option value="d/m/Y" <?php selected($settings['date_format'], 'd/m/Y'); ?>>01/01/2025</option>
                            <option value="m/d/Y" <?php selected($settings['date_format'], 'm/d/Y'); ?>>01/01/2025</option>
                        </select>
                        <p class="wfn-form-description">Format for displaying dates in funeral notices.</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label for="time_format">Time Format</label>
                        <select name="wfn_module_settings[time_format]" id="time_format">
                            <option value="g:i a" <?php selected($settings['time_format'], 'g:i a'); ?>>2:30 pm</option>
                            <option value="G:i" <?php selected($settings['time_format'], 'G:i'); ?>>14:30</option>
                            <option value="h:i A" <?php selected($settings['time_format'], 'h:i A'); ?>>02:30 PM</option>
                        </select>
                        <p class="wfn-form-description">Format for displaying times in funeral notices.</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label class="wfn-toggle-switch">
                            <input type="checkbox"
                                   name="wfn_module_settings[show_featured_image]"
                                   id="show_featured_image"
                                   value="1"
                                   <?php checked($settings['show_featured_image']); ?>>
                            <span class="wfn-toggle-slider"></span>
                            <span class="wfn-toggle-label">Show Featured Images</span>
                        </label>
                        <p class="wfn-form-description">Display featured images in funeral notice cards.</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label for="image_size">Image Size</label>
                        <select name="wfn_module_settings[image_size]" id="image_size">
                            <option value="thumbnail" <?php selected($settings['image_size'], 'thumbnail'); ?>>Thumbnail</option>
                            <option value="medium" <?php selected($settings['image_size'], 'medium'); ?>>Medium</option>
                            <option value="large" <?php selected($settings['image_size'], 'large'); ?>>Large</option>
                            <option value="full" <?php selected($settings['image_size'], 'full'); ?>>Full Size</option>
                        </select>
                        <p class="wfn-form-description">Size of featured images in funeral notice displays.</p>
                    </div>
                </div>
                
                <!-- Content Settings -->
                <div id="content" class="tab-content">
                    <h3>Content Settings</h3>
                    
                    <div class="wfn-form-group">
                        <label for="excerpt_length">Excerpt Length</label>
                        <input type="number" 
                               name="wfn_module_settings[excerpt_length]" 
                               id="excerpt_length" 
                               value="<?php echo esc_attr($settings['excerpt_length']); ?>" 
                               min="50" 
                               max="500">
                        <p class="wfn-form-description">Maximum number of characters for excerpt text.</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label class="wfn-toggle-switch">
                            <input type="checkbox"
                                   name="wfn_module_settings[enable_streaming]"
                                   id="enable_streaming"
                                   value="1"
                                   <?php checked($settings['enable_streaming']); ?>>
                            <span class="wfn-toggle-slider"></span>
                            <span class="wfn-toggle-label">Enable Streaming Integration</span>
                        </label>
                        <p class="wfn-form-description">Show streaming service information and links.</p>
                    </div>

                    <div class="wfn-form-group">
                        <label for="tribute_form_url">Tribute Form URL</label>
                        <input type="url"
                               name="wfn_module_settings[tribute_form_url]"
                               id="tribute_form_url"
                               value="<?php echo esc_attr($settings['tribute_form_url']); ?>"
                               placeholder="https://yoursite.com/contact/"
                               class="wfn-wide-input">
                        <p class="wfn-form-description">
                            URL for tribute form page. Use placeholders for dynamic values:<br>
                            <strong>{firstname}</strong> - person's first name<br>
                            <strong>{lastname}</strong> - person's last name<br>
                            <strong>{fullname}</strong> - person's full name<br>
                            Example: <code>https://yoursite.com/contact/?tribute={firstname}+{lastname}</code><br>
                            <em>Legacy: URLs without placeholders will have ?tribute=First+Last automatically appended</em>
                        </p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label class="wfn-toggle-switch">
                            <input type="checkbox"
                                   name="wfn_module_settings[enable_seo]"
                                   id="enable_seo"
                                   value="1"
                                   <?php checked($settings['enable_seo']); ?>>
                            <span class="wfn-toggle-slider"></span>
                            <span class="wfn-toggle-label">Enable SEO Features</span>
                        </label>
                        <p class="wfn-form-description">Generate meta descriptions and structured data.</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label for="meta_description_length">Meta Description Length</label>
                        <input type="number" 
                               name="wfn_module_settings[meta_description_length]" 
                               id="meta_description_length" 
                               value="<?php echo esc_attr($settings['meta_description_length']); ?>" 
                               min="120" 
                               max="200">
                        <p class="wfn-form-description">Maximum length for generated meta descriptions.</p>
                    </div>
                </div>
                
                <!-- Advanced Settings -->
                <div id="advanced" class="tab-content">
                    <h3>Advanced Settings</h3>
                    
                    <div class="wfn-form-group">
                        <label for="cache_duration">Cache Duration (seconds)</label>
                        <input type="number" 
                               name="wfn_module_settings[cache_duration]" 
                               id="cache_duration" 
                               value="<?php echo esc_attr($settings['cache_duration']); ?>" 
                               min="300" 
                               max="86400">
                        <p class="wfn-form-description">How long to cache funeral notice data (3600 = 1 hour).</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label for="single_slug">Single Funeral Notice URL Slug</label>
                        <input type="text" 
                               name="wfn_module_settings[single_slug]" 
                               id="single_slug" 
                               value="<?php echo esc_attr($settings['single_slug']); ?>" 
                               pattern="[a-z0-9-]+"
                               maxlength="50">
                        <p class="wfn-form-description">
                            URL slug for individual funeral notices (e.g., "funeral-notice", "memorial", "tribute"). 
                            Only lowercase letters, numbers, and hyphens allowed. 
                            <strong>Note:</strong> After changing this, go to Settings → Permalinks and click "Save Changes" to update WordPress permalinks.
                        </p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <label for="location_name">Location/Business Name</label>
                        <input type="text"
                               name="wfn_module_settings[location_name]"
                               id="location_name"
                               value="<?php echo esc_attr($settings['location_name']); ?>"
                               placeholder="e.g., Morrison's Funeral Home"
                               class="wfn-wide-input">
                        <p class="wfn-form-description">Your funeral home or business name. Used in SEO titles and meta descriptions for the funeral notices archive page. If left empty, your WordPress site name will be used instead.</p>
                    </div>
                    
                    <div class="wfn-form-group">
                        <h4>Shortcode Documentation</h4>
                        <div class="wfn-shortcode-docs">
                            <p><strong>Basic Usage:</strong></p>
                            <code>[funeral_notices]</code>
                            
                            <p><strong>With Parameters:</strong></p>
                            <code>[funeral_notices layout="modern" columns="3" per_page="12" show_search="yes"]</code>
                            
                            <p><strong>Available Parameters:</strong></p>
                            <ul>
                                <li><strong>layout:</strong> current, firehawk, modern, elegant, gallery, minimal</li>
                                <li><strong>columns:</strong> 2, 3, 4</li>
                                <li><strong>per_page:</strong> Number of notices per page</li>
                                <li><strong>show_search:</strong> yes, no</li>
                                <li><strong>show_pagination:</strong> yes, no</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php $this->render_submit_button(); ?>
        </form>
        
        <style>
            .wfn-settings-tabs {
                margin-top: 20px;
            }
            
            .nav-tab-wrapper {
                margin-bottom: 20px;
            }
            
            .tab-content {
                display: none;
            }
            
            .tab-content.active {
                display: block;
            }
            
            .wfn-shortcode-docs {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 4px;
                margin-top: 10px;
            }
            
            .wfn-shortcode-docs code {
                background: #fff;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 13px;
            }
            
            .wfn-shortcode-docs ul {
                margin-left: 20px;
            }
            
            .wfn-shortcode-docs li {
                margin-bottom: 5px;
            }
        </style>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.nav-tab');
                const contents = document.querySelectorAll('.tab-content');
                
                tabs.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Remove active class from all tabs and contents
                        tabs.forEach(t => t.classList.remove('nav-tab-active'));
                        contents.forEach(c => c.classList.remove('active'));
                        
                        // Add active class to clicked tab
                        this.classList.add('nav-tab-active');
                        
                        // Show corresponding content
                        const target = this.getAttribute('href').substring(1);
                        document.getElementById(target).classList.add('active');
                    });
                });
            });
        </script>
        <?php
    }
    
    /**
     * Render address field status indicator
     */
    private function render_address_field_status(): void {
        $address_manager = new AddressFieldManager();
        $status_info = $address_manager->get_status_info();
        $current_mode = $status_info['mode'];
        $info = $status_info['info'];
        $warnings = $status_info['warnings'];
        
        ?>
        <div class="wfn-address-field-status" style="margin: 15px 0; padding: 15px; border-left: 4px solid <?php echo esc_attr($info['color']); ?>; background: #f9f9f9;">
            <h4 style="margin: 0 0 8px 0;">
                <?php echo esc_html($info['icon']); ?> Current Mode: <?php echo esc_html($info['label']); ?>
            </h4>
            <p style="margin: 0 0 8px 0; color: #666;">
                <?php echo esc_html($info['description']); ?>
            </p>
            
            <?php if (!empty($warnings)): ?>
                <div style="margin-top: 10px;">
                    <?php foreach ($warnings as $warning): ?>
                        <p style="color: #d63638; margin: 5px 0;">
                            ⚠️ <?php echo esc_html($warning); ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 10px; font-size: 12px; color: #666;">
                <strong>Status:</strong>
                ACFE Available: <?php echo $status_info['acfe_available'] ? '✓ Yes' : '✗ No'; ?> |
                API Key: <?php echo $status_info['api_configured'] ? '✓ Configured' : '✗ Not configured'; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sanitize settings with specific validation
     */
    protected function sanitize_settings(array $settings): array {
        // Start with defaults to ensure all required keys exist
        $sanitized = $this->default_settings;
        
        // Layout validation
        $valid_layouts = ['current', 'firehawk', 'modern', 'elegant', 'gallery', 'minimal'];
        $sanitized['default_layout'] = in_array($settings['default_layout'] ?? '', $valid_layouts) 
            ? $settings['default_layout'] 
            : 'modern';
        
        // Numeric validations
        $sanitized['posts_per_page'] = max(1, min(50, (int) ($settings['posts_per_page'] ?? 12)));
        $sanitized['columns'] = in_array((int) ($settings['columns'] ?? 3), [2, 3, 4]) 
            ? (int) $settings['columns'] 
            : 3;
        $sanitized['excerpt_length'] = max(50, min(500, (int) ($settings['excerpt_length'] ?? 150)));
        $sanitized['cache_duration'] = max(300, min(86400, (int) ($settings['cache_duration'] ?? 3600)));
        $sanitized['meta_description_length'] = max(120, min(200, (int) ($settings['meta_description_length'] ?? 160)));
        
        // Boolean settings
        $boolean_settings = [
            'show_search', 'show_pagination', 'show_featured_image', 
            'enable_streaming', 'enable_seo'
        ];
        
        foreach ($boolean_settings as $setting) {
            $sanitized[$setting] = !empty($settings[$setting]);
        }
        
        // String validations
        $valid_date_formats = ['F j, Y', 'j F Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'];
        $sanitized['date_format'] = in_array($settings['date_format'] ?? '', $valid_date_formats) 
            ? $settings['date_format'] 
            : 'F j, Y';
        
        $valid_time_formats = ['g:i a', 'G:i', 'h:i A'];
        $sanitized['time_format'] = in_array($settings['time_format'] ?? '', $valid_time_formats) 
            ? $settings['time_format'] 
            : 'g:i a';
        
        $valid_image_sizes = ['thumbnail', 'medium', 'large', 'full'];
        $sanitized['image_size'] = in_array($settings['image_size'] ?? '', $valid_image_sizes) 
            ? $settings['image_size'] 
            : 'medium';
        
        // Slug validation - only lowercase letters, numbers, and hyphens
        $slug = sanitize_title($settings['single_slug'] ?? 'funeral-notice');
        $sanitized['single_slug'] = preg_match('/^[a-z0-9-]+$/', $slug) && strlen($slug) > 0 && strlen($slug) <= 50 
            ? $slug 
            : 'funeral-notice';
        
        // Address field mode validation
        $valid_address_modes = ['auto', 'acfe', 'custom'];
        $sanitized['address_field_mode'] = in_array($settings['address_field_mode'] ?? '', $valid_address_modes) 
            ? $settings['address_field_mode'] 
            : 'auto';
        
        // Google Places API key validation
        $api_key = trim($settings['google_places_api_key'] ?? '');
        // Google API keys are typically 35-40 chars, but allow any non-empty string that looks like an API key
        $sanitized['google_places_api_key'] = !empty($api_key) && strlen($api_key) >= 10 ? $api_key : '';

        // Tribute form URL validation
        $tribute_url = trim($settings['tribute_form_url'] ?? '');
        $sanitized['tribute_form_url'] = !empty($tribute_url) && filter_var($tribute_url, FILTER_VALIDATE_URL) ? $tribute_url : '';

        // Default person image validation
        $default_image = trim($settings['default_person_image'] ?? '');
        $sanitized['default_person_image'] = !empty($default_image) && filter_var($default_image, FILTER_VALIDATE_URL) ? $default_image : '';

        // Location name validation
        $location_name = trim($settings['location_name'] ?? '');
        $sanitized['location_name'] = sanitize_text_field($location_name);

        return $sanitized;
    }
    
    /**
     * Handle slug change - flush rewrite rules if needed
     */
    public function handle_slug_change(array $old_value, array $new_value): void {
        $old_slug = $old_value['single_slug'] ?? 'funeral-notice';
        $new_slug = $new_value['single_slug'] ?? 'funeral-notice';
        
        if ($old_slug !== $new_slug) {
            // Flush rewrite rules to update permalinks
            flush_rewrite_rules();
            
            // Add admin notice to inform user
            add_action('admin_notices', function() use ($new_slug) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>Funeral Notice URL slug updated to "' . esc_html($new_slug) . '".</strong> ';
                echo 'Permalinks have been automatically updated.</p>';
                echo '</div>';
            });
        }
    }

    /**
     * Enqueue admin assets for the Settings module
     */
    public function enqueue_admin_assets($hook): void {
        parent::enqueue_admin_assets($hook);

        // Debug logging removed for production

        // Always add a basic script to test if method works
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'console.log("WFN Settings: Basic script loaded on hook: ' . $hook . '");');

        // Load media script on our settings page
        if (strpos($hook, 'weave-funeral-notices') === false && strpos($hook, 'wfn-module-settings') === false) {
            // Debug logging removed for production
            return;
        }

        // Debug logging removed for production

        // Enqueue WordPress media library and dependencies
        wp_enqueue_media();
        wp_enqueue_script('jquery');

        // Add the media script directly to jQuery instead of creating a separate script
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                console.log("WFN Settings Media JS loaded");
                var mediaUploader;

                // Handle image upload
                $(document).on("click", ".wfn-upload-image", function(e) {
                    e.preventDefault();
                    console.log("Upload button clicked");

                    var button = $(this);
                    var targetInput = button.closest(".wfn-image-upload").find("input[type=hidden]");
                    var preview = button.closest(".wfn-image-upload").find(".wfn-image-preview");

                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }

                    mediaUploader = wp.media({
                        title: "Select Default Person Image",
                        button: {
                            text: "Use this image"
                        },
                        multiple: false,
                        library: {
                            type: "image"
                        }
                    });

                    mediaUploader.on("select", function() {
                        var attachment = mediaUploader.state().get("selection").first().toJSON();
                        console.log("Image selected:", attachment.url);
                        targetInput.val(attachment.url);
                        preview.html(
                            "<img src=\"" + attachment.url + "\" alt=\"Default person image preview\" style=\"max-width: 150px; height: auto;\">" +
                            "<button type=\"button\" class=\"wfn-remove-image\" style=\"display: block;\">Remove Image</button>"
                        );
                    });

                    mediaUploader.open();
                });

                // Handle image removal
                $(document).on("click", ".wfn-remove-image", function(e) {
                    e.preventDefault();
                    console.log("Remove button clicked");
                    var button = $(this);
                    var targetInput = button.closest(".wfn-image-upload").find("input[type=hidden]");
                    var preview = button.closest(".wfn-image-upload").find(".wfn-image-preview");

                    targetInput.val("");
                    preview.html("<p>No image selected</p>");
                });
            });
        ', 'after');
    }
}