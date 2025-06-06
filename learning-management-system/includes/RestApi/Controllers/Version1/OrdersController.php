<?php
/**
 * Abstract class controller.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\RestApi\Controllers\Version1;
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Helper\Permission;
use Masteriyo\Exceptions\RestException;
use Masteriyo\ModelException;
use Masteriyo\Models\Order\Order;
use Masteriyo\Roles;

/**
 * OrdersController class.
 */
class OrdersController extends PostsController {

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
	protected $rest_base = 'orders';

	/**
	 * Object type.
	 *
	 * @var string
	 */
	protected $object_type = 'order';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'mto-order';

	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = false;

	/**
	 * Permission class.
	 *
	 * @since 1.0.0
	 *
	 * @var Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Permission $permission Permission object.
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;

		add_action( 'masteriyo_after_trash_order', array( $this, 'update_enrollments_status_for_orders_deletion' ), 10, 2 );
		add_action( 'masteriyo_after_restore_order', array( $this, 'update_enrollments_status_for_orders_restoration' ), 10, 2 );
	}

	/**
	 * Register routes.
	 *
	 * @since 1.0.0
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_items' ),
					'permission_callback' => array( $this, 'delete_items_permissions_check' ),
					'args'                => array(
						'ids'   => array(
							'required'    => true,
							'description' => __( 'Order IDs.', 'learning-management-system' ),
							'type'        => 'array',
						),
						'force' => array(
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
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
							'description' => __( 'Order Ids', 'learning-management-system' ),
							'type'        => 'array',
						),
					),
				),
			)
		);

		/**
		 * Register REST API route for CSV bulk enrollments.
		 *
		 * @since 1.18.1
		 * */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/csv-bulk-enrollments',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import_items' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
			)
		);
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'description'       => __( 'Limit result set to orders assigned a specific status.', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => array_merge( array_keys( masteriyo_get_order_statuses() ), array( 'any', 'trash' ) ),
			'default'           => 'any',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['customer'] = array(
			'description'       => __( 'Limit result set to orders assigned a specific customer.', 'learning-management-system' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['course'] = array(
			'description'       => __( 'Limit result set to orders assigned a specific course.', 'learning-management-system' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['created_via'] = array(
			'description'       => __( 'Limit result set to orders that were created through a specific method.', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get object.
	 *
	 * @param  int|WP_Post $id Object ID.
	 * @return object Model object or WP_Error object.
	 */
	protected function get_object( $id ) {
		try {
			$id    = $id instanceof \WP_Post ? $id->ID : $id;
			$id    = $id instanceof Order ? $id->get_id() : $id;
			$order = masteriyo( 'order' );
			$order->set_id( $id );
			$order_repo = masteriyo( 'order.store' );
			$order_repo->read( $order );
		} catch ( \Exception $e ) {
			return false;
		}

		return $order;
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since  1.0.0
	 *
	 * @param  Masteriyo\Database\Model $object  Model object.
	 * @param  WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_order_data( $object, $context );

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
		 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param array $objects Orders data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Orders query result data.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		return array(
			'data' => $objects,
			'meta' => array(
				'total'        => $query_results['total'],
				'pages'        => $query_results['pages'],
				'current_page' => $query_args['paged'],
				'per_page'     => $query_args['posts_per_page'],
				'orders_count' => $this->get_orders_count(),
			),
		);
	}

	/**
	 * Get order data.
	 *
	 * @param \Masteriyo\Models\Order\Order  $order Order instance.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_order_data( $order, $context = 'view' ) {
		$customer      = masteriyo_get_user( $order->get_customer_id( $context ) );
		$customer_info = null;

		if ( ! is_wp_error( $customer ) ) {
			$customer_info = array(
				'id'           => $customer->get_id(),
				'display_name' => $customer->get_display_name(),
				'avatar_url'   => $customer->get_avatar_url(),
				'email'        => $customer->get_email(),
			);
		}

		$attachment_id = $order->get_attachment_id( $context );
		$attachment    = array();
		if ( $attachment_id ) {
			$file_size = absint( filesize( get_attached_file( $attachment_id ) ) );

				$attachment[] = array(
					'id'                  => $attachment_id,
					'url'                 => wp_get_attachment_url( $attachment_id ),
					'file_size'           => absint( filesize( get_attached_file( $attachment_id ) ) ),
					'formatted_file_size' => size_format( $file_size ),
					'created_at'          => masteriyo_rest_prepare_date_response( $order->get_date_created( $context ) ),
					'mime_type'           => get_post_mime_type( $attachment_id ),
				);
		}

		$items = $order->get_items();

		$data = array(
			'id'                   => $order->get_id(),
			'permalink'            => $order->get_permalink(),
			'status'               => $order->get_status( $context ),
			'total'                => $order->get_total( $context ),
			'formatted_total'      => $order->get_rest_formatted_total( $context ),
			'currency'             => $order->get_currency( $context ),
			'currency_symbol'      => html_entity_decode( masteriyo_get_currency_symbol( $order->get_currency( $context ) ) ),
			'expiry_date'          => $order->get_expiry_date( $context ),
			'date_created'         => masteriyo_rest_prepare_date_response( $order->get_date_created( $context ) ),
			'date_modified'        => masteriyo_rest_prepare_date_response( $order->get_date_modified( $context ) ),
			'customer_id'          => $order->get_customer_id( $context ),
			'customer'             => $customer_info,
			'payment_method'       => $order->get_payment_method( $context ),
			'payment_method_title' => $order->get_payment_method_title( $context ),
			'transaction_id'       => $order->get_transaction_id( $context ),
			'date_paid'            => $order->get_date_paid( $context ),
			'date_completed'       => $order->get_date_completed( $context ),
			'created_via'          => $order->get_created_via( $context ),
			'customer_ip_address'  => $order->get_customer_ip_address( $context ),
			'customer_user_agent'  => $order->get_customer_user_agent( $context ),
			'version'              => $order->get_version( $context ),
			'order_key'            => $order->get_order_key( $context ),
			'customer_note'        => $order->get_customer_note( $context ),
			'attachment'           => $attachment,
			'cart_hash'            => $order->get_cart_hash( $context ),
			'billing'              => array(
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'company'    => $order->get_billing_company(),
				'address_1'  => $order->get_billing_address_1(),
				'address_2'  => $order->get_billing_address_2(),
				'city'       => $order->get_billing_city(),
				'postcode'   => $order->get_billing_postcode(),
				'country'    => $order->get_billing_country(),
				'state'      => $order->get_billing_state(),
				'email'      => $order->get_billing_email(),
				'phone'      => $order->get_billing_phone(),
			),
			'course_lines'         => $this->get_order_item_course( $items, $context ),
			'download_url'         => masteriyo_generate_order_download_url( $order->get_id() ),
		);

		/**
		 * Filter Order rest response data.
		 *
		 * @since 1.4.10
		 *
		 * @param array $data Order data.
		 * @param Masteriyo\Models\Order $order Order object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\OrdersController $controller REST Orders controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $order, $context, $this );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		// Set order status.
		$args['post_status'] = $request['status'];

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			if ( ! empty( $request['customer'] ) ) {
				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => '_customer_id',
						'value'   => absint( $request['customer'] ),
						'compare' => '=',
					),
				);
			}
		} else {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => '_customer_id',
					'value'   => get_current_user_id(),
					'compare' => '=',
				),
			);
		}

		if ( ! empty( $request['created_via'] ) ) {
			if ( empty( $args['meta_query'] ) ) {
				$args['meta_query'] = array(
					'relation' => 'AND',
				);
			}

			$args['meta_query'][] = array(
				'key'     => '_created_via',
				'value'   => sanitize_text_field( $request['created_via'] ),
				'compare' => '=',
			);
		}

		return $args;
	}

	/**
	 * Get the orders' schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$gateway_methods = array( 'paypal', 'offline' );
		try {
			$gateway_methods = masteriyo( 'payment-gateways' )->get_payment_gateway_names();
		} catch ( \Exception $e ) {
			error_log( 'Cannot initialize payment gateways' );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'id'                   => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'permalink'            => array(
					'description' => __( 'Order URL', 'learning-management-system' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'         => array(
					'description' => __( "The date the Order was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'     => array(
					'description' => __( 'The date the Order was created, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified'        => array(
					'description' => __( "The date the Order was last modified, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'    => array(
					'description' => __( 'The date the Order was last modified, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'               => array(
					'description' => __( 'Order status', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => OrderStatus::PENDING,
					'enum'        => OrderStatus::all(),
					'context'     => array( 'view', 'edit' ),
				),
				'total'                => array(
					'description' => __( 'Total amount of the order.', 'learning-management-system' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'currency'             => array(
					'description' => __( 'Currency', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => masteriyo_get_currency_codes(),
				),
				'expiry_date'          => array(
					'description' => __( 'Expiry date of this order.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_id'          => array(
					'description' => __( 'Customer ID', 'learning-management-system' ),
					'type'        => array( 'string', 'number' ),
					'context'     => array( 'view', 'edit' ),
				),
				'payment_method'       => array(
					'description' => __( 'Payment method.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => $gateway_methods,
				),
				'payment_method_title' => array(
					'description' => __( 'Payment method title.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'Paypal' ),
				),
				'transaction_id'       => array(
					'description' => __( 'Transaction ID', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_paid'            => array(
					'description' => __( 'Date of payment.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_completed'       => array(
					'description' => __( 'Date of order completion.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'created_via'          => array(
					'description' => __( 'Method of order creation.', 'learning-management-system' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'customer_ip_address'  => array(
					'description' => __( 'Customer IP address', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_user_agent'  => array(
					'description' => __( 'Customer user agent', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'version'              => array(
					'description' => __( 'Version of Masteriyo which last updated the order.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'order_key'            => array(
					'description' => __( 'Order key', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_note'        => array(
					'description' => __( 'Note left by customer during checkout.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'payment_sheet_id'     => array(
					'description' => __( 'Payment invoice number thats saved during offline payment checkout.', 'learning-management-system' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
				),
				'cart_hash'            => array(
					'description' => __( 'MD5 hash of cart items to ensure orders are not modified.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'billing'              => array(
					'description' => __( 'Order billing details.', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'first_name' => array(
							'description' => __( 'Order billing first name.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'  => array(
							'description' => __( 'Order billing last name.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'    => array(
							'description' => __( 'Order billing company name.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'  => array(
							'description' => __( 'Order billing address 1.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'  => array(
							'description' => __( 'Order billing address 1.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'  => array(
							'description' => __( 'Order billing address 2.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'       => array(
							'description' => __( 'Order billing city.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'   => array(
							'description' => __( 'Order billing post code.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'    => array(
							'description' => __( 'Order billing country.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'      => array(
							'description' => __( 'Order billing state.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'email'      => array(
							'description' => __( 'Order billing email address.', 'learning-management-system' ),
							'type'        => 'email',
							'context'     => array( 'view', 'edit' ),
						),
						'phone'      => array(
							'description' => __( 'Order billing phone number.', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'set_paid'             => array(
					'description' => __( 'Set whether the payment is done.', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'course_lines'         => array(
					'description' => __( 'Course items data', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'      => 'object',
						'id'        => array(
							'description' => __( 'Item ID', 'learning-management-system' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'course_id' => array(
							'description' => __( 'Order billing last name.', 'learning-management-system' ),
							'type'        => 'integer',
							'required'    => true,
							'context'     => array( 'view', 'edit' ),
						),
						'name'      => array(
							'description' => __( 'Course name', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'quantity'  => array(
							'description' => __( 'Quantity ordered.', 'learning-management-system' ),
							'type'        => 'integer',
							'default'     => 1,
							'readonly'    => true,
							'context'     => array( 'view', 'edit' ),
						),
						'subtotal'  => array(
							'description' => __( 'Course total (before discounts).', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total'     => array(
							'description' => __( 'Course total (after discounts).', 'learning-management-system' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'price'     => array(
							'description' => __( 'Course price', 'learning-management-system' ),
							'type'        => 'number',
							'readonly'    => true,
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'meta_data'            => array(
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
	 * Prepare a single order for create or update.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Masteriyo\Database\Model
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id    = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$order = masteriyo( 'order' );

		if ( 0 !== $id ) {
			$order->set_id( $id );
			$order_repo = masteriyo( 'order.store' );
			$order_repo->read( $order );
		}

		// Currency.
		if ( isset( $request['currency'] ) ) {
			$order->set_currency( $request['currency'] );
		}

		// Customer ID.
		if ( isset( $request['customer_id'] ) ) {
			$order->set_customer_id( $request['customer_id'] );
		}

		// Set payment method.
		if ( isset( $request['payment_method'] ) ) {
			$order->set_payment_method( $request['payment_method'] );
		}

		// Set payment method title.
		if ( isset( $request['payment_method_title'] ) ) {
			$order->set_payment_method_title( $request['payment_method_title'] );
		}

		// Set transaction ID.
		if ( isset( $request['transaction_id'] ) ) {
			$order->set_transaction_id( $request['transaction_id'] );
		}

		// Set customer note.
		if ( isset( $request['customer_note'] ) ) {
			$order->set_customer_note( $request['customer_note'] );
		}

		// Set payment sheet id.
		if ( isset( $request['payment_sheet_id'] ) ) {
			$order->set_payment_sheet_id( $request['payment_sheet_id'] );
		}

		// Set order status.
		if ( isset( $request['status'] ) ) {
			$order->set_status( $request['status'] );
		}

		// Set status as paid.
		if ( $request['set_paid'] ) {
			$order->set_status( OrderStatus::COMPLETED );
		}

		// Set created_via.
		if ( $request['created_via'] ) {
			$order->set_created_via( $request['created_via'] );
		}

		// Add course items.
		if ( isset( $request['course_lines'] ) ) {
			foreach ( $request['course_lines'] as $course_line ) {
				$this->set_item( $order, 'course_lines', $course_line );
			}
		}

		// Set customer IP address.
		$order->set_customer_ip_address( masteriyo_get_current_ip_address() );

		// Order billing details.
		if ( isset( $request['billing']['first_name'] ) ) {
			$order->set_billing_first_name( $request['billing']['first_name'] );
		}

		if ( isset( $request['billing']['last_name'] ) ) {
			$order->set_billing_last_name( $request['billing']['last_name'] );
		}

		if ( isset( $request['billing']['company'] ) ) {
			$order->set_billing_company( $request['billing']['company'] );
		}

		if ( isset( $request['billing']['address_1'] ) ) {
			$order->set_billing_address_1( $request['billing']['address_1'] );
		}

		if ( isset( $request['billing']['address_2'] ) ) {
			$order->set_billing_address_2( $request['billing']['address_2'] );
		}

		if ( isset( $request['billing']['city'] ) ) {
			$order->set_billing_city( $request['billing']['city'] );
		}

		if ( isset( $request['billing']['postcode'] ) ) {
			$order->set_billing_postcode( $request['billing']['postcode'] );
		}

		if ( isset( $request['billing']['country'] ) ) {
			$order->set_billing_country( $request['billing']['country'] );
		}

		if ( isset( $request['billing']['state'] ) ) {
			$order->set_billing_state( $request['billing']['state'] );
		}

		if ( isset( $request['billing']['email'] ) ) {
			$order->set_billing_email( $request['billing']['email'] );
		}

		if ( isset( $request['billing']['phone'] ) ) {
			$order->set_billing_phone( $request['billing']['phone'] );
		}

		if ( isset( $request['customer_note'] ) ) {
			$order->set_customer_note( $request['customer_note'] );
		}

		if ( isset( $request['attachment_id'] ) ) {
			$order->set_attachment_id( $request['attachment_id'] );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$order->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.0.0
		 *
		 * @param Masteriyo\Database\Model $order Order object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $order, $request, $creating );
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return boolean|WP_Error True if the request has read access for the item, WP_Error object otherwise.
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

		$id    = absint( $request['id'] );
		$order = masteriyo_get_order( $id );

		if ( is_null( $order ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		$cap = $order->get_customer_id() === get_current_user_id() ? 'read_orders' : 'read_others_orders';

		if ( ! $this->permission->rest_check_order_permissions( $cap, $id ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to read resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( ! $this->permission->rest_check_order_permissions( 'read_orders' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to read resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Check permissions for an item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $object_type Object type.
	 * @param string $context   Request context.
	 * @param int    $object_id Post ID.
	 *
	 * @return bool
	 */
	protected function check_item_permission( $object_type, $context = 'read', $object_id = 0 ) {
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( 'read' === $context ) {
			$order = masteriyo_get_order( $object_id );
			$cap   = $order->get_customer_id() === get_current_user_id() ? 'read_orders' : 'read_others_orders';

			if ( ! $this->permission->rest_check_order_permissions( $cap, $object_id ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.0.0
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

		if ( ! $this->permission->rest_check_order_permissions( 'create' ) ) {
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
	 * Check if a given request has access to delete an item.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
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

		if ( ! $this->permission->rest_check_order_permissions( 'delete', $request['id'] ) ) {
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
	 * Check if a given request has access to update an item.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
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

		/**
		 * Prevent from updating the order owner to someone else.
		 */
		if ( isset( $request['customer_id'] ) && absint( $request['customer_id'] ) !== get_current_user_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to change the owner of the order.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_order_permissions( 'update', $request['id'] ) ) {
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
	 * Get course order items.
	 *
	 * @since 1.0.0
	 *
	 * @param OrderItem[] $items
	 * @return void
	 */
	protected function get_order_item_course( $items, $context ) {
		$course_items = array_filter(
			$items,
			function( $item ) {
				return 'course' === $item->get_type();
			}
		);

		$data = array();

		foreach ( $course_items as $course_item ) {
			$data[] = array(
				'id'                 => $course_item->get_id(),
				'name'               => wp_specialchars_decode( $course_item->get_name( $context ) ),
				'type'               => $course_item->get_type( $context ),
				'course_id'          => $course_item->get_course_id( $context ),
				'quantity'           => $course_item->get_quantity( $context ),
				'subtotal'           => $course_item->get_subtotal( $context ),
				'total'              => $course_item->get_total( $context ),
				'formatted_subtotal' => $course_item->get_rest_formatted_subtotal( $context ),
				'formatted_total'    => $course_item->get_rest_formatted_total( $context ),
			);
		}

		return $data;
	}


	/**
	 * Save an object data.
	 *
	 * @since  1.0.0
	 * @throws RestException But all errors are validated before returning any data.
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return MOdel|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {
		try {
			$object = $this->prepare_object_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			if ( ! is_null( $request['customer_id'] ) && 0 !== $request['customer_id'] ) {
				// Make sure customer exists.
				if ( false === get_user_by( 'id', $request['customer_id'] ) ) {
					throw new RestException( 'masteriyo_rest_invalid_customer_id', __( 'Customer ID is invalid.', 'learning-management-system' ), 400 );
				}

				// Make sure customer is part of blog.
				if ( is_multisite() && ! is_user_member_of_blog( $request['customer_id'] ) ) {
					add_user_to_blog( get_current_blog_id(), $request['customer_id'], 'masteriyo_student' );
				}
			}

			if ( $creating ) {
				if ( empty( $object->get_created_via() ) ) {
					$object->set_created_via( 'rest-api' );
				}

				$object->set_prices_include_tax( 'yes' === get_option( 'masteriyo_prices_include_tax' ) );
				$object->calculate_totals();
			} else {
				// If items have changed, recalculate order totals.
				if ( isset( $request['billing'] ) || isset( $request['course_lines'] ) ) {
					$object->calculate_totals( true );
				}
			}

			// Set status.
			if ( ! empty( $request['status'] ) ) {
				$object->set_status( $request['status'] );
			}

			$object->save();

			// Actions for after the order is saved.
			if ( true === $request['set_paid'] ) {
				if ( $creating || $object->needs_payment() ) {
					$object->payment_complete( $request['transaction_id'] );
				}
			}

			return $this->get_object( $object->get_id() );
		} catch ( ModelException $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( RestException $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Wrapper method to create/update order items.
	 * When updating, the item ID provided is checked to ensure it is associated
	 * with the order.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order order object.
	 * @param string   $item_type The item type.
	 * @param array    $posted item provided in the request body.
	 * @throws RestException If item ID is not associated with order.
	 */
	protected function set_item( $order, $item_type, $posted ) {
		global $wpdb;

		$action = empty( $posted['id'] ) ? 'create' : 'update';
		$method = 'prepare_' . $item_type;
		$item   = null;

		// Verify provided line item ID is associated with order.
		if ( 'update' === $action ) {
			$item = $order->get_item( absint( $posted['id'] ), false );

			if ( ! $item ) {
				throw new RestException( 'masteriyo_rest_invalid_item_id', __( 'Order item ID provided is not associated with order.', 'learning-management-system' ), 400 );
			}
		}

		// Prepare item data.
		$item = $this->$method( $posted, $action, $item );

		/**
		 * Fires before setting an order item in an order object.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $item Order item.
		 * @param mixed $posted Posted data from request.
		 */
		do_action( 'masteriyo_rest_set_order_item', $item, $posted );

		// If creating the order, add the item to it.
		if ( 'create' === $action ) {
			$order->add_item( $item );
		} else {
			$item->save();
		}
	}


	/**
	 * Gets the course ID from the SKU or posted ID.
	 *
	 * @since 1.0.0
	 *
	 * @throws RestException When SKU or ID is not valid.
	 * @param array  $posted Request data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @return int
	 */
	protected function get_course_id( $posted, $action = 'create' ) {
		if ( ! empty( $posted['course_id'] ) && empty( $posted['variation_id'] ) ) {
			$course_id = (int) $posted['course_id'];
		} elseif ( 'update' === $action ) {
			$course_id = 0;
		} else {
			throw new RestException( 'masteriyo_rest_required_course_reference', __( 'Course ID or SKU is required.', 'learning-management-system' ), 400 );
		}
		return $course_id;
	}

	/**
	 * Create or update a course item.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $posted Line item data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return OrderItemCourse
	 * @throws RestException Invalid data, server error.
	 */
	protected function prepare_course_lines( $posted, $action = 'create', $item = null ) {
		if ( is_null( $item ) ) {
			$item = masteriyo( 'order-item.course' );
			if ( ! empty( $posted['id'] ) ) {
				$item->set_id( $posted['id'] );

				masteriyo( 'order-item.course.store' )->read( $item );
			}
		}

		$course = masteriyo_get_course( $this->get_course_id( $posted, $action ) );

		if ( $course && $course !== $item->get_course() ) {
			$item->set_course( $course );

			if ( 'create' === $action ) {
				$quantity = isset( $posted['quantity'] ) ? $posted['quantity'] : 1;
				$total    = masteriyo_get_price_excluding_tax( $course, array( 'qty' => $quantity ) );
				$item->set_total( $total );
				$item->set_subtotal( $total );
			}
		}

		$this->maybe_set_item_props( $item, array( 'name', 'quantity', 'total', 'subtotal' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Maybe set an item prop if the value was posted.
	 *
	 * @since 1.0.0
	 *
	 * @param OrderItem $item   Order item.
	 * @param string        $prop   Order property.
	 * @param array         $posted Request data.
	 */
	protected function maybe_set_item_prop( $item, $prop, $posted ) {
		if ( isset( $posted[ $prop ] ) ) {
			$item->{"set_$prop"}( $posted[ $prop ] );
		}
	}

	/**
	 * Maybe set item props if the values were posted.
	 *
	 * @since 1.0.0
	 *
	 * @param OrderItem $item   Order item data.
	 * @param string[]      $props  Properties.
	 * @param array         $posted Request data.
	 */
	protected function maybe_set_item_props( $item, $props, $posted ) {
		foreach ( $props as $prop ) {
			$this->maybe_set_item_prop( $item, $prop, $posted );
		}
	}

	/**
	 * Maybe set item meta if posted.
	 *
	 * @since 1.0.0
	 *
	 * @param OrderItem $item   Order item data.
	 * @param array         $posted Request data.
	 */
	protected function maybe_set_item_meta_data( $item, $posted ) {
		if ( ! empty( $posted['meta_data'] ) && is_array( $posted['meta_data'] ) ) {
			foreach ( $posted['meta_data'] as $meta ) {
				if ( isset( $meta['key'] ) ) {
					$value = isset( $meta['value'] ) ? $meta['value'] : null;
					$item->update_meta_data( $meta['key'], $value, isset( $meta['id'] ) ? $meta['id'] : '' );
				}
			}
		}
	}

	/**
	 * Restore order.
	 *
	 * @since 1.4.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
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
	 * Get courses count by status.
	 *
	 * @since 1.5.0
	 *
	 * @return Array
	 */
	protected function get_orders_count() {
		$post_count = parent::get_posts_count();

		$post_count        = masteriyo_array_only( $post_count, OrderStatus::all() );
		$post_count['any'] = array_sum( masteriyo_array_except( $post_count, OrderStatus::TRASH ) );

		return $post_count;
	}


	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since  1.6.5
	 * @return array
	 */
	protected function prepare_objects_query_for_batch( $request ) {
		$query_args = parent::prepare_objects_query_for_batch( $request );

		$query_args['post_status'] = OrderStatus::all();

		/**
		 * Filters objects query for batch operation.
		 *
		 * @since 1.6.5
		 *
		 * @param array $query_args Query arguments.
		 * @param WP_REST_Request $request
		 * @param \Masteriyo\RestApi\Controllers\Version1\PostsController $controller
		 */
		return apply_filters( "masteriyo_rest_{$this->object_type}_objects_query_for_batch", $query_args, $request, $this );
	}

	/**
	 * Restore orders.
	 *
	 * @since 1.6.5
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

	/**
	 * Check if a given request has access to delete items.
	 *
	 * @since 1.6.5
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
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

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'batch' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to delete resources', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}


	/**
	 * Import items from a CSV file.
	 *
	 * @since 1.18.1
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function import_items( \WP_REST_Request $request ) {
		$file = $this->get_import_file( $request->get_file_params() );

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		return $this->import( $file, $request );
	}

	/**
	 * Parse Import file.
	 *
	 * @since 1.18.1
	 * @param array $files $_FILES array for a given file.
	 *
	 * @return string|\WP_Error File path on success and WP_Error on failure.
	 */
	protected function get_import_file( $files ) {
		if ( ! isset( $files['file']['tmp_name'] ) ) {
			return new \WP_Error(
				'rest_upload_no_data',
				__( 'No data supplied.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		if (
			! isset( $files['file']['name'] ) ||
			'csv' !== pathinfo( $files['file']['name'], PATHINFO_EXTENSION )
		) {
			return new \WP_Error(
				'invalid_file_ext',
				__( 'Invalid file type for import.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		return $files['file']['tmp_name'];
	}

	/**
	 * Import the CSV data with batch processing.
	 *
	 * @since 1.18.1
	 *
	 * @param string $file_path The path to the CSV file.
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function import( $file_path, $request ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return new \WP_Error( 'invalid_file', 'Invalid or unreadable CSV file.' );
		}

		$rows = $this->parse_csv_file( $file_path );

		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		if ( empty( $rows ) ) {
			return new \WP_Error( 'invalid_csv_format', 'Invalid CSV format. Unable to process data.' );
		}

		$batch_size = 100;
		$total_rows = count( $rows );

		for ( $i = 0; $i < $total_rows; $i += $batch_size ) {
			$batch_rows = array_slice( $rows, $i, $batch_size );

			foreach ( $batch_rows as $row ) {
				try {
					$result = $this->enroll_user_to_course_from_csv_row( $row, $request );

					if ( is_wp_error( $result ) ) {
						continue;
					}
				} catch ( \Exception $e ) {
					continue;
				}
			}
		}

		return new \WP_REST_Response(
			array(
				'message' => __( 'Bulk enrollment completed successfully!.', 'learning-management-system' ),
			)
		);
	}
	/**
	 * Enroll a user to a course based on a CSV row.
	 *
	 * This method takes a row from a CSV file and attempts to enroll a user to a course.
	 * It validates and sanitizes the data, checks for existing users, and then enrolls the user.
	 *
	 * @since 1.18.1
	 *
	 * @param array $row The CSV row containing user and course information.
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_Error|Masteriyo/Models/Order Returns WP_Error on failure, or a Masteriyo/Models/Order object on success.
	 */
	protected function enroll_user_to_course_from_csv_row( $row, $request ) {
		// Validate required fields.
		if ( empty( $row['email'] ) || empty( $row['course_id'] ) ) {
			return new \WP_Error( 'missing_required_fields', 'Email or Course ID are missing.' );
		}

		// Validate email.
		if ( ! is_email( $row['email'] ) ) {
			return new \WP_Error( 'invalid_email', 'Invalid email provided.' );
		}

		// Validate and sanitize course ID.
		$course_id = absint( $row['course_id'] );
		$course    = masteriyo_get_course( $course_id );

		if ( is_null( $course ) || is_wp_error( $course ) ) {
			return new \WP_Error( 'invalid_course', 'Invalid Course ID.' );
		}

		// Sanitize user data.
		$email      = sanitize_email( $row['email'] );
		$username   = sanitize_user( isset( $row['username'] ) ? $row['username'] : '' );
		$role       = Roles::STUDENT;
		$first_name = sanitize_text_field( isset( $row['first_name'] ) ? $row['first_name'] : '' );
		$last_name  = sanitize_text_field( isset( $row['last_name'] ) ? $row['last_name'] : '' );
		$password   = sanitize_text_field( isset( $row['password'] ) ? $row['password'] : '' );

		// Check for existing email and get user ID.
		$user        = get_user_by( 'email', $email );
		$customer_id = $user ? $user->ID : null;

		// If the email doesn't exist, create a new user.
		if ( ! $customer_id ) {
			// Check for existing username.
			if ( ! empty( $username ) && username_exists( $username ) ) {
					return new \WP_Error( 'username_exists', 'The username already exists.' );
			}

			// if username is empty.
			if ( empty( $username ) ) {
				add_filter( 'masteriyo_registration_is_generate_username', '__return_true' );
			}

			// If the password is empty.
			if ( empty( $password ) ) {
				add_filter( 'masteriyo_registration_is_generate_password', '__return_true' );
				$password = '';
			}

			// create a new user.
			$user = masteriyo_create_new_user(
				$email,
				$username,
				$password,
				$role,
				array(
					'first_name' => $first_name,
					'last_name'  => $last_name,
				)
			);

			if ( is_wp_error( $user ) ) {
				return $user;
			}

			$customer_id = $user->get_id();
		}

		// Add additional data to the WP_REST_Request object.
		$request->set_param( 'course_lines', array( array( 'course_id' => $course_id ) ) );
		$request->set_param( 'status', 'completed' );
		$request->set_param( 'created_via', 'manual-enrollment' );
		$request->set_param( 'customer_id', $customer_id );

		return $this->save_object( $request );
	}

	/**
	 * Parse the CSV file and return its data as an array of associative arrays.
	 *
	 * @since 1.18.1
	 *
	 * @return array|\WP_Error
	 */
	protected function parse_csv_file( $file_path = null ) {
		$header   = null;
		$csv_data = array();

		$wp_filesystem = masteriyo_get_filesystem();

		if ( ! $wp_filesystem || ! $wp_filesystem->exists( $file_path ) ) {
			return array(); // Return an empty array if the file doesn't exist.
		}

		$file_content = $wp_filesystem->get_contents( $file_path );

		// Remove the BOM (UTF-8 BOM is \xEF\xBB\xBF) globally.
		$file_content = preg_replace( '/\xEF\xBB\xBF/', '', $file_content );

		// Split the content into lines.
		$lines = explode( "\n", $file_content );

		if ( $lines ) {
			// Get the header row and sanitize it.
			$header = str_getcsv( sanitize_text_field( array_shift( $lines ) ) );

			$header = array_map(
				function ( $item ) {
					return strtolower( str_replace( ' ', '_', $item ) );
				},
				$header
			);

			// Check if required columns are present.
			if ( ! in_array( 'email', $header, true ) || ! in_array( 'course_id', $header, true ) ) {
				return new \WP_Error( 'missing_required_columns', 'The CSV file is missing required columns: Email, Course ID.' );
			}

			foreach ( $lines as $line ) {
				if ( empty( trim( $line ) ) ) {
					continue; // Skip empty lines.
				}
				$data = str_getcsv( $line );
				if ( count( $header ) === count( $data ) ) {
					$csv_data[] = array_combine( $header, $data );
				}
			}
		}

		return $csv_data;
	}




	/**
	 * Update enrollments status.
	 *
	 * @since 1.7.3
	 *
	 * @param integer $id The order ID.
	 * @param \Masteriyo\Models\Order\Order $order The order object.
	 */
	public function update_enrollments_status_for_orders_deletion( $id, $order ) {
		global $wpdb;

		if ( ! $wpdb || ! $id ) {
			return;
		}

		$new_status = 'inactive';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}masteriyo_user_items
					 JOIN {$wpdb->prefix}masteriyo_user_itemmeta ON {$wpdb->prefix}masteriyo_user_items.id = {$wpdb->prefix}masteriyo_user_itemmeta.user_item_id
					 SET {$wpdb->prefix}masteriyo_user_items.status = %s
					 WHERE {$wpdb->prefix}masteriyo_user_itemmeta.meta_key = '_order_id'
					 AND {$wpdb->prefix}masteriyo_user_itemmeta.meta_value = %d",
				$new_status,
				$id
			)
		); // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Update enrollments status.
	 *
	 * @since 1.7.3
	 *
	 * @param integer $id The order ID.
	 * @param \Masteriyo\Models\Order\Order $order The order object.
	 */
	public function update_enrollments_status_for_orders_restoration( $id, $order ) {
		global $wpdb;

		if ( ! $wpdb || ! $id || ! $order ) {
			return;
		}

		if ( OrderStatus::COMPLETED !== $order->get_status() ) {
			return;
		}

		$new_status = 'active';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}masteriyo_user_items
					 JOIN {$wpdb->prefix}masteriyo_user_itemmeta ON {$wpdb->prefix}masteriyo_user_items.id = {$wpdb->prefix}masteriyo_user_itemmeta.user_item_id
					 SET {$wpdb->prefix}masteriyo_user_items.status = %s
					 WHERE {$wpdb->prefix}masteriyo_user_itemmeta.meta_key = '_order_id'
					 AND {$wpdb->prefix}masteriyo_user_itemmeta.meta_value = %d",
				$new_status,
				$id
			)
		); // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	}
}
