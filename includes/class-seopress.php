<?php
/**
* SEOPress functions to set meta tags
*
* @since 1.2.0
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

/**
* No index single funeral notices (if enabled in settings)
*
* @since 1.2.0
* @updated 2.2.12 - Made configurable via settings
*/
function sp_titles_robots($html) {
    // Get plugin settings
    $settings = get_option('wfn_module_settings', []);
    $single_slug = $settings['single_slug'] ?? 'funeral-notice';

    if (is_singular($single_slug)) {
        $noindex_enabled = $settings['noindex_funeral_notices'] ?? false;

        // Only add noindex if enabled in settings (default is to allow indexing)
        if ($noindex_enabled) {
            $html = '<meta name="robots" content="noindex"/>' . "\n";
        }
    }
    return $html;
}
add_filter('seopress_titles_robots', 'sp_titles_robots');

/**
 * Add title tag for Funeral Notice Archive
 *
 * @since 1.2.0
 */
   
function wfn_notice_archive_title($html) {
     if (is_post_type_archive('funeral-notice')) {
         // Get location name from Settings module
         $settings = get_option('wfn_module_settings', []);
         $funeral_location_name = $settings['location_name'] ?? '';
 
         // Define your custom title text - use site name as fallback
         $site_name = get_bloginfo('name');
         $custom_title = !empty($funeral_location_name) 
             ? esc_html($funeral_location_name) . ' Upcoming Funerals | Funeral Times & Livestreams'
             : esc_html($site_name) . ' Upcoming Funerals | Funeral Times & Livestreams';
 
         $html = $custom_title;
     }
     return $html;
 }
 add_filter('seopress_titles_title', 'wfn_notice_archive_title');

   
 /**
 *Add description for Funeral Notice Archive
 *
 * @since 1.2.0
 */
   
function sp_funeral_notice_archive_meta_description($html) {
     if (is_post_type_archive('funeral-notice')) {
         // Get location name from Settings module
         $settings = get_option('wfn_module_settings', []);
         $funeral_location_name = $settings['location_name'] ?? '';
 
         $today = date("Ymd");
 
         $args = [
             'post_type' => 'funeral-notice',
             'post_status' => 'publish',
             'posts_per_page' => -1,
             'orderby' => 'title',
             'order' => 'ASC',
             'meta_query' => [
                 [
                     'key' => 'wfn_details_group_funeral_date',
                     'value' => $today,
                     'type' => 'date_picker',
                     'compare' => '>='
                 ]
             ]
         ];
 
         $query = new WP_Query($args);
 
         $post_names = [];
         while ($query->have_posts()) {
             $query->the_post();
 
             $first_name = get_field('wfn_person_group_firstname');
             $last_name = get_field('wfn_person_group_lastname');
 
             // Check if first name and last name are not empty
             if (!empty($first_name) && !empty($last_name)) {
                 $post_names[] = $first_name . ' ' . $last_name;
             }
         }
 
         wp_reset_postdata();
 
         // Update the meta description - use site name as fallback
         $site_name = get_bloginfo('name');
         $custom_description = !empty($funeral_location_name)
             ? "Explore " . $funeral_location_name . " upcoming funeral notices and streaming information. Find out when and where our funerals are. You can leave a tribute or view a livestream. Upcoming funeral notices for: " . trim(implode(', ', $post_names))
             : "Explore " . $site_name . " upcoming funeral notices and streaming information. Find out when and where funerals are taking place. You can leave a tribute or view a livestream. Upcoming funeral notices for: " . trim(implode(', ', $post_names));
         $html = esc_attr($custom_description);
     }
 
     return $html;
 }
 
 add_filter('seopress_titles_desc', 'sp_funeral_notice_archive_meta_description');

 
 /**
  * Change OG date to today
  *
  * @since 1.2.0
  */
 function sp_titles_og_updated_time($html) {
     if (is_post_type_archive('funeral-notice')) {
         // Set 'og:updated_time' to today's date at 09:00
         $updated_time = date("Y-m-d", strtotime("today")) . "T09:00:00+00:00";
         $html = '<meta property="og:updated_time" content="' . esc_attr($updated_time) . '" />';
     }
     return $html;
 }
 add_filter('seopress_titles_og_updated_time', 'sp_titles_og_updated_time');

/**
 * Remove SEO Press meta box from Funeral Notices
 *
 * @since 1.2.0
 */
function remove_content_analysis_metabox($seopress_get_post_types) {
     global $post;
 
     // Check if the current post is of the 'funeral-notice' post type
     if ($post && $post->post_type === 'funeral-notice') {
         return false; // Remove the content analysis metabox
     }
 
     return $seopress_get_post_types;
 }
add_filter('seopress_metaboxe_content_analysis', 'remove_content_analysis_metabox');

/**
 * Remove SEOPress metaboxes from funeral-location taxonomy screens
 *
 * @since 2.0.0
 */
function wfn_remove_seopress_taxonomy_metaboxes() {
    // Remove SEOPress meta boxes from funeral-location taxonomy edit screens
    remove_meta_box('seopress_cpt', 'edit-funeral-location', 'normal');
    remove_meta_box('seopress_content_analysis', 'edit-funeral-location', 'normal');
    remove_meta_box('seopress_ca', 'edit-funeral-location', 'normal');
    
    // Also remove any other SEOPress metaboxes that might appear on taxonomy screens
    remove_meta_box('seopress_social', 'edit-funeral-location', 'normal');
    remove_meta_box('seopress_advanced', 'edit-funeral-location', 'normal');
    remove_meta_box('seopress_schemas', 'edit-funeral-location', 'normal');
}
add_action('add_meta_boxes', 'wfn_remove_seopress_taxonomy_metaboxes', 99);

/**
 * Hide SEOPress fields from funeral-location taxonomy using CSS
 * (Backup method in case metabox removal doesn't work)
 *
 * @since 2.0.0
 */
function wfn_hide_seopress_taxonomy_fields() {
    $screen = get_current_screen();
    
    // Only apply to funeral-location taxonomy screens
    if ($screen && ($screen->id === 'edit-funeral-location' || $screen->taxonomy === 'funeral-location')) {
        ?>
        <style type="text/css">
            /* Hide SEOPress taxonomy fields */
            .term-seopress-wrap,
            .term-seopress_titles_title-wrap,
            .term-seopress_titles_desc-wrap,
            .term-seopress_social_facebook_title-wrap,
            .term-seopress_social_facebook_desc-wrap,
            .term-seopress_social_twitter_title-wrap,
            .term-seopress_social_twitter_desc-wrap,
            .term-seopress_robots_index-wrap,
            .term-seopress_robots_follow-wrap,
            .term-seopress_robots_canonical-wrap {
                display: none !important;
            }
            
            /* Hide any SEOPress sections on taxonomy screens */
            .seopress-notice,
            .seopress-tabs,
            .seopress-analysis {
                display: none !important;
            }
        </style>
        <?php
    }
}
add_action('admin_head', 'wfn_hide_seopress_taxonomy_fields');

 /**
  * Change social share image for archive page
  *
  * @since 1.2.0
  */
// function wfn_social_og_thumb($html) {
//      if (is_post_type_archive('funeral-notice')) {
//          $html = '<meta property="og:image" content="https://www.seopress.org/wp-content/uploads/2016/12/cropped-ico-logo-seopress-256x256.png" />';
//      }
//      return $html;
//  }
//  add_filter('seopress_social_og_thumb', 'wfn_social_og_thumb');

 /**
* Remove funerals from XML sitemap
*
* @since 1.2.1
*/
add_filter('seopress_sitemaps_cpt', 'wfn_exclude_funeral_notice_from_sitemap');

function wfn_exclude_funeral_notice_from_sitemap($post_types) {
    // Check if 'funeral-notice' exists in the post types
    if (isset($post_types['funeral-notice'])) {
        // Remove 'funeral-notice' from the post types
        unset($post_types['funeral-notice']);
    }

    return $post_types;
}

/**
 * Remove SEOPress admin columns from funeral notice list table
 *
 * @since 2.2.11
 */
add_filter('manage_funeral-notice_posts_columns', 'wfn_remove_seopress_admin_columns', 999);

function wfn_remove_seopress_admin_columns($columns) {
    // Remove SEOPress title and meta description columns
    unset($columns['seopress_title']);
    unset($columns['seopress_desc']);
    unset($columns['seopress_target_kw']);
    unset($columns['seopress_score']);
    unset($columns['seopress_analysis']);
    unset($columns['seopress_noindex']);

    return $columns;
}

/**
 * Enhanced SEOPress integration for single funeral notice pages
 *
 * @since 2.2.14
 */

/**
 * Add custom title for single funeral notice pages
 */
add_filter('seopress_titles_title', 'wfn_funeral_notice_seo_title');

function wfn_funeral_notice_seo_title($title) {
    // Check if SEO features are enabled
    $settings = get_option('wfn_module_settings', []);
    if (empty($settings['enable_seo'])) {
        return $title;
    }

    $single_slug = $settings['single_slug'] ?? 'funeral-notice';

    if (is_singular($single_slug)) {
        global $post;

        // Get person details - handle both new group structure and old flat structure
        $person_group = get_field('wfn_person_group', $post->ID);

        if (!empty($person_group)) {
            // New group structure (post-migration)
            $first_name = $person_group['firstname'] ?? '';
            $last_name = $person_group['lastname'] ?? '';
        } else {
            // Fallback to old flat field structure (pre-migration)
            $first_name = get_field('wfn_person_group_firstname', $post->ID) ?? '';
            $last_name = get_field('wfn_person_group_lastname', $post->ID) ?? '';
        }

        if ($first_name && $last_name) {
            $full_name = trim($first_name . ' ' . $last_name);
            $title_suffix = $settings['seo_title_suffix'] ?? '';
            $site_name = get_bloginfo('name');

            // Build title: Firstname Lastname [Custom Text or 'Funeral Notice'] | Sitename
            $custom_text = !empty($title_suffix) ? $title_suffix : 'Funeral Notice';
            $title = $full_name . ' ' . $custom_text;

            // Add site name if available
            if ($site_name) {
                $title .= ' | ' . $site_name;
            }

        } else {
            // Fallback if no names found
            $site_name = get_bloginfo('name');
            if ($site_name) {
                $title = get_the_title($post->ID) . ' | ' . $site_name;
            } else {
                $title = get_the_title($post->ID);
            }
        }
    }

    return $title;
}

/**
 * Add custom meta description for single funeral notice pages
 */
add_filter('seopress_titles_desc', 'wfn_funeral_notice_seo_description');

function wfn_funeral_notice_seo_description($description) {
    // Check if SEO features are enabled
    $settings = get_option('wfn_module_settings', []);
    if (empty($settings['enable_seo'])) {
        return $description;
    }

    $single_slug = $settings['single_slug'] ?? 'funeral-notice';

    if (is_singular($single_slug)) {
        global $post;

        // Get person details - handle both new group structure and old flat structure
        $person_group = get_field('wfn_person_group', $post->ID);
        $details_group = get_field('wfn_details_group', $post->ID);
        $notice_group = get_field('wfn_notice_group', $post->ID);

        if (!empty($person_group)) {
            // New group structure (post-migration)
            $first_name = $person_group['firstname'] ?? '';
            $last_name = $person_group['lastname'] ?? '';
        } else {
            // Fallback to old flat field structure (pre-migration)
            $first_name = get_field('wfn_person_group_firstname', $post->ID) ?? '';
            $last_name = get_field('wfn_person_group_lastname', $post->ID) ?? '';
        }
        // Handle backward compatibility for all group fields
        if (!empty($details_group)) {
            $funeral_date = $details_group['funeral_date'] ?? '';
            $funeral_time = $details_group['funeral_time'] ?? '';
            $venue = $details_group['venue'] ?? '';
        } else {
            // Fallback to flat structure
            $funeral_date = get_field('wfn_details_group_funeral_date', $post->ID) ?? '';
            $funeral_time = get_field('wfn_details_group_funeral_time', $post->ID) ?? '';
            $venue = get_field('wfn_details_group_venue', $post->ID) ?? '';
        }

        if (!empty($notice_group)) {
            $tribute_text = $notice_group['tribute_text'] ?? '';
        } else {
            // Fallback to flat structure
            $tribute_text = get_field('wfn_notice_group_tribute_text', $post->ID) ?? '';
        }

        // If no tribute_text field, use post content (newspaper notice)
        if (empty($tribute_text)) {
            $tribute_text = $post->post_content;
        }
        $location_name = $settings['location_name'] ?? '';

        if ($first_name && $last_name) {
            $full_name = trim($first_name . ' ' . $last_name);

            // Start with tribute text if available (this is the main content)
            if ($tribute_text) {
                $clean_tribute = strip_tags($tribute_text);
                $clean_tribute = str_replace(['"', "\r", "\n"], ["'", ' ', ' '], $clean_tribute);
                $clean_tribute = preg_replace('/\s+/', ' ', trim($clean_tribute));

                // Get max length setting
                $max_length = (int) ($settings['meta_description_length'] ?? 160);

                // If tribute is too long, truncate it with service details space
                $service_suffix = '';
                if ($funeral_date || $venue) {
                    // Reserve space for service details (approximately 50 chars)
                    $reserve_space = 60;
                    $available_space = $max_length - $reserve_space;

                    if (strlen($clean_tribute) > $available_space) {
                        $clean_tribute = substr($clean_tribute, 0, $available_space - 3) . '...';
                    }

                    // Add service details
                    if ($funeral_date) {
                        $formatted_date = date('F j, Y', strtotime($funeral_date));
                        $service_suffix = " The service will be on {$formatted_date}";

                        if ($funeral_time) {
                            $formatted_time = date('g:i A', strtotime($funeral_time));
                            $service_suffix .= " at {$formatted_time}";
                        }

                        if ($venue) {
                            $service_suffix .= " at {$venue}";
                        }

                        $service_suffix .= '…';
                    }
                } else {
                    // No service details, use full space for tribute
                    if (strlen($clean_tribute) > $max_length - 3) {
                        $clean_tribute = substr($clean_tribute, 0, $max_length - 3) . '...';
                    }
                }

                $description = $clean_tribute . $service_suffix;
            } else {
                // Fallback if no tribute text - use service details
                $description_parts = [];
                $description_parts[] = "Funeral notice for {$full_name}";

                if ($funeral_date) {
                    $formatted_date = date('F j, Y', strtotime($funeral_date));
                    $date_part = "Service on {$formatted_date}";

                    if ($funeral_time) {
                        $formatted_time = date('g:i A', strtotime($funeral_time));
                        $date_part .= " at {$formatted_time}";
                    }

                    $description_parts[] = $date_part;
                }

                if ($venue) {
                    $description_parts[] = "held at {$venue}";
                }

                // Only add location if it exists
                if ($location_name) {
                    $description_parts[] = "From {$location_name}";
                }

                $description = implode('. ', $description_parts);

                // Ensure it doesn't exceed meta description length setting
                $max_length = (int) ($settings['meta_description_length'] ?? 160);
                if (strlen($description) > $max_length) {
                    $description = substr($description, 0, $max_length - 3) . '...';
                }
            }
        }
    }

    return $description;
}

/**
 * Add custom Open Graph image for funeral notices
 */
add_filter('seopress_social_og_thumb', 'wfn_funeral_notice_og_image');

function wfn_funeral_notice_og_image($image) {
    // Check if SEO features are enabled
    $settings = get_option('wfn_module_settings', []);
    if (empty($settings['enable_seo'])) {
        return $image;
    }

    $single_slug = $settings['single_slug'] ?? 'funeral-notice';

    if (is_singular($single_slug)) {
        global $post;

        // Check for custom social share image setting first
        $custom_social_image = $settings['social_share_image'] ?? '';
        if ($custom_social_image) {
            return '<meta property="og:image" content="' . esc_url($custom_social_image) . '" />';
        }

        // Fallback to plugin default image (never use featured image or person image)
        $default_image = plugin_dir_url(__FILE__) . '../assets/images/funeral-notice-social-share.jpg';
        return '<meta property="og:image" content="' . esc_url($default_image) . '" />';
    }

    return $image;
}

/**
 * Disable Twitter Card Integration for Funeral Notices
 *
 * @since 2.2.14
 * @updated 2.2.17 - Use output buffering to strip Twitter cards from HTML
 */

/**
 * Start output buffering to capture and filter wp_head output
 */
function wfn_start_head_buffer() {
    $settings = get_option('wfn_module_settings', []);
    $single_slug = $settings['single_slug'] ?? 'funeral-notice';

    if (is_singular($single_slug)) {
        ob_start('wfn_filter_twitter_cards_from_head');
    }
}
add_action('wp_head', 'wfn_start_head_buffer', 0);

/**
 * Filter Twitter card meta tags from the head output
 */
function wfn_filter_twitter_cards_from_head($html) {
    // Remove all Twitter card meta tags
    $html = preg_replace('/<meta\s+name="twitter:[^"]*"\s+content="[^"]*"\s*\/?>\s*/i', '', $html);
    return $html;
}

/**
 * End output buffering and output filtered content
 */
function wfn_end_head_buffer() {
    $settings = get_option('wfn_module_settings', []);
    $single_slug = $settings['single_slug'] ?? 'funeral-notice';

    if (is_singular($single_slug)) {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }
}
add_action('wp_head', 'wfn_end_head_buffer', PHP_INT_MAX);

