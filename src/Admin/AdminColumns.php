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

        // Prevent WordPress from adding automatic row actions to our custom columns
        add_filter('post_row_actions', [$this, 'remove_default_row_actions'], 10, 2);
    }

    /**
     * Set custom columns for funeral notice admin
     */
    public function set_columns(array $columns): array {
        // Remove SEOPress columns if they exist
        $seopress_columns = [
            'seopress_noindex',
            'seopress_nofollow',
            'seopress_title',
            'seopress_desc'
        ];

        foreach ($seopress_columns as $seopress_column) {
            if (isset($columns[$seopress_column])) {
                unset($columns[$seopress_column]);
            }
        }

        // Keep checkbox for bulk actions
        $new_columns = ['cb' => $columns['cb']];

        // Add our custom columns first
        $new_columns['image'] = 'Image';
        $new_columns['first_name'] = 'First Name';
        $new_columns['last_name'] = 'Last Name';
        $new_columns['funeral_date'] = 'Funeral Date';
        $new_columns['funeral_time'] = 'Funeral Time';
        $new_columns['location'] = 'Location';
        $new_columns['streaming'] = 'Streaming';
        $new_columns['service_sheets'] = 'Service Sheets';

        // Only show slideshow column if license is valid
        if ($this->has_video_license()) {
            $new_columns['slideshow'] = 'Slideshow';
        }

        // Keep the date column
        $new_columns['date'] = 'Published';

        return $new_columns;
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
                $first_name = $this->get_person_field($post_id, 'firstname');
                $display_name = esc_html($first_name ?: '—');
                
                // Add row actions to first name column
                $row_actions = $this->get_row_actions($post_id);
                if (!empty($row_actions)) {
                    $display_name .= '<div class="row-actions">' . implode(' | ', $row_actions) . '</div>';
                }
                
                echo $display_name;
                break;
                
            case 'last_name':
                $last_name = $this->get_person_field($post_id, 'lastname');
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

            case 'slideshow':
                $this->render_slideshow_column($post_id);
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
     * Render image column (100x100px)
     */
    private function render_image_column(int $post_id): void {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        
        if ($thumbnail_id) {
            $image = wp_get_attachment_image($thumbnail_id, [100, 100], false, [
                'style' => 'width: 100px; height: 100px; object-fit: cover; border-radius: 4px;'
            ]);
            echo $image;
        } else {
            // Show placeholder or default image
            echo '<div style="width: 100px; height: 100px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666; font-size: 12px;">No Image</div>';
        }
    }

    /**
     * Render funeral date column
     */
    private function render_funeral_date_column(int $post_id): void {
        $date = $this->get_event_field($post_id, 'funeral_date');
        $hide_datetime = $this->get_event_field($post_id, 'hide_datetime');
        
        if ($hide_datetime) {
            echo '<span style="color: #d9534f; font-style: italic;" title="Date, time, and venue are hidden from public view">🔒 Hidden</span>';
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
        $time = $this->get_event_field($post_id, 'funeral_time');
        $hide_datetime = $this->get_event_field($post_id, 'hide_datetime');
        
        if ($hide_datetime) {
            echo '<span style="color: #d9534f; font-style: italic;" title="Date, time, and venue are hidden from public view">🔒 Hidden</span>';
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
        // Prefer new structure in wfn_details_group
        $details_group = get_field('wfn_details_group', $post_id) ?: [];
        $location_type = $details_group['location_type'] ?? null;
        
        // Check if date/time/venue is hidden
        $hide_datetime = $details_group['hide_datetime'] ?? false;
        
        if ($hide_datetime) {
            echo '<span style="color: #d9534f; font-style: italic;" title="Date, time, and venue are hidden from public view">🔒 Hidden</span>';
            return;
        }

        if ($location_type === 'custom') {
            // New custom address
            $custom_address = $details_group['custom_address'] ?? [];
            $afm = new \WeaveStudios\FuneralNotices\Address\AddressFieldManager();
            $components = $afm->get_address_components($custom_address);
            $name = $components['venue_name'] ?? '';
            $formatted = $afm->get_formatted_address($custom_address);
            $display = $name ?: $formatted;
            echo esc_html($display ?: '—');
            return;
        }

        if ($location_type === 'existing') {
            // New taxonomy-based location
            $term = $details_group['location'] ?? null;
            $term_id = null;
            if ($term instanceof \WP_Term) {
                $term_id = $term->term_id;
            } elseif (!empty($term)) {
                $term_id = is_numeric($term) ? (int) $term : null;
            }

            if ($term_id) {
                $location_term = get_term($term_id, 'funeral-location');
                if ($location_term && !is_wp_error($location_term)) {
                    $location_address = get_field('location_address', 'funeral-location_' . $term_id);
                    if ($location_address) {
                        $address_clean = str_replace(['<br>', '<br/>', '<br />'], ', ', $location_address);
                        $address_clean = strip_tags($address_clean);
                        echo esc_html($address_clean);
                    } else {
                        echo esc_html($location_term->name);
                    }
                    return;
                }
            }
        }

        // Fallback to legacy structure in wfn_location_group
        $location_group = get_field('wfn_location_group', $post_id) ?: [];
        $is_other_location_array = $location_group['is_at_another_location'] ?? [];
        $is_other_location = in_array('yes', $is_other_location_array);
        $other_address = $location_group['other_funeral_address'] ?? null;

        if ($is_other_location && $other_address) {
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
                echo '<span style=\"color: #d63638;\">Custom (No Address)</span>';
            }
            return;
        }

        $location_term_id = $location_group['location'] ?? null;
        if ($location_term_id) {
            $location_term = get_term($location_term_id, 'funeral-location');
            if ($location_term && !is_wp_error($location_term)) {
                $location_address = get_field('location_address', 'funeral-location_' . $location_term_id);
                if ($location_address) {
                    $address_clean = str_replace(['<br>', '<br/>', '<br />'], ', ', $location_address);
                    $address_clean = strip_tags($address_clean);
                    echo esc_html($address_clean);
                } else {
                    echo esc_html($location_term->name);
                }
            } else {
                echo '<span style=\"color: #d63638;\">Invalid Location</span>';
            }
            return;
        }

        echo '—';
    }

    /**
     * Render streaming column
     */
    private function render_streaming_column(int $post_id): void {
        $streaming_url = $this->get_streaming_field($post_id, 'streaming_url');
        $is_private = $this->get_streaming_field($post_id, 'streaming_private');
        
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
        $service_sheet = $this->get_media_field($post_id, 'service_sheet');
        $additional_docs = $this->get_media_field($post_id, 'additional_documents');
        
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
     * Render slideshow column
     */
    private function render_slideshow_column(int $post_id): void {
        $video_status = get_post_meta($post_id, '_wfn_video_status', true);
        $video_data = get_post_meta($post_id, '_wfn_video_data', true);
        $video_id = get_post_meta($post_id, '_wfn_video_id', true);
        $upload_status = get_post_meta($post_id, '_wfn_video_upload_status', true);

        // Show ready if status is ready AND (data exists OR video ID exists for reconstruction)
        if ($video_status === 'ready' && ($video_data || $video_id)) {
            echo '<span style="color: #0073aa; font-weight: bold;" title="Memorial slideshow ready for viewing">🎥 Ready</span>';
        } elseif ($upload_status && isset($upload_status['status'])) {
            $status = $upload_status['status'];
            $progress = $upload_status['progress'] ?? 0;

            switch ($status) {
                case 'processing':
                case 'uploading':
                case 'queued':
                    echo '<span style="color: #f79e05;" title="Video processing: ' . $progress . '%">🔄 Processing</span>';
                    break;
                case 'failed':
                case 'retrying':
                    echo '<span style="color: #dc3545;" title="Upload failed - check diagnostics">❌ Failed</span>';
                    break;
                default:
                    echo '<span style="color: #6c757d;" title="Video upload in progress">⏳ Uploading</span>';
                    break;
            }
        } else {
            // Check if video field has content
            $media_group = get_field('wfn_media_group', $post_id);
            $video_field = $media_group['video_slideshow'] ?? null;

            if ($video_field) {
                echo '<span style="color: #6c757d;" title="Video uploaded, waiting for processing">⏳ Pending</span>';
            } else {
                echo '—';
            }
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
                .wp-list-table .column-image { width: 120px; }
                .wp-list-table .column-first_name { width: 100px; }
                .wp-list-table .column-last_name { width: 100px; }
                .wp-list-table .column-funeral_date { width: 110px; }
                .wp-list-table .column-funeral_time { width: 90px; }
                .wp-list-table .column-location { width: 180px; }
                .wp-list-table .column-streaming { width: 100px; }
                .wp-list-table .column-service_sheets { width: 80px; }
                .wp-list-table .column-slideshow { width: 90px; }
                .wp-list-table .column-date { width: 90px; }

                .wp-list-table .column-image img,
                .wp-list-table .column-image div {
                    margin: 5px 0;
                    width: 100px !important;
                    height: 100px !important;
                }
                
                .wp-list-table .column-location {
                    font-size: 13px;
                    line-height: 1.4;
                }
            </style>';
        }
    }
    
    /**
     * Get person field with backward compatibility
     * Supports both new (wfn_person_group) and legacy (wfn_person_group_*) formats
     */
    private function get_person_field(int $post_id, string $field_name): string {
        // Method 1: Try new FieldGroupManager group structure
        $person_group = get_field('wfn_person_group', $post_id);
        if (is_array($person_group) && !empty($person_group[$field_name])) {
            return trim($person_group[$field_name]);
        }
        
        // Method 2: Fallback to legacy individual field format
        $legacy_field_name = 'wfn_person_group_' . $field_name;
        $legacy_value = get_field($legacy_field_name, $post_id);
        if (!empty($legacy_value)) {
            return trim($legacy_value);
        }
        
        // Method 3: Final fallback to direct field names (if they exist)
        $direct_value = get_field($field_name, $post_id);
        if (!empty($direct_value)) {
            return trim($direct_value);
        }
        
        return '';
    }
    
    /**
     * Get event detail field with backward compatibility
     */
    private function get_event_field(int $post_id, string $field_name) {
        // Method 1: Try new FieldGroupManager group structure
        $details_group = get_field('wfn_details_group', $post_id);
        if (is_array($details_group) && isset($details_group[$field_name])) {
            return $details_group[$field_name];
        }
        
        // Method 2: Fallback to legacy individual field format
        $legacy_field_name = 'wfn_details_group_' . $field_name;
        $legacy_value = get_field($legacy_field_name, $post_id);
        if ($legacy_value !== false && $legacy_value !== null && $legacy_value !== '') {
            return $legacy_value;
        }
        
        // Method 3: Final fallback to direct field names
        $direct_value = get_field($field_name, $post_id);
        if ($direct_value !== false && $direct_value !== null && $direct_value !== '') {
            return $direct_value;
        }
        
        return null;
    }
    
    /**
     * Get streaming field with backward compatibility
     */
    private function get_streaming_field(int $post_id, string $field_name) {
        // Method 1: Try new FieldGroupManager group structure
        $streaming_group = get_field('wfn_streaming_group', $post_id);
        if (is_array($streaming_group) && isset($streaming_group[$field_name])) {
            return $streaming_group[$field_name];
        }
        
        // Method 2: Fallback to legacy individual field format
        $legacy_field_name = 'wfn_streaming_group_' . $field_name;
        $legacy_value = get_field($legacy_field_name, $post_id);
        if ($legacy_value !== false && $legacy_value !== null && $legacy_value !== '') {
            return $legacy_value;
        }
        
        // Method 3: Final fallback to direct field names
        $direct_value = get_field($field_name, $post_id);
        if ($direct_value !== false && $direct_value !== null && $direct_value !== '') {
            return $direct_value;
        }
        
        return null;
    }
    
    /**
     * Get media field with backward compatibility
     */
    private function get_media_field(int $post_id, string $field_name) {
        // Method 1: Try new FieldGroupManager group structure
        $media_group = get_field('wfn_media_group', $post_id);
        if (is_array($media_group) && isset($media_group[$field_name])) {
            return $media_group[$field_name];
        }
        
        // Method 2: Fallback to legacy individual field format
        $legacy_field_name = 'wfn_media_group_' . $field_name;
        $legacy_value = get_field($legacy_field_name, $post_id);
        if ($legacy_value !== false && $legacy_value !== null && $legacy_value !== '') {
            return $legacy_value;
        }
        
        // Method 3: Final fallback to direct field names
        $direct_value = get_field($field_name, $post_id);
        if ($direct_value !== false && $direct_value !== null && $direct_value !== '') {
            return $direct_value;
        }
        
        return null;
    }

    /**
     * Check if video license is valid
     */
    private function has_video_license(): bool {
        if (class_exists('WeaveStudios\FuneralNotices\Services\LicenseService')) {
            return \WeaveStudios\FuneralNotices\Services\LicenseService::hasValidVideoLicense();
        }
        return false;
    }

    /**
     * Generate row actions for a post
     */
    private function get_row_actions(int $post_id): array {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        $actions = [];
        $post_type_object = get_post_type_object($post->post_type);

        // Edit link
        if (current_user_can('edit_post', $post_id)) {
            $actions['edit'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                get_edit_post_link($post_id),
                esc_attr(sprintf(__('Edit "%s"'), $post->post_title)),
                __('Edit')
            );
        }

        // Quick Edit link
        if (current_user_can('edit_post', $post_id)) {
            $actions['inline hide-if-no-js'] = sprintf(
                '<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false">%s</button>',
                esc_attr(sprintf(__('Quick edit "%s" inline'), $post->post_title)),
                __('Quick&nbsp;Edit')
            );
        }

        // Trash/Delete link
        if (current_user_can('delete_post', $post_id)) {
            if (EMPTY_TRASH_DAYS) {
                $actions['trash'] = sprintf(
                    '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                    get_delete_post_link($post_id),
                    esc_attr(sprintf(__('Move "%s" to the Trash'), $post->post_title)),
                    __('Trash')
                );
            } else {
                $actions['delete'] = sprintf(
                    '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                    get_delete_post_link($post_id, '', true),
                    esc_attr(sprintf(__('Delete "%s" permanently'), $post->post_title)),
                    __('Delete Permanently')
                );
            }
        }

        // View link
        if ($post_type_object->public) {
            if (in_array($post->post_status, ['pending', 'draft', 'future'])) {
                $preview_link = get_preview_post_link($post);
                $actions['view'] = sprintf(
                    '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
                    esc_url($preview_link),
                    esc_attr(sprintf(__('Preview "%s"'), $post->post_title)),
                    __('Preview')
                );
            } elseif ('trash' !== $post->post_status) {
                $actions['view'] = sprintf(
                    '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
                    get_permalink($post_id),
                    esc_attr(sprintf(__('View "%s"'), $post->post_title)),
                    __('View')
                );
            }
        }

        return $actions;
    }

    /**
     * Remove WordPress default row actions for funeral notices
     *
     * We handle row actions manually in the first_name column to prevent
     * them from appearing in multiple columns.
     *
     * @param array $actions Row actions
     * @param WP_Post $post Post object
     * @return array
     */
    public function remove_default_row_actions($actions, $post) {
        if ($post->post_type === 'funeral-notice') {
            // Remove all default row actions since we handle them manually
            return array();
        }

        return $actions;
    }
}
