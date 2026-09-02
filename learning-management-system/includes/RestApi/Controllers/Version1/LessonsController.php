<?php
/**
 * Lessons class controller.
 *
 * @since 1.0.0
 * @package Masteriyo\RestApi
 * @subpackage Controllers
 */

namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\AudioSource;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\PostStatus;
use Masteriyo\RestApi\Controllers\Version1\PostsController;
use Masteriyo\Enums\SectionChildrenPostType;
use Masteriyo\Enums\VideoSource;
use Masteriyo\Helper\Permission;
use Masteriyo\PostType\PostType;
use Masteriyo\AddonsFramework\Addons;
use WP_Error;

class LessonsController extends PostsController {
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
	protected $rest_base = 'lessons';

	/** Object type.
	 *
	 * @var string
	 */
	protected $object_type = 'lesson';

	/** Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'mto-lesson';

	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

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
	 * @param Permission $permission
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;
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
							'default'     => true,
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
			'/' . $this->rest_base . '/bulk-update',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'bulk_update_items' ),
					'permission_callback' => array( $this, 'bulk_update_items_permissions_check' ),
					'args'                => array(
						'ids'        => array(
							'description' => __( 'IDs of the lessons to update.', 'learning-management-system' ),
							'type'        => 'array',
							'items'       => array( 'type' => 'integer' ),
							'required'    => true,
						),
						'video_meta' => array(
							'description'          => __( 'Video settings to apply to every lesson.', 'learning-management-system' ),
							'type'                 => 'object',
							'properties'           => array(
								'enable_video_share' => array( 'type' => 'boolean' ),
								'enable_right_button_click' => array( 'type' => 'boolean' ),
							),
							'additionalProperties' => false,
							'validate_callback'    => 'rest_validate_request_arg',
							'sanitize_callback'    => 'rest_sanitize_request_arg',
						),
						'status'     => array(
							'description'       => __( 'Status to apply to every lesson.', 'learning-management-system' ),
							'type'              => 'string',
							'enum'              => array( PostStatus::DRAFT, PostStatus::PUBLISH ),
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'sanitize_key',
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
							'description' => __( 'Lesson IDs.', 'learning-management-system' ),
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

		// @since 2.5.7 Added clone endpoint to lessons REST API.
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
					'permission_callback' => array( $this, 'clone_item_permissions_check' ),
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
	 * Restore a trashed lesson.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function restore_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid ID.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		wp_untrash_post( $object->get_id() );
		$object->set_status( PostStatus::DRAFT );
		$object->save();

		/**
		 * Fires after a lesson is restored from trash.
		 *
		 * @param int                      $id     Lesson ID.
		 * @param \Masteriyo\Models\Lesson $object Lesson object.
		 */
		do_action( 'masteriyo_lesson_restore', $request['id'], $object );

		$object   = $this->get_object( (int) $request['id'] );
		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Gate the bulk update on the collection cap; each ID is re-checked in the loop.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function bulk_update_items_permissions_check( $request ) {
		$permission = $this->update_item_permissions_check( $request );

		if ( true !== $permission ) {
			return $permission;
		}

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'batch' ) ) {
			return new WP_Error(
				'masteriyo_rest_cannot_update',
				__( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Apply one set of settings to many lessons.
	 *
	 * Fields sit at the top level so raw-request addons (content drip) run per lesson.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function bulk_update_items( $request ) {
		$ids = array_unique( array_filter( array_map( 'absint', (array) $request['ids'] ) ) );

		if ( empty( $ids ) ) {
			return new WP_Error(
				'masteriyo_rest_lesson_invalid_ids',
				__( 'No lesson was selected.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$video_meta = $request['video_meta'];
		$fields     = is_array( $video_meta ) ? array( 'video_meta' => $video_meta ) : array();

		if ( ! empty( $request['status'] ) ) {
			$fields['status'] = $request['status'];
		}

		$updated = array();
		$failed  = array();

		foreach ( $ids as $id ) {
			$item = new \WP_REST_Request( 'PUT' );
			$item->set_body_params( array( 'id' => $id ) );

			/** @var \WP_Error|bool $permission */
			$permission = $this->update_item_permissions_check( $item );

			if ( true !== $permission ) {
				$failed[] = array(
					'id'      => $id,
					'message' => is_wp_error( $permission )
						? $permission->get_error_message()
						: __( 'Sorry, you are not allowed to update resources.', 'learning-management-system' ),
				);
				continue;
			}

			$lesson = $this->get_object( $id );

			if ( ! $lesson || 0 === $lesson->get_id() ) {
				$failed[] = array(
					'id'      => $id,
					'message' => __( 'Invalid ID', 'learning-management-system' ),
				);
				continue;
			}

			$body = array_merge( $fields, array( 'id' => $id ) );

			// set_video_meta() replaces the whole array, so carry over the keys the bulk form does not touch.
			if ( isset( $fields['video_meta'] ) ) {
				$body['video_meta'] = array_merge( (array) $lesson->get_video_meta( 'edit' ), $fields['video_meta'] );
			}

			$item->set_body_params( $body );

			/** @var \WP_Error|\WP_REST_Response $response */
			$response = $this->update_item( $item );

			if ( is_wp_error( $response ) ) {
				$failed[] = array(
					'id'      => $id,
					'message' => $response->get_error_message(),
				);
				continue;
			}

			$updated[] = $id;
		}

		return rest_ensure_response(
			array(
				'updated' => $updated,
				'failed'  => $failed,
			)
		);
	}

	/**
	 * Get the query params for collections of download_materials.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		// The sections should be order by menu which is the sort order.
		$params['order']['default']   = 'asc';
		$params['orderby']['default'] = 'menu_order';

		$params['course_id']  = array(
			'description'       => __( 'Limit lessons by course id.', 'learning-management-system' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['slug']       = array(
			'description'       => __( 'Limit result set to lessons with a specific slug.', 'learning-management-system' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['status']     = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to lessons assigned a specific status.', 'learning-management-system' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any', 'future' ), array_keys( get_post_statuses() ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['category']   = array(
			'description'       => __( 'Limit result set to lessons assigned a specific category ID.', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['tag']        = array(
			'description'       => __( 'Limit result set to lessons assigned a specific tag ID.', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['difficulty'] = array(
			'description'       => __( 'Limit result set to lessons assigned a specific difficulty ID.', 'learning-management-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get object.
	 *
	 * @since 1.0.0
	 *
	 * @param  int|WP_Post|Model $object Object ID or WP_Post or Model.
	 *
	 * @return object Model object or WP_Error object.
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = is_a( $object, '\WP_Post' ) ? $object->ID : $object->get_id();
			}
			$lesson = masteriyo( 'lesson' );
			$lesson->set_id( $id );
			$lesson_repo = masteriyo( 'lesson.store' );
			$lesson_repo->read( $lesson );
		} catch ( \Exception $e ) {
			return false;
		}

		return $lesson;
	}


	/**
	 * Prepares the object for the REST response.
	 *
	 * @since   1.0.0
	 *
	 * @param  Masteriyo\Database\Model $object  Model object.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_lesson_data( $object, $context );

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
	 * Get lesson description data
	 *
	 * @since 1.7.3
	 *
	 * @param \Masteriyo\Models\Lesson $lesson Lesson instance.
	 * @param string $context Request context.
	 *
	 * @return object
	 */
	protected function description_data( $lesson, $context ) {
		if ( 'view' === $context ) {
			/**
			 * Filters the raw lesson description before shortcodes are processed.
			 *
			 * Compatibility layers (e.g. H5P) use this to replace shortcodes that
			 * require server-side script enqueueing (like [h5p]) with iframe-based
			 * equivalents before do_shortcode() runs, so the original shortcode
			 * tags are still present and matchable by regex.
			 *
			 * @param string $raw_description Raw lesson description with shortcode tags.
			 * @param \Masteriyo\Models\Lesson $lesson Lesson instance.
			 * @param string $context Request context ('view' or 'edit').
			 */
			$raw = apply_filters( 'masteriyo_lesson_description_pre_shortcode', wp_kses_post( $lesson->get_description() ), $lesson, $context );

			return masteriyo_format_content_for_view( $raw );
		}

		return $lesson->get_description( $context );
	}

	/**
	 * Get lesson data.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Lesson $lesson Lesson instance.
	 * @param string $context Request context.
	 *                        Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_lesson_data( $lesson, $context = 'view' ) {
		$section = masteriyo_get_section( $lesson->get_parent_id() );
		$course  = masteriyo_get_course( $lesson->get_course_id( $context ) );

		/**
		 * Filters lesson short description.
		 *
		 * @since 1.0.0
		 *
		 * @param string $short_description Lesson short description.
		 */
		$short_description = 'view' === $context ? apply_filters( 'masteriyo_short_description', $lesson->get_short_description() ) : $lesson->get_short_description();

		$user_progress_videos_meta = array(
			'notes'       => masteriyo_get_user_activity_meta( get_current_user_id(), $lesson->get_id(), 'notes', 'lesson' ),
			'resume_time' => absint( masteriyo_get_user_activity_meta( get_current_user_id(), $lesson->get_id(), 'resume_time', 'lesson' ) ),
		);

		$data = array(
			'id'                         => $lesson->get_id(),
			'name'                       => wp_specialchars_decode( $lesson->get_name( $context ) ),
			'slug'                       => $lesson->get_slug( $context ),
			'permalink'                  => $lesson->get_permalink(),
			'preview_link'               => $lesson->get_preview_link(),
			'status'                     => $lesson->get_status( $context ),
			'description'                => $this->description_data( $lesson, $context ),
			'short_description'          => $short_description,
			'date_created'               => masteriyo_rest_prepare_date_response( $lesson->get_date_created( $context ) ),
			'date_modified'              => masteriyo_rest_prepare_date_response( $lesson->get_date_modified( $context ) ),
			'menu_order'                 => $lesson->get_menu_order( $context ),
			'parent_menu_order'          => $section ? $section->get_menu_order( $context ) : 0,
			'reviews_allowed'            => $lesson->get_reviews_allowed( $context ),
			'parent_id'                  => $lesson->get_parent_id( $context ),
			'course_id'                  => $course ? $course->get_id() : 0,
			'course_name'                => $course ? wp_specialchars_decode( $course->get_name( $context ) ) : '',
			'featured_image'             => $lesson->get_featured_image( $context ),
			'video_source'               => $this->get_video_source( $lesson->get_video_source( $context ) ),
			'video_source_url'           => $this->get_video_source_url( $lesson, $context ),
			'video_source_id'            => $lesson->get_video_source_id( $context ),
			'video_playback_time'        => $lesson->get_video_playback_time( $context ),
			'download_materials'         => $this->get_download_materials( $lesson, $context, $course ),
			'download_materials_message' => $this->get_download_materials_message( $lesson, $context, $course ),
			'video_meta'                 => $lesson->get_video_meta( $context ),
			'user_progress_videos_meta'  => $user_progress_videos_meta,
			'navigation'                 => $this->get_navigation_items( $lesson, $context ),
			'ends_at'                    => masteriyo_rest_prepare_date_response( $lesson->get_ends_at( $context ) ),
			'starts_at'                  => masteriyo_rest_prepare_date_response( $lesson->get_starts_at( $context ) ),
			'live_chat_enabled'          => $lesson->get_live_chat_enabled( $context ),
			'pdf'                        => $lesson->get_pdf( $context ) ? $lesson->get_pdf( $context ) : null,
			'pdf_downloadable'           => $lesson->get_pdf_downloadable( $context ),
			'enable_lesson_comment'      => masteriyo_get_setting( 'learn_page.display.enable_lesson_comment' ),
			'audio_source'               => $lesson->get_audio_source( $lesson, $context ),
			'audio_source_url'           => $lesson->get_audio_source_url( $lesson, $context ),
			'audio_source_files'         => $this->get_audio_source_files( $lesson, $context ),
			'transform_live_to_video'    => $lesson->get_transform_live_to_video( $context ),
			'subtitle_meta'              => $lesson->get_subtitle_meta( $context, $lesson->get_id() ),
			'custom_fields'              => $lesson->get_custom_fields( $context ),
			'lesson_type'                => $lesson->get_lesson_type( $context ),
		);

		$video_type       = $lesson->get_video_source( $context );
		$video_source_url = $lesson->get_video_source_url( $context );

		if ( 'live-stream' === $video_type && ! empty( $video_source_url ) ) {
			$thumbnail_url                     = masteriyo_get_youtube_thumbnail( $video_source_url );
			$data['live_stream_thumbnail_url'] = $thumbnail_url ?? '';
		}

		if ( masteriyo_get_setting( 'learn_page.display.enable_lesson_comment' ) ) {
			$course = masteriyo_get_course( $lesson->get_course_id() );
			if ( $course ) {
				$data['access_mode'] = null !== $course->get_access_mode() ? $course->get_access_mode() : false;
			}
		}

		/**
		 * Filter lesson rest response data.
		 *
		 * @since 1.4.10
		 *
		 * @param array $data Lesson data.
		 * @param Masteriyo\Models\lesson $lesson Lesson object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\lessonsController $controller REST lessons controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $lesson, $context, $this );
	}

	/**
	 * Get video source.
	 *
	 * @since 1.11.0 [free]
	 *
	 * @param \Masteriyo\Models\Lesson $lesson Lesson instance.
	 * @param string $context Request context.
	 *
	 * @return array
	 */
	protected function get_video_source( $video_source ) {
		if ( ( new Addons() )->is_addon( $video_source ) ) {
			if ( ! ( new Addons() )->is_active( $video_source ) ) {
				return 'self-hosted';
			}
		}
		return $video_source;
	}

	/**
	 * Get video source url.
	 *
	 * @since 1.11.0 [free]
	 *
	 * @param \Masteriyo\Models\Lesson $lesson Lesson instance.
	 * @param string $context Request context.
	 *
	 * @return array
	 */
	protected function get_video_source_url( $lesson, $context ) {
		if ( ( new Addons() )->is_addon( $lesson->get_video_source( $context ) ) ) {
			if ( ! ( new Addons() )->is_active( $lesson->get_video_source() ) ) {
				return '';
			}
		}
		return $lesson->get_video_source_url( $context );
	}

	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @since   1.0.0
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		// Set post_status.
		$args['post_status'] = $request['status'];

		if ( ! empty( $request['course_id'] ) ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => '_course_id',
					'value'   => absint( $request['course_id'] ),
					'compare' => '=',
				),
			);
		}

		return $args;
	}

	/**
	 * Get the lessons'schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->object_type,
			'type'       => 'object',
			'properties' => array(
				'id'                         => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'                       => array(
					'description' => __( 'Lesson name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug'                       => array(
					'description' => __( 'Lesson slug', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink'                  => array(
					'description' => __( 'Lesson URL', 'learning-management-system' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'               => array(
					'description' => __( "The date the lesson was created, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt'           => array(
					'description' => __( 'The date the lesson was created, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'              => array(
					'description' => __( "The date the lesson was last modified, in the site's timezone.", 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'          => array(
					'description' => __( 'The date the lesson was last modified, as GMT.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'                     => array(
					'description' => __( 'Lesson status (post status).', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => PostStatus::PUBLISH,
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'catalog_visibility'         => array(
					'description' => __( 'Catalog visibility', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => 'visible',
					'enum'        => array( 'visible', 'catalog', 'search', 'hidden' ),
					'context'     => array( 'view', 'edit' ),
				),
				'description'                => array(
					'description' => __( 'Lesson description', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'short_description'          => array(
					'description' => __( 'Lesson short description', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'reviews_allowed'            => array(
					'description' => __( 'Allow reviews.', 'learning-management-system' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'average_rating'             => array(
					'description' => __( 'Reviews average rating.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'rating_count'               => array(
					'description' => __( 'Amount of reviews that the lesson has.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_id'                  => array(
					'description' => __( 'Lesson parent ID', 'learning-management-system' ),
					'type'        => 'integer',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'course_id'                  => array(
					'description' => __( 'Course ID', 'learning-management-system' ),
					'type'        => 'integer',
					'required'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'course_name'                => array(
					'description' => __( 'Course name', 'learning-management-system' ),
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'menu_order'                 => array(
					'description' => __( 'Menu order, used to custom sort lessons.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'featured_image'             => array(
					'description' => __( 'Course featured image.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'video_source'               => array(
					'description' => __( 'Video source', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'default'     => VideoSource::SELF_HOSTED,
					'enum'        => array_keys( masteriyo_get_lesson_video_sources() ),
				),
				'video_source_url'           => array(
					'description' => __( 'Video source URL', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'video_playback_time'        => array(
					'description' => __( 'Video playback time', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'download_materials'         => array(
					'description' => __( 'download_materials', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                  => array(
								'description' => __( 'Download material ID', 'learning-management-system' ),
								'type'        => 'integer',
								'default'     => 0,
								'context'     => array( 'view', 'edit' ),
							),
							'title'               => array(
								'description' => __( 'Download material title', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'url'                 => array(
								'description' => __( 'Download material URL', 'learning-management-system' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'preview_url'         => array(
								'description' => __( 'Download material preview URL (served inline).', 'learning-management-system' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'mime_type'           => array(
								'description' => __( 'Download material mime type', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'file_size'           => array(
								'description' => __( 'Download material file size', 'learning-management-system' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'formatted_file_size' => array(
								'description' => __( 'Download material formatted file size', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'created_at'          => array(
								'description' => __( 'Download material creation/upload date.', 'learning-management-system' ),
								'type'        => 'string',
								'format'      => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'download_materials_message' => array(
					'description' => __( 'Message shown when the current user cannot access the download materials.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'audio_source'               => array(
					'description' => __( 'Audio source', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'default'     => AudioSource::SELF_HOSTED,
					'enum'        => array_keys( masteriyo_get_lesson_audio_sources() ),
				),
				'audio_source_url'           => array(
					'description' => __( 'Audio source URL', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'audio_source_files'         => array(
					'description' => __( 'Audio Lesson Files', 'learning-management-system' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                  => array(
								'description' => __( 'Audio file ID', 'learning-management-system' ),
								'type'        => 'integer',
								'default'     => 0,
								'context'     => array( 'view', 'edit' ),
							),
							'title'               => array(
								'description' => __( 'Audio material title', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'url'                 => array(
								'description' => __( 'Audio material URL', 'learning-management-system' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'mime_type'           => array(
								'description' => __( 'Audio material mime type', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'file_size'           => array(
								'description' => __( 'Audio material file size', 'learning-management-system' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'formatted_file_size' => array(
								'description' => __( 'Audio material formatted file size', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'created_at'          => array(
								'description' => __( 'Audio material creation/upload date.', 'learning-management-system' ),
								'type'        => 'string',
								'format'      => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),

				'video_meta'                 => array(
					'description' => __( 'Video metadata', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'enable_video_share'        => array(
								'description' => __( 'Whether to share the video.', 'learning-management-system' ),
								'type'        => 'boolean',
								'default'     => true,
								'context'     => array( 'view', 'edit' ),
							),
							'enable_right_button_click' => array(
								'description' => __( 'Whether user is allowed to right click on video.', 'learning-management-system' ),
								'type'        => 'boolean',
								'default'     => true,
								'context'     => array( 'view', 'edit' ),
							),
							'timestamps'                => array(
								'description' => __( 'Array of timestamps.', 'learning-management-system' ),
								'type'        => 'object',
								'properties'  => array(
									'start_time' => array(
										'description' => __( 'Start time in seconds.', 'learning-management-system' ),
										'type'        => 'integer',
										'default'     => 0,
										'context'     => array( 'view', 'edit' ),
									),
									'end_time'   => array(
										'description' => __( 'End time in seconds.', 'learning-management-system' ),
										'type'        => 'integer',
										'default'     => 0,
										'context'     => array( 'view', 'edit' ),
									),
									'label'      => array(
										'description' => __( 'Label for the timestamp.', 'learning-management-system' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'id'         => array(
										'description' => __( 'Timestamp ID.', 'learning-management-system' ),
										'type'        => 'string',
										'default'     => '',
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
						),
					),
				),

				'subtitle_meta'              => array(
					'description' => __( 'subtitle metadata', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'subtitle_source' => array(
								'description' => __( 'source of subtitle file', 'learning-management-system' ),
								'type'        => 'array',
								'default'     => array(),
								'context'     => array( 'view', 'edit' ),
							),
							'subtitle_label'  => array(
								'description' => __( 'Readable Label for Subtitle', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => '',
								'context'     => array( 'view', 'edit' ),
							),
							'subtitle_type'   => array(
								'description' => __( 'Type of subtitle file', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => '',
								'context'     => array( 'view', 'edit' ),
							),

							'subtitle_kind'   => array(
								'description' => __( 'Kind of subtitle file like (subtitle/caption/description) etc', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => '',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),

				'meta_data'                  => array(
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
				'custom_fields'              => array(
					'description' => __( 'Custom fields', 'learning-management-system' ),
					'type'        => 'object',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
				'lesson_type'                => array(
					'description' => __( 'Lesson Type', 'learning-management-system' ),
					'type'        => 'string',
					'default'     => '',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare a single lesson for create or update.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|Masteriyo\Database\Model
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;

		/** @var \Masteriyo\Models\Lesson $lesson */
		$lesson = masteriyo( 'lesson' );

		if ( 0 !== $id ) {
			$lesson->set_id( $id );
			$lesson_repo = masteriyo( 'lesson.store' );
			$lesson_repo->read( $lesson );
		}

		// Post title.
		if ( isset( $request['name'] ) ) {
			$lesson->set_name( sanitize_text_field( $request['name'] ) );
		}

		// Post content.
		if ( isset( $request['description'] ) ) {
			$lesson->set_description( wp_slash( wp_kses_post( $request['description'] ) ) );
		}

		// Post excerpt.
		if ( isset( $request['short_description'] ) ) {
			$lesson->set_short_description( wp_filter_post_kses( $request['short_description'] ) );
		}

		// Post status.
		if ( isset( $request['status'] ) ) {
			$lesson->set_status( get_post_status_object( $request['status'] ) ? $request['status'] : 'draft' );
		}

		// Publishing must drop a still-pending future date, otherwise
		// wp_insert_post() flips the status straight back to `future`. Reachable
		// for content scheduled outside the outline, e.g. from the post editor.
		if ( isset( $request['status'] ) && PostStatus::PUBLISH === $request['status'] ) {
			$date_created = $lesson->get_date_created( 'edit' );

			if ( $date_created && $date_created->getTimestamp() > time() ) {
				$lesson->set_date_created( time() );
			}
		}

		// Post slug.
		if ( isset( $request['slug'] ) ) {
			$lesson->set_slug( $request['slug'] );
		}

		// Menu order.
		if ( isset( $request['menu_order'] ) ) {
			$lesson->set_menu_order( $request['menu_order'] );
		}

		// Automatically set the menu order if it's not set and the operation is POST.
		if ( ! isset( $request['menu_order'] ) && $creating ) {
			$query = new \WP_Query(
				array(
					'post_type'      => SectionChildrenPostType::all(),
					'post_status'    => PostStatus::all(),
					'posts_per_page' => 1,
					'post_parent'    => $request['parent_id'],
				)
			);

			$lesson->set_menu_order( $query->found_posts );
		}

		// Comment status.
		if ( isset( $request['reviews_allowed'] ) ) {
			$lesson->set_reviews_allowed( $request['reviews_allowed'] );
		}

		// Lesson parent ID.
		if ( isset( $request['parent_id'] ) ) {
			$lesson->set_parent_id( $request['parent_id'] );
		}

		// Course ID.
		if ( isset( $request['course_id'] ) ) {
			$lesson->set_course_id( $request['course_id'] );
		}

		// Featured image.
		if ( isset( $request['featured_image'] ) ) {
			$lesson->set_featured_image( $request['featured_image'] );
		}

		// Lesson video source.
		if ( isset( $request['video_source'] ) ) {
			$lesson->set_video_source( $request['video_source'] );
		}

		// Lesson video source url.
		if ( isset( $request['video_source_url'] ) ) {
			$old_video_source_url = $lesson->get_video_source_url();
			$new_video_source_url = $request['video_source_url'];

			if ( $request['video_source_url'] !== $old_video_source_url ) {
				$this->delete_video_video_meta_data( $old_video_source_url, $new_video_source_url, $lesson );
			}

			$lesson->set_video_source_url( $new_video_source_url );

		}

		if ( isset( $request['subtitle_ids'] ) && ! empty( $request['subtitle_ids'] ) ) {
				$this->delete_video_subtitle_meta_data( $request['subtitle_ids'], $lesson );
		}

		// Lesson video playback time.
		if ( isset( $request['video_playback_time'] ) ) {
			$lesson->set_video_playback_time( $request['video_playback_time'] );
		}

		// Lesson download_materials.
		if ( isset( $request['download_materials'] ) ) {
			$lesson->set_download_materials( wp_list_pluck( $request['download_materials'], 'id' ) );
		}

		// Lesson audio source.
		if ( isset( $request['audio_source'] ) ) {
			$lesson->set_audio_source( $request['audio_source'] );
		}

		// Lesson audio source url.
		if ( isset( $request['audio_source_url'] ) ) {
			$old_audio_source_url = $lesson->get_audio_source_url();
			$new_audio_source_url = $request['audio_source_url'];

			$lesson->set_audio_source_url( $new_audio_source_url );
		}

		// Lesson audio source.
		if ( isset( $request['audio_source_files'] ) ) {
			$lesson->set_audio_source_files( wp_list_pluck( $request['audio_source_files'], 'id' ) );
		}

		// Video meta.
		if ( isset( $request['video_meta'] ) ) {
			$lesson->set_video_meta( $request['video_meta'] );
		}

		// Subtitle meta.
		if ( $request['subtitle_meta'] && is_array( $request['subtitle_meta'] ) ) {
			$lesson->set_subtitle_meta( $request['subtitle_meta'] );
		}

		// Lesson starts_at.
		if ( isset( $request['starts_at'] ) ) {
			$lesson->set_starts_at( $request['starts_at'], 'id' );
		}

		// Lesson ends_at.
		if ( isset( $request['ends_at'] ) ) {
			$lesson->set_ends_at( $request['ends_at'], 'id' );
		}

		// Allow set meta_data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$lesson->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		// Allow Live Chat.
		if ( isset( $request['live_chat_enabled'] ) ) {
			$lesson->set_live_chat_enabled( $request['live_chat_enabled'] );
		}

		// Allow Live to Normal Video.
		if ( isset( $request['transform_live_to_video'] ) ) {
			$lesson->set_transform_live_to_video( $request['transform_live_to_video'] );
		}

		// PDF.
		if ( isset( $request['pdf'] ) ) {
			$lesson->set_pdf( $request['pdf'] );
		}

		// PDF Downloadable.
		if ( isset( $request['pdf_downloadable'] ) ) {
			$lesson->set_pdf_downloadable( $request['pdf_downloadable'] );
		}

		// Custom Fields.
		if ( isset( $request['custom_fields'] ) ) {
			$lesson->set_custom_fields( $request['custom_fields'] );
		}

		// Lesson type.
		if ( isset( $request['lesson_type'] ) ) {
			$lesson->set_lesson_type( $request['lesson_type'] );
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->object_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.0.0
		 *
		 * @param Masteriyo\Database\Model $lesson Lesson object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->object_type}_object", $lesson, $request, $creating );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 1.0.0
	 *
	 * @param Model           $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {
		$links = parent::prepare_links( $object, $request );

		$next_prev_links = $this->get_navigation_links( $object, $request );

		return $links + $next_prev_links;
	}

	/**
	 * Get lesson audio_lesson_source.
	 *
	 * @since 2.17.0
	 *
	 * @param Masteriyo\Models\Lesson $lesson Lesson object.
	 * @param string $context Request context.
	 *
	 * @return array
	 */
	protected function get_audio_source_files( $lesson, $context ) {
		// Filter invalid audio lesson source.
		$audio_lesson_source = array_filter(
			array_map(
				function( $attachment ) {
					$post = get_post( $attachment );

					if ( $post && 'attachment' === $post->post_type ) {
						return $post;
					}

					return false;
				},
				$lesson->get_audio_source_files( $context )
			)
		);

		// Convert the audio_lesson_source to the response format.
		$audio_lesson_source = array_reduce(
			$audio_lesson_source,
			function( $result, $attachment ) {
				$file_size = absint( filesize( get_attached_file( $attachment->ID ) ) );

				$result[] = array(
					'id'                  => $attachment->ID,
					'url'                 => wp_get_attachment_url( $attachment->ID ),
					'title'               => $attachment->post_title,
					'mime_type'           => $attachment->post_mime_type,
					'file_size'           => $file_size,
					'formatted_file_size' => size_format( $file_size ),
					'created_at'          => masteriyo_rest_prepare_date_response( $attachment->post_date_gmt ),
				);
				return $result;
			},
			array()
		);

		/**
		 * Lesson attachment filter.
		 *
		 * @since 2.17.0
		 *
		 * @return array[] $audio_lesson_source Download materials.
		 * @param Masteriyo\Models\Lesson $lesson Lesson object.
		 * @param string $context Context.
		 */
		return apply_filters( "masteriyo_rest_{$this->object_type}_audio_source_files", $audio_lesson_source, $lesson, $context );
	}

	/**
	 * Get lesson download_materials.
	 *
	 * Returns an empty array when the current user cannot access the materials
	 * (see user_can_access_download_materials()).
	 *
	 * API contract note for external consumers (mobile apps, Zapier, headless, etc.):
	 * for non-open courses the `url`/`preview_url` are nonce-signed admin-ajax
	 * endpoints, NOT direct file URLs. Those URLs are bound to the requesting
	 * user's session and expire with the WordPress nonce (~12-24h), so they are
	 * not portable and should not be stored long-term. Open-access courses still
	 * return the direct attachment URL. Integrations that need a stable URL should
	 * authenticate as an authorized user (admin/instructor) and rely on the
	 * `masteriyo_rest_lesson_download_materials` filter to reshape the URLs.
	 *
	 * @since 2.0.2
	 *
	 * @param Masteriyo\Models\Lesson $lesson Lesson object.
	 * @param string $context Request context.
	 *
	 * @return array
	 */
	protected function get_download_materials( $lesson, $context, $course = null ) {
		if ( is_null( $course ) ) {
			$course = masteriyo_get_course( $lesson->get_course_id( $context ) );
		}

		// Only return materials if the current user has access.
		if ( ! $this->user_can_access_download_materials( $lesson, $context, $course ) ) {
			return array();
		}

		$is_open_course = $course && CourseAccessMode::OPEN === $course->get_access_mode();

		// Filter invalid download_materials.
		$download_materials = array_filter(
			array_map(
				function( $attachment ) {
					$post = get_post( $attachment );

					if ( $post && 'attachment' === $post->post_type ) {
						return $post;
					}

					return false;
				},
				$lesson->get_download_materials( $context )
			)
		);

		// Convert the download_materials to the response format.
		$download_materials = array_reduce(
			$download_materials,
			function( $result, $attachment ) use ( $lesson, $is_open_course ) {
				$file_size = absint( filesize( get_attached_file( $attachment->ID ) ) );

				// For open courses, expose the direct URL for both download and preview.
				// For protected courses, route through the nonce-signed endpoint so the
				// raw attachment URL is never exposed. The preview variant streams the
				// file inline instead of forcing a download.
				if ( $is_open_course ) {
					$direct_url   = wp_get_attachment_url( $attachment->ID );
					$download_url = $direct_url;
					$preview_url  = $direct_url;
				} else {
					$download_url = add_query_arg(
						array(
							'action'        => 'masteriyo_download_material',
							'lesson_id'     => $lesson->get_id(),
							'attachment_id' => $attachment->ID,
							'_nonce'        => wp_create_nonce( 'masteriyo_download_material_' . $lesson->get_id() ),
						),
						admin_url( 'admin-ajax.php' )
					);
					$preview_url  = add_query_arg( array( 'preview' => 1 ), $download_url );
				}

				$result[] = array(
					'id'                  => $attachment->ID,
					'url'                 => $download_url,
					'preview_url'         => $preview_url,
					'title'               => $attachment->post_title,
					'mime_type'           => $attachment->post_mime_type,
					'file_size'           => $file_size,
					'formatted_file_size' => size_format( $file_size ),
					'created_at'          => masteriyo_rest_prepare_date_response( $attachment->post_date_gmt ),
				);
				return $result;
			},
			array()
		);

		/**
		 * Lesson attachment filter.
		 *
		 * @since 2.0.2
		 *
		 * @return array[] $download_materials Download materials.
		 * @param Masteriyo\Models\Lesson $lesson Lesson object.
		 * @param string $context Context.
		 */
		return apply_filters( "masteriyo_rest_{$this->object_type}_download_materials", $download_materials, $lesson, $context );
	}

	/**
	 * Check whether the current user can access download materials for the lesson.
	 *
	 * @param \Masteriyo\Models\Lesson $lesson Lesson object.
	 * @param string $context Request context.
	 *
	 * @return bool
	 */
	protected function user_can_access_download_materials( $lesson, $context, $course = null ) {
		if ( is_null( $course ) ) {
			$course = masteriyo_get_course( $lesson->get_course_id( $context ) );
		}

		return masteriyo_can_access_lesson_download_materials( $lesson, $course );
	}

	/**
	 * Get a message explaining why download materials are restricted and how to get access.
	 *
	 * Returns an empty string when the current user already has access.
	 *
	 * @param \Masteriyo\Models\Lesson $lesson Lesson object.
	 * @param string $context Request context.
	 *
	 * @return string
	 */
	protected function get_download_materials_message( $lesson, $context, $course = null ) {
		if ( is_null( $course ) ) {
			$course = masteriyo_get_course( $lesson->get_course_id( $context ) );
		}

		if ( $this->user_can_access_download_materials( $lesson, $context, $course ) ) {
			return '';
		}

		if ( empty( $lesson->get_download_materials( $context ) ) ) {
			return '';
		}

		if ( ! $course ) {
			return '';
		}

		$user_id     = get_current_user_id();
		$access_mode = $course->get_access_mode();

		if ( ! $user_id ) {
			if ( CourseAccessMode::NEED_REGISTRATION === $access_mode ) {
				return __( 'Register and enroll to access download materials.', 'learning-management-system' );
			}
			return __( 'Log in and enroll to access download materials.', 'learning-management-system' );
		}

		if ( in_array( $access_mode, array( CourseAccessMode::ONE_TIME, CourseAccessMode::RECURRING ), true ) ) {
			return __( 'Purchase this course to access download materials.', 'learning-management-system' );
		}

		return __( 'Enroll in this course to access download materials.', 'learning-management-system' );
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
		$course_id = absint( $request['course_id'] );
		$post      = get_post( $course_id );

		if ( is_null( $post ) || PostType::COURSE !== $post->post_type ) {
			return new \WP_Error(
				"masteriyo_rest_{$this->post_type}_invalid_id",
				__( 'Invalid Course ID', 'learning-management-system' ),
				array(
					'status' => 404,
				)
			);
		}

		return parent::create_item_permissions_check( $request );
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @since 2.7.1
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return boolean|\WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$lesson = masteriyo_get_lesson( $request['id'] );

		if ( is_null( $lesson ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_lesson_id',
				__( 'Invalid lesson ID.', 'learning-management-system' ),
				array(
					'status' => 400,
				)
			);
		}

		$course = masteriyo_get_course( $lesson->get_course_id() );

		if ( is_null( $course ) ) {
			return new \WP_Error(
				'masteriyo_rest_invalid_course_id',
				__( 'Invalid course ID.', 'learning-management-system' ),
				array(
					'status' => 400,
				)
			);
		}

		// Restrict access to other course content while a quiz attempt is in progress.
		// Placed before the open-access/preview short-circuits so it always applies to enrolled students.
		$restriction = masteriyo_check_content_restriction_during_quiz( $course, $request['id'] );
		if ( is_wp_error( $restriction ) ) {
			return $restriction;
		}

		if ( ( new Addons() )->is_active( 'multiple-instructors' ) ) {
			if ( masteriyo_is_instructor_or_additional_instructor( $course->get_id() ) ) {
				return true;
			}
		}

		if ( ! user_can( get_current_user_id(), 'edit_course', $course->get_id() ) && ( ! in_array( $course->get_status(), array( PostStatus::PUBLISH, PostStatus::PVT ), true ) || post_password_required( get_post( $course->get_id() ) ) ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_read',
				__( 'Sorry, you are not allowed to read resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( CourseAccessMode::OPEN === $course->get_access_mode() || $lesson->get_enable_preview() ) {
			return true;
		}

		if ( is_user_logged_in() && ! masteriyo_is_current_user_admin() && ! masteriyo_is_current_user_instructor() && ! masteriyo_can_start_course( $course ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_start_course',
				__( 'Sorry, you have not bought the course.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_post_permissions( $this->post_type, 'read', $request['id'] ) ) {
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
		 * Deletes the video metadata for a given video source URL.
		 *
		 * @since 2.13.0
		 *
		 * @param string $old_video_source_url The old video source URL.
		 * @param string $new_video_source_url The new video source URL.
		 * @param \Masteriyo\Models\Lesson $lesson The lesson object.
		 */
	private function delete_video_video_meta_data( $old_video_source_url, $new_video_source_url, &$lesson ) {
		global $wpdb;

		try {
			$lesson->set_video_meta(
				array(
					'time_stamps'               => array(),
					'enable_video_share'        => false,
					'enable_right_button_click' => false,
				)
			);

			$meta_keys = array( 'resume_time', 'notes' );

			$item_id   = $lesson->get_id();
			$item_type = 'lesson';

			$user_ids = $this->get_users_related_to_lesson( $item_id );

			foreach ( $user_ids as $user_id ) {
				foreach ( $meta_keys as $meta_key ) {
						$wpdb->query(
							$wpdb->prepare(
								"DELETE FROM {$wpdb->prefix}masteriyo_user_activitymeta
										WHERE user_activity_id = (
												SELECT id FROM {$wpdb->prefix}masteriyo_user_activities
												WHERE item_id = %d
												AND user_id = %d
												AND activity_type = %s
										)
										AND meta_key = %s",
								$item_id,
								$user_id,
								$item_type,
								$meta_key
							)
						);
				}
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}
	/**
		 * Deletes subtitle metadata for a given lesson and for specific subtitle id.
		 *
		 * @since 2.17.0
		 * @param $subtitle_id Subtitle Unique Id.
		 * @param \Masteriyo\Models\Lesson $lesson The lesson object.
		 */

	private function delete_video_subtitle_meta_data( $subtitle_ids, &$lesson ) {

		$existing_data = (array) $lesson->get_subtitle_meta( 'view' );

		$filtered_subtitles = array();
		foreach ( $existing_data as $subtitle ) {
			if ( ! in_array( $subtitle['subtitle_id'], $subtitle_ids ) ) {
				$filtered_subtitles[] = $subtitle;
			}
		}
		$lesson->set_subtitle_meta( 'subtitle_meta', $filtered_subtitles );
	}



	/**
	 * Retrieve all users related to a specific lesson.
	 *
	 * @since 2.13.0
	 *
	 * @param int $lesson_id The lesson ID.
	 *
	 * @return array The array of user IDs.
	 */
	private function get_users_related_to_lesson( $lesson_id ) {
		global $wpdb;

		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT user_id FROM {$wpdb->prefix}masteriyo_user_activities
					WHERE item_id = %d
					AND activity_type = %s",
				$lesson_id,
				'lesson'
			)
		);

		return $user_ids;
	}
}
