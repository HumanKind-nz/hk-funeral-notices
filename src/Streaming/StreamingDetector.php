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
                return [
                    'service' => 'youtube',
                    'video_id' => $video_id,
                    'embed' => $this->generate_youtube_embed($video_id),
                    'url' => $url,
                    'thumbnail' => "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg"
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

        // Vimeo detection
        if ($this->is_vimeo_url($url)) {
            $video_id = $this->extract_vimeo_id($url);
            if ($video_id) {
                return [
                    'service' => 'vimeo',
                    'video_id' => $video_id,
                    'embed' => $this->generate_vimeo_embed($video_id),
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
     * Extract YouTube video ID
     */
    private function extract_youtube_id(string $url): ?string {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Generate YouTube embed code
     */
    private function generate_youtube_embed(string $video_id): string {
        return sprintf(
            '<div class="wfn-video-embed wfn-youtube-embed">' .
            '<iframe src="https://www.youtube.com/embed/%s?rel=0&modestbranding=1" ' .
            'width="100%%" height="400" frameborder="0" allowfullscreen ' .
            'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">' .
            '</iframe></div>',
            esc_attr($video_id)
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
            'width="100%%" height="600" frameborder="0" ' .
            'allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true" ' .
            'style="border:none;">' .
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
     */
    private function generate_vimeo_embed(string $video_id): string {
        return sprintf(
            '<div class="wfn-video-embed wfn-vimeo-embed">' .
            '<iframe src="https://player.vimeo.com/video/%s?title=0&byline=0&portrait=0" ' .
            'width="100%%" height="400" frameborder="0" allowfullscreen ' .
            'allow="autoplay; fullscreen; picture-in-picture">' .
            '</iframe></div>',
            esc_attr($video_id)
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
            'width="100%%" height="400" style="border:none;overflow:hidden" scrolling="no" frameborder="0" ' .
            'allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">' .
            '</iframe></div>',
            urlencode($url)
        );
    }

    /**
     * Generate generic link for unknown services
     */
    private function generate_generic_link(string $url): string {
        $domain = parse_url($url, PHP_URL_HOST);
        $clean_domain = str_replace('www.', '', $domain ?? '');

        return sprintf(
            '<div class="wfn-streaming-link">' .
            '<a href="%s" target="_blank" rel="noopener noreferrer" class="wfn-stream-button">' .
            '<span class="wfn-stream-icon">📺</span> Watch Live Stream on %s' .
            '</a></div>',
            esc_url($url),
            esc_html(ucfirst($clean_domain))
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