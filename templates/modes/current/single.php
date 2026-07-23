<?php
/**
 * Current Layout Template - Single Funeral Notice
 * Matches existing Beaver Themer layout for transition period
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use HumanKind\FuneralNotices\Templates\TemplateManager;

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
$documents = $data['documents'];
$share = $data['share'];

// Legacy: Get post content
$post_content = get_the_content();
?>

<div class="hkfn-current-single">
    <div class="hkfn-current-container">

        <!-- Header Section -->
        <div class="hkfn-current-header">
            <?php 
            // Get memorial header text from field or use site default
            $notice_group = get_field('hkfn_notice_group', get_the_ID());
            $memorial_header = isset($notice_group['memorial_header']) ? $notice_group['memorial_header'] : '';
            
            // If field is empty, use site default
            if (empty($memorial_header)) {
                $settings = hkfn_get_option('module_settings', []);
                $memorial_header = isset($settings['default_memorial_header']) ? $settings['default_memorial_header'] : 'In loving memory of';
            }
            
            // Display memorial header if not empty
            if (!empty($memorial_header)):
            ?>
                <div class="hkfn-memory-text"><?php echo esc_html($memorial_header); ?></div>
            <?php endif; ?>
            <h1 class="hkfn-current-name"><?php echo esc_html($person['full_name']); ?></h1>
            <?php if ($person['birth_year'] && $person['death_year']): ?>
                <div class="hkfn-current-dates">
                    <?php echo esc_html($person['birth_year'] . ' - ' . $person['death_year']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Portrait Image -->
        <?php if ($image['featured_url'] || $image['fallback_url']): ?>
            <div class="hkfn-current-image">
                <img src="<?php echo esc_url($image['featured_url'] ?: $image['fallback_url']); ?>"
                     alt="<?php echo esc_attr($person['full_name']); ?>"
                     class="hkfn-current-portrait">
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
            <div class="hkfn-celebration-text">
                <h2><?php echo wp_kses_post($celebration_text); ?></h2>
            </div>
        <?php endif; ?>

        <?php 
        // Check if we have content for the left column
        // Only consider tribute button as content if there's also notice content
        $has_left_content = !empty($content['notice']);
        ?>
        
        <!-- Main Content Layout -->
        <div class="hkfn-current-layout <?php echo $has_left_content ? '' : 'hkfn-current-layout-single'; ?>">

            <?php if ($has_left_content): ?>
            <!-- Left Column: Main Content -->
            <div class="hkfn-current-left-column">

                <?php // Removed duplicate intro check - already handled in header ?>

                <?php if ($content['notice']): ?>
                    <div class="hkfn-current-notice">
                        <?php echo wp_kses_post($content['notice']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($tribute['show_button']): ?>
                    <div class="hkfn-current-tribute">
                        <?php if ($tribute['has_url']): ?>
                            <p>Unable to attend the service? <a href="<?php echo esc_url($tribute['full_url']); ?>"
                               target="_blank" rel="noopener">Send a tribute to the family</a></p>
                        <?php else: ?>
                            <p><span style="color: #999;">Send a tribute to the family (Configure URL in Settings)</span></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

            <!-- Right Column: Service Details -->
            <div class="hkfn-current-right-column">

                <?php if (($event['formatted_date'] || $event['formatted_time']) && !$event['hide_time']): ?>
                    <div class="hkfn-current-when">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <h3 style="margin: 0;">WHEN</h3>
                            <button class="hkfn-share-button"
                                    data-url="<?php echo esc_url($share['url']); ?>"
                                    data-title="<?php echo esc_attr($share['title']); ?>"
                                    data-message="<?php echo esc_attr(wp_unslash($share['message'])); ?>"
                                    aria-label="Share this funeral notice">
                                <?php echo hkfn_get_share_icon('', 18); ?>
                                <span>Share</span>
                            </button>
                        </div>
                        <div class="hkfn-service-datetime">
                            <?php if ($event['formatted_date']): ?>
                                <span class="hkfn-service-date"><?php echo esc_html($event['formatted_date']); ?></span>
                            <?php endif; ?>
                            <?php if ($event['formatted_time']): ?>
                                <?php if ($event['formatted_date']): ?> at <?php endif; ?>
                                <span class="hkfn-service-time"><?php echo esc_html($event['formatted_time']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($location['show_location'] && ($location['display_venue'] || !empty($location['display_address'])) && !$event['hide_time']): ?>
                    <div class="hkfn-current-where">
                        <h3>WHERE</h3>
                        <?php if ($location['display_venue']): ?>
                            <div class="hkfn-venue-name"><?php echo esc_html($location['display_venue']); ?></div>
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
                                    echo '<div class="hkfn-address-line">' . esc_html($line) . '</div>';
                                }
                            }
                        }
                        ?>
                        <?php if (!empty($location['maps_url'])): ?>
                            <div class="hkfn-maps-link">
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
                    <div class="hkfn-current-service-sheet">
                        <a href="<?php echo esc_url($documents['service_sheet']['url']); ?>"
                           target="_blank"
                           class="hkfn-service-sheet-btn">
                           <?php echo hkfn_get_document_icon(); ?> Download Service Sheet
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Memorial Video Slideshow Button -->
                <?php if (!empty($documents['video_slideshow'])): ?>
                    <div class="hkfn-current-video-slideshow">
                        <button type="button"
                                class="hkfn-memorial-video-btn"
                                data-video-modal="<?php echo esc_attr($documents['video_slideshow']['modal_id']); ?>">
                                <?php echo hkfn_get_video_icon(); ?> View Slideshow
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Additional Documents (in right column) -->
                <?php if (!empty($documents['additional'])): ?>
                    <div class="hkfn-current-additional-docs">
                        <?php foreach ($documents['additional'] as $doc): ?>
                            <?php
                            // Choose icon based on document type
                            $icon_function = 'hkfn_get_document_icon'; // Default document icon
                            if (isset($doc['document_type']) && $doc['document_type'] === 'url') {
                                $icon_function = 'hkfn_get_link_icon'; // Link icon for external URLs
                            } elseif (isset($doc['document_type']) && $doc['document_type'] === 'file') {
                                $icon_function = 'hkfn_get_document_icon'; // Document icon for files
                            }
                            // Smart icon detection based on URL if type not available
                            if (!isset($doc['document_type']) && isset($doc['url'])) {
                                $url = strtolower($doc['url']);
                                if (strpos($url, 'donate') !== false || strpos($url, 'givealittle') !== false) {
                                    $icon_function = 'hkfn_get_link_icon'; // Use link icon for donations (no specific icon available)
                                } elseif (strpos($url, 'youtube') !== false || strpos($url, 'vimeo') !== false) {
                                    $icon_function = 'hkfn_get_video_icon'; // Video icon
                                } elseif (strpos($url, 'photo') !== false || strpos($url, 'gallery') !== false) {
                                    $icon_function = 'hkfn_get_link_icon'; // Use link icon for photos (no specific icon available)
                                } elseif (!preg_match('/\.(pdf|doc|docx)$/i', $url)) {
                                    $icon_function = 'hkfn_get_link_icon'; // Link icon for external URLs
                                }
                            }
                            ?>
                            <a href="<?php echo esc_url($doc['url']); ?>"
                               target="_blank"
                               class="hkfn-document-link">
                               <?php echo $icon_function(); ?> <?php echo esc_html($doc['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php // Show tribute button in right column when there's no left content ?>
                <?php if (!$has_left_content && $tribute['show_button']): ?>
                    <div class="hkfn-current-tribute hkfn-current-tribute-right">
                        <?php if ($tribute['has_url']): ?>
                            <p>Unable to attend the service? <a href="<?php echo esc_url($tribute['full_url']); ?>"
                               target="_blank" rel="noopener">Send a tribute to the family</a></p>
                        <?php else: ?>
                            <p><span style="color: #999;">Send a tribute to the family (Configure URL in Settings)</span></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>

        <!-- Streaming Section -->
        <?php if ($streaming['is_public']): ?>
            <div class="hkfn-current-stream-section">
                <h2 class="hkfn-stream-title">View the Funeral Stream</h2>

                <?php $stream_container_classes = 'hkfn-stream-container';
                if ($streaming['streaming_service'] === 'oneroom') { $stream_container_classes .= ' hkfn-oneroom'; }
                ?>
                <div class="<?php echo esc_attr($stream_container_classes); ?>">

                    <?php if ($streaming['embed_code']): ?>
                        <!-- StreamingDetector has generated the appropriate embed or button -->
                        <?php echo $streaming['embed_code']; ?>

                    <?php elseif ($streaming['streaming_url']): ?>
                        <!-- Fallback: simple button for unrecognized services -->
                        <div class="hkfn-stream-button-wrapper">
                            <a href="<?php echo esc_url($streaming['streaming_url']); ?>"
                               target="_blank"
                               rel="noopener"
                               class="hkfn-stream-btn">
                               <?php echo hkfn_get_stream_icon(); ?> View Funeral Stream
                            </a>
                        </div>

                    <?php else: ?>
                        <!-- Fallback message -->
                        <div class="hkfn-stream-placeholder">
                            <p>Live streaming will be available during the service.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Streaming Note -->
                    <?php if ($streaming['streaming_note']): ?>
                        <div class="hkfn-streaming-note">
                            <p><?php echo esc_html($streaming['streaming_note']); ?></p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

        <!-- Video Modal -->
        <?php if (!empty($documents['video_slideshow'])): ?>
            <?php $video = $documents['video_slideshow']; ?>
            <div id="<?php echo esc_attr($video['modal_id']); ?>"
                 class="hkfn-video-modal"
                 role="dialog"
                 aria-modal="true"
                 aria-hidden="true"
                 aria-labelledby="<?php echo esc_attr($video['modal_id']); ?>-title">

                <div class="hkfn-video-modal-overlay"></div>

                <div class="hkfn-video-modal-content">
                    <div class="hkfn-video-modal-header">
                        <h3 id="<?php echo esc_attr($video['modal_id']); ?>-title">
                            <?php echo esc_html($video['title']); ?>
                        </h3>
                        <div class="hkfn-video-modal-actions">
                            <a href="<?php echo esc_url($video['stream_url']); ?>"
                               target="_blank"
                               class="hkfn-video-new-window">
                               View in new window
                            </a>
                            <button type="button"
                                    class="hkfn-video-modal-close"
                                    data-close-modal="<?php echo esc_attr($video['modal_id']); ?>"
                                    aria-label="Close video modal">
                                ×
                            </button>
                        </div>
                    </div>

                    <div class="hkfn-video-modal-body">
                        <div class="hkfn-video-container">
                            <div class="hkfn-video-responsive-wrapper">
                                <iframe src="<?php echo esc_url($video['stream_url']); ?>"
                                        frameborder="0"
                                        allowfullscreen
                                        allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        title="<?php echo esc_attr($video['title']); ?>">
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>