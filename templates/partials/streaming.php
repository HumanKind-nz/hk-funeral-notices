<?php
/**
 * Streaming Partial Template  
 * Replaces Tangible Template: [WFN] Streaming
 * 
 * Shows streaming information for archive pages
 * 
 * @var int $post_id
 * @var array $args
 * @var string $mode
 */

// Get template manager instance to fetch structured data
$template_manager = new \WeaveStudios\FuneralNotices\Templates\TemplateManager();
$data = $template_manager->get_funeral_data($post_id);

// Only show if there's streaming
if (!$data['streaming']['has_streaming']) {
    return;
}

// Handle private streaming
if ($data['streaming']['is_private']): ?>
    <h5 class="details">REQUEST STREAMING</h5>
    <div class="stream">
        <a href="/web-streaming/?tribute=<?php echo urlencode($data['person']['full_name']); ?>" target="_blank" rel="noopener">
            <span class="fn_icon-streaming"></span>Request Access
        </a>
    </div>

<?php elseif ($data['streaming']['is_public']): ?>
    <h5 class="details">LIVE STREAM</h5>
    <div class="stream">
        <a href="<?php echo esc_url($data['post_url']); ?>#streaming-row" rel="noopener">
            <span class="fn_icon-streaming"></span>View Live Stream
        </a>
    </div>

<?php endif; ?> 