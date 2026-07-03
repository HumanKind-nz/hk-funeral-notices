<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Services;

/**
 * Supabase Service
 *
 * Handles communication with Supabase for anonymous analytics reporting.
 * Uses Supabase's auto-generated REST API to store usage statistics
 * across multiple funeral home installations.
 *
 * ZERO CONFIGURATION REQUIRED:
 * - Supabase credentials are hardcoded in the plugin
 * - Works immediately on all sites (managed or downloaded)
 * - No per-site setup needed
 *
 * Security:
 * - Uses public anon key (safe to hardcode)
 * - Row Level Security (RLS) policies control access
 * - Only INSERT permission for analytics data
 * - Read access restricted to Weave team via dashboard
 *
 * Setup Instructions (One-Time for Weave):
 * 1. Create free Supabase account at https://supabase.com
 * 2. Create new project
 * 3. Create funeral_analytics table (see SQL below)
 * 4. Enable RLS with INSERT-only policy for anon key
 * 5. Copy Project URL and anon key to constants below
 *
 * SQL Table Creation:
 * ```sql
 * CREATE TABLE funeral_analytics (
 *     id BIGSERIAL PRIMARY KEY,
 *     site_id VARCHAR(64) NOT NULL,
 *     month VARCHAR(7) NOT NULL,
 *     site_name TEXT,
 *     client TEXT,
 *     region VARCHAR(10),
 *     new_funerals INT DEFAULT 0,
 *     with_streaming INT DEFAULT 0,
 *     streaming_percentage DECIMAL(5,2) DEFAULT 0,
 *     total_venues INT DEFAULT 0,
 *     total_funerals_all_time INT DEFAULT 0,
 *     plugin_version VARCHAR(20),
 *     wp_version VARCHAR(20),
 *     php_version VARCHAR(20),
 *     timezone VARCHAR(50),
 *     is_first_registration BOOLEAN DEFAULT false,
 *     created_at TIMESTAMPTZ DEFAULT NOW(),
 *     CONSTRAINT unique_site_month UNIQUE(site_id, month)
 * );
 *
 * -- Indexes for faster queries
 * CREATE INDEX idx_analytics_month ON funeral_analytics(month);
 * CREATE INDEX idx_analytics_site ON funeral_analytics(site_id);
 * CREATE INDEX idx_analytics_region ON funeral_analytics(region);
 * CREATE INDEX idx_analytics_client ON funeral_analytics(client);
 * CREATE INDEX idx_analytics_created ON funeral_analytics(created_at);
 *
 * -- Enable Row Level Security
 * ALTER TABLE funeral_analytics ENABLE ROW LEVEL SECURITY;
 *
 * -- Policy: Allow INSERT for anon key (sites can submit data)
 * CREATE POLICY "Allow anonymous inserts" ON funeral_analytics
 *     FOR INSERT
 *     TO anon
 *     WITH CHECK (true);
 *
 * -- Policy: Allow authenticated users to read (Weave team via dashboard)
 * CREATE POLICY "Allow authenticated reads" ON funeral_analytics
 *     FOR SELECT
 *     TO authenticated
 *     USING (true);
 * ```
 *
 * @since 2.3.0
 */
class SupabaseService {

    /**
     * Supabase Project URL
     *
     * Replace with your Supabase project URL from:
     * Supabase Dashboard → Settings → API → Project URL
     */
    private const SUPABASE_URL = 'https://mvytehnjolkkevaobmxc.supabase.co';

    /**
     * Supabase Anon Key (Public)
     *
     * This is SAFE to hardcode - it's designed to be public.
     * Row Level Security (RLS) controls what it can access.
     *
     * Replace with your anon key from:
     * Supabase Dashboard → Settings → API → Project API keys → anon (public)
     */
    private const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im12eXRlaG5qb2xra2V2YW9ibXhjIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTkzNjU3NjEsImV4cCI6MjA3NDk0MTc2MX0.uBa_TGaDu0MzFUqg9kN7uyVePxSMJHBmfLAt7y5IU_Q';

    /**
     * Send statistics to Supabase
     *
     * Inserts a new row into the funeral_analytics table.
     * Uses upsert to handle duplicate month entries (updates existing).
     *
     * @param array $payload Analytics data payload
     * @return bool Success status
     * @throws \Exception On API errors
     */
    public function send_stats(array $payload): bool {
        // Validate configuration
        if (self::SUPABASE_URL === 'YOUR_SUPABASE_PROJECT_URL' ||
            self::SUPABASE_ANON_KEY === 'YOUR_SUPABASE_ANON_KEY') {
            error_log('WFN Analytics: Supabase not configured yet. Please update SupabaseService constants.');
            return false;
        }

        // Prepare row data for Supabase
        $row_data = $this->format_row_data($payload);

        // Send to Supabase
        return $this->insert_row($row_data);
    }

    /**
     * Format payload data into Supabase table row format
     *
     * @param array $payload Analytics payload from AnalyticsModule
     * @return array Row data ready for Supabase
     */
    private function format_row_data(array $payload): array {
        $metrics = $payload['metrics'] ?? [];
        $registration = $payload['registration'] ?? null;

        // Build row matching table schema
        $row = [
            'site_id' => $payload['site_id'] ?? '',
            'month' => $payload['month'] ?? date('Y-m'),

            // Registration data (only present on first send)
            'site_name' => $registration['site_name'] ?? null,
            'client' => $registration['client'] ?? null,
            'region' => $registration['region'] ?? null,
            'timezone' => $registration['timezone'] ?? null,
            'is_first_registration' => ($registration !== null),

            // Metrics (counts only, no personal data)
            'new_funerals' => $metrics['new_funerals'] ?? 0,
            'with_streaming' => $metrics['with_streaming'] ?? 0,
            'streaming_percentage' => $metrics['streaming_percentage'] ?? 0.0,
            'total_venues' => $metrics['total_venues'] ?? 0,
            'total_funerals_all_time' => $metrics['total_funerals_all_time'] ?? 0,

            // Technical info
            'plugin_version' => $metrics['plugin_version'] ?? '',
            'wp_version' => $metrics['wp_version'] ?? '',
            'php_version' => $metrics['php_version'] ?? '',
        ];

        return $row;
    }

    /**
     * Insert row into Supabase table
     *
     * Uses Supabase REST API with upsert to handle duplicates.
     * If a row for this site_id + month already exists, it updates.
     *
     * @param array $row_data Row data to insert
     * @return bool Success status
     * @throws \Exception On API errors
     */
    private function insert_row(array $row_data): bool {
        $api_url = self::SUPABASE_URL . '/rest/v1/funeral_analytics';

        $response = wp_remote_post($api_url, [
            'headers' => [
                'apikey' => self::SUPABASE_ANON_KEY,
                'Authorization' => 'Bearer ' . self::SUPABASE_ANON_KEY,
                'Content-Type' => 'application/json',
                'Prefer' => 'resolution=merge-duplicates', // Upsert on conflict
            ],
            'body' => json_encode($row_data),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Supabase API request failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Supabase returns 201 (Created) on success
        if ($status_code === 201 || $status_code === 200) {
            return true;
        }

        // Log error details
        throw new \Exception('Supabase API error (HTTP ' . $status_code . '): ' . $body);
    }

    /**
     * Test the Supabase connection
     *
     * Useful for debugging configuration issues.
     * Attempts to query the table to verify credentials and permissions.
     *
     * @return array Test results with status and message
     */
    public function test_connection(): array {
        try {
            // Check if configured
            if (self::SUPABASE_URL === 'YOUR_SUPABASE_PROJECT_URL' ||
                self::SUPABASE_ANON_KEY === 'YOUR_SUPABASE_ANON_KEY') {
                return [
                    'success' => false,
                    'message' => 'Supabase not configured. Please update SUPABASE_URL and SUPABASE_ANON_KEY constants in SupabaseService.php',
                ];
            }

            // Test 1: Can we reach the API?
            $api_url = self::SUPABASE_URL . '/rest/v1/funeral_analytics?limit=1';

            $response = wp_remote_get($api_url, [
                'headers' => [
                    'apikey' => self::SUPABASE_ANON_KEY,
                    'Authorization' => 'Bearer ' . self::SUPABASE_ANON_KEY,
                ],
                'timeout' => 10,
            ]);

            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $response->get_error_message(),
                ];
            }

            $status_code = wp_remote_retrieve_response_code($response);

            // 200 = Success (can read)
            // 406 = RLS blocking read (expected with anon key, but API is working)
            if ($status_code === 200 || $status_code === 406) {
                return [
                    'success' => true,
                    'message' => 'Supabase connection successful! ✓',
                    'note' => $status_code === 406 ? 'RLS is blocking reads (expected - anon key can only insert)' : 'Full access working',
                ];
            }

            // Other error
            $body = wp_remote_retrieve_body($response);
            return [
                'success' => false,
                'message' => 'API error (HTTP ' . $status_code . '): ' . $body,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get recent analytics data (for testing/debugging)
     *
     * NOTE: This will only work if you're authenticated via Supabase dashboard
     * or if you temporarily allow SELECT for anon key.
     *
     * @param int $limit Number of rows to fetch
     * @return array|null Analytics data or null on error
     */
    public function get_recent_stats(int $limit = 10): ?array {
        $api_url = self::SUPABASE_URL . '/rest/v1/funeral_analytics?order=created_at.desc&limit=' . $limit;

        $response = wp_remote_get($api_url, [
            'headers' => [
                'apikey' => self::SUPABASE_ANON_KEY,
                'Authorization' => 'Bearer ' . self::SUPABASE_ANON_KEY,
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
