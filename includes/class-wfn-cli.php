<?php
// WP-CLI integration for WFN migrations

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( defined('WP_CLI') && WP_CLI ) {
    class WFN_CLI_Commands {
        /**
         * Migrate legacy v1 location fields (ACFE) to new schema.
         *
         * ## OPTIONS
         *
         * [--dry-run]
         * : Run without saving changes (report only).
         *
         * [--limit=<number>]
         * : Limit number of posts processed (useful for testing).
         *
         * [--overwrite-none]
         * : Reprocess posts that already have location_type='none' (useful after improving detection).
         *
         * ## EXAMPLES
         *     wp wfn migrate-locations --dry-run
         *     wp wfn migrate-locations --limit=50
         *     wp wfn migrate-locations --overwrite-none
         */
        public function migrate_locations( $args, $assoc_args ) {
            $dry_run        = isset( $assoc_args['dry-run'] );
            $limit          = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : -1;
            $overwrite_none = isset( $assoc_args['overwrite-none'] );

            if ( $limit > 0 ) {
                $posts = get_posts([
                    'post_type'      => 'funeral-notice',
                    'posts_per_page' => $limit,
                    'post_status'    => 'any'
                ]);
                WP_CLI::log( "Processing {$limit} posts (test run)" );
            } else {
                $posts = get_posts([
                    'post_type'      => 'funeral-notice',
                    'posts_per_page' => -1,
                    'post_status'    => 'any'
                ]);
            }

            $migrated = 0; $skipped = 0; $errors = 0;

            foreach ( $posts as $post ) {
                try {
                    $details_group  = get_field('wfn_details_group', $post->ID) ?: [];
                    $location_group = get_field('wfn_location_group', $post->ID) ?: [];

                    if ( isset( $details_group['location_type'] ) ) {
                        if ( $overwrite_none && $details_group['location_type'] === 'none' ) {
                            // allow reprocessing
                        } else {
                            $skipped++;
                            continue;
                        }
                    }

                    $is_other_array   = $location_group['is_at_another_location'] ?? [];
                    if ( empty( $is_other_array ) ) {
                        $is_other_array = get_field('wfn_location_group_is_at_another_location', $post->ID ) ?: [];
                    }
                    $is_other         = is_array($is_other_array) ? in_array('yes', $is_other_array) : (bool) $is_other_array;

                    $acfe_address     = $location_group['other_funeral_address'] ?? null;
                    if ( empty( $acfe_address ) ) {
                        $acfe_address = get_field('wfn_location_group_other_funeral_address', $post->ID );
                    }

                    $taxonomy_term_id = $location_group['location'] ?? null;
                    if ( empty( $taxonomy_term_id ) ) {
                        $taxonomy_term_id = get_field('wfn_location_group_location', $post->ID );
                    }

                    $location_type = 'none';
                    if ( $is_other && ! empty( $acfe_address ) ) {
                        $location_type = 'custom';
                    } elseif ( ! empty( $taxonomy_term_id ) ) {
                        $location_type = 'existing';
                    }

                    $updated = $details_group;
                    $updated['location_type'] = $location_type;
                    if ( $location_type === 'custom' && is_array( $acfe_address ) ) {
                        $updated['custom_address'] = $acfe_address;
                    }
                    if ( $location_type === 'existing' && ! empty( $taxonomy_term_id ) ) {
                        $updated['location'] = $taxonomy_term_id;
                    }

                    if ( ! $dry_run ) {
                        update_field( 'wfn_details_group', $updated, $post->ID );
                    }
                    $migrated++;

                } catch ( \Exception $e ) {
                    WP_CLI::warning( "Post {$post->ID} error: " . $e->getMessage() );
                    $errors++;
                }
            }

            WP_CLI::success( sprintf( 'Locations migration finished. Migrated: %d, Skipped: %d, Errors: %d%s%s',
                $migrated, $skipped, $errors,
                $dry_run ? ' (dry run)' : '',
                $overwrite_none ? ' [overwrite-none]' : ''
            ) );
        }
    }

    WP_CLI::add_command( 'wfn migrate-locations', [ 'WFN_CLI_Commands', 'migrate_locations' ] );
}
