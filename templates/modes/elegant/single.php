<?php
/**
 * Elegant Layout Template - Single Funeral Notice
 * Formal, traditional funeral styling
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use WeaveStudios\FuneralNotices\Templates\TemplateManager;

$template_manager = new TemplateManager();
$data = $template_manager->get_funeral_data(get_the_ID());

// Extract data
$person = $data['person'];
$event = $data['event'];
$content = $data['content'];
$streaming = $data['streaming'];
$location = $data['location'];
$image = $data['image'];
$tribute = $data['tribute'];
?>

<div class="wfn-elegant-single">
    <div class="wfn-elegant-container">
        
        <!-- Header with ornamental design -->
        <div class="wfn-elegant-header">
            <div class="wfn-elegant-ornament"></div>
            
            <?php if ($image['featured_url'] || $image['fallback_url']): ?>
                <div class="wfn-elegant-portrait-wrapper">
                    <img src="<?php echo esc_url($image['featured_url'] ?: $image['fallback_url']); ?>" 
                         alt="<?php echo esc_attr($person['full_name']); ?>" 
                         class="wfn-elegant-portrait">
                </div>
            <?php endif; ?>
            
            <h1 class="wfn-elegant-name"><?php echo esc_html($person['full_name']); ?></h1>
            <?php if ($person['years_display']): ?>
                <p class="wfn-elegant-years"><?php echo esc_html($person['years_display']); ?></p>
            <?php endif; ?>
            
            <div class="wfn-elegant-ornament"></div>
        </div>

        <!-- Celebration Text -->
        <?php 
        // Get celebration text from field
        $notice_group = get_field('wfn_notice_group', get_the_ID());
        $celebration_text = isset($notice_group['celebration_text']) ? trim($notice_group['celebration_text']) : '';
        
        // Only show if not explicitly empty (allows user to clear it)
        if ($celebration_text !== ''):
            // Use default if field wasn't modified or is empty
            if (empty($celebration_text)) {
                $celebration_text = 'Please join us in celebrating {firstname} {lastname}\'s life';
            }
            
            // Replace template variables with actual values
            $celebration_text = str_replace(
                ['{firstname}', '{lastname}', '{fullname}'],
                [$person['first_name'], $person['last_name'], $person['full_name']],
                $celebration_text
            );
        ?>
            <div class="wfn-elegant-memorial">
                <p class="wfn-elegant-invitation">
                    <?php echo wp_kses_post($celebration_text); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Service Details with traditional styling -->
        <?php if (!$event['hide_time'] && ($event['formatted_date'] || $event['formatted_time'] || ($location['show_location'] && ($location['display_venue'] || !empty($location['display_address']))))): ?>
            <div class="wfn-elegant-service">
                <h2 class="wfn-elegant-section-title">Memorial Service</h2>
                <div class="wfn-elegant-service-details">
                    <?php if ($event['formatted_date']): ?>
                        <div class="wfn-elegant-detail-row">
                            <span class="wfn-elegant-detail-label">Date</span>
                            <span class="wfn-elegant-detail-divider">•</span>
                            <span class="wfn-elegant-detail-value"><?php echo esc_html($event['formatted_date']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($event['formatted_time']): ?>
                        <div class="wfn-elegant-detail-row">
                            <span class="wfn-elegant-detail-label">Time</span>
                            <span class="wfn-elegant-detail-divider">•</span>
                            <span class="wfn-elegant-detail-value"><?php echo esc_html($event['formatted_time']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Location moved here to group with date/time -->
                    <?php if ($location['show_location'] && ($location['display_venue'] || !empty($location['display_address']))): ?>
                        <div class="wfn-elegant-detail-row">
                            <span class="wfn-elegant-detail-label">Location</span>
                            <span class="wfn-elegant-detail-divider">•</span>
                            <span class="wfn-elegant-detail-value">
                                <?php if ($location['display_venue']): ?>
                                    <strong><?php echo esc_html($location['display_venue']); ?></strong>
                                    <?php if (!empty($location['display_address'])): ?><br><?php endif; ?>
                                <?php endif; ?>
                                <?php 
                                $address = $location['formatted_address'] ?? '';
                                if (!empty($address)) {
                                    $formatted_plain = wp_strip_all_tags(str_replace(['<br />','<br>','<br/>'], ', ', $address));
                                    $parts = preg_split('/,\s*/', $formatted_plain);
                                    echo esc_html(trim($parts[0] ?? $formatted_plain));
                                }
                                ?>
                                <?php if (!empty($location['maps_url'])): ?>
                                    <br><a href="<?php echo esc_url($location['maps_url']); ?>" 
                                           target="_blank" 
                                           rel="noopener"
                                           class="wfn-elegant-maps-link">
                                           📍 View on Google Maps
                                    </a>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Content with elegant typography -->
        <?php if ($content['notice']): ?>
            <div class="wfn-elegant-content">
                <h2 class="wfn-elegant-section-title">Remembrance</h2>
                <div class="wfn-elegant-notice">
                    <?php echo wp_kses_post($content['notice']); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Streaming with dignified presentation -->
        <?php if ($streaming['is_public']): ?>
            <div class="wfn-elegant-streaming">
                <h2 class="wfn-elegant-section-title">Live Service</h2>
                <div class="wfn-elegant-stream-wrapper">
                    
                    <?php if ($streaming['streaming_service'] === 'oneroom' && $streaming['embed_code']): ?>
                        <!-- OneRoom embed -->
                        <p class="wfn-elegant-stream-notice">
                            For those unable to attend in person, the service will be available online.
                        </p>
                        <div class="wfn-elegant-stream-embed">
                            <?php echo $streaming['embed_code']; ?>
                        </div>
                        <?php if (!empty($streaming['streaming_url'])): ?>
                            <div class="wfn-streaming-actions" style="margin-top: 0.75rem; text-align: center;">
                                <a href="<?php echo esc_url($streaming['streaming_url']); ?>" target="_blank" rel="noopener" class="wfn-elegant-external-link">
                                    Open on OneRoom
                                </a>
                            </div>
                        <?php endif; ?>
                        
                    <?php elseif (in_array($streaming['streaming_service'], ['youtube', 'vimeo', 'vimeo_pro']) && $streaming['embed_code']): ?>
                        <!-- YouTube/Vimeo embed -->
                        <p class="wfn-elegant-stream-notice">
                            For those unable to attend in person, the service will be available online.
                        </p>
                        <div class="wfn-elegant-stream-embed">
                            <?php echo $streaming['embed_code']; ?>
                        </div>
                        <?php if (!empty($streaming['streaming_url'])): ?>
                            <div class="wfn-streaming-actions" style="margin-top: 0.75rem; text-align: center;">
                                <a href="<?php echo esc_url($streaming['streaming_url']); ?>" target="_blank" rel="noopener" class="wfn-elegant-external-link">
                                    View in new window
                                </a>
                            </div>
                        <?php endif; ?>
                        
                    <?php elseif ($streaming['streaming_service'] === 'other' && $streaming['streaming_url']): ?>
                        <!-- Other streaming service - button link -->
                        <p class="wfn-elegant-stream-notice">
                            For those unable to attend in person, the service will be available online.
                        </p>
                        <div class="wfn-elegant-stream-link">
                            <a href="<?php echo esc_url($streaming['streaming_url']); ?>" 
                               target="_blank" 
                               rel="noopener"
                               class="wfn-elegant-tribute-link">
                               Watch Live Service
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <!-- Fallback message -->
                        <p class="wfn-elegant-stream-notice">
                            For those unable to attend in person, the service will be available online.
                        </p>
                    <?php endif; ?>
                    
                    <!-- Streaming Note -->
                    <?php if ($streaming['streaming_note']): ?>
                        <div class="wfn-elegant-streaming-note">
                            <p><?php echo esc_html($streaming['streaming_note']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        <?php endif; ?>

        <!-- Service Documents -->
        <?php 
        // Include service sheets partial
        echo $template_manager->render_partial('service-sheets', get_the_ID(), ['mode' => 'elegant']);
        ?>

        <!-- Closing section with formal actions -->
        <div class="wfn-elegant-closing">
            <div class="wfn-elegant-ornament-small"></div>
            
            <?php if ($tribute['show_button']): ?>
                <div class="wfn-elegant-actions">
                    <p class="wfn-elegant-tribute-notice">
                        Share your memories and condolences with the family.
                    </p>
                    <?php if ($tribute['has_url']): ?>
                        <a href="<?php echo esc_url($tribute['full_url']); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="wfn-elegant-tribute-link">
                           Send a Tribute
                        </a>
                    <?php else: ?>
                        <span class="wfn-elegant-tribute-link" style="color: #999; cursor: not-allowed;">
                           Send a Tribute (Configure URL in Settings)
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="wfn-elegant-ornament-small"></div>
        </div>

    </div>
</div> 