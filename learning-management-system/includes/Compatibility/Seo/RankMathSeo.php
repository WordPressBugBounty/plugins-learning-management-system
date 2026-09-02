<?php
/**
 * Compatibility with Rank Math SEO plugin.
 *
 * @since 1.6.11
 */

namespace Masteriyo\Compatibility\Seo;

defined( 'ABSPATH' ) || exit;


class RankMathSeo {
	/**
	 * Initialize.
	 *
	 * @since 1.6.11
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'rank_math/simple_page_id', array( $this, 'update_simple_page_id' ) );
	}

	/**
	 * Update simple page id.
	 *
	 * Courses page is actually an archive page, not a regular page.
	 * So, rank math seo plugin customization doesn't reflect.
	 * To make it compatible, we need to set that the current courses page is actually just a regular page,
	 * which can be achieved through the above hook.
	 *
	 * @see https://github.com/rankmath/seo-by-rank-math/blob/76e87a34fb3a4f325c396251a65d19fd71fac6b5/includes/class-post.php#L130
	 *
	 * @since 1.6.11
	 *
	 * @param int $page_id
	 * @return int
	 */
	public function update_simple_page_id( $page_id ) {
		if ( masteriyo_is_courses_page() ) {
			$courses_page_id = masteriyo_get_page_id( 'courses' );

			// Sites without a Courses page (#665) read -1 here, and handing a truthy
			// bogus ID to Rank Math is worse than leaving its own value alone.
			if ( 0 < $courses_page_id ) {
				$page_id = $courses_page_id;
			}
		}

		return $page_id;
	}
}
