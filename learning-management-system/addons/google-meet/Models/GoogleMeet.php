<?php
/**
 * GoogleMeet model.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\Addons\GoogleMeet
 */

namespace Masteriyo\Addons\GoogleMeet\Models;

use Masteriyo\Addons\GoogleMeet\Enums\GoogleMeetStatus;
use Masteriyo\Addons\GoogleMeet\Enums\GoogleMeetType;
use Masteriyo\Addons\GoogleMeet\Repository\GoogleMeetRepository;
use Masteriyo\Database\Model;
use Masteriyo\DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Google Meet model (post type).
 *
 * @since 1.11.0
 */
class GoogleMeet extends Model {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $object_type = 'google-meet';

	/**
	 * Post type.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $post_type = 'mto-google-meet';

	/**
	 * Cache group.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $cache_group = 'google-meet';

	/**
	 * Stores google Meet data.
	 *
	 * @since 1.11.0
	 *
	 * @var array
	 */
	protected $data = array(
		'name'                         => '',
		'description'                  => '',
		'starts_at'                    => null,
		'ends_at'                      => null,
		'author_id'                    => 0,
		'course_id'                    => 0,
		'menu_order'                   => 0,
		'parent_id'                    => 0,
		'meeting_id'                   => '',
		'time_zone'                    => '',
		'created_at'                   => null,
		'calender_url'                 => '',
		'meet_url'                     => '',
		'type'                         => GoogleMeetType::SCHEDULED,
		'add_all_students_as_attendee' => false,
	);

	/**
	 * Get the Google Meet session if ID
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\GoogleMeet\Repository\GoogleMeetRepository $google_meet_repository Google Met Repository.
	*/
	public function __construct( GoogleMeetRepository $google_meet_repository ) {
		$this->repository = $google_meet_repository;
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the GoogleMeet session title. For courses this is the course name.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_title() {
		/**
		 * Filters GoogleMeet title.
		 *
		 * @since 1.11.0
		 *
		 * @param string $title GoogleMeet title.
		 * @param Masteriyo\Models|GoogleMeet $GoogleMeet GoogleMeet object.
		 */
		return apply_filters( 'masteriyo_google_meet_title', $this->get_name(), $this );
	}

	/**
	 * Get google_meet description.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		return $this->get_prop( 'description', $context );
	}

	/**
	 * google_meet permalink.
	 *
	 * @return string
	 */
	public function get_permalink() {
		return get_permalink( $this->get_id() );
	}

	/**
	 * Get the object type.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Get the post type.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Get post preview link.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_post_preview_link() {
		$preview_link = get_preview_post_link( $this->get_id() );

		/**
		 * google_meet post preview link.
		 *
		 * @since 1.11.0
		 *
		 * @param string $url Preview URL.
		 * @param Masteriyo\Models\google_meet $google_meet google_meet object.
		 */
		return apply_filters( 'masteriyo_google_meet_post_preview_link', $preview_link, $this );
	}

	/**
	 * Get preview link in learn page.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_preview_link() {
		$preview_link = '';
		$course       = masteriyo_get_course( $this->get_course_id() );

		if ( $course ) {
			$course_preview_link = $course->get_preview_link( false );
			$preview_link        = trailingslashit( $course_preview_link ) . 'google-meet/' . $this->get_id();
		}

		/**
		 * google_meet preview link for learn page.
		 *
		 * @since 1.11.0
		 *
		 * @param string $url Preview URL.
		 * @param \Masteriyo\Addons\google_meet\Models\google_meet $google_meet google_meet object.
		 */
		return apply_filters( 'masteriyo_google_meet_preview_link', $preview_link, $this );
	}

	/**
	 * Get icon.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_icon( $context = 'single-course.google_meet.section.content' ) {
		$icon = masteriyo_get_svg( 'google_meet' );

		/**
		 * Filters google_meet icon.
		 *
		 * @since 1.11.0
		 *
		 * @param string $icon.
		 * @param string $context.
		 */
		return apply_filters( 'masteriyo_google_meet_icon', $icon, $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get google_meet session name.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		/**
		 * Filters google_meet name.
		 *
		 * @since 1.11.0
		 *
		 * @param string $name google_meet name.
		 * @param \Masteriyo\Models|GoogleMeet $google_meet google_meet object.
		 */
		return apply_filters( 'masteriyo_google_meet_name', $this->get_prop( 'name', $context ), $this );
	}

	/**
	* Get google_meet session type.
	*
	* @since  1.11.0
	*
	* @param  string $context What the value is for. Valid values are view and edit.
	*
	* @return string
	*/
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * Get google_meet session created date.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_created_at( $context = 'view' ) {
		return $this->get_prop( 'created_at', $context );
	}

	/**
	 * Get google_meet session modified date.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_modified_at( $context = 'view' ) {
		return $this->get_prop( 'modified_at', $context );
	}



	/**
	 * Returns google_meet parent id.
	 *
	 * @since  1.11.0
	 *
	 * @param  integer $context What the value is for. Valid values are view and edit.
	 *
	 * @return integer
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Returns google_meet meeting id.
	 *
	 * @since  1.11.0
	 *
	 * @param  integer $context What the value is for. Valid values are view and edit.
	 *
	 * @return integer
	 */
	public function get_meeting_id( $context = 'view' ) {
		return $this->get_prop( 'meeting_id', $context );
	}

	/**
	 * Returns google_meet meeting id.
	 *
	 * @since  1.11.0
	 *
	 * @param  integer $context What the value is for. Valid values are view and edit.
	 *
	 * @return integer
	 */
	public function get_calender_url( $context = 'view' ) {
		return $this->get_prop( 'calender_url', $context );
	}

	/**
	 * Returns google_meet meeting id.
	 *
	 * @since  1.11.0
	 *
	 * @param  integer $context What the value is for. Valid values are view and edit.
	 *
	 * @return integer
	 */
	public function get_meet_url( $context = 'view' ) {
		return $this->get_prop( 'meet_url', $context );
	}

	/**
	 * Returns google_meet session menu order.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_menu_order( $context = 'view' ) {
		return $this->get_prop( 'menu_order', $context );
	}

	/**
	 * Get google_meet session status.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Get google_meet time zone.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_time_zone( $context = 'view' ) {
		return $this->get_prop( 'time_zone', $context );
	}

	/**
	 * Get google_meet session starts date in timestamp
	 *
	 * @since  1.11.0
	 *
	 * @param  integer $context What the value is for. Valid values are view and edit.
	 *
	 * @return DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_starts_at( $context = 'view' ) {
		return $this->get_prop( 'starts_at', $context );
	}

	/**
	 * Get google_meet session starts date in timestamp
	 *
	 * @since  1.11.0
	 *
	 * @param  integer $context What the value is for. Valid values are view and edit.
	 *
	 * @return DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_ends_at( $context = 'view' ) {
		return $this->get_prop( 'ends_at', $context );
	}

	/**
	 * Get google_meet course id.
	 *
	 * @since  1.11.0
	 *
	 * @param  integer $context What the value is for. Valid values are view and edit.
	 *
	 * @return integer
	 */
	public function get_course_id( $context = 'view' ) {
		return $this->get_prop( 'course_id', $context );
	}

	/**
	 * Returns the google_meet author id.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return integer
	 */
	public function get_author_id( $context = 'view' ) {
		return $this->get_prop( 'author_id', $context );
	}

	/**
	 * Returns the google_meet attendee value.
	 *
	 * @since  1.11.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return boolean
	 */
	public function get_add_all_students_as_attendee( $context = 'view' ) {
		return $this->get_prop( 'add_all_students_as_attendee', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set google_meet name.
	 *
	 * @since 1.11.0
	 *
	 * @param string $name google_meet name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set the google_meet author id.
	 *
	 * @since 1.11.0
	 *
	 * @param int $author_id author id.
	 */
	public function set_author_id( $author_id ) {
		$this->set_prop( 'author_id', absint( $author_id ) );
	}

	/**
	 * Set google_meet timezone.
	 *
	 * @since 1.11.0
	 *
	 * @param string $time_zone google_meet.
	 */
	public function set_time_zone( $time_zone ) {
		$this->set_prop( 'time_zone', $time_zone );
	}

	/**
	 * Set google_meet session created date.
	 *
	 * @since 1.11.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_created_at( $date ) {
		$this->set_date_prop( 'created_at', $date );
	}

	/**
	 * Set course id.
	 *
	 * @since 1.11.0
	 *
	 *  @param int $course_id course id.
	 */
	public function set_course_id( $course_id ) {
		$this->set_prop( 'course_id', absint( $course_id ) );
	}

	/**
	 * Set google_meet session modified date.
	 *
	 * @since 1.11.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_modified_at( $date ) {
		$this->set_date_prop( 'modified_at', $date );
	}

	/**
	 * Set google_meet session descriptions.
	 *
	 * @since 1.11.0
	 *
	 * @param string $description google_meet description.
	 */
	public function set_description( $description ) {
		$this->set_prop( 'description', $description );
	}

	/**
	 * Set the google_meet session parent id.
	 *
	 * @since 1.11.0
	 *
	 * @param int $parent Parent id.
	 */
	public function set_parent_id( $parent ) {
		$this->set_prop( 'parent_id', absint( $parent ) );
	}

	/**
	 * Set the google_meet session meeting id.
	 *
	 * @since 1.11.0
	 *
	 * @param int $meeting meeting id.
	 */
	public function set_meeting_id( $meeting ) {
		$this->set_prop( 'meeting_id', $meeting );
	}

	/**
	 * Set the google_meet session meeting id.
	 *
	 * @since 1.11.0
	 *
	 * @param int $meeting meeting id.
	 */
	public function set_calender_url( $url ) {
		$this->set_prop( 'calender_url', $url );
	}


	/**
	 * Set the google_meet session meeting id.
	 *
	 * @since 1.11.0
	 *
	 * @param int $meeting meeting id.
	 */
	public function set_meet_url( $url ) {
		$this->set_prop( 'meet_url', $url );
	}


	/**
	 * Set the google_meet session menu order.
	 *
	 * @since 1.11.0
	 *
	 * @param int $menu_order Menu order id.
	 */
	public function set_menu_order( $menu_order ) {
		$this->set_prop( 'menu_order', absint( $menu_order ) );
	}

	/**
	 * Set google_meet session status.
	 *
	 * @since 1.11.0
	 *
	 * @param string $status google_meet status.
	 */
	public function set_status( $status ) {
		$this->set_prop( 'status', $status );
	}

	/**
	 * Set google_meet session type.
	 *
	 * @since 1.11.0
	 *
	 * @param string $type google_meet type.
	 */
	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Set google_meet expiring time.
	 *
	 * @since 1.11.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_expires_at( $expires_at ) {
		$this->set_date_prop( 'expires_at', $expires_at, $this->get_time_zone() );
	}

	/**
	 * Set google_meet start time.
	 *
	 * @since 1.11.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_starts_at( $starts_at ) {
		$this->set_prop( 'starts_at', $starts_at );
	}

	/**
	 * Set google_meet end time.
	 *
	 * @since 1.11.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_ends_at( $ends_at ) {
		$this->set_prop( 'ends_at', $ends_at );
	}

	/**
	 * Set the google_meet attendee data.
	 *
	 * @since  1.11.0
	 *
	 * @param  boolean $add_all_students_as_attendee What the value is. Valid values are true and false.
	 *
	 */
	public function set_add_all_students_as_attendee( $add_all_students_as_attendee ) {
		$this->set_prop( 'add_all_students_as_attendee', $add_all_students_as_attendee );
	}
}
