<?php
/**
 * Wishlist items controller class.
 *
 * @since 1.12.2
 *
 * @package \Masteriyo\Addons\WishList\RestApi\Controllers\Version1
 */

namespace Masteriyo\Addons\WishList\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Helper\Permission;
use Masteriyo\Helper\Utils;
use Masteriyo\RestApi\Controllers\Version1\PostsController;

class WishListItemsController extends PostsController {
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	protected $rest_base = 'wishlist-items';

	/**
	 * Post type.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	protected $post_type = 'mto-wishlist-item';

	/**
	 * If object is hierarchical.
	 *
	 * @since 1.12.2
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

	/**
	 * Object type.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	protected $object_type = 'wishlist_item';

	/**
	 * Permission class.
	 *
	 * @since 1.12.2
	 *
	 * @var \Masteriyo\Helper\Permission
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.12.2
	 *
	 * @param \Masteriyo\Helper\Permission $permission
	 */
	public function __construct( Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.12.2
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
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/clone',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clone_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @since 1.12.2
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to a specific status.', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Prepare objects query.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @since 1.12.2
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		$args['post_parent'] = $request['course_id'];

		if ( ! masteriyo_is_current_user_admin() ) {
			$args['author'] = get_current_user_id();
		} elseif ( isset( $request['author'] ) ) {
			$args['author'] = absint( $request['author'] );
		}

		return $args;
	}

	/**
	 * Get wishlist item object.
	 *
	 * @since 1.12.2
	 *
	 * @param integer|\Masteriyo\Addons\WishList\Models\WishListItem|\WP_Post $object Object ID or WishListItem or WP_Post object.
	 *
	 * @return \Masteriyo\Addons\WishList\Models\WishListItem|null
	 */
	protected function get_object( $object ) {
		return masteriyo_get_wishlist_item( $object );
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since 1.12.2
	 *
	 * @param \Masteriyo\Addons\WishList\Models\WishListItem $object Model object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->get_wishlist_item_data( $object, $context );
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @since 1.12.2
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \Masteriyo\Addons\WishList\Models\WishListItem $object Model object.
		 * @param \WP_REST_Request $request Request object.
		 */
		return apply_filters( "masteriyo_pro_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Get wishlist item data.
	 *
	 * @since 1.12.2
	 *
	 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item Wishlist item instance.
	 * @param string  $context Request context. Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_wishlist_item_data( $wishlist_item, $context = 'view' ) {
		$author = masteriyo_get_user( $wishlist_item->get_author_id( $context ) );
		$course = masteriyo_get_course( $wishlist_item->get_course_id( $context ) );

		if ( ! is_wp_error( $author ) ) {
			$author = array(
				'id'           => $author->get_id(),
				'display_name' => $author->get_display_name(),
				'avatar_url'   => $author->get_avatar_url(),
			);
		}

		$data = array(
			'id'                         => $wishlist_item->get_id(),
			'course_title'               => $wishlist_item->get_course_title( $context ),
			'course_id'                  => $wishlist_item->get_course_id( $context ),
			'course'                     => null,
			'author_id'                  => $wishlist_item->get_author_id( $context ),
			'author'                     => $author,
			'created_at'                 => masteriyo_rest_prepare_date_response( $wishlist_item->get_date_created( $context ) ),
			'default_featured_image_url' => masteriyo_placeholder_img_src( 'masteriyo_thumbnail' ),
		);

		if ( $course ) {
			$course_author = masteriyo_get_user( $course->get_author_id( $context ) );

			/**
			 * Filters short description of a course.
			 *
			 * @since 1.12.2
			 *
			 * @param string $short_description Short description of a course.
			 */
			$short_description = 'view' === $context ? apply_filters( 'masteriyo_short_description', $course->get_short_description() ) : $course->get_short_description();

			$data['course'] = array(
				'id'                 => $course->get_id(),
				'name'               => wp_specialchars_decode( $course->get_name( $context ) ),
				'slug'               => $course->get_slug( $context ),
				'featured_image_url' => $course->get_featured_image_url( 'masteriyo_thumbnail' ),
				'permalink'          => $course->get_permalink(),
				'preview_permalink'  => $course->get_preview_link(),
				'status'             => $course->get_status( $context ),
				'description'        => 'view' === $context ? wpautop( do_shortcode( $course->get_description() ) ) : $course->get_description( $context ),
				'short_description'  => $short_description,
				'reviews_allowed'    => $course->get_reviews_allowed( $context ),
				'parent_id'          => $course->get_parent_id( $context ),
				'menu_order'         => $course->get_menu_order( $context ),
				'author'             => null,
				'date_created'       => masteriyo_rest_prepare_date_response( $course->get_date_created( $context ) ),
				'date_modified'      => $course->get_date_modified( $context ),
				'featured'           => $course->get_featured( $context ),
				'price'              => $course->get_price( $context ),
				'price_label'        => wp_kses_post( masteriyo_price( $course->get_price() ) ),
				'regular_price'      => $course->get_regular_price( $context ),
				'sale_price'         => $course->get_sale_price( $context ),
				'price_type'         => $course->get_price_type( $context ),
				'featured_image'     => $course->get_featured_image( $context ),
				'enrollment_limit'   => $course->get_enrollment_limit( $context ),
				'duration'           => $course->get_duration( $context ),
				'access_mode'        => $course->get_access_mode( $context ),
				'billing_cycle'      => $course->get_billing_cycle( $context ),
				'show_curriculum'    => $course->get_show_curriculum( $context ),
				'highlights'         => $course->get_highlights( $context ),
				'edit_post_link'     => get_edit_post_link( $course->get_id(), $context ),
				'categories'         => $this->get_taxonomy_terms( $course, 'cat' ),
				'difficulty'         => $this->get_taxonomy_terms( $course, 'difficulty' ),
				'average_rating'     => $course->get_average_rating( $context ),
				'review_count'       => $course->get_review_count( $context ),
				'start_course_url'   => $course->start_course_url(),
				'add_to_cart_url'    => $course->add_to_cart_url(),
				'buy_button'         => masteriyo_get_course_buy_button( $course ),
			);

			if ( ! is_wp_error( $course_author ) ) {
				$data['course']['author'] = array(
					'id'           => $course_author->get_id(),
					'display_name' => $course_author->get_display_name( $context ),
					'avatar_url'   => $course_author->get_avatar_url(),
				);
			}
		}

		/**
		 * Filter wishlist item rest response data.
		 *
		 * @since 1.12.2
		 *
		 * @param array $data Wishlist item data.
		 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item Wishlist item object.
		 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
		 * @param \Masteriyo\Addons\WishList\RestApi\Controllers\Version1\WishListItemsController $controller Wishlist items REST controller object.
		 */
		return apply_filters( "masteriyo_pro_rest_response_{$this->object_type}_data", $data, $wishlist_item, $context, $this );
	}

	/**
	 * Get the wishlist item schema, conforming to JSON Schema.
	 *
	 * @since 1.12.2
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'course_title'   => array(
					'description' => __( 'Course title.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'created_at'     => array(
					'description' => __( "The date the wishlist item was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'created_at_gmt' => array(
					'description' => __( 'The date the wishlist item was created, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'course_id'      => array(
					'description' => __( 'Course ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare a single course wishlist item to create or update.
	 *
	 * @since 1.12.2
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param bool $creating True if creating a new object.
	 *
	 * @return \WP_Error|\Masteriyo\Addons\WishList\Models\WishListItem
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id            = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$wishlist_item = masteriyo_create_wishlist_item_object();

		if ( 0 !== $id ) {
			$wishlist_item->set_id( $id );
			$wishlist_item_repo = masteriyo_get_wishlist_item_store();
			$wishlist_item_repo->read( $wishlist_item );
		}

		// Course title.
		if ( isset( $request['course_title'] ) ) {
			$wishlist_item->set_course_title( wp_kses_post( $request['course_title'] ) );
		}

		// Course ID.
		if ( isset( $request['course_id'] ) ) {
			$course_id = absint( $request['course_id'] );
			$course    = masteriyo_get_course( $course_id );

			$wishlist_item->set_course_id( $course_id );

			if ( ! is_null( $course ) ) {
				// Update the course_title attribute, if it's not set explicitly.
				if ( ! isset( $request['course_title'] ) ) {
					$wishlist_item->set_course_title( $course->get_name() );
				}

				// Update the course_category_ids attribute, if it's not set explicitly.
				if ( ! isset( $request['course_category_ids'] ) ) {
					$wishlist_item->set_course_category_ids( $course->get_category_ids() );
				}

				// Update the course_difficulty attribute, if it's not set explicitly.
				if ( ! isset( $request['course_difficulty'] ) ) {
					$wishlist_item->set_course_difficulty( $course->get_difficulty() );
				}
			}
		}

		// Author ID.
		if ( isset( $request['author_id'] ) ) {
			$wishlist_item->set_author_id( absint( $request['author_id'] ) );
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.12.2
		 *
		 * @param \Masteriyo\Addons\WishList\Models\WishListItem $wishlist_item  Wishlist item object.
		 * @param \WP_REST_Request $request Request object.
		 * @param bool $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_pro_rest_pre_insert_{$this->object_type}_object", $wishlist_item, $request, $creating );
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @since 1.12.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$course_id = absint( $request['course_id'] );
		$course    = masteriyo_get_course( $course_id );

		if ( is_null( $course ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid course ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		$author_id = absint( $request['author_id'] );

		if ( get_current_user_id() !== $author_id ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to add courses in others wishlist.', 'learning-management-system' ),
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
	 * @since 1.12.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$id            = absint( $request['id'] );
		$wishlist_item = masteriyo_get_wishlist_item( $id );

		if ( is_null( $wishlist_item ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( get_current_user_id() !== $wishlist_item->get_author_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete others wishlist items.', 'learning-management-system' ),
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
	 * @since 1.12.2
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$id            = absint( $request['id'] );
		$wishlist_item = masteriyo_get_wishlist_item( $id );

		if ( is_null( $wishlist_item ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			return true;
		}

		if ( get_current_user_id() !== $wishlist_item->get_author_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update others wishlist items.', 'learning-management-system' ),
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
	 * @since 1.12.2
	 *
	 * @param string $object_type Object type.
	 * @param string $context Request context.
	 * @param int    $object_id Post ID.
	 *
	 * @return bool
	 */
	protected function check_item_permission( $object_type, $context = 'read', $object_id = 0 ) {
		return true;
	}

	/**
	 * Process objects collection.
	 *
	 * @since 1.12.2
	 *
	 * @param array $objects Wishlist items.
	 * @param array $query_args Query arguments.
	 * @param array $query_results Wishlist items query result.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		return array(
			'data' => $objects,
			'meta' => array(
				'total'                => $query_results['total'],
				'pages'                => $query_results['pages'],
				'current_page'         => $query_args['paged'],
				'per_page'             => $query_args['posts_per_page'],
				'wishlist_items_count' => $this->get_posts_count(),
			),
		);
	}

	/**
	 * Get taxonomy terms of a course.
	 *
	 * @since 1.12.2
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array
	 */
	protected function get_taxonomy_terms( $course, $taxonomy = 'cat' ) {
		$terms = Utils::get_object_terms( $course->get_id(), 'course_' . $taxonomy );

		$terms = array_map(
			function( $term ) {
				return array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			},
			$terms
		);

		$terms = 'difficulty' === $taxonomy ? array_shift( $terms ) : $terms;

		return $terms;
	}
}
