<?php
/**
 * One-time offer of a public Courses page, once a site has a catalog worth one.
 *
 * Masteriyo no longer creates the Courses page at install or during the wizard
 * (#665): the course archive is already the public listing, and a fresh site
 * does not need a fifth page it never asked for. The page is still worth having
 * for a site that wants to edit its intro copy or drop it into a menu, so the
 * offer is made once the catalog exists rather than never.
 */

namespace Masteriyo;

use Masteriyo\PostType\PostType;

defined( 'ABSPATH' ) || exit;

class CoursesPagePrompt {

	/**
	 * Option recording how the prompt was answered: 'created' or 'dismissed'.
	 *
	 * Its presence is what makes the prompt one-time; the value is for support.
	 *
	 * @var string
	 */
	const STATE_OPTION = 'masteriyo_courses_page_prompt';

	/**
	 * Query arg carrying the answer back from the notice.
	 *
	 * @var string
	 */
	const ACTION_ARG = 'masteriyo_courses_page';

	/**
	 * Nonce action for both answers.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'masteriyo_courses_page_prompt';

	/**
	 * Transient prefix carrying what the click did, per user.
	 *
	 * The redirect that follows the click is what loses the outcome, so it has to
	 * survive exactly one request rather than ride in the URL. Holds the new page
	 * ID on success and 0 when nothing was created.
	 *
	 * @var string
	 */
	const RESULT_TRANSIENT_PREFIX = 'masteriyo_courses_page_prompt_result_';

	/**
	 * Published courses a site needs before it is asked.
	 *
	 * One course is a site still finding its feet; the second is the point where a
	 * listing starts to mean something.
	 *
	 * @var int
	 */
	const COURSE_THRESHOLD = 2;

	/**
	 * Register the prompt.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'handle_action' ) );
		add_action( 'masteriyo_admin_notices', array( __CLASS__, 'render' ) );
	}

	/**
	 * Whether the prompt still has anything to ask.
	 *
	 * @return bool
	 */
	public static function should_show() {
		if ( ! self::user_can_answer() ) {
			return false;
		}

		if ( get_option( self::STATE_OPTION ) ) {
			return false;
		}

		// Assigning a page by hand answers the question as well as the notice does.
		if ( self::has_courses_page() ) {
			return false;
		}

		return self::has_enough_published_courses();
	}

	/**
	 * Whether this user may answer the offer.
	 *
	 * The answer writes a Masteriyo page setting, so a manager who owns that screen
	 * qualifies — gating on manage_options alone would show them a Courses page they
	 * could configure by hand but never accept here.
	 *
	 * @return bool
	 */
	protected static function user_can_answer() {
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' );
	}

	/**
	 * Whether a usable Courses page is already assigned.
	 *
	 * @return bool
	 */
	protected static function has_courses_page() {
		$page_id = masteriyo_get_page_id( 'courses' );

		return 0 < $page_id && 'publish' === get_post_status( $page_id );
	}

	/**
	 * Whether the site has published enough courses to be asked.
	 *
	 * Capped at the threshold, so this stays a two-row query however large the
	 * catalog grows.
	 *
	 * @return bool
	 */
	protected static function has_enough_published_courses() {
		$query = new \WP_Query(
			array(
				'post_type'              => PostType::COURSE,
				'post_status'            => 'publish',
				'posts_per_page'         => self::COURSE_THRESHOLD,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return self::COURSE_THRESHOLD <= count( $query->posts );
	}

	/**
	 * Act on the answer and drop the query args from the URL.
	 */
	public static function handle_action() {
		$action = isset( $_GET[ self::ACTION_ARG ] ) ? sanitize_key( wp_unslash( $_GET[ self::ACTION_ARG ] ) ) : '';

		if ( ! in_array( $action, array( 'create', 'dismiss' ), true ) ) {
			return;
		}

		if ( ! self::user_can_answer() ) {
			return;
		}

		// check_admin_referer() over a bare wp_verify_nonce(): a stale link — a
		// bookmark, a second tab, a browser restoring yesterday's screen — otherwise
		// looks exactly like a click that did nothing. This is WordPress's own
		// expired-link screen, with a way back.
		check_admin_referer( self::NONCE_ACTION );

		self::perform( $action );

		wp_safe_redirect( remove_query_arg( array( self::ACTION_ARG, '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Record the answer, doing the work it asks for.
	 *
	 * Separate from handle_action() so the authorized path is reachable without the
	 * redirect that ends the request — the branch that actually changes the site was
	 * otherwise the one branch no test could run.
	 *
	 * @param string $action Either 'create' or 'dismiss'.
	 * @return bool Whether the offer is now answered.
	 */
	public static function perform( $action ) {
		// A creation that produced no page must not retire the prompt: recording it as
		// answered would leave the user with no page and nothing left to click. The
		// state stays unwritten so the offer returns, and the next screen says why.
		$answered = 'dismiss' === $action || self::create_page();

		if ( $answered ) {
			update_option( self::STATE_OPTION, 'create' === $action ? 'created' : 'dismissed', false );
		}

		// Creating a page in silence is indistinguishable from a click that did
		// nothing — the notice vanishes either way and the page lands somewhere the
		// user may not be looking. Carry the outcome across the redirect and say it.
		if ( 'create' === $action ) {
			set_transient(
				self::RESULT_TRANSIENT_PREFIX . get_current_user_id(),
				array( 'page_id' => $answered ? absint( masteriyo_get_page_id( 'courses' ) ) : 0 ),
				MINUTE_IN_SECONDS
			);
		}

		return $answered;
	}

	/**
	 * Create the Courses page and assign it.
	 *
	 * @return bool Whether a published Courses page is assigned afterwards.
	 */
	public static function create_page() {
		// create_pages() takes the pages to SKIP, so naming the five to leave alone
		// would silently create any sixth a third party adds through this filter.
		// Narrowing the list to Courses is what SetupPagesAjaxHandler does, and it
		// stays correct however the page set grows.
		$only_courses = function ( $pages ) {
			return array_intersect_key( $pages, array( 'courses' => true ) );
		};

		add_filter( 'masteriyo_create_pages', $only_courses );
		Activation::create_pages();
		remove_filter( 'masteriyo_create_pages', $only_courses );

		if ( ! self::has_courses_page() ) {
			return false;
		}

		// The course archive is registered at the Courses page URI when there is one,
		// so the rules it was registered with this request are now stale. The post
		// type is already registered by admin_init, hence the flag over a direct
		// flush: masteriyo_maybe_flush_rewrite() runs it on the next 'init'.
		update_option( 'masteriyo_flush_rewrite_rules', 'yes' );

		return true;
	}

	/**
	 * Report what the last click did, then forget it.
	 */
	protected static function render_result_notice() {
		$key    = self::RESULT_TRANSIENT_PREFIX . get_current_user_id();
		$result = get_transient( $key );

		if ( ! is_array( $result ) ) {
			return;
		}

		delete_transient( $key );

		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=masteriyo#/settings?first=general&second=pages' ) ) . '">' . esc_html__( 'Settings → Pages', 'learning-management-system' ) . '</a>';
		$page_id       = absint( $result['page_id'] ?? 0 );

		if ( ! $page_id ) {
			printf(
				'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
				esc_html( self::brand_prefix() ),
				sprintf(
					/* translators: %s: link to the Masteriyo pages settings */
					wp_kses_post( __( 'The Courses page could not be created. Assign one in %s instead.', 'learning-management-system' ) ),
					wp_kses_post( $settings_link )
				)
			);

			return;
		}

		// Both destinations are worth offering: the page itself is where the intro
		// copy goes, and the settings row is the proof it was actually assigned.
		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>%s</strong> %s</p></div>',
			esc_html( self::brand_prefix() ),
			sprintf(
				/* translators: 1: link to edit the new Courses page, 2: link to view it, 3: link to the Masteriyo pages settings */
				wp_kses_post( __( 'Your Courses page is ready — %1$s or %2$s. It is now set as your Courses page in %3$s.', 'learning-management-system' ) ),
				'<a href="' . esc_url( (string) get_edit_post_link( $page_id ) ) . '">' . esc_html__( 'edit it', 'learning-management-system' ) . '</a>',
				'<a href="' . esc_url( (string) get_permalink( $page_id ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'view it', 'learning-management-system' ) . '</a>',
				wp_kses_post( $settings_link )
			)
		);
	}

	/**
	 * The "Brand:" prefix the notices open with.
	 *
	 * @return string
	 */
	protected static function brand_prefix() {
		/* translators: %s: the product's name */
		return sprintf( __( '%s:', 'learning-management-system' ), masteriyo_get_plugin_name() );
	}

	/**
	 * Print the notice.
	 */
	public static function render() {
		self::render_result_notice();

		if ( ! self::should_show() ) {
			return;
		}

		$create_url   = wp_nonce_url( add_query_arg( self::ACTION_ARG, 'create' ), self::NONCE_ACTION );
		$dismiss_url  = wp_nonce_url( add_query_arg( self::ACTION_ARG, 'dismiss' ), self::NONCE_ACTION );
		$settings_url = admin_url( 'admin.php?page=masteriyo#/settings?first=general&second=pages' );
		$archive_link = get_post_type_archive_link( PostType::COURSE );

		$message = sprintf(
			/* translators: %s: the product's name */
			__( 'Your courses are already public, so %s did not create a Courses page for you.', 'learning-management-system' ),
			masteriyo_get_plugin_name()
		);

		if ( $archive_link ) {
			$message = sprintf(
				/* translators: 1: URL of the course archive, 2: the product's name */
				__( 'Your courses are already listed at <a href="%1$s">the course archive</a>, so %2$s did not create a Courses page for you.', 'learning-management-system' ),
				esc_url( $archive_link ),
				masteriyo_get_plugin_name()
			);
		}

		?>
		<div class="notice notice-info masteriyo-courses-page-prompt">
			<p>
				<strong><?php echo esc_html( self::brand_prefix() ); ?></strong>
				<?php echo wp_kses_post( $message ); ?>
				<?php esc_html_e( 'Want one anyway, so you can add your own intro and put it in a menu?', 'learning-management-system' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $create_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Create a Courses page', 'learning-management-system' ); ?>
				</a>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="button">
					<?php esc_html_e( 'Choose an existing page', 'learning-management-system' ); ?>
				</a>
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button-link">
					<?php esc_html_e( 'No thanks', 'learning-management-system' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
