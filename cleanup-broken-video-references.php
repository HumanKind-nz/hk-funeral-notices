<?php
/**
 * Cleanup Broken Video References
 *
 * This script removes all video metadata from funeral notices where
 * the video no longer exists in BunnyStream. This prevents broken
 * video buttons from showing on the frontend.
 *
 * Run via: wp eval-file cleanup-broken-video-references.php
 *
 * IMPORTANT: This is SAFE - it only removes WordPress metadata.
 * It does NOT delete any videos from BunnyStream.
 */

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

echo "🧹 Cleanup Broken Video References\n";
echo str_repeat('=', 70) . "\n\n";

// Get BunnyStream configuration
$library_id = defined('WFN_BUNNYSTREAM_LIBRARY_ID') ? WFN_BUNNYSTREAM_LIBRARY_ID :
              (defined('WFN_VIDEO_LIBRARY_ID') ? WFN_VIDEO_LIBRARY_ID : null);

if (!$library_id) {
    die("❌ ERROR: Bunny library ID not configured\n");
}

echo "Site: " . get_site_url() . "\n";
echo "Library ID: {$library_id}\n";
echo "Date: " . current_time('mysql') . "\n\n";

// Initialize BunnyStream service
try {
    $bunny_service = new WeaveStudios\FuneralNotices\Services\BunnyStreamService();

    if (!$bunny_service->is_configured()) {
        die("❌ ERROR: BunnyStream service not configured\n");
    }

    echo "✅ BunnyStream service connected\n\n";

} catch (Exception $e) {
    die("❌ ERROR: Failed to initialize BunnyStream: " . $e->getMessage() . "\n");
}

// Get all funeral notices with video metadata
echo "📊 Finding funeral notices with video metadata...\n";

$posts_with_video = get_posts([
    'post_type' => 'funeral-notice',
    'post_status' => ['publish', 'draft', 'pending', 'private'],
    'meta_query' => [
        [
            'key' => '_wfn_video_id',
            'compare' => 'EXISTS'
        ]
    ],
    'posts_per_page' => -1
]);

$total_posts = count($posts_with_video);
echo "Found {$total_posts} funeral notices with video IDs\n\n";

if ($total_posts === 0) {
    echo "✅ No video metadata found. Nothing to clean up.\n";
    exit;
}

// Get all videos currently in BunnyStream
echo "📊 Fetching current videos from BunnyStream...\n";
$bunny_result = $bunny_service->list_all_videos();

if (!$bunny_result['success']) {
    die("❌ ERROR: Failed to fetch BunnyStream videos: " . $bunny_result['message'] . "\n");
}

$bunny_videos = $bunny_result['videos'];
$bunny_video_ids = array_map(function($video) {
    return $video['guid'];
}, $bunny_videos);

echo "Found " . count($bunny_videos) . " videos in BunnyStream\n\n";

// Ask for confirmation before proceeding
echo str_repeat('=', 70) . "\n";
echo "⚠️  CONFIRMATION REQUIRED\n";
echo str_repeat('=', 70) . "\n";
echo "This will remove video metadata from WordPress posts where the video\n";
echo "no longer exists in BunnyStream. This will:\n\n";
echo "  ✅ Hide broken video buttons on the frontend\n";
echo "  ✅ Free up the video field for re-uploading\n";
echo "  ✅ Clean up confusing 'ready' status indicators\n\n";
echo "  ❌ Does NOT delete any videos from BunnyStream\n";
echo "  ❌ Does NOT delete any funeral notice posts\n\n";

// Check if running in interactive mode
if (defined('WP_CLI') && WP_CLI) {
    // WP-CLI - ask for confirmation
    fwrite(STDOUT, "Do you want to proceed? (yes/no): ");
    $confirmation = trim(fgets(STDIN));

    if (strtolower($confirmation) !== 'yes') {
        echo "\n❌ Cleanup cancelled by user.\n";
        exit;
    }
} else {
    // Browser mode - require URL parameter
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
        echo "To confirm, add ?confirm=yes to the URL\n";
        exit;
    }
}

echo "\n";
echo str_repeat('=', 70) . "\n";
echo "🧹 CLEANING UP BROKEN REFERENCES\n";
echo str_repeat('=', 70) . "\n\n";

$cleaned_count = 0;
$skipped_count = 0;
$error_count = 0;

foreach ($posts_with_video as $post) {
    $video_id = get_post_meta($post->ID, '_wfn_video_id', true);
    $video_status = get_post_meta($post->ID, '_wfn_video_status', true);

    // Get person details for display
    $person_group = get_field('wfn_person_group', $post->ID) ?: [];
    $person_name = trim(($person_group['firstname'] ?? '') . ' ' . ($person_group['lastname'] ?? ''));
    if (empty($person_name)) {
        $person_name = $post->post_title;
    }

    // Check if video exists in Bunny
    $exists_in_bunny = in_array($video_id, $bunny_video_ids);

    if (!$exists_in_bunny) {
        // Video is missing - clean up metadata
        echo "🧹 Cleaning Post #{$post->ID}: {$person_name}\n";
        echo "   Video ID: {$video_id}\n";
        echo "   Status was: " . ($video_status ?: 'unknown') . "\n";

        try {
            // Remove all video-related metadata
            delete_post_meta($post->ID, '_wfn_video_id');
            delete_post_meta($post->ID, '_wfn_video_metadata');
            delete_post_meta($post->ID, '_wfn_video_status');
            delete_post_meta($post->ID, '_wfn_video_upload_status');
            delete_post_meta($post->ID, '_wfn_video_upload_job');
            delete_post_meta($post->ID, '_wfn_video_data');
            delete_post_meta($post->ID, '_wfn_video_id_old');
            delete_post_meta($post->ID, '_wfn_video_upload_session');

            // Clear the ACF field value (if it references the missing video)
            $media_group = get_field('wfn_media_group', $post->ID);
            if (is_array($media_group)) {
                // Keep other media, just clear video_slideshow
                $media_group['video_slideshow'] = '';
                update_field('wfn_media_group', $media_group, $post->ID);
            }

            echo "   ✅ Cleaned up successfully\n\n";
            $cleaned_count++;

        } catch (Exception $e) {
            echo "   ❌ Error: " . $e->getMessage() . "\n\n";
            $error_count++;
        }

    } else {
        // Video exists - skip
        $skipped_count++;
        // Don't output for each skip - too noisy
    }
}

// Summary
echo str_repeat('=', 70) . "\n";
echo "📊 CLEANUP SUMMARY\n";
echo str_repeat('=', 70) . "\n";
echo "Site: " . get_site_url() . "\n";
echo "Total posts checked: {$total_posts}\n";
echo "Broken references cleaned: {$cleaned_count}\n";
echo "Valid videos skipped: {$skipped_count}\n";
echo "Errors: {$error_count}\n\n";

if ($cleaned_count > 0) {
    echo "✅ Cleanup complete! Broken video buttons will no longer appear.\n";
    echo "   Video fields are now free for re-uploading.\n\n";
} else {
    echo "✅ No broken references found. All video IDs are valid!\n\n";
}

// Save cleanup log
$cleanup_log = [
    'site' => get_site_url(),
    'timestamp' => current_time('mysql'),
    'total_posts' => $total_posts,
    'cleaned' => $cleaned_count,
    'skipped' => $skipped_count,
    'errors' => $error_count
];

update_option('wfn_last_video_cleanup', $cleanup_log);

echo "📝 Cleanup log saved to: wfn_last_video_cleanup option\n";
echo "\n✨ Done!\n";
