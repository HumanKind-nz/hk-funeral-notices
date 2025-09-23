<?php
/**
 * Firehawk Layout Template - Single Funeral Notice
 * Matches actual Firehawk CRM design and layout
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// CSS is automatically enqueued by the main plugin based on display mode

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

<div class="firehawk-crm firehawk-single-page">
    
    <!-- Hero Banner Section -->
    <div class="firehawk-hero-banner" <?php if ($data['image']['hero_background']['has_image']): ?>style="background-image: url('<?php echo esc_url($data['image']['hero_background']['url']); ?>');"<?php endif; ?>>
        <div class="firehawk-hero-overlay">
            <div class="firehawk-hero-container">
                <div class="firehawk-hero-content">
                    <!-- Portrait Image -->
                    <?php if ($image['featured_url'] || $image['fallback_url']): ?>
                        <div class="firehawk-hero-portrait">
                            <img src="<?php echo esc_url($image['featured_url'] ?: $image['fallback_url']); ?>" 
                                 alt="<?php echo esc_attr($person['full_name']); ?>" 
                                 class="firehawk-portrait-image">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Name and Dates -->
                    <div class="firehawk-hero-text">
                        <h1 class="firehawk-hero-name"><?php echo esc_html($person['full_name']); ?></h1>
                        <?php if ($person['years_display']): ?>
                            <div class="firehawk-hero-dates"><?php echo esc_html($person['years_display']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons Section (Horizontal) -->
    <div class="firehawk-actions-container">
        <div class="firehawk-actions-horizontal">
            
            <!-- Livestream Button -->
            <?php if ($streaming['is_public']): ?>
                <a href="#streaming" class="firehawk-action-btn firehawk-action-livestream">
                    <span class="firehawk-action-icon">📺</span>
                    <span class="firehawk-action-text">Livestream</span>
                </a>
            <?php elseif ($streaming['is_private']): ?>
                <a href="/web-streaming/?tribute=<?php echo urlencode($person['full_name']); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="firehawk-action-btn firehawk-action-livestream">
                    <span class="firehawk-action-icon">📺</span>
                    <span class="firehawk-action-text">Request Streaming</span>
                </a>
            <?php endif; ?>
            
            <!-- Share Tribute Button -->
            <?php if ($tribute['show_button']): ?>
                <?php if ($tribute['has_url']): ?>
                    <a href="<?php echo esc_url($tribute['full_url']); ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="firehawk-action-btn firehawk-action-tribute">
                        <span class="firehawk-action-icon">💐</span>
                        <span class="firehawk-action-text">Share Tribute</span>
                    </a>
                <?php else: ?>
                    <div class="firehawk-action-btn firehawk-action-tribute firehawk-action-disabled">
                        <span class="firehawk-action-icon">💐</span>
                        <span class="firehawk-action-text">Share Tribute</span>
                        <small class="firehawk-action-note">Configure URL in Settings</small>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            

            
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="firehawk-main-container">
        <div class="firehawk-content-single-column">
            
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
                <div class="firehawk-celebration-section">
                    <h2 class="firehawk-celebration-text">
                        <?php echo wp_kses_post($celebration_text); ?>
                    </h2>
                </div>
            <?php endif; ?>
            
            <!-- Funeral Notice Content -->
            <?php if ($content['notice']): ?>
                <div class="firehawk-notice-section">
                    <div class="firehawk-notice-content">
                        <?php echo wp_kses_post($content['notice']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Service Details -->
            <?php if ($event['formatted_date'] || $event['formatted_time'] || ($location['show_location'] && ($location['display_venue'] || !empty($location['display_address'])))): ?>
                <div class="firehawk-service-section">
                    <h3 class="firehawk-section-title">Service Details</h3>
                    <div class="firehawk-service-details">
                        <?php if ($event['formatted_date']): ?>
                            <div class="firehawk-detail-row">
                                <span class="firehawk-detail-label">Date:</span>
                                <span class="firehawk-detail-value"><?php echo esc_html($event['formatted_date']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($event['formatted_time'] && !$event['hide_time']): ?>
                            <div class="firehawk-detail-row">
                                <span class="firehawk-detail-label">Time:</span>
                                <span class="firehawk-detail-value"><?php echo esc_html($event['formatted_time']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($location['show_location'] && ($location['display_venue'] || !empty($location['formatted_address']))): ?>
                            <div class="firehawk-detail-row">
                                <span class="firehawk-detail-label">Location:</span>
                                <div class="firehawk-detail-value">
                                    <?php if ($location['display_venue']): ?>
                                        <div class="firehawk-venue-name"><?php echo esc_html($location['display_venue']); ?></div>
                                    <?php endif; ?>
                                    <?php
                                    // Use formatted address; normalize potential <br> tags
                                    $formatted_address = $location['formatted_address'] ?? '';
                                    if (!empty($formatted_address)) {
                                        $formatted_plain = wp_strip_all_tags(str_replace(['<br />','<br>','<br/>'], ', ', $formatted_address));
                                        $lines = preg_split('/,\s*/', $formatted_plain);
                                        foreach ($lines as $line) {
                                            $line = trim($line);
                                            if (!empty($line)) {
                                                echo '<div class="firehawk-address-line">' . esc_html($line) . '</div>';
                                            }
                                        }
                                    }
                                    ?>
                                    <?php if (!empty($location['maps_url'])): ?>
                                        <div class="firehawk-maps-link">
                                            <a href="<?php echo esc_url($location['maps_url']); ?>" 
                                               target="_blank" 
                                               rel="noopener"
                                               class="firehawk-maps-button">
                                               View on Google Maps
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Streaming Section -->
            <?php if ($streaming['is_public']): ?>
                <div class="firehawk-streaming-section">
                    <h3 class="firehawk-section-title">Live Streaming</h3>
                    <div class="firehawk-stream-content">
                        
                        <?php if ($streaming['can_embed'] && $streaming['embed_code']): ?>
                            <!-- Inline embed for recognised services -->
                            <div class="firehawk-video-embed">
                                <?php echo $streaming['embed_code']; ?>
                            </div>
                            
                            <!-- View in new window link -->
                            <div class="firehawk-streaming-actions">
                                <a href="<?php echo esc_url($streaming['streaming_url']); ?>" 
                                   target="_blank" 
                                   rel="noopener" 
                                   class="firehawk-button firehawk-button-secondary">
                                   View in new window
                                </a>
                            </div>
                            
                        <?php elseif ($streaming['streaming_url']): ?>
                            <!-- Button for unrecognised services -->
                            <div class="firehawk-stream-link">
                                <p class="firehawk-stream-info">Watch the service live online</p>
                                <a href="<?php echo esc_url($streaming['streaming_url']); ?>" 
                                   target="_blank" 
                                   rel="noopener"
                                   class="firehawk-button firehawk-button-primary">
                                   Watch Live Stream
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($streaming['streaming_note']): ?>
                            <p class="firehawk-streaming-note"><?php echo esc_html($streaming['streaming_note']); ?></p>
                        <?php endif; ?>
                    </div>
                            </div>
        <?php endif; ?>

            <!-- Service Documents -->
            <?php 
            // Include service sheets partial
            echo $template_manager->render_partial('service-sheets', get_the_ID(), ['mode' => 'firehawk']);
            ?>

            <!-- Additional Info Section -->
            <?php if ($streaming['is_private']): ?>
                <div class="firehawk-info-section">
                    <div class="firehawk-info-box">
                        <h4>Private Streaming</h4>
                        <p>This service has private streaming available. Click "Request Streaming" above to access.</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div> 