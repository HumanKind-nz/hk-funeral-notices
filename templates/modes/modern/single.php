<?php
/**
 * Modern Layout Template - Single Funeral Notice
 * Clean, contemporary funeral notice design
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

<div class="wfn-modern-single">
    <div class="wfn-modern-container">
        
        <!-- Hero Section -->
        <div class="wfn-modern-hero">
            <?php if ($image['featured_url'] || $image['fallback_url']): ?>
                <div class="wfn-modern-image">
                    <img src="<?php echo esc_url($image['featured_url'] ?: $image['fallback_url']); ?>" 
                         alt="<?php echo esc_attr($person['full_name']); ?>" 
                         class="wfn-modern-portrait">
                </div>
            <?php endif; ?>
            
            <div class="wfn-modern-details">
                <h1 class="wfn-modern-name"><?php echo esc_html($person['full_name']); ?></h1>
                <?php if ($person['years_display']): ?>
                    <p class="wfn-modern-years"><?php echo esc_html($person['years_display']); ?></p>
                <?php endif; ?>
                
                <?php 
                // Get intro text from field or use site default
                $notice_group = get_field('wfn_notice_group', get_the_ID());
                $intro_text = isset($notice_group['intro_text']) ? $notice_group['intro_text'] : '';
                
                // If field is empty, use site default
                if (empty($intro_text)) {
                    $settings = get_option('wfn_module_settings', []);
                    $intro_text = isset($settings['default_intro_text']) ? $settings['default_intro_text'] : 'In loving memory of';
                }
                
                // Display intro text if not empty  
                if (!empty($intro_text)):
                ?>
                    <p class="wfn-modern-intro">
                        <?php echo esc_html($intro_text); ?> <?php echo esc_html($person['first_name']); ?>, 
                        please join us in celebrating a life well lived.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Service Information -->
        <?php if ($event['formatted_date'] || $event['formatted_time'] || ($location['show_location'] && ($location['display_venue'] || !empty($location['display_address'])))): ?>
            <div class="wfn-modern-service">
                <h2 class="wfn-modern-section-title">Service Information</h2>
                <div class="wfn-modern-service-details">
                    <?php if ($event['formatted_date']): ?>
                        <div class="wfn-modern-detail">
                            <span class="wfn-modern-label">Date:</span>
                            <span class="wfn-modern-value"><?php echo esc_html($event['formatted_date']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($event['formatted_time'] && !$event['hide_time']): ?>
                        <div class="wfn-modern-detail">
                            <span class="wfn-modern-label">Time:</span>
                            <span class="wfn-modern-value"><?php echo esc_html($event['formatted_time']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Location moved here to group with date/time -->
                    <?php if ($location['show_location'] && ($location['display_venue'] || !empty($location['display_address']))): ?>
                        <div class="wfn-modern-detail wfn-modern-location-detail">
                            <span class="wfn-modern-label">Location:</span>
                            <div class="wfn-modern-value">
                                <?php if ($location['display_venue']): ?>
                                    <strong><?php echo esc_html($location['display_venue']); ?></strong><br>
                                <?php endif; ?>
                                <?php 
                                $address = $location['display_address'];
                                if (is_array($address)) {
                                    foreach ($address as $line) {
                                        if (!empty($line)) {
                                            echo esc_html($line) . '<br>';
                                        }
                                    }
                                } elseif (!empty($address)) {
                                    echo esc_html($address) . '<br>';
                                }
                                ?>
                                
                                <?php if (!empty($location['maps_url'])): ?>
                                    <a href="<?php echo esc_url($location['maps_url']); ?>" 
                                       target="_blank" 
                                       rel="noopener"
                                       class="wfn-modern-maps-link">
                                       View on Google Maps
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <?php if ($content['notice']): ?>
            <div class="wfn-modern-content">
                <h2 class="wfn-modern-section-title">Funeral Notice</h2>
                <div class="wfn-modern-notice">
                    <?php echo wp_kses_post($content['notice']); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Streaming Section -->
        <?php if ($streaming['is_public']): ?>
            <div class="wfn-modern-streaming">
                <h2 class="wfn-modern-section-title">Live Stream</h2>
                <div class="wfn-modern-stream-wrapper">
                    
                    <?php if ($streaming['can_embed'] && $streaming['embed_code']): ?>
                        <!-- Inline embed for recognised services -->
                        <?php echo $streaming['embed_code']; ?>
                        
                        <!-- View in new window link -->
                        <div class="wfn-streaming-actions">
                            <a href="<?php echo esc_url($streaming['streaming_url']); ?>" 
                               target="_blank" 
                               rel="noopener" 
                               class="wfn-modern-button wfn-view-external">
                               🔗 View in new window
                            </a>
                        </div>
                        
                    <?php elseif ($streaming['streaming_url']): ?>
                        <!-- Button for unrecognised services -->
                        <p>Watch the service live online</p>
                        <a href="<?php echo esc_url($streaming['streaming_url']); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="wfn-modern-button">
                           📺 View Live Stream
                        </a>
                        
                    <?php else: ?>
                        <!-- Fallback message -->
                        <p>Live streaming will be available during the service.</p>
                    <?php endif; ?>
                    
                    <?php if ($streaming['streaming_note']): ?>
                        <p class="wfn-streaming-note"><?php echo esc_html($streaming['streaming_note']); ?></p>
                    <?php endif; ?>
                    
                </div>
            </div>
        <?php endif; ?>

        <!-- Service Documents -->
        <?php 
        // Include service sheets partial
        echo $template_manager->render_partial('service-sheets', get_the_ID(), ['mode' => 'modern']);
        ?>

        <!-- Actions -->
        <?php if ($tribute['show_button']): ?>
            <div class="wfn-modern-actions">
                <?php if ($tribute['has_url']): ?>
                    <a href="<?php echo esc_url($tribute['full_url']); ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="wfn-modern-button">
                       Send a Tribute
                    </a>
                <?php else: ?>
                    <span class="wfn-modern-button wfn-modern-button-disabled">
                       Send a Tribute (Configure URL in Settings)
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div> 