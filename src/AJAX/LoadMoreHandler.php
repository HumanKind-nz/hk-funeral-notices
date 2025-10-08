<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\AJAX;

/**
 * Load More Handler
 *
 * Handles AJAX requests for loading additional funeral notices
 * with "Load More" button functionality.
 *
 * @since 2.4.0
 */
class LoadMoreHandler {

    /**
     * Constructor - Register AJAX hooks
     */
    public function __construct() {
        add_action('wp_ajax_wfn_load_more', [$this, 'handle_load_more']);
        add_action('wp_ajax_nopriv_wfn_load_more', [$this, 'handle_load_more']);
    }

    /**
     * Handle AJAX load more request
     */
    public function handle_load_more(): void {
        // Verify nonce
        check_ajax_referer('wfn_load_more_nonce', 'nonce');

        // Get parameters from POST
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        $posts_per_load = isset($_POST['posts_per_load']) ? absint($_POST['posts_per_load']) : 8;
        $layout = isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : 'modern';

        // Get filter parameters
        $filters = isset($_POST['filters']) ? $this->sanitize_filters($_POST['filters']) : [];

        // Build query arguments
        $args = [
            'post_type' => 'funeral-notice',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_load,
            'offset' => $offset,
            'meta_key' => 'wfn_details_group_funeral_date',
            'meta_type' => 'DATE',
            'orderby' => [
                'meta_value' => 'DESC', // furthest dates first
                'date'       => 'DESC', // for notices without dates, newest published first
            ],
        ];

        // Apply filters if present
        $meta_query = [];

        // Handle type filter (all, future, archived, today, this_week, this_month)
        if (!empty($filters['type'])) {
            $today = date('Y-m-d');
            $type = $filters['type'];

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
        }

        // Handle search term
        if (!empty($filters['search_term'])) {
            $args['s'] = $filters['search_term'];

            // Add meta query for ACF fields (firstname, lastname)
            $search_meta_query = [
                'relation' => 'OR',
                [
                    'key' => 'wfn_person_group_firstname',
                    'value' => $filters['search_term'],
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'wfn_person_group_lastname',
                    'value' => $filters['search_term'],
                    'compare' => 'LIKE'
                ]
            ];

            if (!empty($meta_query)) {
                $meta_query = [
                    'relation' => 'AND',
                    $meta_query,
                    $search_meta_query
                ];
            } else {
                $meta_query = $search_meta_query;
            }
        }

        // Handle location filter (searches both taxonomy and custom address fields)
        if (!empty($filters['location'])) {
            // Get posts with matching taxonomy locations
            $tax_posts = [];
            $location_terms = get_terms([
                'taxonomy' => 'funeral-location',
                'name__like' => $filters['location'],
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
                        'value' => $filters['location'],
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'wfn_details_group_custom_address_name',
                        'value' => $filters['location'],
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'wfn_details_group_custom_address_street_name',
                        'value' => $filters['location'],
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'wfn_details_group_custom_address_city',
                        'value' => $filters['location'],
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

        // Handle date range filters
        if (!empty($filters['date_from'])) {
            $meta_query[] = [
                'key'     => 'wfn_details_group_funeral_date',
                'value'   => $filters['date_from'],
                'compare' => '>=',
                'type'    => 'DATE',
            ];
        }

        if (!empty($filters['date_to'])) {
            $meta_query[] = [
                'key'     => 'wfn_details_group_funeral_date',
                'value'   => $filters['date_to'],
                'compare' => '<=',
                'type'    => 'DATE',
            ];
        }

        // Apply meta query if we have conditions
        if (!empty($meta_query)) {
            if (count($meta_query) > 1 && !isset($meta_query['relation'])) {
                $meta_query['relation'] = 'AND';
            }
            $args['meta_query'] = $meta_query;
        }

        // Execute query
        $query = new \WP_Query($args);

        // Check if we have more posts
        $total_found = $query->found_posts;
        $loaded_so_far = $offset + $posts_per_load;
        $has_more = $loaded_so_far < $total_found;

        // Render posts HTML
        $html = '';
        if ($query->have_posts()) {
            ob_start();

            while ($query->have_posts()) {
                $query->the_post();

                // Render based on layout template
                $this->render_funeral_card($layout);
            }

            $html = ob_get_clean();
            wp_reset_postdata();
        }

        // Send JSON response
        wp_send_json_success([
            'html' => $html,
            'has_more' => $has_more,
            'total_found' => $total_found,
            'loaded' => $loaded_so_far,
            'offset' => $loaded_so_far,
        ]);
    }

    /**
     * Sanitize filter parameters
     */
    private function sanitize_filters($filters): array {
        // Handle both string (from archive) and array (from shortcode)
        if (is_string($filters)) {
            $filters = json_decode(stripslashes($filters), true) ?: [];
        }

        $sanitized = [];

        if (isset($filters['type'])) {
            $sanitized['type'] = sanitize_text_field($filters['type']);
        }

        if (isset($filters['search_term'])) {
            $sanitized['search_term'] = sanitize_text_field($filters['search_term']);
        }

        if (isset($filters['location'])) {
            $sanitized['location'] = sanitize_text_field($filters['location']);
        }

        if (isset($filters['date_from'])) {
            $sanitized['date_from'] = sanitize_text_field($filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $sanitized['date_to'] = sanitize_text_field($filters['date_to']);
        }

        return $sanitized;
    }

    /**
     * Render funeral notice card based on layout template
     */
    private function render_funeral_card(string $layout): void {
        $post_id = get_the_ID();

        // Map layout names to template files
        $template_map = [
            'modern' => 'layouts/modern-grid-card.php',
            'elegant' => 'layouts/elegant-grid-card.php',
            'minimal' => 'layouts/minimal-card.php',
            'firehawk' => 'layouts/firehawk-card.php',
            'current' => 'layouts/current-card.php',
        ];

        $template_file = $template_map[$layout] ?? $template_map['modern'];
        $template_path = WFN_PLUGIN_DIR . 'templates/' . $template_file;

        // Check if template exists
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback: output basic card HTML
            $this->render_fallback_card($post_id);
        }
    }

    /**
     * Render fallback card if template not found
     */
    private function render_fallback_card(int $post_id): void {
        $person_group = get_field('wfn_person_group', $post_id);
        $details_group = get_field('wfn_details_group', $post_id);

        $firstname = $person_group['firstname'] ?? '';
        $lastname = $person_group['lastname'] ?? '';
        $full_name = trim($firstname . ' ' . $lastname);

        $funeral_date = $details_group['funeral_date'] ?? '';

        ?>
        <div class="wfn-funeral-card">
            <h3><?php echo esc_html($full_name); ?></h3>
            <?php if ($funeral_date): ?>
                <p class="funeral-date"><?php echo esc_html(date('F j, Y', strtotime($funeral_date))); ?></p>
            <?php endif; ?>
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="wfn-read-more">View Tribute</a>
        </div>
        <?php
    }
}
