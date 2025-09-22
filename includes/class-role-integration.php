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
        
        // Ensure roles have necessary capabilities for funeral notices
        if ($staff_role) {
            // funeral_staff already has edit_posts, delete_posts, publish_posts
            // Just ensure they're set (in case of issues)
            $staff_role->add_cap('edit_posts');
            $staff_role->add_cap('delete_posts');
            $staff_role->add_cap('publish_posts');
            $staff_role->add_cap('upload_files');
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
}

// Initialize the integration
WFN_Role_Integration::init();