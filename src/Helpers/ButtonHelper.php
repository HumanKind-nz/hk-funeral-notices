<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Helpers;

/**
 * Button Helper
 * Provides consistent button generation across all templates
 * 
 * @since 2.0.0
 */
class ButtonHelper {

    /**
     * Generate a primary button (e.g., Send Tribute, Download Service Sheet)
     */
    public static function primary_button(string $text, string $url, array $attributes = []): string {
        $default_attributes = [
            'class' => 'wfn-btn wfn-btn-primary',
            'target' => '_self'
        ];
        
        $attributes = array_merge($default_attributes, $attributes);
        $attr_string = self::build_attributes($attributes);
        
        return sprintf(
            '<a href="%s" %s>%s</a>',
            esc_url($url),
            $attr_string,
            esc_html($text)
        );
    }

    /**
     * Generate a secondary button (e.g., View Map, Share)
     */
    public static function secondary_button(string $text, string $url, array $attributes = []): string {
        $default_attributes = [
            'class' => 'wfn-btn wfn-btn-secondary',
            'target' => '_self'
        ];
        
        $attributes = array_merge($default_attributes, $attributes);
        $attr_string = self::build_attributes($attributes);
        
        return sprintf(
            '<a href="%s" %s>%s</a>',
            esc_url($url),
            $attr_string,
            esc_html($text)
        );
    }

    /**
     * Generate a download button for service documents
     */
    public static function download_button(string $text, string $file_url, array $attributes = []): string {
        $default_attributes = [
            'class' => 'wfn-btn wfn-btn-primary wfn-download-btn',
            'target' => '_blank',
            'download' => ''
        ];
        
        $attributes = array_merge($default_attributes, $attributes);
        $attr_string = self::build_attributes($attributes);
        
        return sprintf(
            '<a href="%s" %s><span class="dashicons dashicons-download"></span> %s</a>',
            esc_url($file_url),
            $attr_string,
            esc_html($text)
        );
    }

    /**
     * Generate a card action button (e.g., View Details on modern cards)
     */
    public static function card_action_button(string $text, string $url, array $attributes = []): string {
        $default_attributes = [
            'class' => 'wfn-card-action wfn-btn wfn-btn-primary',
            'target' => '_self'
        ];
        
        $attributes = array_merge($default_attributes, $attributes);
        $attr_string = self::build_attributes($attributes);
        
        return sprintf(
            '<a href="%s" %s>%s</a>',
            esc_url($url),
            $attr_string,
            esc_html($text)
        );
    }

    /**
     * Generate a tribute/message button
     */
    public static function tribute_button(string $text, string $url, array $attributes = []): string {
        $default_attributes = [
            'class' => 'wfn-btn wfn-btn-primary wfn-tribute-btn',
            'target' => '_blank'
        ];
        
        $attributes = array_merge($default_attributes, $attributes);
        $attr_string = self::build_attributes($attributes);
        
        return sprintf(
            '<a href="%s" %s><span class="dashicons dashicons-heart"></span> %s</a>',
            esc_url($url),
            $attr_string,
            esc_html($text)
        );
    }

    /**
     * Generate an edit button for admin users
     */
    public static function edit_button(int $post_id, array $attributes = []): string {
        if (!current_user_can('edit_post', $post_id)) {
            return '';
        }

        $edit_url = get_edit_post_link($post_id);
        if (!$edit_url) {
            return '';
        }

        $default_attributes = [
            'class' => 'wfn-btn wfn-btn-secondary wfn-edit-btn',
            'target' => '_blank'
        ];
        
        $attributes = array_merge($default_attributes, $attributes);
        $attr_string = self::build_attributes($attributes);
        
        return sprintf(
            '<a href="%s" %s><span class="dashicons dashicons-edit"></span> Edit</a>',
            esc_url($edit_url),
            $attr_string
        );
    }

    /**
     * Build HTML attributes string from array
     */
    private static function build_attributes(array $attributes): string {
        $attr_parts = [];
        
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            
            if ($value === true) {
                $attr_parts[] = esc_attr($key);
            } else {
                $attr_parts[] = sprintf('%s="%s"', esc_attr($key), esc_attr($value));
            }
        }
        
        return implode(' ', $attr_parts);
    }

    /**
     * Get customizable button text from styling options
     */
    public static function get_button_text(string $key, string $default = ''): string {
        $text_settings = get_option('wfn_styling_text', []);
        return $text_settings[$key] ?? $default;
    }
} 