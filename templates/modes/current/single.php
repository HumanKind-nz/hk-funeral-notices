<?php
/**
 * Current Layout Template - Single Funeral Notice
 * Matches existing Beaver Themer layout for transition period
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
$documents = $data['documents'];

// Legacy: Get post content
$post_content = get_the_content();
?>

<div class="wfn-current-single">
    <div class="wfn-current-container">

        <!-- Header Section -->
        <div class="wfn-current-header">
            <?php 
            // Get memorial header text from field or use site default
            $notice_group = get_field('wfn_notice_group', get_the_ID());
            $memorial_header = isset($notice_group['memorial_header']) ? $notice_group['memorial_header'] : '';
            
            // If field is empty, use site default
            if (empty($memorial_header)) {
                $settings = get_option('wfn_module_settings', []);
                $memorial_header = isset($settings['default_memorial_header']) ? $settings['default_memorial_header'] : 'In loving memory of';
            }
            
            // Display memorial header if not empty
            if (!empty($memorial_header)):
            ?>
                <div class="wfn-memory-text"><?php echo esc_html($memorial_header); ?></div>
            <?php endif; ?>
            <h1 class="wfn-current-name"><?php echo esc_html($person['full_name']); ?></h1>
            <?php if ($person['birth_year'] && $person['death_year']): ?>
                <div class="wfn-current-dates">
                    <?php echo esc_html($person['birth_year'] . ' - ' . $person['death_year']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Portrait Image -->
        <?php if ($image['featured_url'] || $image['fallback_url']): ?>
            <div class="wfn-current-image">
                <img src="<?php echo esc_url($image['featured_url'] ?: $image['fallback_url']); ?>"
                     alt="<?php echo esc_attr($person['full_name']); ?>"
                     class="wfn-current-portrait">
            </div>
        <?php endif; ?>

        <!-- Celebration Text -->
        <?php 
        // Get celebration text from field
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
            <div class="wfn-celebration-text">
                <h2><?php echo wp_kses_post($celebration_text); ?></h2>
            </div>
        <?php endif; ?>

        <?php 
        // Check if we have content for the left column
        // Only consider tribute button as content if there's also notice content
        $has_left_content = !empty($content['notice']);
        ?>
        
        <!-- Main Content Layout -->
        <div class="wfn-current-layout <?php echo $has_left_content ? '' : 'wfn-current-layout-single'; ?>">

            <?php if ($has_left_content): ?>
            <!-- Left Column: Main Content -->
            <div class="wfn-current-left-column">

                <?php // Removed duplicate intro check - already handled in header ?>

                <?php if ($content['notice']): ?>
                    <div class="wfn-current-notice">
                        <?php echo wp_kses_post($content['notice']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($tribute['show_button']): ?>
                    <div class="wfn-current-tribute">
                        <?php if ($tribute['has_url']): ?>
                            <p>Unable to attend the service? <a href="<?php echo esc_url($tribute['full_url']); ?>"
                               target="_blank" rel="noopener">Send a Tribute</a></p>
                        <?php else: ?>
                            <p><span style="color: #999;">Send a Tribute (Configure URL in Settings)</span></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

            <!-- Right Column: Service Details -->
            <div class="wfn-current-right-column">

                <?php if (($event['formatted_date'] || $event['formatted_time']) && !$event['hide_time']): ?>
                    <div class="wfn-current-when">
                        <h3>WHEN</h3>
                        <div class="wfn-service-datetime">
                            <?php if ($event['formatted_date']): ?>
                                <span class="wfn-service-date"><?php echo esc_html($event['formatted_date']); ?></span>
                            <?php endif; ?>
                            <?php if ($event['formatted_time']): ?>
                                <?php if ($event['formatted_date']): ?> at <?php endif; ?>
                                <span class="wfn-service-time"><?php echo esc_html($event['formatted_time']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($location['show_location'] && ($location['display_venue'] || !empty($location['display_address'])) && !$event['hide_time']): ?>
                    <div class="wfn-current-where">
                        <h3>WHERE</h3>
                        <?php if ($location['display_venue']): ?>
                            <div class="wfn-venue-name"><?php echo esc_html($location['display_venue']); ?></div>
                        <?php endif; ?>
                        <?php
                        // Use formatted address for clean display
                        $formatted_address = $location['formatted_address'];
                        if (!empty($formatted_address)) {
                            // Normalize potential <br> tags from taxonomy field and split cleanly
                            $formatted_plain = wp_strip_all_tags(str_replace(['<br />','<br>','<br/>'], ', ', $formatted_address));
                            $lines = preg_split('/,\s*/', $formatted_plain);
                            foreach ($lines as $line) {
                                $line = trim($line);
                                if (!empty($line)) {
                                    echo '<div class="wfn-address-line">' . esc_html($line) . '</div>';
                                }
                            }
                        }
                        ?>
                        <?php if (!empty($location['maps_url'])): ?>
                            <div class="wfn-maps-link">
                                <a href="<?php echo esc_url($location['maps_url']); ?>"
                                   target="_blank"
                                   rel="noopener">
                                   View on Google Maps
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>


                <!-- Service Sheet Download -->
                <?php if (!empty($documents['service_sheet'])): ?>
                    <div class="wfn-current-service-sheet">
                        <a href="<?php echo esc_url($documents['service_sheet']['url']); ?>"
                           target="_blank"
                           class="wfn-service-sheet-btn">
                           📄 Download Service Sheet
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Additional Documents (in right column) -->
                <?php if (!empty($documents['additional'])): ?>
                    <div class="wfn-current-additional-docs">
                        <?php foreach ($documents['additional'] as $doc): ?>
                            <?php
                            // Choose icon based on document type
                            $icon = '📄'; // Default document icon
                            if (isset($doc['document_type']) && $doc['document_type'] === 'url') {
                                $icon = '🔗'; // Link icon for external URLs
                            } elseif (isset($doc['document_type']) && $doc['document_type'] === 'file') {
                                $icon = '📄'; // Document icon for files
                            }
                            // Smart icon detection based on URL if type not available
                            if (!isset($doc['document_type']) && isset($doc['url'])) {
                                $url = strtolower($doc['url']);
                                if (strpos($url, 'donate') !== false || strpos($url, 'givealittle') !== false) {
                                    $icon = '💝'; // Gift icon for donations
                                } elseif (strpos($url, 'youtube') !== false || strpos($url, 'vimeo') !== false) {
                                    $icon = '📹'; // Video icon
                                } elseif (strpos($url, 'photo') !== false || strpos($url, 'gallery') !== false) {
                                    $icon = '📷'; // Camera icon for photos
                                } elseif (!preg_match('/\.(pdf|doc|docx)$/i', $url)) {
                                    $icon = '🔗'; // Link icon for external URLs
                                }
                            }
                            ?>
                            <a href="<?php echo esc_url($doc['url']); ?>"
                               target="_blank"
                               class="wfn-document-link">
                               <?php echo $icon; ?> <?php echo esc_html($doc['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php // Show tribute button in right column when there's no left content ?>
                <?php if (!$has_left_content && $tribute['show_button']): ?>
                    <div class="wfn-current-tribute wfn-current-tribute-right">
                        <?php if ($tribute['has_url']): ?>
                            <p>Unable to attend the service? <a href="<?php echo esc_url($tribute['full_url']); ?>"
                               target="_blank" rel="noopener">Send a Tribute</a></p>
                        <?php else: ?>
                            <p><span style="color: #999;">Send a Tribute (Configure URL in Settings)</span></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>

        <!-- Streaming Section -->
        <?php if ($streaming['is_public']): ?>
            <div class="wfn-current-stream-section">
                <h2 class="wfn-stream-title">View the Funeral Stream</h2>

                <?php $stream_container_classes = 'wfn-stream-container';
                if ($streaming['streaming_service'] === 'oneroom') { $stream_container_classes .= ' wfn-oneroom'; }
                ?>
                <div class="<?php echo esc_attr($stream_container_classes); ?>">

                    <?php if ($streaming['streaming_service'] === 'oneroom' && $streaming['embed_code']): ?>
                        <!-- OneRoom embed -->
                        <div class="wfn-video-wrapper">
                            <?php echo $streaming['embed_code']; ?>
                        </div>
                        <?php if (!empty($streaming['streaming_url'])): ?>
                            <div class="wfn-streaming-actions" style="margin-top: 0.75rem;">
                                <a href="<?php echo esc_url($streaming['streaming_url']); ?>" target="_blank" rel="noopener" class="wfn-view-external">
                                    Open on OneRoom
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php elseif (in_array($streaming['streaming_service'], ['youtube', 'vimeo', 'vimeo_pro']) && $streaming['embed_code']): ?>
                        <!-- YouTube/Vimeo embed -->
                        <div class="wfn-video-wrapper">
                            <?php echo $streaming['embed_code']; ?>
                        </div>
                        <?php if (!empty($streaming['streaming_url'])): ?>
                            <div class="wfn-streaming-actions" style="margin-top: 0.75rem;">
                                <a href="<?php echo esc_url($streaming['streaming_url']); ?>" target="_blank" rel="noopener" class="wfn-view-external">
                                    View in new window
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($streaming['streaming_service'] === 'other' && $streaming['streaming_url']): ?>
                        <!-- Other streaming service - button link -->
                        <div class="wfn-stream-button-wrapper">
                            <a href="<?php echo esc_url($streaming['streaming_url']); ?>"
                               target="_blank"
                               rel="noopener"
                               class="wfn-stream-btn">
                               View Funeral Stream
                            </a>
                        </div>

                    <?php else: ?>
                        <!-- Fallback message -->
                        <div class="wfn-stream-placeholder">
                            <p>Live streaming will be available during the service.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Streaming Note -->
                    <?php if ($streaming['streaming_note']): ?>
                        <div class="wfn-streaming-note">
                            <p><?php echo esc_html($streaming['streaming_note']); ?></p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>



    </div>
</div>