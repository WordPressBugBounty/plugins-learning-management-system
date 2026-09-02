<?php
/**
 * One-click Login|Logout link insertion.
 *
 * @package Masteriyo\NavMenu
 */

namespace Masteriyo\NavMenu;

defined( 'ABSPATH' ) || exit;

/**
 * One-click insert of the "Login | Logout" link — a classic menu item or the
 * login-link block by theme type. Each insert is recorded in an option so
 * undo() reverts exactly that, never trusting client input.
 */
class LoginLinkInserter {

	const OPTION       = 'masteriyo_nav_login_link_insertion';
	const BLOCK_MARKUP = '<!-- wp:masteriyo/login-link /-->';

	/**
	 * Add the login link to the site navigation.
	 *
	 * @return array|\WP_Error Response data on success.
	 */
	public function insert() {
		$use_block_navigation = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();

		/**
		 * Filters whether the one-click login link targets block-theme navigation
		 * instead of a classic menu.
		 *
		 * @param bool $use_block_navigation Defaults to wp_is_block_theme().
		 */
		if ( apply_filters( 'masteriyo_login_link_use_block_navigation', $use_block_navigation ) ) {
			return $this->insert_into_block_navigation();
		}

		return $this->insert_into_classic_menu();
	}

	/**
	 * Revert the last insert() operation.
	 *
	 * @return array|\WP_Error
	 */
	public function undo() {
		$op = get_option( self::OPTION );

		if ( empty( $op ) || ! is_array( $op ) ) {
			return new \WP_Error(
				'masteriyo_nav_menu_nothing_to_undo',
				__( 'There is nothing to undo.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		if ( 'block' === ( $op['theme_type'] ?? '' ) ) {
			$this->undo_block( $op );
		} else {
			$this->undo_classic( $op );
		}

		delete_option( self::OPTION );
		// Reverting means the user may want the prompt again.
		delete_user_meta( get_current_user_id(), NavMenu::DISMISS_META_KEY );
		( new NavMenu() )->invalidate_has_used_menus_cache();

		return array( 'status' => 'reverted' );
	}

	/**
	 * Add the loginout item to the classic menu at the theme's primary location.
	 *
	 * @return array|\WP_Error
	 */
	private function insert_into_classic_menu() {
		if ( $this->classic_loginout_item_exists() ) {
			return array(
				'status'     => 'exists',
				'theme_type' => 'classic',
				'undoable'   => false,
			);
		}

		$location = $this->pick_primary_location();

		if ( ! $location ) {
			// Nowhere to render: an item in an unassigned menu would report
			// success while the site shows nothing.
			return array(
				'status'     => 'no_location',
				'theme_type' => 'classic',
				'undoable'   => false,
			);
		}

		$locations         = get_nav_menu_locations();
		$menu_id           = isset( $locations[ $location ] ) ? (int) $locations[ $location ] : 0;
		$menu              = $menu_id ? wp_get_nav_menu_object( $menu_id ) : false;
		$menu_created      = false;
		$created_item_ids  = array();
		$location_assigned = false;

		if ( ! $menu ) {
			// Never adopt an unassigned menu — assigning it could swap the
			// header nav wholesale. The empty location was rendering the
			// theme's page-list fallback, which the seeded menu reproduces.
			$menu_id = $this->create_menu();

			if ( is_wp_error( $menu_id ) ) {
				return $menu_id;
			}

			$menu_created     = true;
			$created_item_ids = $this->add_top_level_pages( $menu_id );
			$menu             = wp_get_nav_menu_object( $menu_id );
		}

		$item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => __( 'Login | Logout', 'learning-management-system' ),
				'menu-item-url'    => '#masteriyo-loginout',
				'menu-item-type'   => 'custom',
				'menu-item-status' => 'publish',
			)
		);

		if ( is_wp_error( $item_id ) ) {
			if ( $menu_created ) {
				wp_delete_nav_menu( $menu_id );
			}
			return $item_id;
		}

		$created_item_ids[] = (int) $item_id;

		if ( (int) ( $locations[ $location ] ?? 0 ) !== $menu_id ) {
			$locations[ $location ] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
			$location_assigned = true;
		}

		update_option(
			self::OPTION,
			array(
				'theme_type'        => 'classic',
				'menu_id'           => $menu_id,
				'menu_created'      => $menu_created,
				'item_id'           => (int) $item_id,
				'created_item_ids'  => $created_item_ids,
				'location'          => (string) $location,
				'location_assigned' => $location_assigned,
			),
			false
		);

		return array(
			'status'      => 'added',
			'theme_type'  => 'classic',
			'menu_id'     => $menu_id,
			'target_name' => $menu ? $menu->name : '',
			'created'     => $menu_created,
			'undoable'    => true,
		);
	}

	/**
	 * Append the login-link block to the navigation the theme's header uses.
	 *
	 * @return array|\WP_Error
	 */
	private function insert_into_block_navigation() {
		if ( NavMenu::block_login_link_exists() ) {
			return array(
				'status'     => 'exists',
				'theme_type' => 'block',
				'undoable'   => false,
			);
		}

		$navigation = $this->find_target_navigation();
		$created    = false;

		if ( $navigation ) {
			$result = wp_update_post(
				array(
					'ID'           => $navigation->ID,
					'post_content' => $navigation->post_content . "\n" . self::BLOCK_MARKUP,
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		} else {
			// A ref-less core/navigation block renders the newest navigation, so a
			// fresh one is picked up without touching any template. page-list keeps
			// the nav from collapsing to a lone Login link.
			$navigation_id = wp_insert_post(
				array(
					'post_type'    => 'wp_navigation',
					'post_title'   => __( 'Navigation', 'learning-management-system' ),
					'post_status'  => 'publish',
					'post_content' => "<!-- wp:page-list /-->\n" . self::BLOCK_MARKUP,
				),
				true
			);

			if ( is_wp_error( $navigation_id ) ) {
				return $navigation_id;
			}

			$navigation = get_post( $navigation_id );
			$created    = true;
		}

		update_option(
			self::OPTION,
			array(
				'theme_type'         => 'block',
				'navigation_id'      => (int) $navigation->ID,
				'navigation_created' => $created,
			),
			false
		);

		return array(
			'status'      => 'added',
			'theme_type'  => 'block',
			'target_name' => get_the_title( $navigation ),
			'created'     => $created,
			'undoable'    => true,
		);
	}

	/**
	 * Revert a classic-theme insertion.
	 *
	 * @param array $op Recorded operation.
	 * @return void
	 */
	private function undo_classic( array $op ): void {
		$item_id      = (int) ( $op['item_id'] ?? 0 );
		$menu_id      = (int) ( $op['menu_id'] ?? 0 );
		$menu_deleted = false;

		if ( ! empty( $op['menu_created'] ) && $menu_id && wp_get_nav_menu_object( $menu_id ) ) {
			$items       = wp_get_nav_menu_items( $menu_id );
			$current_ids = array_map( 'intval', wp_list_pluck( $items ? $items : array(), 'db_id' ) );
			$created_ids = array_map( 'intval', (array) ( $op['created_item_ids'] ?? array() ) );

			// Only delete a menu we created if the user hasn't added anything to it.
			if ( empty( array_diff( $current_ids, $created_ids ) ) ) {
				wp_delete_nav_menu( $menu_id );
				$menu_deleted = true;
			}
		}

		if ( ! $menu_deleted && $item_id && '#masteriyo-loginout' === get_post_meta( $item_id, '_menu_item_url', true ) ) {
			wp_delete_post( $item_id, true );
		}

		// The assignment was ours, so drop the key entirely — whether it still
		// points at our menu or wp_delete_nav_menu() already zeroed it (core
		// zeroes locations of a deleted menu, it never unsets them).
		if ( ! empty( $op['location_assigned'] ) && ! empty( $op['location'] ) ) {
			$locations = get_nav_menu_locations();
			$assigned  = (int) ( $locations[ $op['location'] ] ?? -1 );

			if ( $assigned === $menu_id || 0 === $assigned ) {
				unset( $locations[ $op['location'] ] );
				set_theme_mod( 'nav_menu_locations', $locations );
			}
		}
	}

	/**
	 * Revert a block-theme insertion.
	 *
	 * @param array $op Recorded operation.
	 * @return void
	 */
	private function undo_block( array $op ): void {
		$navigation_id = (int) ( $op['navigation_id'] ?? 0 );
		$navigation    = $navigation_id ? get_post( $navigation_id ) : null;

		if ( ! $navigation || 'wp_navigation' !== $navigation->post_type ) {
			return;
		}

		if ( ! empty( $op['navigation_created'] ) ) {
			wp_delete_post( $navigation_id, true );
			return;
		}

		$content  = $navigation->post_content;
		$position = strpos( $content, self::BLOCK_MARKUP );

		if ( false !== $position ) {
			$content = substr_replace( $content, '', $position, strlen( self::BLOCK_MARKUP ) );
			wp_update_post(
				array(
					'ID'           => $navigation_id,
					'post_content' => trim( $content ),
				)
			);
		}
	}

	/**
	 * Whether a classic loginout item already exists in any menu.
	 *
	 * @return bool
	 */
	private function classic_loginout_item_exists(): bool {
		$items = get_posts(
			array(
				'post_type'      => 'nav_menu_item',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => '_menu_item_url',
						'value' => '#masteriyo-loginout',
					),
				),
			)
		);

		return ! empty( $items );
	}

	/**
	 * Create the menu, suffixing the name while a same-named menu exists.
	 *
	 * @return int|\WP_Error Menu term ID.
	 */
	private function create_menu() {
		$base = __( 'Primary Menu', 'learning-management-system' );
		$name = $base;

		for ( $suffix = 2; $suffix <= 10; $suffix++ ) {
			$menu_id = wp_create_nav_menu( $name );

			if ( ! is_wp_error( $menu_id ) || 'menu_exists' !== $menu_id->get_error_code() ) {
				return $menu_id;
			}

			$name = $base . ' ' . $suffix;
		}

		return $menu_id;
	}

	/**
	 * Pick the theme's primary menu location.
	 *
	 * @return string Location slug, or '' if the theme registers none.
	 */
	private function pick_primary_location(): string {
		$registered = array_keys( get_registered_nav_menus() );

		if ( empty( $registered ) ) {
			return '';
		}

		$candidates = array( 'primary', 'primary-menu', 'menu-1', 'main', 'main-menu', 'header', 'header-menu', 'top' );

		foreach ( $candidates as $candidate ) {
			if ( in_array( $candidate, $registered, true ) ) {
				return $candidate;
			}
		}

		return (string) $registered[0];
	}

	/**
	 * Seed a freshly created menu with the site's top-level pages.
	 *
	 * Without this, assigning the new menu would replace the theme's page-list
	 * fallback with a navigation containing nothing but the login link.
	 *
	 * @param int $menu_id Menu term ID.
	 * @return int[] Created nav menu item IDs.
	 */
	private function add_top_level_pages( int $menu_id ): array {
		$item_ids = array();
		$pages    = get_pages(
			array(
				'parent'      => 0,
				'sort_column' => 'menu_order,post_title',
				'number'      => 10,
			)
		);

		foreach ( (array) $pages as $page ) {
			$item_id = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-object-id' => $page->ID,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);

			if ( ! is_wp_error( $item_id ) ) {
				$item_ids[] = (int) $item_id;
			}
		}

		return $item_ids;
	}

	/**
	 * Find the navigation post the site actually renders in its header.
	 *
	 * Prefers the navigation a header template part references by ref; falls
	 * back to the newest published navigation, which is what a ref-less
	 * core/navigation block renders.
	 *
	 * @return \WP_Post|null
	 */
	private function find_target_navigation() {
		if ( function_exists( 'get_block_templates' ) ) {
			$parts = get_block_templates( array( 'area' => 'header' ), 'wp_template_part' );

			foreach ( $parts as $part ) {
				$ref = $this->find_navigation_ref( parse_blocks( (string) $part->content ) );

				if ( $ref ) {
					$navigation = get_post( $ref );

					if ( $navigation && 'wp_navigation' === $navigation->post_type && 'publish' === $navigation->post_status ) {
						return $navigation;
					}
				}
			}
		}

		$navigations = get_posts(
			array(
				'post_type'      => 'wp_navigation',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return empty( $navigations ) ? null : $navigations[0];
	}

	/**
	 * Recursively find the first core/navigation ref in a parsed block tree.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return int Navigation post ID, or 0 if none found.
	 */
	private function find_navigation_ref( array $blocks ): int {
		foreach ( $blocks as $block ) {
			if ( 'core/navigation' === ( $block['blockName'] ?? '' ) && ! empty( $block['attrs']['ref'] ) ) {
				return (int) $block['attrs']['ref'];
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$ref = $this->find_navigation_ref( $block['innerBlocks'] );

				if ( $ref ) {
					return $ref;
				}
			}
		}

		return 0;
	}
}
