<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\FieldGroups;

/**
 * Keeps the legacy wfn_ post meta in step with the hkfn_ keys ACF writes.
 *
 * v3.0.0 renamed the ACF field groups from wfn_ to hkfn_, and
 * FieldGroupMigration copied the existing rows across as a one-off. The
 * front-end queries still join on wfn_details_group_funeral_date, and
 * WP_Query builds a plain meta_key as an INNER JOIN, so a notice without
 * the legacy row drops out of the result set entirely rather than sorting
 * last.
 *
 * Notices created after a site upgraded only ever got the hkfn_ rows, so
 * they were invisible in the grid while looking correct in wp-admin, where
 * the columns read through a hkfn_ -> wfn_ -> direct fallback.
 *
 * This mirrors hkfn_ values back to wfn_ on every save and backfills the
 * notices created between a site upgrading and this fix landing. The
 * queries move to hkfn_ in v3.1, at which point this class and the
 * mirrored rows can be dropped.
 *
 * @since 3.0.4
 */
class LegacyMetaMirror {

	private const BACKFILL_OPTION = 'hkfn_legacy_meta_backfill';

	/**
	 * The key the front-end grid joins on. A notice missing this row is
	 * invisible, so it is what the backfill scans for.
	 */
	private const GRID_JOIN_KEY = 'wfn_details_group_funeral_date';

	/**
	 * Register the save hook.
	 *
	 * Priority 20 so ACF has finished writing its own meta first.
	 */
	public function register(): void {
		add_action( 'acf/save_post', [ $this, 'mirror_on_save' ], 20 );
	}

	/**
	 * Mirror a notice after ACF saves it.
	 *
	 * @param mixed $post_id ACF passes 'options', 'user_1' etc. for non-post saves.
	 */
	public function mirror_on_save( $post_id ): void {
		if ( ! is_numeric( $post_id ) ) {
			return;
		}

		$post_id = (int) $post_id;

		if ( 'funeral-notice' !== get_post_type( $post_id ) ) {
			return;
		}

		$this->mirror_post( $post_id );
	}

	/**
	 * Copy every hkfn_ value row on a notice to its wfn_ equivalent.
	 *
	 * Adds and updates only. Legacy rows with no hkfn_ counterpart are left
	 * alone, so a site that never ran the prefix migration keeps its data.
	 *
	 * @return int Number of legacy rows written.
	 */
	public function mirror_post( int $post_id ): int {
		global $wpdb;

		// esc_like() escapes the trailing underscore so it stays a literal
		// rather than a single-character wildcard. The pattern anchors at
		// 'hkfn_', so it will not match the _hkfn_ ACF reference rows, which
		// point at field keys the old group no longer registers.
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT meta_key, meta_value FROM {$wpdb->postmeta}
			 WHERE post_id = %d AND meta_key LIKE %s",
			$post_id,
			$wpdb->esc_like( 'hkfn_' ) . '%'
		) );

		if ( ! $rows ) {
			return 0;
		}

		$existing = array_flip( $wpdb->get_col( $wpdb->prepare(
			"SELECT meta_key FROM {$wpdb->postmeta}
			 WHERE post_id = %d AND meta_key LIKE %s",
			$post_id,
			$wpdb->esc_like( 'wfn_' ) . '%'
		) ) );

		$written       = 0;
		$has_join_key  = isset( $existing[ self::GRID_JOIN_KEY ] );

		foreach ( $rows as $row ) {
			$legacy_key = 'wfn_' . substr( $row->meta_key, 5 );
			$value      = (string) $row->meta_value;

			if ( self::GRID_JOIN_KEY === $legacy_key ) {
				$has_join_key = true;
			}

			// Direct SQL throughout: the stored value may already be
			// serialized, and update_post_meta() would serialize it again.
			if ( isset( $existing[ $legacy_key ] ) ) {
				$wpdb->update(
					$wpdb->postmeta,
					[ 'meta_value' => $value ],
					[
						'post_id'  => $post_id,
						'meta_key' => $legacy_key,
					]
				);
			} else {
				$wpdb->insert(
					$wpdb->postmeta,
					[
						'post_id'    => $post_id,
						'meta_key'   => $legacy_key,
						'meta_value' => $value,
					]
				);
			}

			$written++;
		}

		// A notice with no funeral date set has no hkfn_ date row to mirror,
		// which would leave it invisible for the same reason. The grid joins
		// on the row existing, not on it holding a value, so an empty row is
		// enough. This matches the pre-upgrade notices that display fine
		// with no service date.
		if ( ! $has_join_key ) {
			$wpdb->insert(
				$wpdb->postmeta,
				[
					'post_id'    => $post_id,
					'meta_key'   => self::GRID_JOIN_KEY,
					'meta_value' => '',
				]
			);
			$written++;
		}

		// Direct SQL bypasses the object cache (Redis on production).
		wp_cache_delete( $post_id, 'post_meta' );

		return $written;
	}

	/**
	 * Run the one-off backfill once per site.
	 */
	public function maybe_backfill(): void {
		if ( get_option( self::BACKFILL_OPTION ) ) {
			return;
		}

		$this->backfill();

		update_option( self::BACKFILL_OPTION, current_time( 'mysql' ) );
	}

	/**
	 * Mirror every notice that is missing the key the grid joins on.
	 *
	 * Scoped to broken notices so working ones are never rewritten. Safe to
	 * re-run, and worth re-running after a bulk import that wrote meta
	 * without going through ACF:
	 *
	 *   wp eval '(new HumanKind\FuneralNotices\FieldGroups\LegacyMetaMirror())->backfill();'
	 *
	 * @return array{scanned:int,repaired:int,rows:int,ids:int[]}
	 */
	public function backfill( bool $dry_run = false ): array {
		global $wpdb;

		// One NOT EXISTS lookup rather than a metadata_exists() call per
		// notice: 'fields' => 'ids' leaves the meta cache cold, so the
		// per-post version costs a query each on sites with hundreds of
		// notices. NOT EXISTS also distinguishes a missing row from one
		// holding an empty string, which get_post_meta() cannot.
		$broken_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 WHERE p.post_type = 'funeral-notice'
			   AND NOT EXISTS (
			       SELECT 1 FROM {$wpdb->postmeta} m
			       WHERE m.post_id = p.ID AND m.meta_key = %s
			   )",
			self::GRID_JOIN_KEY
		) );

		$results = [
			'scanned'  => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'funeral-notice'"
			),
			'repaired' => count( $broken_ids ),
			'rows'     => 0,
			'ids'      => array_map( 'intval', $broken_ids ),
		];

		if ( $dry_run ) {
			return $results;
		}

		foreach ( $results['ids'] as $post_id ) {
			$results['rows'] += $this->mirror_post( $post_id );
		}

		return $results;
	}
}
