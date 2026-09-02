<?php
/**
 * REST API Onboarding Controller.
 *
 * Manages onboarding steps via REST API endpoints for the Masteriyo plugin.
 *
 * @category API
 * @package  Masteriyo\RestApi
 * @since    1.18.0 [Free]
 */

namespace Masteriyo\RestApi\Controllers\Version1;

use Masteriyo\Activation;
use Masteriyo\Addons\Stripe\Setting as StripeSetting;
use Masteriyo\Setup\SampleContent;
use Masteriyo\AddonsFramework\Addons;
use WP_REST_Request;
use WP_Error;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Onboarding Controller Class.
 *
 * Handles CRUD operations for onboarding data.
 *
 * @package Masteriyo\RestApi
 */
class OnboardingController extends RestController {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'onboarding';

	/**
	 * Onboarding data option name.
	 *
	 * @since 1.18.0 [Free]
	 * @var string
	 */
	const ONBOARDING_DATA_OPTION = 'masteriyo_onboarding_data';

	/**
	 * Valid onboarding steps.
	 *
	 * @since 1.18.0 [Free]
	 * @var string[]
	 */
	const VALID_STEPS = array(
		'welcome',
		'setup',
		'templates',
		'finish',
	);

	/**
	 * Valid learner-access answers.
	 *
	 * @var string[]
	 */
	const LEARNER_ACCESS_VALUES = array( 'sell', 'enroll', 'both', 'explore' );

	/**
	 * Register REST routes for onboarding.
	 *
	 * @since 1.18.0 [Free]
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<step>[a-z_]+)',
			array(
				'args' => array(
					'step' => array(
						'description'       => __( 'Unique identifier for the onboarding step.', 'learning-management-system' ),
						'type'              => 'string',
						'validate_callback' => array( $this, 'validate_step_parameter' ),
						'sanitize_callback' => 'sanitize_key',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
			)
		);
	}

	/**
	 * Validate the step parameter.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param string          $value   The step name.
	 * @param WP_REST_Request $request The request object.
	 * @param string          $param   The parameter name.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_step_parameter( $value, $request, $param ) {
		if ( ! in_array( $value, self::VALID_STEPS, true ) ) {
			return new WP_Error(
				'rest_invalid_param',
				sprintf(
					/* translators: %1$s: Parameter name, %2$s: List of valid values */
					__( '%1$s is not one of %2$s.', 'learning-management-system' ),
					$param,
					implode( ', ', self::VALID_STEPS )
				),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to read/delete item(s).
	 *
	 * @since 1.18.0 [Free]
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
	 * Check if a given request has access to read items.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
			return $this->permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
			return $this->permissions_check( $request );
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
			return $this->permissions_check( $request );
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		return $this->permissions_check( $request );
	}

	/**
	 * Get default onboarding data structure.
	 *
	 * @since 1.18.0 [Free]
	 * @return array
	 */
	protected function get_default_onboarding_data() {
		$saved_data = get_option( self::ONBOARDING_DATA_OPTION, array() );

		$saved_currency      = $saved_data['steps']['setup']['options']['payments']['currency'] ?? '';
		$configured_currency = masteriyo_array_get( get_option( 'masteriyo_settings', array() ), 'payments.currency.currency', '' );

		// A stored USD with the setup step never answered (in either wizard
		// generation) is a hydration artifact — pre-3.3.3 full saves persisted the
		// USD default — not a choice. Reads resolve to USD either way, so
		// re-inferring changes only the suggestion shown.
		$setup_answered = ! empty( $saved_data['steps']['setup']['completed'] )
			|| ! empty( $saved_data['steps']['setup']['skipped'] )
			|| ! empty( $saved_data['steps']['payment'] );

		if ( 'USD' === $configured_currency && ! $setup_answered ) {
			$configured_currency = '';
		}

		$stripe_setting = new StripeSetting();
		$addons         = new Addons();

		return array(
			'started' => $saved_data['started'] ?? false,
			'steps'   => array(
				'welcome'   => array(
					'step'      => 1,
					'completed' => $saved_data['steps']['welcome']['completed'] ?? false,
					'skipped'   => $saved_data['steps']['welcome']['skipped'] ?? false,
					'options'   => array(
						'learner_access' => $saved_data['steps']['welcome']['options']['learner_access'] ?? '',
						// Derived from learner_access on every welcome save. A site that never
						// answered reads '' and must not be assumed to want payments.
						'payments'       => masteriyo_string_to_bool( $saved_data['steps']['welcome']['options']['payments'] ?? false ),
						'allow_usage'    => masteriyo_string_to_bool( $saved_data['steps']['welcome']['options']['allow_usage'] ?? true ),
					),
				),

				'setup'     => array(
					'step'      => 2,
					'completed' => $saved_data['steps']['setup']['completed'] ?? false,
					'skipped'   => $saved_data['steps']['setup']['skipped'] ?? false,
					'options'   => array(
						'payments' => array(
							'offer_paid_courses' => $saved_data['steps']['setup']['options']['payments']['offer_paid_courses'] ?? $saved_data['steps']['payment']['options']['offer_paid_courses'] ?? false,
							// masteriyo_get_setting() merges hardcoded defaults over the stored
							// option and answers 'USD' on a fresh install, so "already configured"
							// has to be read from the raw option — which install-time writes keep
							// sparse via masteriyo_set_raw_setting() for exactly this reason.
							'currency'           => $saved_currency ? $saved_currency : ( $configured_currency ? $configured_currency : masteriyo_infer_currency_from_domain() ),
							'offline_payment'    => $saved_data['steps']['setup']['options']['payments']['offline_payment'] ?? masteriyo_get_setting( 'payments.offline.enable' ) ?? false,
							'paypal'             => $saved_data['steps']['setup']['options']['payments']['paypal'] ?? masteriyo_get_setting( 'payments.paypal.enable' ) ?? false,
							'stripe'             => $addons->is_active( 'stripe' ),
							'sandbox'            => $saved_data['steps']['setup']['options']['payments']['sandbox'] ?? $stripe_setting->get( 'sandbox' ) ?? false,
							// The four Stripe API keys are gone for good — nothing renders them.
							// These two do get rendered (the connect/disconnect state and the
							// PayPal email field), so they stay in the response and are stripped
							// on the way into the option instead. Both re-derive from settings,
							// and handle_setup_actions() only ever writes them back unchanged.
							// Settings only, like stripe_user_id: a stale onboarding copy (for
							// example a backup restored after the one-time scrub) would be
							// written back over the merchant's current email on the next save.
							'paypal_email'       => masteriyo_get_setting( 'payments.paypal.email' ) ?? '',
							'stripe_user_id'     => $stripe_setting::get_stripe_user_id() ?? '',
						),
					),
				),

				'templates' => array(
					'step'      => 3,
					'completed' => $saved_data['steps']['templates']['completed'] ?? false,
					'skipped'   => $saved_data['steps']['templates']['skipped'] ?? false,
					'options'   => array(
						'course_layout'                   => $saved_data['steps']['templates']['options']['course_layout'] ?? masteriyo_get_setting( 'course_archive.display.view_mode' ) ?? 'grid-view',
						'course_card_layout_style'        => $this->map_settings_to_onboarding_values( 'course_card', $saved_data['steps']['templates']['options']['course_card_layout_style'] ?? masteriyo_get_setting( 'course_archive.display.template.layout' ) ?? 'default' ),
						'single_course_card_layout_style' => $this->map_settings_to_onboarding_values( 'single_course', $saved_data['steps']['templates']['options']['single_course_card_layout_style'] ?? masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default' ),
						'is_fresh_site'                   => $this->is_site_fresh(),
					),
				),

				'finish'    => array(
					'step'      => 4,
					'completed' => $saved_data['steps']['finish']['completed'] ?? false,
					'skipped'   => $saved_data['steps']['finish']['skipped'] ?? false,
					'options'   => array(
						'install_sample_course' => $saved_data['steps']['finish']['options']['install_sample_course'] ?? $saved_data['steps']['course']['options']['install_sample_course'] ?? true,
					),
				),
			),
		);

	}

	/**
	 * Get the IDs of pages created by the Masteriyo plugin.
	 *
	 * Settings, not top-level options: nothing writes those, so this returned an
	 * empty list and excluded nothing. Harmless until pages started being created at
	 * install — now the plugin's own pages make every fresh site look used.
	 *
	 * @return int[] Array of page IDs created by Masteriyo.
	 */
	protected function get_masteriyo_page_ids() {
		$setting_keys = array(
			'general.pages.courses_page_id',
			'general.pages.account_page_id',
			'general.pages.checkout_page_id',
			'general.pages.learn_page_id',
			'general.pages.instructor_registration_page_id',
			'general.pages.instructors_list_page_id',
		);

		$page_ids = array();

		foreach ( $setting_keys as $key ) {
			$id = absint( masteriyo_get_setting( $key ) );

			if ( $id > 0 ) {
				$page_ids[] = $id;
			}
		}

		return $page_ids;
	}


	/**
	 * Determines if the current WordPress site is considered "fresh" based on several criteria.
	 *
	 * The method calculates a score by checking:
	 * - The 'fresh_site' option value.
	 * - The number of published pages and posts.
	 * - The number of media attachments.
	 * - The number of customized theme modifications.
	 *
	 * Returns true if the calculated score is less than or equal to 2, indicating a fresh site.
	 *
	 * @return bool True if the site is fresh, false otherwise.
	 */
	public function is_site_fresh() {
		$fresh_site_option = (int) get_option( 'fresh_site' );

		$masteriyo_page_ids = $this->get_masteriyo_page_ids();
		$pages_query        = new \WP_Query(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'post__not_in'           => $masteriyo_page_ids,
			)
		);
		$pages              = $pages_query->found_posts;

		$posts = wp_count_posts( 'post' )->publish ?? 0;
		$media = wp_count_posts( 'attachment' )->inherit ?? 0;
		$mods  = array_filter( get_theme_mods() );

		$is_fresh = 1 === $fresh_site_option
			&& $pages <= 1
			&& $posts <= 1
			&& $media <= 2
			&& count( $mods ) <= 2;

		return (bool) $is_fresh;
	}

	/**
	 * Map settings values to onboarding form values (reverse mapping).
	 *
	 * @since 2.0.1 [Free]
	 *
	 * @param string $type Either 'course_card' or 'single_course'.
	 * @param string $value The settings value to map.
	 * @return string The mapped onboarding value.
	 */
	private function map_settings_to_onboarding_values( $type, $value ) {
		if ( 'course_card' === $type ) {
			$reverse_map = array(
				'default' => 'default',
				'layout1' => 'modern',
				'layout2' => 'overlay',
			);
		} else {
			$reverse_map = array(
				'default' => 'default',
				'layout1' => 'modern',
				'minimal' => 'minimal',
			);
		}

		return $reverse_map[ $value ] ?? $value;
	}




	/**
	 * Get merged onboarding data with defaults.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @return array merged onboarding data.
	 */
	protected function get_onboarding_data() {
		return $this->get_default_onboarding_data();
	}

	/**
	 * Retrieve all onboarding data.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		return rest_ensure_response( $this->get_onboarding_data() );
	}

	/**
	 * Create new onboarding data.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$params = $this->sanitize_request_params( $request->get_params() );

		// Special case for marking onboarding as started
		if ( isset( $params['started'] ) ) {
			$current_data = get_option( self::ONBOARDING_DATA_OPTION, array() );
			$updated_data = array_merge( $current_data, array( 'started' => masteriyo_string_to_bool( $params['started'] ) ) );

			update_option( self::ONBOARDING_DATA_OPTION, masteriyo_redact_onboarding_secrets( $updated_data ), false );

			if ( $updated_data['started'] ) {
				$this->handle_getting_started_actions();
				/**
				 * Action fired when onboarding is started.
				 *
				 * @since 1.18.0 [Free]
				 */
				do_action( 'masteriyo_onboarding_started' );
			}

			return rest_ensure_response( $this->get_onboarding_data() );
		}

		// Handle other create operations normally
		$default_data   = $this->get_default_onboarding_data();
		$validated_data = $this->validate_onboarding_data( $params, $default_data );

		if ( is_wp_error( $validated_data ) ) {
			return $validated_data;
		}

		$current_data = get_option( self::ONBOARDING_DATA_OPTION, array() );
		$current_data = ! is_array( $current_data ) ? array() : $current_data;
		$updated_data = array_merge( $current_data, $validated_data );

		update_option( self::ONBOARDING_DATA_OPTION, masteriyo_redact_onboarding_secrets( $updated_data ), false );

		// The in-memory merge can still carry legacy stored secrets on a not-yet-migrated
		// site; echoing it would leak them once even though storage was scrubbed.
		return rest_ensure_response( $this->get_onboarding_data() );
	}

	/**
	 * Retrieve a single onboarding step.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$step = sanitize_key( $request['step'] );
		$data = $this->get_onboarding_data();

		if ( ! isset( $data['steps'][ $step ] ) ) {
			return new WP_Error(
				'masteriyo_rest_onboarding_step_not_found',
				__( 'Invalid onboarding step.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$response_data = array(
			$step => $data['steps'][ $step ],
		);

		$response_data['started'] = $data['started'];

		return rest_ensure_response( $response_data );
	}

	/**
	 * Update an existing onboarding step.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$step   = sanitize_key( $request['step'] );
		$params = $this->sanitize_request_params( $request->get_params() );

		unset( $params['step'] );

		$default_data = $this->get_default_onboarding_data();

		if ( ! isset( $default_data['steps'][ $step ] ) ) {
			return new WP_Error(
				'masteriyo_rest_onboarding_step_not_found',
				__( 'Invalid onboarding step.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$validated_data = $this->validate_step_data( $step, $params, $default_data );

		if ( is_wp_error( $validated_data ) ) {
			return $validated_data;
		}

		if ( 'welcome' === $step && isset( $validated_data['options']['learner_access'] ) ) {
			$validated_data['options']['payments'] = in_array(
				$validated_data['options']['learner_access'],
				array( 'sell', 'both' ),
				true
			);
		}

		$current_data = get_option( self::ONBOARDING_DATA_OPTION, array() );
		$current_step = $current_data['steps'][ $step ] ?? array();

		// The wizard enforces the learner-access answer in React only; this endpoint
		// is the invariant's real home. Without it an authorized request can mark
		// Welcome done with no answer (or one that sanitized to ''), and every
		// downstream setup decision silently falls back to defaults.
		if ( 'welcome' === $step ) {
			$effective_access = $validated_data['options']['learner_access']
				?? $current_step['options']['learner_access']
				?? '';

			if ( ! in_array( $effective_access, self::LEARNER_ACCESS_VALUES, true ) ) {
				return new WP_Error(
					'masteriyo_rest_onboarding_learner_access_required',
					__( 'Choose how learners will get access before submitting this step.', 'learning-management-system' ),
					array( 'status' => 400 )
				);
			}
		}

		$merged_step = array_merge( $current_step, $validated_data );

		// A skip payload carries no options; without this the merge above erases the answer.
		if ( ! isset( $validated_data['options'] ) && isset( $current_step['options'] ) ) {
			$merged_step['options'] = $current_step['options'];
		}

		$updated_data = array_merge(
			$current_data,
			array(
				'steps' => array_merge( $current_data['steps'] ?? array(), array( $step => $merged_step ) ),
			)
		);

		// Mark onboarding as started.
		$updated_data['started'] = true;

		update_option( self::ONBOARDING_DATA_OPTION, masteriyo_redact_onboarding_secrets( $updated_data ), false );

		// A skip counts too: it requires and persists the answer, and its side effects
		// already ran. Guarding on completed alone would run the addon block a second
		// time when a previously-skipped user later completes, overriding any addon
		// choice they made by hand in between.
		$first_welcome_completion = empty( $current_data['steps']['welcome']['completed'] ) && empty( $current_data['steps']['welcome']['skipped'] );

		// Finish is the exception to "skips run their actions": its action installs
		// content, a skip payload carries no options, and the handler's absent-option
		// default is consent — so a skipped Finish would import the example courses
		// the user just declined to ask for.
		if ( 'finish' !== $step || empty( $validated_data['skipped'] ) ) {
			$this->handle_step_specific_actions( $step, $validated_data['options'] ?? array(), $first_welcome_completion );
		}

		return rest_ensure_response( $this->get_onboarding_data() );
	}

	/**
	 * Handle actions after user starts onboarding.
	 *
	 * @since 1.18.0 [Free]
	 */
	protected function handle_getting_started_actions() {
		// Checkout is excluded here and created by the sell/both branch below, or when
		// the first paid course is saved. Creating it on every wizard start would put a
		// checkout page on sites that answered "we enrol them ourselves". Courses is
		// excluded outright: the archive already lists courses, and a wizard run is too
		// early to know whether the site wants a page of its own (#665).
		Activation::create_pages( array( 'courses', 'instructor-registration', 'instructors-list', 'checkout' ) );
	}

	/**
	 * Handle step-specific actions after update.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param string $step Step name.
	 * @param array  $options Step options.
	 * @param bool   $first_welcome_completion Whether the welcome step is being completed for the first time.
	 */
	protected function handle_step_specific_actions( $step, $options, $first_welcome_completion = true ) {
			$handlers = array(
				'welcome'   => array( $this, 'handle_welcome_type_step_actions' ),
				'setup'     => array( $this, 'handle_setup_actions' ),
				'templates' => array( $this, 'handle_templates_step_actions' ),
				'finish'    => array( $this, 'handle_finish_step_actions' ),
			);

			if ( isset( $handlers[ $step ] ) ) {
				if ( 'welcome' === $step ) {
					call_user_func( $handlers[ $step ], $options, $first_welcome_completion );
				} else {
					call_user_func( $handlers[ $step ], $options );
				}
			}
	}

	/**
	 * Handle business type step actions.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param array $options Business type options.
	 * @param bool  $first_welcome_completion Whether the welcome step is being completed for the first time.
	 */
	protected function handle_welcome_type_step_actions( $options, $first_welcome_completion = true ) {
		$this->handle_getting_started_actions();
		if ( ! empty( $options ) ) {
			$addons = new Addons();

			// Every addon mutation stays inside the first-completion guard, deliberately:
			// re-saving this step (changing the answer, skipping) must never override an
			// addon choice the user made by hand afterwards — that is #569's bug class.
			// The wizard asks about none of these addons, so it must not deactivate them
			// either: an existing site that enabled one by hand would lose it the
			// moment the welcome step completes.
			if ( $first_welcome_completion && ! $addons->is_active( 'certificate' ) ) {
				global $wpdb;
				$has_course = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 1", \Masteriyo\PostType\PostType::COURSE ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				// The wizard never asks about certificates, so activating is a new-site
				// default, not a user choice. A site that already has courses may have
				// switched the addon off on purpose; it keeps its state.
				if ( ! $has_course ) {
					$addons->set_active( 'certificate' );
				}
			}

			// Raw write: a full save here would hydrate the USD default between the
			// welcome save and the payments step, blocking currency inference. Raw
			// writes skip the model's sanitizers, so coerce the bool here.
			masteriyo_set_raw_setting( 'advance.tracking.allow_usage', masteriyo_string_to_bool( $options['allow_usage'] ) );

			$learner_access = $options['learner_access'] ?? '';

			if ( in_array( $learner_access, array( 'sell', 'both' ), true ) ) {
				Activation::create_pages(
					array( 'courses', 'account', 'learn', 'instructor-registration', 'instructors-list' )
				);
			}

			// The wizard is how an admin turns commerce on before ever seeing the
			// Settings → Payments toggle, so a sell/both answer writes the setting
			// directly; 'explore' and an unanswered wizard leave the tri-state at ''.
			// Seeded exactly once, tracked by its own marker: re-submitting this step
			// must never override a choice made elsewhere afterwards — and a bare
			// "is the setting still ''" test can't tell an untouched site from an
			// explicit later "decide automatically" pick, which is also ''.
			if ( ! get_option( 'masteriyo_payments_enabled_seeded' ) ) {
				if ( in_array( $learner_access, array( 'sell', 'both' ), true ) ) {
					masteriyo_set_setting( 'payments.enabled', 'yes' );
				} elseif ( 'enroll' === $learner_access ) {
					// A site with real orders never gets 'no' from a wizard answer: the wizard is
					// reachable on grandfathered sites via the finish-setup notice, and one walk
					// through it must not hide the Orders history behind an answer about the
					// future. Settings → Payments remains the explicit way to turn it off.
					global $wpdb;
					$has_order = $wpdb->get_var(
						$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 1", \Masteriyo\PostType\PostType::ORDER ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					);

					if ( ! $has_order ) {
						masteriyo_set_setting( 'payments.enabled', 'no' );
					}
				}

				update_option( 'masteriyo_payments_enabled_seeded', 1 );
			}
		}
	}

	/**
	 * Save setup-step settings (payments + stripe).
	 *
	 * @param array $options
	 */
	protected function handle_setup_actions( $options ) {
		$addons = new Addons();

		$p        = (array) ( $options['payments'] ?? array() );
		$settings = array();

		if ( isset( $p['currency'] ) ) {
			$settings['payments.currency.currency'] = sanitize_text_field( $p['currency'] );
		}

		if ( array_key_exists( 'offline_payment', $p ) ) {
			$settings['payments.offline.enable'] = masteriyo_string_to_bool( $p['offline_payment'] );
		}

		if ( array_key_exists( 'paypal', $p ) ) {
			$paypal_enabled                     = masteriyo_string_to_bool( $p['paypal'] );
			$settings['payments.paypal.enable'] = $paypal_enabled;

			if ( $paypal_enabled && ! empty( $p['paypal_email'] ) ) {
				$settings['payments.paypal.email'] = sanitize_email( $p['paypal_email'] );
			}
		}

		foreach ( $settings as $key => $value ) {
			masteriyo_set_setting( $key, $value );
		}

		if ( isset( $p['stripe'] ) && masteriyo_string_to_bool( $p['stripe'] ) ) {
			$stripe_setting = new StripeSetting();
			$stripe_setting::set( 'enable', true );

			if ( ! $addons->is_active( 'stripe' ) ) {
				$addons->set_active( 'stripe' );
			}

			foreach ( array(
				'live_publishable_key',
				'live_secret_key',
				'test_publishable_key',
				'test_secret_key',
				'stripe_user_id',
			) as $k ) {
				if ( isset( $p[ $k ] ) ) {
					$stripe_setting::set( $k, sanitize_text_field( $p[ $k ] ) );
				}
			}

			$stripe_setting::set( 'sandbox', (bool) ( $p['sandbox'] ?? false ) );

		} elseif ( isset( $p['stripe'] ) ) {
			$stripe_setting = new StripeSetting();
			$stripe_setting::set( 'enable', false );

			if ( $addons->is_active( 'stripe' ) ) {
				$addons->set_inactive( 'stripe' );
			}
		}
	}


	/**
	 * Handle course step actions.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param array $options Course options.
	 */
	protected function handle_templates_step_actions( $options ) {

		if ( empty( $options ) ) {
			return;
		}

		$settings = array();

		if ( isset( $options['course_layout'] ) && ! empty( $options['course_layout'] ) ) {
			$settings['course_archive.display.view_mode'] = $options['course_layout'];
		}

		if ( isset( $options['course_card_layout_style'] ) && ! empty( $options['course_card_layout_style'] ) ) {
			$course_card_layout_map                             = array(
				'default' => 'default',
				'modern'  => 'layout1',
				'overlay' => 'layout2',
			);
			$mapped_value                                       = $course_card_layout_map[ $options['course_card_layout_style'] ] ?? $options['course_card_layout_style'];
			$settings['course_archive.display.template.layout'] = $mapped_value;
		}

		if ( isset( $options['single_course_card_layout_style'] ) && ! empty( $options['single_course_card_layout_style'] ) ) {
			$single_course_layout_map                          = array(
				'default' => 'default',
				'modern'  => 'layout1',
				'minimal' => 'minimal',
			);
			$mapped_value                                      = $single_course_layout_map[ $options['single_course_card_layout_style'] ] ?? $options['single_course_card_layout_style'];
			$settings['single_course.display.template.layout'] = $mapped_value;
		}

		foreach ( $settings as $key => $value ) {
			masteriyo_set_setting( $key, $value );
		}

	}

	/**
	 * Handle finish step actions.
	 *
	 * @since 2.0.0 [Free]
	 * @param array $options Course options.
	 */
	protected function handle_finish_step_actions( $options ) {
		// The install-time product-tour seed is async; on hosts where the queue
		// never runs (broken loopbacks are common on local sites) it stays pending
		// forever. Riding it along here — only while the site still qualifies for
		// seeding — makes this request the catch-up. import() skips per slug, so on
		// a healthy site this adds nothing.
		$slugs = SampleContent::should_seed() ? array( 'product-tour' ) : array();

		if ( masteriyo_string_to_bool( $options['install_sample_course'] ?? true ) ) {
			$slugs[] = 'subject-course';
			$slugs[] = 'cohort-course';
		}

		if ( empty( $slugs ) ) {
			return;
		}

		// Synchronous, not enqueued: the wizard is showing its finalizing overlay,
		// the user lands on the Courses page next, and an Action Scheduler unique
		// enqueue would be refused outright while the install-time job is still
		// pending (uniqueness is hook-scoped, not args-scoped — reproduced: the
		// pending product-tour job made the extras enqueue return false, and the
		// two example courses were never imported). The queue remains only as
		// import()'s own fallback when another import already holds the lock.
		SampleContent::import( $slugs );
	}


	/**
	 * Merge saved onboarding data with defaults.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param array $default_data Default onboarding data.
	 * @param array $saved_data   Saved onboarding data.
	 * @return array Merged data.
	 */
	protected function merge_onboarding_data( $default_data, $saved_data ) {
		if ( empty( $saved_data ) ) {
			return $default_data;
		}

		$merged_data = $default_data;

		if ( isset( $saved_data['started'] ) ) {
			$merged_data['started'] = masteriyo_string_to_bool( $saved_data['started'] );
		}

		if ( isset( $saved_data['steps'] ) ) {
			foreach ( $saved_data['steps'] as $key => $saved_step ) {
				if ( isset( $default_data['steps'][ $key ] ) ) {
					$merged_data['steps'][ $key ] = array_merge( $default_data['steps'][ $key ], $saved_step );
				}
			}
		}

		return $merged_data;
	}

	/**
	 * Validate onboarding data against defaults.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param array $input_data  Input data.
	 * @param array $default_data Default data.
	 * @return array|WP_Error
	 */
	protected function validate_onboarding_data( $input_data, $default_data ) {
		$validated_data = array();

		if ( isset( $input_data['started'] ) ) {
			$validated_data['started'] = masteriyo_string_to_bool( $input_data['started'] );
		}

		$skipped = masteriyo_string_to_bool( $input_data['skipped'] ?? false );
		if ( $skipped ) {
			$validated_data['skipped'] = true;

			return $validated_data;
		}

		if ( isset( $input_data['steps'] ) ) {
			$validated_steps = array();
			foreach ( $input_data['steps'] as $step_key => $step_data ) {
				$step_key = sanitize_key( $step_key );
				if ( ! isset( $default_data['steps'][ $step_key ] ) ) {
					continue;
				}

				$validated_step = $this->validate_step_data( $step_key, $step_data, $default_data );

				if ( is_wp_error( $validated_step ) ) {
					return $validated_step;
				}

				if ( ! empty( $validated_step ) ) {
					$validated_steps[ $step_key ] = $validated_step;
				}
			}
			if ( ! empty( $validated_steps ) ) {
				$validated_data['steps'] = $validated_steps;
			}
		}

		return $validated_data;
	}

	/**
	 * Validate step data.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param string $step       Step name.
	 * @param array  $input_data Input data.
	 * @param array  $default_data Default data.
	 * @return array|WP_Error
	 */
	protected function validate_step_data( $step, $input_data, $default_data ) {
		if ( ! isset( $default_data['steps'][ $step ] ) ) {
			return new WP_Error(
				'masteriyo_rest_invalid_onboarding_step',
				__( 'Invalid onboarding step.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$validated_step = array();

		foreach ( $default_data['steps'][ $step ] as $property => $default_value ) {
			if ( isset( $input_data[ $property ] ) ) {
				$validated_step[ $property ] = $this->validate_property( $property, $input_data[ $property ], $default_data['steps'][ $step ] );
			}
		}

		// Handle options
		if ( isset( $input_data['options'] ) ) {
			$validated_step['options'] = $this->validate_step_options( $step, $input_data['options'], $default_data['steps'][ $step ]['options'] ?? array() );
		}

		return $validated_step;
	}

	/**
	 * Validate step options.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param string $step Step name.
	 * @param array $input_options Input options.
	 * @param array $default_options Default options.
	 * @return array Validated options.
	 */
	protected function validate_step_options( $step, $input_options, $default_options ) {
		$merged    = wp_parse_args( $input_options, $default_options );
		$sanitized = array();
		foreach ( $merged as $key => $value ) {
			$def               = $default_options[ $key ] ?? $value;
			$sanitized[ $key ] = $this->sanitize_option_value( $key, $value, $def );
		}

		// Retired questions. wp_parse_args() above merges whatever a client sends, and
		// there is no schema to reject unknown keys, so drop them here instead of
		// storing answers nothing reads.
		if ( 'welcome' === $step ) {
			unset(
				$sanitized['site_creator'],
				$sanitized['certificates'],
				$sanitized['multiple_courses'],
				$sanitized['groups'],
				$sanitized['revenue_sharing']
			);
		}

		if ( 'setup' === $step ) {
			unset( $sanitized['revenue_sharing'] );
		}

		return $sanitized;
	}

	/**
	 * Sanitize option value based on its type.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param string $key Option key.
	 * @param mixed $value Option value.
	 * @param mixed $default_value Default value.
	 * @return mixed Sanitized value.
	 */
	protected function sanitize_option_value( $key, $value, $default_value ) {
		if ( 'learner_access' === $key ) {
			$value = sanitize_key( $value );
			return in_array( $value, self::LEARNER_ACCESS_VALUES, true ) ? $value : '';
		}

		if ( is_bool( $default_value ) ) {
			return masteriyo_string_to_bool( $value );
		} elseif ( is_int( $default_value ) ) {
			return absint( $value );
		} elseif ( is_array( $default_value ) ) {
			$is_list = array_values( $default_value ) === $default_value;

			if ( $is_list ) {
				return array_map( 'sanitize_text_field', (array) $value );
			}

			$sanitized = array();
			$value     = (array) $value;

			foreach ( $default_value as $k => $def ) {
				if ( array_key_exists( $k, $value ) ) {
					$sanitized[ $k ] = $this->sanitize_option_value( $k, $value[ $k ], $def );
				} else {
					$sanitized[ $k ] = $def;
				}
			}

			return $sanitized;
		}

		return sanitize_text_field( $value );
	}


	/**
	 * Validate a property.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param string $property     Property name.
	 * @param mixed  $value        Property value.
	 * @param array  $default_step Default step data.
	 * @return mixed
	 */
	protected function validate_property( $property, $value, $default_step ) {
		switch ( $property ) {
			case 'step':
				return absint( $value );
			case 'completed':
			case 'skipped':
				return masteriyo_string_to_bool( $value );
			default:
				return sanitize_text_field( $value );
		}
	}


	/**
	 * Sanitize request parameters recursively.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param array $params Request parameters.
	 * @return array Sanitized parameters.
	 */
	protected function sanitize_request_params( $params ) {
		$sanitized = array();

		foreach ( $params as $key => $value ) {
			$key               = sanitize_key( $key );
			$sanitized[ $key ] = is_array( $value )
			? $this->sanitize_request_params( $value )
			: sanitize_text_field( $value );
		}

		return $sanitized;
	}
}
