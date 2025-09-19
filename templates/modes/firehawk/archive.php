<?php
/**
 * Firehawk Archive Funeral Notice Template
 * Clean, modern grid layout compatible with Firehawk themes
 * 
 * @package WeaveStudios\FuneralNotices
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="wfn-firehawk-archive">
    <div class="wfn-container">
        
        <?php if (have_posts()): ?>
            
            <header class="wfn-archive-header">
                <h1 class="wfn-archive-title"><?php post_type_archive_title(); ?></h1>
            </header>
            
            <div class="wfn-firehawk-grid wfn-cols-3">
                
                <?php while (have_posts()): the_post(); ?>
                    
                    <article class="wfn-firehawk-card">
                        <a href="<?php the_permalink(); ?>" class="wfn-firehawk-link">
                            
                            <div class="wfn-firehawk-header">
                                <?php if (has_post_thumbnail()): ?>
                                    <img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')); ?>" 
                                         alt="<?php echo esc_attr(get_the_title()); ?>" 
                                         class="wfn-firehawk-portrait">
                                <?php endif; ?>
                                
                                <div class="wfn-firehawk-details">
                                    <h2 class="wfn-firehawk-name"><?php the_title(); ?></h2>
                                    
                                    <?php 
                                    $person_group = get_field('wfn_person_group') ?: [];
                                    $birth_year = $person_group['birth_year'] ?? '';
                                    $death_year = $person_group['death_year'] ?? '';
                                    if ($birth_year && $death_year): ?>
                                        <p class="wfn-firehawk-years"><?php echo esc_html("{$birth_year} - {$death_year}"); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php 
                            $details_group = get_field('wfn_details_group') ?: [];
                            $funeral_date = $details_group['funeral_date'] ?? '';
                            if ($funeral_date): ?>
                                <div class="wfn-firehawk-date">
                                    <strong>Service:</strong> <?php echo esc_html(date('F j, Y', strtotime($funeral_date))); ?>
                                </div>
                            <?php endif; ?>
                            
                        </a>
                    </article>
                    
                <?php endwhile; ?>
                
            </div>
            
            <?php
            // Pagination
            the_posts_pagination([
                'mid_size' => 2,
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;',
                'class' => 'wfn-pagination'
            ]);
            ?>
            
        <?php else: ?>
            
            <div class="wfn-no-results">
                <h2>No funeral notices found</h2>
                <p>There are currently no funeral notices to display.</p>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?> 