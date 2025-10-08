<?php
/**
 * Elegant Archive Funeral Notice Template
 * Traditional, formal grid layout with ornamental elements
 * 
 * @package WeaveStudios\FuneralNotices
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="wfn-elegant-archive">
    <div class="wfn-container">
        
        <?php if (have_posts()): ?>
            
            <header class="wfn-archive-header">
                <h1 class="wfn-archive-title"><?php post_type_archive_title(); ?></h1>
            </header>
            
            <div class="wfn-elegant-grid wfn-cols-3">
                
                <?php while (have_posts()): the_post(); ?>
                    
                    <article class="wfn-elegant-card">
                        <a href="<?php the_permalink(); ?>" class="wfn-elegant-link">
                            
                            <div class="wfn-elegant-header">
                                <?php if (has_post_thumbnail()): ?>
                                    <img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')); ?>" 
                                         alt="<?php echo esc_attr(get_the_title()); ?>" 
                                         class="wfn-elegant-portrait">
                                <?php endif; ?>
                                
                                <div class="wfn-elegant-details">
                                    <h2 class="wfn-elegant-name"><?php the_title(); ?></h2>
                                    
                                    <?php 
                                    $person_group = get_field('wfn_person_group') ?: [];
                                    $birth_year = $person_group['birth_year'] ?? '';
                                    $death_year = $person_group['death_year'] ?? '';
                                    if ($birth_year && $death_year): ?>
                                        <p class="wfn-elegant-years"><?php echo esc_html("{$birth_year} - {$death_year}"); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php 
                            $details_group = get_field('wfn_details_group') ?: [];
                            $funeral_date = $details_group['funeral_date'] ?? '';
                            if ($funeral_date): ?>
                                <div class="wfn-elegant-date">
                                    <strong>Service:</strong> <?php echo esc_html(date('F j, Y', strtotime($funeral_date))); ?>
                                </div>
                            <?php endif; ?>
                            
                        </a>
                    </article>
                    
                <?php endwhile; ?>
                
            </div>

            <?php
            // Load More Button
            global $wp_query;
            $total_posts = $wp_query->found_posts;
            $posts_per_page = get_option('posts_per_page', 12);
            $shown_posts = $wp_query->post_count;

            if ($total_posts > $shown_posts):
                $settings = get_option('wfn_module_settings', []);
                $load_more_posts = $settings['load_more_posts'] ?? 9;
                ?>
                <div class="wfn-load-more-container">
                    <button class="wfn-load-more-button"
                            data-offset="<?php echo esc_attr($shown_posts); ?>"
                            data-per-load="<?php echo esc_attr($load_more_posts); ?>"
                            data-layout="elegant"
                            data-filters="{}">
                        Load More
                    </button>
                </div>
            <?php endif; ?>

        <?php else: ?>
            
            <div class="wfn-no-results">
                <h2>No funeral notices found</h2>
                <p>There are currently no funeral notices to display.</p>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?> 