<?php
/**
 * Price Zones Controller Class.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\Addons\MultipleCurrency
 */

namespace Masteriyo\Addons\MultipleCurrency\Controllers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MultipleCurrency\Enums\PriceZoneStatus;
use Masteriyo\Addons\MultipleCurrency\Query\PriceZoneQuery;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Helper\Permission;
use Masteriyo\PostType\PostType;
use Masteriyo\RestApi\Controllers\Version1\PostsController;

/**
 * PriceZonesController class.
 */
class PriceZonesController extends PostsController {
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
	protected $rest_base = 'multiple-currency/pricing-zones';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = PostType::PRICE_ZONE;

	/**
	 * Object type.
	 *
	 * @var string
	 */
	protected $object_type = 'price-zone';

	/**
	 * Permission class.
	 *
	 * @since 1.11.0
	 *
	 * @var Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.11.0
	 *
	 * @param Permission $permission
	 */
	public function __construct( Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.11.0
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
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
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
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
							'type'        => 'boolean',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_items' ),
					'permission_callback' => array( $this, 'delete_items_permissions_check' ),
					'args'                => array(
						'ids'      => array(
							'required'    => true,
							'description' => __( 'Price zone IDs.', 'learning-management-system' ),
							'type'        => 'array',
						),
						'force'    => array(
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
							'type'        => 'boolean',
						),
						'children' => array(
							'default'     => false,
							'description' => __( 'Whether to delete the children(sections, lessons, quizzes and questions) under the course.', 'learning-management-system' ),
							'type'        => 'boolean',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/restore',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'restore_items' ),
					'permission_callback' => array( $this, 'delete_items_permissions_check' ),
					'args'                => array(
						'ids' => array(
							'required'    => true,
							'description' => __( 'Price zone Ids', 'learning-management-system' ),
							'type'        => 'array',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/restore',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'restore_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to get an item.
	 *
	 * @since 1.11.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( ! $this->permission->rest_check_comment_permissions( 'read' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you cannot list resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.11.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'create' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to create resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @since 1.11.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		$id         = absint( $request['id'] );
		$price_zone = masteriyo_get_price_zone( $id );

		if ( is_null( $price_zone ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'update', $id ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete item.
	 *
	 * @since 1.11.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		$id         = absint( $request['id'] );
		$price_zone = masteriyo_get_price_zone( $id );

		if ( is_null( $price_zone ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'delete', $id ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete multiple items.
	 *
	 * @since 1.11.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function delete_items_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'delete' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}
	/**
	 * Get the query params for collections of price zones.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => PriceZoneStatus::ANY,
			'description'       => __( 'Limit result set to price zones assigned a specific status.', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => wp_parse_args( PriceZoneStatus::all(), array( PriceZoneStatus::ANY ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get object.
	 *
	 * @since 1.11.0
	 *
	 * @param int|\Masteriyo\Addons\MultipleCurrency\Models\PriceZone|\WP_Post $object Object ID or Model or WP_Post object.
	 *
	 * @return false|\Masteriyo\Addons\MultipleCurrency\Models\PriceZone
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = is_a( $object, \WP_Post::class ) ? $object->ID : $object->get_id();
			}

			$price_zone = masteriyo_create_pricing_zone_object();
			$price_zone->set_id( $id );
			$price_zone_repo = masteriyo_create_pricing_zone_store();
			$price_zone_repo->read( $price_zone );
		} catch ( \Exception $e ) {
			return false;
		}

		return $price_zone;
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since 1.11.0
	 *
	 * @param  \Masteriyo\Database\Model $object  Model object.
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_pricing_zone_data( $object, $context );

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->object_type,
		 * refers to object type being prepared for the response.
		 *
		 * @since 1.11.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Masteriyo\Database\Model $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Process objects collection.
	 *
	 * @since 1.11.0
	 *
	 * @param array $objects Price Zones data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Price Zones query result data.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		return array(
			'data' => $objects,
			'meta' => array(
				'total'               => $query_results['total'],
				'pages'               => $query_results['pages'],
				'current_page'        => $query_args['paged'],
				'per_page'            => $query_args['posts_per_page'],
				'pricing_zones_count' => $this->pricing_zones_count(),
			),
		);
	}

	/**
		 * Get price zones count by status.
		 *
		 * @since 1.11.0
		 *
		 * @return Array
		 */
	protected function pricing_zones_count() {
		$post_count = parent::get_posts_count();

		return masteriyo_array_only( $post_count, array_merge( array( 'any' ), PriceZoneStatus::all() ) );
	}

	/**
	 * Get price zone data.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $price_zone Price zone instance.
	 * @param string $context Request context. Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_pricing_zone_data( $price_zone, $context = 'view' ) {
		$countries = $price_zone->get_countries( $context );

		$countries = array_filter(
			array_map(
				function( $country ) {

					if ( ! $country ) {
						return null;
					}

					return array(
						'value' => $country,
						'label' => masteriyo( 'countries' )->get_country_from_code( $country ),
					);
				},
				$countries
			)
		);

		$currency_code = $price_zone->get_currency( $context );
		$currency_name = masteriyo_get_currency_from_code( $currency_code );

		$currency = array(
			'value' => $currency_code,
			'label' => $currency_name,
		);

		$data = array(
			'id'            => $price_zone->get_id(),
			'title'         => wp_specialchars_decode( $price_zone->get_title( $context ) ),
			'countries'     => $countries,
			'exchange_rate' => $price_zone->get_exchange_rate( $context ),
			'currency'      => $currency,
			'status'        => $price_zone->get_status( $context ),
			'date_created'  => masteriyo_rest_prepare_date_response( $price_zone->get_date_created( $context ) ),
			'date_modified' => masteriyo_rest_prepare_date_response( $price_zone->get_date_modified( $context ) ),
		);

		/**
		 * Filter Price zone rest response data.
		 *
		 * @since 1.11.0
		 *
		 * @param array $data Price zone data.
		 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $price_zone Price zone object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param \Masteriyo\Addons\MultipleCurrency\Controllers\PriceZonesController $controller REST price zones controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $price_zone, $context, $this );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @since 1.11.0
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		$args['post_status'] = $request['status'];

		if ( ! empty( $request['author_id'] ) ) {
			$args['author'] = $request['author_id'];
		} elseif ( ! masteriyo_is_current_user_admin() ) {
			$args['author'] = get_current_user_id();
		}

		return $args;
	}

	/**
	 * Get the price zones'schema, conforming to JSON Schema.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'         => array(
					'description' => __( 'Price zone name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'exchange_rate' => array(
					'description' => __( 'Price zone exchange rate', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'currency'      => array(
					'description' => __( 'Price zone currency', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'menu_order'    => array(
					'description' => __( 'Menu order, used to custom sort price zones.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'countries'     => array(
					'description' => __( 'A list of the countries for the price zone.', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type' => 'string',
					),
				),
				'status'        => array(
					'description' => __( 'Price zone status (post status).', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => PriceZoneStatus::ACTIVE,
					'enum'        => PriceZoneStatus::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'author_id'     => array(
					'description' => __( 'Price zone author ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'  => array(
					'description' => __( "The date the price zone was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified' => array(
					'description' => __( "The date the price zone was last modified, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'meta_data'     => array(
					'description' => __( 'Meta data', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID', 'learning-management-system' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => __( 'Meta key', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value', 'learning-management-system' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare a single price zone for create or update.
	 *
	 * @since 1.11.0
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return \WP_Error|\Masteriyo\Database\Model
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;

		$price_zone = masteriyo_create_pricing_zone_object();

		if ( 0 !== $id ) {
			$price_zone->set_id( $id );
			$price_zone_repo = masteriyo_create_pricing_zone_store();
			$price_zone_repo->read( $price_zone );
		}

		$count_posts = wp_count_posts( PostType::PRICE_ZONE );

		$status = get_post_status( $id );

		if ( PriceZoneStatus::ACTIVE !== $status ) {
			if ( ! $id && isset( $count_posts->active ) && 2 <= $count_posts->active && isset( $request['status'] ) && PriceZoneStatus::ACTIVE === $request['status'] ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_upgrade_required", __( 'You cannot create more than two active pricing zones in the free version. Please upgrade to Pro.', 'learning-management-system' ), array( 'status' => 400 ) );
			}

			if ( $id && isset( $request['status'] ) && PriceZoneStatus::ACTIVE === $request['status'] && isset( $count_posts->active ) && 2 <= $count_posts->active ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_upgrade_required", __( 'You cannot have more than two active pricing zones in the free version. Please upgrade to Pro.', 'learning-management-system' ), array( 'status' => 400 ) );
			}
		}

		if ( ! $id ) {
			if ( ! isset( $request['title'] ) || empty( $request['title'] ) ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_missing_title", __( 'Pricing zone name is required.', 'learning-management-system' ), array( 'status' => 400 ) );
			}

			if ( ! isset( $request['exchange_rate'] ) || ! is_numeric( $request['exchange_rate'] ) || floatval( $request['exchange_rate'] ) <= 0 ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_invalid_exchange_rate", __( 'Pricing zone exchange rate must be a positive number.', 'learning-management-system' ), array( 'status' => 400 ) );
			}

			if ( ! isset( $request['currency'] ) || empty( $request['currency'] ) ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_missing_currency", __( 'Pricing zone currency is required.', 'learning-management-system' ), array( 'status' => 400 ) );
			}

			if ( ! isset( $request['countries'] ) || ! is_array( $request['countries'] ) || empty( $request['countries'] ) ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_invalid_countries", __( 'At least one country must be selected for the pricing zone.', 'learning-management-system' ), array( 'status' => 400 ) );
			}
		} else {
			if ( isset( $request['title'] ) && empty( $request['title'] ) ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_missing_title", __( 'Pricing zone name is required.', 'learning-management-system' ), array( 'status' => 400 ) );
			}

			if ( isset( $request['exchange_rate'] ) && ( ! is_numeric( $request['exchange_rate'] ) || floatval( $request['exchange_rate'] ) <= 0 ) ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_invalid_exchange_rate", __( 'Pricing zone exchange rate must be a positive number.', 'learning-management-system' ), array( 'status' => 400 ) );
			}

			if ( isset( $request['currency'] ) && empty( $request['currency'] ) ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_missing_currency", __( 'Pricing zone currency is required.', 'learning-management-system' ), array( 'status' => 400 ) );
			}

			if ( isset( $request['countries'] ) && ( ! is_array( $request['countries'] ) || empty( $request['countries'] ) ) ) {
				return new \WP_Error( "masteriyo_rest_{$this->post_type}_invalid_countries", __( 'At least one country must be selected for the pricing zone.', 'learning-management-system' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $request['currency'] ) && masteriyo_get_currency() === $request['currency'] ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_currency",
				__( 'The selected currency must be different from the base currency. Please choose a different currency for the pricing zone.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		// Post title.
		if ( isset( $request['title'] ) ) {
			$price_zone->set_title( sanitize_text_field( $request['title'] ) );
		}

		// Post author.
		if ( isset( $request['author_id'] ) ) {
			$price_zone->set_author_id( absint( $request['author_id'] ) );
		}

		// Post status.
		if ( isset( $request['status'] ) ) {
			$new_status = ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : PriceZoneStatus::ACTIVE;
			$price_zone->set_status( $new_status );
		}

		// Countries.
		if ( isset( $request['countries'] ) ) {

			$used_countries  = masteriyo_get_used_country_list_for_pricing_zone( array( $id ) );
			$input_countries = $request['countries'];

			$countries = array_intersect( $used_countries, $input_countries );

			if ( ! empty( $countries ) ) {
				$first_country = reset( $countries );
				return new \WP_Error(
					'masteriyo_rest_pricing_zone_invalid_countries',
					/* translators: %s: country name */
					sprintf( __( 'The country %s is already in another pricing zone.', 'learning-management-system' ), masteriyo( 'countries' )->get_country_from_code( $first_country ) ),
					array( 'status' => 400 )
				);
			}

			$price_zone->set_countries( $request['countries'] );
		}

		// Exchange rate.
		if ( isset( $request['exchange_rate'] ) ) {
			$price_zone->set_exchange_rate( $request['exchange_rate'] );
		}

		// Currency.
		if ( isset( $request['currency'] ) ) {

			$used_currencies = masteriyo_get_used_currency_list_for_pricing_zone( array( $id ) );
			$input_currency  = $request['currency'];

			if ( ! empty( $used_currencies ) ) {
				if ( in_array( strtoupper( $input_currency ), $used_currencies, true ) ) {
					return new \WP_Error(
						'masteriyo_rest_pricing_zone_invalid_countries',
						/* translators: %s: currency name */
						sprintf( __( 'The currency %s is already in another pricing zone.', 'learning-management-system' ), masteriyo_get_currency_from_code( strtoupper( $input_currency ) ) ),
						array( 'status' => 400 )
					);
				}
			}

			$price_zone->set_currency( $request['currency'] );
		}

		// Menu order.
		if ( isset( $request['menu_order'] ) ) {
			$price_zone->set_menu_order( absint( $request['menu_order'] ) );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$price_zone->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.11.0
		 *
		 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $price_zone Price zone object.
		 * @param \WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $price_zone, $request, $creating );
	}

	/**
	 * Delete multiple items.
	 *
	 * @since 1.11.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_items( $request ) {
		$objects         = array_map( 'masteriyo_get_price_zone', array_map( 'absint', $request['ids'] ) );
		$deleted_objects = array();
		$is_force_delete = isset( $request['force'] ) ? masteriyo_string_to_bool( $request['force'] ) : false;

		foreach ( $objects as $object ) {
			$data = $this->prepare_object_for_response( $object, $request );

			$object->delete( $is_force_delete, $request->get_params() );

			if ( 0 === $object->get_id() ) {
				$deleted_objects[] = $this->prepare_response_for_collection( $data );
			}
		}

		if ( empty( $deleted_objects ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_bulk_delete',
				/* translators: %s: post type */
				sprintf( __( 'The %s cannot be bulk deleted.', 'learning-management-system' ), $this->object_type ),
				array( 'status' => 500 )
			);
		}

		/**
		 * Fires after a multiple objects is deleted or trashed via the REST API.
		 *
		 * @since 1.11.0
		 *
		 * @param array $deleted_objects Objects collection which are deleted.
		 * @param array $objects Objects which are supposed to be deleted.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "masteriyo_rest_bulk_delete_{$this->object_type}_objects", $deleted_objects, $objects, $request );

		return rest_ensure_response( $deleted_objects );
	}

	/**
	 * Restore price zone.
	 *
	 * @since 1.11.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function restore_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new \WP_Error( "masteriyo_rest_{$this->object_type}_invalid_id", __( 'Invalid ID.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$object->restore();

		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		if ( $this->public ) {
			$response->link_header( 'alternate', $this->get_permalink( $object ), array( 'type' => 'text/html' ) );
		}

		return $response;
	}

	/**
	 * Restore price zones.
	 *
	 * @since 1.11.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function restore_items( $request ) {
		$restored_objects = array();

		$objects = $this->get_objects(
			array(
				'post_status'    => PostStatus::TRASH,
				'post_type'      => $this->post_type,
				'post__in'       => $request['ids'],
				'posts_per_page' => -1,
			)
		);

		$objects = isset( $objects['objects'] ) ? $objects['objects'] : array();

		foreach ( $objects as $object ) {
			if ( ! $object || 0 === $object->get_id() ) {
				continue;
			}

			$object->restore();

			$data               = $this->prepare_object_for_response( $object, $request );
			$restored_objects[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $restored_objects );
	}
}
