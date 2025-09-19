<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Admin;

/**
 * Admin Columns Manager
 * Custom admin columns for funeral-notice CPT
 * Replaces Admin Columns Pro dependency
 * 
 * @since 2.0.0
 */
class AdminColumns {

    /**
     * Initialize admin columns functionality
     */
    public function __construct() {
        add_filter('manage_funeral-notice_posts_columns', [$this, 'set_columns']);
        add_action('manage_funeral-notice_posts_custom_column', [$this, 'populate_columns'], 10, 2);
        add_filter('manage_edit-funeral-notice_sortable_columns', [$this, 'sortable_columns']);
        add_action('admin_head', [$this, 'add_column_styles']);
    }

    /**
     * Set custom columns for funeral notice admin
     */
    public function set_columns(array $columns): array {
        // Remove default title and date columns
        unset($columns['title'], $columns['date']);
        
        return [
            'cb' => $columns['cb'],
            'image' => 'Image',
            'first_name' => 'First Name',
            'last_name' => 'Last Name', 
            'funeral_date' => 'Funeral Date',
            'funeral_time' => 'Funeral Time',
            'location' => 'Location',
            'streaming' => 'Streaming',
            'service_sheets' => 'Service Sheets',
        ];
    }

    /**
     * Populate custom column content
     */
    public function populate_columns(string $column, int $post_id): void {
        switch ($column) {
            case 'image':
                $this->render_image_column($post_id);
                break;
                
            case 'first_name':
                $person_group = get_field('wfn_person_group', $post_id) ?: [];
                $first_name = $person_group['firstname'] ?? '';
                echo esc_html($first_name ?: '—');
                break;
                
            case 'last_name':
                $person_group = get_field('wfn_person_group', $post_id) ?: [];
                $last_name = $person_group['lastname'] ?? '';
                echo esc_html($last_name ?: '—');
                break;
                
            case 'funeral_date':
                $this->render_funeral_date_column($post_id);
                break;
                
            case 'funeral_time':
                $this->render_funeral_time_column($post_id);
                break;
                
            case 'location':
                $this->render_location_column($post_id);
                break;
                
            case 'streaming':
                $this->render_streaming_column($post_id);
                break;
                
            case 'service_sheets':
                $this->render_service_sheets_column($post_id);
                break;
        }
    }

    /**
     * Make certain columns sortable
     */
    public function sortable_columns(array $columns): array {
        $columns['first_name'] = 'first_name';
        $columns['last_name'] = 'last_name';
        $columns['funeral_date'] = 'funeral_date';
        return $columns;
    }

    /**
     * Render image column (150x150px)
     */
    private function render_image_column(int $post_id): void {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        
        if ($thumbnail_id) {
            $image = wp_get_attachment_image($thumbnail_id, [150, 150], false, [
                'style' => 'width: 150px; height: 150px; object-fit: cover; border-radius: 4px;'
            ]);
            echo $image;
        } else {
            // Show placeholder or default image
            echo '<div style="width: 150px; height: 150px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666; font-size: 12px;">No Image</div>';
        }
    }

    /**
     * Render funeral date column
     */
    private function render_funeral_date_column(int $post_id): void {
        $details_group = get_field('wfn_details_group', $post_id) ?: [];
        $date = $details_group['funeral_date'] ?? '';
        $hide_datetime = $details_group['hide_datetime'] ?? false;
        
        if ($hide_datetime) {
            echo '<span style="color: #999; font-style: italic;">Hidden</span>';
        } elseif ($date) {
            // Format as "Tues 27th May 2025"
            $day_of_week = date('D', strtotime($date));
            $day_with_suffix = date('jS', strtotime($date));
            $month_year = date('M Y', strtotime($date));
            $formatted_date = "{$day_of_week} {$day_with_suffix} {$month_year}";
            echo esc_html($formatted_date);
        } else {
            echo '—';
        }
    }

    /**
     * Render funeral time column
     */
    private function render_funeral_time_column(int $post_id): void {
        $details_group = get_field('wfn_details_group', $post_id) ?: [];
        $time = $details_group['funeral_time'] ?? '';
        $hide_datetime = $details_group['hide_datetime'] ?? false;
        
        if ($hide_datetime) {
            echo '<span style="color: #999; font-style: italic;">Hidden</span>';
        } elseif ($time) {
            $formatted_time = date('g:i A', strtotime($time));
            echo esc_html($formatted_time);
        } else {
            echo '—';
        }
    }

    /**
     * Render location column
     */
    private function render_location_column(int $post_id): void {
        // Location data is stored in wfn_location_group (separate from details group)
        $location_group = get_field('wfn_location_group', $post_id) ?: [];
        
        // Check if using custom location (old structure)
        $is_other_location_array = $location_group['is_at_another_location'] ?? [];
        $is_other_location = in_array('yes', $is_other_location_array);
        $other_address = $location_group['other_funeral_address'] ?? null;
        
        if ($is_other_location && $other_address) {
            // Custom location with Google Maps data
            $address_name = $other_address['name'] ?? '';
            $street_number = $other_address['street_number'] ?? '';
            $street_name = $other_address['street_name'] ?? '';
            $city = $other_address['city'] ?? '';
            $post_code = $other_address['post_code'] ?? '';
            
            if ($address_name) {
                echo esc_html($address_name);
            } elseif ($street_number || $street_name || $city) {
                $address_parts = array_filter([$street_number, $street_name, $city, $post_code]);
                echo esc_html(implode(', ', $address_parts));
            } else {
                echo '<span style="color: #d63638;">Custom (No Address)</span>';
            }
        } else {
            // Use taxonomy-based location
            $location_term_id = $location_group['location'] ?? null;
            
            if ($location_term_id) {
                $location_term = get_term($location_term_id, 'funeral-location');
                
                if ($location_term && !is_wp_error($location_term)) {
                    // Try to get the address from the taxonomy term
                    $location_address = get_field('location_address', 'funeral-location_' . $location_term_id);
                    
                    if ($location_address) {
                        // Clean up the address for compact display
                        $address_clean = str_replace(['<br>', '<br/>', '<br />'], ', ', $location_address);
                        $address_clean = strip_tags($address_clean);
                        echo esc_html($address_clean);
                    } else {
                        // Fallback to just the location name
                        echo esc_html($location_term->name);
                    }
                } else {
                    echo '<span style="color: #d63638;">Invalid Location</span>';
                }
            } else {
                echo '—';
            }
        }
    }

    /**
     * Render streaming column
     */
    private function render_streaming_column(int $post_id): void {
        $streaming_group = get_field('wfn_streaming_group', $post_id) ?: [];
        $streaming_url = $streaming_group['streaming_url'] ?? '';
        $is_private = $streaming_group['streaming_private'] ?? false;
        
        if (empty($streaming_url)) {
            echo '—';
            return;
        }
        
        // Detect service type from URL
        $service_name = $this->detect_streaming_service_from_url($streaming_url);
        
        // Show service name with privacy indicator
        $privacy_indicator = $is_private ? ' (Private)' : '';
        $color = $is_private ? '#d4af37' : '#0073aa';
        
        echo '<span style="color: ' . $color . ';">' . esc_html($service_name . $privacy_indicator) . '</span>';
    }

    /**
     * Render service sheets column
     */
    private function render_service_sheets_column(int $post_id): void {
        $media_group = get_field('wfn_media_group', $post_id) ?: [];
        $service_sheet = $media_group['service_sheet'] ?? null;
        $additional_docs = $media_group['additional_documents'] ?? [];
        
        $doc_count = 0;
        
        if ($service_sheet) {
            $doc_count++;
        }
        
        if ($additional_docs && is_array($additional_docs)) {
            $doc_count += count($additional_docs);
        }
        
        if ($doc_count > 0) {
            echo '<span style="color: #0073aa;">' . $doc_count . ' file' . ($doc_count > 1 ? 's' : '') . '</span>';
        } else {
            echo '—';
        }
    }

    /**
     * Detect streaming service from URL
     */
    private function detect_streaming_service_from_url(string $url): string {
        if (strpos($url, 'view.oneroomstreaming.com') !== false) {
            return 'OneRoom';
        } elseif (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return 'YouTube';
        } elseif (strpos($url, 'vimeo.com') !== false) {
            return 'Vimeo';
        } elseif (strpos($url, 'facebook.com') !== false) {
            return 'Facebook Live';
        } elseif (strpos($url, 'twitch.tv') !== false) {
            return 'Twitch';
        } else {
            $domain = parse_url($url, PHP_URL_HOST);
            $clean_domain = str_replace('www.', '', $domain ?? '');
            return ucfirst($clean_domain) ?: 'Custom URL';
        }
    }

    /**
     * Add custom CSS for admin columns
     */
    public function add_column_styles(): void {
        $screen = get_current_screen();
        
        if ($screen && $screen->post_type === 'funeral-notice' && $screen->base === 'edit') {
            echo '<style>
                .wp-list-table .column-image { width: 170px; }
                .wp-list-table .column-first_name { width: 120px; }
                .wp-list-table .column-last_name { width: 120px; }
                .wp-list-table .column-funeral_date { width: 110px; }
                .wp-list-table .column-funeral_time { width: 100px; }
                .wp-list-table .column-location { width: 200px; }
                .wp-list-table .column-streaming { width: 120px; }
                .wp-list-table .column-service_sheets { width: 100px; }
                
                .wp-list-table .column-image img,
                .wp-list-table .column-image div {
                    margin: 5px 0;
                }
                
                .wp-list-table .column-location {
                    font-size: 13px;
                    line-height: 1.4;
                }
            </style>';
        }
    }
} 