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
        <header class="wfn-current-header">
            <?php if (!$content['hide_intro']): ?>
                <div class="wfn-memory-text">In loving memory of</div>
            <?php endif; ?>
            <h1 class="wfn-current-name"><?php echo esc_html($person['full_name']); ?></h1>
            <?php if ($person['birth_year'] && $person['death_year']): ?>
                <div class="wfn-current-dates">
                    <?php echo esc_html($person['birth_year'] . ' - ' . $person['death_year']); ?>
                </div>
            <?php endif; ?>
        </header>

        <!-- Portrait Image -->
        <?php if ($image['featured_url'] || $image['fallback_url']): ?>
            <div class="wfn-current-image">
                <img src="<?php echo esc_url($image['featured_url'] ?: $image['fallback_url']); ?>"
                     alt="<?php echo esc_attr($person['full_name']); ?>"
                     class="wfn-current-portrait">
            </div>
        <?php endif; ?>

        <!-- Main Content Layout -->
        <div class="wfn-current-layout">

            <!-- Left Column: Main Content -->
            <div class="wfn-current-left-column">

                <?php if (!$content['hide_intro']): ?>
                    <div class="wfn-current-intro">
                        Please join us in celebrating <?php echo esc_html($person['full_name']); ?>'s life.
                    </div>
                <?php endif; ?>

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

                <?php if ($location['show_location'] && ($location['display_venue'] || !empty($location['display_address']))): ?>
                    <div class="wfn-current-where">
                        <h3>WHERE</h3>
                        <?php if ($location['display_venue']): ?>
                            <div class="wfn-venue-name"><?php echo esc_html($location['display_venue']); ?></div>
                        <?php endif; ?>
                        <?php
                        // Use formatted address for clean display
                        $formatted_address = $location['formatted_address'];
                        if (!empty($formatted_address)) {
                            // Split formatted address into lines for better display
                            $lines = explode(', ', $formatted_address);
                            foreach ($lines as $line) {
                                if (!empty(trim($line))) {
                                    echo '<div class="wfn-address-line">' . esc_html(trim($line)) . '</div>';
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

            </div>

        </div>

        <!-- Streaming Section -->
        <?php if ($streaming['is_public']): ?>
            <div class="wfn-current-stream-section">
                <h2 class="wfn-stream-title">View the Funeral Stream</h2>

                <div class="wfn-stream-container">

                    <?php if ($streaming['streaming_service'] === 'oneroom' && $streaming['embed_code']): ?>
                        <!-- OneRoom embed -->
                        <div class="wfn-video-wrapper">
                            <?php echo $streaming['embed_code']; ?>
                        </div>

                    <?php elseif (in_array($streaming['streaming_service'], ['youtube', 'vimeo', 'vimeo_pro']) && $streaming['embed_code']): ?>
                        <!-- YouTube/Vimeo embed -->
                        <div class="wfn-video-wrapper">
                            <?php echo $streaming['embed_code']; ?>
                        </div>

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

                </div>
            </div>
        <?php endif; ?>



    </div>
</div>