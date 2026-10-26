<?php
/**
 * Course tracking information.
 *
 * @package Masteriyo\Tracking
 */

namespace Masteriyo\Tracking;

use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CourseBillingPeriod;
use Masteriyo\Enums\CourseFlow;
use Masteriyo\PostType\PostType;
use Masteriyo\Taxonomy\Taxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Published course aggregates: how courses are sold and structured.
 *
 * @phpstan-type CoursesData array{by_access_mode: array<array-key, positive-int>, by_flow: array<array-key, positive-int>, recurring_period: array<array-key, positive-int>, category_count: int, tag_count: int, difficulty_count: int}
 */
class CoursesTrackingInfo {

	/**
	 * Build the courses group.
	 *
	 * @return CoursesData
	 */
	public static function all(): array {
		return array(
			'by_access_mode'   => TrackingCounts::by_meta_value( PostType::COURSE, '_access_mode', CourseAccessMode::all() ),
			'by_flow'          => TrackingCounts::by_meta_value( PostType::COURSE, '_flow', CourseFlow::all() ),
			'recurring_period' => TrackingCounts::by_meta_value( PostType::COURSE, '_billing_period', CourseBillingPeriod::all(), 'publish', array( '_access_mode', CourseAccessMode::RECURRING ) ),
			'category_count'   => self::count_terms( Taxonomy::COURSE_CATEGORY ),
			'tag_count'        => self::count_terms( Taxonomy::COURSE_TAG ),
			'difficulty_count' => self::count_terms( Taxonomy::COURSE_DIFFICULTY ),
		);
	}

	/**
	 * Term count of a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return int
	 */
	private static function count_terms( string $taxonomy ): int {
		$count = wp_count_terms( array( 'taxonomy' => $taxonomy ) );

		return is_wp_error( $count ) ? 0 : absint( $count );
	}
}
