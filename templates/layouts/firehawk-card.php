<?php
/**
 * Firehawk Grid Card Template
 * Used by Load More AJAX handler
 *
 * @package HumanKind\FuneralNotices
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();

// Use TemplateManager for unified data access
$template_manager = new \HumanKind\FuneralNotices\Templates\TemplateManager();
$data = $template_manager->get_funeral_data($post_id);
$person = $data['person'];

$first_name = $person['first_name'];
$last_name = $person['last_name'];
$years_display = $person['years_display'];

// Format name for Firehawk style (LASTNAME, First)
$formatted_name = strtoupper($last_name) . ', ' . $first_name;

// Get image
$featured_image = get_the_post_thumbnail_url($post_id, 'medium');
$settings = hkfn_get_option('module_settings', []);
$fallback_url = $settings['default_person_image'] ?? '';
if (empty($fallback_url)) {
    $fallback_url = plugin_dir_url(dirname(__FILE__, 2)) . 'assets/images/fallback.webp';
}
$image_url = $featured_image ?: $fallback_url;
?>

<div class="grid-col">
    <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
        <div class="grid-item compact">
            <div class="top-content">
                <div class="top-img" style="background-image: url('<?php echo esc_url($image_url); ?>')"></div>
                <div class="title-container">
                    <div class="title"><?php echo esc_html($formatted_name); ?></div>
                    <?php if ($years_display): ?>
                        <div class="dates"><?php echo esc_html($years_display); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </a>
</div>
