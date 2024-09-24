<?php

/**
 * Google Meet Controller class.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\Addons\GoogleMeet\RestApi
 */

namespace Masteriyo\Addons\GoogleMeet\RestApi;

defined( 'ABSPATH' ) || exit;

use DateTimeZone;
use League\OAuth2\Client\Grant\RefreshToken;
use Masteriyo\Addons\GoogleMeet\Enums\GoogleMeetStatus;
use Masteriyo\Addons\GoogleMeet\Models\GoogleMeetSetting;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\SectionChildrenPostType;
use Masteriyo\Helper\Permission;
use Masteriyo\RestApi\Controllers\Version1\PostsController;
use GuzzleHttp\Client;
use Masteriyo\DateTime;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CourseChildrenPostType;
use Masteriyo\PostType\PostType;
use WP_REST_Request;

use function cli\err;

/**
 * GoogleMeetController class.
 */
class GoogleMeetController extends PostsController {
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $rest_base = 'google-meet';

	/**
	 * Post type.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $post_type = 'mto-google-meet';

	/**
	 * Object type.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $object_type = 'google-meet';

	/**
	 * If object is hierarchical.
	 *
	 * @since 1.11.0
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

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
					'callback'            => array( $this, 'create_meeting' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                =>
					$this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
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
							'description' => __( 'Meet IDs.', 'learning-management-system' ),
							'type'        => 'array',
						),
						'force' => array(
							'default'     => true,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learning-management-system' ),
							'type'        => 'boolean',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w\s\S]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
						'type'        => 'string',
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
					'callback'            => array( $this, 'update_meeting' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_meeting' ),
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
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @since 1.11.0
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

		if ( ! current_user_can( 'get_google-meets' ) ) {
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
	 * Check if a given request has access to read items.
	 *
	 * @since 1.11.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
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
		if ( isset( $post ) ) {
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
	 * @since 1.11.0
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

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() || masteriyo_is_current_user_instructor() ) {
			return true;
		}

		if ( ! current_user_can( 'edit_google-meets' ) ) {
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
	 * Check if a given request has access to read items.
	 *
	 * @since 1.11.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_items_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() || masteriyo_is_current_user_instructor() ) {
			return true;
		}

		if ( ! current_user_can( 'edit_google-meets' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete resources.', 'learning-management-system' ),
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
	 * Check if a given request has access to delete an item.
	 *
	 * @since 1.11.0
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

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() || masteriyo_is_current_user_instructor() ) {
			return true;
		}

		if ( ! current_user_can( 'edit_google-meets' ) ) {
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
	 * Get the Google Meet meetings from Google Calendar.
	 *
	 * @since 1.11.0
	 */
	public function get_google_meet( $request ) {
		if ( $request ) {
			$google_setting_data = ( new GoogleMeetSetting() )->get_data();
			$google_provider     = create_google_meet_client( $google_setting_data );

			if ( $google_setting_data['refresh_token'] ) {
				$grant              = new RefreshToken();
				$token              = $google_provider->getAccessToken( $grant, array( 'refresh_token' => $google_setting_data['refresh_token'] ) );
				$data_from_calendar = masteriyo_google_calendar_meeting_data_insertion( $token, $google_provider );
				update_option( 'masteriyo_google_calendar_data_' . masteriyo_get_current_user_id(), $data_from_calendar );
			}
		} else {
			$data_from_calendar = get_option( 'masteriyo_google_calendar_data_' . masteriyo_get_current_user_id() );
		}

		if ( ! empty( $data_from_calendar['meetings'] ) ) {
			$google_meetings = array();
			foreach ( $data_from_calendar['meetings'] as $google_meeting ) {
				if ( isset( $google_meeting['id'] ) && $google_meeting['id'] === $request['id'] ) {

					$new_request = new WP_REST_Request( 'GET' );
					$new_request->set_query_params(
						array(
							'offset'         => isset( $request['offset'] ) ? $request['offset'] : 0,
							'order'          => isset( $request['order'] ) ? $request['order'] : 'DESC',
							'orderby'        => isset( $request['orderby'] ) ? $request['orderby'] : 'date',
							'paged'          => isset( $request['paged'] ) ? $request['paged'] : 1,
							'posts_per_page' => isset( $request['post_per_page'] ) ? $request['post_per_page'] : 10,
						)
					);

					$meeting_data = $this->get_items( $new_request );

					if ( ! empty( $meeting_data ) ) {
						$meeting_data     = (array) $meeting_data;
						$meeting_data_new = $meeting_data['data']['data'];
						foreach ( $meeting_data_new as $meeting ) {
							if ( $meeting['meeting_id'] === $request['id'] ) {
								$meeting             = (array) $meeting;
								$google_meeting_data = array_merge( $google_meeting, $meeting );
								return $google_meeting_data;
							}
						}
					}
				}
			}
		}

		return array();
	}

	/**
	 * Get the Google Meet meetings from Google Calendar.
	 *
	 * @since 1.11.0
	 */
	public function get_google_meetings( $request ) {
		if ( $request ) {
			$google_setting_data = ( new GoogleMeetSetting() )->get_data();
			$google_provider     = create_google_meet_client( $google_setting_data ); // Adjust this function according to your implementation.

			if ( $google_setting_data['refresh_token'] ) {
				$grant              = new RefreshToken();
				$token              = $google_provider->getAccessToken( $grant, array( 'refresh_token' => $google_setting_data['refresh_token'] ) );
				$data_from_calendar = masteriyo_google_calendar_meeting_data_insertion( $token, $google_provider );
				update_option( 'masteriyo_google_calendar_data_' . masteriyo_get_current_user_id(), $data_from_calendar );
			}
		} else {
			$data_from_calendar = get_option( 'masteriyo_google_calendar_data_' . masteriyo_get_current_user_id() );
		}

		if ( ! empty( $data_from_calendar['meetings'] ) ) {
			$google_meetings = array();
			foreach ( $data_from_calendar['meetings'] as $google_meeting ) {

				$meeting_data = $this->get_items( $request );

				$meeting_data = (array) $meeting_data;

				$google_meetings[] = array_merge( $google_meeting, $meeting_data );

			}

			return $google_meetings;
		}

		return array();
	}

	/**
	 * Get course child data.
	 *
	 * @since 1.11.0
	 *
	 * @param Model $course_item Course instance.
	 * @param string     $context Request context.
	 *                            Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	public function get_course_child_data( $course_item, $context = 'view' ) {
		$data = array(
			'id'          => $course_item->get_id(),
			'name'        => wp_specialchars_decode( $course_item->get_name( $context ) ),
			'name'        => $course_item->get_name( $context ),
			'description' => $course_item->get_description( $context ),
			'type'        => $course_item->get_object_type(),
			'menu_order'  => $course_item->get_menu_order( $context ),
			'parent_id'   => $course_item->get_parent_id( $context ),
			'author'      => $course_item->get_author_id( $context ),
			'status'      => $course_item->get_status( $context ),
		);

		if ( 'mto-lesson' === $course_item->get_post_type() ) {
			$data['video'] = ! empty( $course_item->get_video_source_url() );
		}

		return $data;
	}

	/**
	 * Create a Google Meet meeting in Google Calendar.
	 *
	 * @since 1.11.0
	 */
	public function create_meeting( $request ) {
		$google_setting_data = ( new GoogleMeetSetting() )->get_data();
		$google_provider     = create_google_meet_client( $google_setting_data );

		if ( $google_setting_data['refresh_token'] ) {
			$grant = new RefreshToken();
			$token = $google_provider->getAccessToken( $grant, array( 'refresh_token' => $google_setting_data['refresh_token'] ) );

			$response = $this->create_google_calendar_event( $token, $google_provider, $request );

			return $response;
		}

		return false;
	}

	/**
	 * Delete a Google Meet meeting in Google Calendar.
	 *
	 * @since 1.11.0
	 */
	public function delete_meeting( $request ) {
		$meta_meeting_id               = array();
		$meta_meeting_id['id']         = get_post_meta( $request['id'], '_meeting_id', true );
		$meta_meeting_id['meeting_id'] = $request['id'];
		$google_setting_data           = ( new GoogleMeetSetting() )->get_data();
		$google_provider               = create_google_meet_client( $google_setting_data );

		if ( $google_setting_data['refresh_token'] ) {
			$grant = new RefreshToken();
			$token = $google_provider->getAccessToken( $grant, array( 'refresh_token' => $google_setting_data['refresh_token'] ) );

			$data_from_calender = $this->get_google_meet( $meta_meeting_id );

			$response = $this->delete_google_calendar_event( $token, $google_provider, $meta_meeting_id, $data_from_calender );

			return $response;
		}

		return false;
	}

	/**
	 * Delete a Google Meet meeting in Google Calendar.
	 *
	 * @since 1.11.0
	 */
	public function delete_items( $request ) {

		if ( ! isset( $request['ids'] ) || ! is_array( $request['ids'] ) ) {
			return false;
		}

		$meeting_ids = array_map( 'sanitize_text_field', $request['ids'] );

		foreach ( $meeting_ids as $meet_id ) {
			$meta_meeting_id = array(
				'id'         => get_post_meta( $meet_id, '_meeting_id', true ),
				'meeting_id' => $meet_id,
			);

			$google_setting_data = ( new GoogleMeetSetting() )->get_data();

			$google_provider = create_google_meet_client( $google_setting_data );

			if ( ! empty( $google_setting_data['refresh_token'] ) ) {
				$grant = new RefreshToken();
				$token = $google_provider->getAccessToken( $grant, array( 'refresh_token' => $google_setting_data['refresh_token'] ) );

				$data_from_calender = $this->get_google_meet( $meta_meeting_id );

				$response = $this->delete_google_calendar_events( $token, $google_provider, $meta_meeting_id, $data_from_calender );
			}
		}
		return true;
	}

	/**
	 * Create a Google Meet meeting in Google Calendar.
	 *
	 * @since 1.11.0
	 */
	public function update_meeting( $request ) {

		$google_setting_data = ( new GoogleMeetSetting() )->get_data();
		$google_provider     = create_google_meet_client( $google_setting_data );

		if ( $google_setting_data['refresh_token'] ) {
			$grant = new RefreshToken();
			$token = $google_provider->getAccessToken( $grant, array( 'refresh_token' => $google_setting_data['refresh_token'] ) );

			$response = $this->update_google_calendar_event( $token, $google_provider, $request );

			return $response;
		}

		return false;
	}

	/**
	 * Delete a Google Meet event in Google Calendar by ID.
	 *
	 * @since 1.11.0
	 */
	public function update_google_calendar_event( $token, $provider, $request ) {

		$start_date_time = new DateTime( $request['starts_at'] );
		$end_date_time   = new DateTime( $request['ends_at'] );

		$start_date_time->setTimeZone( new DateTimeZone( $request['time_zone'] ) );
		$end_date_time->setTimeZone( new DateTimeZone( $request['time_zone'] ) );

		$participants = array();

		if ( $request['add_all_students_as_attendee'] ) {
			$students = $request['attendees'];
			foreach ( $students as $student ) {
				if ( masteriyo_is_user_enrolled_in_course( $request['course_id'], $student ) ) {
					$user           = masteriyo_get_user( $student );
					$participants[] = array( 'email' => $user->get_email() );
				}
			}
		} else {
			$participants[] = array();
		}

		$event_data = array(
			'summary'     => $request['summary'],
			'description' => $request['description'],
			'start'       => array(
				'dateTime' => $start_date_time->format( 'Y-m-d\TH:i:s' ),
				'timeZone' => $request['time_zone'],
			),
			'end'         => array(
				'dateTime' => $end_date_time->format( 'Y-m-d\TH:i:s' ),
				'timeZone' => $request['time_zone'],
			),
			'attendees'   => $participants,
		);

		$calendar_id = 'primary';
		$endpoint    = sprintf(
			'https://www.googleapis.com/calendar/v3/calendars/%s/events/%s',
			$calendar_id,
			$request['id']
		);

			$client = new Client();

			$response = $client->request(
				'PATCH',
				$endpoint,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token->getToken(),
						'Content-Type'  => 'application/json',
					),
					'json'    => $event_data,
				)
			);

		if ( $response->getStatusCode() === 200 ) {
			$event      = json_decode( $response->getBody(), true );
			$event_data = array(
				'id'                           => $request['meeting_id'],
				'event_id'                     => $event['id'],
				'summary'                      => $event['summary'],
				'description'                  => $event['description'],
				'starts_at'                    => $event['start']['dateTime'],
				'ends_at'                      => $event['end']['dateTime'],
				'author'                       => $event['id'],
				'calender_url'                 => $event['htmlLink'],
				'time_zone'                    => $event['start']['timeZone'],
				'meet_url'                     => $event['hangoutLink'],
				'add_all_students_as_attendee' => $request['add_all_students_as_attendee'],
			);
			$this->save_object( $event_data );
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Delete a Google Meet event in Google Calendar by ID.
	 *
	 * @since 1.11.0
	 */
	public function delete_google_calendar_event( $token, $provider, $event_id, $data_from_calendar ) {
		$calendar_id = 'primary';
		$endpoint    = sprintf(
			'https://www.googleapis.com/calendar/v3/calendars/%s/events/%s',
			$calendar_id,
			$event_id['id']
		);

		$client = new Client();

		$response = $client->request(
			'DELETE',
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token->getToken(),
					'Content-Type'  => 'application/json',
				),
			)
		);

		if ( $response->getStatusCode() === 204 ) {
			if ( ! $event_id || 0 === $event_id['meeting_id'] ) {
				return new \WP_Error( "masteriyo_rest_{$this->object_type}_invalid_id", __( 'Invalid ID', 'learning-management-system' ), array( 'status' => 404 ) );
			}
			wp_delete_post( $event_id['meeting_id'] );
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Delete a Google Meet event in Google Calendar by ID.
	 *
	 * @since 1.11.0
	 */
	public function delete_google_calendar_events( $token, $provider, $event_id, $data_from_calendar ) {
		$calendar_id = 'primary';
		$endpoint    = sprintf(
			'https://www.googleapis.com/calendar/v3/calendars/%s/events/%s',
			$calendar_id,
			$event_id['id']
		);

		$client = new Client();

		$response = $client->request(
			'DELETE',
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token->getToken(),
					'Content-Type'  => 'application/json',
				),
			)
		);

		if ( $response->getStatusCode() === 204 ) {
			wp_delete_post( $event_id['meeting_id'] );
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Create a Google Meet event in Google Calendar.
	 *
	 * @since 1.11.0
	 */
	public function create_google_calendar_event( $token, $provider, $request ) {

		$course = masteriyo_get_course( $request['course_id'] );

		if ( empty( $course ) ) {
			return false;
		}

		$participants = array();

		if ( $request['add_all_students_as_attendee'] ) {
			$students = $request['attendees'];
			foreach ( $students as $student ) {
				if ( masteriyo_is_user_enrolled_in_course( $request['course_id'], $student ) ) {
					$user           = masteriyo_get_user( $student );
					$participants[] = array( 'email' => $user->get_email() );
				}
			}
		} else {
			$participants[] = array();
		}

		$request_id = uniqid( '', true );

		$conference_data = array(
			'createRequest' => array(
				'requestId'             => $request_id,
				'conferenceSolutionKey' => array(
					'type' => 'hangoutsMeet',
				),
			),
		);

			$event_data = array(
				'summary'        => $request['summary'],
				'description'    => $request['description'],
				'start'          => array(
					'dateTime' => $request['starts_at'],
					'timeZone' => $request['time_zone'],
				),
				'end'            => array(
					'dateTime' => $request['ends_at'],
					'timeZone' => $request['time_zone'],
				),
				'conferenceData' => $conference_data,
				'attendees'      => $participants,
				'reminders'      => array(
					'useDefault' => false,
					'overrides'  => array(
						array(
							'method'  => 'email',
							'minutes' => 24 * 60,
						),
						array(
							'method'  => 'popup',
							'minutes' => 10,
						),
					),
				),
			);

			$endpoint = 'https://www.googleapis.com/calendar/v3/calendars/{calendarId}/events?conferenceDataVersion=1';

			$calendar_id = 'primary';

			$endpoint = str_replace( '{calendarId}', $calendar_id, $endpoint );

			$client = new Client();

			$response = $client->request(
				'POST',
				$endpoint,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token->getToken(),
						'Content-Type'  => 'application/json',
					),
					'json'    => $event_data,
				)
			);

		if ( $response->getStatusCode() === 200 ) {
			$event = json_decode( $response->getBody(), true );

			$event_data = array(
				'event_id'                     => $event['id'],
				'summary'                      => $event['summary'],
				'description'                  => $event['description'],
				'starts_at'                    => $event['start']['dateTime'],
				'ends_at'                      => $event['end']['dateTime'],
				'author'                       => $event['id'],
				'calender_url'                 => $event['htmlLink'],
				'time_zone'                    => $event['start']['timeZone'],
				'meet_url'                     => $event['hangoutLink'],
				'parent_id'                    => $request['section_id'],
				'course_id'                    => $course->get_id(),
				'add_all_students_as_attendee' => $request['add_all_students_as_attendee'],
			);

			if ( ! empty( $event_data['id'] ) ) {
				/* translators: %s: post type */
				return new \WP_Error( "masteriyo_rest_{$this->object_type}_exists", sprintf( __( 'Cannot create existing %s.', 'learning-management-system' ), $this->object_type ), array( 'status' => 400 ) );
			}

			$this->save_object( $event_data, true );
			return $event;
		} else {
			return false;
		}
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		// The GoogleMeet session should be order by menu which is the sort order.
		$params['order']['default']   = 'asc';
		$params['orderby']['default'] = 'menu_order';

		return $params;
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

		if ( ! current_user_can( 'publish_google-meets' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to create resources.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
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

		if ( $course->get_author_id() !== get_current_user_id() ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to create google_meet session for others course.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Get object.
	 *
	 * @since 1.11.0
	 *
	 * @param  int|Model|WP_Post $object Object ID or Model or WP_Post object.
	 * @return object Model object or WP_Error object.
	 */
	protected function get_object( $object ) {
		try {
			if ( is_int( $object ) ) {
				$id = $object;
			} else {
				$id = is_a( $object, '\WP_Post' ) ? $object->ID : $object->get_id();
			}
			$google_meet = masteriyo( 'google-meet' );
			$google_meet->set_id( $id );
			$google_meet_repo = masteriyo( 'google-meet.store' );
			$google_meet_repo->read( $google_meet );
		} catch ( \Exception $e ) {
			return false;
		}

		return $google_meet;
	}

	/**
	 * Prepares the object for the REST response.
	 *
	 * @since  1.11.0
	 *
	 * @param  Masteriyo\Database\Model $object  Model object.
	 * @param  WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	protected function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data     = $this->get_google_meet_data( $object, $context );
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
		 * @since 1.11.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Masteriyo\Database\Model $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "masteriyo_rest_prepare_{$this->object_type}_object", $response, $object, $request );
	}

	/**
	 * Get the GoogleMeets'schema, conforming to JSON Schema.
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
				'id'                           => array(
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'summary'                      => array(
					'description' => __( 'Meeting Name', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'description'                  => array(
					'description' => __( 'Meeting Description.', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'time_zone'                    => array(
					'description' => __( 'User TimeZone', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'starts_at'                    => array(
					'description' => __( 'Meeting start time', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'ends_at'                      => array(
					'description' => __( 'Meeting end time', 'learning-management-system' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'participants'                 => array(
					'description' => __( 'Allow user to join before host.', 'learning-management-system' ),
					'type'        => array(),
					'context'     => array( 'view', 'edit' ),
				),
				'parent_id'                    => array(
					'description' => __( 'Google Meet parent ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'course_id'                    => array(
					'description' => __( 'Course ID', 'learning-management-system' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'add_all_students_as_attendee' => array(
					'description' => __( 'Add all students as attendee.', 'learning-management-system' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Process objects collection.
	 *
	 * @since 1.11.0
	 *
	 * @param array $objects GoogleMeet data.
	 * @param array $query_args Query arguments.
	 * @param array $query_results GoogleMeet query result data.
	 *
	 * @return array
	 */
	protected function process_objects_collection( $objects, $query_args, $query_results ) {
		return array(
			'data' => $objects,
			'meta' => array(
				'total'            => $query_results['total'],
				'pages'            => $query_results['pages'],
				'current_page'     => $query_args['paged'],
				'per_page'         => $query_args['posts_per_page'],
				'googleMeetCounts' => $this->get_google_meet_counts(),
			),
		);
	}

	/**
	 * Get GoogleMeet count by status.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	protected function get_google_meet_counts() {
		$post_count = $this->get_google_meet_count();
		return masteriyo_array_only( $post_count, array_merge( array( 'any' ), GoogleMeetStatus::all() ) );

	}

	/**
	 * Get GoogleMeet sessions count by status.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	protected function get_google_meet_count() {
		$post_count        = parent::get_posts_count();
		$post_count['all'] = array_sum( array( $post_count[ GoogleMeetStatus::PUBLISH ] ) );

		/**
		 * Filters the GoogleMeet counts.
		 *
		 * @since 1.11.0
		 *
		 * @param array $post_count GoogleMeet count.
		 * @param \Masteriyo\RestApi\Controllers\Version1\PostsController $controller Posts Controller.
		 */
		return apply_filters( "masteriyo_rest_{$this->object_type}_count", $post_count, $this );
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  1.11.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		if ( ! empty( $request['status'] ) ) {
			if ( GoogleMeetStatus::UPCOMING === $request['status'] ) {

				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => '_starts_at',
						'value'   => time(),
						'type'    => 'numeric',
						'compare' => '>',
					),
				);

			} elseif ( GoogleMeetStatus::EXPIRED === $request['status'] ) {

				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => '_ends_at',
						'value'   => time(),
						'type'    => 'numeric',
						'compare' => '<',
					),
				);
			} elseif ( GoogleMeetStatus::ACTIVE === $request['status'] ) {

				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => '_starts_at',
						'value'   => time(),
						'type'    => 'numeric',
						'compare' => '<',
					),
					array(
						'key'     => '_ends_at',
						'value'   => time(),
						'type'    => 'numeric',
						'compare' => '>',
					),
				);

			}
		}

		if ( masteriyo_is_current_user_instructor() ) {
			$args['author__in'] = array( get_current_user_id() );
		}

		$args['posts_per_page'] = isset( $request['posts_per_page'] ) ? $request['posts_per_page'] : 10;
		$args['paged']          = isset( $request['paged'] ) ? $request['paged'] : 1;

		return $args;
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

		$query_args['post_status'] = GoogleMeetStatus::all();

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
	 * Add new GoogleMeet session.
	 *
	 * @since 1.11.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return WP_Error|\Masteriyo\Addons\GoogleMeet\Models\GoogleMeet
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {

		$id          = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$google_meet = masteriyo( 'google-meet' );

		if ( 0 !== $id ) {
			$google_meet->set_id( $id );
			$google_meet_repo = masteriyo( 'google-meet.store' );
			$google_meet_repo->read( $google_meet );
		}

		// GoogleMeet meeting name
		if ( isset( $request['summary'] ) ) {
			$google_meet->set_name( $request['summary'] );
		}

		// GoogleMeet meeting description
		if ( isset( $request['description'] ) ) {
			$google_meet->set_description( wp_slash( $request['description'] ) );
		}

		if ( isset( $request['author'] ) ) {
			$google_meet->set_author_id( $request['author'] );
		}

		if ( isset( $request['event_id'] ) ) {
			$google_meet->set_meeting_id( $request['event_id'] );
		}

		if ( isset( $request['menu_order'] ) ) {
			$google_meet->set_menu_order( $request['menu_order'] );
		}

		if ( ! isset( $request['menu_order'] ) && $creating ) {
			$query            = new \WP_Query(
				array(
					'post_type'      => SectionChildrenPostType::all(),
					'post_status'    => PostStatus::all(),
					'posts_per_page' => 10,
					'post_parent'    => $request['parent_id'],
				)
			);
			$menu_order_count = $query->found_posts;
			$google_meet->set_menu_order( $menu_order_count );
		}

		// // GoogleMeet meeting parent id
		if ( isset( $request['parent_id'] ) ) {
			$google_meet->set_parent_id( $request['parent_id'] );
		}

		// GoogleMeet meeting course id
		if ( isset( $request['course_id'] ) ) {
			$google_meet->set_course_id( $request['course_id'] );
		}

		// GoogleMeet meeting time zone
		if ( isset( $request['time_zone'] ) ) {
			$google_meet->set_time_zone( $request['time_zone'] );
		}

		// GoogleMeet meeting start time
		if ( isset( $request['starts_at'] ) ) {
			$starts_at = sanitize_text_field( $request['starts_at'] );
			$google_meet->set_starts_at( $starts_at );
		}

		if ( isset( $request['ends_at'] ) ) {
			$ends_at = sanitize_text_field( $request['ends_at'] );
			$google_meet->set_ends_at( $ends_at );
		}

		if ( isset( $request['calender_url'] ) ) {
			$google_meet->set_calender_url( $request['calender_url'] );
		}

		if ( isset( $request['meet_url'] ) ) {
			$google_meet->set_meet_url( $request['meet_url'] );
		}

		if ( isset( $request['add_all_students_as_attendee'] ) ) {
			$google_meet->set_add_all_students_as_attendee( $request['add_all_students_as_attendee'] );
		}

		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				$google_meet->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @since 1.11.0
		 *
		 * @param Masteriyo\Database\Model $google_meet GoogleMeet object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "masteriyo_rest_pre_insert_{$this->post_type}_object", $google_meet, $request, $creating );
	}

	/**
	 * Get GoogleMeet data.
	 *
	 * @since 1.11.0
	 *
	 * @param Masteriyo\Models\GoogleMeet $google_meet GoogleMeet instance.
	 * @param string  $context Request context.
	 * Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_google_meet_data( $google_meet, $context = 'view' ) {
		$section = masteriyo_get_section( $google_meet->get_parent_id() );
		$author  = masteriyo_get_user( $google_meet->get_author_id( $context ) );
		$author  = is_wp_error( $author ) || is_null( $author ) ? null : array(
			'id'           => $author->get_id(),
			'display_name' => $author->get_display_name(),
			'avatar_url'   => $author->get_avatar_url(),
		);

		$course = masteriyo_get_course( $google_meet->get_course_id( $context ) );
		$data   = array(
			'id'                           => $google_meet->get_id(),
			'name'                         => wp_specialchars_decode( $google_meet->get_name( $context ) ),
			'permalink'                    => $google_meet->get_permalink( $context ),
			'preview_link'                 => $google_meet->get_preview_link(),
			'menu_order'                   => $google_meet->get_menu_order( $context ),
			'parent_menu_order'            => $section ? $section->get_menu_order( $context ) : 0,
			'description'                  => $google_meet->get_description( $context ),
			'parent_id'                    => $google_meet->get_parent_id( $context ),
			'course_id'                    => $google_meet->get_course_id( $context ),
			'created_at'                   => masteriyo_rest_prepare_date_response( $google_meet->get_created_at( $context ) ),
			'course_name'                  => $course ? $course->get_name() : '',
			'course_permalink'             => $course ? $course->get_permalink() : '',
			'starts_at'                    => masteriyo_rest_prepare_date_response( $google_meet->get_starts_at( $context ) ),
			'ends_at'                      => masteriyo_rest_prepare_date_response( $google_meet->get_ends_at( $context ) ),
			'time_zone'                    => $google_meet->get_time_zone( $context ),
			'meeting_id'                   => $google_meet->get_meeting_id( $google_meet, $context ),
			'calender_url'                 => $google_meet->get_calender_url( $context ),
			'meet_url'                     => $google_meet->get_meet_url( $google_meet, $context ),
			'author'                       => $author,
			'navigation'                   => $this->get_navigation_items( $google_meet, $context ),
			'add_all_students_as_attendee' => masteriyo_string_to_bool( $google_meet->get_add_all_students_as_attendee( $context ) ),
		);

		/**
		 * Filter google_meet rest response data.
		 *
		 * @since 1.11.0
		 *
		 * @param array $data google_meet data.
		 * @param Masteriyo\Models\GoogleMeet $google_meet google_meet object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param Masteriyo\RestApi\Controllers\Version1\GoogleMeetController $controller REST google_meets controller object.
		 */
		return apply_filters( "masteriyo_rest_response_{$this->object_type}_data", $data, $google_meet, $context, $this );
	}
}
