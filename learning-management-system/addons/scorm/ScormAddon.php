<?php
/**
 * SCORM Addon for Masteriyo.
 *
 * @since 1.8.3
 */

namespace Masteriyo\Addons\Scorm;

use Masteriyo\Addons\Scorm\Controllers\ScormController;
use Masteriyo\Constants;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Pro\Addons;
use Masteriyo\Query\CourseProgressQuery;

/**
 * SCORM Addon main class for Masteriyo.
 *
 * @since 1.8.3
 */
class ScormAddon {

	/**
	 * Initialize.
	 *
	 * @since 1.8.3
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.8.3
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_migrations_paths', array( $this, 'add_migrations' ) );
		add_filter( 'masteriyo_rest_api_get_rest_namespaces', array( $this, 'register_rest_namespaces' ) );
		add_filter( 'masteriyo_rest_response_course_progress_data', array( $this, 'rest_response_course_progress_data' ), 10, 4 );
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		add_action( 'masteriyo_after_learn_page_process', array( $this, 'scorm_learn_page_handler' ) );
		add_filter( 'masteriyo_rest_prepare_course_builder_collection', array( $this, 'rest_prepare_course_builder_collection' ), 10, 2 );
		add_filter( 'masteriyo_rest_course_builder_schema', array( $this, 'add_scorm_schema_to_course_builder' ) );
		add_filter( 'masteriyo_whitelist_styles_learn_page', array( $this, 'whitelist_styles_learn_page' ), 10, 1 );
		// Setting related hooks.
		add_filter( 'masteriyo_new_setting', array( $this, 'save_setting' ), 10 );
		add_filter( 'masteriyo_rest_response_setting_data', array( $this, 'append_setting_in_response' ), 10, 4 );

		add_action( 'template_redirect', array( $this, 'course_complete_handler' ) );
	}
	/**
	 * Handles course completion for SCORM courses.
	 *
	 * @since 1.14.2
	 */
	public function course_complete_handler() {
		if ( ! isset( $_GET['masteriyo_scorm_complete'] ) || ! is_user_logged_in() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$course_id = absint( $_GET['masteriyo_scorm_complete'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$course    = masteriyo_get_course( $course_id );
		$user_id   = get_current_user_id();

		if ( ! $user_id || ! $course instanceof \Masteriyo\Models\Course ) {
			return;
		}

		$user_course = masteriyo_get_user_course_by_user_and_course( $user_id, $course_id );

		if ( ! $user_course ) {
			return;
		}

		masteriyo_update_user_scorm_course_progress( $course_id, $user_id, CourseProgressStatus::COMPLETED );

		$learn_page_url = masteriyo_get_page_permalink( 'learn' );
		$url            = trailingslashit( $learn_page_url ) . 'course/' . $course->get_slug();
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Add the the the whitelisted styles for scorm learn page.
	 *
	 * @since v.x.x [free]
	 *
	 * @param array $styles Array of the whitelisted styles.
	 *
	 * @return array $styles Array of the whitelisted styles.
	 */
	public function whitelist_styles_learn_page( $styles ) {
		$styles[] = 'masteriyo-scorm-style';

		return $styles;
	}

	/**
	 * Add scorm fields to lesson schema.
	 *
	 * @since 1.8.3
	 *
	 * @param array $schema
	 *
	 * @return array
	 */
	public function add_scorm_schema_to_course_builder( $schema ) {
		$schema = masteriyo_parse_args(
			$schema,
			array(
				'scorm_package' => array(
					'description' => __( 'SCORM package', 'learning-management-system' ),
					'type'        => 'array',
					'required'    => false,
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'path'          => array(
								'description' => __( 'Scorm file path.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'url'           => array(
								'description' => __( 'Scorm file url.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'scorm_version' => array(
								'description' => __( 'Scorm version.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'file_name'     => array(
								'description' => __( 'Scorm file name.', 'learning-management-system' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			)
		);

		return $schema;
	}

	/**
	 * Adds the scorm addon data to the course builder collection.
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param  \WP_Post $course Course object.
	 *
	 * @since 1.8.3
	 *
	 * @return array An array of course builder data.
	 */
	public function rest_prepare_course_builder_collection( $results, $course ) {

		if ( $course ) {
			$results['scorm_package'] = masteriyo_get_scorm_meta( $course->ID );
		}

		return $results;
	}

	/**
	 * Handle the scorm learn page handle.
	 *
	 * @since 1.8.3
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 *
	 * @return void
	 */
	public function scorm_learn_page_handler( $course ) {
		$scorm_package = masteriyo_get_scorm_meta( $course );

		if ( ! empty( $scorm_package ) ) {

			$course_id = $course->get_id();

			$query = new CourseProgressQuery(
				array(
					'course_id' => $course_id,
					'user_id'   => get_current_user_id(),
				)
			);

			$progress        = current( $query->get_course_progress() );
			$certificate_url = masteriyo_generate_certificate_download_url( $course_id );

			require Constants::get( 'MASTERIYO_SCORM_ADDON_TEMPLATES' ) . '/scorm-learn.php';

			exit;
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.8.3
	 *
	 * @param array $scripts
	 */
	public function enqueue_scripts( $scripts ) {
		$old_callback = $scripts['learn']['callback'];

		$scripts['learn']['callback'] = function () use ( $old_callback ) {
			if ( masteriyo_is_learn_page() && ( new Addons() )->is_active( 'scorm' ) ) {
				$preview   = masteriyo_string_to_bool( get_query_var( 'mto-preview', false ) );
				$course_id = get_query_var( 'course_name', 0 );

				if ( '' === get_option( 'permalink_structure' ) || $preview ) {
					$course_id = get_query_var( 'course_name', 0 );
				} else {
					$course_slug = get_query_var( 'course_name', '' );

					$courses = get_posts(
						array(
							'post_type'   => 'mto-course',
							'name'        => $course_slug,
							'numberposts' => 1,
							'fields'      => 'ids',
						)
					);

					$course_id = is_array( $courses ) ? array_shift( $courses ) : 0;
				}

				$scorm_package = json_decode( get_post_meta( $course_id, '_scorm_package', true ), true );

				if ( ! empty( $scorm_package ) ) {
					return false;
				}

				return $old_callback;
			}

		};

		return $scripts;
	}

	/**
	 * Modify the course progress rest response data.
	 *
	 * @since 1.8.3
	 *
	 * @param array $data Course progress data.
	 * @param \Masteriyo\Models\CourseProgress $course_progress Course progress object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param \Masteriyo\RestApi\Controllers\Version1\CoursesController $controller REST course progress controller object.
	 */
	public function rest_response_course_progress_data( $data, $course_progress, $context, $controller ) {

		$is_scorm_course_progress = masteriyo_is_scorm_course( $course_progress->get_course_id() );

		$data['is_scorm_course_progress'] = $is_scorm_course_progress;

		if ( $is_scorm_course_progress ) {
			$data['status'] = $course_progress->get_completed_at() ? CourseProgressStatus::COMPLETED : CourseProgressStatus::STARTED;
		}

		return $data;
	}

	/**
	 * Register REST API namespaces for the SCORM.
	 *
	 * @since 1.8.3
	 *
	 * @param array $namespaces Rest namespaces.
	 *
	 * @return array Modified REST namespaces including SCORM endpoints.
	 */
	public function register_rest_namespaces( $namespaces ) {
		$namespaces['masteriyo/v1']['scorm'] = ScormController::class;
		return $namespaces;
	}

	/**
	 * Add migrations.
	 *
	 * @since 1.8.3
	 *
	 * @param array $migrations
	 * @return array
	 */
	public function add_migrations( $migrations ) {
		$migrations[] = plugin_dir_path( MASTERIYO_SCORM_FILE ) . 'migrations';

		return $migrations;
	}

	/**
	 * Save setting.
	 *
	 * @since 1.14.0
	 *
	 * @param \Masteriyo\Models\Setting $setting
	 */
	public function save_setting() {
		$request = masteriyo_current_http_request();

		if ( ! masteriyo_is_rest_api_request() ) {
			return;
		}

		if ( ! isset( $request['advance']['scorm']['allowed_extensions'] ) ) {
			return;
		}

		Setting::read();

		// Sanitization.
		if ( isset( $request['advance']['scorm']['allowed_extensions'] ) ) {
			Setting::set( 'allowed_extensions', sanitize_text_field( $request['advance']['scorm']['allowed_extensions'] ) );
		}
	}

	/**
	 * Append setting to response.
	 *
	 * @since 1.14.0
	 *
	 * @param array $data Setting data.
	 * @param \Masteriyo\Models\Setting $setting Setting object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param \Masteriyo\RestApi\Controllers\Version1\SettingsController $controller REST settings controller object.
	 *
	 * @return array
	 */
	public function append_setting_in_response( $data, $setting, $context, $controller ) {
		$data['advance']['scorm'] = Setting::all();

		return $data;
	}
}
