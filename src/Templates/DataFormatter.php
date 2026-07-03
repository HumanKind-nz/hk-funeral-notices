<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Templates;

/**
 * Data Formatter
 * 
 * Maps complex ACF data structure to simple template variables
 * Bridges ACF complexity with FCRM-style template simplicity
 * 
 * @package HumanKind\FuneralNotices\Templates
 * @since 2.0.0
 */
class DataFormatter {

    private TemplateManager $template_manager;

    public function __construct(TemplateManager $template_manager) {
        $this->template_manager = $template_manager;
    }

    /**
     * Format funeral data for FCRM-style templates
     * 
     * Takes complex ACF data and converts to simple template variables
     * matching FCRM Enhancement Suite structure
     */
    public function format_funeral_for_template(int $post_id): array {
        // Get existing complex ACF data
        $funeral_data = $this->template_manager->get_funeral_data($post_id);
        
        return [
            // Basic Information
            'id' => $post_id,
            'fullName' => $this->format_full_name($funeral_data),
            'firstName' => $funeral_data['first_name'] ?? '',
            'lastName' => $funeral_data['last_name'] ?? '',
            'title' => $funeral_data['title'] ?? '',
            'displayName' => $this->format_display_name($funeral_data),
            
            // Dates
            'birthDate' => $funeral_data['birth_date'] ?? '',
            'deathDate' => $funeral_data['death_date'] ?? '',
            'birthYear' => $funeral_data['birth_year'] ?? '',
            'deathYear' => $funeral_data['death_year'] ?? '',
            'ageAtDeath' => $this->calculate_age($funeral_data),
            'lifeSpan' => $this->format_life_span($funeral_data),
            
            // Service Information
            'serviceDateTime' => $funeral_data['funeral_date'] ?? '',
            'serviceDateTimeFormatted' => $this->format_service_date($funeral_data),
            'serviceDate' => $this->format_service_date_only($funeral_data),
            'serviceTime' => $this->format_service_time($funeral_data),
            'serviceLocation' => $this->format_service_location($funeral_data),
            'serviceFacility' => $funeral_data['facility'] ?? '',
            'serviceAddress' => $funeral_data['address'] ?? '',
            'serviceCity' => $funeral_data['city'] ?? '',
            'serviceRegion' => $funeral_data['region'] ?? '',
            
            // Images
            'displayImage' => $funeral_data['featured_image_url'] ?? '',
            'thumbnailImage' => $this->get_thumbnail_image($post_id),
            'hasImage' => !empty($funeral_data['featured_image_url']),
            'imageAlt' => $this->format_image_alt($funeral_data),
            
            // Links
            'permalink' => get_permalink($post_id),
            'readMoreUrl' => get_permalink($post_id),
            'editUrl' => $this->get_edit_url($post_id),
            
            // Streaming
            'streamingUrl' => $funeral_data['streaming_url'] ?? '',
            'streamingService' => $funeral_data['streaming_service'] ?? '',
            'hasStreaming' => !empty($funeral_data['streaming_url']),
            'streamingLabel' => $this->format_streaming_label($funeral_data),
            
            // Location & Contact
            'locationDisplay' => $funeral_data['location_display'] ?? '',
            'contactPhone' => $funeral_data['contact_phone'] ?? '',
            'contactEmail' => $funeral_data['contact_email'] ?? '',
            'organizerName' => $funeral_data['organizer_name'] ?? '',
            
            // Content
            'excerpt' => $this->format_excerpt($post_id),
            'description' => $funeral_data['description'] ?? '',
            'biography' => $funeral_data['biography'] ?? '',
            'tribute' => $funeral_data['tribute'] ?? '',
            'hasContent' => $this->has_content($funeral_data),
            
            // Meta Information
            'postType' => get_post_type($post_id),
            'publishedDate' => get_the_date('Y-m-d H:i:s', $post_id),
            'modifiedDate' => get_the_modified_date('Y-m-d H:i:s', $post_id),
            'postStatus' => get_post_status($post_id),
            
            // Categories/Tags (if used)
            'categories' => $this->get_funeral_categories($post_id),
            'tags' => $this->get_funeral_tags($post_id),
            
            // Additional ACF fields (preserve all existing)
            'acf' => $funeral_data, // Keep full ACF data available
            
            // Display helpers
            'cssClasses' => $this->generate_css_classes($funeral_data),
            'dataAttributes' => $this->generate_data_attributes($funeral_data),
            
            // Time-based helpers
            'isUpcoming' => $this->is_upcoming_service($funeral_data),
            'isPast' => $this->is_past_service($funeral_data),
            'isToday' => $this->is_today_service($funeral_data),
            'timeUntilService' => $this->time_until_service($funeral_data),
            'relativeServiceTime' => $this->relative_service_time($funeral_data),
            
            // Search helpers
            'searchableText' => $this->generate_searchable_text($funeral_data),
            'keywords' => $this->extract_keywords($funeral_data),
        ];
    }

    /**
     * Format full name with proper handling of titles
     */
    private function format_full_name(array $funeral_data): string {
        $parts = [];
        
        if (!empty($funeral_data['title'])) {
            $parts[] = $funeral_data['title'];
        }
        
        if (!empty($funeral_data['first_name'])) {
            $parts[] = $funeral_data['first_name'];
        }
        
        if (!empty($funeral_data['last_name'])) {
            $parts[] = $funeral_data['last_name'];
        }
        
        return implode(' ', $parts);
    }

    /**
     * Format display name (can be different from full name)
     */
    private function format_display_name(array $funeral_data): string {
        // Use display name if explicitly set, otherwise use full name
        if (!empty($funeral_data['display_name'])) {
            return $funeral_data['display_name'];
        }
        
        return $this->format_full_name($funeral_data);
    }

    /**
     * Calculate age at death
     */
    private function calculate_age(array $funeral_data): ?int {
        $birth_date = $funeral_data['birth_date'] ?? '';
        $death_date = $funeral_data['death_date'] ?? '';
        
        if (empty($birth_date) || empty($death_date)) {
            return null;
        }
        
        try {
            $birth = new \DateTime($birth_date);
            $death = new \DateTime($death_date);
            return $birth->diff($death)->y;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format life span (1950 - 2023)
     */
    private function format_life_span(array $funeral_data): string {
        $birth_year = $funeral_data['birth_year'] ?? '';
        $death_year = $funeral_data['death_year'] ?? '';
        
        if (empty($birth_year) && empty($death_year)) {
            return '';
        }
        
        if (empty($birth_year)) {
            return '- ' . $death_year;
        }
        
        if (empty($death_year)) {
            return $birth_year . ' -';
        }
        
        return $birth_year . ' - ' . $death_year;
    }

    /**
     * Format service date and time
     */
    private function format_service_date(array $funeral_data): string {
        $date = $funeral_data['funeral_date'] ?? '';
        
        if (empty($date)) {
            return '';
        }
        
        try {
            $datetime = new \DateTime($date);
            return $datetime->format('l, F j, Y \a\t g:i A');
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Format service date only
     */
    private function format_service_date_only(array $funeral_data): string {
        $date = $funeral_data['funeral_date'] ?? '';
        
        if (empty($date)) {
            return '';
        }
        
        try {
            $datetime = new \DateTime($date);
            return $datetime->format('F j, Y');
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Format service time only
     */
    private function format_service_time(array $funeral_data): string {
        $date = $funeral_data['funeral_date'] ?? '';
        
        if (empty($date)) {
            return '';
        }
        
        try {
            $datetime = new \DateTime($date);
            return $datetime->format('g:i A');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format service location
     */
    private function format_service_location(array $funeral_data): string {
        // Use existing location_display if available
        if (!empty($funeral_data['location_display'])) {
            return $funeral_data['location_display'];
        }
        
        // Otherwise build from components
        $parts = [];
        
        if (!empty($funeral_data['facility'])) {
            $parts[] = $funeral_data['facility'];
        }
        
        if (!empty($funeral_data['address'])) {
            $parts[] = $funeral_data['address'];
        }
        
        if (!empty($funeral_data['city'])) {
            $parts[] = $funeral_data['city'];
        }
        
        return implode(', ', $parts);
    }

    /**
     * Get thumbnail image
     */
    private function get_thumbnail_image(int $post_id): string {
        $thumbnail = get_the_post_thumbnail_url($post_id, 'thumbnail');
        return $thumbnail ?: '';
    }

    /**
     * Format image alt text
     */
    private function format_image_alt(array $funeral_data): string {
        $name = $this->format_full_name($funeral_data);
        return !empty($name) ? "Photo of {$name}" : 'Funeral notice photo';
    }

    /**
     * Get edit URL for admin users
     */
    private function get_edit_url(int $post_id): string {
        if (current_user_can('edit_post', $post_id)) {
            return get_edit_post_link($post_id) ?: '';
        }
        return '';
    }

    /**
     * Format streaming label
     */
    private function format_streaming_label(array $funeral_data): string {
        $service = $funeral_data['streaming_service'] ?? '';
        
        if (!empty($service)) {
            return "Watch on {$service}";
        }
        
        return 'Watch Online';
    }

    /**
     * Format excerpt
     */
    private function format_excerpt(int $post_id): string {
        $excerpt = get_the_excerpt($post_id);
        
        if (empty($excerpt)) {
            // Generate from content
            $content = get_post_field('post_content', $post_id);
            $excerpt = wp_trim_words(strip_tags($content), 30);
        }
        
        return $excerpt;
    }

    /**
     * Check if has content
     */
    private function has_content(array $funeral_data): bool {
        $content_fields = ['description', 'biography', 'tribute'];
        
        foreach ($content_fields as $field) {
            if (!empty($funeral_data[$field])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get funeral categories
     */
    private function get_funeral_categories(int $post_id): array {
        $terms = wp_get_post_terms($post_id, 'funeral_category');
        
        if (is_wp_error($terms)) {
            return [];
        }
        
        return array_map(function($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'url' => get_term_link($term)
            ];
        }, $terms);
    }

    /**
     * Get funeral tags
     */
    private function get_funeral_tags(int $post_id): array {
        $terms = wp_get_post_terms($post_id, 'funeral_tag');
        
        if (is_wp_error($terms)) {
            return [];
        }
        
        return array_map(function($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'url' => get_term_link($term)
            ];
        }, $terms);
    }

    /**
     * Generate CSS classes
     */
    private function generate_css_classes(array $funeral_data): string {
        $classes = ['funeral-notice'];
        
        if (!empty($funeral_data['featured_image_url'])) {
            $classes[] = 'has-image';
        } else {
            $classes[] = 'no-image';
        }
        
        if (!empty($funeral_data['streaming_url'])) {
            $classes[] = 'has-streaming';
        }
        
        if ($this->is_upcoming_service($funeral_data)) {
            $classes[] = 'upcoming-service';
        } elseif ($this->is_past_service($funeral_data)) {
            $classes[] = 'past-service';
        }
        
        return implode(' ', $classes);
    }

    /**
     * Generate data attributes
     */
    private function generate_data_attributes(array $funeral_data): array {
        return [
            'data-funeral-id' => $funeral_data['id'] ?? '',
            'data-service-date' => $funeral_data['funeral_date'] ?? '',
            'data-has-streaming' => !empty($funeral_data['streaming_url']) ? 'true' : 'false',
            'data-location' => $funeral_data['city'] ?? ''
        ];
    }

    /**
     * Check if service is upcoming
     */
    private function is_upcoming_service(array $funeral_data): bool {
        $service_date = $funeral_data['funeral_date'] ?? '';
        
        if (empty($service_date)) {
            return false;
        }
        
        try {
            $service = new \DateTime($service_date);
            $now = new \DateTime();
            return $service > $now;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if service is in the past
     */
    private function is_past_service(array $funeral_data): bool {
        $service_date = $funeral_data['funeral_date'] ?? '';
        
        if (empty($service_date)) {
            return false;
        }
        
        try {
            $service = new \DateTime($service_date);
            $now = new \DateTime();
            return $service < $now;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if service is today
     */
    private function is_today_service(array $funeral_data): bool {
        $service_date = $funeral_data['funeral_date'] ?? '';
        
        if (empty($service_date)) {
            return false;
        }
        
        try {
            $service = new \DateTime($service_date);
            $today = new \DateTime();
            return $service->format('Y-m-d') === $today->format('Y-m-d');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Time until service
     */
    private function time_until_service(array $funeral_data): string {
        $service_date = $funeral_data['funeral_date'] ?? '';
        
        if (empty($service_date)) {
            return '';
        }
        
        try {
            $service = new \DateTime($service_date);
            $now = new \DateTime();
            $diff = $now->diff($service);
            
            if ($service < $now) {
                return 'Service has passed';
            }
            
            if ($diff->days > 0) {
                return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' until service';
            }
            
            if ($diff->h > 0) {
                return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' until service';
            }
            
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' until service';
            
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Relative service time
     */
    private function relative_service_time(array $funeral_data): string {
        $service_date = $funeral_data['funeral_date'] ?? '';
        
        if (empty($service_date)) {
            return '';
        }
        
        try {
            $service = new \DateTime($service_date);
            return human_time_diff($service->getTimestamp(), current_time('timestamp'));
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Generate searchable text
     */
    private function generate_searchable_text(array $funeral_data): string {
        $searchable = [];
        
        $searchable[] = $this->format_full_name($funeral_data);
        $searchable[] = $funeral_data['description'] ?? '';
        $searchable[] = $funeral_data['biography'] ?? '';
        $searchable[] = $funeral_data['facility'] ?? '';
        $searchable[] = $funeral_data['city'] ?? '';
        
        return implode(' ', array_filter($searchable));
    }

    /**
     * Extract keywords
     */
    private function extract_keywords(array $funeral_data): array {
        $keywords = [];
        
        $keywords[] = $this->format_full_name($funeral_data);
        
        if (!empty($funeral_data['city'])) {
            $keywords[] = $funeral_data['city'];
        }
        
        if (!empty($funeral_data['facility'])) {
            $keywords[] = $funeral_data['facility'];
        }
        
        return array_filter($keywords);
    }
} 