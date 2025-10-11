<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Shortcodes;

use WeaveStudios\FuneralNotices\Templates\TemplateManager;
use WeaveStudios\FuneralNotices\Plugin;

/**
 * Funeral Notices Shortcode Handler
 * Consolidates functionality from weave-funeral-notice-display plugin
 * Adds enhanced display modes and better caching
 * 
 * @since 2.0.0
 */
class FuneralNoticesShortcode {

    private TemplateManager $template_manager;

    public function __construct(TemplateManager $template_manager) {
        $this->template_manager = $template_manager;
    }

    /**
     * Register shortcodes
     */
    public function register(): void {
        add_shortcode('funeral_notices', [$this, 'render_funeral_grid']);
        add_shortcode('funeral_notice_single', [$this, 'render_single_notice']);
    }

    /**
     * Main shortcode handler for funeral notices grid
     * Enhanced version of the original display plugin shortcode
     * 
     * Available shortcode attributes:
     * - layout/style: Layout style (firehawk, modern, elegant, minimal)
     * - type: Filter type (all, future, archived, today, this_week, this_month)
     * - per_page: Number of items per page (default: 12)
     * - columns: Number of columns (1, 2, 3, 4)
     * - show_pagination: Enable pagination (yes, no)
     * - show_search: Enable search form (yes, no)
     * - location: Filter by location slug
     * - date_from: Filter from date (Y-m-d format)
     * - date_to: Filter to date (Y-m-d format)
     * - ids: Comma-separated list of post IDs to display (e.g., "123,456,789")
     * - exclude: Comma-separated list of post IDs to exclude (e.g., "123,456")
     *
     * Example usage:
     * [funeral_notices layout="modern" columns="3" type="future"]
     * [funeral_notices layout="elegant" per_page="6" show_search="no"]
     * [funeral_notices layout="minimal" columns="2" show_search="yes"]
     * [funeral_notices ids="123,456" columns="2" layout="modern"]
     * [funeral_notices exclude="123,456" layout="modern"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_funeral_grid($atts = []): string {
        // Parse shortcode attributes
        $atts = shortcode_atts([
            'type' => 'all',           // all, future, archived, today, this_week, this_month
            'per_page' => 12,          // Number of items per page
            'style' => $this->template_manager->get_archive_mode(),     // Use archive setting as default
            'layout' => '',           // Alternative parameter name for style (documented usage)
            'columns' => 3,            // 1, 2, 3, 4
            'show_pagination' => 'yes', // yes, no
            'show_search' => 'yes',    // yes, no - show search form above grid
            'location' => '',          // Filter by specific location slug
            'date_from' => '',         // Filter from specific date (Y-m-d format)
            'date_to' => '',           // Filter to specific date (Y-m-d format)
            'ids' => '',               // Comma-separated post IDs (e.g., "123,456,789")
            'exclude' => ''            // Comma-separated post IDs to exclude (e.g., "123,456")
        ], $atts);

        // Sanitize inputs
        $type = sanitize_text_field($atts['type']);
        $per_page = (int) $atts['per_page'];
        
        // Support both 'layout' and 'style' parameters (layout takes precedence if provided)
        $style = !empty($atts['layout']) ? sanitize_text_field($atts['layout']) : sanitize_text_field($atts['style']);
        
        $columns = max(1, min(4, (int) $atts['columns']));
        $show_pagination = $atts['show_pagination'] === 'yes';
        $show_search = $atts['show_search'] === 'yes';
        
        // Override with GET parameters if search form was submitted
        $location_search = sanitize_text_field($_GET['wfn_location_search'] ?? $atts['location'] ?? '');
        $date_from = sanitize_text_field($_GET['wfn_date_from'] ?? $atts['date_from']);
        $date_to = sanitize_text_field($_GET['wfn_date_to'] ?? $atts['date_to']);
        $search_term = sanitize_text_field($_GET['wfn_search'] ?? '');

        // Get current page for pagination
        $paged = max(1, (int) (get_query_var('paged') ?: 1));

        // Build query arguments
        $args = [
            'post_type' => 'funeral-notice',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'post_status' => 'publish'
        ];

        // Handle specific post IDs if provided
        $specific_ids = !empty($atts['ids']) ? sanitize_text_field($atts['ids']) : '';
        if (!empty($specific_ids)) {
            // Parse comma-separated IDs
            $id_array = array_map('intval', array_filter(explode(',', $specific_ids)));

            if (!empty($id_array)) {
                $args['post__in'] = $id_array;
                $args['orderby'] = 'post__in'; // Maintain the order specified in the shortcode
                $args['posts_per_page'] = -1; // Show all specified IDs

                // Disable pagination and search when showing specific IDs
                $show_pagination = false;
                $show_search = false;
            }
        }

        // Handle excluded post IDs if provided
        $exclude_ids = !empty($atts['exclude']) ? sanitize_text_field($atts['exclude']) : '';
        if (!empty($exclude_ids)) {
            // Parse comma-separated IDs
            $exclude_array = array_map('intval', array_filter(explode(',', $exclude_ids)));

            if (!empty($exclude_array)) {
                $args['post__not_in'] = $exclude_array;
            }
        }
        
        // Order by funeral date first (Y-m-d), then fall back to post date for items without a date
        // Notes:
        // - meta_type=DATE ensures correct chronological ordering
        // - Posts without the meta_key naturally fall to the end of the result set
        // - Secondary 'date' ordering keeps no-date items sorted by publish date (newest first)
        $args['meta_key'] = 'wfn_details_group_funeral_date';
        $args['meta_type'] = 'DATE';
        $args['orderby'] = [
            'meta_value' => 'DESC', // furthest dates first (Oct 4 before Sep 26, Sep 25)
            'date'       => 'DESC', // for notices without dates, newest published first
        ];

        // Add date filtering based on type
        $today = date('Y-m-d');
        $meta_query = [];
        
        if ($type === 'archived') {
            $meta_query[] = [
                'key' => 'wfn_details_group_funeral_date',
                'value' => $today,
                'type' => 'DATE',
                'compare' => '<'
            ];
        } elseif ($type === 'future') {
            $meta_query[] = [
                'key' => 'wfn_details_group_funeral_date',
                'value' => $today,
                'type' => 'DATE',
                'compare' => '>='
            ];
        } elseif ($type === 'today') {
            $meta_query[] = [
                'key' => 'wfn_details_group_funeral_date',
                'value' => $today,
                'type' => 'DATE',
                'compare' => '='
            ];
        } elseif ($type === 'this_week') {
            $week_start = date('Y-m-d', strtotime('monday this week'));
            $week_end = date('Y-m-d', strtotime('sunday this week'));
            $meta_query[] = [
                'key' => 'wfn_details_group_funeral_date',
                'value' => [$week_start, $week_end],
                'type' => 'DATE',
                'compare' => 'BETWEEN'
            ];
        } elseif ($type === 'this_month') {
            $month_start = date('Y-m-01');
            $month_end = date('Y-m-t');
            $meta_query[] = [
                'key' => 'wfn_details_group_funeral_date',
                'value' => [$month_start, $month_end],
                'type' => 'DATE',
                'compare' => 'BETWEEN'
            ];
        }

        // Add custom date range filtering
        if (!empty($date_from)) {
            $meta_query[] = [
                'key' => 'wfn_details_group_funeral_date',
                'value' => $date_from,
                'type' => 'DATE',
                'compare' => '>='
            ];
        }

        if (!empty($date_to)) {
            $meta_query[] = [
                'key' => 'wfn_details_group_funeral_date',
                'value' => $date_to,
                'type' => 'DATE',
                'compare' => '<='
            ];
        }

        // Apply meta query if we have conditions
        if (!empty($meta_query)) {
            if (count($meta_query) > 1) {
                $meta_query['relation'] = 'AND';
            }
            $args['meta_query'] = $meta_query;
        }

        // Add location filtering
        if (!empty($location_search)) {
            // Use the SearchManager's location search functionality
            $search_manager = new \WeaveStudios\FuneralNotices\Admin\SearchManager();
            
            // Get posts with matching taxonomy locations
            $tax_posts = [];
            $location_terms = get_terms([
                'taxonomy' => 'funeral-location',
                'name__like' => $location_search,
                'hide_empty' => false
            ]);
            
            if (!empty($location_terms)) {
                $tax_query = new \WP_Query([
                    'post_type' => 'funeral-notice',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'tax_query' => [
                        [
                            'taxonomy' => 'funeral-location',
                            'field' => 'term_id',
                            'terms' => wp_list_pluck($location_terms, 'term_id')
                        ]
                    ]
                ]);
                $tax_posts = $tax_query->posts;
            }
            
            // Get posts with matching custom addresses
            $address_posts = [];
            $address_meta_query = new \WP_Query([
                'post_type' => 'funeral-notice',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => 'wfn_details_group_custom_address_address',
                        'value' => $location_search,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'wfn_details_group_custom_address_name',
                        'value' => $location_search,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'wfn_details_group_custom_address_street_name',
                        'value' => $location_search,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'wfn_details_group_custom_address_city',
                        'value' => $location_search,
                        'compare' => 'LIKE'
                    ],
                    // Legacy field support
                    [
                        'key' => 'wfn_location_group_other_funeral_address_address',
                        'value' => $location_search,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'wfn_location_group_other_funeral_address_name',
                        'value' => $location_search,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'wfn_location_group_other_funeral_address_street_name',
                        'value' => $location_search,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'wfn_location_group_other_funeral_address_city',
                        'value' => $location_search,
                        'compare' => 'LIKE'
                    ]
                ]
            ]);
            $address_posts = $address_meta_query->posts;
            
            // Combine both result sets
            $matching_posts = array_unique(array_merge($tax_posts, $address_posts));
            
            if (!empty($matching_posts)) {
                $args['post__in'] = $matching_posts;
            } else {
                // No matches found - return empty result
                $args['post__in'] = [0];
            }
        }

        // Add search functionality
        if (!empty($search_term)) {
            // Check if Relevanssi is active and configured for funeral notices
            if (function_exists('relevanssi_do_query') && $this->is_relevanssi_configured()) {
                // Use Relevanssi for search - it should handle ACF fields if configured
                $args['s'] = $search_term;
                // Don't add meta_query for search when using Relevanssi
            } else {
                // Use native WordPress search with meta_query for ACF fields
                $search_meta_query = [
                    'relation' => 'OR',
                    [
                        'key' => 'wfn_person_group_firstname',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'wfn_person_group_lastname', 
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    ]
                ];
                
                // Combine with existing meta query
                if (!empty($args['meta_query'])) {
                    // If we already have meta queries (like date filters), combine them
                    $args['meta_query'] = [
                        'relation' => 'AND',
                        $args['meta_query'],
                        $search_meta_query
                    ];
                } else {
                    $args['meta_query'] = $search_meta_query;
                }
                
                // Also add WordPress native search for post title/content
                $args['s'] = $search_term;
            }
        }

        // Execute query
        $query = new \WP_Query($args);
        
        // Start output
        ob_start();
        
        // Render search form if requested
        if ($show_search) {
            // Enqueue search CSS for shortcode forms (without JavaScript)
            wp_enqueue_style(
                'wfn-search',
                plugin_dir_url(__FILE__) . '../../assets/css/search.css',
                [],
                '2.0.2'
            );
            
            // Add inline CSS to ensure mobile responsive styles work
            $inline_css = '
                @media screen and (max-width: 900px) {
                    .wfn-shortcode-search-form .wfn-search-row {
                        flex-direction: column !important;
                        gap: 10px !important;
                        width: 100% !important;
                    }
                    .wfn-shortcode-search-form .wfn-search-field {
                        min-width: auto !important;
                        width: 100% !important;
                        flex: none !important;
                        max-width: 100% !important;
                    }
                    .wfn-shortcode-search-form .wfn-search-field input,
                    .wfn-shortcode-search-form .wfn-search-field select {
                        width: 100% !important;
                        box-sizing: border-box !important;
                        max-width: 100% !important;
                    }
                    .wfn-shortcode-search-form .wfn-search-field.wfn-date-field {
                        display: none !important;
                    }
                }
            ';
            wp_add_inline_style('wfn-search', $inline_css);
            
            $this->render_shortcode_search_form($type, $location_search, $date_from, $date_to, $search_term);
        }
        
        if (!$query->have_posts()) {
            // Show no results message after search form
            echo '<div class="wfn-no-results">No funerals found, please try another search.</div>';
            wp_reset_postdata();
            return ob_get_clean();
        }
        
        // Render based on style
        switch ($style) {
            case 'firehawk':
                $this->render_firehawk_grid($query, $columns);
                break;
            case 'modern':
                $this->render_enhancement_modern_grid($query, $columns);
                break;
            case 'elegant':
                $this->render_enhancement_elegant_grid($query, $columns);
                break;
            case 'minimal':
                $this->render_enhancement_minimal($query, $columns);
                break;
            default:
                $this->render_firehawk_grid($query, $columns); // Fallback
        }

        // Enqueue Load More assets (always enqueue to avoid issues)
        wp_enqueue_style(
            'wfn-load-more',
            plugin_dir_url(__FILE__) . '../../assets/css/load-more.css',
            [],
            '2.4.0'
        );

        wp_enqueue_script(
            'wfn-load-more',
            plugin_dir_url(__FILE__) . '../../assets/js/load-more.js',
            ['jquery'],
            '2.4.0',
            true
        );

        wp_localize_script('wfn-load-more', 'wfnLoadMore', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wfn_load_more_nonce')
        ]);

        // Add Load More button if there are more posts
        $total_posts = $query->found_posts;
        $shown_posts = $query->post_count;

        if ($total_posts > $shown_posts) {
            $settings_option = get_option('wfn_module_settings', []);
            $load_more_posts = $settings_option['load_more_posts'] ?? 9;

            // Build filters array for AJAX
            $filters = [
                'type' => $type,
                'location' => $location_search,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'search_term' => $search_term
            ];

            echo '<div class="wfn-load-more-container">';
            echo '<button class="wfn-load-more-button"';
            echo ' data-offset="' . esc_attr($shown_posts) . '"';
            echo ' data-per-load="' . esc_attr($load_more_posts) . '"';
            echo ' data-layout="' . esc_attr($style) . '"';
            echo ' data-filters="' . esc_attr(json_encode($filters)) . '">';
            echo 'Load More';
            echo '</button>';
            echo '</div>';
        }

        $output = ob_get_clean();
        wp_reset_postdata();

        return $output;
    }

    /**
     * Ensure StylingModule assets are enqueued when shortcodes render
     * This reconnects the Visual Styling system to shortcode output
     */
    private function ensure_styling_module_assets(): void {
        $plugin = Plugin::getInstance();
        $styling_module = $plugin->get_module('styling');
        
        if ($styling_module && $styling_module->is_enabled()) {
            // Force CSS regeneration to ensure latest settings are applied
            $styling_module->generate_css_file();
            
            // Enqueue StylingModule's frontend assets
            $styling_module->enqueue_styling_assets();
            
            // Ensure custom CSS is output
            add_action('wp_head', [$styling_module, 'output_custom_css'], 5);
            
            // Add styling body classes if not already added
            add_filter('body_class', [$styling_module, 'add_styling_body_classes'], 5);
        }
    }
    
    /**
     * Render Firehawk-compatible grid (uses consistent wfn classes)
     */
    private function render_firehawk_grid(\WP_Query $query, int $columns): void {
        // Ensure StylingModule assets are loaded for color schemes and typography
        $this->ensure_styling_module_assets();
        
        // Enqueue Firehawk CSS
        wp_enqueue_style('wfn-firehawk', plugin_dir_url(__FILE__) . '../../assets/css/firehawk-compat.css', [], '2.0.1');
        
        echo '<div class="firehawk-crm firehawk-crm-large-grid" id="wfn-tributes-list">';
        echo '<div class="firehawk-crm-large-grid-view">';

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Use TemplateManager for unified data access
            $data = $this->template_manager->get_funeral_data($post_id);
            $person = $data['person'];
            
            $first_name = $person['first_name'];
            $last_name = $person['last_name'];
            $full_name = $person['full_name'];
            $years_display = $person['years_display'];
            
            // Format name for Firehawk style (LASTNAME, First)
            $formatted_name = strtoupper($last_name) . ', ' . $first_name;
            
            // Get image
            $featured_image = get_the_post_thumbnail_url($post_id, 'medium');
            $fallback_url = $this->get_fallback_image_url();
            $image_url = $featured_image ?: $fallback_url;

            echo '<div class="grid-col">';
            echo '<a href="' . esc_url(get_permalink($post_id)) . '">';
            echo '<div class="grid-item compact">';
            echo '<div class="top-content">';
            echo '<div class="top-img" style="background-image: url(\'' . esc_url($image_url) . '\')"></div>';
            echo '<div class="title-container">';
            echo '<div class="title">' . esc_html($formatted_name) . '</div>';
            if ($years_display) {
                echo '<div class="dates">' . esc_html($years_display) . '</div>';
            }
            echo '</div></div></div></a></div>';
        }

        echo '</div></div>';
    }


    /**
     * Render current grid layout - DEPRECATED: Removed in v2.0.1
     * This layout has been removed. Falls back to modern layout.
     */
    private function render_current_grid(\WP_Query $query, int $columns): void {
        // Fallback to modern grid since current layout is removed
        $this->render_modern_grid($query, $columns);
    }

    /**
     * Legacy current grid implementation (DEPRECATED)
     * This method is kept for reference but is no longer used
     */
    private function render_current_grid_legacy(\WP_Query $query, int $columns): void {
        echo '<ul class="fn_notices">';

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Use direct ACF access like the old working version
            $person_group = get_field('wfn_person_group', $post_id) ?: [];
            $details_group = get_field('wfn_details_group', $post_id) ?: [];
            $location_group = get_field('wfn_location_group', $post_id) ?: [];
            $notice_group = get_field('wfn_notice_group', $post_id) ?: [];
            $streaming_group = get_field('wfn_streaming_group', $post_id) ?: [];
            
            $first_name = $person_group['firstname'] ?? '';
            $last_name = $person_group['lastname'] ?? '';
            $birth_year = $person_group['birth_year'] ?? '';
            $death_year = $person_group['death_year'] ?? '';
            
            $full_name = trim("{$first_name} {$last_name}");
            $years_display = ($birth_year && $death_year) ? "{$birth_year} - {$death_year}" : '';
            
            // Get funeral date and time
            $funeral_date = $details_group['funeral_date'] ?? '';
            $funeral_time = $details_group['funeral_time'] ?? '';
            $hide_time = $details_group['hide_time'] ?? false;
            
            // Format date and time
            $formatted_date = $funeral_date ? date('j F Y', strtotime($funeral_date)) : '';
            $formatted_time = ($funeral_time && !$hide_time) ? date('g:i A', strtotime($funeral_time)) : '';
            
            // Get location
            $venue_name = $location_group['venue_name'] ?? '';
            $address = $location_group['address'] ?? '';
            
            // Get notice content (truncated)
            $notice_content = $notice_group['notice'] ?? '';
            $excerpt = $notice_content ? wp_trim_words(strip_tags($notice_content), 60) : '';
            if (strlen($excerpt) > 360) {
                $excerpt = substr($excerpt, 0, 360) . '...';
            }
            
            // Get streaming info
            $has_streaming = !empty($streaming_group['streaming_service']);
            
            // Get image
            $featured_image = get_the_post_thumbnail_url($post_id, 'funeral-image');
            $fallback_url = $this->get_fallback_image_url();
            $image_url = $featured_image ?: $fallback_url;

            echo '<li>';
            echo '<div class="single_notice">';
            
            // First column - Image
            echo '<div class="first column">';
            echo '<div class="profile">';
            if ($image_url) {
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($full_name) . '">';
            }
            echo '</div>';
            echo '</div>';
            
            // Middle column - Main content
            echo '<div class="middle column">';
            echo '<h3 class="memorial">';
            echo '<a href="' . esc_url(get_permalink($post_id)) . '">' . esc_html($full_name) . '</a>';
            if (current_user_can('edit_post', $post_id)) {
                echo '<span>  <a href="' . admin_url('post.php?post=' . $post_id . '&action=edit') . '">[edit]</a></span>';
            }
            echo '</h3>';
            
            if ($years_display) {
                echo '<div class="years">' . esc_html($years_display) . '</div>';
            }
            
            if ($excerpt) {
                echo '<div class="notice">' . wp_kses_post($excerpt) . '</div>';
            }
            
            echo '<div class="view-more"><a href="' . esc_url(get_permalink($post_id)) . '">View details &rarr;</a></div>';
            echo '</div>';
            
            // Last column - Date, location, streaming
            echo '<div class="last column">';
            
            // Date and time
            if ($formatted_date) {
                echo '<div class="funeral_date">';
                echo '<h5 class="details">Service Date & Time</h5>';
                echo '<span>' . esc_html($formatted_date) . '</span>';
                if ($formatted_time) {
                    echo '<br><span>' . esc_html($formatted_time) . '</span>';
                }
                echo '</div>';
            }
            
            // Location
            if ($venue_name || $address) {
                echo '<div class="location">';
                echo '<h5 class="details">Location</h5>';
                if ($venue_name) {
                    echo '<div class="fn_loc_name">' . esc_html($venue_name) . '</div>';
                }
                if ($address) {
                    echo '<div class="address">' . esc_html($address) . '</div>';
                }
                echo '</div>';
            }
            
            // Streaming
            if ($has_streaming) {
                echo '<div class="stream">';
                echo '<h5 class="details">Online Streaming</h5>';
                echo '<a href="' . esc_url(get_permalink($post_id)) . '">View streaming details</a>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
            echo '</li>';
        }

        echo '</ul>';
    }

    /**
     * Render pagination with FCRM-style buttons
     */
    private function render_pagination(\WP_Query $query, int $paged): void {
        // Enqueue pagination CSS
        wp_enqueue_style('wfn-pagination', plugin_dir_url(__FILE__) . '../../assets/css/pagination.css', [], '2.0.0');
        
        echo '<div class="wfn-pagination">';
        echo '<ul class="wfn-pagination-list">';
        
        $total_pages = $query->max_num_pages;
        $current_page = $paged;
        
        // Previous button
        if ($current_page > 1) {
            $prev_url = str_replace('999999999', (string)($current_page - 1), esc_url(get_pagenum_link(999999999)));
            echo '<li><a href="' . $prev_url . '" class="wfn-pagination-btn wfn-pagination-prev">&laquo;</a></li>';
        }
        
        // Page numbers with smart range
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        
        // Show first page if we're not showing it in range
        if ($start > 1) {
            $first_url = str_replace('999999999', '1', esc_url(get_pagenum_link(999999999)));
            echo '<li><a href="' . $first_url . '" class="wfn-pagination-btn">1</a></li>';
            if ($start > 2) {
                echo '<li><span class="wfn-pagination-dots">...</span></li>';
            }
        }
        
        // Page range
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $current_page) {
                echo '<li><span class="wfn-pagination-btn wfn-pagination-current">' . $i . '</span></li>';
            } else {
                $page_url = str_replace('999999999', (string)$i, esc_url(get_pagenum_link(999999999)));
                echo '<li><a href="' . $page_url . '" class="wfn-pagination-btn">' . $i . '</a></li>';
            }
        }
        
        // Show last page if we're not showing it in range
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) {
                echo '<li><span class="wfn-pagination-dots">...</span></li>';
            }
            $last_url = str_replace('999999999', (string)$total_pages, esc_url(get_pagenum_link(999999999)));
            echo '<li><a href="' . $last_url . '" class="wfn-pagination-btn">' . $total_pages . '</a></li>';
        }
        
        // Next button
        if ($current_page < $total_pages) {
            $next_url = str_replace('999999999', (string)($current_page + 1), esc_url(get_pagenum_link(999999999)));
            echo '<li><a href="' . $next_url . '" class="wfn-pagination-btn wfn-pagination-next">&raquo;</a></li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }

    /**
     * Render search form for shortcodes - Enhancement Suite style
     */
    private function render_shortcode_search_form(string $type, string $location_search, string $date_from, string $date_to, string $search_term = ''): void {
        // Get current page URL without query parameters
        $current_url = is_front_page() ? home_url('/') : get_permalink();
        
        // Enqueue Enhancement Suite search CSS
        wp_enqueue_style('wfn-enhancement-search', plugin_dir_url(__FILE__) . '../../assets/css/layouts/enhancement-search.css', [], '2.0.0');
        ?>
        <div class="wfn-enhancement-search">
            <form method="get" action="<?php echo esc_url($current_url); ?>" class="wfn-enhancement-search-form">
                <!-- Hidden field to maintain page context -->
                <?php if (is_front_page()): ?>
                    <input type="hidden" name="wfn_shortcode_search" value="1" />
                <?php endif; ?>
                
                <div class="search-container">
                    <!-- Name Search - Primary -->
                    <div class="name-search-field">
                        <label for="wfn_search_input" class="visually-hidden">Search by name</label>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                                    <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </span>
                            <input type="text" 
                                   id="wfn_search_input"
                                   name="wfn_search" 
                                   class="search-input"
                                   placeholder="Search by name..." 
                                   aria-label="Search funeral notices by name"
                                   value="<?php echo esc_attr($search_term); ?>" 
                                   autocomplete="off" />
                            <?php if ($search_term): ?>
                            <button type="button" class="clear-btn" 
                                    aria-label="Clear name search"
                                    onclick="this.previousElementSibling.value=''; this.parentElement.parentElement.parentElement.submit();">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/>
                                    <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Date Range - Single Flatpickr Field -->
                    <fieldset class="date-range-field">
                        <legend class="visually-hidden">Filter by date range</legend>
                        <div class="input-group">
                            <span class="input-icon" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/>
                                    <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/>
                                    <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </span>
                            <label for="wfn-date-range" class="visually-hidden">Select date range</label>
                            <input type="text"
                                   id="wfn-date-range"
                                   class="date-input"
                                   placeholder="Select date range..."
                                   aria-label="Select date range for filtering"
                                   readonly
                                   value="">

                            <!-- Hidden inputs for backend compatibility -->
                            <input type="hidden"
                                   id="wfn-date-from"
                                   name="wfn_date_from"
                                   value="<?php echo esc_attr($_GET['wfn_date_from'] ?? $date_from); ?>">
                            <input type="hidden"
                                   id="wfn-date-to"
                                   name="wfn_date_to"
                                   value="<?php echo esc_attr($_GET['wfn_date_to'] ?? $date_to); ?>">

                            <?php if ($date_from || $date_to): ?>
                            <button type="button" class="clear-btn wfn-date-clear"
                                    aria-label="Clear date filter"
                                    onclick="const group = this.parentElement; group.querySelector('#wfn-date-range').value=''; group.querySelector('#wfn-date-from').value=''; group.querySelector('#wfn-date-to').value=''; group.closest('form').submit();">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/>
                                    <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </div>
                    </fieldset>
                    
                    <!-- Search Button -->
                    <div class="search-actions">
                        <button type="submit" class="search-btn" aria-label="Search funeral notices">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>Search</span>
                        </button>
                        
                        <?php if ($search_term || $date_from || $date_to): ?>
                        <a href="<?php echo esc_url($current_url); ?>" class="clear-all-btn" aria-label="Clear all filters">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Check if Relevanssi is properly configured for funeral notices
     */
    private function is_relevanssi_configured(): bool {
        if (!function_exists('relevanssi_get_options')) {
            return false;
        }

        $options = relevanssi_get_options();
        $indexed_post_types = $options['index_post_types'] ?? [];
        
        return in_array('funeral-notice', $indexed_post_types);
    }

    /**
     * Get current page URL for form action
     */
    private function get_current_page_url(): string {
        global $wp;
        
        // For homepage, use the home URL
        if (is_front_page()) {
            return home_url('/');
        }
        
        // For other pages, get the current URL without query parameters
        return home_url(add_query_arg([], $wp->request));
    }

    /**
     * Single funeral notice shortcode
     */
    public function render_single_notice($atts = []): string {
        $atts = shortcode_atts([
            'id' => 0,
            'style' => 'modern'
        ], $atts);

        $post_id = (int) $atts['id'];
        if (!$post_id || get_post_type($post_id) !== 'funeral-notice') {
            return '<div class="wfn-error">Invalid funeral notice ID.</div>';
        }

        return $this->template_manager->render_template('single', [
            'post_id' => $post_id,
            'style' => sanitize_text_field($atts['style'])
        ]);
    }

    /**
     * Render Enhancement Suite Modern Grid (native WFN version)
     */
    private function render_enhancement_modern_grid(\WP_Query $query, int $columns): void {
        // Ensure StylingModule assets are loaded for color schemes and typography
        $this->ensure_styling_module_assets();
        
        // Enqueue shared base styles first
        wp_enqueue_style('wfn-enhancement-base', plugin_dir_url(__FILE__) . '../../assets/css/layouts/shared-base.css', [], '2.0.1');
        // Enqueue Enhancement Suite modern grid CSS
        wp_enqueue_style('wfn-enhancement-modern', plugin_dir_url(__FILE__) . '../../assets/css/layouts/modern-grid.css', ['wfn-enhancement-base'], '2.0.1');
        
        $grid_class = "wfn-enhancement-modern-grid wfn-cols-{$columns}";
        echo "<div class=\"{$grid_class}\">";

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Use TemplateManager for unified data access
            $data = $this->template_manager->get_funeral_data($post_id);
            $person = $data['person'];
            $event = $data['event'];
            
            $full_name = $person['full_name'];
            $years_display = $person['years_display'];
            
            // Get funeral date and time for service info
            $funeral_date = $event['funeral_date'];
            $funeral_time = $event['funeral_time'];
            
            // Get image
            $featured_image = get_the_post_thumbnail_url($post_id, 'medium');
            $fallback_url = $this->get_fallback_image_url();
            $image_url = $featured_image ?: $fallback_url;

            echo '<article class="wfn-enhancement-modern-card">';
            echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="wfn-enhancement-modern-link">';
            
            if ($image_url) {
                echo '<div class="wfn-enhancement-modern-image">';
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($full_name) . '" loading="lazy">';
                echo '</div>';
            }
            
            echo '<div class="wfn-enhancement-modern-content">';
            
            // Check if we should hide date/time/venue
            $hide_details = $event['hide_time'] ?? false;
            
            // Show floating streaming icon when details are hidden but streaming is available
            $streaming = $data['streaming'];
            if ($hide_details && $streaming['is_public'] && !empty($streaming['streaming_url'])) {
                echo '<span class="wfn-streaming-icon-float" title="Live streaming available">';
                echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">';
                echo '<path d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm128-64l0 128c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-40 80 40 0-128-80 40 0-40c0-17.7-14.3-32-32-32l-128 0c-17.7 0-32 14.3-32 32z"/>';
                echo '</svg>';
                echo '</span>';
            }
            
            echo '<h3 class="wfn-enhancement-modern-title">' . esc_html($full_name) . '</h3>';
            
            if ($years_display) {
                echo '<p class="wfn-enhancement-modern-dates">' . esc_html($years_display) . '</p>';
            }
            
            if ($funeral_date && !$hide_details) {
                echo '<div class="wfn-enhancement-modern-service">';
                echo '<span class="wfn-service-info">';
                echo '<span class="service-label">Service:</span> ';
                echo esc_html(date('j M Y', strtotime($funeral_date)));
                if ($funeral_time) {
                    $formatted_time = date('g:i A', strtotime($funeral_time));
                    echo ' at ' . esc_html($formatted_time);
                }
                echo '</span>';
                
                // Check for streaming and add icon
                $streaming = $data['streaming'];
                if ($streaming['is_public'] && !empty($streaming['streaming_url'])) {
                    echo '<span class="wfn-streaming-icon" title="Live streaming available">';
                    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">';
                    echo '<path d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm128-64l0 128c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-40 80 40 0-128-80 40 0-40c0-17.7-14.3-32-32-32l-128 0c-17.7 0-32 14.3-32 32z"/>';
                    echo '</svg>';
                    echo '</span>';
                }
                
                echo '</div>';
            }
            
            // View Details span removed - entire card is clickable for better accessibility
            echo '</div></a></article>';
        }

        echo '</div>';
    }

    /**
     * Render Enhancement Suite Elegant Grid (native WFN version)
     */
    private function render_enhancement_elegant_grid(\WP_Query $query, int $columns): void {
        // Ensure StylingModule assets are loaded for color schemes and typography
        $this->ensure_styling_module_assets();
        
        // Enqueue shared base styles first
        wp_enqueue_style('wfn-enhancement-base', plugin_dir_url(__FILE__) . '../../assets/css/layouts/shared-base.css', [], '2.0.1');
        // Enqueue Enhancement Suite elegant grid CSS
        wp_enqueue_style('wfn-enhancement-elegant', plugin_dir_url(__FILE__) . '../../assets/css/layouts/elegant-grid.css', ['wfn-enhancement-base'], '2.0.1');
        
        $grid_class = "wfn-enhancement-elegant-grid wfn-cols-{$columns}";
        echo "<div class=\"{$grid_class}\">";

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Use TemplateManager for unified data access
            $data = $this->template_manager->get_funeral_data($post_id);
            $person = $data['person'];
            $event = $data['event'];
            
            $full_name = $person['full_name'];
            $years_display = $person['years_display'];
            
            // Get funeral date and time
            $funeral_date = $event['funeral_date'];
            $funeral_time = $event['funeral_time'];
            
            // Get image
            $featured_image = get_the_post_thumbnail_url($post_id, 'medium');
            $fallback_url = $this->get_fallback_image_url();
            $image_url = $featured_image ?: $fallback_url;

            echo '<article class="wfn-enhancement-elegant-card">';
            echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="wfn-enhancement-elegant-link">';
            
            echo '<div class="wfn-enhancement-elegant-header">';
            
            // Check if we should hide date/time/venue
            $hide_details = $event['hide_time'] ?? false;
            
            // Show floating streaming icon when details are hidden but streaming is available
            $streaming = $data['streaming'];
            if ($hide_details && $streaming['is_public'] && !empty($streaming['streaming_url'])) {
                echo '<span class="wfn-streaming-icon-float" title="Live streaming available">';
                echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">';
                echo '<path d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm128-64l0 128c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-40 80 40 0-128-80 40 0-40c0-17.7-14.3-32-32-32l-128 0c-17.7 0-32 14.3-32 32z"/>';
                echo '</svg>';
                echo '</span>';
            }
            
            if ($image_url) {
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($full_name) . '" class="wfn-enhancement-elegant-portrait">';
            }
            echo '<div class="wfn-enhancement-elegant-details">';
            echo '<h3 class="wfn-enhancement-elegant-name">' . esc_html($full_name) . '</h3>';
            if ($years_display) {
                echo '<p class="wfn-enhancement-elegant-years">' . esc_html($years_display) . '</p>';
            }
            echo '</div></div>';
            
            if ($funeral_date && !$hide_details) {
                echo '<div class="wfn-enhancement-elegant-service">';
                echo '<strong>Service:</strong> ' . esc_html(date('j F Y', strtotime($funeral_date)));
                if ($funeral_time) {
                    $formatted_time = date('g:i A', strtotime($funeral_time));
                    echo ' at ' . esc_html($formatted_time);
                }
                echo '</div>';
            }
            
            echo '</a></article>';
        }

        echo '</div>';
    }

    /**
     * Render Enhancement Suite Minimal (native WFN version)
     */
    private function render_enhancement_minimal(\WP_Query $query, int $columns): void {
        // Ensure StylingModule assets are loaded for color schemes and typography
        $this->ensure_styling_module_assets();
        
        // Enqueue shared base styles first
        wp_enqueue_style('wfn-enhancement-base', plugin_dir_url(__FILE__) . '../../assets/css/layouts/shared-base.css', [], '2.0.0');
        // Enqueue Enhancement Suite minimal CSS
        wp_enqueue_style('wfn-enhancement-minimal', plugin_dir_url(__FILE__) . '../../assets/css/layouts/minimal.css', ['wfn-enhancement-base'], '2.0.0');
        
        $grid_class = "wfn-enhancement-minimal-grid wfn-cols-{$columns}";
        echo "<div class=\"{$grid_class}\">";

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Use TemplateManager for unified data access
            $data = $this->template_manager->get_funeral_data($post_id);
            $person = $data['person'];
            
            $full_name = $person['full_name'];
            $years_display = $person['years_display'];
            
            echo '<article class="wfn-enhancement-minimal-card">';
            echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="wfn-enhancement-minimal-link">';

            echo '<div class="wfn-enhancement-minimal-content">';
            echo '<h3 class="wfn-enhancement-minimal-name">' . esc_html($full_name) . '</h3>';

            echo '<div class="wfn-enhancement-minimal-details">';
            if ($years_display) {
                echo '<span class="wfn-enhancement-minimal-years">' . esc_html($years_display) . '</span>';
            }
            echo '</div>';
            
            echo '</div></a></article>';
        }

        echo '</div>';
    }

    /**
     * Get fallback image URL with robust error handling
     */
    private function get_fallback_image_url(): string {
        // Get fallback image from Settings module
        $settings = get_option('wfn_module_settings', []);
        $fallback_url = $settings['default_person_image'] ?? '';

        if (!empty($fallback_url)) {
            return $fallback_url;
        }

        // Default fallback to bundled image
        return plugin_dir_url(dirname(__FILE__, 2)) . 'assets/images/fallback.webp';
    }
} 