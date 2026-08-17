<?php
/**
 * WP-CLI commands for auditing video storage.
 *
 * Everything here is read-only by default. Nothing in this file deletes a
 * video unless you pass an explicit flag and confirm, and even then it routes
 * through the same ownership checks as the rest of the plugin.
 *
 * @package HumanKind\FuneralNotices
 */

declare( strict_types=1 );

namespace HumanKind\FuneralNotices\CLI;

defined( 'ABSPATH' ) || exit;

use HumanKind\FuneralNotices\Services\BunnyStreamService;
use HumanKind\FuneralNotices\Services\LicenseService;
use HumanKind\FuneralNotices\Services\VideoAuditLog;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Inspect memorial video storage.
 */
class Video_Command {

	/**
	 * Show the video deletion audit log, newest first.
	 *
	 * ## OPTIONS
	 *
	 * [--outcome=<outcome>]
	 * : Filter by outcome. One of: deleted, blocked, failed, already_gone, skipped.
	 *
	 * [--limit=<number>]
	 * : How many entries to show. Default 50.
	 *
	 * [--format=<format>]
	 * : Output format. table, json, csv, yaml. Default table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp hkfn video log
	 *     wp hkfn video log --outcome=deleted
	 *     wp hkfn video log --format=csv > deletions.csv
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function log( $args, $assoc_args ): void {
		$entries = VideoAuditLog::all();
		$outcome = $assoc_args['outcome'] ?? '';
		$limit   = (int) ( $assoc_args['limit'] ?? 50 );
		$format  = $assoc_args['format'] ?? 'table';

		if ( $outcome ) {
			$entries = array_values( array_filter( $entries, static function ( $e ) use ( $outcome ) {
				return ( $e['outcome'] ?? '' ) === $outcome;
			} ) );
		}

		if ( ! $entries ) {
			\WP_CLI::success( 'No video deletion activity recorded.' );
			return;
		}

		$total   = count( $entries );
		$entries = array_slice( $entries, 0, $limit );

		\WP_CLI\Utils\format_items(
			$format,
			$entries,
			[ 'time', 'outcome', 'video_id', 'post_id', 'title', 'user', 'trigger', 'reason' ]
		);

		if ( $total > $limit ) {
			\WP_CLI::log( sprintf( 'Showing %d of %d entries. Use --limit to see more.', $limit, $total ) );
		}
	}

	/**
	 * Compare the Bunny library against videos this site still references.
	 *
	 * Read-only. Reports three groups:
	 *   - orphaned: in Bunny, no funeral notice points at it (safe to prune by hand)
	 *   - missing:  a notice points at a video that is no longer in Bunny
	 *   - matched:  in Bunny and referenced
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format for the detail tables. table, json, csv, yaml. Default table.
	 *
	 * [--show=<group>]
	 * : Which group to list. One of: orphaned, missing, matched, all. Default orphaned.
	 *
	 * ## EXAMPLES
	 *
	 *     wp hkfn video reconcile
	 *     wp hkfn video reconcile --show=all
	 *     wp hkfn video reconcile --show=orphaned --format=csv > orphans.csv
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function reconcile( $args, $assoc_args ): void {
		global $wpdb;

		if ( ! LicenseService::isVideoConfigured() ) {
			\WP_CLI::error( 'Video hosting is not configured on this site. Missing: ' . implode( ', ', LicenseService::getMissingVideoConfig() ) );
		}

		$format = $assoc_args['format'] ?? 'table';
		$show   = $assoc_args['show'] ?? 'orphaned';

		\WP_CLI::log( 'Fetching video library from Bunny...' );

		$service = new BunnyStreamService();
		$remote  = [];
		$page    = 1;

		do {
			$result = $service->list_all_videos( $page, 100 );

			if ( empty( $result['success'] ) ) {
				\WP_CLI::error( 'Could not list videos: ' . ( $result['message'] ?? 'unknown error' ) );
			}

			$items = $result['videos'] ?? [];

			foreach ( $items as $video ) {
				// Bunny identifies a video by its guid.
				$id = $video['guid'] ?? '';
				if ( $id ) {
					$remote[ $id ] = $video['title'] ?? '';
				}
			}

			$page++;
		} while ( count( $items ) === 100 );

		\WP_CLI::log( sprintf( 'Bunny library holds %d video(s).', count( $remote ) ) );

		// Videos this site still points at.
		$rows = $wpdb->get_results(
			"SELECT pm.post_id, pm.meta_value AS video_id, p.post_title, p.post_status
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key IN ( '_hkfn_video_id', '_wfn_video_id' )
			   AND pm.meta_value != ''",
			ARRAY_A
		);

		$referenced = [];
		foreach ( $rows as $row ) {
			$referenced[ $row['video_id'] ] = $row;
		}

		\WP_CLI::log( sprintf( 'This site references %d video(s).', count( $referenced ) ) );

		$orphaned = [];
		$matched  = [];
		foreach ( $remote as $id => $title ) {
			if ( isset( $referenced[ $id ] ) ) {
				$matched[] = [
					'video_id' => $id,
					'title'    => $title,
					'post_id'  => $referenced[ $id ]['post_id'],
					'notice'   => $referenced[ $id ]['post_title'],
					'status'   => $referenced[ $id ]['post_status'],
				];
			} else {
				$orphaned[] = [ 'video_id' => $id, 'title' => $title ];
			}
		}

		$missing = [];
		foreach ( $referenced as $id => $row ) {
			if ( ! isset( $remote[ $id ] ) ) {
				$missing[] = [
					'video_id' => $id,
					'post_id'  => $row['post_id'],
					'notice'   => $row['post_title'],
					'status'   => $row['post_status'],
				];
			}
		}

		\WP_CLI::log( '' );
		\WP_CLI::log( sprintf( 'matched:  %d', count( $matched ) ) );
		\WP_CLI::log( sprintf( 'orphaned: %d  (in Bunny, nothing points at them)', count( $orphaned ) ) );
		\WP_CLI::log( sprintf( 'missing:  %d  (a notice points at them, not in Bunny)', count( $missing ) ) );
		\WP_CLI::log( '' );

		if ( $missing ) {
			\WP_CLI::warning( 'Some notices reference videos that are no longer in Bunny. Check `wp hkfn video log` for what happened to them.' );
		}

		$by_group = [
			'orphaned' => $orphaned,
			'missing'  => $missing,
			'matched'  => $matched,
		];

		$groups = ( 'all' === $show ) ? array_keys( $by_group ) : [ $show ];

		foreach ( $groups as $group ) {
			if ( ! isset( $by_group[ $group ] ) ) {
				\WP_CLI::warning( sprintf( 'Unknown group "%s". Use orphaned, missing, matched, or all.', $group ) );
				continue;
			}

			$data = $by_group[ $group ];

			if ( ! $data ) {
				\WP_CLI::log( sprintf( '%s: none', $group ) );
				continue;
			}

			\WP_CLI::log( sprintf( '--- %s ---', $group ) );
			\WP_CLI\Utils\format_items( $format, $data, array_keys( $data[0] ) );
			\WP_CLI::log( '' );
		}

		\WP_CLI::log( 'Nothing was deleted. Prune orphans manually in the Bunny dashboard once you have checked them.' );
	}
}

\WP_CLI::add_command( 'hkfn video', __NAMESPACE__ . '\Video_Command' );
