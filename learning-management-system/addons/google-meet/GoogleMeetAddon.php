<?php
/**
 * Masteriyo Google Meet setup.
 *
 * @package Masteriyo\GoogleMeet
 *
 * @since 1.11.0
 */

namespace Masteriyo\Addons\GoogleMeet;

use Masteriyo\Addons\GoogleMeet\Models\GoogleMeetSetting;
use Masteriyo\Addons\GoogleMeet\Models\Setting;
use Masteriyo\PostType\PostType;
use Masteriyo\Addons\GoogleMeet\PostType\GoogleMeet;
use Masteriyo\Addons\GoogleMeet\RestApi\GoogleMeetSettingController;
use Masteriyo\Addons\GoogleMeet\RestApi\GoogleMeetController;
use Masteriyo\Enums\PostStatus;

use function cli\err;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo Google Meet class.
 *
 * @class Masteriyo\Addons\GoogleMeet\GoogleMeetAddon
 */

class GoogleMeetAddon {

	/**
	 * @var Setting
	 *
	 * @since 1.11.0
	 */
	public $setting;

	/**
	 * constructor
	 *
	 * @since 1.11.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize the application.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.11.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_admin_submenus', array( $this, 'register_google_meet_submenu' ) );
		add_filter( 'masteriyo_register_post_types', array( $this, 'register_post_type' ) );
		add_filter( 'masteriyo_rest_api_get_rest_namespaces', array( $this, 'register_rest_namespaces' ) );

		add_filter( 'masteriyo_course_children_post_types', array( $this, 'include_google_meet_post_type' ) );
		add_filter( 'masteriyo_course_progress_post_types', array( $this, 'include_google_meet_post_type' ) );
		add_filter( 'masteriyo_section_children_post_types', array( $this, 'include_google_meet_post_type' ) );

		add_filter( 'masteriyo_course_progress_item_types', array( $this, 'include_google_meet_item_type' ) );
		add_filter( 'masteriyo_section_children_item_types', array( $this, 'include_google_meet_item_type' ) );

		add_filter( 'masteriyo_single_course_curriculum_summaries', array( $this, 'include_google_meet_in_curriculum_summary' ), 10, 3 );
		add_filter( 'masteriyo_single_course_curriculum_section_summaries', array( $this, 'include_google_meet_in_curriculum_section_summary' ), 10, 4 );

		add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'add_localization_to_admin_scripts' ) );

		add_filter( 'admin_init', array( $this, 'redirect_google_meet' ) );

		add_filter( 'masteriyo_course_builder_course_child_data', array( $this, 'add_google_meet_data_to_course_builder' ), 10, 3 );

		add_filter( 'masteriyo_course_progress_item_data', array( $this, 'add_google_meet_data_to_course_progress_item' ), 10, 3 );

		add_action( 'masteriyo_layout_1_single_course_curriculum_shortinfo_item', array( $this, 'shortinfo_item' ), 20, 1 );
		add_action( 'masteriyo_layout_1_single_course_curriculum_accordion_header_info_item', array( $this, 'header_info_item' ), 20, 1 );
	}

	/**
	 * Add localization data to admin scripts.
	 *
	 * @since 1.11.0
	 *
	 * @param array $localized_scripts
	 * @return array
	 */
	public function add_localization_to_admin_scripts( $localized_scripts ) {
		return masteriyo_parse_args(
			$localized_scripts,
			array(
				'backend' => array(
					'data' => array(
						'isGoogleMeetCredentialsSet' => masteriyo_bool_to_string( masteriyo_is_google_meet_credentials_set() ),
						'redirect_url'               => admin_url() . 'admin.php?page=masteriyo',
						'google_console_link'        => 'https://console.cloud.google.com/apis/dashboard',
					),
				),
			)
		);
	}

	/**
	 * Add google meet data to course builder.
	 *
	 * @since 1.11.0
	 *
	 * @param array $data Course child data.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @return array
	 */
	public function add_google_meet_data_to_course_builder( $data, $course_item, $context ) {

		if ( 'mto-google-meet' === $course_item->get_post_type() ) {
			$data['meeting_id']   = $course_item->get_meeting_id();
			$data['meet_url']     = $course_item->get_meet_url();
			$data['calender_url'] = $course_item->get_calender_url();
			$data['time_zone']    = $course_item->get_time_zone();
		}

		return $data;

	}

	/**
	 * Displays a short information item for the Google Meet addon.
	 *
	 * This function generates an HTML list item that displays the number of Google Meet meetings
	 * associated with the given course.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	public function shortinfo_item( $course ) {
		if ( ! $course instanceof \Masteriyo\Models\Course ) {
			return;
		}

		$google_meet_count = $this->get_google_meet_course_section_children_count_by_course( $course->get_id(), PostType::GOOGLEMEET );

		$html  = '<li class="masteriyo-single-body__main--curriculum-content-top--shortinfo-item">';
		$html .= sprintf(
			/* translators: %1$s: Google Meet count */
			esc_html( _nx( '%1$s Google Meet', '%1$s Google Meets', $google_meet_count, 'Google Meets Count', 'learning-management-system' ) ),
			esc_html( number_format_i18n( $google_meet_count ) )
		);
		$html .= '</li>';

		echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Displays a short information item for the Google Meet addon.
	 *
	 * This function generates an HTML span element that displays the number of Google Meet meetings
	 * associated with the given section.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Models\Section $section The section object.
	 */
	public function header_info_item( $section ) {
		if ( ! $section instanceof \Masteriyo\Models\Section ) {
			return;
		}

		$google_meet_count = $this->get_google_meet_course_section_children_count_by_section( $section->get_id(), PostType::GOOGLEMEET );

		$html  = '<span class="masteriyo-single-body-accordion-info">';
		$html .= sprintf(
			/* translators: %1$s: Google Meet count */
			esc_html( _nx( '%1$s Google Meet', '%1$s Google Meets', $google_meet_count, 'Google Meets Count', 'learning-management-system' ) ),
			esc_html( number_format_i18n( $google_meet_count ) )
		);
		$html .= '</span>';

		echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

		/**
		 * Get google meet count by course.
		 *
		 * @since 1.11.0
		 *
		 * @param int $course_id Course ID.
		 * @param string $type The type of section items. Default is 'google-meet'.
		 *
		 * @return int
		 */
	public function get_google_meet_course_section_children_count_by_course( $course_id, $type = 'google-meet' ) {
		$children_count = 0;

		$section_ids = get_posts(
			array(
				'post_type'      => PostType::SECTION,
				'post_status'    => PostStatus::ANY,
				'post_parent'    => $course_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$section_ids = array_filter( $section_ids );

		if ( empty( $section_ids ) ) {
			return $children_count;
		}

		foreach ( $section_ids as $section_id ) {
			$google_meet = get_posts(
				array(
					'post_type'      => PostType::GOOGLEMEET,
					'post_status'    => PostStatus::PUBLISH,
					'post_parent'    => $section_id,
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			$children_count += count( array_filter( $google_meet ) );
		}

		return $children_count;
	}


		/**
		 * Get google meet count by section.
		 *
		 * @since 1.11.0
		 *
		 * @param int $section_id section ID.
		 * @param string $type The type of section items. Default is 'google-meet'.
		 *
		 * @return int
		 */
	public function get_google_meet_course_section_children_count_by_section( $section_id, $type = 'google-meet' ) {
		$count = 0;

		$post_ids = get_posts(
			array(
				'post_type'      => PostType::GOOGLEMEET,
				'post_status'    => PostStatus::PUBLISH,
				'post_parent'    => $section_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$count = count( array_filter( $post_ids ) );

		return $count;
	}

	/**
	 * Add google meet data to course progress item.
	 *
	 * @since 1.11.0
	 *
	 * @param array $data Course progress item data.
	 * @param \Masteriyo\Models\CourseProgressItem $course_progress_item Course progress item object.
	 * @param string $context The context in which the data is being retrieved.
	 * @return array
	 */
	public function add_google_meet_data_to_course_progress_item( $data, $course_progress_item, $context ) {

		$google_meet_id = '';

		if ( 'google-meet' === $course_progress_item->get_item_type() ) {
			$google_meet_id         = get_post_meta( $course_progress_item->get_item_id( $context ), '_meeting_id', true );
			$data['google_meet_id'] = $google_meet_id;
		}

		return $data;

	}


	/**
	 * Include google meet in single course curriculum.
	 *
	 * @since 1.11.0
	 *
	 * @param array $summaries Section summaries.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\Section $section Section object.
	 * @param \WP_Post[] $posts Children of section (lessons and quizzes).
	 *
	 * @return array
	 */
	public function include_google_meet_in_curriculum_section_summary( $summaries, $course, $section, $posts ) {
		$google_meet_count = array_reduce(
			$posts,
			function( $count, $post ) {
				if ( PostType::GOOGLEMEET === $post->post_type ) {
					++$count;
				}

				return $count;
			},
			0
		);

		$google_meet_summary = array(
			array(
				'wrapper_start' => '<span class="masteriyo-google-meets-count">',
				'wrapper_end'   => '</span>',
				'content'       => sprintf(
					/* translators: %d: Course google meets count */
					esc_html( _nx( '%d Google Meet', '%d Google Meets', $google_meet_count, 'Google Meets Count', 'learning-management-system' ) ),
					esc_html( number_format_i18n( $google_meet_count ) )
				),
			),
		);

		// @see https://stackoverflow.com/questions/3797239/insert-new-item-in-array-on-any-position-in-php
		array_splice( $summaries, 2, 0, $google_meet_summary );

		return $summaries;
	}

	/**
	 * Include google meet in single course curriculum.
	 *
	 * @since 1.11.0
	 *
	 * @param array $summaries Summaries.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param WP_Post[] $posts Array of sections, quizzes and sections.
	 * @return array
	 */
	public function include_google_meet_in_curriculum_summary( $summaries, $course, $posts ) {
		$google_meet_count = array_reduce(
			$posts,
			function( $count, $post ) {
				if ( PostType::GOOGLEMEET === $post->post_type ) {
					++$count;
				}

				return $count;
			},
			0
		);

		$google_meet_summary = array(
			array(
				'wrapper_start' => ' < li > ',
				'wrapper_end'   => ' < / li > ',
				'content'       => sprintf(
					/* translators: %d: Course google meets count */
					esc_html( _nx( ' % d Google Meet', ' % d Google Meets', $google_meet_count, 'Google Meets Count', 'learning-management-system' ) ),
					esc_html( number_format_i18n( $google_meet_count ) )
				),
			),
		);

		// @see https://stackoverflow.com/questions/3797239/insert-new-item-in-array-on-any-position-in-php
		array_splice( $summaries, 3, 0, $google_meet_summary );

		return $summaries;
	}

	/**
	 * When user clicks the Go to google consent screen button in setting of google meet, and provides the google access, it
	 * redirects to google meet setAPI page in backend page. if the code is valid.
	 *
	 * @since 1.11.0
	 */
	public function redirect_google_meet() {
		if ( ! empty( $_GET['code'] ) && ! empty( $_GET['page'] ) && isset( $_GET['state'] ) && 'masteriyo_google_meet' === $_GET['state'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$code                = $_GET['code'];// phpcs:ignore WordPress.Security.NonceVerification
			$google_setting_data = ( new GoogleMeetSetting() )->get_data();
			$setting             = new GoogleMeetSetting();

			$google_provider = create_google_meet_client( $google_setting_data );

			try {

				$access_token = $google_provider->getAccessToken(
					'authorization_code',
					array(
						'code' => $code,
					)
				);

				$refresh_token = $access_token->getRefreshToken();

				if ( $access_token ) {
					$token = json_decode( json_encode( $access_token ) );

					if ( $refresh_token ) {
						$setting->set( 'refresh_token', $refresh_token );
					} else {
						$setting->set( 'refresh_token', '' );
					}
					$setting->set( 'access_token', $token->access_token );
					$setting->save();

				}
				$site_url = get_site_url();
				wp_safe_redirect(
					$site_url . '/wp-admin/admin.php?page=masteriyo#/google-meet/setAPI'
				);
			} catch ( \League\OAuth2\Client\Provider\Exception\IdentityProviderException $e ) {
				$setting->set( 'refresh_token', '' );
				$setting->save();
				error_log( 'Google Meet API Error (IdentityProviderException): ' . $e->getMessage() );
				wp_die( 'There was an error with Google Calendar authentication. Please ensure your credentials are correct and try again.' );
			} catch ( \Exception $e ) {
				$setting->set( 'refresh_token', '' );
				$setting->save();
				if ( $e->getCode() === 403 ) {
					wp_die( esc_html__( 'Google Classroom API has not been used in the given project, Please Enable Google Classroom API.', 'learning-management-system' ) );
				}
				error_log( 'Google Meet API Error (General Exception): ' . $e->getMessage() );
				wp_die( 'There was an error connecting to Google Calendar. Please try again later.' );
			}
		}
	}

	/**
	 * Include google meet item type.
	 *
	 * @since 1.11.0
	 *
	 * @param array $types Item types.
	 * @return array
	 */
	public function include_google_meet_item_type( $types ) {
		return array_merge( $types, array( 'google-meet' ) );
	}

	/**
	 * Include google meet post type.
	 *
	 * @since 1.11.0
	 *
	 * @param array $types post types.
	 * @return array
	 */
	public function include_google_meet_post_type( $types ) {
		return array_merge( $types, array( PostType::GOOGLEMEET ) );
	}

	/**
	 * Register google meet post type.
	 *
	 * @since 1.11.0
	 *
	 * @param array $post_types
	 * @return array
	 */
	public function register_post_type( $post_types ) {
		$post_types['google-meet'] = GoogleMeet::class;
		return $post_types;
	}

	/**
	 * Add google meet submenu.
	 *
	 * @since 1.11.0
	 */
	public function register_google_meet_submenu( $submenus ) {
		$submenus['google-meet/meetings'] = array(
			'page_title' => __( 'Google Meet', 'learning-management-system' ),
			'menu_title' => __( 'Google Meet', 'learning-management-system' ),
			'capability' => 'get_google-meets',
			'position'   => 65,
		);
		return $submenus;
	}

	/**
	 * Register namespaces.
	 *
	 * @since 1.11.0
	 *
	 * @param array $namespaces
	 * @return array
	 */
	public function register_rest_namespaces( $namespaces ) {
		$namespaces['masteriyo/v1']['google-meet-setting'] = GoogleMeetSettingController::class;
		$namespaces['masteriyo/v1']['google-meet']         = GoogleMeetController::class;

		return $namespaces;
	}
}
