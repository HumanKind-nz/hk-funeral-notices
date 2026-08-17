<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Services;

/**
 * Audit trail for Bunny Stream video deletions.
 *
 * Every deletion attempt is recorded, whether it succeeded, was blocked by an
 * ownership check, or failed. Nothing here deletes anything: it exists so that
 * if a video goes missing you can find out when, why, and what triggered it,
 * and so a periodic reconcile against the Bunny library has something to
 * compare against.
 *
 * Stored in a single non-autoloaded option as a capped ring buffer. Deletions
 * are rare, so this stays small, and it avoids adding a table to 40+ sites.
 *
 * @package HumanKind\FuneralNotices\Services
 * @since 3.1.0
 */
class VideoAuditLog {

    /** Option holding the log. Not autoloaded. */
    private const OPTION = 'hkfn_video_audit_log';

    /** Maximum entries retained. Oldest are dropped first. */
    private const MAX_ENTRIES = 500;

    /**
     * Record a deletion attempt.
     *
     * @param string $video_id Bunny video ID.
     * @param string $outcome  One of: deleted, blocked, failed, already_gone, skipped.
     * @param string $reason   Human-readable explanation.
     * @param array  $context  Extra detail (post_id, title, error_code, trigger).
     */
    public static function record(string $video_id, string $outcome, string $reason = '', array $context = []): void {
        $entry = [
            'time'     => current_time('mysql', true),
            'video_id' => $video_id,
            'outcome'  => $outcome,
            'reason'   => $reason,
            'user'     => self::current_actor(),
            'trigger'  => $context['trigger'] ?? self::detect_trigger(),
            'post_id'  => $context['post_id'] ?? self::find_post_for_video($video_id),
            'title'    => $context['title'] ?? '',
        ];

        if (!empty($context['error_code'])) {
            $entry['error_code'] = $context['error_code'];
        }

        $log = self::all();
        array_unshift($log, $entry);

        if (count($log) > self::MAX_ENTRIES) {
            $log = array_slice($log, 0, self::MAX_ENTRIES);
        }

        update_option(self::OPTION, $log, false);
    }

    /**
     * Read the log, newest first.
     *
     * @return array
     */
    public static function all(): array {
        $log = get_option(self::OPTION, []);
        return is_array($log) ? $log : [];
    }

    /**
     * Entries whose outcome is a real deletion.
     *
     * @return array
     */
    public static function deletions(): array {
        return array_values(array_filter(self::all(), static function ($e) {
            return ($e['outcome'] ?? '') === 'deleted';
        }));
    }

    /**
     * Empty the log.
     */
    public static function clear(): void {
        delete_option(self::OPTION);
    }

    /**
     * Who triggered this: a logged-in user, WP-CLI, or cron.
     */
    private static function current_actor(): string {
        if (defined('WP_CLI') && WP_CLI) {
            return 'wp-cli';
        }

        if (wp_doing_cron()) {
            return 'cron';
        }

        $user = function_exists('wp_get_current_user') ? wp_get_current_user() : null;

        if ($user && $user->exists()) {
            return $user->user_login;
        }

        return 'unknown';
    }

    /**
     * Best-effort name of the plugin method that triggered the deletion.
     *
     * Deletion runs from many call sites; recording which one makes an
     * unexpected deletion traceable without guesswork.
     */
    private static function detect_trigger(): string {
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);

        foreach ($frames as $frame) {
            $class = $frame['class'] ?? '';
            $fn    = $frame['function'] ?? '';

            if ($class === self::class || $fn === 'record' || $fn === 'detect_trigger') {
                continue;
            }

            if ($fn === 'delete_video') {
                continue;
            }

            if (strpos($class, 'HumanKind\\FuneralNotices') === 0) {
                $short = substr(strrchr($class, '\\') ?: $class, 1);
                return $short . '::' . $fn;
            }
        }

        return 'unknown';
    }

    /**
     * Find the funeral notice referencing a video, if it still exists.
     *
     * @return int|null
     */
    private static function find_post_for_video(string $video_id): ?int {
        global $wpdb;

        if (empty($video_id)) {
            return null;
        }

        $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                '_hkfn_video_id',
                $video_id
            )
        );

        if (!$post_id) {
            $post_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                    '_wfn_video_id',
                    $video_id
                )
            );
        }

        return $post_id ? (int) $post_id : null;
    }
}
