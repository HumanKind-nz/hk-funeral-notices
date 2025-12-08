<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Streaming;

/**
 * Streaming Service Detector
 * Automatically detects streaming services from URLs and generates embeds
 * 
 * @since 2.0.0
 */
class StreamingDetector {

    /**
     * Detect streaming service from URL
     */
    public function detect_service(string $url): array {
        $url = trim($url);
        
        if (empty($url)) {
            return ['service' => 'none', 'embed' => '', 'url' => ''];
        }

        // YouTube detection
        if ($this->is_youtube_url($url)) {
            $video_id = $this->extract_youtube_id($url);
            if ($video_id) {
                $thumbnail_url = '';
                if ($video_id !== 'LIVE_URL') {
                    $thumbnail_url = "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
                }
                
                return [
                    'service' => 'youtube',
                    'video_id' => $video_id,
                    'embed' => $this->generate_youtube_embed($video_id, $url),
                    'url' => $url,
                    'thumbnail' => $thumbnail_url
                ];
            }
        }

        // OneRoom detection
        if ($this->is_oneroom_url($url)) {
            return [
                'service' => 'oneroom',
                'embed' => $this->generate_oneroom_embed($url),
                'url' => $url,
                'thumbnail' => ''
            ];
        }

        // iStream and similar streaming page services (button-only)
        if ($this->is_istream_url($url)) {
            return [
                'service' => 'istream',
                'embed' => $this->generate_generic_link($url, 'iStream'),
                'url' => $url,
                'thumbnail' => ''
            ];
        }

        // Vimeo Pro detection (button-only, no embed)
        if ($this->is_vimeo_pro_url($url)) {
            return [
                'service' => 'vimeo_pro',
                'embed' => $this->generate_generic_link($url, 'Vimeo'),
                'url' => $url,
                'thumbnail' => ''
            ];
        }

        // Regular Vimeo detection (embeddable)
        if ($this->is_vimeo_url($url)) {
            $video_id = $this->extract_vimeo_id($url);
            if ($video_id) {
                return [
                    'service' => 'vimeo',
                    'video_id' => $video_id,
                    'embed' => $this->generate_vimeo_embed($video_id, $url),
                    'url' => $url,
                    'thumbnail' => $this->get_vimeo_thumbnail($video_id)
                ];
            }
        }

        // Facebook Live detection
        if ($this->is_facebook_url($url)) {
            return [
                'service' => 'facebook',
                'embed' => $this->generate_facebook_embed($url),
                'url' => $url,
                'thumbnail' => ''
            ];
        }

        // Generic/other service
        return [
            'service' => 'other',
            'embed' => $this->generate_generic_link($url),
            'url' => $url,
            'thumbnail' => ''
        ];
    }

    /**
     * Check if URL is YouTube
     */
    private function is_youtube_url(string $url): bool {
        return (bool) preg_match('/(?:youtube\.com|youtu\.be)/i', $url);
    }

    /**
     * Extract YouTube video ID - handles all YouTube URL formats
     */
    private function extract_youtube_id(string $url): ?string {
        $patterns = [
            // Standard watch URLs
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
            // Live URLs
            '/youtube\.com\/live\/([a-zA-Z0-9_-]{11})/',
            // Embed URLs
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            // Channel live URLs
            '/youtube\.com\/channel\/[^\/]+\/live/',
            // User live URLs
            '/youtube\.com\/user\/[^\/]+\/live/',
            // Handle @username format
            '/youtube\.com\/@[^\/]+\/live/',
            // Mobile URLs
            '/m\.youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1] ?? null;
            }
        }

        // Special handling for live URLs without video ID - use the URL as-is
        if (preg_match('/youtube\.com\/.*(live|stream)/i', $url)) {
            // Return a special marker for live URLs
            return 'LIVE_URL';
        }

        return null;
    }

    /**
     * Generate YouTube embed code
     */
    private function generate_youtube_embed(string $video_id, string $original_url = ''): string {
        // Handle special live URLs that don't have extractable video IDs
        if ($video_id === 'LIVE_URL') {
            // For live URLs without video ID, create a button link
            return $this->generate_generic_link($original_url, 'YouTube Live');
        }

        $embed_url = "https://www.youtube.com/watch?v=" . $video_id;

        return sprintf(
            '<div class="wfn-video-embed wfn-youtube-embed">' .
            '<iframe src="https://www.youtube.com/embed/%s?rel=0&modestbranding=1" ' .
            'width="100%%" height="450" frameborder="0" allowfullscreen ' .
            'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">' .
            '</iframe>' .
            '<div class="wfn-streaming-actions" style="margin-top: 0.75rem;">' .
            '<a href="%s" target="_blank" rel="noopener" class="wfn-view-external">Open in YouTube</a>' .
            '</div>' .
            '</div>',
            esc_attr($video_id),
            esc_url($embed_url)
        );
    }

    /**
     * Check if URL is OneRoom
     */
    private function is_oneroom_url(string $url): bool {
        return (bool) preg_match('/view\.oneroomstreaming\.com/i', $url);
    }

    /**
     * Generate OneRoom embed code
     */
    private function generate_oneroom_embed(string $url): string {
        return sprintf(
            '<div class="wfn-video-embed wfn-oneroom-embed">' .
            '<iframe class="livestream-video" src="%s" ' .
            'width="100%%" height="1000" frameborder="0" ' .
            'allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true" ' .
            'style="border:none; height: 1000px;">' .
            '</iframe></div>',
            esc_url($url)
        );
    }

    /**
     * Check if URL is Vimeo
     */
    private function is_vimeo_url(string $url): bool {
        return (bool) preg_match('/vimeo\.com/i', $url);
    }

    /**
     * Check if URL is Vimeo Pro (button-only, no embed)
     */
    private function is_vimeo_pro_url(string $url): bool {
        // Vimeo Pro typically uses private/protected URLs that can't be embedded directly
        return (bool) preg_match('/vimeo\.com\/.*\/(.*\?h=|.*private)/i', $url) ||
               (bool) preg_match('/player\.vimeo\.com\/video\/.*\?h=/i', $url);
    }

    /**
     * Check if URL is iStream or similar streaming page service
     */
    private function is_istream_url(string $url): bool {
        return (bool) preg_match('/(istream\.co\.nz|streamingfunerals\.)/i', $url);
    }

    /**
     * Extract Vimeo video ID
     */
    private function extract_vimeo_id(string $url): ?string {
        $patterns = [
            '/vimeo\.com\/(\d+)/',
            '/vimeo\.com\/video\/(\d+)/',
            '/player\.vimeo\.com\/video\/(\d+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Generate Vimeo embed code
     * Handles both regular and privacy-protected Vimeo URLs
     */
    private function generate_vimeo_embed(string $video_id, string $original_url = ''): string {
        // Extract privacy hash if present in URL
        // Vimeo privacy URLs can be:
        // - vimeo.com/VIDEO_ID/HASH
        // - vimeo.com/VIDEO_ID?h=HASH
        // - player.vimeo.com/video/VIDEO_ID?h=HASH

        $privacy_hash = '';

        // Check for hash in path (e.g., /1142878313/dc414dd32c)
        if (preg_match('/vimeo\.com\/\d+\/([a-zA-Z0-9]+)/', $original_url, $matches)) {
            $privacy_hash = $matches[1];
        }
        // Check for hash in query parameter (e.g., ?h=dc414dd32c)
        elseif (preg_match('/[?&]h=([a-zA-Z0-9]+)/', $original_url, $matches)) {
            $privacy_hash = $matches[1];
        }

        // Build iframe src with privacy hash if present
        $iframe_src = "https://player.vimeo.com/video/{$video_id}";
        $iframe_params = ['title' => '0', 'byline' => '0', 'portrait' => '0'];

        if (!empty($privacy_hash)) {
            $iframe_params['h'] = $privacy_hash;
        }

        $iframe_src .= '?' . http_build_query($iframe_params);

        // Use original URL for button if available, otherwise reconstruct
        $vimeo_url = !empty($original_url) ? $original_url : "https://vimeo.com/" . $video_id;

        return sprintf(
            '<div class="wfn-video-embed wfn-vimeo-embed">' .
            '<iframe src="%s" ' .
            'width="100%%" height="450" frameborder="0" allowfullscreen ' .
            'allow="autoplay; fullscreen; picture-in-picture">' .
            '</iframe>' .
            '<div class="wfn-streaming-actions" style="margin-top: 0.75rem;">' .
            '<a href="%s" target="_blank" rel="noopener" class="wfn-view-external">Open in Vimeo</a>' .
            '</div>' .
            '</div>',
            esc_attr($iframe_src),
            esc_url($vimeo_url)
        );
    }

    /**
     * Get Vimeo thumbnail (requires API call)
     */
    private function get_vimeo_thumbnail(string $video_id): string {
        // For now, return empty - could implement Vimeo API call later
        return '';
    }

    /**
     * Check if URL is Facebook
     */
    private function is_facebook_url(string $url): bool {
        return (bool) preg_match('/facebook\.com/i', $url);
    }

    /**
     * Generate Facebook embed code
     */
    private function generate_facebook_embed(string $url): string {
        return sprintf(
            '<div class="wfn-video-embed wfn-facebook-embed">' .
            '<iframe src="https://www.facebook.com/plugins/video.php?href=%s&show_text=false&width=560" ' .
            'width="100%%" height="450" style="border:none;overflow:hidden" scrolling="no" frameborder="0" ' .
            'allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">' .
            '</iframe></div>',
            urlencode($url)
        );
    }

    /**
     * Generate generic link for unknown services
     */
    private function generate_generic_link(string $url, string $service_name = ''): string {
        $domain = parse_url($url, PHP_URL_HOST);
        $clean_domain = str_replace('www.', '', $domain ?? '');

        // Use provided service name or auto-detect from domain
        $display_name = $service_name ?: ucfirst($clean_domain);

        // Determine button text based on service
        $button_text = match(strtolower($display_name)) {
            'istream' => 'View on iStream',
            'vimeo' => 'Open in Vimeo',
            default => 'View in Browser'
        };

        // Load SVG icon function if not already loaded
        if (!function_exists('wfn_get_stream_icon')) {
            require_once plugin_dir_path(dirname(__FILE__, 2)) . 'templates/partials/svg-icons.php';
        }

        return sprintf(
            '<div class="wfn-stream-button-wrapper">' .
            '<a href="%s" target="_blank" rel="noopener" class="wfn-stream-btn">' .
            '%s %s' .
            '</a></div>',
            esc_url($url),
            wfn_get_stream_icon(),
            esc_html($button_text)
        );
    }

    /**
     * Render streaming content based on field data
     */
    public function render_streaming_content(array $streaming_data): string {
        if (empty($streaming_data)) {
            return '';
        }

        $streaming_type = $streaming_data['streaming_type'] ?? 'none';
        $is_private = $streaming_data['streaming_private'] ?? false;
        $note = $streaming_data['streaming_note'] ?? '';

        // Check privacy settings
        if ($is_private && !is_user_logged_in()) {
            return '<div class="wfn-streaming-private">Live streaming available for family and friends. Please log in to view.</div>';
        }

        $output = '';

        // Add streaming note if provided
        if (!empty($note)) {
            $output .= sprintf('<div class="wfn-streaming-note">%s</div>', esc_html($note));
        }

        switch ($streaming_type) {
            case 'oneroom':
                $oneroom_code = $streaming_data['oneroom_code'] ?? '';
                if (!empty($oneroom_code)) {
                    // Wrap OneRoom code in responsive container
                    $output .= '<div class="wfn-video-embed wfn-oneroom-embed">' . $oneroom_code . '</div>';
                }
                break;

            case 'url':
                $streaming_url = $streaming_data['streaming_url'] ?? '';
                if (!empty($streaming_url)) {
                    $detected = $this->detect_service($streaming_url);
                    $output .= $detected['embed'];
                }
                break;

            case 'none':
            default:
                return '';
        }

        if (!empty($output)) {
            return '<div class="wfn-streaming-section">' . $output . '</div>';
        }

        return '';
    }

    /**
     * Migrate old streaming data to new format
     */
    public function migrate_legacy_streaming_data(array $old_data): array {
        // Handle migration from old streaming_service field structure
        $streaming_service = $old_data['streaming_service'] ?? 'none';
        $oneroom_code = $old_data['oneroom_code'] ?? '';
        $streaming_url = $old_data['streaming_url'] ?? '';
        $is_private = $old_data['streaming_private'] ?? false;

        // Determine new streaming type based on old data
        if (!empty($oneroom_code)) {
            return [
                'streaming_type' => 'oneroom',
                'oneroom_code' => $oneroom_code,
                'streaming_private' => $is_private,
                'streaming_note' => '',
            ];
        } elseif (!empty($streaming_url)) {
            return [
                'streaming_type' => 'url',
                'streaming_url' => $streaming_url,
                'streaming_private' => $is_private,
                'streaming_note' => '',
            ];
        }

        return [
            'streaming_type' => 'none',
            'streaming_private' => false,
            'streaming_note' => '',
        ];
    }
} 