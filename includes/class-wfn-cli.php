<?php
/**
 * WP-CLI Commands for WFN
 * 
 * Provides command-line tools for funeral notice management and migration
 * 
 * @since 2.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * WFN CLI Commands
 */
class WFN_CLI {
    
    /**
     * Migrate legacy location fields to new format
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Show what would be changed without making changes
     *
     * [--limit=<number>]
     * : Limit the number of posts to process
     *
     * [--overwrite-none]
     * : Also process posts with location_type='none'
     *
     * ## EXAMPLES
     *
     *     # Preview changes without applying them
     *     wp wfn migrate-locations --dry-run
     *
     *     # Migrate first 50 posts
     *     wp wfn migrate-locations --limit=50
     *
     *     # Full migration
     *     wp wfn migrate-locations
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function migrate_locations( $args, $assoc_args ) {
        $dry_run = isset( $assoc_args['dry-run'] );
        $limit = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : -1;
        $overwrite_none = isset( $assoc_args['overwrite-none'] );
        
        if ( $dry_run ) {
            WP_CLI::line( 'DRY RUN MODE - No changes will be made' );
            WP_CLI::line( '' );
        }
        
        // Get all funeral notices
        $args_query = [
            'post_type' => 'funeral-notice',
            'posts_per_page' => $limit,
            'post_status' => 'any',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'wfn_location_group_location_type',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'location_type', // Legacy field
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        $query = new WP_Query( $args_query );
        $processed = 0;
        $migrated = 0;
        
        WP_CLI::line( sprintf( 'Found %d funeral notices to process', $query->found_posts ) );
        WP_CLI::line( '' );
        
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
            $processed++;
            
            // Get current location data
            $location_group = get_field( 'wfn_location_group', $post_id ) ?: [];
            $current_location_type = $location_group['location_type'] ?? '';
            $legacy_location_type = get_post_meta( $post_id, 'location_type', true );
            
            // Skip if already has new format and no legacy data
            if ( $current_location_type && ! $legacy_location_type ) {
                continue;
            }
            
            // Skip 'none' types unless specifically requested
            if ( ! $overwrite_none && ( $current_location_type === 'none' || $legacy_location_type === 'none' ) ) {
                continue;
            }
            
            // Get legacy address fields
            $legacy_address = get_post_meta( $post_id, 'address', true );
            $legacy_venue = get_post_meta( $post_id, 'venue', true );
            $legacy_location_type = $legacy_location_type ?: 'venue';
            
            if ( $legacy_address || $legacy_venue || $legacy_location_type ) {
                $title = get_the_title( $post_id );
                
                if ( $dry_run ) {
                    WP_CLI::line( sprintf( 'Would migrate: %s (ID: %d)', $title, $post_id ) );
                    WP_CLI::line( sprintf( '  Legacy type: %s', $legacy_location_type ) );
                    WP_CLI::line( sprintf( '  Legacy venue: %s', $legacy_venue ?: 'none' ) );
                    WP_CLI::line( sprintf( '  Legacy address: %s', $legacy_address ?: 'none' ) );
                    WP_CLI::line( '' );
                } else {
                    // Perform migration
                    $new_location_group = [
                        'location_type' => $legacy_location_type,
                        'venue_name' => $legacy_venue ?: '',
                        'address' => $legacy_address ?: '',
                        'google_maps_url' => '', // Will be generated when saved
                    ];
                    
                    // Update the field
                    update_field( 'wfn_location_group', $new_location_group, $post_id );
                    
                    // Clean up legacy fields
                    delete_post_meta( $post_id, 'location_type' );
                    delete_post_meta( $post_id, 'address' );
                    delete_post_meta( $post_id, 'venue' );
                    
                    WP_CLI::success( sprintf( 'Migrated: %s (ID: %d)', $title, $post_id ) );
                }
                
                $migrated++;
            }
        }
        
        wp_reset_postdata();
        
        WP_CLI::line( '' );
        WP_CLI::line( sprintf( 'Processed: %d posts', $processed ) );
        WP_CLI::line( sprintf( 'Migrated: %d posts', $migrated ) );
        
        if ( $dry_run ) {
            WP_CLI::warning( 'This was a dry run - no changes were made. Remove --dry-run to apply changes.' );
        } else {
            WP_CLI::success( 'Location migration completed successfully!' );
        }
    }
    
    /**
     * Show statistics about funeral notices
     *
     * ## EXAMPLES
     *
     *     wp wfn stats
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function stats( $args, $assoc_args ) {
        $total = wp_count_posts( 'funeral-notice' );
        $published = $total->publish ?? 0;
        $draft = $total->draft ?? 0;
        
        WP_CLI::line( 'Funeral Notice Statistics:' );
        WP_CLI::line( sprintf( 'Published: %d', $published ) );
        WP_CLI::line( sprintf( 'Drafts: %d', $draft ) );
        WP_CLI::line( sprintf( 'Total: %d', $published + $draft ) );
        
        // Check for legacy fields
        $legacy_count = get_posts([
            'post_type' => 'funeral-notice',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'location_type',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'address',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'venue',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        WP_CLI::line( '' );
        WP_CLI::line( sprintf( 'Posts with legacy location fields: %d', count( $legacy_count ) ) );
        
        if ( count( $legacy_count ) > 0 ) {
            WP_CLI::warning( 'Run "wp wfn migrate-locations" to migrate legacy location data.' );
        }
    }
}

// Register the CLI commands
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'wfn', 'WFN_CLI' );
}
