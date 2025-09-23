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

		$revenue_setting = new Setting();
		$stripe_setting  = new StripeSetting();

		return array(
			'steps' => array(
				'welcome'   => array(
					'step'      => 1,
					'completed' => $saved_data['steps']['welcome']['completed'] ?? false,
					'skipped'   => $saved_data['steps']['welcome']['skipped'] ?? false,
					'options'   => array(
						'site_creator'     => $saved_data['steps']['welcome']['options']['site_creator'] ?? '',
						'payments'         => masteriyo_string_to_bool( $saved_data['steps']['welcome']['options']['payments'] ?? false ),
						'certificates'     => masteriyo_string_to_bool( $saved_data['steps']['welcome']['options']['certificates'] ?? false ),
						'groups'           => masteriyo_string_to_bool( $saved_data['steps']['welcome']['options']['groups'] ?? false ),
						'multiple_courses' => masteriyo_string_to_bool( $saved_data['steps']['welcome']['options']['multiple_courses'] ?? false ),
						'revenue_sharing'  => masteriyo_string_to_bool( $saved_data['steps']['welcome']['options']['revenue_sharing'] ?? false ),
						'allow_usage'      => masteriyo_string_to_bool( $saved_data['steps']['welcome']['options']['allow_usage'] ?? true ),

					),
				),

				'setup'     => array(
					'step'      => 2,
					'completed' => $saved_data['steps']['setup']['completed'] ?? false,
					'skipped'   => $saved_data['steps']['setup']['skipped'] ?? false,
					'options'   => array(
						'payments'        => array(
							'offer_paid_courses'   => $saved_data['steps']['payment']['options']['offer_paid_courses'] ?? false,
							'currency'             => masteriyo_get_currency(),
							'offline_payment'      => masteriyo_get_setting( 'payments.offline.enable' ) ?? false,
							'paypal'               => masteriyo_get_setting( 'payments.paypal.enable' ) ?? false,
							'stripe'               => $stripe_setting->get( 'enable' ) ?? false,
							'paypal_email'         => masteriyo_get_setting( 'payments.paypal.email' ) ?? '',
							'live_publishable_key' => $stripe_setting->get( 'live_publishable_key' ) ?? '',
							'live_secret_key'      => $stripe_setting->get( 'live_secret_key' ) ?? '',
							'test_secret_key'      => $stripe_setting->get( 'test_secret_key' ) ?? '',
							'test_publishable_key' => $stripe_setting->get( 'test_publishable_key' ) ?? '',
							'sandbox'              => $stripe_setting->get( 'sandbox' ) ?? false,
							'stripe_user_id'       => $stripe_setting::get_stripe_user_id(),
						),
						'revenue_sharing' => array(
							'commission_rate' => array(
								'admin_rate'      => $revenue_setting->get( 'admin_rate' ) ?? 70,
								'instructor_rate' => $revenue_setting->get( 'instructor_rate' ) ?? 30,
							),
							'payment_method'  => $revenue_setting->get( 'withdraw.methods' ) ?? array(),
						),
					),
				),

				'templates' => array(
					'step'      => 3,
					'completed' => $saved_data['steps']['templates']['completed'] ?? false,
					'skipped'   => $saved_data['steps']['templates']['skipped'] ?? false,
					'options'   => array(
						'course_layout'                   => masteriyo_get_setting( 'course_archive.display.view_mode' ) ?? 'grid-view',
						'course_card_layout_style'        => masteriyo_get_setting( 'course_archive.display.template.layout' ) ?? 'default',
						'single_course_card_layout_style' => masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default',
					),
				),

				'finish'    => array(
					'step'      => 4,
					'completed' => $saved_data['steps']['finish']['completed'] ?? false,
					'skipped'   => $saved_data['steps']['finish']['skipped'] ?? false,
					'options'   => array(
						'install_sample_course' => $saved_data['steps']['course']['options']['install_sample_course'] ?? false,
					),
				),
			),
		);

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

			update_option( self::ONBOARDING_DATA_OPTION, $updated_data, false );

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

		update_option( self::ONBOARDING_DATA_OPTION, $updated_data, false );

		return rest_ensure_response( $updated_data );
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
	 * @since 1.18.0 [Free]
	 */
	protected function handle_getting_started_actions() {
		// Create pages.
		Activation::create_pages();
	}

	/**
	 * Handle step-specific actions after update.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param string $step Step name.
	 * @param array  $options Step options.
	 */
	protected function handle_step_specific_actions( $step, $options ) {
			$handlers = array(
				'welcome'   => array( $this, 'handle_welcome_type_step_actions' ),
				'setup'     => array( $this, 'handle_setup_actions' ),
				'templates' => array( $this, 'handle_templates_step_actions' ),
				'finish'    => array( $this, 'handle_finish_step_actions' ),
			);

			if ( isset( $handlers[ $step ] ) ) {
				call_user_func( $handlers[ $step ], $options );
			}
	}

	/**
	 * Handle business type step actions.
	 *
	 * @since 1.18.0 [Free]
	 *
	 * @param array $options Business type options.
	 */
	protected function handle_welcome_type_step_actions( $options ) {
		$this->handle_getting_started_actions();

		if ( ! empty( $options ) ) {
			$addons  = new Addons();
			$setting = new Setting();

			$certificates_enable  = masteriyo_string_to_bool( $options['certificates'] ?? true );
			$revenue_enable       = masteriyo_string_to_bool( $options['revenue_sharing'] ?? true );
			$group_courses_enable = masteriyo_string_to_bool( $options['groups'] ?? true );

			$setting->set( 'enable', $certificates_enable );
			if ( ! $certificates_enable ) {
				if ( $addons->is_active( 'certificate' ) ) {
					$addons->set_inactive( 'certificate' );
				}
			} elseif ( ! $addons->is_active( 'certificate' ) ) {
					$addons->set_active( 'certificate' );
			}

			if ( ! $revenue_enable ) {
				if ( $addons->is_active( 'revenue-sharing' ) ) {
					$addons->set_inactive( 'revenue-sharing' );
				}
			} elseif ( ! $addons->is_active( 'revenue-sharing' ) ) {
					$addons->set_active( 'revenue-sharing' );
			}

			if ( ! $group_courses_enable ) {
				if ( $addons->is_active( 'group-courses' ) ) {
					$addons->set_inactive( 'group-courses' );
				}
			} elseif ( ! $addons->is_active( 'group-courses' ) ) {
					$addons->set_active( 'group-courses' );
			}

			masteriyo_set_setting( 'advance.tracking.allow_usage', $options['allow_usage'] );
		}
	}

	/**
	 * Save setup-step settings (payments + revenue-sharing + stripe).
	 *
	 * @param array $options
	 */
	protected function handle_setup_actions( $options ) {
		$addons  = new Addons();
		$setting = new Setting();

		if ( ! $addons->is_active( 'revenue-sharing' ) ) {
			$addons->set_active( 'revenue-sharing' );
		}

		/* ---------------------------------------------------------------------
		 * Payments  → saved to the main settings table
		 * -------------------------------------------------------------------*/
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

		$rs = (array) ( $options['revenue_sharing'] ?? array() );

		$cr = $rs['commission_rate'] ?? array();
		if ( ! is_array( $cr ) ) {
			$cr = array();
		}

		$admin_rate      = absint( $cr['admin_rate'] ?? 70 );
		$instructor_rate = absint( $cr['instructor_rate'] ?? 30 );

		$pm_raw = $rs['payment_method'] ?? $rs['withdraw_methods'] ?? array();

		if ( is_string( $pm_raw ) ) {
			$withdraw_methods = array_filter(
				array_map( 'sanitize_text_field', array_map( 'trim', explode( ',', $pm_raw ) ) )
			);
		} elseif ( is_array( $pm_raw ) ) {
			$withdraw_methods = array_filter( array_map( 'sanitize_text_field', $pm_raw ) );
		} else {
			$withdraw_methods = array();
		}

		$setting->set( 'admin_rate', $admin_rate );
		$setting->set( 'instructor_rate', $instructor_rate );
		$setting->set( 'withdraw.methods', $withdraw_methods );

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

		$settings = array(
			'course_archive.display.view_mode'       => $options['course_layout'],
			'course_archive.display.template.layout' => $options['course_card_layout_style'],
			'single_course.display.template.layout'  => $options['single_course_card_layout_style'],
		);

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
		if ( $options['install_sample_course'] ?? false ) {
			$this->import_sample_courses(
				$options['course_option'] ?? 'lessonsOnly',
				$options['course_status'] ?? 'publish'
			);
		}
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
	 * Import sample courses.
	 *
	 * @since 1.18.0 [Free]
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

	private function apply_revenue_sharing_settings_from_options( $options ) {
		$enable = masteriyo_string_to_bool( $options['revenue_sharing'] ?? false );

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

		if ( isset( $options['commission_rate']['admin_rate'] ) ) {
			$setting->set( 'admin_rate', absint( $options['commission_rate']['admin_rate'] ) );
		}
		if ( isset( $options['commission_rate']['instructor_rate'] ) ) {
			$setting->set( 'instructor_rate', absint( $options['commission_rate']['instructor_rate'] ) );
		}
		if ( isset( $options['withdraw_methods'] ) ) {
			$setting->set( 'withdraw_methods', (array) $options['withdraw_methods'] );
		}
	}
}
