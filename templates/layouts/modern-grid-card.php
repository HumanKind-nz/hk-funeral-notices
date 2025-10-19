<?php
/**
 * Modern Grid Card Template
 * Used by Load More AJAX handler
 *
 * @package WeaveStudios\FuneralNotices
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();

// Use TemplateManager for unified data access
$template_manager = new \WeaveStudios\FuneralNotices\Templates\TemplateManager();
$data = $template_manager->get_funeral_data($post_id);
$person = $data['person'];
$event = $data['event'];
$streaming = $data['streaming'];

$full_name = $person['full_name'];
$years_display = $person['years_display'];
$funeral_date = $event['funeral_date'];
$funeral_time = $event['funeral_time'];
$hide_details = $event['hide_time'] ?? false;

// Get image - use grid crop if available, fallback to medium size
$thumbnail_id = get_post_thumbnail_id($post_id);
$featured_image = false;

if ($thumbnail_id) {
    // Try to get the grid crop size first
    $featured_image = wp_get_attachment_image_url($thumbnail_id, 'wfn-grid-crop');

    // If no grid crop exists, fall back to medium size
    if (!$featured_image) {
        $featured_image = wp_get_attachment_image_url($thumbnail_id, 'medium');
    }
}

$settings = get_option('wfn_module_settings', []);
$fallback_url = $settings['default_person_image'] ?? '';
if (empty($fallback_url)) {
    $fallback_url = plugin_dir_url(dirname(__FILE__, 2)) . 'assets/images/fallback.webp';
}
$image_url = $featured_image ?: $fallback_url;
?>

<article class="wfn-enhancement-modern-card">
    <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="wfn-enhancement-modern-link">

        <?php if ($image_url): ?>
            <div class="wfn-enhancement-modern-image">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($full_name); ?>" loading="lazy">
            </div>
        <?php endif; ?>

        <div class="wfn-enhancement-modern-content">

            <?php if ($hide_details && $streaming['is_public'] && !empty($streaming['streaming_url'])): ?>
                <span class="wfn-streaming-icon-float" title="Live streaming available">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm128-64l0 128c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-40 80 40 0-128-80 40 0-40c0-17.7-14.3-32-32-32l-128 0c-17.7 0-32 14.3-32 32z"/>
                    </svg>
                </span>
            <?php endif; ?>

            <h3 class="wfn-enhancement-modern-title"><?php echo esc_html($full_name); ?></h3>

            <?php if ($years_display): ?>
                <p class="wfn-enhancement-modern-dates"><?php echo esc_html($years_display); ?></p>
            <?php endif; ?>

            <?php if ($funeral_date && !$hide_details): ?>
                <div class="wfn-enhancement-modern-service">
                    <span class="wfn-service-info">
                        <span class="service-label">Service:</span>
                        <?php echo esc_html(date('j M Y', strtotime($funeral_date))); ?>
                        <?php if ($funeral_time): ?>
                            at <?php echo esc_html(date('g:i A', strtotime($funeral_time))); ?>
                        <?php endif; ?>
                    </span>

                    <?php if ($streaming['is_public'] && !empty($streaming['streaming_url'])): ?>
                        <span class="wfn-streaming-icon" title="Live streaming available">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path d="M0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0zm128-64l0 128c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-40 80 40 0-128-80 40 0-40c0-17.7-14.3-32-32-32l-128 0c-17.7 0-32 14.3-32 32z"/>
                            </svg>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </a>
</article>
