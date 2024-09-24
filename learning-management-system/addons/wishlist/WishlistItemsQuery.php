<?php
/**
 * Class for parameter-based wishlist items query.
 *
 * @since 1.12.2
 * @package Masteriyo\Addons\WishList
 */

namespace Masteriyo\Addons\WishList;

use Masteriyo\Abstracts\ObjectQuery;
use Masteriyo\Enums\PostStatus;

defined( 'ABSPATH' ) || exit;

class WishlistItemsQuery extends ObjectQuery {

	/**
	 * Valid query vars for the query.
	 *
	 * @since 1.12.2
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			array(
				'date_created' => null,
				'status'       => PostStatus::all(),
				'course'       => array(),
			)
		);
	}

	/**
	 * Get wishlist items matching the current query vars.
	 *
	 * @since 1.12.2
	 *
	 * @return array The queried wishlist items.
	 */
	public function get_wishlist_items() {
		/**
		 * Filters query args for querying wishlist items.
		 *
		 * @since 1.12.2
		 *
		 * @param array $query_args The object query args.
		 */
		$args   = apply_filters( 'masteriyo_wishlist_items_object_query_args', $this->get_query_vars() );
		$result = masteriyo_get_wishlist_item_store()->query( $args );

		/**
		 * Filters wishlist item object query result.
		 *
		 * @since 1.12.2
		 *
		 * @param array $result The query result.
		 * @param array $query_args The object query args.
		 */
		return apply_filters( 'masteriyo_wishlist_items_object_query', $result, $args );
	}
}
