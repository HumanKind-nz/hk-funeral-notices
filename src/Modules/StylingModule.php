<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Modules;

/**
 * Styling Module
 * 
 * Comprehensive visual customization and theming system.
 * Manages color schemes, typography, layout spacing, and custom CSS.
 * 
 * @since 2.0.0
 */
class StylingModule extends BaseModule {
    
    protected array $default_settings = [
        'color_scheme' => 'custom',
        'custom_colors' => [
            'primary' => '#2c5282',
            'secondary' => '#d4af37',
            'accent' => '#667eea',
            'background' => '#ffffff',
            'card_background' => '#ffffff',
            'text_primary' => '#2d3748',
            'text_secondary' => '#718096',
            'text_muted' => '#a0aec0',
            'border' => '#e2e8f0',
            'shadow' => 'rgba(0, 0, 0, 0.1)',
            'row_alternate' => '#f8fafc'
        ],
        'typography' => [
            'heading_font' => 'inherit',
            'body_font' => 'inherit',
            'heading_size' => 'medium',
            'body_size' => 'medium',
            'line_height' => 'normal',
            'letter_spacing' => 'normal'
        ],
        'layout_spacing' => [
            'card_padding' => 'medium',
            'card_margin' => 'medium',
            'section_spacing' => 'medium',
            'border_radius' => 'medium'
        ],
        'card_styling' => [
            'shadow_intensity' => 'medium',
            'border_width' => 1,
            'hover_effect' => 'lift',
            'transition_speed' => 'medium'
        ],
        'enable_custom_css' => false,
        'custom_css' => '',
        'enable_dark_mode' => false,
        'enable_high_contrast' => false,
        'enable_reduced_motion' => false,
        'load_google_fonts' => true,
        'css_optimization' => true,
        'inline_critical_css' => false,
        'enable_css_variables' => true
    ];
    
    private array $color_schemes = [
        'professional' => [
            'name' => 'Professional Blue',
            'description' => 'Clean, professional blue theme suitable for most funeral homes',
            'colors' => [
                'primary' => '#2c5282',
                'secondary' => '#d4af37',
                'accent' => '#667eea',
                'background' => '#ffffff',
                'card_background' => '#ffffff',
                'text_primary' => '#2d3748',
                'text_secondary' => '#718096',
                'text_muted' => '#a0aec0',
                'border' => '#e2e8f0',
                'shadow' => 'rgba(0, 0, 0, 0.1)',
                'row_alternate' => '#f8fafc'
            ]
        ],
        'elegant' => [
            'name' => 'Elegant Navy',
            'description' => 'Sophisticated navy theme with gold accents for upscale presentation',
            'colors' => [
                'primary' => '#1a365d',
                'secondary' => '#b7791f',
                'accent' => '#4a5568',
                'background' => '#f7fafc',
                'card_background' => '#ffffff',
                'text_primary' => '#2d3748',
                'text_secondary' => '#4a5568',
                'text_muted' => '#718096',
                'border' => '#cbd5e0',
                'shadow' => 'rgba(0, 0, 0, 0.12)',
                'row_alternate' => '#f1f5f9'
            ]
        ],
        'warm' => [
            'name' => 'Warm Earth',
            'description' => 'Comforting warm tones with earth colors for a welcoming feel',
            'colors' => [
                'primary' => '#8b4513',
                'secondary' => '#d69e2e',
                'accent' => '#c05621',
                'background' => '#fffaf0',
                'card_background' => '#ffffff',
                'text_primary' => '#2d3748',
                'text_secondary' => '#744210',
                'text_muted' => '#a0aec0',
                'border' => '#fbd38d',
                'shadow' => 'rgba(139, 69, 19, 0.1)',
                'row_alternate' => '#fef5e7'
            ]
        ],
        'serene' => [
            'name' => 'Serene Green',
            'description' => 'Peaceful green theme promoting tranquility and remembrance',
            'colors' => [
                'primary' => '#22543d',
                'secondary' => '#38a169',
                'accent' => '#68d391',
                'background' => '#f0fff4',
                'card_background' => '#ffffff',
                'text_primary' => '#2d3748',
                'text_secondary' => '#276749',
                'text_muted' => '#718096',
                'border' => '#c6f6d5',
                'shadow' => 'rgba(34, 84, 61, 0.1)',
                'row_alternate' => '#f0fff4'
            ]
        ],
        'classic' => [
            'name' => 'Classic Black',
            'description' => 'Traditional black and white theme for formal, timeless presentation',
            'colors' => [
                'primary' => '#1a202c',
                'secondary' => '#4a5568',
                'accent' => '#718096',
                'background' => '#ffffff',
                'card_background' => '#f9f9f9',
                'text_primary' => '#2d3748',
                'text_secondary' => '#4a5568',
                'text_muted' => '#a0aec0',
                'border' => '#e2e8f0',
                'shadow' => 'rgba(0, 0, 0, 0.15)',
                'row_alternate' => '#f7fafc'
            ]
        ]
    ];
    
    private array $google_fonts = [
        'inherit' => 'Default Theme Font',
        'Open Sans' => 'Open Sans',
        'Roboto' => 'Roboto',
        'Lato' => 'Lato',
        'Montserrat' => 'Montserrat',
        'Source Sans Pro' => 'Source Sans Pro',
        'Raleway' => 'Raleway',
        'Poppins' => 'Poppins',
        'Playfair Display' => 'Playfair Display',
        'Merriweather' => 'Merriweather',
        'Crimson Text' => 'Crimson Text',
        'Libre Baskerville' => 'Libre Baskerville'
    ];
    
    private array $size_options = [
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large',
        'extra-large' => 'Extra Large'
    ];
    
    private array $spacing_options = [
        'none' => 'None',
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large',
        'extra-large' => 'Extra Large'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'styling',
            'Visual Styling',
            'Comprehensive visual customization and theming system',
            '2.0.0'
        );
    }
    
    /**
     * Initialize the module
     */
    public function init(): void {
        parent::init();
        
        // Admin styling always available
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styling_assets']);
    }
    
    /**
     * Initialize frontend functionality
     */
    protected function init_frontend(): void {
        // Enqueue styling assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styling_assets']);
        add_action('wp_head', [$this, 'output_custom_css']);
        
        // Body classes for styling
        add_filter('body_class', [$this, 'add_styling_body_classes']);
        
        // Generate CSS
        add_action('wfn_generate_css', [$this, 'generate_css_file']);
    }
    
    /**
     * Get module features
     */
    public function get_features(): array {
        return [
            '5 Professional Color Schemes',
            'Advanced Color Picker with Alpha Support',
            'Typography Controls (Font families, sizes, weights)',
            'Live CSS Generation & Preview',
            'Layout & Spacing Controls',
            'Custom CSS Support',
            'Web Font Integration (Google Fonts)',
            'Dark Mode Support',
            'High Contrast Mode',
            'CSS Optimization & Minification'
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
     * Enqueue styling assets
     */
    public function enqueue_styling_assets(): void {
        if (!$this->is_enabled()) {
            return;
        }
        
        // Only load styles on relevant pages
        if (!$this->should_load_styles()) {
            return;
        }
        
        $settings = $this->get_settings();
        
        // Enqueue Google Fonts if enabled
        if ($settings['load_google_fonts'] && $this->has_google_fonts()) {
            wp_enqueue_style(
                'wfn-google-fonts',
                $this->get_google_fonts_url(),
                [],
                $this->get_version()
            );
        }
        
        // Enqueue generated CSS
        if ($settings['css_optimization'] && file_exists($this->get_css_file_path())) {
            wp_enqueue_style(
                'wfn-custom-styling',
                $this->get_css_file_url(),
                [],
                filemtime($this->get_css_file_path())
            );
        }
        
        // Note: Removed styling-admin.js from frontend as it requires wpColorPicker which is admin-only
        // The custom CSS file handles all frontend styling needs
    }
    
    /**
     * Enqueue admin styling assets
     */
    public function enqueue_admin_styling_assets(): void {
        $screen = get_current_screen();
        
        // Debug logging removed for production
        
        // Check for both possible screen ID patterns
        $is_styling_page = $screen && (
            strpos($screen->id, 'wfn-module-styling') !== false ||
            strpos($screen->id, 'hkfn-module-styling') !== false ||
            $screen->id === 'hkfn-module-styling' ||
            $screen->id === 'wfn-module-styling'
        );
        
        if ($is_styling_page) {
            // Enqueue WordPress color picker
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // Add custom styling for better color picker appearance
            wp_add_inline_style('wp-color-picker', '
                .wp-picker-container .wp-color-result {
                    height: 32px;
                    margin-right: 10px;
                }
                .wfn-color-field {
                    margin-bottom: 20px;
                }
                .wfn-color-field .wp-picker-container {
                    margin-top: 5px;
                }
                .wfn-color-field label {
                    font-weight: 600;
                    margin-bottom: 8px;
                    display: block;
                }
            ');
            
            wp_enqueue_script(
                'wfn-admin-styling',
                WFN_PLUGIN_URL . 'assets/js/admin/styling-module.js',
                ['jquery', 'wp-color-picker'],
                $this->get_version(),
                true
            );
            
            // Add debug script to check if everything loads properly
            wp_add_inline_script('wfn-admin-styling', '
                console.log("WFN Styling: Admin JS loaded");
                console.log("WFN Styling: jQuery version:", jQuery.fn.jquery);
                console.log("WFN Styling: wp-color-picker available:", typeof jQuery.fn.wpColorPicker);
            ');
        }
    }
    
    /**
     * Output custom CSS
     */
    public function output_custom_css(): void {
        if (!$this->is_enabled()) {
            return;
        }
        
        $settings = $this->get_settings();
        
        // Generate CSS variables
        echo '<style id="wfn-styling-variables">';
        echo $this->generate_css_variables();
        echo '</style>';
        
        // Output inline CSS if not optimized
        if (!$settings['css_optimization']) {
            echo '<style id="wfn-custom-styling">';
            echo $this->generate_custom_css();
            echo '</style>';
        }
        
        // Output custom CSS
        if ($settings['enable_custom_css'] && !empty($settings['custom_css'])) {
            echo '<style id="wfn-user-custom-css">';
            echo wp_strip_all_tags($settings['custom_css']);
            echo '</style>';
        }
    }
    
    /**
     * Add styling body classes
     */
    public function add_styling_body_classes(array $classes): array {
        if (!$this->is_enabled()) {
            return $classes;
        }
        
        $settings = $this->get_settings();
        
        $classes[] = 'wfn-styling-enabled';
        $classes[] = 'wfn-scheme-' . $settings['color_scheme'];
        $classes[] = 'wfn-spacing-' . $settings['layout_spacing']['card_margin'];
        
        if ($settings['enable_dark_mode']) {
            $classes[] = 'wfn-dark-mode';
        }
        
        if ($settings['enable_high_contrast']) {
            $classes[] = 'wfn-high-contrast';
        }
        
        if ($settings['enable_reduced_motion']) {
            $classes[] = 'wfn-reduced-motion';
        }
        
        return $classes;
    }
    
    /**
     * Generate CSS variables
     */
    private function generate_css_variables(): string {
        $settings = $this->get_settings();
        $colors = $this->get_current_colors();
        
        $css = ':root {';
        
        // Color variables
        foreach ($colors as $key => $value) {
            $css .= '--wfn-color-' . str_replace('_', '-', $key) . ': ' . $value . ';';
        }
        
        // Typography variables
        $typography = $settings['typography'];
        if ($typography['heading_font'] !== 'inherit') {
            $css .= '--wfn-font-heading: "' . $typography['heading_font'] . '", sans-serif;';
        }
        if ($typography['body_font'] !== 'inherit') {
            $css .= '--wfn-font-body: "' . $typography['body_font'] . '", sans-serif;';
        }
        
        // Size variables
        $size_map = [
            'small' => '0.875rem',
            'medium' => '1rem',
            'large' => '1.125rem',
            'extra-large' => '1.25rem'
        ];
        
        $css .= '--wfn-text-size: ' . ($size_map[$typography['body_size']] ?? '1rem') . ';';
        $css .= '--wfn-heading-size: ' . ($size_map[$typography['heading_size']] ?? '1.25rem') . ';';
        
        // Spacing variables
        $spacing_map = [
            'none' => '0',
            'small' => '0.5rem',
            'medium' => '1rem',
            'large' => '1.5rem',
            'extra-large' => '2rem'
        ];
        
        $spacing = $settings['layout_spacing'];
        $css .= '--wfn-card-padding: ' . ($spacing_map[$spacing['card_padding']] ?? '1rem') . ';';
        $css .= '--wfn-card-margin: ' . ($spacing_map[$spacing['card_margin']] ?? '1rem') . ';';
        $css .= '--wfn-section-spacing: ' . ($spacing_map[$spacing['section_spacing']] ?? '1rem') . ';';
        
        // Border radius
        $radius_map = [
            'none' => '0',
            'small' => '0.25rem',
            'medium' => '0.5rem',
            'large' => '1rem',
            'extra-large' => '1.5rem'
        ];
        
        $css .= '--wfn-border-radius: ' . ($radius_map[$spacing['border_radius']] ?? '0.5rem') . ';';
        
        // Card styling
        $card = $settings['card_styling'];
        $shadow_map = [
            'none' => 'none',
            'small' => '0 1px 3px rgba(0, 0, 0, 0.1)',
            'medium' => '0 4px 6px rgba(0, 0, 0, 0.1)',
            'large' => '0 10px 15px rgba(0, 0, 0, 0.1)',
            'extra-large' => '0 20px 25px rgba(0, 0, 0, 0.15)'
        ];
        
        $css .= '--wfn-card-shadow: ' . ($shadow_map[$card['shadow_intensity']] ?? $shadow_map['medium']) . ';';
        $css .= '--wfn-border-width: ' . $card['border_width'] . 'px;';
        
        // Transition speed
        $transition_map = [
            'none' => '0s',
            'fast' => '0.15s',
            'medium' => '0.3s',
            'slow' => '0.5s'
        ];
        
        $css .= '--wfn-transition-speed: ' . ($transition_map[$card['transition_speed']] ?? '0.3s') . ';';
        
        $css .= '}';
        
        return $css;
    }
    
    /**
     * Generate custom CSS
     */
    private function generate_custom_css(): string {
        $settings = $this->get_settings();
        
        $css = '';
        
        // Base funeral notices styling
        $css .= '.wfn-funeral-notices {';
        $css .= 'color: var(--wfn-color-text-primary);';
        $css .= 'font-family: var(--wfn-font-body, inherit);';
        $css .= 'font-size: var(--wfn-text-size);';
        $css .= '}';
        
        // Card styling
        $css .= '.wfn-funeral-card {';
        $css .= 'background: var(--wfn-color-card-background);';
        $css .= 'border: var(--wfn-border-width) solid var(--wfn-color-border);';
        $css .= 'border-radius: var(--wfn-border-radius);';
        $css .= 'box-shadow: var(--wfn-card-shadow);';
        $css .= 'padding: var(--wfn-card-padding);';
        $css .= 'margin: var(--wfn-card-margin);';
        $css .= 'transition: all var(--wfn-transition-speed) ease;';
        $css .= '}';
        
        // Hover effects
        if ($settings['card_styling']['hover_effect'] === 'lift') {
            $css .= '.wfn-funeral-card:hover {';
            $css .= 'transform: translateY(-2px);';
            $css .= 'box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);';
            $css .= '}';
        } elseif ($settings['card_styling']['hover_effect'] === 'glow') {
            $css .= '.wfn-funeral-card:hover {';
            $css .= 'box-shadow: 0 0 20px var(--wfn-color-accent);';
            $css .= '}';
        }
        
        // Heading styling
        $css .= '.wfn-funeral-card h3, .wfn-funeral-card h4 {';
        $css .= 'color: var(--wfn-color-text-primary);';
        $css .= 'font-family: var(--wfn-font-heading, inherit);';
        $css .= 'font-size: var(--wfn-heading-size);';
        $css .= 'margin-bottom: 0.5rem;';
        $css .= '}';
        
        // Link styling
        $css .= '.wfn-funeral-card a {';
        $css .= 'color: var(--wfn-color-primary);';
        $css .= 'text-decoration: none;';
        $css .= 'transition: color var(--wfn-transition-speed) ease;';
        $css .= '}';
        
        $css .= '.wfn-funeral-card a:hover {';
        $css .= 'color: var(--wfn-color-accent);';
        $css .= '}';
        
        // Search form styling
        $css .= '.wfn-search-form {';
        $css .= 'background: var(--wfn-color-background);';
        $css .= 'border: var(--wfn-border-width) solid var(--wfn-color-border);';
        $css .= 'border-radius: var(--wfn-border-radius);';
        $css .= 'padding: var(--wfn-card-padding);';
        $css .= 'margin-bottom: var(--wfn-section-spacing);';
        $css .= '}';
        
        // Alternating row styling (for list/current layout)
        $css .= '.fn_notices li:nth-child(odd), .wfn-funeral-notices .wfn-funeral-row:nth-child(odd) {';
        $css .= 'background: var(--wfn-color-row-alternate);';
        $css .= '}';
        
        // Enhancement Suite shortcode styling - Modern Grid
        $css .= '.wfn-enhancement-modern-grid {';
        $css .= 'color: var(--wfn-color-text-primary);';
        $css .= 'font-family: var(--wfn-font-body, inherit);';
        $css .= '}';
        
        $css .= '.wfn-enhancement-modern-card {';
        $css .= 'background: var(--wfn-color-card-background) !important;';
        $css .= 'border: var(--wfn-border-width) solid var(--wfn-color-border) !important;';
        $css .= 'border-radius: var(--wfn-border-radius) !important;';
        $css .= 'box-shadow: var(--wfn-card-shadow) !important;';
        $css .= '/* padding and margin removed to allow layout CSS to control spacing */';
        $css .= 'transition: all var(--wfn-transition-speed) ease !important;';
        $css .= '}';
        
        $css .= '.wfn-enhancement-modern-link {';
        $css .= 'color: var(--wfn-color-text-primary) !important;';
        $css .= 'text-decoration: none !important;';
        $css .= '}';
        
        $css .= '.wfn-enhancement-modern-title {';
        $css .= 'color: var(--wfn-color-text-primary) !important;';
        $css .= 'font-family: var(--wfn-font-heading, inherit) !important;';
        $css .= 'font-size: var(--wfn-heading-size) !important;';
        $css .= '}';
        
        $css .= '.wfn-enhancement-modern-dates {';
        $css .= 'color: var(--wfn-color-text-secondary) !important;';
        $css .= '}';
        
        // Service info color removed - let modern-grid.css control it with primary color
        // .wfn-enhancement-modern-service uses var(--wfn-color-primary) in modern-grid.css
        
        $css .= '.wfn-enhancement-modern-more {';
        $css .= 'color: white !important;';
        $css .= 'background: var(--wfn-color-primary) !important;';
        $css .= '}';
        
        // Enhancement Suite shortcode styling - Elegant Grid
        $css .= '.wfn-enhancement-elegant-grid {';
        $css .= 'color: var(--wfn-color-text-primary);';
        $css .= 'font-family: var(--wfn-font-body, inherit);';
        $css .= '}';
        
        $css .= '.wfn-enhancement-elegant-card {';
        $css .= 'background: var(--wfn-color-card-background) !important;';
        $css .= 'border: var(--wfn-border-width) solid var(--wfn-color-border) !important;';
        $css .= 'border-radius: var(--wfn-border-radius) !important;';
        $css .= 'box-shadow: var(--wfn-card-shadow) !important;';
        $css .= '/* padding and margin removed to allow layout CSS to control spacing */';
        $css .= 'transition: all var(--wfn-transition-speed) ease !important;';
        $css .= '}';
        
        $css .= '.wfn-enhancement-elegant-link {';
        $css .= 'color: var(--wfn-color-text-primary) !important;';
        $css .= 'text-decoration: none !important;';
        $css .= '}';
        
        $css .= '.wfn-enhancement-elegant-name {';
        $css .= 'color: var(--wfn-color-text-primary) !important;';
        $css .= 'font-family: var(--wfn-font-heading, inherit) !important;';
        $css .= 'font-size: var(--wfn-heading-size) !important;';
        $css .= '}';
        
        $css .= '.wfn-enhancement-elegant-years {';
        $css .= 'color: var(--wfn-color-text-secondary) !important;';
        $css .= '}';
        
        $css .= '.wfn-enhancement-elegant-service {';
        $css .= 'color: var(--wfn-color-text-secondary) !important;';
        $css .= '}';
        
        
        // Enhancement Suite shortcode styling - Minimal Grid
        $css .= '.wfn-enhancement-minimal-grid {';
        $css .= 'color: var(--wfn-color-text-primary);';
        $css .= 'font-family: var(--wfn-font-body, inherit);';
        $css .= '}';
        
        $css .= '.wfn-enhancement-minimal-card {';
        $css .= 'background: var(--wfn-color-card-background) !important;';
        $css .= 'border: var(--wfn-border-width) solid var(--wfn-color-border) !important;';
        $css .= 'border-radius: var(--wfn-border-radius) !important;';
        $css .= 'box-shadow: var(--wfn-card-shadow) !important;';
        $css .= 'padding: var(--wfn-card-padding) !important;';
        $css .= 'margin: var(--wfn-card-margin) !important;';
        $css .= 'transition: all var(--wfn-transition-speed) ease !important;';
        $css .= '}';
        
        $css .= '.wfn-enhancement-minimal-name {';
        $css .= 'color: var(--wfn-color-text-primary) !important;';
        $css .= 'font-family: var(--wfn-font-heading, inherit) !important;';
        $css .= 'font-size: var(--wfn-heading-size) !important;';
        $css .= '}';
        
        $css .= '.wfn-enhancement-minimal-years {';
        $css .= 'color: var(--wfn-color-text-secondary) !important;';
        $css .= '}';
        
        // Search form button styling
        $css .= '.wfn-enhancement-search-form .search-btn {';
        $css .= 'background: var(--wfn-color-primary) !important;';
        $css .= 'border-color: var(--wfn-color-primary) !important;';
        $css .= 'color: white !important;';
        $css .= '}';
        
        $css .= '.wfn-enhancement-search-form .search-btn:hover {';
        $css .= 'background: var(--wfn-color-secondary) !important;';
        $css .= 'border-color: var(--wfn-color-secondary) !important;';
        $css .= '}';
        
        // Hover effects for shortcode cards
        if ($settings['card_styling']['hover_effect'] === 'lift') {
            $css .= '.wfn-enhancement-modern-card:hover, .wfn-enhancement-elegant-card:hover, .wfn-enhancement-minimal-card:hover {';
            $css .= 'transform: translateY(-2px) !important;';
            $css .= 'box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;';
            $css .= '}';
        } elseif ($settings['card_styling']['hover_effect'] === 'glow') {
            $css .= '.wfn-enhancement-modern-card:hover, .wfn-enhancement-elegant-card:hover, .wfn-enhancement-minimal-card:hover {';
            $css .= 'box-shadow: 0 0 20px var(--wfn-color-accent) !important;';
            $css .= '}';
        }
        
        // Dark mode adjustments
        if ($settings['enable_dark_mode']) {
            $css .= '@media (prefers-color-scheme: dark) {';
            $css .= '.wfn-dark-mode {';
            $css .= '--wfn-color-background: #1a202c;';
            $css .= '--wfn-color-card-background: #2d3748;';
            $css .= '--wfn-color-text-primary: #f7fafc;';
            $css .= '--wfn-color-text-secondary: #e2e8f0;';
            $css .= '--wfn-color-border: #4a5568;';
            $css .= '--wfn-color-row-alternate: #2d3748;';
            $css .= '}';
            $css .= '}';
        }
        
        // High contrast mode
        if ($settings['enable_high_contrast']) {
            $css .= '.wfn-high-contrast {';
            $css .= '--wfn-color-text-primary: #000000;';
            $css .= '--wfn-color-background: #ffffff;';
            $css .= '--wfn-color-border: #000000;';
            $css .= '--wfn-border-width: 2px;';
            $css .= '}';
        }
        
        // Reduced motion
        if ($settings['enable_reduced_motion']) {
            $css .= '.wfn-reduced-motion * {';
            $css .= 'animation-duration: 0.01ms !important;';
            $css .= 'animation-iteration-count: 1 !important;';
            $css .= 'transition-duration: 0.01ms !important;';
            $css .= '}';
        }
        
        return $css;
    }
    
    /**
     * Get current colors (always custom now)
     */
    private function get_current_colors(): array {
        $settings = $this->get_settings();
        return $settings['custom_colors'];
    }
    
    /**
     * Check if Google Fonts are needed
     */
    private function has_google_fonts(): bool {
        $settings = $this->get_settings();
        $typography = $settings['typography'];
        
        return ($typography['heading_font'] !== 'inherit' && array_key_exists($typography['heading_font'], $this->google_fonts)) ||
               ($typography['body_font'] !== 'inherit' && array_key_exists($typography['body_font'], $this->google_fonts));
    }
    
    /**
     * Get Google Fonts URL
     */
    private function get_google_fonts_url(): string {
        $settings = $this->get_settings();
        $typography = $settings['typography'];
        
        $fonts = [];
        
        if ($typography['heading_font'] !== 'inherit' && array_key_exists($typography['heading_font'], $this->google_fonts)) {
            $fonts[] = str_replace(' ', '+', $typography['heading_font']) . ':400,500,600,700';
        }
        
        if ($typography['body_font'] !== 'inherit' && array_key_exists($typography['body_font'], $this->google_fonts)) {
            $fonts[] = str_replace(' ', '+', $typography['body_font']) . ':400,500,600';
        }
        
        if (empty($fonts)) {
            return '';
        }
        
        return 'https://fonts.googleapis.com/css2?family=' . implode('&family=', array_unique($fonts)) . '&display=swap';
    }
    
    /**
     * Get CSS file path
     */
    private function get_css_file_path(): string {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/wfn-styling.css';
    }
    
    /**
     * Get CSS file URL with correct protocol
     */
    private function get_css_file_url(): string {
        $upload_dir = wp_upload_dir();
        $url = $upload_dir['baseurl'] . '/wfn-styling.css';
        
        // Ensure URL uses the same protocol as the site
        if (is_ssl()) {
            $url = str_replace('http://', 'https://', $url);
        }
        
        return $url;
    }
    
    /**
     * Generate CSS file
     */
    public function generate_css_file(): void {
        $css = $this->generate_css_variables() . "\n" . $this->generate_custom_css();
        
        $file_path = $this->get_css_file_path();
        file_put_contents($file_path, $css);
    }
    
    /**
     * Render module admin content
     */
    protected function render_module_admin_content(): void {
        $settings = $this->get_settings();
        ?>
        <form method="post" action="">
            <?php $this->render_nonce_field(); ?>
            
            <div class="wfn-styling-admin">
                <div class="wfn-admin-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#colors" class="nav-tab nav-tab-active">Color Schemes</a>
                        <a href="#typography" class="nav-tab">Typography</a>
                        <a href="#layout" class="nav-tab">Layout & Spacing</a>
                        <a href="#advanced" class="nav-tab">Advanced</a>
                    </nav>
                    
                    <!-- Color Schemes Tab -->
                    <div id="colors" class="tab-content active">
                        <h3>Custom Colors</h3>
                        <p class="wfn-form-description">Customize the colors to match your website's theme. Leave fields at default to inherit from your theme.</p>
                        
                        <!-- Always show custom colors since that's the only option now -->
                        <div class="wfn-custom-colors">
                            <input type="hidden" name="wfn_module_settings[color_scheme]" value="custom">
                            <h4>Color Settings</h4>
                            <div class="wfn-color-grid">
                                <?php foreach ($settings['custom_colors'] as $key => $value): ?>
                                    <div class="wfn-color-field">
                                        <label for="color_<?php echo esc_attr($key); ?>">
                                            <?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>
                                        </label>
                                        <input type="text" 
                                               id="color_<?php echo esc_attr($key); ?>" 
                                               name="wfn_module_settings[custom_colors][<?php echo esc_attr($key); ?>]" 
                                               value="<?php echo esc_attr($value); ?>" 
                                               class="wp-color-picker-field"
                                               data-default-color="<?php echo esc_attr($this->default_settings['custom_colors'][$key] ?? $value); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Typography Tab -->
                    <div id="typography" class="tab-content">
                        <h3>Typography Settings</h3>
                        
                        <div class="wfn-typography-grid">
                            <div class="wfn-form-group">
                                <label for="heading_font">Heading Font</label>
                                <select name="wfn_module_settings[typography][heading_font]" id="heading_font">
                                    <?php foreach ($this->google_fonts as $font_value => $font_name): ?>
                                        <option value="<?php echo esc_attr($font_value); ?>" 
                                                <?php selected($settings['typography']['heading_font'], $font_value); ?>>
                                            <?php echo esc_html($font_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label for="body_font">Body Font</label>
                                <select name="wfn_module_settings[typography][body_font]" id="body_font">
                                    <?php foreach ($this->google_fonts as $font_value => $font_name): ?>
                                        <option value="<?php echo esc_attr($font_value); ?>" 
                                                <?php selected($settings['typography']['body_font'], $font_value); ?>>
                                            <?php echo esc_html($font_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label for="heading_size">Heading Size</label>
                                <select name="wfn_module_settings[typography][heading_size]" id="heading_size">
                                    <?php foreach ($this->size_options as $size_value => $size_name): ?>
                                        <option value="<?php echo esc_attr($size_value); ?>" 
                                                <?php selected($settings['typography']['heading_size'], $size_value); ?>>
                                            <?php echo esc_html($size_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label for="body_size">Body Size</label>
                                <select name="wfn_module_settings[typography][body_size]" id="body_size">
                                    <?php foreach ($this->size_options as $size_value => $size_name): ?>
                                        <option value="<?php echo esc_attr($size_value); ?>" 
                                                <?php selected($settings['typography']['body_size'], $size_value); ?>>
                                            <?php echo esc_html($size_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label class="wfn-toggle-switch">
                                <input type="checkbox"
                                       name="wfn_module_settings[load_google_fonts]"
                                       id="load_google_fonts"
                                       value="1"
                                       <?php checked($settings['load_google_fonts']); ?>>
                                <span class="wfn-toggle-slider"></span>
                                <span class="wfn-toggle-label">Load Google Fonts</span>
                            </label>
                            <p class="wfn-form-description">Automatically load Google Fonts for selected font families.</p>
                        </div>
                    </div>
                    
                    <!-- Layout & Spacing Tab -->
                    <div id="layout" class="tab-content">
                        <h3>Layout & Spacing</h3>
                        
                        <div class="wfn-spacing-grid">
                            <div class="wfn-form-group">
                                <label for="card_padding">Card Padding</label>
                                <select name="wfn_module_settings[layout_spacing][card_padding]" id="card_padding">
                                    <?php foreach ($this->spacing_options as $spacing_value => $spacing_name): ?>
                                        <option value="<?php echo esc_attr($spacing_value); ?>" 
                                                <?php selected($settings['layout_spacing']['card_padding'], $spacing_value); ?>>
                                            <?php echo esc_html($spacing_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label for="card_margin">Card Margin</label>
                                <select name="wfn_module_settings[layout_spacing][card_margin]" id="card_margin">
                                    <?php foreach ($this->spacing_options as $spacing_value => $spacing_name): ?>
                                        <option value="<?php echo esc_attr($spacing_value); ?>" 
                                                <?php selected($settings['layout_spacing']['card_margin'], $spacing_value); ?>>
                                            <?php echo esc_html($spacing_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label for="section_spacing">Section Spacing</label>
                                <select name="wfn_module_settings[layout_spacing][section_spacing]" id="section_spacing">
                                    <?php foreach ($this->spacing_options as $spacing_value => $spacing_name): ?>
                                        <option value="<?php echo esc_attr($spacing_value); ?>" 
                                                <?php selected($settings['layout_spacing']['section_spacing'], $spacing_value); ?>>
                                            <?php echo esc_html($spacing_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label for="border_radius">Border Radius</label>
                                <select name="wfn_module_settings[layout_spacing][border_radius]" id="border_radius">
                                    <?php foreach ($this->spacing_options as $spacing_value => $spacing_name): ?>
                                        <option value="<?php echo esc_attr($spacing_value); ?>" 
                                                <?php selected($settings['layout_spacing']['border_radius'], $spacing_value); ?>>
                                            <?php echo esc_html($spacing_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <h4>Card Styling</h4>
                        <div class="wfn-card-styling-grid">
                            <div class="wfn-form-group">
                                <label for="shadow_intensity">Shadow Intensity</label>
                                <select name="wfn_module_settings[card_styling][shadow_intensity]" id="shadow_intensity">
                                    <option value="none" <?php selected($settings['card_styling']['shadow_intensity'], 'none'); ?>>None</option>
                                    <option value="small" <?php selected($settings['card_styling']['shadow_intensity'], 'small'); ?>>Small</option>
                                    <option value="medium" <?php selected($settings['card_styling']['shadow_intensity'], 'medium'); ?>>Medium</option>
                                    <option value="large" <?php selected($settings['card_styling']['shadow_intensity'], 'large'); ?>>Large</option>
                                    <option value="extra-large" <?php selected($settings['card_styling']['shadow_intensity'], 'extra-large'); ?>>Extra Large</option>
                                </select>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label for="border_width">Border Width (px)</label>
                                <input type="number" 
                                       name="wfn_module_settings[card_styling][border_width]" 
                                       id="border_width" 
                                       value="<?php echo esc_attr($settings['card_styling']['border_width']); ?>" 
                                       min="0" 
                                       max="5">
                            </div>
                            
                            <div class="wfn-form-group">
                                <label for="hover_effect">Hover Effect</label>
                                <select name="wfn_module_settings[card_styling][hover_effect]" id="hover_effect">
                                    <option value="none" <?php selected($settings['card_styling']['hover_effect'], 'none'); ?>>None</option>
                                    <option value="lift" <?php selected($settings['card_styling']['hover_effect'], 'lift'); ?>>Lift</option>
                                    <option value="glow" <?php selected($settings['card_styling']['hover_effect'], 'glow'); ?>>Glow</option>
                                    <option value="scale" <?php selected($settings['card_styling']['hover_effect'], 'scale'); ?>>Scale</option>
                                </select>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label for="transition_speed">Transition Speed</label>
                                <select name="wfn_module_settings[card_styling][transition_speed]" id="transition_speed">
                                    <option value="none" <?php selected($settings['card_styling']['transition_speed'], 'none'); ?>>None</option>
                                    <option value="fast" <?php selected($settings['card_styling']['transition_speed'], 'fast'); ?>>Fast</option>
                                    <option value="medium" <?php selected($settings['card_styling']['transition_speed'], 'medium'); ?>>Medium</option>
                                    <option value="slow" <?php selected($settings['card_styling']['transition_speed'], 'slow'); ?>>Slow</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Tab -->
                    <div id="advanced" class="tab-content">
                        <h3>Advanced Settings</h3>
                        
                        <div class="wfn-accessibility-settings">
                            <h4>Accessibility</h4>
                            
                            <div class="wfn-form-group">
                                <label class="wfn-toggle-switch">
                                    <input type="checkbox"
                                           name="wfn_module_settings[enable_dark_mode]"
                                           id="enable_dark_mode"
                                           value="1"
                                           <?php checked($settings['enable_dark_mode']); ?>>
                                    <span class="wfn-toggle-slider"></span>
                                    <span class="wfn-toggle-label">Enable Dark Mode Support</span>
                                </label>
                                <p class="wfn-form-description">Automatically adjust colors for users with dark mode preference.</p>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label class="wfn-toggle-switch">
                                    <input type="checkbox"
                                           name="wfn_module_settings[enable_high_contrast]"
                                           id="enable_high_contrast"
                                           value="1"
                                           <?php checked($settings['enable_high_contrast']); ?>>
                                    <span class="wfn-toggle-slider"></span>
                                    <span class="wfn-toggle-label">Enable High Contrast Mode</span>
                                </label>
                                <p class="wfn-form-description">Provide high contrast styling for accessibility.</p>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label class="wfn-toggle-switch">
                                    <input type="checkbox"
                                           name="wfn_module_settings[enable_reduced_motion]"
                                           id="enable_reduced_motion"
                                           value="1"
                                           <?php checked($settings['enable_reduced_motion']); ?>>
                                    <span class="wfn-toggle-slider"></span>
                                    <span class="wfn-toggle-label">Respect Reduced Motion Preference</span>
                                </label>
                                <p class="wfn-form-description">Reduce animations for users with motion sensitivity.</p>
                            </div>
                        </div>
                        
                        <div class="wfn-performance-settings">
                            <h4>Performance</h4>
                            
                            <div class="wfn-form-group">
                                <label class="wfn-toggle-switch">
                                    <input type="checkbox"
                                           name="wfn_module_settings[css_optimization]"
                                           id="css_optimization"
                                           value="1"
                                           <?php checked($settings['css_optimization']); ?>>
                                    <span class="wfn-toggle-slider"></span>
                                    <span class="wfn-toggle-label">Enable CSS Optimization</span>
                                </label>
                                <p class="wfn-form-description">Generate optimized CSS file instead of inline styles.</p>
                            </div>
                            
                            <div class="wfn-form-group">
                                <label class="wfn-toggle-switch">
                                    <input type="checkbox"
                                           name="wfn_module_settings[enable_css_variables]"
                                           id="enable_css_variables"
                                           value="1"
                                           <?php checked($settings['enable_css_variables']); ?>>
                                    <span class="wfn-toggle-slider"></span>
                                    <span class="wfn-toggle-label">Use CSS Variables</span>
                                </label>
                                <p class="wfn-form-description">Use CSS custom properties for better performance and flexibility.</p>
                            </div>
                        </div>
                        
                        <div class="wfn-custom-css-settings">
                            <h4>Custom CSS</h4>
                            
                            <div class="wfn-form-group">
                                <label class="wfn-toggle-switch">
                                    <input type="checkbox"
                                           name="wfn_module_settings[enable_custom_css]"
                                           id="enable_custom_css"
                                           value="1"
                                           <?php checked($settings['enable_custom_css']); ?>>
                                    <span class="wfn-toggle-slider"></span>
                                    <span class="wfn-toggle-label">Enable Custom CSS</span>
                                </label>
                                <p class="wfn-form-description">Add custom CSS rules for advanced styling.</p>
                            </div>
                            
                            <?php if ($settings['enable_custom_css']): ?>
                            <div class="wfn-form-group">
                                <label for="custom_css">Custom CSS</label>
                                <textarea name="wfn_module_settings[custom_css]" 
                                          id="custom_css" 
                                          rows="10" 
                                          class="wfn-custom-css-editor"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                                <p class="wfn-form-description">Enter custom CSS rules. Use CSS variables like var(--wfn-color-primary) for consistency.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php $this->render_submit_button(); ?>
        </form>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                console.log('WFN Styling: Inline script started');
                
                // Backup color picker initialization
                setTimeout(function() {
                    console.log('WFN Styling: Attempting backup color picker init');
                    
                    if (typeof $.fn.wpColorPicker !== 'undefined') {
                        $('.wp-color-picker-field').each(function() {
                            var $input = $(this);
                            if (!$input.hasClass('wp-color-picker')) {
                                console.log('Initializing color picker for:', $input.attr('id'));
                                try {
                                    $input.wpColorPicker({
                                        defaultColor: $input.data('default-color') || $input.val(),
                                        hide: true,
                                        palettes: ['#2c5282', '#667eea', '#28a745', '#dc3545', '#ffc107', '#17a2b8']
                                    });
                                } catch (e) {
                                    console.error('Color picker init error:', e);
                                }
                            }
                        });
                    } else {
                        console.error('wpColorPicker not available in backup init');
                    }
                    
                    // Tab functionality
                    $('.nav-tab').on('click', function(e) {
                        e.preventDefault();
                        var targetTab = $(this).attr('href');
                        $('.nav-tab').removeClass('nav-tab-active');
                        $('.tab-content').removeClass('active');
                        $(this).addClass('nav-tab-active');
                        $(targetTab).addClass('active');
                    });
                    
                    // Custom colors toggle
                    $('input[name="wfn_module_settings[color_scheme]"]').on('change', function() {
                        var $customColors = $('.wfn-custom-colors');
                        if ($(this).val() === 'custom') {
                            $customColors.show();
                        } else {
                            $customColors.hide();
                        }
                    });
                }, 250);
            });
        </script>
        
        <style>
            /* Tab functionality CSS */
            .tab-content {
                display: none;
            }
            
            .tab-content.active {
                display: block;
            }
            
            .wfn-color-schemes {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            
            .wfn-color-scheme {
                border: 2px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .wfn-color-scheme:hover {
                border-color: #667eea;
                transform: translateY(-2px);
            }
            
            .wfn-color-scheme input[type="radio"] {
                display: none;
            }
            
            .wfn-color-scheme input[type="radio"]:checked + .wfn-scheme-preview {
                border-color: #667eea;
                background: #f0f4ff;
            }
            
            .wfn-scheme-preview {
                padding: 20px;
                border: 2px solid transparent;
                transition: all 0.3s ease;
            }
            
            .wfn-scheme-colors {
                display: flex;
                gap: 5px;
                margin-bottom: 15px;
            }
            
            .wfn-color-swatch {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                border: 2px solid #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .wfn-scheme-info h4 {
                margin: 0 0 5px 0;
                font-size: 16px;
            }
            
            .wfn-scheme-info p {
                margin: 0;
                color: #666;
                font-size: 13px;
            }
            
            .wfn-color-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            
            .wfn-color-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
            }
            
            .wfn-color-picker {
                width: 100%;
                height: 40px;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
            }
            
            .wfn-typography-grid,
            .wfn-spacing-grid,
            .wfn-card-styling-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            
            .wfn-custom-css-editor {
                width: 100%;
                font-family: monospace;
                font-size: 14px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 10px;
            }
            
            .wfn-accessibility-settings,
            .wfn-performance-settings,
            .wfn-custom-css-settings {
                margin-bottom: 30px;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 8px;
                background: #f9f9f9;
            }
            
            .wfn-accessibility-settings h4,
            .wfn-performance-settings h4,
            .wfn-custom-css-settings h4 {
                margin-top: 0;
                margin-bottom: 15px;
                color: #333;
            }
        </style>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Tab functionality
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
                
                // Color scheme toggle removed - always custom now
                
                // Custom CSS toggle
                const customCssCheckbox = document.getElementById('enable_custom_css');
                const customCssEditor = document.querySelector('.wfn-form-group:has(#custom_css)');
                
                if (customCssCheckbox && customCssEditor) {
                    customCssCheckbox.addEventListener('change', function() {
                        customCssEditor.style.display = this.checked ? 'block' : 'none';
                    });
                }
                
                // Initialize WordPress color pickers with alpha support
                if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
                    jQuery('.wfn-color-picker').each(function() {
                        var $input = jQuery(this);
                        var options = {
                            defaultColor: $input.data('default-color') || false,
                            change: function(event, ui) {
                                // Trigger change for live preview if implemented
                                $input.trigger('wfn-color-change', ui.color.toString());
                            },
                            clear: function() {
                                $input.trigger('wfn-color-clear');
                            },
                            hide: true,
                            palettes: [
                                '#2c5282', '#d4af37', '#667eea', // Primary colors
                                '#2d3748', '#718096', '#a0aec0', // Text colors
                                '#e2e8f0', '#f8fafc', '#ffffff', // Background colors
                                '#1a202c', '#4a5568', '#718096'  // Dark colors
                            ]
                        };
                        
                        // Add alpha support if enabled
                        if ($input.data('alpha-enabled')) {
                            options.alphaEnabled = true;
                            options.alphaCustomWidth = $input.data('alpha-custom-width') || 30;
                        }
                        
                        $input.wpColorPicker(options);
                    });
                }
            });
        </script>
        <?php
    }
    
    /**
     * Sanitize settings with specific validation
     */
    protected function sanitize_settings(array $settings): array {
        $sanitized = [];
        
        // Color scheme always custom now
        $sanitized['color_scheme'] = 'custom';
        
        // Custom colors validation
        $sanitized['custom_colors'] = [];
        if (isset($settings['custom_colors']) && is_array($settings['custom_colors'])) {
            foreach ($settings['custom_colors'] as $key => $value) {
                $sanitized['custom_colors'][$key] = sanitize_hex_color($value) ?: $this->default_settings['custom_colors'][$key] ?? '#000000';
            }
        }
        
        // Merge with defaults for missing colors
        $sanitized['custom_colors'] = array_merge($this->default_settings['custom_colors'], $sanitized['custom_colors']);
        
        // Typography validation
        $valid_fonts = array_keys($this->google_fonts);
        $valid_sizes = array_keys($this->size_options);
        
        $sanitized['typography'] = [
            'heading_font' => in_array($settings['typography']['heading_font'] ?? '', $valid_fonts) 
                ? $settings['typography']['heading_font'] 
                : 'inherit',
            'body_font' => in_array($settings['typography']['body_font'] ?? '', $valid_fonts) 
                ? $settings['typography']['body_font'] 
                : 'inherit',
            'heading_size' => in_array($settings['typography']['heading_size'] ?? '', $valid_sizes) 
                ? $settings['typography']['heading_size'] 
                : 'medium',
            'body_size' => in_array($settings['typography']['body_size'] ?? '', $valid_sizes) 
                ? $settings['typography']['body_size'] 
                : 'medium',
            'line_height' => 'normal',
            'letter_spacing' => 'normal'
        ];
        
        // Layout spacing validation
        $valid_spacing = array_keys($this->spacing_options);
        $sanitized['layout_spacing'] = [
            'card_padding' => in_array($settings['layout_spacing']['card_padding'] ?? '', $valid_spacing) 
                ? $settings['layout_spacing']['card_padding'] 
                : 'medium',
            'card_margin' => in_array($settings['layout_spacing']['card_margin'] ?? '', $valid_spacing) 
                ? $settings['layout_spacing']['card_margin'] 
                : 'medium',
            'section_spacing' => in_array($settings['layout_spacing']['section_spacing'] ?? '', $valid_spacing) 
                ? $settings['layout_spacing']['section_spacing'] 
                : 'medium',
            'border_radius' => in_array($settings['layout_spacing']['border_radius'] ?? '', $valid_spacing) 
                ? $settings['layout_spacing']['border_radius'] 
                : 'medium'
        ];
        
        // Card styling validation
        $valid_shadows = ['none', 'small', 'medium', 'large', 'extra-large'];
        $valid_hover = ['none', 'lift', 'glow', 'scale'];
        $valid_transitions = ['none', 'fast', 'medium', 'slow'];
        
        $sanitized['card_styling'] = [
            'shadow_intensity' => in_array($settings['card_styling']['shadow_intensity'] ?? '', $valid_shadows) 
                ? $settings['card_styling']['shadow_intensity'] 
                : 'medium',
            'border_width' => max(0, min(5, (int) ($settings['card_styling']['border_width'] ?? 1))),
            'hover_effect' => in_array($settings['card_styling']['hover_effect'] ?? '', $valid_hover) 
                ? $settings['card_styling']['hover_effect'] 
                : 'lift',
            'transition_speed' => in_array($settings['card_styling']['transition_speed'] ?? '', $valid_transitions) 
                ? $settings['card_styling']['transition_speed'] 
                : 'medium'
        ];
        
        // Boolean settings
        $boolean_settings = [
            'enable_custom_css', 'enable_dark_mode', 'enable_high_contrast', 'enable_reduced_motion',
            'load_google_fonts', 'css_optimization', 'inline_critical_css', 'enable_css_variables'
        ];
        
        foreach ($boolean_settings as $setting) {
            $sanitized[$setting] = !empty($settings[$setting]);
        }
        
        // Custom CSS
        $sanitized['custom_css'] = wp_strip_all_tags($settings['custom_css'] ?? '');
        
        return $sanitized;
    }
    
    /**
     * Handle form submission with CSS generation
     */
    public function handle_form_submission(): bool {
        $result = parent::handle_form_submission();
        
        if ($result) {
            // Generate CSS file after settings update
            $this->generate_css_file();
        }
        
        return $result;
    }
}