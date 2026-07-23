<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Modules;

use HumanKind\FuneralNotices\Services\SupabaseService;

/**
 * Analytics Module
 *
 * Collects anonymous usage statistics to improve the plugin and provide
 * industry insights. This module is designed for agency-managed installations
 * where Weave Digital Studio manages the WordPress sites on behalf of funeral homes.
 *
 * PRIVACY & DATA COLLECTION:
 *
 * What We Collect (Anonymous Aggregated Data Only):
 * - Number of funeral notices created per month (count only)
 * - Percentage of notices using streaming features (count only)
 * - Number of configured venue locations (count only)
 * - Plugin version, WordPress version, PHP version
 * - Anonymous site identifier (non-reversible hash)
 * - Site name and URL (one-time only, for administrative grouping)
 *
 * What We NEVER Collect:
 * - Deceased persons' names, dates, or biographical information
 * - Funeral notice content or memorial messages
 * - Personal information about site administrators or staff
 * - Website visitor information or analytics
 * - Any personally identifiable information
 *
 * Purpose:
 * - Improve plugin features based on real-world usage patterns
 * - Provide anonymized industry reporting to funeral home clients
 * - Ensure compatibility with WordPress/PHP updates
 * - Plan future development priorities
 *
 * Opt-Out:
 * Since this plugin is managed by Weave Digital Studio, analytics are enabled
 * by default across all installations. Clients may request analytics be disabled
 * for their specific site by contacting support@weave.co.nz.
 *
 * Developers can disable analytics per-site using:
 * add_filter('hkfn_enable_analytics', '__return_false');
 *
 * Data Security:
 * All analytics data is transmitted securely and stored in compliance with
 * New Zealand Privacy Act 2020 requirements.
 *
 * @since 2.3.0
 */
class AnalyticsModule extends BaseModule {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'analytics',
            'Usage Analytics',
            'Anonymous usage statistics to improve plugin features and provide industry insights',
            '1.0.0'
        );
    }

    /**
     * Initialize the module
     */
    public function init(): void {
        // Don't call parent::init() as we don't need admin pages for this module

        // Register custom monthly cron schedule
        add_filter('cron_schedules', [$this, 'add_monthly_cron_schedule']);

        // Only set up analytics if enabled
        if (!$this->is_analytics_enabled()) {
            return;
        }

        // Schedule monthly analytics report
        if (!wp_next_scheduled('hkfn_send_monthly_analytics')) {
            // Schedule for 2am on the 1st of next month
            $next_run = strtotime('first day of next month 02:00:00');
            wp_schedule_event($next_run, 'hkfn_monthly', 'hkfn_send_monthly_analytics');
        }

        // Schedule weekly heartbeat to keep Supabase project active (free tier requirement)
        // Supabase pauses projects after 7 days of inactivity
        if (!wp_next_scheduled('hkfn_supabase_heartbeat')) {
            wp_schedule_event(time(), 'weekly', 'hkfn_supabase_heartbeat');
        }

        add_action('hkfn_send_monthly_analytics', [$this, 'send_monthly_stats']);
        add_action('hkfn_supabase_heartbeat', [$this, 'send_heartbeat']);

        // Allow manual trigger via admin (for testing)
        add_action('admin_post_hkfn_test_analytics', [$this, 'handle_test_analytics']);
    }

    /**
     * Add custom monthly cron schedule
     *
     * WordPress doesn't have a built-in monthly schedule, so we add one.
     * 30 days = 2592000 seconds
     *
     * @param array $schedules Existing cron schedules
     * @return array Modified schedules
     */
    public function add_monthly_cron_schedule(array $schedules): array {
        $schedules['hkfn_monthly'] = [
            'interval' => 2592000, // 30 days in seconds
            'display'  => __('Once Monthly (WFN Analytics)', 'hk-funeral-notices'),
        ];
        return $schedules;
    }

    /**
     * Check if analytics are enabled
     *
     * Respects multiple opt-out methods:
     * 1. WordPress filter (hkfn_enable_analytics) - for agency control
     * 2. wp-config.php constant (HKFN_DISABLE_ANALYTICS) - for deployment control
     *
     * Default: Enabled (since this is agency-managed)
     *
     * @return bool True if analytics should be sent
     */
    private function is_analytics_enabled(): bool {
        // Check wp-config.php constant first (deployment-level control)
        if (hkfn_get_constant('DISABLE_ANALYTICS')) {
            return false;
        }

        // Check WordPress filter (per-site control for agencies)
        // Default to true since this is agency-managed
        return (bool) apply_filters('hkfn_enable_analytics', true);
    }

    /**
     * Get anonymous site identifier
     *
     * Creates a consistent but non-reversible identifier for grouping
     * statistics while maintaining site anonymity in the data.
     *
     * The site URL is sent ONCE during initial registration for
     * administrative grouping purposes, then never transmitted again.
     * This allows Weave to maintain a lookup table mapping anonymous
     * IDs to client names without exposing that information in analytics data.
     *
     * @return string 8-character anonymous site ID
     */
    private function get_site_identifier(): string {
        $cached = hkfn_get_option('analytics_site_id');

        if ($cached) {
            return $cached;
        }

        // Generate consistent anonymous ID using site URL + salt
        // This ensures the same site always gets the same ID
        $site_id = substr(md5(get_site_url() . 'hkfn-analytics-salt-v1'), 0, 8);
        update_option('hkfn_analytics_site_id', $site_id);

        return $site_id;
    }

    /**
     * Send monthly statistics
     *
     * Transmits aggregated, anonymous usage data to Supabase for analysis.
     * Only sends counts and percentages - NO personal information or funeral details.
     *
     * Data is sent monthly via scheduled cron job. First-time sites also include
     * registration data (site name) for administrative grouping purposes.
     *
     * @return bool Success status
     */
    public function send_monthly_stats(): bool {
        // Respect opt-out settings
        if (!$this->is_analytics_enabled()) {
            error_log('WFN Analytics: Skipped (analytics disabled via filter or constant)');
            return false;
        }

        $is_first_time = !hkfn_get_option('analytics_registered', false);

        // Build analytics payload
        $payload = [
            'site_id' => $this->get_site_identifier(),
            'month' => date('Y-m', strtotime('first day of last month')), // Report for last month

            // ANONYMOUS METRICS ONLY - Counts and aggregates
            'metrics' => [
                'new_funerals' => $this->count_new_funerals_last_month(),
                'with_streaming' => $this->count_with_streaming_last_month(),
                'streaming_percentage' => $this->calculate_streaming_percentage_last_month(),
                'total_venues' => $this->count_total_venues(),
                'total_funerals_all_time' => $this->count_total_funerals(),
                'plugin_version' => HKFN_VERSION,
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
            ],

            // Site registration data (first time only, for admin lookup)
            // This allows Weave to create a mapping: Site_ID → Client Name/Region
            // Subsequent reports only include the anonymous site_id
            'registration' => $is_first_time ? [
                'site_name' => get_bloginfo('name'),
                'client' => '', // Weave fills this in manually via Supabase dashboard
                'region' => '', // Weave fills this in manually via Supabase dashboard
                'timezone' => wp_timezone_string(),
            ] : null,
        ];

        try {
            $service = new SupabaseService();
            $result = $service->send_stats($payload);

            if ($is_first_time && $result) {
                update_option('hkfn_analytics_registered', true);
                error_log('WFN Analytics: First-time registration completed for site ID ' . $payload['site_id']);
            }

            if ($result) {
                error_log('WFN Analytics: Successfully sent stats for ' . $payload['month']);
            }

            return $result;

        } catch (\Exception $e) {
            error_log('WFN Analytics Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send heartbeat to keep Supabase project active
     *
     * Supabase free tier pauses projects after 7 days of inactivity.
     * This weekly heartbeat performs a simple query to keep the project active.
     *
     * Since full analytics are sent monthly (1st of month), sites would be
     * inactive for 29-30 days. This heartbeat runs weekly to prevent pausing.
     *
     * We use a simple SELECT query instead of INSERT to avoid polluting data.
     *
     * @return bool Success status
     */
    public function send_heartbeat(): bool {
        // Respect opt-out settings
        if (!$this->is_analytics_enabled()) {
            return false;
        }

        try {
            $service = new SupabaseService();

            // Test connection - this performs a SELECT query which counts as activity
            // No data is written, just a read to keep the database active
            $test_result = $service->test_connection();

            if ($test_result['success']) {
                error_log('WFN Analytics: Heartbeat successful (Supabase project kept active)');
                return true;
            }

            error_log('WFN Analytics: Heartbeat failed - ' . $test_result['message']);
            return false;

        } catch (\Exception $e) {
            error_log('WFN Analytics Heartbeat Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Count new funeral notices created last month
     *
     * Returns only the COUNT - no names, content, or personal details.
     *
     * @return int Count of funeral notices published last month
     */
    private function count_new_funerals_last_month(): int {
        $start = date('Y-m-01', strtotime('first day of last month'));
        $end = date('Y-m-t', strtotime('last day of last month'));

        $query = new \WP_Query([
            'post_type' => 'funeral-notice',
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $start,
                    'before' => $end,
                    'inclusive' => true,
                ],
            ],
            'fields' => 'ids', // Only IDs for counting, no content
            'posts_per_page' => -1,
        ]);

        return $query->found_posts;
    }

    /**
     * Count funeral notices with streaming links last month
     *
     * Returns only the COUNT - no streaming URLs or service details.
     *
     * @return int Count of funerals with streaming enabled
     */
    private function count_with_streaming_last_month(): int {
        $start = date('Y-m-01', strtotime('first day of last month'));
        $end = date('Y-m-t', strtotime('last day of last month'));

        $query = new \WP_Query([
            'post_type' => 'funeral-notice',
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $start,
                    'before' => $end,
                    'inclusive' => true,
                ],
            ],
            'meta_query' => [
                [
                    'key' => 'hkfn_streaming_group_streaming_url',
                    'value' => '',
                    'compare' => '!=',
                ],
            ],
            'fields' => 'ids',
            'posts_per_page' => -1,
        ]);

        return $query->found_posts;
    }

    /**
     * Calculate streaming usage percentage last month
     *
     * @return float Percentage (0-100) of funerals with streaming
     */
    private function calculate_streaming_percentage_last_month(): float {
        $total = $this->count_new_funerals_last_month();

        if ($total === 0) {
            return 0.0;
        }

        $with_streaming = $this->count_with_streaming_last_month();
        return round(($with_streaming / $total) * 100, 2);
    }

    /**
     * Count total configured venue locations
     *
     * @return int Number of funeral-location taxonomy terms
     */
    private function count_total_venues(): int {
        $terms = get_terms([
            'taxonomy' => 'funeral-location',
            'hide_empty' => false,
            'fields' => 'count',
        ]);

        return is_array($terms) ? count($terms) : 0;
    }

    /**
     * Count total funeral notices all time
     *
     * @return int Total published funeral notices
     */
    private function count_total_funerals(): int {
        $counts = wp_count_posts('funeral-notice');
        return (int) ($counts->publish ?? 0);
    }

    /**
     * Handle manual analytics test trigger
     *
     * Allows admins to manually trigger analytics send for testing purposes.
     * Accessible via: wp-admin/admin-post.php?action=hkfn_test_analytics
     */
    public function handle_test_analytics(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $result = $this->send_monthly_stats();

        if ($result) {
            wp_die('Analytics test successful! Data sent to Supabase. <a href="' . admin_url() . '">Back to Dashboard</a>');
        } else {
            wp_die('Analytics test failed. Check error logs. <a href="' . admin_url() . '">Back to Dashboard</a>');
        }
    }

    /**
     * This module doesn't have an admin page
     * Required by BaseModule but not used
     */
    public function register_admin_page(): void {
        // Analytics module doesn't need an admin page
        // It runs silently in the background
    }

    /**
     * This module doesn't have admin content
     * Required by BaseModule but not used
     */
    protected function render_module_admin_content(): void {
        // Not used - analytics runs in background
    }
}
