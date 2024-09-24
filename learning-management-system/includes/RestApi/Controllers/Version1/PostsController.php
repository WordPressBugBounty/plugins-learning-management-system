<?php
/**
 * Abstract Rest Posts Controller Class
 *
 * @class PostsController
 * @package Masteriyo/RestApi
 */

namespace Masteriyo\RestApi\Controllers\Version1;

use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CourseChildrenPostType;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * PostsController
 *
 * @package Masteriyo/RestApi
 * @version  1.0.0
 */
abstract class PostsController extends CrudController {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = '';

	/**
	 * Checks if a given request has access to get a specific item.
	 *use Masteriyo\Enums\SectionChildrenPostType;

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

		$post = get_post( (int) $request['id'] );

		// Allow to get the items for open courses.
		if ( $post && in_array( $post->post_type, array_merge( CourseChildrenPostType::all(), array( PostType::QUESTION ) ), true ) ) {
			$course_id = get_post_meta( $post->ID, '_course_id', true );
			$course    = masteriyo_get_course( $course_id );

			if ( is_null( $course ) ) {
				return new \WP_Error(
					'masteriyo_rest_invalid_course_id',
					__( 'Invalid course ID.', 'learning-management-system' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}

			if ( CourseAccessMode::OPEN === $course->get_access_mode() && ! post_password_required( get_post( $course->get_id() ) ) ) {
				return true;
			}

			if ( is_user_logged_in() && masteriyo_is_current_user_student() && ! masteriyo_can_start_course( $course ) ) {
				return new \WP_Error(
					'masteriyo_rest_cannot_start_course',
					__( 'Sorry, you have not bought the course.', 'learning-management-system' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}

			if ( ! user_can( get_current_user_id(), 'edit_course', $course->get_id() ) && ( PostStatus::PUBLISH !== $course->get_status() || post_password_required( get_post( $course->get_id() ) ) ) ) {
				return new \WP_Error(
					'masteriyo_rest_cannot_start_course',
					__( 'Sorry, you are not allowed to read resources.', 'learning-management-system' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		if ( $post && ! $this->permission->rest_check_post_permissions( $this->post_type, 'read', $request['id'] ) ) {
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

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'read' ) ) {
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

		$instructor = masteriyo_get_current_instructor();
		if ( $instructor && ! $instructor->is_active() ) {
			return new \WP_Error(
				'masteriyo_rest_user_not_approved',
				__( 'Sorry, you are not approved by the manager.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
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

		$instructor = masteriyo_get_current_instructor();
		if ( $instructor && ! $instructor->is_active() ) {
			return new \WP_Error(
				'masteriyo_rest_user_not_approved',
				__( 'Sorry, you are not approved by the manager.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$post = get_post( (int) $request['id'] );

		if ( $post && ! $this->permission->rest_check_post_permissions( $this->post_type, 'delete', $post->ID ) ) {
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
	 * Check if a given request has access to delete items.
	 *
	 * @since 1.6.0
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

		$instructor = masteriyo_get_current_instructor();
		if ( $instructor && ! $instructor->is_active() ) {
			return new \WP_Error(
				'masteriyo_rest_user_not_approved',
				__( 'Sorry, you are not approved by the manager.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
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

		$instructor = masteriyo_get_current_instructor();
		if ( $instructor && ! $instructor->is_active() ) {
			return new \WP_Error(
				'masteriyo_rest_user_not_approved',
				__( 'Sorry, you are not approved by the manager.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$post = get_post( (int) $request['id'] );

		if ( $post && ! $this->permission->rest_check_post_permissions( $this->post_type, 'update', $post->ID ) ) {
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
	 * Get previous and next links for the request.
	 *
	 * @since 1.0.0
	 *
	 * @param Model           $object  Object data.
	 * @return array                   Links for the given post.
	 */
	protected function get_navigation_objects( $object ) {
		$previous = '';
		$next     = '';

		$course_contents = masteriyo_get_course_contents( $object->get_course_id() );
		$course_contents = array_filter(
			$course_contents,
			function( $course_content ) {
				return PostType::SECTION !== $course_content->get_post_type();
			}
		);
		$course_contents = array_values( $course_contents );

		try {
			foreach ( $course_contents as $index => $content ) {
				if ( $content->get_id() === $object->get_id() ) {
					$previous = ( $index - 1 ) > -1 ? $course_contents[ $index - 1 ] : '';
					$next     = ( $index + 1 ) < count( $course_contents ) ? $course_contents[ $index + 1 ] : '';
				}
			}
		} catch ( \Exception $error ) {
			// TODO Error log
			$error = $error->getErrorMessage();
		}

		return array(
			'previous' => $previous,
			'next'     => $next,
		);
	}

	/**
	 * Get previous and next links for the request.
	 *
	 * @since 1.0.0
	 *
	 * @param Model           $object  Object data.
	 * @return array                   Links for the given post.
	 */
	protected function get_navigation_links( $object ) {
		$navigation = $this->get_navigation_objects( $object );

		$links['previous'] = array(
			'href' => $this->get_navigation_link( $navigation['previous'] ),
		);

		$links['next'] = array(
			'href' => $this->get_navigation_link( $navigation['next'] ),
		);

		return $links;
	}

	/**
	 * Get navigation link.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $object Post object.
	 * @return string
	 */
	protected function get_navigation_link( $object ) {
		if ( empty( $object ) ) {
			return '';
		}

		$object_type = $object instanceof \WP_Post ? str_replace( 'mto-', '', $object->post_type ) : $object->get_object_type();
		$object_id   = $object instanceof \WP_Post ? $object->ID : $object->get_id();
		$object_rest = masteriyo( "{$object_type}.rest" );
		$link        = rest_url( sprintf( '/%s/%s/%d', $object_rest->namespace, $object_rest->rest_base, $object_id ) );

		return $link;
	}

	/**
	 * Get navigation items.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Database\Model $object Model object.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_navigation_items( $object, $context = 'view' ) {
		$navigation = $this->get_navigation_objects( $object );

		return array(
			'previous' => $this->get_navigation_item( $navigation['previous'] ),
			'next'     => $this->get_navigation_item( $navigation['next'] ),
		);
	}

	/**
	 * Get navigation item.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Database\Model $object Model object.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_navigation_item( $object, $context = 'view' ) {
		if ( empty( $object ) ) {
			return '';
		}
		$previous_parent = get_post( $object->get_parent_id() );
		$video           = get_post_meta( $object->get_id(), '_video_source_url', true );

		$previous = array(
			'id'     => $object->get_id(),
			'name'   => wp_specialchars_decode( $object->get_name() ),
			'type'   => $object->get_object_type(),
			'video'  => ! empty( trim( $video ) ),
			'parent' => is_null( $previous_parent ) ? null : array(
				'id'   => $previous_parent->ID,
				'name' => $previous_parent->post_title,
			),
		);

		return $previous;
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

		/**
		 * Filters list of object types to hide from instructor.
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $object_types The list of object types to hide from instructor.
		 */
		$object_types = apply_filters(
			'masteriyo_rest_hide_object_types_from_instructor',
			array(
				'course',
				'lesson',
				'quiz',
				'section',
				// 'question', // Removed since 1.11.0: Instructors can now view all quiz questions if they are enrolled in the course, regardless of authorship.
			)
		);

		if ( masteriyo_is_current_user_instructor() && in_array( $this->object_type, $object_types, true ) ) {
			$args = array_merge( $args, array( 'author' => get_current_user_id() ) );
		}

		return $args;
	}

	/**
	 * Get posts count by status.
	 *
	 * @since 1.4.12
	 * @since 1.5.0 Filter post counts by post authors.
	 *
	 * @return array
	 */
	protected function get_posts_count() {
		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			$post_count = (array) wp_count_posts( $this->post_type );
		} else {
			$post_count = (array) masteriyo_count_posts( $this->post_type, get_current_user_id() );
		}

		$post_count        = array_map( 'absint', $post_count );
		$post_count['any'] = array_sum( masteriyo_array_except( $post_count, PostStatus::TRASH ) );

		/**
		 * Filters the post counts.
		 *
		 * @since 1.5.12
		 *
		 * @param array $post_count Posts count.
		 * @param \Masteriyo\RestApi\Controllers\Version1\PostsController $controller Posts Controller.
		 */
		return apply_filters( "masteriyo_rest_{$this->object_type}_count", $post_count, $this );
	}

	/**
	 * Create new post title based on previous old post title.
	 *
	 * @since 1.9.3
	 *
	 * @param WP_Post $old_post Old post.
	 * @param string $post_title New post title.
	 *
	 * @return string
	 */
	protected function create_new_post_title( $old_post, $post_title = '' ) {
		$new_title   = empty( trim( $old_post->post_title ) ) ? __( 'Untitled', 'learning-management-system' ) : $old_post->post_title;
		$total_count = masteriyo_array_get( $this->get_posts_count(), 'any', 0 );
		$new_title   = "{$new_title} - Copy({$total_count})";

		$new_title = empty( $post_title ) ? $new_title : $post_title;
		$new_title = sanitize_text_field( $new_title );

		/**
		 * Filter new clone post title.
		 *
		 * @since 1.9.3
		 *
		 * @param string $new_title New post title.
		 * @param WP_Post $old_post Old post.
		 * @param string $post_title Passed new post title.
		 */
		return apply_filters( "masteriyo_pro_rest_{$this->object_type}_new_clone_post_title", $new_title, $old_post, $post_title );
	}

		/**
	 * Clone a WP Post object.
	 *
	 * @since 1.9.3
	 *
	 * @param \WP_Post $post_id WP Post ID.
	 * @param string[] $args New post content args.
	 * @return WP_Post|null New cloned post
	 */
	public function clone( $post_id, $args = '' ) {
		$old_post = get_post( $post_id );

		if ( null === $old_post ) {
			return null;
		}

		$new_post_arr = array(
			'post_author'           => $old_post->post_author,
			'post_content'          => $old_post->post_content,
			'post_title'            => $old_post->post_title,
			'post_excerpt'          => $old_post->post_excerpt,
			'post_status'           => $old_post->post_status,
			'comment_status'        => $old_post->comment_status,
			'ping_status'           => $old_post->ping_status,
			'post_password'         => $old_post->post_password,
			'to_ping'               => $old_post->to_ping,
			'pinged'                => $old_post->pinged,
			'post_content_filtered' => $old_post->post_content_filtered,
			'post_parent'           => $old_post->post_parent,
			'menu_order'            => $old_post->menu_order,
			'post_type'             => $old_post->post_type,
			'post_mime_type'        => $old_post->post_mime_type,
			'post_date'             => current_time( 'mysql' ),
			'post_date_gmt'         => current_time( 'mysql', true ),
		);

		$new_post_arr = wp_parse_args( $args, $new_post_arr );
		$new_post_id  = wp_insert_post( $new_post_arr );

		$a = get_post( $new_post_id );

		if ( 0 === $new_post_id || is_wp_error( $new_post_id ) ) {
			return null;
		}

		// Clone all the meta data.
		$meta_data = get_post_meta( absint( $post_id ) );
		foreach ( $meta_data as $meta_key => $meta_value ) {
			update_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value[0] ) );
		}

		return get_post( $new_post_id );
	}

		/**
	 * Clone one item/post from the collection.
	 *
	 * @since 1.9.3
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function clone_item( $request ) {
		$old_post       = get_post( absint( $request['id'] ) );
		$new_post_title = isset( $request['title'] ) ? $request['title'] : '';
		$new_post_title = empty( trim( $new_post_title ) ) ? $this->create_new_post_title( $old_post, $new_post_title ) : $new_post_title;
		$new_post_id    = $this->clone(
			$request['id'],
			array(
				'post_title'  => $new_post_title,
				'post_status' => PostStatus::DRAFT,
			)
		);

		if ( is_null( $new_post_id ) ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_cannot_clone",
				__( 'Unable to clone', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		// Read the new object.
		$object = $this->get_object( $new_post_id );

		if ( ! $object || 0 === $object->get_id() ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->object_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		if ( $this->public ) {
			$response->link_header( 'alternate', $this->get_permalink( $object ), array( 'type' => 'text/html' ) );
		}

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->object_type,
		 * refers to object type being prepared for the response.
		 *
		 * @since 1.9.3
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param \Masteriyo\Database\Model  $object New object
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_clone_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Check if a given request has access to clone an item.
	 *
	 * @since 1.9.3
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function clone_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$post = get_post( (int) $request['id'] );

		if ( $post && $this->post_type !== $post->post_type ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}
		if ( $post && ! $this->permission->rest_check_post_permissions( $this->post_type, 'update', $post->ID ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_clone',
				__( 'Sorry, you are not allowed to clone resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Restore courses.
	 *
	 * @since 1.6.0
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
			if ( ! $this->check_item_permission( $this->post_type, 'delete', $object->get_id() ) ) {
				continue;
			}

			wp_untrash_post( $object->get_id() );

			// Read object again.
			$object->set_status( PostStatus::DRAFT );

			$data               = $this->prepare_object_for_response( $object, $request );
			$restored_objects[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $restored_objects );
	}

	/**
	 * Prepare objects query for batch.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since  1.6.5
	 * @return array
	 */
	protected function prepare_objects_query_for_batch( $request ) {
		$query_args = array(
			'post_status'    => PostStatus::all(),
			'post_type'      => $this->post_type,
			'post__in'       => wp_parse_id_list( $request['ids'] ),
			'posts_per_page' => -1,
		);

		/**
		 * Filters objects query for batch operation.
		 *
		 * @since 1.6.5
		 *
		 * @param array $query_args Query arguments.
		 * @param WP_REST_Request $request
		 * @param \Masteriyo\RestApi\Controllers\Version1\PostsController $controller
		 */
		return apply_filters( 'masteriyo_rest_objects_query_for_batch', $query_args, $request, $this );
	}

	/**
	 * Delete multiple items.
	 *
	 * @since 1.6.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_items( $request ) {
		$force           = isset( $request['force'] ) ? (bool) $request['force'] : true;
		$deleted_objects = array();

		$request->set_param( 'context', 'edit' );

		$objects = $this->get_objects( $this->prepare_objects_query_for_batch( $request ) );

		$objects = isset( $objects['objects'] ) ? $objects['objects'] : array();

		foreach ( $objects as $object ) {
			if ( ! $this->check_item_permission( $this->post_type, 'delete', $object->get_id() ) ) {
				continue;
			}

			$data           = $this->prepare_object_for_response( $object, $request );
			$supports_trash = EMPTY_TRASH_DAYS > 0 && is_callable( array( $object, 'get_status' ) );

			/**
			 * Filter whether an object is trashable.
			 *
			 * Return false to disable trash support for the object.
			 *
			 * @since 1.6.0
			 *
			 * @param boolean $supports_trash Whether the object type support trashing.
			 * @param Masteriyo\Database\Model $object The object being considered for trashing support.
			 */
			$supports_trash = apply_filters( "masteriyo_rest_{$this->object_type}_object_trashable", $supports_trash, $object );

			if ( $force ) {
				$object->delete( $force, $request->get_params() );

				if ( 0 === $object->get_id() ) {
					$deleted_objects[] = $this->prepare_response_for_collection( $data );
				}
			} else {
				if ( ! $supports_trash ) {
					continue;
				}

				if ( is_callable( array( $object, 'get_status' ) ) ) {
					if ( PostStatus::TRASH === $object->get_status() ) {
						continue;
					}

					$object->delete( $force, $request->get_params() );

					if ( PostStatus::TRASH === $object->get_status() ) {
						$deleted_objects[] = $this->prepare_response_for_collection( $data );
					}
				}
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
		 * @since 1.6.0
		 *
		 * @param array $deleted_objects Objects collection which are deleted.
		 * @param array $objects Objects which are supposed to be deleted.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "masteriyo_rest_bulk_delete_{$this->object_type}_objects", $deleted_objects, $objects, $request );

		return rest_ensure_response( $deleted_objects );
	}
}
