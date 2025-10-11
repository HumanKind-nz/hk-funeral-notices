<?php
/**
 * Service Sheets Partial Template
 * Shows downloadable service documents
 * 
 * @var int $post_id
 * @var array $args
 * @var string $mode
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load SVG icon functions
require_once __DIR__ . '/svg-icons.php';

// Get media group data
$media_group = get_field('wfn_media_group', $post_id) ?: [];
$service_sheet = $media_group['service_sheet'] ?? null;
$additional_docs = $media_group['additional_documents'] ?? [];

// Check for video slideshow (direct upload system)
$video_status = get_post_meta($post_id, '_wfn_video_status', true);
$video_id = get_post_meta($post_id, '_wfn_video_id', true);
$video_data = get_post_meta($post_id, '_wfn_video_data', true);

// Decode JSON if needed
if (is_string($video_data)) {
    $video_data = json_decode($video_data, true);
}

// Only show video if ready
$has_video = ($video_status === 'ready' && !empty($video_id) && !empty($video_data));

// Check if we have any documents to show
$has_documents = !empty($service_sheet) || !empty($additional_docs) || $has_video;

if (!$has_documents) {
    return;
}

// Get mode for styling
$mode = $args['mode'] ?? 'modern';
?>

<?php if ($mode === 'current'): ?>
    <!-- Current layout styling - match Service Information structure -->
    <div class="wfn-current-service-info">
        <div class="wfn-current-service-details">
            <h3>Service Documents</h3>
<?php elseif ($mode === 'firehawk'): ?>
    <!-- Firehawk layout styling -->
    <div class="firehawk-service-section">
        <h3 class="firehawk-section-title">Service Documents</h3>
<?php else: ?>
    <!-- Modern and Elegant layouts -->
    <div class="wfn-service-documents wfn-<?php echo esc_attr($mode); ?>-service-documents">
        <?php if ($mode === 'elegant'): ?>
            <h2 class="wfn-elegant-section-title">Service Documents</h2>
        <?php else: // modern and default ?>
            <h2 class="wfn-modern-section-title">Service Documents</h2>
        <?php endif; ?>
<?php endif; ?>
    
    <?php if ($mode === 'current'): ?>
        <!-- Current layout content -->
            <?php if ($service_sheet): ?>
                <p><strong>Service Sheet:</strong> <a href="<?php echo esc_url($service_sheet['url']); ?>" target="_blank" rel="noopener"><?php echo wfn_get_document_icon(); ?> Download Service Sheet</a></p>
            <?php endif; ?>
            <?php if ($has_video): ?>
                <p><strong>Memorial Video Slideshow:</strong> <a href="<?php echo esc_url($video_data['stream_url'] ?? ''); ?>" target="_blank" rel="noopener">📹 Watch Memorial Video</a></p>
            <?php endif; ?>
            <?php if (!empty($additional_docs)): ?>
                <?php foreach ($additional_docs as $doc): ?>
                    <?php if (!empty($doc['file'])): ?>
                        <p><strong><?php echo esc_html($doc['title'] ?: 'Additional Document'); ?>:</strong> <a href="<?php echo esc_url($doc['file']['url']); ?>" target="_blank" rel="noopener"><?php echo wfn_get_document_icon(); ?> Download</a></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    <?php elseif ($mode === 'firehawk'): ?>
        <!-- Firehawk layout styling - match service details structure -->
        <div class="firehawk-service-details">
            <?php if ($service_sheet): ?>
                <div class="firehawk-detail-row">
                    <span class="firehawk-detail-label">Service Sheet:</span>
                    <span class="firehawk-detail-value">
                        <a href="<?php echo esc_url($service_sheet['url']); ?>"
                           target="_blank"
                           rel="noopener"
                           class="firehawk-maps-button">
                           <?php echo wfn_get_document_icon(); ?> Download Service Sheet
                        </a>
                    </span>
                </div>
            <?php endif; ?>
            <?php if ($has_video): ?>
                <div class="firehawk-detail-row">
                    <span class="firehawk-detail-label">Memorial Video:</span>
                    <span class="firehawk-detail-value">
                        <a href="<?php echo esc_url($video_data['stream_url'] ?? ''); ?>"
                           target="_blank"
                           rel="noopener"
                           class="firehawk-maps-button">
                           📹 Watch Memorial Video
                        </a>
                    </span>
                </div>
            <?php endif; ?>
            <?php if (!empty($additional_docs)): ?>
                <?php foreach ($additional_docs as $doc): ?>
                    <?php if (!empty($doc['file'])): ?>
                        <div class="firehawk-detail-row">
                            <span class="firehawk-detail-label"><?php echo esc_html($doc['title'] ?: 'Document'); ?>:</span>
                            <span class="firehawk-detail-value">
                                <a href="<?php echo esc_url($doc['file']['url']); ?>"
                                   target="_blank"
                                   rel="noopener"
                                   class="firehawk-maps-button">
                                   <?php echo wfn_get_document_icon(); ?> Download
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <!-- Modern and Elegant layouts - structured document items -->
        <div class="wfn-documents-list wfn-<?php echo esc_attr($mode); ?>-documents-list">
            <?php if ($service_sheet): ?>
                <div class="wfn-document-item wfn-<?php echo esc_attr($mode); ?>-document-item">
                    <div class="wfn-document-info wfn-<?php echo esc_attr($mode); ?>-document-info">
                        <h3 class="wfn-document-title wfn-<?php echo esc_attr($mode); ?>-document-title">Service Sheet</h3>
                        <p class="wfn-document-description wfn-<?php echo esc_attr($mode); ?>-document-description">Order of service and program details</p>
                    </div>
                    <div class="wfn-document-actions wfn-<?php echo esc_attr($mode); ?>-document-actions">
                        <a href="<?php echo esc_url($service_sheet['url']); ?>"
                           target="_blank"
                           rel="noopener"
                           class="wfn-download-button wfn-<?php echo esc_attr($mode); ?>-download-button">
                           <?php echo wfn_get_document_icon(); ?> Download Service Sheet
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($has_video): ?>
                <div class="wfn-document-item wfn-<?php echo esc_attr($mode); ?>-document-item wfn-video-item">
                    <div class="wfn-document-info wfn-<?php echo esc_attr($mode); ?>-document-info">
                        <h3 class="wfn-document-title wfn-<?php echo esc_attr($mode); ?>-document-title">Memorial Video Slideshow</h3>
                        <p class="wfn-document-description wfn-<?php echo esc_attr($mode); ?>-document-description">Watch the memorial video tribute</p>
                    </div>
                    <div class="wfn-document-actions wfn-<?php echo esc_attr($mode); ?>-document-actions">
                        <a href="<?php echo esc_url($video_data['stream_url'] ?? ''); ?>"
                           target="_blank"
                           rel="noopener"
                           class="wfn-download-button wfn-<?php echo esc_attr($mode); ?>-download-button wfn-video-button">
                           📹 Watch Memorial Video
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($additional_docs)): ?>
                <?php foreach ($additional_docs as $doc): ?>
                    <?php if (!empty($doc['file'])): ?>
                        <div class="wfn-document-item wfn-<?php echo esc_attr($mode); ?>-document-item">
                            <div class="wfn-document-info wfn-<?php echo esc_attr($mode); ?>-document-info">
                                <h3 class="wfn-document-title wfn-<?php echo esc_attr($mode); ?>-document-title">
                                    <?php echo esc_html($doc['title'] ?: 'Additional Document'); ?>
                                </h3>
                                <?php if (!empty($doc['file']['filename'])): ?>
                                    <p class="wfn-document-filename wfn-<?php echo esc_attr($mode); ?>-document-filename"><?php echo esc_html($doc['file']['filename']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="wfn-document-actions wfn-<?php echo esc_attr($mode); ?>-document-actions">
                                <a href="<?php echo esc_url($doc['file']['url']); ?>" 
                                   target="_blank" 
                                   rel="noopener"
                                   class="wfn-download-button wfn-<?php echo esc_attr($mode); ?>-download-button">
                                   <?php echo wfn_get_document_icon(); ?> Download
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php if ($mode === 'current'): ?>
        </div>
    </div>
<?php elseif ($mode === 'firehawk'): ?>
    </div>
<?php else: ?>
    </div>
<?php endif; ?> 