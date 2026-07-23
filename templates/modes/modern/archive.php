<?php
/**
 * Modern Archive Funeral Notice Template
 * Contemporary grid layout with modern styling
 * 
 * @package HumanKind\FuneralNotices
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="hkfn-modern-archive">
    <div class="hkfn-container">
        
        <?php if (have_posts()): ?>
            
            <header class="hkfn-archive-header">
                <h1 class="hkfn-archive-title"><?php post_type_archive_title(); ?></h1>
            </header>
            
            <div class="hkfn-modern-grid hkfn-cols-3">
                
                <?php while (have_posts()): the_post(); ?>
                    
                    <article class="hkfn-modern-card">
                        <a href="<?php the_permalink(); ?>" class="hkfn-modern-link">
                            
                            <?php if (has_post_thumbnail()): ?>
                                <div class="hkfn-modern-image">
                                    <img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'medium')); ?>" 
                                         alt="<?php echo esc_attr(get_the_title()); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="hkfn-modern-content">
                                <h2 class="hkfn-modern-name"><?php the_title(); ?></h2>
                                
                                <?php 
                                $person_group = get_field('hkfn_person_group') ?: [];
                                $birth_year = $person_group['birth_year'] ?? '';
                                $death_year = $person_group['death_year'] ?? '';
                                if ($birth_year && $death_year): ?>
                                    <p class="hkfn-modern-years"><?php echo esc_html("{$birth_year} - {$death_year}"); ?></p>
                                <?php endif; ?>
                                
                                <?php 
                                $details_group = get_field('hkfn_details_group') ?: [];
                                $funeral_date = $details_group['funeral_date'] ?? '';
                                if ($funeral_date): ?>
                                    <div class="hkfn-modern-date">
                                        <strong>Service:</strong> <?php echo esc_html(date('F j, Y', strtotime($funeral_date))); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
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
                $settings = hkfn_get_option('module_settings', []);
                $load_more_posts = $settings['load_more_posts'] ?? 9;
                ?>
                <div class="hkfn-load-more-container">
                    <button class="hkfn-load-more-button"
                            data-offset="<?php echo esc_attr($shown_posts); ?>"
                            data-per-load="<?php echo esc_attr($load_more_posts); ?>"
                            data-layout="modern"
                            data-filters="{}">
                        Load More
                    </button>
                </div>
            <?php endif; ?>

        <?php else: ?>
            
            <div class="hkfn-no-results">
                <h2>No funeral notices found</h2>
                <p>There are currently no funeral notices to display.</p>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?> 