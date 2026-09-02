<?php
/**
 * Masteriyo Home setup guide.
 *
 * @package Masteriyo\Setup
 */

namespace Masteriyo\Setup;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Jobs\SampleContentSeedJob;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;
use Masteriyo\Taxonomy\Taxonomy;

/**
 * Class HomeGuide
 *
 * Decides which setup cards Masteriyo Home shows and which are already done.
 * Every card reads the product state it asks the user to create; none completes on
 * a click. get_facts() is the WordPress glue, build_cards() a pure function of
 * those facts, so the branching is testable without WordPress.
 */
class HomeGuide {

	/**
	 * Option holding the card ids the user dismissed.
	 *
	 * @var string
	 */
	const DISMISSED_OPTION = 'masteriyo_home_guide_dismissed';

	/**
	 * Option set when the user turns the whole guide off.
	 *
	 * The escape hatch for numbered cards, which carry no dismiss control.
	 *
	 * @var string
	 */
	const GUIDE_OFF_OPTION = 'masteriyo_home_guide_off';

	/**
	 * The last computed answer to "has the guide finished?".
	 *
	 * is_complete() costs about twenty queries. That is fine on a Masteriyo
	 * screen and not fine in the admin menu, which is rebuilt on every wp-admin
	 * page load. The menu reads this flag; whoever computes the real answer
	 * refreshes it.
	 *
	 * @var string
	 */
	const COMPLETE_OPTION = 'masteriyo_home_guide_complete';

	/**
	 * Option recording that a person chose the store currency.
	 *
	 * @var string
	 */
	const CURRENCY_CONFIRMED_OPTION = 'masteriyo_currency_confirmed';

	/**
	 * Cards that carry no number and never block the guide from finishing.
	 *
	 * `preview` is here because no state distinguishes previewed from not, and
	 * completing on a click is ruled out — it would hold the guide open forever.
	 *
	 * @var string[]
	 */
	const OPTIONAL_CARDS = array( 'preview', 'starter_templates', 'choose_access' );

	/**
	 * Gather the product state the cards are derived from.
	 *
	 * Memoized per request — the gathering runs several uncached queries. Pass
	 * $fresh after mutating any input (dismissals, options, pages) in the same
	 * request, or the response describes the state from before the change.
	 *
	 * @param bool $fresh Recompute even if already gathered this request.
	 * @return array
	 */
	public static function get_facts( $fresh = false ) {
		static $memo = null;

		if ( ! $fresh && null !== $memo ) {
			return $memo;
		}

		$access = self::stored_access();

		$paid_course_id    = self::paid_published_course_id();
		$payment_connected = self::is_payment_usable();

		$facts = array(
			'learner_access'            => $access,
			'missing_pages'             => (array) check_required_pages(),
			'show_starter_templates'    => 'no' !== get_option( 'show_starters_templates', 'yes' ),
			'has_real_course'           => self::has_real_course( PostStatus::ANY ),
			'has_paid_published_course' => $paid_course_id > 0,
			'storefront_url'            => $paid_course_id > 0 ? get_permalink( $paid_course_id ) : '',
			'currency_confirmed'        => self::is_currency_confirmed(),
			'payment_connected'         => $payment_connected,
			// Not masteriyo_site_needs_checkout(): that is true for any site that merely
			// has a Checkout page, which is every install before Onboarding v2.
			'commerce_enabled'          => $payment_connected || $paid_course_id > 0,
			'has_learner'               => self::has_learner(),
			'has_enrollment'            => self::has_enrollment(),
			'has_completed_order'       => self::has_completed_order(),
			'sample'                    => self::get_sample_state(),
			'dismissed'                 => self::get_dismissed(),
			'guide_dismissed'           => (bool) get_option( self::GUIDE_OFF_OPTION ),
		);

		/**
		 * Filters the product state Masteriyo Home derives its setup cards from.
		 *
		 * @param array $facts The gathered state.
		 */
		$memo = apply_filters( 'masteriyo_home_guide_facts', $facts );

		return $memo;
	}

	/**
	 * Build the ordered card list for a given set of facts.
	 *
	 * Pure. Copy and links live in the admin bundle.
	 *
	 * @param array $facts Facts from get_facts().
	 * @return array[] List of array( 'id' => string, 'status' => 'todo'|'done', 'optional' => bool, 'number' => int|null ).
	 */
	public static function build_cards( array $facts ) {
		if ( ! empty( $facts['guide_dismissed'] ) ) {
			return array();
		}

		$access    = $facts['learner_access'] ?? '';
		$dismissed = (array) ( $facts['dismissed'] ?? array() );
		$ids       = array();

		// A dismissal must not outlive the answer that made the card optional.
		if ( '' === $access ) {
			$dismissed = array_values( array_diff( $dismissed, array( 'choose_access' ) ) );
		}

		if ( ! empty( $facts['missing_pages'] ) ) {
			$ids[] = 'required_pages';
		}

		// "Just exploring" is an answer; never having answered is not.
		if ( '' === $access || 'explore' === $access ) {
			$ids[] = 'choose_access';
		}

		$ids[] = 'first_course';
		$ids[] = 'preview';

		$sell   = self::sell_cards( $facts );
		$enroll = self::enroll_cards( $facts );

		if ( 'sell' === $access ) {
			$ids = array_merge( $ids, $sell );
		} elseif ( 'enroll' === $access ) {
			// No payment card until the site actually turns commerce on by itself.
			$ids = array_merge( $ids, $enroll, $facts['commerce_enabled'] ? $sell : array() );
		} elseif ( 'both' === $access ) {
			// One step per path, or the guide doubles in length.
			$ids = array_merge(
				$ids,
				self::first_incomplete( $sell, $facts, $dismissed ),
				self::first_incomplete( $enroll, $facts, $dismissed )
			);
		}

		if ( $facts['show_starter_templates'] ?? false ) {
			$ids[] = 'starter_templates';
		}

		$cards  = array();
		$number = 0;

		foreach ( array_values( array_unique( $ids ) ) as $id ) {
			if ( in_array( $id, $dismissed, true ) ) {
				continue;
			}

			$optional = self::is_optional( $id, $access );
			$status   = self::is_done( $id, $facts ) ? 'done' : 'todo';

			if ( ! $optional ) {
				++$number;
			}

			$cards[] = array(
				'id'       => $id,
				'status'   => $status,
				'optional' => $optional,
				'number'   => $optional ? null : $number,
			);
		}

		return $cards;
	}

	/**
	 * The stored learner-access answer, whitelisted through a single reader.
	 *
	 * @return string
	 */
	private static function stored_access() {
		$onboarding = get_option( 'masteriyo_onboarding_data', array() );
		$access     = $onboarding['steps']['welcome']['options']['learner_access'] ?? '';

		return in_array( $access, array( 'sell', 'enroll', 'both', 'explore' ), true ) ? $access : '';
	}

	/**
	 * Whether a card is an unnumbered recommendation for this site.
	 *
	 * Only the access question varies: dismissible once answered, numbered before.
	 *
	 * @param string $id     Card id.
	 * @param string $access Stored learner-access answer.
	 * @return bool
	 */
	private static function is_optional( $id, $access ) {
		if ( 'choose_access' === $id ) {
			return '' !== $access;
		}

		return in_array( $id, self::OPTIONAL_CARDS, true );
	}

	/**
	 * Whether every numbered card is done.
	 *
	 * @param array $facts Facts from get_facts().
	 * @return bool
	 */
	public static function is_complete( array $facts ) {
		foreach ( self::build_cards( $facts ) as $card ) {
			if ( ! $card['optional'] && 'done' !== $card['status'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Record the real answer for the cheap readers.
	 *
	 * @param bool $complete Whether the guide is finished.
	 * @return void
	 */
	public static function remember_completion( $complete ) {
		$value = $complete ? 'yes' : 'no';

		if ( get_option( self::COMPLETE_OPTION ) !== $value ) {
			update_option( self::COMPLETE_OPTION, $value, true );
		}
	}

	/**
	 * The last recorded answer, for code that cannot afford to compute it.
	 *
	 * Unknown reads as "not finished", so a site that has never rendered a
	 * Masteriyo screen is pointed at the guide. Being wrong that way costs one
	 * redirect; being wrong the other way hides the guide from the person who
	 * still needs it.
	 *
	 * @return bool
	 */
	public static function was_complete() {
		return 'yes' === get_option( self::COMPLETE_OPTION, '' );
	}

	/**
	 * Card ids for the selling path, in order.
	 *
	 * @param array $facts Facts from get_facts().
	 * @return string[]
	 */
	private static function sell_cards( array $facts ) {
		unset( $facts );

		return array( 'currency', 'payment_method', 'price_and_publish', 'test_checkout' );
	}

	/**
	 * Card ids for the enrol-them-ourselves path, in order.
	 *
	 * @param array $facts Facts from get_facts().
	 * @return string[]
	 */
	private static function enroll_cards( array $facts ) {
		unset( $facts );

		return array( 'add_learner', 'enroll_learner' );
	}

	/**
	 * Keep the done cards and the first card that is not done.
	 *
	 * A dismissed card is not a stopping point, or its path never advances.
	 *
	 * @param string[] $ids       Card ids in order.
	 * @param array    $facts     Facts from get_facts().
	 * @param string[] $dismissed Dismissed card ids.
	 * @return string[]
	 */
	private static function first_incomplete( array $ids, array $facts, array $dismissed = array() ) {
		$kept = array();

		foreach ( $ids as $id ) {
			$kept[] = $id;

			if ( ! self::is_done( $id, $facts ) && ! in_array( $id, $dismissed, true ) ) {
				break;
			}
		}

		return $kept;
	}

	/**
	 * Whether a card's underlying product state already exists.
	 *
	 * @param string $id    Card id.
	 * @param array  $facts Facts from get_facts().
	 * @return bool
	 */
	private static function is_done( $id, array $facts ) {
		switch ( $id ) {
			case 'required_pages':
				return empty( $facts['missing_pages'] );
			case 'choose_access':
				return '' !== ( $facts['learner_access'] ?? '' );
			case 'first_course':
				return ! empty( $facts['has_real_course'] );
			case 'currency':
				return ! empty( $facts['currency_confirmed'] );
			case 'payment_method':
				return ! empty( $facts['payment_connected'] );
			case 'price_and_publish':
				return ! empty( $facts['has_paid_published_course'] );
			case 'test_checkout':
				return ! empty( $facts['has_completed_order'] );
			case 'add_learner':
				return ! empty( $facts['has_learner'] );
			case 'enroll_learner':
				return ! empty( $facts['has_enrollment'] );
			default:
				return false;
		}
	}

	/**
	 * Record a dismissed card.
	 *
	 * @param string $id Card id.
	 * @return bool Whether the card is dismissible and is now dismissed.
	 */
	public static function dismiss( $id ) {
		$id = sanitize_key( $id );

		// Unnumbered, known ids only: a milestone must not be dismissible, and an open
		// list would grow the option without bound.
		if ( ! in_array( $id, self::OPTIONAL_CARDS, true ) ) {
			return false;
		}

		// Same guard as build_cards(), reading the answer the same way.
		if ( ! self::is_optional( $id, self::stored_access() ) ) {
			return false;
		}

		$dismissed = self::get_dismissed();

		if ( ! in_array( $id, $dismissed, true ) ) {
			$dismissed[] = $id;
			update_option( self::DISMISSED_OPTION, $dismissed, false );
		}

		return true;
	}

	/**
	 * Turn the whole guide off.
	 *
	 * @return void
	 */
	public static function dismiss_guide() {
		update_option( self::GUIDE_OFF_OPTION, 1, false );
	}

	/**
	 * The dismissed card ids.
	 *
	 * @return string[]
	 */
	private static function get_dismissed() {
		$dismissed = get_option( self::DISMISSED_OPTION, array() );
		$dismissed = is_array( $dismissed ) ? array_values( array_filter( array_map( 'sanitize_key', $dismissed ) ) ) : array();

		// The old Home's "Skip Setup" answer carries over.
		if ( 'yes' === get_option( 'skip_payment_setup' ) && ! in_array( 'payment_method', $dismissed, true ) ) {
			$dismissed[] = 'payment_method';
		}

		return $dismissed;
	}

	/**
	 * Whether a course the user did not get from the sample pack exists.
	 *
	 * @param string $status Post status to look for, or 'any'.
	 * @return bool
	 */
	private static function has_real_course( $status ) {
		$courses = get_posts(
			array(
				'post_type'        => PostType::COURSE,
				'post_status'      => $status,
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_masteriyo_is_sample_content',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		return ! empty( $courses );
	}

	/**
	 * The id of a published course a buyer could pay for.
	 *
	 * Samples excluded: the bundled cohort example ships with a price.
	 *
	 * @return int Course id, or 0 when the site sells nothing.
	 */
	private static function paid_published_course_id() {
		$args = array(
			'post_type'        => PostType::COURSE,
			'post_status'      => PostStatus::PUBLISH,
			'numberposts'      => 1,
			'fields'           => 'ids',
			'suppress_filters' => false,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_masteriyo_is_sample_content',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$has_price = array(
			'key'     => '_price',
			'value'   => 0,
			'type'    => 'NUMERIC',
			'compare' => '>',
		);

		// The price clause is still needed: CourseRepository sets the paid term from
		// the access mode alone, so a one-time course priced at zero carries it.
		$tagged_args               = $args;
		$tagged_args['meta_query'] = array_merge( $args['meta_query'], array( $has_price ) );
		$tagged_args['tax_query']  = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => Taxonomy::COURSE_VISIBILITY,
				'field'    => 'name',
				'terms'    => CoursePriceType::PAID,
			),
		);

		$tagged = get_posts( $tagged_args );

		if ( ! empty( $tagged ) ) {
			return absint( $tagged[0] );
		}

		// The term is only written on save, so older courses need the slow path.
		$args['numberposts']  = 20;
		$args['meta_query'][] = $has_price;

		foreach ( get_posts( $args ) as $course_id ) {
			$course = masteriyo_get_course( $course_id );

			if ( $course && CoursePriceType::FREE !== $course->get_price_type() ) {
				return absint( $course_id );
			}
		}

		return 0;
	}

	/**
	 * Whether the store currency was ever chosen rather than defaulted.
	 *
	 * Not the settings option: install writes the whole default tree, so a fresh
	 * site already stores USD.
	 *
	 * @return bool
	 */
	private static function is_currency_confirmed() {
		if ( get_option( self::CURRENCY_CONFIRMED_OPTION ) ) {
			return true;
		}

		$onboarding = get_option( 'masteriyo_onboarding_data', array() );
		$chosen     = $onboarding['steps']['setup']['options']['payments']['currency'] ?? '';

		return '' !== (string) $chosen;
	}

	/**
	 * Register the hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'masteriyo_rest_insert_setting_object', array( __CLASS__, 'maybe_record_currency_confirmation' ), 10, 2 );
	}

	/**
	 * Record that the currency was chosen, when a save actually submits one.
	 *
	 * The request is the evidence; any settings save would tick on an editor switch.
	 *
	 * @param mixed            $object  The saved setting object.
	 * @param \WP_REST_Request $request The request behind the save.
	 * @return void
	 */
	public static function maybe_record_currency_confirmation( $object, $request ) {
		unset( $object );

		$submitted = $request['payments']['currency']['currency'] ?? '';

		if ( '' === (string) $submitted ) {
			return;
		}

		update_option( self::CURRENCY_CONFIRMED_OPTION, 1, false );
	}

	/**
	 * Whether at least one payment method is switched on.
	 *
	 * Public because masteriyo_site_needs_checkout() must ask the same question.
	 *
	 * @return bool
	 */
	public static function is_payment_connected() {
		// Cheap and always answerable, including before the container is built.
		if ( masteriyo_string_to_bool( masteriyo_get_setting( 'payments.offline.enable' ) )
			|| masteriyo_string_to_bool( masteriyo_get_setting( 'payments.paypal.enable' ) ) ) {
			return true;
		}

		// Then the registry, so Mollie, Lemon Squeezy and Razorpay count too.
		$gateways = masteriyo( 'payment-gateways' );

		if ( is_object( $gateways ) && method_exists( $gateways, 'get_available_payment_gateways' ) ) {
			return ! empty( $gateways->get_available_payment_gateways() );
		}

		return false;
	}

	/**
	 * Whether at least one payment method could actually take money.
	 *
	 * Stricter than is_payment_connected(), which reads intent (a toggle) and is
	 * the right question for "does this site need a Checkout page". Marking the
	 * guide's payment card done needs proof: PayPal without an email and Stripe
	 * without a completed Connect are switched on yet cannot charge anyone.
	 * Offline has no credential — its toggle is the whole setup.
	 *
	 * @return bool
	 */
	public static function is_payment_usable() {
		if ( masteriyo_string_to_bool( masteriyo_get_setting( 'payments.offline.enable' ) ) ) {
			return true;
		}

		if ( masteriyo_string_to_bool( masteriyo_get_setting( 'payments.paypal.enable' ) )
			&& '' !== (string) masteriyo_get_setting( 'payments.paypal.email' ) ) {
			return true;
		}

		$gateways = masteriyo( 'payment-gateways' );

		if ( is_object( $gateways ) && method_exists( $gateways, 'get_available_payment_gateways' ) ) {
			foreach ( array_keys( (array) $gateways->get_available_payment_gateways() ) as $id ) {
				if ( 'paypal' === $id ) {
					continue; // Judged above, on the email.
				}

				if ( 'stripe' === $id ) {
					if ( class_exists( '\Masteriyo\Addons\Stripe\Setting' ) && \Masteriyo\Addons\Stripe\Setting::get_stripe_user_id() ) {
						return true;
					}
					continue;
				}

				// Other gateways keep their availability as the signal — their
				// configuredness is not this class's to re-derive.
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the site has at least one student account.
	 *
	 * @return bool
	 */
	private static function has_learner() {
		$users = get_users(
			array(
				'role__in' => array( Roles::STUDENT ),
				'fields'   => 'ID',
				'number'   => 1,
			)
		);

		return ! empty( $users );
	}

	/**
	 * Whether anyone has ever been enrolled in anything.
	 *
	 * Any recorded enrolment, so an expiring one cannot un-complete the card.
	 *
	 * @return bool
	 */
	private static function has_enrollment() {
		global $wpdb;

		if ( ! $wpdb ) {
			return false;
		}

		// Existence, not a tally: this runs on every guide evaluation, admin boot included.
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->prefix}masteriyo_user_items WHERE item_type = %s LIMIT 1",
				'user_course'
			)
		);

		return ! empty( $found );
	}

	/**
	 * Whether someone has completed a purchase.
	 *
	 * Orders the site made for itself do not count. Origins are excluded by name
	 * rather than requiring `checkout`, so integrations still count as sales.
	 *
	 * @return bool
	 */
	private static function has_completed_order() {
		$orders = get_posts(
			array(
				'post_type'        => PostType::ORDER,
				'post_status'      => OrderStatus::COMPLETED,
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => '_created_via',
						'value'   => array( 'rest-api', 'manual-enrollment', 'manual-enrollment__trashed', 'migration' ),
						'compare' => 'NOT IN',
					),
					array(
						'key'     => '_created_via',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		return ! empty( $orders );
	}

	/**
	 * What became of the bundled sample content, and what to preview.
	 *
	 * Public so the REST layer can re-derive it rather than trust the client.
	 *
	 * @return array
	 */
	public static function get_sample_state() {
		$option  = get_option( SampleContent::OPTION );
		$retries = (int) get_option( SampleContent::RETRIES, 0 );

		// The install flag is deleted the moment the job is accepted, so ask the
		// scheduler as well.
		$pending = (bool) get_option( SampleContent::PENDING_FLAG );

		if ( ! $pending && function_exists( 'as_next_scheduled_action' ) ) {
			$pending = false !== as_next_scheduled_action(
				SampleContentSeedJob::NAME,
				array( array( 'product-tour' ) ),
				SampleContentSeedJob::GROUP_NAME
			);
		}

		$state     = 'none';
		$course_id = 0;

		if ( is_array( $option ) ) {
			$course_id = self::first_existing_course( $option['course_ids'] ?? array() );

			if ( ! empty( $option['removed'] ) || ( ! empty( $option['slugs'] ) && 0 === $course_id ) ) {
				// Imported once and gone since — the honest offer is to import it again.
				$state = 'removed';
			} elseif ( $course_id > 0 ) {
				$state = 'ready';
			}
		}

		// Not an else: forget() leaves the option in place with an empty slug list.
		if ( 'none' === $state ) {
			if ( $pending ) {
				$state = 'pending';
			} elseif ( $retries > SampleContent::MAX_RETRIES ) {
				$state = 'failed';
			} elseif ( self::has_real_course( PostStatus::ANY ) ) {
				// Never seeded because the site already had content of its own.
				$state = 'skipped';
			}
		}

		if ( 0 === $course_id ) {
			$course_id = self::newest_course_id();
		}

		return array(
			'state'       => $state,
			'course_id'   => $course_id,
			'preview_url' => self::viewable_url( $course_id ),
		);
	}

	/**
	 * A URL that actually renders the course.
	 *
	 * A draft has no public permalink; get_permalink() would answer 404.
	 *
	 * @param int $course_id Course id.
	 * @return string
	 */
	private static function viewable_url( $course_id ) {
		if ( $course_id < 1 ) {
			return '';
		}

		if ( PostStatus::PUBLISH === get_post_status( $course_id ) ) {
			return (string) get_permalink( $course_id );
		}

		return (string) get_preview_post_link( $course_id );
	}

	/**
	 * The first id in the list that still points at a course.
	 *
	 * @param array $ids Course ids.
	 * @return int
	 */
	private static function first_existing_course( $ids ) {
		foreach ( (array) $ids as $id ) {
			$id = absint( $id );

			if ( $id > 0 && PostType::COURSE === get_post_type( $id ) && 'trash' !== get_post_status( $id ) ) {
				return $id;
			}
		}

		return 0;
	}

	/**
	 * The most recently created course of any status, sample or not.
	 *
	 * @return int
	 */
	private static function newest_course_id() {
		$courses = get_posts(
			array(
				'post_type'        => PostType::COURSE,
				// FUTURE too, so this agrees with has_real_course( ANY ).
				'post_status'      => array( PostStatus::PUBLISH, PostStatus::DRAFT, PostStatus::PENDING, PostStatus::PVT, PostStatus::FUTURE ),
				'numberposts'      => 1,
				'fields'           => 'ids',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => false,
			)
		);

		return empty( $courses ) ? 0 : absint( $courses[0] );
	}
}
