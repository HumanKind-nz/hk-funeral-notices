<?php
/**
 * Main Archive Funeral Notice Template
 * Delegates to mode-specific templates based on archive display settings
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<main id="main" class="site-main">
    <div class="container">
        <?php
        use HumanKind\FuneralNotices\Templates\TemplateManager;

        // Get the template manager
        $template_manager = new TemplateManager();
        $active_mode = $template_manager->get_archive_mode(); // Use archive-specific mode

        // Enqueue base CSS for all layouts
        wp_enqueue_style('hkfn-enhancement-base', plugin_dir_url(__FILE__) . '../assets/css/layouts/shared-base.css', [], '2.0.0');

        // Enqueue mode-specific CSS
        $css_files = [
            'modern' => 'modern-grid.css',
            'elegant' => 'elegant-grid.css',
            'minimal' => 'minimal.css'
        ];

        if (isset($css_files[$active_mode])) {
            wp_enqueue_style("hkfn-enhancement-{$active_mode}", plugin_dir_url(__FILE__) . "../assets/css/layouts/{$css_files[$active_mode]}", ['hkfn-enhancement-base'], '2.0.0');
        }

        // Build path to mode-specific template
        $mode_template = plugin_dir_path(__FILE__) . "modes/{$active_mode}/archive.php";

        // Check if mode-specific template exists
        if (file_exists($mode_template)) {
            include $mode_template;
        } else {
            // Fallback to modern template if mode template doesn't exist
            $fallback_template = plugin_dir_path(__FILE__) . "modes/modern/archive.php";
            if (file_exists($fallback_template)) {
                include $fallback_template;
            } else {
                // Ultimate fallback - basic WordPress archive
                ?>
                <div class="hkfn-archive-fallback">
                    <h1><?php post_type_archive_title(); ?></h1>
                    <?php if (have_posts()): ?>
                        <div class="hkfn-posts">
                            <?php while (have_posts()): the_post(); ?>
                                <article>
                                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                    <?php the_excerpt(); ?>
                                </article>
                            <?php endwhile; ?>
                        </div>
                        <?php the_posts_pagination(); ?>
                    <?php else: ?>
                        <p>No funeral notices found.</p>
                    <?php endif; ?>
                </div>
                <?php
            }
        }
        ?>
    </div>
</main>

<?php get_footer(); 