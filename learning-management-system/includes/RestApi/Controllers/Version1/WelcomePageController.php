<?php
/**
 * REST API WelcomePageController class.
 *
 * Handles the API requests for checking and creating the required pages for Masteriyo.
 *
 * @since 2.0.0 [Free]
 */
namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Activation;
use Masteriyo\Addons\Stripe\Setting as StripeSetting;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\AddonsFramework\Addons;
use Masteriyo\Setup\HomeGuide;
use Masteriyo\Setup\SampleContent;
use WP_REST_Request;
use WP_Error;
use WP_REST_Response;

class WelcomePageController extends RestController {

	protected $rest_base = 'welcome-page';
	protected $namespace = 'masteriyo/v1';

	/**
	 * Register the routes for the create pages API.
	 *
	 * Registers the routes for retrieving the current status of pages and creating missing pages.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);
	}


	/**
	 * Return publish courses count.
	 *
	 * @since 3.0.0
	 *
	 * @return integer
	 */
	public function get_course_count() {
		return masteriyo_array_get( (array) wp_count_posts( PostType::COURSE ), PostStatus::PUBLISH, 0 );
	}


	/**
	 * Get the status of the required pages.
	 *
	 * Checks if the required pages (Learn, Account, Checkout) are present and published.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function get_item( $request ) {
		$page_check_result      = check_required_pages();
		$stripe_setting         = new StripeSetting();
		$payment_data           = array(
			'offline_payment' => masteriyo_get_setting( 'payments.offline.enable' ) ?? false,
			'paypal'          => masteriyo_get_setting( 'payments.paypal.enable' ) ?? false,
			'paypal_email'    => masteriyo_get_setting( 'payments.paypal.email' ) ?? '',
			'stripe'          => $stripe_setting->get( 'enable' ) ?? false,
			'stripe_user_id'  => $stripe_setting->get( 'stripe_user_id' ) ?? false,
		);
		$show_staters_templates = get_option( 'show_starters_templates', 'yes' );
		$skip_payment_setup     = get_option( 'skip_payment_setup' );
		$course_count           = $this->get_course_count();
		$course_created         = false;
		if ( $course_count > 0 ) {
			$course_created = true;
		}
		return new WP_REST_Response(
			array(
				'missing_pages'           => $page_check_result,
				'payment_data'            => $payment_data,
				'show_starters_templates' => $show_staters_templates,
				'course_created'          => $course_created,
				'skip_payment_setup'      => $skip_payment_setup,
				'guide'                   => $this->get_guide(),
			),
			200
		);
	}

	/**
	 * The personalized setup guide for Masteriyo Home.
	 *
	 * @param bool $fresh Recompute the facts — required after an action mutated them.
	 * @return array
	 */
	protected function get_guide( $fresh = false ) {
		$facts    = HomeGuide::get_facts( $fresh );
		$complete = HomeGuide::is_complete( $facts );

		// The admin menu reads a recorded flag rather than paying twenty queries on
		// every wp-admin load, so whoever computes the real answer owes it an
		// update. Without this, hiding the guide leaves Home in the sidebar until
		// some later request happens to recompute it.
		HomeGuide::remember_completion( $complete );

		return array(
			'learner_access'           => $facts['learner_access'],
			'cards'                    => HomeGuide::build_cards( $facts ),
			'sample'                   => $facts['sample'],
			'storefront_url'           => $facts['storefront_url'],
			'commerce_enabled'         => $facts['commerce_enabled'],
			'complete'                 => $complete,
		);
	}


	/**
	 * Create the missing required pages if needed.
	 *
	 * Creates any missing required pages (Learn, Account, Checkout) if they are not found.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function create_item( $request ) {
		// Return before the page repair below: a dismissal must not generate pages.
		if ( isset( $request['learner_access'] ) ) {
			return $this->handle_learner_access( $request['learner_access'] );
		}

		// Presence is not consent: dismiss_guide => false is a plain read.
		if ( isset( $request['dismiss_guide'] ) ) {
			if ( masteriyo_string_to_bool( $request['dismiss_guide'] ) ) {
				HomeGuide::dismiss_guide();
			}

			return new WP_REST_Response( $this->get_state_response(), 200 );
		}

		if ( isset( $request['dismiss_card'] ) ) {
			if ( ! HomeGuide::dismiss( $request['dismiss_card'] ) ) {
				return new WP_Error(
					'masteriyo_rest_card_not_dismissible',
					__( 'That setup step cannot be dismissed on its own.', 'learning-management-system' ),
					array( 'status' => 400 )
				);
			}

			return new WP_REST_Response( $this->get_state_response(), 200 );
		}

		if ( isset( $request['sample_action'] ) ) {
			return $this->handle_sample_action( $request['sample_action'] );
		}

		// Rejected before any write — kept ahead of the page repair so the guarantee
		// does not lean on that block's own payments guard. sanitize_email() would
		// otherwise quietly store '' while the client reports the save as a success.
		if ( isset( $request['payments']['paypal_email'] ) ) {
			$paypal_email = trim( (string) $request['payments']['paypal_email'] );

			if ( '' !== $paypal_email && ! is_email( $paypal_email ) ) {
				return new WP_Error(
					'masteriyo_rest_invalid_paypal_email',
					__( 'Please enter a valid PayPal email address.', 'learning-management-system' ),
					array( 'status' => 400 )
				);
			}
		}

		$page_check_result = check_required_pages();
		if ( ! empty( $page_check_result ) && ! isset( $request['payments'] ) ) {
			// Courses is not one of the required pages this repairs — the archive is the
			// public listing and the page is offered separately (#665). Leaving it out
			// of the exclude list would quietly recreate it on any repair run.
			$exclude = array( 'courses', 'instructor-registration', 'instructors-list' );

			// Repairing the required pages must not hand a checkout page to a site that
			// does not sell — the check above already excludes it from "required" there.
			if ( ! masteriyo_site_needs_checkout() ) {
				$exclude[] = 'checkout';
			}

			Activation::create_pages( $exclude );
		}

		$stripe_setting = new StripeSetting();
		$addons         = new Addons();

		if ( isset( $request['payments'] ) && is_array( $request['payments'] ) ) {
			$this->process_payment_settings( $request['payments'], $addons, $stripe_setting );
		}
		if ( isset( $request['show_starters_templates'] ) ) {
			update_option(
				'show_starters_templates',
				masteriyo_bool_to_string( $request['show_starters_templates'] )
			);
		}

		if ( isset( $request['skip_payment_setup'] ) ) {
			update_option(
				'skip_payment_setup',
				masteriyo_bool_to_string( $request['skip_payment_setup'] )
			);
		}
		// The full state, not a bespoke subset: a partial payment_data (no
		// paypal_email, no stripe_user_id) replaces the client's cached copy
		// wholesale and reads as those fields having been wiped.
		return new WP_REST_Response( $this->get_state_response(), 200 );
	}

	/**
	 * The full Home state, as the GET route answers it.
	 *
	 * Only POST actions answer with this, and every one of them just mutated the
	 * state it describes — so the guide is always recomputed past the memo.
	 *
	 * @return array
	 */
	protected function get_state_response() {
		$stripe_setting = new StripeSetting();

		return array(
			'missing_pages'           => check_required_pages(),
			'payment_data'            => array(
				'offline_payment' => masteriyo_get_setting( 'payments.offline.enable' ) ?? false,
				'paypal'          => masteriyo_get_setting( 'payments.paypal.enable' ) ?? false,
				'paypal_email'    => masteriyo_get_setting( 'payments.paypal.email' ) ?? '',
				'stripe'          => $stripe_setting->get( 'enable' ) ?? false,
				'stripe_user_id'  => $stripe_setting->get( 'stripe_user_id' ) ?? false,
			),
			'show_starters_templates' => get_option( 'show_starters_templates', 'yes' ),
			'course_created'          => $this->get_course_count() > 0,
			'skip_payment_setup'      => get_option( 'skip_payment_setup' ),
			'guide'                   => $this->get_guide( true ),
		);
	}

	/**
	 * Record the answer to "how will learners get access", given from Home.
	 *
	 * Home writes to the same onboarding option the wizard does, so the two can
	 * never disagree, and re-uses the wizard's whitelist — an unknown value stores
	 * nothing rather than routing the site down a path it did not choose.
	 *
	 * @param string $access The submitted answer.
	 * @return WP_REST_Response|WP_Error
	 */
	protected function handle_learner_access( $access ) {
		$access = sanitize_key( $access );

		if ( ! in_array( $access, array( 'sell', 'enroll', 'both', 'explore' ), true ) ) {
			return new WP_Error(
				'masteriyo_rest_invalid_learner_access',
				__( 'Invalid learner access value.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$data = get_option( OnboardingController::ONBOARDING_DATA_OPTION, array() );
		$data = is_array( $data ) ? $data : array();

		$data['steps']                       = isset( $data['steps'] ) && is_array( $data['steps'] ) ? $data['steps'] : array();
		$data['steps']['welcome']            = isset( $data['steps']['welcome'] ) && is_array( $data['steps']['welcome'] ) ? $data['steps']['welcome'] : array();
		$data['steps']['welcome']['options'] = isset( $data['steps']['welcome']['options'] ) && is_array( $data['steps']['welcome']['options'] ) ? $data['steps']['welcome']['options'] : array();
		$data['steps']['welcome']['options']['learner_access'] = $access;
		// Kept in step with the wizard, which derives the same flag on every save.
		$data['steps']['welcome']['options']['payments'] = in_array( $access, array( 'sell', 'both' ), true );

		update_option( OnboardingController::ONBOARDING_DATA_OPTION, masteriyo_redact_onboarding_secrets( $data ), false );

		// Install skips this page, so answering here is what creates it.
		if ( in_array( $access, array( 'sell', 'both' ), true ) ) {
			Activation::create_pages(
				array( 'courses', 'account', 'learn', 'instructor-registration', 'instructors-list' )
			);
		}

		return new WP_REST_Response( $this->get_state_response(), 200 );
	}

	/**
	 * Re-queue the bundled sample content after a failed or undone import.
	 *
	 * @param string $action Either 'retry' or 'reimport'.
	 * @return WP_REST_Response|WP_Error
	 */
	protected function handle_sample_action( $action ) {
		if ( ! in_array( $action, array( 'retry', 'reimport' ), true ) ) {
			return new WP_Error(
				'masteriyo_rest_invalid_sample_action',
				__( 'Invalid sample content action.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		// Re-derived, not trusted: otherwise repeated POSTs seed the tour again and
		// again, and this endpoint is open to managers.
		$expected = 'retry' === $action ? 'failed' : 'removed';
		$state    = masteriyo_array_get( HomeGuide::get_sample_state(), 'state', 'none' );

		if ( $expected !== $state ) {
			return new WP_Error(
				'masteriyo_rest_sample_action_not_applicable',
				__( 'The sample course does not need importing right now.', 'learning-management-system' ),
				array( 'status' => 409 )
			);
		}

		// Queue first: the markers are what render the action this error asks for.
		// Non-unique: Action Scheduler's uniqueness is hook-level, so any other
		// sample batch in flight would refuse this one and the user would see a 500.
		if ( ! SampleContent::enqueue( array( 'product-tour' ), false ) ) {
			return new WP_Error(
				'masteriyo_rest_sample_enqueue_failed',
				__( 'Could not schedule the sample course import. Please try again.', 'learning-management-system' ),
				array( 'status' => 500 )
			);
		}

		// import() skips recorded slugs, and the job reads these on a later request.
		delete_option( SampleContent::RETRIES );

		if ( 'reimport' === $action ) {
			SampleContent::forget( array( 'product-tour' ) );
		}

		return new WP_REST_Response( $this->get_state_response(), 200 );
	}

	private function process_payment_settings( $payments, $addons, $stripe_setting ) {
		$settings = array();

		if ( array_key_exists( 'offline_payment', $payments ) ) {
			$settings['payments.offline.enable'] = masteriyo_string_to_bool( $payments['offline_payment'] );
		}

		if ( array_key_exists( 'paypal', $payments ) ) {
			$settings['payments.paypal.enable'] = masteriyo_string_to_bool( $payments['paypal'] );
		}
		if ( isset( $payments['paypal_email'] ) ) {
			$settings['payments.paypal.email'] = sanitize_email( $payments['paypal_email'] );
		}

		if ( isset( $payments['stripe'] ) ) {
			$stripe_enabled                     = masteriyo_string_to_bool( $payments['stripe'] );
			$settings['payments.stripe.enable'] = $stripe_enabled;

			// Stripe reads masteriyo_stripe_settings, not the global settings option, so
			// writing only the latter left it looking on and behaving off. Same call the
			// wizard's setup step makes.
			$stripe_setting::set( 'enable', $stripe_enabled );
		}

		// Before the writes: saving one resolves the shared gateway registry, which
		// would then be cached for the request without Stripe in it.
		if ( isset( $settings['payments.stripe.enable'] ) && $settings['payments.stripe.enable'] && ! $addons->is_active( 'stripe' ) ) {
			$addons->set_active( 'stripe' );
		}

		foreach ( $settings as $key => $value ) {
			masteriyo_set_setting( $key, $value );
		}
	}




	/**
	 * Check if a given request has access to read/delete item(s).
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function permissions_check( $request ) {
		if ( masteriyo_is_current_user_admin() ) {
			return true;
		}

		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' );
	}

	/**
	 * Permissions check for getting page details.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function get_items_permissions_check( $request ) {
		return $this->permissions_check( $request );
	}

	/**
	 * Permissions check for creating pages.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ) {
		return $this->permissions_check( $request );
	}
}
