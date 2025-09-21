<?php
/**
 * Migration functions for HK Funeral Notices
 * 
 * @package HK_Funeral_Notices
 * @since 2.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Optimized one-time migration: Copy legacy streaming URLs to current field structure
 * Only runs when specifically triggered to avoid performance issues
 */
add_action('wp_ajax_wfn_migrate_streaming_urls', 'wfn_handle_streaming_migration');

function wfn_handle_streaming_migration() {
    // Security check
    if (!current_user_can('manage_options') || !check_ajax_referer('wfn_migrate_streaming', 'nonce', false)) {
        wp_die('Unauthorized');
    }
    
    // Check if migration has been run
    if (get_option('wfn_streaming_migration_completed')) {
        wp_send_json_success(['message' => 'Migration already completed']);
        return;
    }
    
    // Get all funeral notices with legacy streaming URLs (limit for performance)
    $posts = get_posts([
        'post_type' => 'funeral-notice',
        'posts_per_page' => 50, // Process in batches
        'meta_query' => [
            [
                'key' => 'wfn_streaming_group_public_streaming_link',
                'value' => '',
                'compare' => '!='
            ]
        ]
    ]);
    
    $migrated = 0;
    foreach ($posts as $post) {
        $legacy_url = get_field('wfn_streaming_group_public_streaming_link', $post->ID);
        $current_url = get_field('wfn_streaming_group_streaming_url', $post->ID);
        
        // Only migrate if legacy has URL and current is empty
        if (!empty(trim($legacy_url)) && empty(trim($current_url))) {
            update_field('wfn_streaming_group_streaming_url', trim($legacy_url), $post->ID);
            $migrated++;
        }
    }
    
    // Mark migration as completed
    update_option('wfn_streaming_migration_completed', true);
    
    wp_send_json_success([
        'message' => "Successfully migrated {$migrated} legacy streaming URLs",
        'migrated' => $migrated
    ]);
}

/**
 * Add admin notice to trigger streaming URL migration manually if needed
 */
add_action('admin_notices', 'wfn_streaming_migration_admin_notice');

function wfn_streaming_migration_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Only show on funeral notices pages
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'funeral-notice') {
        return;
    }
    
    // Check if migration is needed
    if (get_option('wfn_streaming_migration_completed')) {
        return;
    }
    
    // Check if there are any legacy URLs that need migration
    $legacy_count = get_posts([
        'post_type' => 'funeral-notice',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'wfn_streaming_group_public_streaming_link',
                'value' => '',
                'compare' => '!='
            ]
        ]
    ]);
    
    if (empty($legacy_count)) {
        update_option('wfn_streaming_migration_completed', true);
        return;
    }
    
    ?>
    <div class="notice notice-info">
        <p>
            <strong>Streaming URL Migration Available:</strong> 
            Some funeral notices have streaming URLs in the legacy field format. 
            <button id="wfn-migrate-streaming" class="button button-primary" style="margin-left: 10px;">Migrate URLs Now</button>
            <span id="wfn-migration-status" style="margin-left: 10px;"></span>
        </p>
    </div>
    <script>
    document.getElementById('wfn-migrate-streaming').addEventListener('click', function(e) {
        e.preventDefault();
        const button = this;
        const status = document.getElementById('wfn-migration-status');
        
        button.disabled = true;
        button.textContent = 'Migrating...';
        status.textContent = '';
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=wfn_migrate_streaming_urls&nonce=' + '<?php echo wp_create_nonce('wfn_migrate_streaming'); ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                status.innerHTML = '<span style="color: green;">✅ ' + data.data.message + '</span>';
                button.style.display = 'none';
                setTimeout(() => {
                    document.querySelector('.notice').style.display = 'none';
                }, 3000);
            } else {
                status.innerHTML = '<span style="color: red;">❌ Migration failed</span>';
                button.disabled = false;
                button.textContent = 'Migrate URLs Now';
            }
        })
        .catch(error => {
            status.innerHTML = '<span style="color: red;">❌ Error: ' + error.message + '</span>';
            button.disabled = false;
            button.textContent = 'Migrate URLs Now';
        });
    });
    </script>
    <?php
}