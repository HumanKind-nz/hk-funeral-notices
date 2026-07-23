<?php
/**
 * Minimal Card Template
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

$full_name = $person['full_name'];
$years_display = $person['years_display'];
?>

<article class="hkfn-enhancement-minimal-card">
    <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="hkfn-enhancement-minimal-link">
        <div class="hkfn-enhancement-minimal-content">
            <h3 class="hkfn-enhancement-minimal-name"><?php echo esc_html($full_name); ?></h3>
            <div class="hkfn-enhancement-minimal-details">
                <?php if ($years_display): ?>
                    <span class="hkfn-enhancement-minimal-years"><?php echo esc_html($years_display); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </a>
</article>
