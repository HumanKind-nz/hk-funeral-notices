<?php
/**
 * Date/Time Partial Template
 * Replaces Tangible Template: [WFN] Date / Time
 * 
 * Shows funeral date and time with conditional logic for hiding
 * 
 * @var int $post_id
 * @var array $args
 * @var string $mode
 */

try {
    // Get template manager instance to fetch structured data
    $template_manager = new \WeaveStudios\FuneralNotices\Templates\TemplateManager();
    $data = $template_manager->get_funeral_data($post_id);

    // Only show if time is not hidden and we have valid data
    if (isset($data['event']) && !$data['event']['hide_time']): ?>
        <h5 class="details">WHEN</h5>
        <div class="funeral_date">
            <span>
                <?php if ($data['event']['formatted_date']): ?>
                    <?php echo esc_html($data['event']['formatted_date']); ?>
                    <?php if ($data['event']['formatted_time']): ?>
                        at <?php echo esc_html($data['event']['formatted_time']); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </span>
        </div>
    <?php endif;
} catch (Exception $e) {
    // Fail silently in production, log error if debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('WFN Date/Time template error: ' . $e->getMessage());
    }
} ?> 