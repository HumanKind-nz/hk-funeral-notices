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
* No index single funeral notices
*
* @since 1.2.0
*/
function sp_titles_robots($html) {
       if (is_singular('funeral-notice')) {
           $html = '<meta name="robots" content="noindex"/>' . "\n";
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
