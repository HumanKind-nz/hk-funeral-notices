<?php
/**
 * Short Funeral Notice Partial Template
 * Replaces Tangible Template: [WFN] Funeral Notice (short)
 * 
 * Shows truncated funeral notice content for archive pages
 * 
 * @var int $post_id
 * @var array $args
 * @var string $mode
 */

// Get template manager instance to fetch structured data
$template_manager = new \HumanKind\FuneralNotices\Templates\TemplateManager();
$data = $template_manager->get_funeral_data($post_id);

if ($data['content']['notice']): 
    // Strip HTML tags and limit to 360 characters (matching Tangible Template)
    $excerpt = wp_trim_words(strip_tags($data['content']['notice']), 60);
    if (strlen($excerpt) > 360) {
        $excerpt = substr($excerpt, 0, 360) . '...';
    }
    echo wp_kses_post($excerpt);
endif; ?> 