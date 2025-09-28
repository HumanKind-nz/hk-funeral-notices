<?php
/**
 * Integration with HK Funeral Suite Roles
 * 
 * This class ensures the HK Funeral Notices plugin works seamlessly
 * with the existing funeral_staff and funeral_manager roles from
 * the HK Funeral Suite plugin.
 * 
 * @package HK_Funeral_Notices
 * @since 2.0.7
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class WFN_Role_Integration {
    
    /**
     * Initialize the role integration
     */
    public static function init() {
        // Hook into init to ensure roles are available
        add_action('init', array(__CLASS__, 'setup_role_integration'), 20);

        // Add custom capability check filter
        add_filter('wfn_can_manage_notices', array(__CLASS__, 'can_manage_notices'), 10, 2);

        // Customize admin menu visibility
        add_action('admin_menu', array(__CLASS__, 'adjust_menu_visibility'), 999);

        // CRITICAL: Add meta capability mapping to fix admin columns visibility
        add_filter('map_meta_cap', array(__CLASS__, 'map_funeral_notice_meta_caps'), 10, 4);

        // Add Edit link to admin bar for single funeral notice posts
        add_action('admin_bar_menu', array(__CLASS__, 'add_funeral_notice_edit_link'), 81);
    }
    
    /**
     * Setup role integration with HK Funeral Suite
     */
    public static function setup_role_integration() {
        // Check if HK Funeral Suite roles exist
        $staff_role = get_role('funeral_staff');
        $manager_role = get_role('funeral_manager');
        
        // If roles don't exist (HK Funeral Suite not installed), create basic versions
        if (!$staff_role && !$manager_role) {
            self::create_fallback_roles();
        }
        
        // Add funeral notice specific capabilities to all appropriate roles
        self::add_funeral_notice_caps_to_existing_roles();
        
        // Ensure roles have necessary capabilities for funeral notices
        if ($staff_role) {
            // funeral_staff gets basic funeral notice capabilities
            $staff_role->add_cap('edit_posts');
            $staff_role->add_cap('delete_posts');
            $staff_role->add_cap('publish_posts');
            $staff_role->add_cap('upload_files');
            
            // Add funeral notice specific caps
            $staff_role->add_cap('edit_funeral_notices');
            $staff_role->add_cap('edit_funeral_notice');
            $staff_role->add_cap('read_funeral_notice');
            $staff_role->add_cap('delete_funeral_notices');
            $staff_role->add_cap('delete_funeral_notice');
            $staff_role->add_cap('publish_funeral_notices');
            $staff_role->add_cap('create_funeral_notices');
            $staff_role->add_cap('edit_published_funeral_notices');
            $staff_role->add_cap('delete_published_funeral_notices');
        }
        
        if ($manager_role) {
            // funeral_manager needs additional capabilities for full management
            $manager_role->add_cap('edit_posts');
            $manager_role->add_cap('edit_others_posts');
            $manager_role->add_cap('edit_published_posts');
            $manager_role->add_cap('delete_posts');
            $manager_role->add_cap('delete_others_posts');
            $manager_role->add_cap('delete_published_posts');
            $manager_role->add_cap('publish_posts');
            $manager_role->add_cap('upload_files');
            
            // Add funeral notice specific caps (full management)
            $manager_role->add_cap('edit_funeral_notices');
            $manager_role->add_cap('edit_funeral_notice');
            $manager_role->add_cap('read_funeral_notice');
            $manager_role->add_cap('delete_funeral_notices');
            $manager_role->add_cap('delete_funeral_notice');
            $manager_role->add_cap('publish_funeral_notices');
            $manager_role->add_cap('create_funeral_notices');
            $manager_role->add_cap('edit_others_funeral_notices');
            $manager_role->add_cap('edit_private_funeral_notices');
            $manager_role->add_cap('edit_published_funeral_notices');
            $manager_role->add_cap('read_private_funeral_notices');
            $manager_role->add_cap('delete_funeral_notices');
            $manager_role->add_cap('delete_others_funeral_notices');
            $manager_role->add_cap('delete_private_funeral_notices');
            $manager_role->add_cap('delete_published_funeral_notices');
        }
    }
    
    /**
     * Add funeral notice capabilities to existing WordPress roles
     */
    private static function add_funeral_notice_caps_to_existing_roles() {
        // Administrator gets all capabilities
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_caps = [
                'edit_funeral_notices',
                'edit_funeral_notice', 
                'read_funeral_notice',
                'delete_funeral_notices',
                'delete_funeral_notice',
                'publish_funeral_notices',
                'create_funeral_notices',
                'edit_others_funeral_notices',
                'edit_private_funeral_notices',
                'edit_published_funeral_notices',
                'read_private_funeral_notices',
                'delete_others_funeral_notices',
                'delete_private_funeral_notices',
                'delete_published_funeral_notices',
            ];
            foreach ($admin_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }

        // Editor gets most capabilities (can edit others' posts)
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_caps = [
                'edit_funeral_notices',
                'edit_funeral_notice',
                'read_funeral_notice',
                'delete_funeral_notices', 
                'delete_funeral_notice',
                'publish_funeral_notices',
                'create_funeral_notices',
                'edit_others_funeral_notices',
                'edit_published_funeral_notices',
                'delete_others_funeral_notices',
                'delete_published_funeral_notices',
            ];
            foreach ($editor_caps as $cap) {
                $editor_role->add_cap($cap);
            }
        }

        // Author gets basic capabilities (own posts only)
        $author_role = get_role('author');
        if ($author_role) {
            $author_caps = [
                'edit_funeral_notices',
                'edit_funeral_notice',
                'read_funeral_notice',
                'delete_funeral_notices',
                'delete_funeral_notice', 
                'publish_funeral_notices',
                'create_funeral_notices',
                'edit_published_funeral_notices',
                'delete_published_funeral_notices',
            ];
            foreach ($author_caps as $cap) {
                $author_role->add_cap($cap);
            }
        }

        // Contributor gets limited capabilities (can create/edit but not publish)
        $contributor_role = get_role('contributor');
        if ($contributor_role) {
            $contributor_caps = [
                'edit_funeral_notices',
                'edit_funeral_notice',
                'read_funeral_notice',
                'delete_funeral_notices',
                'delete_funeral_notice',
                'create_funeral_notices',
            ];
            foreach ($contributor_caps as $cap) {
                $contributor_role->add_cap($cap);
            }
        }
    }

    /**
     * Create fallback roles if HK Funeral Suite is not installed
     * This ensures the plugin works standalone if needed
     */
    private static function create_fallback_roles() {
        // Only create if they don't exist
        if (!get_role('funeral_staff')) {
            add_role('funeral_staff', 
                __('Funeral Staff', 'hk-funeral-notices'),
                array(
                    'read' => true,
                    'edit_posts' => true,
                    'delete_posts' => true,
                    'publish_posts' => true,
                    'upload_files' => true,
                    // Funeral notice specific capabilities
                    'edit_funeral_notices' => true,
                    'edit_funeral_notice' => true,
                    'read_funeral_notice' => true,
                    'delete_funeral_notices' => true,
                    'delete_funeral_notice' => true,
                    'publish_funeral_notices' => true,
                    'create_funeral_notices' => true,
                    'edit_published_funeral_notices' => true,
                    'delete_published_funeral_notices' => true,
                )
            );
        }
        
        if (!get_role('funeral_manager')) {
            add_role('funeral_manager',
                __('Funeral Manager', 'hk-funeral-notices'),
                array(
                    'read' => true,
                    'read_private_posts' => true,
                    'edit_posts' => true,
                    'edit_others_posts' => true,
                    'edit_published_posts' => true,
                    'publish_posts' => true,
                    'delete_posts' => true,
                    'delete_others_posts' => true,
                    'delete_published_posts' => true,
                    'delete_private_posts' => true,
                    'edit_private_posts' => true,
                    'upload_files' => true,
                    // Funeral notice specific capabilities (full management)
                    'edit_funeral_notices' => true,
                    'edit_funeral_notice' => true,
                    'read_funeral_notice' => true,
                    'delete_funeral_notices' => true,
                    'delete_funeral_notice' => true,
                    'publish_funeral_notices' => true,
                    'create_funeral_notices' => true,
                    'edit_others_funeral_notices' => true,
                    'edit_private_funeral_notices' => true,
                    'edit_published_funeral_notices' => true,
                    'read_private_funeral_notices' => true,
                    'delete_others_funeral_notices' => true,
                    'delete_private_funeral_notices' => true,
                    'delete_published_funeral_notices' => true,
                )
            );
        }
    }
    
    /**
     * Check if current user can manage funeral notices
     * 
     * @param bool $can_manage Default capability check
     * @param WP_User $user User to check
     * @return bool
     */
    public static function can_manage_notices($can_manage, $user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        // Check for our custom roles
        if (in_array('funeral_staff', $user->roles) || 
            in_array('funeral_manager', $user->roles) ||
            in_array('administrator', $user->roles)) {
            return true;
        }
        
        // Check for HK Funeral Suite capability
        if ($user->has_cap('manage_funeral_content')) {
            return true;
        }
        
        // Fall back to standard capability check
        return $user->has_cap('edit_posts');
    }
    
    /**
     * Adjust admin menu visibility for funeral roles
     */
    public static function adjust_menu_visibility() {
        global $menu, $submenu;
        
        $user = wp_get_current_user();
        
        // If user is funeral_staff (not manager), hide some advanced items
        if (in_array('funeral_staff', $user->roles) && !in_array('funeral_manager', $user->roles)) {
            // Hide plugin settings from basic staff
            if (isset($submenu['hk-funeral-notices'])) {
                foreach ($submenu['hk-funeral-notices'] as $key => $item) {
                    // Hide settings pages from basic staff
                    if (strpos($item[2], 'settings') !== false || 
                        strpos($item[2], 'modules') !== false) {
                        unset($submenu['hk-funeral-notices'][$key]);
                    }
                }
            }
        }
    }
    
    /**
     * Force update of role capabilities (for existing installations)
     * Call this if you need to update capabilities for existing users
     */
    public static function force_update_capabilities() {
        self::add_funeral_notice_caps_to_existing_roles();
        
        // Also update existing funeral roles
        $staff_role = get_role('funeral_staff');
        $manager_role = get_role('funeral_manager');
        
        if ($staff_role) {
            $staff_caps = [
                'edit_funeral_notices', 'edit_funeral_notice', 'read_funeral_notice',
                'delete_funeral_notices', 'delete_funeral_notice', 'publish_funeral_notices',
                'create_funeral_notices', 'edit_published_funeral_notices', 
                'delete_published_funeral_notices'
            ];
            foreach ($staff_caps as $cap) {
                $staff_role->add_cap($cap);
            }
        }
        
        if ($manager_role) {
            $manager_caps = [
                'edit_funeral_notices', 'edit_funeral_notice', 'read_funeral_notice',
                'delete_funeral_notices', 'delete_funeral_notice', 'publish_funeral_notices',
                'create_funeral_notices', 'edit_others_funeral_notices',
                'edit_private_funeral_notices', 'edit_published_funeral_notices',
                'read_private_funeral_notices', 'delete_others_funeral_notices',
                'delete_private_funeral_notices', 'delete_published_funeral_notices'
            ];
            foreach ($manager_caps as $cap) {
                $manager_role->add_cap($cap);
            }
        }
    }

    /**
     * Get display name for current user's funeral role
     * 
     * @return string|null Role display name or null if not a funeral role
     */
    public static function get_funeral_role_name() {
        $user = wp_get_current_user();
        
        if (in_array('funeral_manager', $user->roles)) {
            return __('Funeral Manager', 'hk-funeral-notices');
        } elseif (in_array('funeral_staff', $user->roles)) {
            return __('Funeral Staff', 'hk-funeral-notices');
        }
        
        return null;
    }
    
    /**
     * Check if HK Funeral Suite is active
     *
     * @return bool
     */
    public static function is_funeral_suite_active() {
        return defined('HK_FS_VERSION');
    }

    /**
     * Map meta capabilities for funeral notice post type
     *
     * This is CRITICAL for fixing admin columns visibility.
     * WordPress core checks current_user_can('edit_post', $post_id) but with
     * custom post types, this needs to map to the correct capability.
     *
     * @param array $caps The capabilities required
     * @param string $cap Capability name
     * @param int $user_id User ID
     * @param array $args Additional arguments
     * @return array
     */
    public static function map_funeral_notice_meta_caps($caps, $cap, $user_id, $args) {
        // Only map capabilities for funeral notice posts
        if (!empty($args[0])) {
            $post = get_post($args[0]);
            if (!$post || $post->post_type !== 'funeral-notice') {
                return $caps;
            }
        }

        // Get user object to check roles directly
        $user = get_userdata($user_id);
        if (!$user) {
            return $caps;
        }

        switch ($cap) {
            case 'edit_post':
                // Check if user has roles that should be able to edit funeral notices
                $allowed_roles = array('administrator', 'editor', 'funeral_staff', 'funeral_manager');
                $user_roles = (array) $user->roles;

                if (array_intersect($allowed_roles, $user_roles)) {
                    // User has an allowed role - check ownership for non-admin roles
                    if (in_array('administrator', $user_roles)) {
                        $caps = array('read'); // Admins can edit anything
                    } elseif (in_array('editor', $user_roles) || in_array('funeral_manager', $user_roles)) {
                        $caps = array('edit_posts'); // Editors and managers can edit others' posts
                    } else {
                        // funeral_staff - check ownership
                        if (!empty($args[0])) {
                            $post = get_post($args[0]);
                            if ($post && ($post->post_author == $user_id || in_array('funeral_manager', $user_roles))) {
                                $caps = array('edit_posts');
                            } else {
                                $caps = array('edit_others_posts'); // Will fail for funeral_staff
                            }
                        } else {
                            $caps = array('edit_posts');
                        }
                    }
                } else {
                    // User doesn't have allowed role - fall back to original custom caps
                    $caps = array('edit_funeral_notice');
                }
                break;

            case 'delete_post':
                // Same logic as edit_post
                $allowed_roles = array('administrator', 'editor', 'funeral_staff', 'funeral_manager');
                $user_roles = (array) $user->roles;

                if (array_intersect($allowed_roles, $user_roles)) {
                    if (in_array('administrator', $user_roles)) {
                        $caps = array('read'); // Admins can delete anything
                    } elseif (in_array('editor', $user_roles) || in_array('funeral_manager', $user_roles)) {
                        $caps = array('delete_posts'); // Editors and managers can delete others' posts
                    } else {
                        // funeral_staff - check ownership
                        if (!empty($args[0])) {
                            $post = get_post($args[0]);
                            if ($post && ($post->post_author == $user_id || in_array('funeral_manager', $user_roles))) {
                                $caps = array('delete_posts');
                            } else {
                                $caps = array('delete_others_posts'); // Will fail for funeral_staff
                            }
                        } else {
                            $caps = array('delete_posts');
                        }
                    }
                } else {
                    $caps = array('delete_funeral_notice');
                }
                break;

            case 'read_post':
                // Anyone who can read can read funeral notices
                $caps = array('read');
                break;

            case 'publish_posts':
                if (!empty($args[0])) {
                    $post = get_post($args[0]);
                    if ($post && $post->post_type === 'funeral-notice') {
                        $allowed_roles = array('administrator', 'editor', 'funeral_staff', 'funeral_manager');
                        $user_roles = (array) $user->roles;

                        if (array_intersect($allowed_roles, $user_roles)) {
                            $caps = array('publish_posts');
                        } else {
                            $caps = array('publish_funeral_notices');
                        }
                    }
                }
                break;

            case 'create_posts':
                // Check if user has allowed role for creating posts
                $allowed_roles = array('administrator', 'editor', 'funeral_staff', 'funeral_manager');
                $user_roles = (array) $user->roles;

                if (array_intersect($allowed_roles, $user_roles)) {
                    $caps = array('edit_posts');
                } else {
                    $caps = array('create_funeral_notices');
                }
                break;
        }

        return $caps;
    }

    /**
     * Add Edit link to admin bar for single funeral notice posts
     *
     * Since we disabled show_in_admin_bar for the post type to remove the confusing
     * "View Funeral Notices" link, we need to manually add the Edit link for single posts.
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public static function add_funeral_notice_edit_link($wp_admin_bar) {
        // Only show on single funeral notice posts
        if (!is_singular('funeral-notice')) {
            return;
        }

        global $post;
        if (!$post || $post->post_type !== 'funeral-notice') {
            return;
        }

        // Check if user can edit this post
        if (!current_user_can('edit_post', $post->ID)) {
            return;
        }

        // Get the edit link
        $edit_link = get_edit_post_link($post->ID);
        if (!$edit_link) {
            return;
        }

        // Add the edit node to admin bar
        $wp_admin_bar->add_node(array(
            'id'    => 'edit-funeral-notice',
            'title' => __('Edit Funeral Notice', 'hk-funeral-notices'),
            'href'  => $edit_link,
            'meta'  => array(
                'title' => sprintf(__('Edit "%s"'), $post->post_title)
            )
        ));
    }
}

// Initialize the integration
WFN_Role_Integration::init();