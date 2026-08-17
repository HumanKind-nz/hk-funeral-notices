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
	 * Export this site's video references, for a fleet-wide prune.
	 *
	 * One Bunny library is shared by many sites, each with its own collection.
	 * To work out what is genuinely unused you need every site's inventory
	 * before you can judge any single video, so run this on each site, collect
	 * the JSON, then compare the union against the library.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : json (default) or csv.
	 *
	 * ## EXAMPLES
	 *
	 *     wp hkfn video inventory > /tmp/inventory-$(hostname).json
	 *
	 *     # across a GridPane server, one file per site
	 *     for d in $(ls /var/www); do
	 *       wp --path="/var/www/$d/htdocs" hkfn video inventory --allow-root > "/tmp/inv-$d.json" 2>/dev/null
	 *     done
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function inventory( $args, $assoc_args ): void {
		$format = $assoc_args['format'] ?? 'json';
		$refs   = self::referenced_videos();

		$collection = null;
		if ( LicenseService::isVideoConfigured() ) {
			$service    = new BunnyStreamService();
			$collection = $service->get_site_collection_id();
		}

		if ( 'csv' === $format ) {
			$rows = [];
			foreach ( $refs as $id => $row ) {
				$rows[] = [
					'site'       => get_site_url(),
					'collection' => (string) $collection,
					'video_id'   => $id,
					'post_id'    => $row['post_id'],
					'status'     => $row['post_status'],
					'notice'     => $row['post_title'],
				];
			}
			if ( ! $rows ) {
				\WP_CLI::log( 'site,collection,video_id,post_id,status,notice' );
				return;
			}
			\WP_CLI\Utils\format_items( 'csv', $rows, array_keys( $rows[0] ) );
			return;
		}

		echo wp_json_encode(
			[
				'site'          => get_site_url(),
				'domain'        => wp_parse_url( get_site_url(), PHP_URL_HOST ),
				'collection_id' => $collection,
				'generated'     => current_time( 'mysql', true ),
				'plugin_version' => HKFN_VERSION,
				'video_ids'     => array_keys( $refs ),
				'count'         => count( $refs ),
			],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		) . "\n";
	}

	/**
	 * Compare Bunny against the videos this site references.
	 *
	 * Read-only. Scoped to this site's own Bunny collection by default,
	 * because one library is shared by many sites and everything outside your
	 * collection belongs to someone else.
	 *
	 * Reports:
	 *   - orphaned: in your collection, no notice points at it
	 *   - missing:  a notice points at a video that is not in Bunny
	 *   - matched:  in your collection and referenced
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format for the detail tables. table, json, csv, yaml. Default table.
	 *
	 * [--show=<group>]
	 * : Which group to list. One of: orphaned, missing, matched, all. Default orphaned.
	 *
	 * [--scope=<scope>]
	 * : collection (default) or library. "library" lists the entire shared
	 *   library including other sites' videos, and must never be used as a
	 *   prune list on its own.
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
		if ( ! LicenseService::isVideoConfigured() ) {
			\WP_CLI::error( 'Video hosting is not configured on this site. Missing: ' . implode( ', ', LicenseService::getMissingVideoConfig() ) );
		}

		$format = $assoc_args['format'] ?? 'table';
		$show   = $assoc_args['show'] ?? 'orphaned';
		$scope  = $assoc_args['scope'] ?? 'collection';

		if ( ! in_array( $scope, [ 'collection', 'library' ], true ) ) {
			\WP_CLI::error( 'scope must be "collection" or "library".' );
		}

		$service        = new BunnyStreamService();
		$site_collection = $service->get_site_collection_id();

		if ( 'collection' === $scope && empty( $site_collection ) ) {
			// A collection is created on the first upload. No collection and no
			// references simply means nothing has been uploaded yet.
			if ( ! self::referenced_videos() ) {
				\WP_CLI::success( 'No videos have been uploaded from this site yet, so there is nothing to reconcile.' );
				return;
			}

			\WP_CLI::error(
				"This site references videos but has no Bunny collection, so they cannot be told apart from other sites sharing the library.\n"
				. 'Refusing to guess. Run with --scope=library to inspect the whole library, but do not treat that as a prune list.'
			);
		}

		\WP_CLI::log( sprintf( 'Library: %s', $service->get_library_id() ) );
		if ( $site_collection ) {
			\WP_CLI::log( sprintf( 'This site\'s collection: %s', $site_collection ) );
		}
		\WP_CLI::log( 'Fetching videos from Bunny...' );

		$remote  = [];
		$skipped = 0;
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
				if ( ! $id ) {
					continue;
				}

				if ( 'collection' === $scope && ( $video['collectionId'] ?? null ) !== $site_collection ) {
					$skipped++;
					continue;
				}

				$remote[ $id ] = $video['title'] ?? '';
			}

			$page++;
		} while ( count( $items ) === 100 );

		if ( 'collection' === $scope ) {
			\WP_CLI::log( sprintf( 'Your collection holds %d video(s). Ignored %d belonging to other sites.', count( $remote ), $skipped ) );
		} else {
			\WP_CLI::warning( 'Listing the ENTIRE shared library. Videos outside your collection belong to other sites. Do not prune from this list.' );
			\WP_CLI::log( sprintf( 'Library holds %d video(s).', count( $remote ) ) );
		}

		$referenced = self::referenced_videos();
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
		\WP_CLI::log( sprintf( 'orphaned: %d  (in Bunny, nothing on this site points at them)', count( $orphaned ) ) );
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

		\WP_CLI::log( 'Nothing was deleted. Check orphans against the other sites sharing this library before removing anything.' );
	}

	/**
	 * Videos this site's funeral notices point at, keyed by Bunny video ID.
	 *
	 * @return array<string, array>
	 */
	private static function referenced_videos(): array {
		global $wpdb;

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

		return $referenced;
	}
}

\WP_CLI::add_command( 'hkfn video', __NAMESPACE__ . '\Video_Command' );
