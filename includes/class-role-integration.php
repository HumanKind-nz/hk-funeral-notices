<?php
/**
 * Simplified Role Integration for HK Funeral Notices
 *
 * Provides basic capability grants for funeral_staff and funeral_manager roles
 * if they exist (from HK Funeral Suite plugin or legacy installations).
 *
 * Uses standard WordPress 'post' capabilities for maximum compatibility.
 *
 * @package HK_Funeral_Notices
 * @since 2.4.2
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class HKFN_Role_Integration {

    /**
     * Initialize the simplified role integration
     */
    public static function init() {
        // Setup capability grants on init
        add_action('init', array(__CLASS__, 'grant_funeral_notice_capabilities'), 20);

        // Add View link to admin bar for single funeral notice posts
        add_action('admin_bar_menu', array(__CLASS__, 'add_view_link_to_admin_bar'), 100);

        // Add View Funeral Notice button beside page title (next to "+ New Funeral Notice")
        add_action('admin_title', array(__CLASS__, 'add_view_button_in_header'), 10, 2);
    }

    /**
     * Grant standard WordPress capabilities to funeral roles if they exist
     *
     * This ensures backward compatibility with existing sites using funeral_staff
     * and funeral_manager roles while using standard WordPress capabilities.
     */
    public static function grant_funeral_notice_capabilities() {
        // Grant capabilities to funeral_staff if it exists
        $staff_role = get_role('funeral_staff');
        if ($staff_role) {
            // funeral_staff gets editor-level capabilities for funeral notices
            $staff_role->add_cap('edit_posts');
            $staff_role->add_cap('edit_others_posts');
            $staff_role->add_cap('edit_published_posts');
            $staff_role->add_cap('delete_posts');
            $staff_role->add_cap('delete_others_posts');
            $staff_role->add_cap('delete_published_posts');
            $staff_role->add_cap('publish_posts');
            $staff_role->add_cap('read');
            $staff_role->add_cap('upload_files');

            // Grant funeral location taxonomy capabilities
            $staff_role->add_cap('manage_funeral-location');
            $staff_role->add_cap('edit_funeral-location');
            $staff_role->add_cap('delete_funeral-location');
            $staff_role->add_cap('assign_funeral-location');
        }

        // Grant capabilities to funeral_manager if it exists
        $manager_role = get_role('funeral_manager');
        if ($manager_role) {
            // funeral_manager gets full editor-level capabilities
            $manager_role->add_cap('edit_posts');
            $manager_role->add_cap('edit_others_posts');
            $manager_role->add_cap('edit_published_posts');
            $manager_role->add_cap('edit_private_posts');
            $manager_role->add_cap('delete_posts');
            $manager_role->add_cap('delete_others_posts');
            $manager_role->add_cap('delete_published_posts');
            $manager_role->add_cap('delete_private_posts');
            $manager_role->add_cap('publish_posts');
            $manager_role->add_cap('read');
            $manager_role->add_cap('read_private_posts');
            $manager_role->add_cap('upload_files');

            // Grant funeral location taxonomy capabilities
            $manager_role->add_cap('manage_funeral-location');
            $manager_role->add_cap('edit_funeral-location');
            $manager_role->add_cap('delete_funeral-location');
            $manager_role->add_cap('assign_funeral-location');
        }

        // Grant capabilities to custom 'funeral' role if it exists
        $funeral_role = get_role('funeral');
        if ($funeral_role) {
            // funeral role gets editor-level capabilities for funeral notices
            $funeral_role->add_cap('edit_posts');
            $funeral_role->add_cap('edit_others_posts');
            $funeral_role->add_cap('edit_published_posts');
            $funeral_role->add_cap('delete_posts');
            $funeral_role->add_cap('delete_others_posts');
            $funeral_role->add_cap('delete_published_posts');
            $funeral_role->add_cap('publish_posts');
            $funeral_role->add_cap('read');
            $funeral_role->add_cap('upload_files');

            // Grant funeral location taxonomy capabilities
            $funeral_role->add_cap('manage_funeral-location');
            $funeral_role->add_cap('edit_funeral-location');
            $funeral_role->add_cap('delete_funeral-location');
            $funeral_role->add_cap('assign_funeral-location');
        }

        // Grant taxonomy capabilities to standard 'editor' role
        $editor_role = get_role('editor');
        if ($editor_role) {
            // Grant funeral location taxonomy capabilities
            $editor_role->add_cap('manage_funeral-location');
            $editor_role->add_cap('edit_funeral-location');
            $editor_role->add_cap('delete_funeral-location');
            $editor_role->add_cap('assign_funeral-location');
        }
    }

    /**
     * Add View link to admin bar for single funeral notice posts
     *
     * Adds both "Edit Funeral Notice" and "View Funeral Notice" links when viewing
     * a funeral notice on the frontend.
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public static function add_view_link_to_admin_bar($wp_admin_bar) {
        // Only show on single funeral notice posts
        if (!is_singular('funeral-notice')) {
            return;
        }

        global $post;
        if (!$post || $post->post_type !== 'funeral-notice') {
            return;
        }

        // Add Edit link
        if (current_user_can('edit_post', $post->ID)) {
            $edit_link = get_edit_post_link($post->ID);
            if ($edit_link) {
                $wp_admin_bar->add_node(array(
                    'id'    => 'edit-funeral-notice',
                    'title' => __('Edit Funeral Notice', 'hk-funeral-notices'),
                    'href'  => $edit_link,
                    'meta'  => array(
                        'title' => sprintf(__('Edit "%s"', 'hk-funeral-notices'), $post->post_title)
                    )
                ));
            }
        }

        // Add View link (always available if post is published)
        if (in_array($post->post_status, ['publish', 'private'])) {
            $view_link = get_permalink($post->ID);
            $wp_admin_bar->add_node(array(
                'id'    => 'view-funeral-notice',
                'title' => __('View Funeral Notice', 'hk-funeral-notices'),
                'href'  => $view_link,
                'meta'  => array(
                    'title' => sprintf(__('View "%s"', 'hk-funeral-notices'), $post->post_title)
                )
            ));
        }
    }

    /**
     * Add View Funeral Notice button beside page title (next to "+ New Funeral Notice")
     *
     * Uses JavaScript to inject the button beside the page title for a cleaner layout.
     *
     * @param string $admin_title The page title
     * @param string $title The title tag content
     * @return string
     */
    public static function add_view_button_in_header($admin_title, $title) {
        $screen = get_current_screen();

        // Only on funeral notice edit screen
        if (!$screen || $screen->post_type !== 'funeral-notice' || $screen->base !== 'post') {
            return $admin_title;
        }

        global $post;
        if (!$post || $post->post_type !== 'funeral-notice') {
            return $admin_title;
        }

        // Get the appropriate link based on post status
        if (in_array($post->post_status, ['publish', 'private'])) {
            $view_url = get_permalink($post->ID);
            $button_text = __('View Funeral Notice', 'hk-funeral-notices');
            $icon = 'visibility';
        } elseif (in_array($post->post_status, ['draft', 'pending', 'future'])) {
            $view_url = get_preview_post_link($post);
            $button_text = __('Preview Funeral Notice', 'hk-funeral-notices');
            $icon = 'visibility';
        } else {
            // No view link for auto-draft or trash
            return $admin_title;
        }

        // Inject button via JavaScript (cleaner than trying to filter HTML)
        add_action('admin_footer', function() use ($view_url, $button_text, $icon) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                // Find the page title wrapper (where "+ New Funeral Notice" button is)
                var $titleWrap = $('.wrap > h1, .wrap > .wp-heading-inline').first().parent();

                // Check if button already exists
                if ($titleWrap.find('.hkfn-view-notice-btn').length === 0) {
                    // Create the View button
                    var viewButton = $('<a>', {
                        href: '<?php echo esc_js($view_url); ?>',
                        class: 'page-title-action hkfn-view-notice-btn',
                        target: '_blank',
                        rel: 'noopener noreferrer',
                        html: '<span class="dashicons dashicons-<?php echo esc_js($icon); ?>" style="vertical-align: middle; margin-top: 3px;"></span> <?php echo esc_js($button_text); ?>'
                    });

                    // Insert after the "+ New Funeral Notice" button
                    var $addNewBtn = $titleWrap.find('.page-title-action').first();
                    if ($addNewBtn.length) {
                        viewButton.insertAfter($addNewBtn);
                    } else {
                        // Fallback: add after the title heading
                        var $heading = $titleWrap.find('h1, .wp-heading-inline').first();
                        viewButton.insertAfter($heading);
                    }
                }
            });
            </script>
            <style>
            .hkfn-view-notice-btn {
                margin-left: 8px !important;
            }
            .hkfn-view-notice-btn .dashicons {
                font-size: 17px;
                height: 17px;
                width: 17px;
                margin-top: -2px !important;
            }
            </style>
            <?php
        });

        return $admin_title;
    }
}

// Initialize the simplified integration
HKFN_Role_Integration::init();
