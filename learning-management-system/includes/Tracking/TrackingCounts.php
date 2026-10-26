<?php
/**
 * Shared helpers for building aggregate tracking counts.
 *
 * @package Masteriyo\Tracking
 */

namespace Masteriyo\Tracking;

use Masteriyo\Enums\PostStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Grouped-count helpers. Everything returned is a slug => integer map, so no
 * user-entered text can reach the tracking payload.
 */
class TrackingCounts {

	/**
	 * Bucket for values outside the allowed list.
	 *
	 * @var string
	 */
	const OTHER = 'other';

	/**
	 * Bucket for posts with no value for the meta key at all.
	 *
	 * @var string
	 */
	const UNSET = 'unset';

	/**
	 * Normalize raw grouped rows into a slug => count map.
	 *
	 * Values are slugified; values outside `$allowed` (when given) are summed
	 * into `other`, including non-empty values that slugify to nothing. Raw
	 * null/empty values go to the `$missing` bucket, or are dropped when it is
	 * null. Non-positive counts are dropped.
	 *
	 * @param array<array-key, array{value?: mixed, total?: int|string}> $rows    Rows of raw values and totals.
	 * @param array<array-key, string>|null                               $allowed Allowed slugs, or null to accept any slug.
	 * @param string|null                                                 $missing Bucket for raw null/empty values, or null to drop them.
	 *
	 * @return array<array-key, positive-int> Map of slug => count, sorted by key.
	 */
	public static function normalize( array $rows, ?array $allowed = null, ?string $missing = null ): array {
		$counts = array();

		foreach ( $rows as $row ) {
			$raw   = $row['value'] ?? null;
			$total = isset( $row['total'] ) ? (int) $row['total'] : 0;

			if ( $total < 1 ) {
				continue;
			}

			if ( null === $raw || '' === $raw ) {
				if ( null === $missing ) {
					continue;
				}

				$value = $missing;
			} else {
				$value = self::slugify( $raw );

				if ( null !== $allowed && ! in_array( $value, $allowed, true ) ) {
					$value = self::OTHER;
				} elseif ( '' === $value ) {
					continue;
				}
			}

			$counts[ $value ] = ( $counts[ $value ] ?? 0 ) + $total;
		}

		ksort( $counts );

		return $counts;
	}

	/**
	 * Lowercase slug limited to letters, digits, dashes and underscores.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function slugify( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return substr( strtolower( preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $value ) ), 0, 40 );
	}

	/**
	 * Count posts of a type grouped by a meta value, in one query.
	 *
	 * @param string                        $post_type Post type.
	 * @param string                        $meta_key  Meta key.
	 * @param array<array-key, string>|null $allowed   Allowed slugs (see normalize()).
	 * @param string                        $status    Post status to count.
	 * @param array{string, string}|null    $also      Optional array( meta_key, meta_value ) the same post must also carry.
	 *
	 * @return array<array-key, positive-int> Map of slug => count.
	 */
	public static function by_meta_value( string $post_type, string $meta_key, ?array $allowed = null, string $status = 'publish', ?array $also = null ): array {
		return self::normalize( self::grouped_meta_rows( 'INNER', $post_type, $meta_key, $status, $also ), $allowed );
	}

	/**
	 * Count posts of a type grouped by a meta value, including posts with no
	 * value for the meta key at all (bucketed as `$missing` rather than dropped).
	 *
	 * @param string                   $post_type Post type.
	 * @param string                   $meta_key  Meta key.
	 * @param array<array-key, string> $allowed   Allowed slugs (see normalize()).
	 * @param string                   $status    Post status to count.
	 * @param string                   $missing   Bucket for posts with no or an empty value.
	 *
	 * @return array<array-key, positive-int> Map of slug => count.
	 */
	public static function by_meta_value_or_unset( string $post_type, string $meta_key, array $allowed, string $status = 'publish', string $missing = self::UNSET ): array {
		return self::normalize( self::grouped_meta_rows( 'LEFT', $post_type, $meta_key, $status ), $allowed, $missing );
	}

	/**
	 * Run the grouped meta-value query shared by the by_meta_value*() methods.
	 *
	 * @param string                        $join_type 'INNER' or 'LEFT'.
	 * @param string                        $post_type Post type.
	 * @param string                        $meta_key  Meta key.
	 * @param string                        $status    Post status to count.
	 * @param array{string, string}|null    $also      Optional array( meta_key, meta_value ) the same post must also carry.
	 *
	 * @return list<array{value: string|null, total: string}> Raw rows.
	 */
	private static function grouped_meta_rows( string $join_type, string $post_type, string $meta_key, string $status, ?array $also = null ): array {
		global $wpdb;

		if ( ! $wpdb ) {
			return array();
		}

		$join_type = 'LEFT' === $join_type ? 'LEFT' : 'INNER';
		$join      = '';
		$args      = array( $meta_key );

		if ( is_array( $also ) && 2 === count( $also ) ) {
			$join   = "INNER JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = %s AND pm2.meta_value = %s";
			$args[] = (string) $also[0];
			$args[] = (string) $also[1];
		}

		$args[] = $post_type;
		$args[] = $status;

		// $join_type is whitelisted above and $join is a fixed fragment with its own placeholders; its args are in $args.
		return self::rows(
			"SELECT pm.meta_value AS value, COUNT(DISTINCT p.ID) AS total
			FROM {$wpdb->posts} p
			{$join_type} JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			{$join}
			WHERE p.post_type = %s AND p.post_status = %s
			GROUP BY pm.meta_value",
			...$args
		);
	}

	/**
	 * Prepare and run a grouped `value`/`total` query.
	 *
	 * @param string $sql  Query with placeholders; table names only may be interpolated.
	 * @param string ...$args Placeholder values.
	 *
	 * @return list<array{value: string|null, total: string}> Raw rows, or an empty list on failure.
	 */
	public static function rows( string $sql, string ...$args ): array {
		global $wpdb;

		if ( ! $wpdb ) {
			return array();
		}

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared here.

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Per-status counts of a post type, keyed by status slug.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return array<array-key, positive-int> Map of status => count (zero counts dropped).
	 */
	public static function by_post_status( string $post_type ): array {
		$rows = array();

		foreach ( (array) wp_count_posts( $post_type ) as $status => $total ) {
			$rows[] = array(
				'value' => $status,
				'total' => $total,
			);
		}

		return self::normalize( $rows );
	}

	/**
	 * Published post count of a type.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return int
	 */
	public static function count_published( string $post_type ): int {
		return absint( masteriyo_array_get( (array) wp_count_posts( $post_type ), PostStatus::PUBLISH, 0 ) );
	}
}
