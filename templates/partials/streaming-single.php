<?php
/**
 * Streaming Single Partial Template
 * Replaces Tangible Template: [WFN] Streaming - Single
 * 
 * Shows streaming information for single funeral pages
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
        <?php if ($data['streaming']['streaming_note']): ?>
            <p class="streaming-note"><?php echo esc_html($data['streaming']['streaming_note']); ?></p>
        <?php endif; ?>
    </div>

<?php elseif ($data['streaming']['is_public']): ?>
    <h5 class="details">LIVE STREAM</h5>
    <div class="stream">
        <?php if ($data['streaming']['can_embed']): ?>
            <!-- Inline embed for recognised services -->
            <?php echo $data['streaming']['embed_code']; ?>
            <div class="streaming-actions">
                <a href="<?php echo esc_url($data['streaming']['streaming_url']); ?>" target="_blank" rel="noopener" class="view-external">
                    View in new window
                </a>
            </div>
        <?php else: ?>
            <!-- Button for unrecognised services -->
            <a href="<?php echo esc_url($data['streaming']['streaming_url']); ?>" target="_blank" rel="noopener" class="stream-button">
                <span class="fn_icon-streaming"></span>View Live Stream
            </a>
        <?php endif; ?>
        
        <?php if ($data['streaming']['streaming_note']): ?>
            <p class="streaming-note"><?php echo esc_html($data['streaming']['streaming_note']); ?></p>
        <?php endif; ?>
    </div>

<?php endif; ?> 