<?php
/**
 * Main Single Funeral Notice Template
 * Delegates to mode-specific templates based on display settings
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<main id="main" class="site-main hkfn-fullwidth-main">
        <?php
        use HumanKind\FuneralNotices\Templates\TemplateManager;

        // Get the template manager
        $template_manager = new TemplateManager();
        $active_mode = $template_manager->get_single_mode(); // Use single-specific mode

        // CSS is now handled by LayoutsModule to avoid duplication
        // Mode-specific CSS is loaded automatically based on the active template

        // Build path to mode-specific template
        $mode_template = plugin_dir_path(__FILE__) . "modes/{$active_mode}/single.php";

        // Check if mode-specific template exists
        if (file_exists($mode_template)) {
            include $mode_template;
        } else {
            // Fallback to modern template if mode template doesn't exist
            $fallback_template = plugin_dir_path(__FILE__) . "modes/modern/single.php";
            if (file_exists($fallback_template)) {
                include $fallback_template;
            } else {
                // Ultimate fallback - basic display
                ?>
                <div class="hkfn-single-fallback">
                    <h1><?php the_title(); ?></h1>
                    <div class="content">
                        <?php the_content(); ?>
                    </div>
                </div>
                <?php
            }
        }
        ?>
</main>

<?php get_footer(); 