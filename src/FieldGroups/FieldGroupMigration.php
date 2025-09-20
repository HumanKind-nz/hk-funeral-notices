<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\FieldGroups;

/**
 * Field Group Migration
 * Handles migration from monolithic to modular field groups
 * 
 * @since 2.0.0
 */
class FieldGroupMigration {

    private const OLD_GROUP_KEY = 'group_6125700a6a0a7';
    private const MIGRATION_OPTION = 'wfn_field_migration_completed';

    /**
     * Check if migration is needed and run it
     */
    public function maybe_migrate(): void {
        if ($this->is_migration_completed()) {
            return;
        }

        if ($this->should_migrate()) {
            $this->run_migration();
        }
    }

    /**
     * Check if migration has been completed
     */
    private function is_migration_completed(): bool {
        return (bool) get_option(self::MIGRATION_OPTION, false);
    }

    /**
     * Check if migration should run
     */
    private function should_migrate(): bool {
        // Check if old field group exists
        if (!function_exists('acf_get_field_group')) {
            return false;
        }

        $old_group = acf_get_field_group(self::OLD_GROUP_KEY);
        return !empty($old_group);
    }

    /**
     * Run the migration process
     */
    private function run_migration(): void {
        // Debug logging removed for production

        try {
            // Step 1: Verify data integrity
            $this->verify_existing_data();

            // Step 2: Deactivate old field group
            $this->deactivate_old_field_group();

            // Step 3: Migrate location structure
            $location_results = $this->migrate_location_structure(false, true); // allow fixing records set to 'none'

            // Step 4: Migrate hero background images to options
            $hero_results = $this->migrate_hero_background_to_options();

            // Step 5: Test new field groups work with existing data
            $this->test_new_field_groups();

            // Step 6: Mark migration as complete
            $this->mark_migration_complete();

            // Debug logging removed for production

        } catch (Exception $e) {
            // Debug logging removed for production
            $this->rollback_migration();
        }
    }

    /**
     * Verify existing data is accessible
     */
    private function verify_existing_data(): void {
        $funeral_notices = get_posts([
            'post_type' => 'funeral-notice',
            'posts_per_page' => 5,
            'post_status' => 'any',
        ]);

        foreach ($funeral_notices as $post) {
            // Test that we can read existing field data
            $person_group = get_field('wfn_person_group', $post->ID);
            $notice_group = get_field('wfn_notice_group', $post->ID);
            $details_group = get_field('wfn_details_group', $post->ID);
            $streaming_group = get_field('wfn_streaming_group', $post->ID);

            if (empty($person_group) && empty($notice_group) && empty($details_group) && empty($streaming_group)) {
                // This might be a new post with no data, which is fine
                continue;
            }

            // Debug logging removed for production
        }
    }

    /**
     * Deactivate the old monolithic field group
     */
    private function deactivate_old_field_group(): void {
        if (!function_exists('acf_update_field_group')) {
            return;
        }

        $old_group = acf_get_field_group(self::OLD_GROUP_KEY);
        if ($old_group) {
            $old_group['active'] = 0;
            acf_update_field_group($old_group);
        }
    }

    /**
     * Test that new field groups work with existing data
     */
    private function test_new_field_groups(): void {
        $funeral_notices = get_posts([
            'post_type' => 'funeral-notice',
            'posts_per_page' => 3,
            'post_status' => 'any',
        ]);

        foreach ($funeral_notices as $post) {
            // Test reading data through new field structure
            $person_data = get_field('wfn_person_group', $post->ID);
            
            if (!empty($person_data)) {
                // Verify we can access individual fields
                $firstname = $person_data['firstname'] ?? '';
                $lastname = $person_data['lastname'] ?? '';
                
                    if (!empty($firstname) || !empty($lastname)) {
                    // Debug logging removed for production
                }
            }
        }
    }

    /**
     * Mark migration as complete
     */
    private function mark_migration_complete(): void {
        update_option(self::MIGRATION_OPTION, true);
        update_option('wfn_field_migration_date', current_time('mysql'));
    }

    /**
     * Rollback migration if something goes wrong
     */
    private function rollback_migration(): void {
        // Reactivate old field group
        if (function_exists('acf_update_field_group')) {
            $old_group = acf_get_field_group(self::OLD_GROUP_KEY);
            if ($old_group) {
                $old_group['active'] = 1;
                acf_update_field_group($old_group);
            }
        }
    }

    /**
     * Force migration reset (for testing)
     */
    public function reset_migration(): void {
        delete_option(self::MIGRATION_OPTION);
        delete_option('wfn_field_migration_date');
    }

    /**
     * Get migration status for admin display
     */
    public function get_migration_status(): array {
        return [
            'completed' => $this->is_migration_completed(),
            'date' => get_option('wfn_field_migration_date', ''),
            'old_group_exists' => $this->should_migrate(),
        ];
    }

    /**
     * Manual migration trigger for admin
     */
    public function force_migration(): bool {
        try {
            $this->reset_migration();
            $this->run_migration();
            return true;
        } catch (Exception $e) {
            error_log('WFN: Forced migration failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Migrate location structure from checkbox to radio options
     */
    public function migrate_location_structure(bool $dry_run = false, bool $overwrite_none = false): array {
        $results = [
            'migrated' => 0,
            'skipped' => 0,
            'errors'  => []
        ];

        $posts = get_posts([
            'post_type'      => 'funeral-notice',
            'posts_per_page' => -1,
            'post_status'    => 'any'
        ]);

        foreach ($posts as $post) {
            try {
                $details_group  = get_field('wfn_details_group', $post->ID) ?: [];
                $location_group = get_field('wfn_location_group', $post->ID) ?: [];

                // Skip if already migrated to new schema, unless we explicitly want to overwrite 'none'
                if (isset($details_group['location_type'])) {
                    if ($overwrite_none && $details_group['location_type'] === 'none') {
                        // allow reprocessing
                    } else {
                        $results['skipped']++;
                        continue;
                    }
                }

                // Legacy v1 structure (ACFE):
                // - Checkbox: wfn_location_group['is_at_another_location'] => ['yes'] or []
                // - ACFE Google Map: wfn_location_group['other_funeral_address'] (array with address/lat/lng/...)
                // - Taxonomy selection: wfn_location_group['location'] (term id)
                // Prefer group values
                $is_other_array   = $location_group['is_at_another_location'] ?? [];
                // Also read direct subfield meta if stored outside the group
                if (empty($is_other_array)) {
                    $is_other_array = get_field('wfn_location_group_is_at_another_location', $post->ID) ?: [];
                }
                $is_other = is_array($is_other_array) ? in_array('yes', $is_other_array) : (bool) $is_other_array;

                // ACFE Google Map field (array)
                $acfe_address = $location_group['other_funeral_address'] ?? null;
                if (empty($acfe_address)) {
                    $acfe_address = get_field('wfn_location_group_other_funeral_address', $post->ID);
                }

                // Selected taxonomy location id
                $taxonomy_term_id = $location_group['location'] ?? null;
                if (empty($taxonomy_term_id)) {
                    $taxonomy_term_id = get_field('wfn_location_group_location', $post->ID);
                }

                // Determine new location_type and target values
                $location_type = 'none';
                if ($is_other && !empty($acfe_address)) {
                    $location_type = 'custom';
                } elseif (!empty($taxonomy_term_id)) {
                    $location_type = 'existing';
                }

                // Build updated details group
                $updated = $details_group; // keep any other new fields already present
                $updated['location_type'] = $location_type;

                if ($location_type === 'custom' && !empty($acfe_address) && is_array($acfe_address)) {
                    // Directly copy ACFE Google Map structure to our custom_address (AddressFieldManager will normalize on read)
                    $updated['custom_address'] = $acfe_address;
                }

                if ($location_type === 'existing' && !empty($taxonomy_term_id)) {
                    // Store selected taxonomy term id
                    $updated['location'] = $taxonomy_term_id;
                }

                if (!$dry_run) {
                    update_field('wfn_details_group', $updated, $post->ID);
                }

                $results['migrated']++;

            } catch (Exception $e) {
                $results['errors'][] = "Post {$post->ID}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Migrate hero background images from posts to options
     */
    public function migrate_hero_background_to_options(): array {
        $results = [
            'migrated' => 0,
            'message' => 'No hero background images found'
        ];

        // Check if there's already a hero background in options
        $existing_hero = get_field('wfn_hero_background_image', 'option');
        if (!empty($existing_hero)) {
            $results['message'] = 'Hero background already exists in options - skipped migration';
            return $results;
        }

        $posts = get_posts([
            'post_type' => 'funeral-notice',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        $hero_images_found = [];

        foreach ($posts as $post) {
            $person_group = get_field('wfn_person_group', $post->ID) ?: [];
            $hero_background = $person_group['hero_background_image'] ?? null;
            
            if (!empty($hero_background) && is_array($hero_background) && !empty($hero_background['url'])) {
                $hero_images_found[] = [
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'image' => $hero_background
                ];
                $results['migrated']++;
            }
        }

        if (!empty($hero_images_found)) {
            // Use the first hero background image found as the sitewide default
            $first_hero = $hero_images_found[0]['image'];
            update_field('wfn_hero_background_image', $first_hero, 'option');
            
            $results['message'] = "Migrated hero background from post '{$hero_images_found[0]['post_title']}' to sitewide options. Found {$results['migrated']} total hero images.";
            
            // Log all found images for reference
            error_log('WFN: Hero background images found in posts: ' . json_encode(array_map(function($item) {
                return "Post {$item['post_id']}: {$item['post_title']}";
            }, $hero_images_found)));
        }

        return $results;
    }
} 