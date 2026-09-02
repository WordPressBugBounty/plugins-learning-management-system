<?php
/**
 * Install
 *
 * @since 1.0.0
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Taxonomy\Taxonomy;
use Masteriyo\Activation;
use Masteriyo\Setup\SampleContent;
use Masteriyo\PostType\PostType;

class Install {

	/**
	 * Initialization.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::create_difficulties();
		self::install();
	}

	/**
	 * Update Masteriyo information.
	 *
	 * @since 1.0.0
	 */
	public static function install() {
		$masteriyo_version = get_option( 'masteriyo_plugin_version' );

		// Roles must be verified on every load; it's a cheap in-memory check.
		self::maybe_create_roles();
		self::maybe_revoke_instructor_unfiltered_html();

		// Skip expensive DB writes and rewrite flush when version hasn't changed.
		if ( MASTERIYO_VERSION === $masteriyo_version ) {
			return;
		}

		if ( empty( $masteriyo_version ) ) {
			/**
			 * Filters boolean value to enable/disable setup wizard. True for enable.
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $enable True to enable setup wizard.
			 */
			$enable_setup_wizard = apply_filters( 'masteriyo_enable_setup_wizard', true );

			/**
			 * Filters boolean value to enable/disable setup wizard. True for enable.
			 *
			 * @since 1.0.0
			 * @deprecated x.x.x Use masteriyo_enable_setup_wizard instead. Retained so
			 *                   integrations written against the free plugin keep working.
			 *
			 * @param boolean $enable True to enable setup wizard.
			 */
			$enable_setup_wizard = apply_filters( 'masteriyo_free_enable_setup_wizard', $enable_setup_wizard );

			if ( $enable_setup_wizard ) {
				set_transient( '_masteriyo_activation_redirect', 1, 30 );
			}

			// Pages must exist even when the wizard is filtered off or abandoned.
			// Checkout is deliberately absent: whether this site sells is not known
			// yet, and it is created later by the wizard or on first need. Courses is
			// absent for a different reason: the course archive is already the public
			// listing, so the page only earns its place once a site has a catalog
			// worth pointing a menu at (#665) — CoursesPagePrompt offers it then.
			Activation::create_pages( array( 'courses', 'instructor-registration', 'instructors-list', 'checkout' ) );

			// Flag the site for a single async sample-content seed. Deferred to
			// action_scheduler_init: Install::install() runs on init priority 0
			// but Action Scheduler only bootstraps its store on init priority 1,
			// so enqueuing here would silently never run.
			update_option( SampleContent::PENDING_FLAG, 1, false );

			// Marketplace machinery is off for new sites. Written explicitly rather than
			// flipping the hardcoded defaults, which every existing site without a stored
			// value silently inherits. Raw writes: a hydrated tree would store the USD
			// default and block the wizard's currency inference.
			masteriyo_set_raw_setting( 'general.registration.enable_instructor_registration', false );
			masteriyo_set_raw_setting( 'accounts_page.display.enable_instructor_apply', false );

			// A ccTLD names where the business is, so stamp its money locale once, at
			// install: Settings, pricing and the wizard then agree from minute one, and
			// a later domain move never flips a live catalogue. Generic TLDs stamp
			// nothing and keep the USD defaults, leaving the wizard suggestion live.
			$host        = wp_parse_url( home_url(), PHP_URL_HOST );
			$host        = is_string( $host ) ? $host : '';
			$locale_info = masteriyo_get_domain_locale_info( $host );
			$currency    = masteriyo_infer_currency_from_domain( $host );
			$currency    = is_string( $currency ) ? strtoupper( sanitize_text_field( $currency ) ) : '';

			// Filter callbacks can return anything; only a known code may be stamped.
			if ( ! in_array( $currency, masteriyo_get_currency_codes(), true ) ) {
				$currency = '';
			}

			if ( $currency && ( $locale_info || 'USD' !== $currency ) ) {
				masteriyo_set_raw_setting( 'payments.currency.currency', $currency );

				// The print format is the locale row's; it only fits the row's own
				// code, not one a masteriyo_inferred_currency callback substituted.
				if ( $locale_info && $currency === $locale_info['currency_code'] ) {
					masteriyo_set_raw_setting( 'payments.currency.currency_position', $locale_info['currency_pos'] );
					masteriyo_set_raw_setting( 'payments.currency.thousand_separator', $locale_info['thousand_sep'] );
					masteriyo_set_raw_setting( 'payments.currency.decimal_separator', $locale_info['decimal_sep'] );
					masteriyo_set_raw_setting( 'payments.currency.number_of_decimals', $locale_info['num_decimals'] );
				}
			}
		}
		// Before the version write: install() early-returns on a matching version, so
		// anything after that write gets exactly one attempt per version bump — this
		// step's own marker-based retry only works while it runs before the bump.
		self::maybe_grandfather_commerce_visibility();

		update_option( 'masteriyo_plugin_version', MASTERIYO_VERSION );

		// Save the install date.
		if ( false === get_option( 'masteriyo_install_date' ) ) {
			update_option( 'masteriyo_install_date', current_time( 'mysql', true ) );
		}

		// One-time scrub of onboarding secrets for existing installs on next version bump.
		// The marker is written only when nothing needed scrubbing or the scrubbed write
		// succeeded; otherwise the secrets are still at rest and the next bump retries.
		if ( ! get_option( 'masteriyo_onboarding_secrets_redacted' ) ) {
			$onboarding_data = get_option( 'masteriyo_onboarding_data' );
			$scrubbed        = is_array( $onboarding_data ) ? masteriyo_redact_onboarding_secrets( $onboarding_data ) : $onboarding_data;

			if ( $scrubbed === $onboarding_data || update_option( 'masteriyo_onboarding_data', $scrubbed, false ) ) {
				update_option( 'masteriyo_onboarding_secrets_redacted', 1 );
			}
		}

		flush_rewrite_rules();
	}

	/**
	 * Grandfather commerce visibility for installs that are not genuinely new.
	 *
	 * `payments.enabled` defaults to '' and is compared strictly to 'yes', so leaving it
	 * unwritten hides commerce, not neutral. A site that already has a course, an enrollment
	 * or an order is never "genuinely new", so this writes the setting explicitly so it never
	 * loses its Orders menu to the evidence-only branch. Fresh installs are left untouched, so
	 * the live evidence rule governs them as they grow. Idempotent via the marker option.
	 *
	 * @since 2.31.0
	 */
	private static function maybe_grandfather_commerce_visibility() {
		if ( get_option( 'masteriyo_commerce_grandfathered' ) ) {
			return;
		}

		global $wpdb;

		$has_course = $wpdb->get_var(
			$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 1", PostType::COURSE ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$has_enrollment = $has_course ? null : $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}masteriyo_user_items LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$has_order = ( $has_course || $has_enrollment )
			? null
			: $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 1", PostType::ORDER ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		/*
		 * Mark this decided on the FIRST run, whatever the answer: returning early without the
		 * marker would re-run the probe on every later version bump, silently grandfathering a
		 * genuinely fresh install to 'yes' after it adds one free course — defeating commerce
		 * hiding for exactly the free-tier sites it exists to serve.
		 */
		if ( $has_course || $has_enrollment || $has_order ) {
			// Write the setting BEFORE the marker: if this throws or the request dies here, the marker stays absent and the next run retries instead of leaving the site permanently un-grandfathered.
			masteriyo_set_setting( 'payments.enabled', 'yes' );
		}

		update_option( 'masteriyo_commerce_grandfathered', 1 );
	}

	/**
	 * Recreate the Masteriyo roles if they have gone missing.
	 *
	 * Deactivating one plugin in dual-active mode (Free + Pro) removes the
	 * shared roles; the surviving plugin restores them here so user
	 * registration never throws an "Invalid roles" fatal. Cheap in-memory
	 * check — safe to run on every load.
	 *
	 * @return void
	 */
	private static function maybe_create_roles() {
		if ( null === get_role( Roles::STUDENT ) || null === get_role( Roles::INSTRUCTOR ) ) {
			Roles::create();
		}

		// Always sync, even right after create(): create() seeds the Masteriyo
		// roles but not the administrator's Masteriyo capabilities, and a load
		// that had to create the roles (fresh install without the activation
		// hook) would otherwise leave admins without them until the next request.
		Roles::sync_caps();
	}

	/**
	 * Revoke the `unfiltered_html` capability from the instructor role on existing sites.
	 *
	 * Granted in 1.6.13 so instructors could embed iframes, but it also let a low-trust
	 * instructor store raw <script>/on* payloads that execute in any viewer's browser,
	 * including admins (MAS-3779, stored XSS). Iframe embedding and <style> are already
	 * covered without this capability by masteriyo_add_iframe_to_post_context() and
	 * masteriyo_add_style_to_post_context() via wp_kses_allowed_html.
	 * Roles::sync_caps() above only adds capabilities missing from the stored role, it never
	 * removes one, so an existing site's persisted role capabilities never pick up a removal
	 * made here in code without an explicit revoke like this. Cheap in-memory check — safe to
	 * run on every load.
	 *
	 * @return void
	 */
	private static function maybe_revoke_instructor_unfiltered_html() {
		$role = get_role( Roles::INSTRUCTOR );

		if ( $role && $role->has_cap( 'unfiltered_html' ) ) {
			$role->remove_cap( 'unfiltered_html' );
		}
	}

	/**
	 * Remove previous roles.
	 *
	 * @since 1.3.0
	 * @since 1.5.37 Moved to Roles class.
	 *
	 * @deprecated 1.5.37
	 */
	public static function remove_roles() {
		// Remove the masteriyo manager role for now.
		remove_role( 'masteriyo_manager' );

		foreach ( Roles::get_all() as $role_slug => $role ) {
			remove_role( $role_slug );
		}
	}

	/**
	 * Create roles.
	 *
	 * @since 1.0.0
	 * @since 1.5.37 Move to Activation class.
	 *
	 * @deprecated 1.5.37
	 */
	private static function create_roles() {
		foreach ( Roles::get_all() as $role_slug => $role ) {
			add_role( $role_slug, $role['display_name'], $role['capabilities'] );
		}
	}

	/**
	 * Create default difficulties.
	 *
	 * @since 1.0.0
	 */
	public static function create_difficulties() {

		$difficulty_count = wp_count_terms(
			array(
				'taxonomy'   => Taxonomy::COURSE_DIFFICULTY,
				'hide_empty' => false,
			)
		);

		if ( $difficulty_count > 0 ) {
			return;
		}

		$terms = array(
			'beginner'     => esc_html__( 'Beginner', 'learning-management-system' ),
			'intermediate' => esc_html__( 'Intermediate', 'learning-management-system' ),
			'expert'       => esc_html__( 'Expert', 'learning-management-system' ),
		);

		foreach ( $terms as $slug => $name ) {
			$term = get_term_by( 'slug', $slug, 'course_difficulty' );

			if ( false === $term ) {
				wp_insert_term( $name, 'course_difficulty' );
			}
		}
	}

	/**
	 * Return a list of Masteriyo tables.
	 *
	 * @since 1.5.20
	 *
	 * @return string[]
	 */
	public static function get_tables() {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}masteriyo_notifications",
			"{$wpdb->prefix}masteriyo_user_items",
			"{$wpdb->prefix}masteriyo_user_itemmeta",
			"{$wpdb->prefix}masteriyo_user_activities",
			"{$wpdb->prefix}masteriyo_user_activitymeta",
			"{$wpdb->prefix}masteriyo_sessions",
			"{$wpdb->prefix}masteriyo_order_items",
			"{$wpdb->prefix}masteriyo_order_itemmeta",
			"{$wpdb->prefix}masteriyo_quiz_attempts",
			"{$wpdb->prefix}masteriyo_quiz_question_rel",
			"{$wpdb->prefix}masteriyo_migrations",
			// Dead table (trailing underscore intact) created since 2023 by a typo'd,
			// since-deleted pro payment-retry migration; nothing ever read or wrote it.
			"{$wpdb->prefix}masteriyo_subscription_",
		);

		/**
		 * Filter the list of known Masteriyo tables.
		 *
		 * @since 1.5.20
		 *
		 * @param array $tables An array of Masteriyo-specific database table names.
		 */
		$tables = apply_filters( 'masteriyo_get_tables', $tables );

		return $tables;
	}
}
