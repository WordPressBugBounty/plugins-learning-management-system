<?php
/**
 * REST API Onboarding Controller.
 *
 * Manages onboarding steps via REST API endpoints for the Masteriyo plugin.
 *
 * @category API
 * @package  Masteriyo\RestApi
 * @since    1.18.0
 */

namespace Masteriyo\RestApi\Controllers\Version1;

use Masteriyo\Activation;
use Masteriyo\Addons\RevenueSharing\Setting;
use Masteriyo\Addons\Stripe\Setting as StripeSetting;
use Masteriyo\Constants;
use Masteriyo\Importer\CourseImporter;
use Masteriyo\Pro\Addons;
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
	 * @since 1.18.0
	 * @var string
	 */
	const ONBOARDING_DATA_OPTION = 'masteriyo_onboarding_data';

	/**
	 * Valid onboarding steps.
	 *
	 * @since 1.18.0
	 * @var string[]
	 */
	const VALID_STEPS = array(
		'business_type',
		'marketplace',
		'course',
		'payment',
	);

	/**
	 * Register REST routes for onboarding.
	 *
	 * @since 1.18.0
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
	 * @since 1.18.0
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
	 * @since 1.18.0
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
	 * @since 1.18.0
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
	 * @since 1.18.0
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
	 * @since 1.18.0
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
	 * @since 1.18.0
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
	 * @since 1.18.0
	 * @return array
	 */
	protected function get_default_onboarding_data() {
		$saved_data = get_option( self::ONBOARDING_DATA_OPTION, array() );

		$revenue_setting = new Setting();
		$stripe_setting  = new StripeSetting();

			return array(
				'started' => $saved_data['started'] ?? false,
				'steps'   => array(
					'business_type' => array(
						'step'      => 1,
						'completed' => $saved_data['steps']['business_type']['completed'] ?? false,
						'skipped'   => $saved_data['steps']['business_type']['skipped'] ?? false,
						'options'   => array(
							'business_type' => $saved_data['steps']['business_type']['options']['business_type'] ?? 'individual',
						),
					),
					'marketplace'   => array(
						'step'      => 2,
						'completed' => $saved_data['steps']['marketplace']['completed'] ?? false,
						'skipped'   => $saved_data['steps']['marketplace']['skipped'] ?? false,
						'options'   => array(
							'revenue_sharing'  => $revenue_setting->get( 'enable' ) ?? true,
							'commission_rate'  => array(
								'admin_rate'      => $revenue_setting->get( 'admin_rate' ) ?? 70,
								'instructor_rate' => $revenue_setting->get( 'instructor_rate' ) ?? 30,
							),
							'withdraw_methods' => $revenue_setting->get( 'withdraw_methods' ) ?? array(),
						),
					),
					'course'        => array(
						'step'      => 3,
						'completed' => $saved_data['steps']['course']['completed'] ?? false,
						'skipped'   => $saved_data['steps']['course']['skipped'] ?? false,
						'options'   => array(
							'courses_plan_to_create' => $saved_data['steps']['course']['options']['courses_plan_to_create'] ?? 'single',
							'view_mode'              => masteriyo_get_setting( 'course_archive.display.view_mode' ) ?? 'grid-view',
							'per_row'                => masteriyo_get_setting( 'course_archive.display.per_row' ) ?? 3,
							'per_page'               => masteriyo_get_setting( 'course_archive.display.per_page' ) ?? 12,
							'enable_search'          => masteriyo_get_setting( 'course_archive.display.enable_search' ) ?? true,
							'install_sample_course'  => $saved_data['steps']['course']['options']['install_sample_course'] ?? false,
							'course_option'          => $saved_data['steps']['course']['options']['course_option'] ?? 'lessonsOnly',
							'course_status'          => $saved_data['steps']['course']['options']['course_status'] ?? 'publish',
						),
					),
					'payment'       => array(
						'step'      => 4,
						'completed' => $saved_data['steps']['payment']['completed'] ?? false,
						'skipped'   => $saved_data['steps']['payment']['skipped'] ?? false,
						'options'   => array(
							'offer_paid_courses'   => $saved_data['steps']['payment']['options']['offer_paid_courses'] ?? false,
							'currency'             => masteriyo_get_currency(),
							'offline_payment'      => masteriyo_get_setting( 'payments.offline.enable' ) ?? false,
							'paypal'               => masteriyo_get_setting( 'payments.paypal.enable' ) ?? false,
							'stripe'               => $stripe_setting->get( 'enable' ) ?? false,
							'paypal_email'         => masteriyo_get_setting( 'payments.paypal.email' ) ?? '',
							'live_publishable_key' => $stripe_setting->get( 'live_publishable_key' ) ?? '',
							'live_secret_key'      => $stripe_setting->get( 'live_secret_key' ) ?? '',
						),
					),
				),
			);
	}

	/**
	 * Get merged onboarding data with defaults.
	 *
	 * @since 1.18.0
	 *
	 * @return array merged onboarding data.
	 */
	protected function get_onboarding_data() {
		return $this->get_default_onboarding_data();
	}

	/**
	 * Retrieve all onboarding data.
	 *
	 * @since 1.18.0
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
	 * @since 1.18.0
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

			update_option( self::ONBOARDING_DATA_OPTION, $updated_data, false );

			if ( $updated_data['started'] ) {
				$this->handle_getting_started_actions();
				/**
				 * Action fired when onboarding is started.
				 *
				 * @since 1.18.0
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

		update_option( self::ONBOARDING_DATA_OPTION, $updated_data, false );

		return rest_ensure_response( $updated_data );
	}

	/**
	 * Retrieve a single onboarding step.
	 *
	 * @since 1.18.0
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
	 * @since 1.18.0
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

		$current_data = get_option( self::ONBOARDING_DATA_OPTION, array() );
		$updated_data = array_merge(
			$current_data,
			array(
				'steps' => array_merge( $current_data['steps'] ?? array(), array( $step => $validated_data ) ),
			)
		);

		// Mark onboarding as started.
		$updated_data['started'] = true;

		update_option( self::ONBOARDING_DATA_OPTION, $updated_data, false );
		$this->handle_step_specific_actions( $step, $validated_data['options'] ?? array() );

		return rest_ensure_response( $this->get_onboarding_data() );
	}

	/**
	 * Handle actions after user starts onboarding.
	 *
	 * @since 1.18.0
	 */
	protected function handle_getting_started_actions() {
		// Create pages.
		Activation::create_pages();
	}

	/**
	 * Handle step-specific actions after update.
	 *
	 * @since 1.18.0
	 *
	 * @param string $step Step name.
	 * @param array  $options Step options.
	 */
	protected function handle_step_specific_actions( $step, $options ) {
			$handlers = array(
				'business_type' => array( $this, 'handle_business_type_step_actions' ),
				'marketplace'   => array( $this, 'handle_marketplace_step_actions' ),
				'course'        => array( $this, 'handle_course_step_actions' ),
				'payment'       => array( $this, 'handle_payment_step_actions' ),
			);

			if ( isset( $handlers[ $step ] ) ) {
				call_user_func( $handlers[ $step ], $options );
			}
	}

	/**
	 * Handle business type step actions.
	 *
	 * @since 1.18.0
	 *
	 * @param array $options Business type options.
	 */
	protected function handle_business_type_step_actions( $options ) {
		if ( isset( $options['business_type'] ) ) {
			$business_type = sanitize_text_field( $options['business_type'] );
			masteriyo_set_setting( 'general.business_type', $business_type );

			// If business_type is 'marketplace', enable revenue-sharing addon.
			if ( 'marketplace' === $business_type ) {
				$addons = new Addons();
				if ( ! $addons->is_active( 'revenue-sharing' ) ) {
					$addons->set_active( 'revenue-sharing' );
				}
			}
		}
	}

	/**
	 * Handle marketplace step actions.
	 *
	 * @since 1.18.0
	 *
	 * @param array $options Marketplace options.
	 */
	protected function handle_marketplace_step_actions( $options ) {
		if ( ! empty( $options ) ) {
			$enable = masteriyo_string_to_bool( $options['revenue_sharing'] ?? true );

			// If addon not activated, activate it.
			$addons  = new Addons();
			$setting = new Setting();

			$setting->set( 'enable', $enable );
			if ( ! $enable ) {
				if ( $addons->is_active( 'revenue-sharing' ) ) {
					$addons->set_inactive( 'revenue-sharing' );
				}
				return;
			}

			if ( ! $addons->is_active( 'revenue-sharing' ) ) {
				$addons->set_active( 'revenue-sharing' );
			}

			$settings = array(
				'admin_rate'       => absint( $options['commission_rate']['admin_rate'] ?? 70 ),
				'instructor_rate'  => absint( $options['commission_rate']['instructor_rate'] ?? 30 ),
				'withdraw.methods' => $options['withdraw_methods'] ?? array(),
			);

			foreach ( $settings as $key => $value ) {
				$setting->set( $key, $value );
			}
		}
	}

	/**
	 * Handle course step actions.
	 *
	 * @since 1.18.0
	 *
	 * @param array $options Course options.
	 */
	protected function handle_course_step_actions( $options ) {
		$courses_plan_to_create = $options['courses_plan_to_create'] ?? 'single';
		$view_mode              = 'single' === $courses_plan_to_create ? 'list-view' : $options['view_mode'] ?? 'grid-view';

		$settings = array(
			'course_archive.display.view_mode' => $view_mode,
		);

		if ( 'single' !== $courses_plan_to_create ) {
			$settings['course_archive.display.per_row']       = absint( $options['per_row'] ?? 3 );
			$settings['course_archive.display.per_page']      = absint( $options['per_page'] ?? 12 );
			$settings['course_archive.display.enable_search'] = masteriyo_string_to_bool( $options['enable_search'] ?? true );
		}

		foreach ( $settings as $key => $value ) {
			masteriyo_set_setting( $key, $value );
		}

		if ( $options['install_sample_course'] ?? false ) {
			$this->import_sample_courses(
				$options['course_option'] ?? 'lessonsOnly',
				$options['course_status'] ?? 'publish'
			);
		}
	}

	/**
	 * Handle payment step actions.
	 *
	 * @since 1.18.0
	 *
	 * @param array $options Payment options.
	 */
	protected function handle_payment_step_actions( $options ) {
		$settings = array();

		if ( isset( $options['currency'] ) ) {
			$settings['payments.currency.currency'] = sanitize_text_field( $options['currency'] );
		}

		if ( isset( $options['offline_payment'] ) ) {
			$settings['payments.offline.enable'] = masteriyo_string_to_bool( $options['offline_payment'] );
		}

		if ( isset( $options['paypal'] ) ) {
			$settings['payments.paypal.enable'] = masteriyo_string_to_bool( $options['paypal'] );

			if ( $options['paypal'] && isset( $options['paypal_email'] ) ) {
				$settings['payments.paypal.email'] = sanitize_email( $options['paypal_email'] );
			}
		}

		foreach ( $settings as $key => $value ) {
			masteriyo_set_setting( $key, $value );
		}

		if ( isset( $options['stripe'] ) ) {
			$enable = masteriyo_string_to_bool( $options['stripe'] );

			$addons         = new Addons();
			$stripe_setting = new StripeSetting();

			$stripe_setting::set( 'enable', $enable );

			if ( ! $enable ) {
				if ( $addons->is_active( 'stripe' ) ) {
					$addons->set_inactive( 'stripe' );
				}
				return;
			}

			if ( ! $addons->is_active( 'stripe' ) ) {
				$addons->set_active( 'stripe' );
			}

			if ( isset( $options['live_publishable_key'] ) ) {
				$stripe_setting::set( 'live_publishable_key', sanitize_text_field( $options['live_publishable_key'] ) );
			}

			if ( isset( $options['live_secret_key'] ) ) {
				$stripe_setting::set( 'live_secret_key', sanitize_text_field( $options['live_secret_key'] ) );
			}

			if ( ! empty( $options['live_publishable_key'] ?? '' ) && ! empty( $options['live_secret_key'] ?? '' ) ) {
				$stripe_setting::set( 'sandbox', false );
			}
		}
	}

	/**
	 * Merge saved onboarding data with defaults.
	 *
	 * @since 1.18.0
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
	 * @since 1.18.0
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
	 * @since 1.18.0
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
	 * @since 1.18.0
	 *
	 * @param string $step Step name.
	 * @param array $input_options Input options.
	 * @param array $default_options Default options.
	 * @return array Validated options.
	 */
	protected function validate_step_options( $step, $input_options, $default_options ) {
		$validated_options = array();

		foreach ( $default_options as $key => $default_value ) {
			if ( isset( $input_options[ $key ] ) ) {
				$validated_options[ $key ] = $this->sanitize_option_value( $key, $input_options[ $key ], $default_value );
			}
		}

		return wp_parse_args( $validated_options, $default_options );
	}

	/**
	 * Sanitize option value based on its type.
	 *
	 * @since 1.18.0
	 *
	 * @param string $key Option key.
	 * @param mixed $value Option value.
	 * @param mixed $default_value Default value.
	 * @return mixed Sanitized value.
	 */
	protected function sanitize_option_value( $key, $value, $default_value ) {
		if ( is_bool( $default_value ) ) {
			return masteriyo_string_to_bool( $value );
		} elseif ( is_int( $default_value ) ) {
			return absint( $value );
		} elseif ( is_array( $default_value ) ) {
			return array_map( 'sanitize_text_field', (array) $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Validate a property.
	 *
	 * @since 1.18.0
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
	 * Import sample courses.
	 *
	 * @since 1.18.0
	 *
	 * @param string $course_option Course option (lessonsOnly or lessonsAndQuizzes).
	 * @param string $status        Course status (publish or draft).
	 * @return WP_Error|bool
	 */
	protected function import_sample_courses( $course_option, $status ) {
		$file = Constants::get( 'MASTERIYO_PLUGIN_DIR' ) . '/sample-data/courses.json';

		if ( ! file_exists( $file ) ) {
				return new WP_Error(
					'masteriyo_rest_import_sample_courses_file_not_found',
					__( 'Sample courses file not found.', 'learning-management-system' ),
					array( 'status' => 404 )
				);
		}

		try {
				$importer = new CourseImporter( $status );
				$importer->import( $file, 'sample-courses', 'lessonsOnly' === $course_option );
				return true;
		} catch ( \Exception $e ) {
				return new WP_Error(
					'masteriyo_rest_import_sample_courses_error',
					$e->getMessage(),
					array( 'status' => 500 )
				);
		}
	}

	/**
	 * Sanitize request parameters recursively.
	 *
	 * @since 1.18.0
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
