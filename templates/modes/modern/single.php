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

// Load SVG icon functions
require_once __DIR__ . '/../../partials/svg-icons.php';

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
                    <p class="wfn-modern-intro">
                        <?php echo wp_kses_post($celebration_text); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Service Information -->
        <?php if (!$event['hide_time'] && ($event['formatted_date'] || $event['formatted_time'] || ($location['show_location'] && ($location['display_venue'] || !empty($location['display_address']))))): ?>
            <div class="wfn-modern-service">
                <h2 class="wfn-modern-section-title">Service Information</h2>
                <div class="wfn-modern-service-details">
                    <?php if ($event['formatted_date']): ?>
                        <div class="wfn-modern-detail">
                            <span class="wfn-modern-label">Date:</span>
                            <span class="wfn-modern-value"><?php echo esc_html($event['formatted_date']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($event['formatted_time']): ?>
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
                               <?php echo wfn_get_link_icon(); ?> View in new window
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
                       Send a tribute to the family
                    </a>
                <?php else: ?>
                    <span class="wfn-modern-button wfn-modern-button-disabled">
                       Send a tribute to the family (Configure URL in Settings)
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div> 