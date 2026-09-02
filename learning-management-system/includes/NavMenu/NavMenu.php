<?php
/**
 * Login/Logout Navigation Menu handler.
 *
 * @package Masteriyo\NavMenu
 */

namespace Masteriyo\NavMenu;

defined( 'ABSPATH' ) || exit;

/**
 * Adds Masteriyo login/logout/account links to the WordPress classic nav menus
 * via Appearance › Menus (Walker_Nav_Menu_Checklist panel + wp_nav_menu hooks).
 */
class NavMenu {

	const DISMISS_META_KEY = 'masteriyo_dismissed_nav_menu_notice';

	/**
	 * Return placeholder slugs mapped to their display config with translated labels.
	 *
	 * Implemented as a static method (not a class constant) so that `__()` can be
	 * called at runtime after the text domain is loaded.
	 *
	 * 'show_when' values: 'logged_out' | 'logged_in' | 'always'
	 * 'css_class' is stamped on every item so filter_nav_menu_objects() can act
	 * on it after the URL has already been resolved.
	 *
	 * @return array<string, array{label: string, show_when: string, css_class: string}>
	 */
	private static function get_items(): array {
		return array(
			'#masteriyo-login'    => array(
				'label'     => __( 'Login', 'learning-management-system' ),
				'show_when' => 'logged_out',
				'css_class' => 'masteriyo-nav-login',
			),
			'#masteriyo-register' => array(
				'label'     => __( 'Register', 'learning-management-system' ),
				'show_when' => 'logged_out',
				'css_class' => 'masteriyo-nav-register',
			),
			'#masteriyo-account'  => array(
				'label'     => __( 'My Account', 'learning-management-system' ),
				'show_when' => 'logged_in',
				'css_class' => 'masteriyo-nav-account',
			),
			'#masteriyo-logout'   => array(
				'label'     => __( 'Logout', 'learning-management-system' ),
				'show_when' => 'logged_in',
				'css_class' => 'masteriyo-nav-logout',
			),
			'#masteriyo-loginout' => array(
				/* translators: separator between Login and Logout labels */
				'label'     => __( 'Login | Logout', 'learning-management-system' ),
				'show_when' => 'always',
				'css_class' => 'masteriyo-nav-loginout',
			),
		);
	}

	/**
	 * Register all WordPress hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'admin_head-nav-menus.php', array( $this, 'register_meta_box' ) );
		add_action( 'admin_print_footer_scripts-nav-menus.php', array( $this, 'enqueue_nav_menu_js' ) );
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'render_item_hint' ), 10, 2 );
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_nav_menu_item' ) );
		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_nav_menu_objects' ) );
		add_action( 'wp_update_nav_menu_item', array( $this, 'invalidate_cache_on_item_save' ), 10, 3 );
		add_action( 'wp_delete_nav_menu', array( $this, 'invalidate_has_used_menus_cache' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render_menus_screen_notice' ) );
		add_action( 'save_post_wp_navigation', array( $this, 'handle_navigation_save' ), 10, 2 );
	}

	/**
	 * Keep the notice state consistent when a block-theme navigation is saved.
	 *
	 * Mirrors invalidate_cache_on_item_save(): saving a navigation that contains
	 * the Masteriyo login-link block counts as "used the feature", so the prompt
	 * silently auto-dismisses for the saving user.
	 *
	 * @param int      $_post_id Navigation post ID (unused).
	 * @param \WP_Post $post     Navigation post.
	 * @return void
	 */
	public function handle_navigation_save( int $_post_id, \WP_Post $post ): void {
		$this->invalidate_has_used_menus_cache();

		if ( false !== strpos( (string) $post->post_content, 'wp:masteriyo/login-link' ) ) {
			update_user_meta( get_current_user_id(), self::DISMISS_META_KEY, 1 );
		}
	}

	/**
	 * Invalidate the has_used_menus cache when a Masteriyo nav item is saved.
	 *
	 * Only clears the cache when the saved item uses a #masteriyo- placeholder URL,
	 * avoiding unnecessary invalidations on unrelated menu saves.
	 *
	 * @param int   $_menu_id    ID of the menu being updated (unused).
	 * @param int   $_item_db_id Database ID of the nav menu item (unused).
	 * @param array $args        Nav menu item arguments, including 'menu-item-url'.
	 * @return void
	 */
	public function invalidate_cache_on_item_save( int $_menu_id, int $_item_db_id, array $args ): void {
		if ( isset( $args['menu-item-url'] ) && 0 === strpos( $args['menu-item-url'], '#masteriyo-' ) ) {
			$this->invalidate_has_used_menus_cache();
			update_user_meta( get_current_user_id(), self::DISMISS_META_KEY, 1 );
		}
	}

	/**
	 * Invalidate the has_used_menus transient cache.
	 *
	 * @return void
	 */
	public function invalidate_has_used_menus_cache(): void {
		masteriyo_transient_cache()->delete_cache( 'has_nav_menu_items', 'masteriyo_nav_menu' );
	}

	/**
	 * Render a helper note inside each Masteriyo nav menu item settings panel.
	 *
	 * Shows the visibility condition for every item and, for the Login|Logout
	 * toggle, an extra hint about pipe-separated custom labels.
	 * Fires via the standard `wp_nav_menu_item_custom_fields` action.
	 *
	 * @param int      $_item_id Nav menu item post ID (unused).
	 * @param \WP_Post $item     Nav menu item object.
	 * @return void
	 */
	public function render_item_hint( $_item_id, $item ): void {
		$visibility = array(
			'#masteriyo-login'    => __( 'Displayed when the user is logged out.', 'learning-management-system' ),
			'#masteriyo-register' => __( 'Displayed when the user is logged out.', 'learning-management-system' ),
			'#masteriyo-account'  => __( 'Displayed when the user is logged in.', 'learning-management-system' ),
			'#masteriyo-logout'   => __( 'Displayed when the user is logged in.', 'learning-management-system' ),
			'#masteriyo-loginout' => __( 'Dynamically switches based on user status (logged out → Login, logged in → Logout).', 'learning-management-system' ),
		);

		if ( ! isset( $visibility[ $item->url ] ) ) {
			return;
		}
		?>
		<p class="description description-wide" style="margin-top:0;">
			<?php echo esc_html( $visibility[ $item->url ] ); ?>
			<?php if ( '#masteriyo-loginout' === $item->url ) : ?>
				<?php esc_html_e( 'Set custom labels separated by a pipe (|).', 'learning-management-system' ); ?>
				<?php
				echo ' ' . wp_kses(
					sprintf(
						/* translators: %s: code example showing pipe-separated labels */
						__( 'Example: %s', 'learning-management-system' ),
						'<code>Sign In | Sign Out</code>'
					),
					array( 'code' => array() )
				);
				?>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * The name of this plugin's panel on the Menus screen, under the brand.
	 *
	 * @return string
	 */
	private function panel_name(): string {
		/* translators: %s: the product's name */
		return sprintf( __( '%s LMS', 'learning-management-system' ), masteriyo_get_plugin_name() );
	}

	/**
	 * Register the Masteriyo panel inside Appearance › Menus.
	 *
	 * @return void
	 */
	public function register_meta_box(): void {
		add_meta_box(
			'masteriyo-nav-link',
			$this->panel_name(),
			array( $this, 'render_meta_box' ),
			'nav-menus',
			'side',
			'low'
		);
	}

	/**
	 * Output inline Javascript to automatically expand the Masteriyo metabox
	 * if the URL hash is #masteriyo-nav-link.
	 *
	 * @return void
	 */
	public function enqueue_nav_menu_js(): void {
		?>
		<script type="text/javascript">
			jQuery( function( $ ) {
				var expandBox = function() {
					if ( '#masteriyo-nav-link' === window.location.hash ) {
						var $box = $( '#masteriyo-nav-link' );
						if ( $box.length ) {
							if ( ! $box.hasClass( 'open' ) ) {
								$box.find( '.accordion-trigger' ).trigger( 'click' );
								setTimeout( function() {
									$( 'html, body' ).animate( {
										scrollTop: $box.offset().top - 150
									}, 800 );
								}, 300 );
							} else {
								$( 'html, body' ).animate( {
									scrollTop: $box.offset().top - 150
								}, 800 );
							}
							return true;
						}
					}
					return false;
				};

				expandBox();
				$( window ).on( 'load hashchange', expandBox );

				var attempts = 0;
				var interval = setInterval( function() {
					attempts++;
					var expanded = expandBox();
					if ( expanded || attempts > 10 ) {
						clearInterval( interval );
					}
				}, 100 );
			} );
		</script>
		<?php
	}

	/**
	 * Output the checklist of Masteriyo items inside the meta box.
	 *
	 * @return void
	 */
	public function render_meta_box(): void {
		$walker = new \Walker_Nav_Menu_Checklist();
		?>
		<div id="masteriyo-nav-link-div" class="posttypediv">
			<div id="tabs-panel-masteriyo-nav-link-all" class="tabs-panel tabs-panel-active">
				<ul id="masteriyo-nav-linkchecklist" class="categorychecklist form-no-clear">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo walk_nav_menu_tree( $this->get_post_objects(), 0, (object) array( 'walker' => $walker ) );
					?>
				</ul>
			</div>
			<p class="button-controls" data-items-type="masteriyo-nav-link">
				<span class="list-controls hide-if-no-js">
					<input type="checkbox" id="masteriyo-nav-link-all" class="select-all" />
					<label for="masteriyo-nav-link-all"><?php esc_html_e( 'Select All', 'learning-management-system' ); ?></label>
				</span>
				<span class="add-to-menu">
					<input
						type="submit"
						class="button button-secondary submit-add-to-menu right"
						value="<?php esc_attr_e( 'Add to Menu', 'learning-management-system' ); ?>"
						name="add-masteriyo-nav-link-menu-item"
						id="submit-masteriyo-nav-link"
					>
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Build synthetic WP_Post objects for Walker_Nav_Menu_Checklist.
	 *
	 * The walker expects objects shaped like nav menu items. Negative IDs prevent
	 * collisions with any real post IDs in the database.
	 *
	 * @return \WP_Post[]
	 */
	private function get_post_objects(): array {
		$objects = array();
		$i       = -1;

		foreach ( self::get_items() as $url => $config ) {
			$post = new \WP_Post( (object) array() );

			$post->ID               = $i;
			$post->object_id        = $i;
			$post->db_id            = 0;
			$post->menu_item_parent = 0;
			$post->type             = 'custom';
			$post->type_label       = masteriyo_get_plugin_name();
			$post->object           = 'custom';
			$post->title            = $config['label'];
			$post->post_title       = $config['label'];
			$post->url              = $url;
			$post->attr_title       = '';
			$post->description      = '';
			$post->classes          = array( $config['css_class'] );
			$post->target           = '';
			$post->xfn              = '';
			$post->post_status      = 'publish';

			$objects[] = $post;
			--$i;
		}

		return $objects;
	}

	/**
	 * Resolve Masteriyo placeholder URLs to real URLs and stamp a tracking CSS class.
	 *
	 * The CSS class lets filter_nav_menu_objects() act on items after the URL has
	 * already been resolved. In the admin the placeholder URL is preserved so
	 * editors can see it is a dynamic Masteriyo link rather than a static URL.
	 *
	 * Customizing Navigation Labels in Appearance › Menus:
	 * - Login, Register, My Account, Logout — the Navigation Label set by the admin
	 *   is used as-is on the frontend. Change it to anything you like.
	 * - Login | Logout — this item switches its label based on login state. To
	 *   customize both labels, set the Navigation Label to "your-login-text | your-logout-text"
	 *   (separated by a pipe character). Example: "Sign In | Sign Out". If no pipe
	 *   is present, the translated defaults "Login" / "Logout" are used.
	 *
	 * @param \WP_Post $item Nav menu item.
	 * @return \WP_Post
	 */
	public function setup_nav_menu_item( $item ) {
		if ( ! is_object( $item ) || ! isset( $item->url ) ) {
			return $item;
		}

		$placeholder = $item->url;
		$items       = self::get_items();

		if ( ! isset( $items[ $placeholder ] ) ) {
			return $item;
		}

		$config = $items[ $placeholder ];

		// Stamp the tracking class regardless of context.
		$item->classes = array_unique(
			array_merge( (array) $item->classes, array( $config['css_class'] ) )
		);

		// Always stamp the type label so the menu editor shows "Masteriyo" instead of "Custom Link".
		$item->type_label = masteriyo_get_plugin_name();

		// Keep the placeholder URL visible in the admin menu editor.
		if ( is_admin() ) {
			return $item;
		}

		$item->url = self::resolve_url( $placeholder );

		// Switch loginout label on auth state; honours a custom "Login | Logout" separator.
		if ( '#masteriyo-loginout' === $placeholder ) {
			list( $login_label, $logout_label ) = self::split_loginout_label( (string) $item->post_title );

			$title            = is_user_logged_in() ? $logout_label : $login_label;
			$item->title      = $title;
			$item->post_title = $title;
		}

		return $item;
	}

	/**
	 * Remove Masteriyo items that should not be visible to the current visitor.
	 *
	 * Also removes any child items whose parent was removed, preventing orphaned
	 * children from floating to the top level of the rendered menu.
	 * Skipped in the admin so the menu editor always shows all items regardless
	 * of the editor's own logged-in state.
	 *
	 * @param \WP_Post[] $items Nav menu items (ordered: parents before children).
	 * @return \WP_Post[]
	 */
	public function filter_nav_menu_objects( $items ) {
		if ( is_admin() ) {
			return $items;
		}

		$logged_in    = is_user_logged_in();
		$config_items = self::get_items();
		$removed_ids  = array();
		$filtered     = array();

		foreach ( $items as $item ) {
			$parent_id = (int) ( $item->menu_item_parent ?? 0 );

			// Cascade removal: drop children of already-removed parents.
			if ( $parent_id && in_array( $parent_id, $removed_ids, true ) ) {
				$removed_ids[] = (int) $item->ID;
				continue;
			}

			$classes = (array) ( $item->classes ?? array() );
			$keep    = true;

			foreach ( $config_items as $config ) {
				if ( ! in_array( $config['css_class'], $classes, true ) ) {
					continue;
				}

				if ( 'logged_in' === $config['show_when'] ) {
					$keep = $logged_in;
				} elseif ( 'logged_out' === $config['show_when'] ) {
					$keep = ! $logged_in;
				}
				break;
			}

			if ( ! $keep ) {
				$removed_ids[] = (int) $item->ID;
				continue;
			}

			$filtered[] = $item;
		}

		return $filtered;
	}

	/**
	 * Split a loginout item title into its login and logout labels.
	 *
	 * Exactly two pipe-separated parts are used as custom labels (trimmed,
	 * empties included — matching the long-standing classic item behavior);
	 * anything else falls back to the translated defaults.
	 *
	 * @param string $title Navigation label as saved by the admin.
	 * @return array{0: string, 1: string} Login label, logout label.
	 */
	public static function split_loginout_label( string $title ): array {
		$parts = explode( '|', $title );

		if ( 2 === count( $parts ) ) {
			return array( trim( $parts[0] ), trim( $parts[1] ) );
		}

		return array(
			__( 'Login', 'learning-management-system' ),
			__( 'Logout', 'learning-management-system' ),
		);
	}

	/**
	 * Resolve a Masteriyo placeholder URL to its real URL.
	 *
	 * Each URL is filterable so themes and plugins can override individual links.
	 * The loginout placeholder resolves to logout or login URL based on auth state.
	 * Public so the login-link block resolves through the same filters.
	 *
	 * @param string $placeholder One of the #masteriyo-* placeholder strings.
	 * @return string Resolved URL, or the original placeholder if unrecognised.
	 */
	public static function resolve_url( string $placeholder ): string {
		switch ( $placeholder ) {
			case '#masteriyo-login':
				/**
				 * Filters the login URL used in Masteriyo nav menu items.
				 *
				 * @param string $url Resolved login URL.
				 */
				return (string) apply_filters( 'masteriyo_nav_login_url', masteriyo_get_account_url() );

			case '#masteriyo-register':
				/**
				 * Filters the registration URL used in Masteriyo nav menu items.
				 *
				 * @param string $url Resolved registration URL.
				 */
				return (string) apply_filters( 'masteriyo_nav_register_url', masteriyo_get_account_endpoint_url( 'signup' ) );

			case '#masteriyo-account':
				/**
				 * Filters the account URL used in Masteriyo nav menu items.
				 *
				 * @param string $url Resolved account URL.
				 */
				return (string) apply_filters( 'masteriyo_nav_account_url', masteriyo_get_account_url() );

			case '#masteriyo-logout':
				/**
				 * Filters the logout URL used in Masteriyo nav menu items.
				 *
				 * @param string $url Resolved logout URL (includes nonce).
				 */
				return (string) apply_filters( 'masteriyo_nav_logout_url', masteriyo_logout_url() );

			case '#masteriyo-loginout':
				return is_user_logged_in()
					? (string) apply_filters( 'masteriyo_nav_logout_url', masteriyo_logout_url() )
					: (string) apply_filters( 'masteriyo_nav_login_url', masteriyo_get_account_url() );

			default:
				return $placeholder;
		}
	}

	/**
	 * Check if any Masteriyo navigation menu item has been added.
	 *
	 * Result is cached via TransientCache for one hour and invalidated by
	 * invalidate_cache_on_item_save() / invalidate_has_used_menus_cache().
	 * Uses get_cache() directly (not has_cache()) to avoid a double-prefix bug
	 * in TransientCache, and stores 1/0 instead of true/false because WordPress
	 * transients cannot distinguish a stored false from a cache miss.
	 *
	 * @return bool True if at least one item exists, false otherwise.
	 */
	public static function has_used_menus(): bool {
		$cache     = masteriyo_transient_cache();
		$cache_key = 'has_nav_menu_items';
		$group     = 'masteriyo_nav_menu';

		$cached = $cache->get_cache( $cache_key, $group );

		if ( null !== $cached ) {
			return (bool) $cached;
		}

		$items = get_posts(
			array(
				'post_type'      => 'nav_menu_item',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'     => '_menu_item_url',
						'value'   => '#masteriyo-',
						'compare' => 'LIKE',
					),
				),
			)
		);

		$result = ! empty( $items ) || self::block_login_link_exists();
		$cache->set_cache( $cache_key, $result ? 1 : 0, HOUR_IN_SECONDS, $group );

		return $result;
	}

	/**
	 * Check whether any block-theme navigation contains the Masteriyo login-link block.
	 *
	 * @return bool
	 */
	public static function block_login_link_exists(): bool {
		$navigations = get_posts(
			array(
				'post_type'      => 'wp_navigation',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		foreach ( $navigations as $navigation ) {
			if ( false !== strpos( (string) $navigation->post_content, 'wp:masteriyo/login-link' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render the discovery notice at the top of Appearance › Menus.
	 *
	 * Same conditions and endpoints as the dashboard banner; the one-click
	 * button swaps the notice into a success + Undo state.
	 *
	 * @return void
	 */
	public function maybe_render_menus_screen_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'nav-menus' !== $screen->id ) {
			return;
		}

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), self::DISMISS_META_KEY, true ) ) {
			return;
		}

		if ( self::has_used_menus() ) {
			return;
		}
		?>
		<div id="masteriyo-nav-menu-notice" class="notice notice-info is-dismissible">
			<div data-masteriyo-state="prompt">
				<p>
					<strong><?php echo esc_html( $this->panel_name() ); ?>:</strong>
					<?php esc_html_e( 'Your visitors have no way to log in from your navigation yet. Add a smart Login | Logout link — it switches automatically with the visitor\'s login state.', 'learning-management-system' ); ?>
				</p>
				<p>
					<button type="button" id="masteriyo-add-login-link" class="button button-primary">
						<?php esc_html_e( 'Add Login | Logout link', 'learning-management-system' ); ?>
					</button>
					<a href="#masteriyo-nav-link" style="margin-inline-start: 8px;">
						<?php
						/* translators: %s: the nav-menu panel's name */
						echo esc_html( sprintf( __( 'or pick individual links from the %s panel', 'learning-management-system' ), $this->panel_name() ) );
						?>
					</a>
				</p>
			</div>
			<div data-masteriyo-state="success" hidden>
				<p>
					<strong><?php echo esc_html( $this->panel_name() ); ?>:</strong>
					<span data-masteriyo-success-text></span>
				</p>
				<p>
					<button type="button" id="masteriyo-undo-login-link" class="button">
						<?php esc_html_e( 'Undo', 'learning-management-system' ); ?>
					</button>
					<a data-masteriyo-edit-link href="" hidden style="margin-inline-start: 8px;">
						<?php esc_html_e( 'Edit menu', 'learning-management-system' ); ?>
					</a>
				</p>
			</div>
		</div>
		<script>
			( function() {
				var notice = document.getElementById( 'masteriyo-nav-menu-notice' );
				if ( ! notice ) {
					return;
				}
				var nonce      = <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;
				var addUrl     = <?php echo wp_json_encode( esc_url_raw( rest_url( 'masteriyo/v1/nav-menu/login-link' ) ) ); ?>;
				var dismissUrl = <?php echo wp_json_encode( esc_url_raw( rest_url( 'masteriyo/v1/nav-menu-notice/dismiss' ) ) ); ?>;
				var menusUrl   = <?php echo wp_json_encode( esc_url_raw( admin_url( 'nav-menus.php' ) ) ); ?>;
				var editorUrl  = <?php echo wp_json_encode( esc_url_raw( admin_url( 'site-editor.php?path=/navigation' ) ) ); ?>;
				var text       = {
					added: <?php /* translators: %s: menu or navigation name */ echo wp_json_encode( __( 'Done — a Login | Logout link was added to “%s”. It switches automatically with the visitor’s login state.', 'learning-management-system' ) ); ?>,
					addedNoName: <?php echo wp_json_encode( __( 'Done — a Login | Logout link was added to your navigation. It switches automatically with the visitor’s login state.', 'learning-management-system' ) ); ?>,
					exists: <?php echo wp_json_encode( __( 'A Login | Logout link already exists in your navigation.', 'learning-management-system' ) ); ?>,
					noLocation: <?php /* translators: %s: the nav-menu panel's name */ echo wp_json_encode( sprintf( __( 'The login link could not be added automatically — your theme has no menu locations. Pick individual links from the %s panel instead.', 'learning-management-system' ), $this->panel_name() ) ); ?>
				};
				var prompt  = notice.querySelector( '[data-masteriyo-state="prompt"]' );
				var success = notice.querySelector( '[data-masteriyo-state="success"]' );

				function showSuccess( data ) {
					var added    = 'added' === data.status;
					var editLink = success.querySelector( '[data-masteriyo-edit-link]' );
					var message  = text.exists;

					if ( added ) {
						message = data.target_name ? text.added.replace( '%s', data.target_name ) : text.addedNoName;
					} else if ( 'no_location' === data.status ) {
						message = text.noLocation;
					}

					success.querySelector( '[data-masteriyo-success-text]' ).textContent = message;
					document.getElementById( 'masteriyo-undo-login-link' ).hidden = ! added;

					if ( added && 'block' === data.theme_type ) {
						editLink.href   = editorUrl;
						editLink.hidden = false;
					} else if ( added && data.menu_id ) {
						editLink.href   = menusUrl + '?action=edit&menu=' + data.menu_id;
						editLink.hidden = false;
					} else {
						editLink.hidden = true;
					}

					prompt.hidden  = true;
					success.hidden = false;
					notice.classList.toggle( 'notice-success', added );
					notice.classList.toggle( 'notice-info', ! added );
				}

				function showPrompt() {
					success.hidden = true;
					prompt.hidden  = false;
					notice.classList.remove( 'notice-success' );
					notice.classList.add( 'notice-info' );
					document.getElementById( 'masteriyo-add-login-link' ).disabled = false;
				}

				notice.addEventListener( 'click', function( event ) {
					var target = event.target;

					if ( target.classList.contains( 'notice-dismiss' ) ) {
						window.fetch( dismissUrl, { method: 'POST', headers: { 'X-WP-Nonce': nonce } } );
						return;
					}

					if ( 'masteriyo-add-login-link' === target.id ) {
						target.disabled = true;
						window.fetch( addUrl, { method: 'POST', headers: { 'X-WP-Nonce': nonce } } )
							.then( function( response ) {
								if ( ! response.ok ) {
									throw new Error();
								}
								return response.json();
							} )
							.then( showSuccess )
							.catch( function() {
								target.disabled = false;
							} );
					}

					if ( 'masteriyo-undo-login-link' === target.id ) {
						target.disabled = true;
						window.fetch( addUrl, { method: 'DELETE', headers: { 'X-WP-Nonce': nonce } } )
							.then( function( response ) {
								if ( ! response.ok ) {
									throw new Error();
								}
								showPrompt();
							} )
							.finally( function() {
								target.disabled = false;
							} );
					}
				} );
			} )();
		</script>
		<?php
	}
}
