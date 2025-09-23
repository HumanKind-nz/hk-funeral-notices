<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Modules;

/**
 * Search Module
 * 
 * Enhanced search functionality with filtering capabilities.
 * Manages search forms, AJAX functionality, filtering options,
 * and search result customization.
 * 
 * @since 2.0.0
 */
class SearchModule extends BaseModule {
    
    protected array $default_settings = [
        // Core search functionality
        'enable_advanced_search' => true,
        'search_fields' => ['name', 'location', 'content'],
        'search_placeholder' => 'Search funeral notices...',
        'search_form_style' => 'modern',
        
        // Basic filters (commonly used)
        'enable_date_range' => true,
        'enable_location_filter' => true,
        'show_search_count' => true,
        
        // AJAX functionality (essential for modern UX)
        'enable_ajax_search' => true,
        'min_search_length' => 3,
        'search_delay' => 300,
        'results_per_page' => 12,
        
        // Advanced features (disabled by default to keep it simple)
        'enable_search_suggestions' => false,
        'enable_relevance_scoring' => false,
        'highlight_search_terms' => false,
        'enable_search_analytics' => false,
        'enable_fuzzy_search' => false,
        'enable_search_history' => false,
        
        // Internal settings
        'date_range_format' => 'Y-m-d',
        'search_operators' => ['AND', 'OR'],
        'max_search_history' => 10
    ];
    
    private array $search_form_styles = [
        'modern' => [
            'name' => 'Modern Search Form',
            'description' => 'Clean, contemporary search interface with Enhancement Suite styling',
            'css_class' => 'wfn-search-modern',
            'template' => 'search-modern.php'
        ],
        'classic' => [
            'name' => 'Classic Search Form',
            'description' => 'Traditional search form with standard WordPress styling',
            'css_class' => 'wfn-search-classic',
            'template' => 'search-classic.php'
        ],
        'minimal' => [
            'name' => 'Minimal Search',
            'description' => 'Simple, clean search interface with minimal styling',
            'css_class' => 'wfn-search-minimal',
            'template' => 'search-minimal.php'
        ],
        'compact' => [
            'name' => 'Compact Search',
            'description' => 'Space-efficient search form for sidebar or header placement',
            'css_class' => 'wfn-search-compact',
            'template' => 'search-compact.php'
        ]
    ];
    
    private array $searchable_fields = [
        'name' => [
            'label' => 'Name',
            'description' => 'Search in first name, last name, and maiden name fields',
            'meta_keys' => ['firstname', 'lastname', 'maidenname'],
            'weight' => 10
        ],
        'location' => [
            'label' => 'Location',
            'description' => 'Search in funeral locations and addresses',
            'meta_keys' => ['address', 'suburb', 'city'],
            'weight' => 8
        ],
        'content' => [
            'label' => 'Content',
            'description' => 'Search in funeral notice content and descriptions',
            'meta_keys' => ['post_content', 'post_excerpt'],
            'weight' => 6
        ],
        'date' => [
            'label' => 'Date',
            'description' => 'Search by funeral date and date ranges',
            'meta_keys' => ['funeral_date', 'death_date', 'birth_date'],
            'weight' => 5
        ],
        'service_type' => [
            'label' => 'Service Type',
            'description' => 'Search by type of service (funeral, memorial, etc.)',
            'meta_keys' => ['service_type', 'service_details'],
            'weight' => 4
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'search',
            'Advanced Search',
            'Enhanced search functionality with filtering capabilities',
            '2.0.0'
        );
    }
    
    /**
     * Initialize the module
     */
    public function init(): void {
        parent::init();
    }
    
    /**
     * Initialize frontend functionality
     */
    protected function init_frontend(): void {
        // Register search functionality
        add_action('wp_enqueue_scripts', [$this, 'enqueue_search_assets']);
        add_action('wp_ajax_wfn_search', [$this, 'handle_ajax_search']);
        add_action('wp_ajax_nopriv_wfn_search', [$this, 'handle_ajax_search']);
        
        // Search form filters
        add_filter('wfn_search_form_html', [$this, 'render_search_form']);
        add_filter('wfn_search_query_args', [$this, 'modify_search_query']);
        
        // Search result filters
        add_filter('wfn_search_results', [$this, 'process_search_results']);
        add_filter('wfn_search_highlight', [$this, 'highlight_search_terms']);
        
        // Analytics tracking
        if ($this->get_settings()['enable_search_analytics']) {
            add_action('wp_ajax_wfn_track_search', [$this, 'track_search_analytics']);
            add_action('wp_ajax_nopriv_wfn_track_search', [$this, 'track_search_analytics']);
        }
    }
    
    /**
     * Get module features
     */
    public function get_features(): array {
        return [
            'Advanced Search Interface with AJAX',
            'Date Range Filtering with Calendar Picker',
            'Real-time Search Results',
            'Search Suggestions & Autocomplete',
            'Location & Service Type Filtering',
            'Search Performance Optimization',
            'Mobile-Friendly Search Forms',
            'Search Analytics & Tracking',
            'Relevance Scoring & Highlighting',
            'Multiple Search Form Styles'
        ];
    }
    
    /**
     * Check if current page should load search functionality
     */
    private function should_load_styles(): bool {
        // DO NOT load on single funeral notice pages - they don't have search
        if (is_singular('funeral-notice')) {
            return false;
        }
        
        // Check if current page/post has the funeral_notices shortcode (which may include search)
        global $post;
        if ($post && has_shortcode($post->post_content, 'funeral_notices')) {
            return true;
        }
        
        // Check if it's the archive page for funeral notices (has search)
        if (is_post_type_archive('funeral-notice')) {
            return true;
        }
        
        // Check if it's a funeral location taxonomy page (has search)
        if (is_tax('funeral-location')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Enqueue search assets
     */
    public function enqueue_search_assets(): void {
        if (!$this->is_enabled()) {
            return;
        }
        
        // Only load styles on relevant pages
        if (!$this->should_load_styles()) {
            return;
        }
        
        $settings = $this->get_settings();
        
        // Enqueue search styles
        wp_enqueue_style(
            'wfn-search',
            WFN_PLUGIN_URL . 'assets/css/search.css',
            [],
            $this->get_version()
        );
        
        // Enqueue search form specific styles
        if (isset($this->search_form_styles[$settings['search_form_style']])) {
            wp_enqueue_style(
                'wfn-search-' . $settings['search_form_style'],
                WFN_PLUGIN_URL . 'assets/css/search-' . $settings['search_form_style'] . '.css',
                ['wfn-search'],
                $this->get_version()
            );
        }
        
        // Enqueue search scripts
        wp_enqueue_script(
            'wfn-search',
            WFN_PLUGIN_URL . 'assets/js/search.js',
            ['jquery'],
            $this->get_version(),
            true
        );
        
        // Localize search script
        wp_localize_script('wfn-search', 'wfnSearch', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wfn_search_nonce'),
            'settings' => [
                'enableAjax' => $settings['enable_ajax_search'],
                'minLength' => $settings['min_search_length'],
                'searchDelay' => $settings['search_delay'],
                'enableSuggestions' => $settings['enable_search_suggestions'],
                'highlightTerms' => $settings['highlight_search_terms'],
                'enableHistory' => $settings['enable_search_history'],
                'maxHistory' => $settings['max_search_history']
            ],
            'strings' => [
                'searching' => 'Searching...',
                'noResults' => 'No funeral notices found.',
                'resultsFound' => 'funeral notices found',
                'clearSearch' => 'Clear search',
                'searchHistory' => 'Recent searches'
            ]
        ]);
    }
    
    /**
     * Handle AJAX search requests
     */
    public function handle_ajax_search(): void {
        check_ajax_referer('wfn_search_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        $location = sanitize_text_field($_POST['location'] ?? '');
        $page = (int) ($_POST['page'] ?? 1);
        
        $settings = $this->get_settings();
        
        // Build search query
        $args = [
            'post_type' => 'funeral-notice',
            'post_status' => 'publish',
            'posts_per_page' => $settings['results_per_page'],
            'paged' => $page,
            'meta_query' => [],
            'date_query' => []
        ];
        
        // Add search term query
        if (!empty($search_term) && strlen($search_term) >= $settings['min_search_length']) {
            $args['s'] = $search_term;
            
            // Add meta query for custom fields
            if (in_array('name', $settings['search_fields'])) {
                $args['meta_query'][] = [
                    'relation' => 'OR',
                    [
                        'key' => 'firstname',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'lastname',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'maidenname',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    ]
                ];
            }
        }
        
        // Add date range query
        if (!empty($date_from) || !empty($date_to)) {
            $date_query = [];
            
            if (!empty($date_from)) {
                $date_query['after'] = $date_from;
            }
            
            if (!empty($date_to)) {
                $date_query['before'] = $date_to;
            }
            
            $args['meta_query'][] = [
                'key' => 'funeral_date',
                'value' => [$date_from, $date_to],
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            ];
        }
        
        // Add location filter
        if (!empty($location)) {
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key' => 'address',
                    'value' => $location,
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'suburb',
                    'value' => $location,
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'city',
                    'value' => $location,
                    'compare' => 'LIKE'
                ]
            ];
        }
        
        // Apply filters
        $args = apply_filters('wfn_search_query_args', $args, $search_term, $date_from, $date_to, $location);
        
        // Execute search
        $query = new \WP_Query($args);
        
        $results = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $results[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'permalink' => get_permalink(),
                    'date' => get_the_date(),
                    'image' => get_the_post_thumbnail_url($post_id, 'medium'),
                    'meta' => [
                        'funeral_date' => get_field('funeral_date', $post_id),
                        'location' => get_field('address', $post_id),
                        'streaming' => get_field('streaming_service', $post_id)
                    ]
                ];
            }
        }
        
        wp_reset_postdata();
        
        // Apply result filters
        $results = apply_filters('wfn_search_results', $results, $search_term);
        
        // Track search analytics
        if ($settings['enable_search_analytics']) {
            $this->track_search_query($search_term, count($results));
        }
        
        wp_send_json_success([
            'results' => $results,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
            'search_term' => $search_term,
            'results_text' => count($results) . ' funeral notices found'
        ]);
    }
    
    /**
     * Render search form
     */
    public function render_search_form(string $form_html = ''): string {
        $settings = $this->get_settings();
        $style = $this->search_form_styles[$settings['search_form_style']] ?? $this->search_form_styles['modern'];
        
        ob_start();
        ?>
        <div class="wfn-search-container <?php echo esc_attr($style['css_class']); ?>">
            <form class="wfn-search-form" method="get" action="">
                <div class="wfn-search-fields">
                    <div class="wfn-search-field wfn-search-text">
                        <label for="wfn-search-input" class="screen-reader-text">Search Funeral Notices</label>
                        <input type="text" 
                               id="wfn-search-input" 
                               name="search" 
                               class="wfn-search-input"
                               placeholder="<?php echo esc_attr($settings['search_placeholder']); ?>"
                               value="<?php echo esc_attr($_GET['search'] ?? ''); ?>">
                        <button type="submit" class="wfn-search-submit">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="M21 21l-4.35-4.35"></path>
                            </svg>
                            <span class="screen-reader-text">Search</span>
                        </button>
                    </div>
                    
                    <?php if ($settings['enable_date_range']): ?>
                    <div class="wfn-search-field wfn-search-dates">
                        <label for="wfn-date-from">From Date</label>
                        <input type="date" 
                               id="wfn-date-from" 
                               name="date_from" 
                               class="wfn-date-input"
                               value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>">
                        
                        <label for="wfn-date-to">To Date</label>
                        <input type="date" 
                               id="wfn-date-to" 
                               name="date_to" 
                               class="wfn-date-input"
                               value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['enable_location_filter']): ?>
                    <div class="wfn-search-field wfn-search-location">
                        <label for="wfn-location-input">Location</label>
                        <input type="text" 
                               id="wfn-location-input" 
                               name="location" 
                               class="wfn-location-input"
                               placeholder="Enter location..."
                               value="<?php echo esc_attr($_GET['location'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="wfn-search-actions">
                        <button type="submit" class="wfn-search-button">Search</button>
                        <button type="reset" class="wfn-clear-button">Clear</button>
                    </div>
                </div>
            </form>
            
            <?php if ($settings['enable_search_suggestions']): ?>
            <div class="wfn-search-suggestions" style="display: none;">
                <div class="wfn-suggestions-list"></div>
            </div>
            <?php endif; ?>
            
            <?php if ($settings['show_search_count']): ?>
            <div class="wfn-search-results-count" style="display: none;">
                <span class="wfn-results-text"></span>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Track search analytics
     */
    private function track_search_query(string $search_term, int $result_count): void {
        if (empty($search_term)) {
            return;
        }
        
        $analytics = get_option('wfn_search_analytics', []);
        $date = date('Y-m-d');
        
        if (!isset($analytics[$date])) {
            $analytics[$date] = [];
        }
        
        $analytics[$date][] = [
            'term' => $search_term,
            'results' => $result_count,
            'timestamp' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        // Keep only last 30 days of analytics
        $analytics = array_slice($analytics, -30, 30, true);
        
        update_option('wfn_search_analytics', $analytics);
    }
    
    /**
     * Render module admin content
     */
    protected function render_module_admin_content(): void {
        $settings = $this->get_settings();
        ?>
        <form method="post" action="">
            <?php $this->render_nonce_field(); ?>
            
            <div class="wfn-search-admin">
                <div class="wfn-admin-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#search-form" class="nav-tab nav-tab-active">Search Form</a>
                        <a href="#search-fields" class="nav-tab">Search Fields</a>
                        <a href="#ajax-settings" class="nav-tab">AJAX Settings</a>
                        <a href="#analytics" class="nav-tab">Analytics</a>
                    </nav>
                    
                    <!-- Search Form Tab -->
                    <div id="search-form" class="wfn-tab-pane active">
                        <h3>Search Form Configuration</h3>
                        
                        <div class="wfn-form-group">
                            <label class="wfn-toggle-switch">
                                <input type="checkbox"
                                       name="wfn_module_settings[enable_advanced_search]"
                                       id="enable_advanced_search"
                                       value="1"
                                       <?php checked($settings['enable_advanced_search']); ?>>
                                <span class="wfn-toggle-slider"></span>
                                <span class="wfn-toggle-label">Enable Advanced Search</span>
                            </label>
                            <p class="wfn-form-description">Enable advanced search features including filters and AJAX functionality.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label for="search_form_style">Search Form Style</label>
                            <select name="wfn_module_settings[search_form_style]" id="search_form_style">
                                <?php foreach ($this->search_form_styles as $style_id => $style): ?>
                                    <option value="<?php echo esc_attr($style_id); ?>" 
                                            <?php selected($settings['search_form_style'], $style_id); ?>>
                                        <?php echo esc_html($style['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="wfn-form-description">Visual style for search forms.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label for="search_placeholder">Search Placeholder Text</label>
                            <input type="text" 
                                   name="wfn_module_settings[search_placeholder]" 
                                   id="search_placeholder" 
                                   value="<?php echo esc_attr($settings['search_placeholder']); ?>">
                            <p class="wfn-form-description">Placeholder text displayed in search input field.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label class="wfn-toggle-switch">
                                <input type="checkbox"
                                       name="wfn_module_settings[enable_date_range]"
                                       id="enable_date_range"
                                       value="1"
                                       <?php checked($settings['enable_date_range']); ?>>
                                <span class="wfn-toggle-slider"></span>
                                <span class="wfn-toggle-label">Enable Date Range Filter</span>
                            </label>
                            <p class="wfn-form-description">Add date range filtering to search form.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label class="wfn-toggle-switch">
                                <input type="checkbox"
                                       name="wfn_module_settings[enable_location_filter]"
                                       id="enable_location_filter"
                                       value="1"
                                       <?php checked($settings['enable_location_filter']); ?>>
                                <span class="wfn-toggle-slider"></span>
                                <span class="wfn-toggle-label">Enable Location Filter</span>
                            </label>
                            <p class="wfn-form-description">Add location filtering to search form.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label class="wfn-toggle-switch">
                                <input type="checkbox"
                                       name="wfn_module_settings[show_search_count]"
                                       id="show_search_count"
                                       value="1"
                                       <?php checked($settings['show_search_count']); ?>>
                                <span class="wfn-toggle-slider"></span>
                                <span class="wfn-toggle-label">Show Search Results Count</span>
                            </label>
                            <p class="wfn-form-description">Display number of search results found.</p>
                        </div>
                    </div>
                    
                    <!-- Search Fields Tab -->
                    <div id="search-fields" class="wfn-tab-pane">
                        <h3>Searchable Fields</h3>
                        <p>Select which fields to include in search functionality.</p>
                        
                        <div class="wfn-search-fields-list">
                            <?php foreach ($this->searchable_fields as $field_id => $field): ?>
                                <div class="wfn-search-field-item">
                                    <label>
                                        <input type="checkbox" 
                                               name="wfn_module_settings[search_fields][]" 
                                               value="<?php echo esc_attr($field_id); ?>"
                                               <?php checked(in_array($field_id, $settings['search_fields'])); ?>>
                                        <strong><?php echo esc_html($field['label']); ?></strong>
                                    </label>
                                    <p class="wfn-field-description">
                                        <?php echo esc_html($field['description']); ?>
                                        <span class="wfn-field-weight">Weight: <?php echo esc_html($field['weight']); ?></span>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label class="wfn-toggle-switch">
                                <input type="checkbox"
                                       name="wfn_module_settings[enable_relevance_scoring]"
                                       id="enable_relevance_scoring"
                                       value="1"
                                       <?php checked($settings['enable_relevance_scoring']); ?>>
                                <span class="wfn-toggle-slider"></span>
                                <span class="wfn-toggle-label">Enable Relevance Scoring</span>
                            </label>
                            <p class="wfn-form-description">Order search results by relevance score.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label class="wfn-toggle-switch">
                                <input type="checkbox"
                                       name="wfn_module_settings[highlight_search_terms]"
                                       id="highlight_search_terms"
                                       value="1"
                                       <?php checked($settings['highlight_search_terms']); ?>>
                                <span class="wfn-toggle-slider"></span>
                                <span class="wfn-toggle-label">Highlight Search Terms</span>
                            </label>
                            <p class="wfn-form-description">Highlight matching search terms in results.</p>
                        </div>
                    </div>
                    
                    <!-- AJAX Settings Tab -->
                    <div id="ajax-settings" class="wfn-tab-pane">
                        <h3>AJAX Search Settings</h3>
                        <div class="wfn-info-box">
                            <p><strong>Enhanced User Experience:</strong> AJAX search provides real-time search results without page reloads, making the search experience faster and more responsive.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label class="wfn-toggle-switch">
                                <input type="checkbox"
                                       name="wfn_module_settings[enable_ajax_search]"
                                       id="enable_ajax_search"
                                       value="1"
                                       <?php checked($settings['enable_ajax_search']); ?>>
                                <span class="wfn-toggle-slider"></span>
                                <span class="wfn-toggle-label">Enable AJAX Search</span>
                            </label>
                            <p class="wfn-form-description">Enable real-time search without page reload.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label for="min_search_length">Minimum Search Length</label>
                            <input type="number" 
                                   name="wfn_module_settings[min_search_length]" 
                                   id="min_search_length" 
                                   value="<?php echo esc_attr($settings['min_search_length']); ?>" 
                                   min="1" 
                                   max="10">
                            <p class="wfn-form-description">Minimum number of characters required to trigger search.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label for="search_delay">Search Delay (ms)</label>
                            <input type="number" 
                                   name="wfn_module_settings[search_delay]" 
                                   id="search_delay" 
                                   value="<?php echo esc_attr($settings['search_delay']); ?>" 
                                   min="100" 
                                   max="2000" 
                                   step="100">
                            <p class="wfn-form-description">Delay before triggering search after user stops typing.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label for="results_per_page">Results Per Page</label>
                            <input type="number" 
                                   name="wfn_module_settings[results_per_page]" 
                                   id="results_per_page" 
                                   value="<?php echo esc_attr($settings['results_per_page']); ?>" 
                                   min="1" 
                                   max="50">
                            <p class="wfn-form-description">Number of results to display per page in AJAX search.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label class="wfn-toggle-switch">
                                <input type="checkbox"
                                       name="wfn_module_settings[enable_search_suggestions]"
                                       id="enable_search_suggestions"
                                       value="1"
                                       <?php checked($settings['enable_search_suggestions']); ?>>
                                <span class="wfn-toggle-slider"></span>
                                <span class="wfn-toggle-label">Enable Search Suggestions</span>
                            </label>
                            <p class="wfn-form-description">Show search suggestions as user types.</p>
                        </div>
                    </div>
                    
                    <!-- Analytics Tab -->
                    <div id="analytics" class="wfn-tab-pane">
                        <h3>Search Analytics</h3>
                        <div class="wfn-info-box">
                            <p><strong>Optional Feature:</strong> Search analytics tracks what visitors search for on your site. This is useful for understanding user behavior but not required for basic search functionality.</p>
                            <p>When enabled, anonymous search data is stored locally in your WordPress database for analysis.</p>
                        </div>
                        
                        <div class="wfn-form-group">
                            <label class="wfn-toggle-switch">
                                <input type="checkbox"
                                       name="wfn_module_settings[enable_search_analytics]"
                                       id="enable_search_analytics"
                                       value="1"
                                       <?php checked($settings['enable_search_analytics']); ?>>
                                <span class="wfn-toggle-slider"></span>
                                <span class="wfn-toggle-label">Enable Search Analytics</span>
                            </label>
                            <p class="wfn-form-description">Track search queries and results for analysis. <strong>Disabled by default.</strong></p>
                        </div>
                        
                        <?php if ($settings['enable_search_analytics']): ?>
                        <div class="wfn-search-analytics-display">
                            <?php $this->display_search_analytics(); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php $this->render_submit_button(); ?>
        </form>
        
        <style>
            .wfn-info-box {
                background: #f0f8ff;
                border-left: 4px solid #1f4b8f;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            
            .wfn-info-box p {
                margin: 0 0 10px 0;
                color: #333;
            }
            
            .wfn-info-box p:last-child {
                margin-bottom: 0;
            }
            
            .wfn-search-fields-list {
                display: grid;
                gap: 15px;
                margin-top: 15px;
            }
            
            .wfn-search-field-item {
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background: #f9f9f9;
            }
            
            .wfn-search-field-item label {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 5px;
            }
            
            .wfn-field-description {
                color: #666;
                font-size: 13px;
                margin: 0;
            }
            
            .wfn-field-weight {
                color: #999;
                font-weight: 500;
            }
            
            .wfn-search-analytics-display {
                margin-top: 20px;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 5px;
            }
        </style>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.nav-tab');
                const contents = document.querySelectorAll('.wfn-tab-pane');
                
                tabs.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Remove active classes
                        tabs.forEach(t => t.classList.remove('nav-tab-active'));
                        contents.forEach(c => c.classList.remove('active'));
                        
                        // Add active class to clicked tab
                        this.classList.add('nav-tab-active');
                        
                        // Show target content
                        const target = this.getAttribute('href').substring(1);
                        const targetElement = document.getElementById(target);
                        if (targetElement) {
                            targetElement.classList.add('active');
                        }
                    });
                });
            });
        </script>
        <?php
    }
    
    /**
     * Display search analytics
     */
    private function display_search_analytics(): void {
        $analytics = get_option('wfn_search_analytics', []);
        
        if (empty($analytics)) {
            echo '<p>No search analytics data available yet.</p>';
            return;
        }
        
        // Calculate basic stats
        $total_searches = 0;
        $popular_terms = [];
        
        foreach ($analytics as $date => $searches) {
            $total_searches += count($searches);
            
            foreach ($searches as $search) {
                $term = strtolower(trim($search['term']));
                if (!empty($term)) {
                    $popular_terms[$term] = ($popular_terms[$term] ?? 0) + 1;
                }
            }
        }
        
        arsort($popular_terms);
        $top_terms = array_slice($popular_terms, 0, 10, true);
        
        ?>
        <div class="wfn-analytics-stats">
            <h4>Search Statistics</h4>
            <p><strong>Total Searches:</strong> <?php echo esc_html($total_searches); ?></p>
            <p><strong>Unique Terms:</strong> <?php echo esc_html(count($popular_terms)); ?></p>
            <p><strong>Average per Day:</strong> <?php echo esc_html(round($total_searches / count($analytics), 1)); ?></p>
            
            <h4>Top Search Terms</h4>
            <ol>
                <?php foreach ($top_terms as $term => $count): ?>
                    <li><?php echo esc_html($term); ?> <span class="wfn-search-count">(<?php echo esc_html($count); ?>)</span></li>
                <?php endforeach; ?>
            </ol>
        </div>
        
        <style>
            .wfn-analytics-stats ol {
                margin-left: 20px;
            }
            
            .wfn-search-count {
                color: #666;
                font-size: 0.9em;
            }
        </style>
        <?php
    }
    
    /**
     * Sanitize settings with specific validation
     */
    protected function sanitize_settings(array $settings): array {
        $sanitized = [];
        
        // Boolean settings
        $boolean_settings = [
            'enable_advanced_search', 'enable_date_range', 'enable_location_filter',
            'enable_ajax_search', 'enable_search_suggestions', 'enable_relevance_scoring',
            'highlight_search_terms', 'enable_search_analytics', 'show_search_count',
            'enable_fuzzy_search', 'enable_search_history'
        ];
        
        foreach ($boolean_settings as $setting) {
            $sanitized[$setting] = !empty($settings[$setting]);
        }
        
        // Search fields validation
        $valid_fields = array_keys($this->searchable_fields);
        $sanitized['search_fields'] = array_intersect($settings['search_fields'] ?? [], $valid_fields);
        
        // Ensure at least one field is selected
        if (empty($sanitized['search_fields'])) {
            $sanitized['search_fields'] = ['name'];
        }
        
        // Search form style validation
        $valid_styles = array_keys($this->search_form_styles);
        $sanitized['search_form_style'] = in_array($settings['search_form_style'] ?? '', $valid_styles) 
            ? $settings['search_form_style'] 
            : 'modern';
        
        // String settings
        $sanitized['search_placeholder'] = sanitize_text_field($settings['search_placeholder'] ?? 'Search funeral notices...');
        $sanitized['date_range_format'] = sanitize_text_field($settings['date_range_format'] ?? 'Y-m-d');
        
        // Numeric settings
        $sanitized['results_per_page'] = max(1, min(50, (int) ($settings['results_per_page'] ?? 12)));
        $sanitized['min_search_length'] = max(1, min(10, (int) ($settings['min_search_length'] ?? 3)));
        $sanitized['search_delay'] = max(100, min(2000, (int) ($settings['search_delay'] ?? 300)));
        $sanitized['max_search_history'] = max(5, min(50, (int) ($settings['max_search_history'] ?? 10)));
        
        // Arrays
        $sanitized['search_operators'] = $settings['search_operators'] ?? ['AND', 'OR'];
        
        return $sanitized;
    }
}