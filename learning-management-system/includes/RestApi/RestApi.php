<?php
/**
 * Initialize REST API.
 *
 * @since 1.0.0
 *
 * @package  Masteriyo\RestApi
 */

namespace Masteriyo\RestApi;

use Masteriyo\RestApi\Controllers\Version1\AddonsController;
use Masteriyo\RestApi\Controllers\Version1\BlocksController;
use Masteriyo\RestApi\Controllers\Version1\CourseBuilderController;
use Masteriyo\RestApi\Controllers\Version1\CourseCategoriesController;
use Masteriyo\RestApi\Controllers\Version1\CourseChildrenController;
use Masteriyo\RestApi\Controllers\Version1\CourseDifficultiesController;
use Masteriyo\RestApi\Controllers\Version1\CourseProgressController;
use Masteriyo\RestApi\Controllers\Version1\CourseProgressItemsController;
use Masteriyo\RestApi\Controllers\Version1\CourseQuestionAnswersController;
use Masteriyo\RestApi\Controllers\Version1\CourseReviewsController;
use Masteriyo\RestApi\Controllers\Version1\CoursesController;
use Masteriyo\RestApi\Controllers\Version1\CoursesImportExportController;
use Masteriyo\RestApi\Controllers\Version1\CourseTagsController;
use Masteriyo\RestApi\Controllers\Version1\DataController;
use Masteriyo\RestApi\Controllers\Version1\InstructorsController;
use Masteriyo\RestApi\Controllers\Version1\LessonsController;
use Masteriyo\RestApi\Controllers\Version1\NotificationsController;
use Masteriyo\RestApi\Controllers\Version1\OrderItemsController;
use Masteriyo\RestApi\Controllers\Version1\OrdersController;
use Masteriyo\RestApi\Controllers\Version1\PagesController;
use Masteriyo\RestApi\Controllers\Version1\QuestionsController;
use Masteriyo\RestApi\Controllers\Version1\QuizAttemptsController;
use Masteriyo\RestApi\Controllers\Version1\QuizBuilderController;
use Masteriyo\RestApi\Controllers\Version1\QuizesController;
use Masteriyo\RestApi\Controllers\Version1\QuizReviewsController;
use Masteriyo\RestApi\Controllers\Version1\SectionChildrenController;
use Masteriyo\RestApi\Controllers\Version1\SectionsController;
use Masteriyo\RestApi\Controllers\Version1\SettingsController;
use Masteriyo\RestApi\Controllers\Version1\UserCoursesController;
use Masteriyo\RestApi\Controllers\Version1\UsersController;
use Masteriyo\RestApi\Controllers\Version1\AnalyticsController;
use Masteriyo\RestApi\Controllers\Version1\ChangelogController;
use Masteriyo\RestApi\Controllers\Version1\ErrorReportsController;
use Masteriyo\RestApi\Controllers\Version1\LessonReviewsController;
use Masteriyo\RestApi\Controllers\Version1\QuizzesImportExportController;
use Masteriyo\RestApi\Controllers\Version1\RolesController;
use Masteriyo\RestApi\Controllers\Version1\WebhooksController;
use Masteriyo\RestApi\Controllers\Version1\OpenAIController;
use Masteriyo\RestApi\Controllers\Version1\UsersImportExportController;
use Masteriyo\RestApi\Controllers\Version1\UtilitiesController;
use Masteriyo\RestApi\Controllers\Version1\LogsController;
use Masteriyo\RestApi\Controllers\Version1\OnboardingController;
use Masteriyo\RestApi\Controllers\Version1\RestAuthController;

defined( 'ABSPATH' ) || exit;

class RestApi {

	/**
	 * REST API namespaces and endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected static $controllers = array();

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public static function register_rest_routes() {
		foreach ( self::get_rest_namespaces() as $namespace => $controllers ) {
			foreach ( $controllers as $controller_name => $controller_class ) {
				self::$controllers[ $namespace ][ $controller_name ] = masteriyo( $controller_class );
				self::$controllers[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}

	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of Namespaces and Main controller classes.
	 */
	protected static function get_rest_namespaces() {
		/**
		 * Filters rest API controller classes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $controllers API namespace to API controllers index array.
		 */
		return apply_filters(
			'masteriyo_rest_api_get_rest_namespaces',
			array(
				'masteriyo/v1'     => self::get_v1_controllers(),
				'masteriyo/pro/v1' => self::get_pro_v1_controllers(),
			)
		);
	}

	/**
	 * List of controllers in the masteriyo/v1 namespace.
	 *
	 * @since 1.0.0
	 * @static
	 *
	 * @return array
	 */
	protected static function get_v1_controllers() {

		return array(
			'courses'               => CoursesController::class,
			'courses.categories'    => CourseCategoriesController::class,
			'courses.tags'          => CourseTagsController::class,
			'courses.difficulties'  => CourseDifficultiesController::class,
			'courses.children'      => CourseChildrenController::class,
			'lessons'               => LessonsController::class,
			'questions'             => QuestionsController::class,
			'quizzes'               => QuizesController::class,
			'quizzes.attempts'      => QuizAttemptsController::class,
			'sections'              => SectionsController::class,
			'sections.children'     => SectionChildrenController::class,
			'orders'                => OrdersController::class,
			'orders.items'          => OrderItemsController::class,
			'users'                 => UsersController::class,
			'settings'              => SettingsController::class,
			'courses.reviews'       => CourseReviewsController::class,
			'lessons.reviews'       => LessonReviewsController::class,
			'quizzes.reviews'       => QuizReviewsController::class,
			'courses.qas'           => CourseQuestionAnswersController::class,
			'courses.builder'       => CourseBuilderController::class,
			'quizzes.builder'       => QuizBuilderController::class,
			'course-progress'       => CourseProgressController::class,
			'course-progress.items' => CourseProgressItemsController::class,
			'data'                  => DataController::class,
			'blocks'                => BlocksController::class,
			'instructors'           => InstructorsController::class,
			'users.courses'         => UserCoursesController::class,
			'notifications'         => NotificationsController::class,
			'pages'                 => PagesController::class,
			'courses.import-export' => CoursesImportExportController::class,
			'analytics'             => AnalyticsController::class,
			'webhooks'              => WebhooksController::class,
			'roles'                 => RolesController::class,
			'openai'                => OpenAIController::class,
			'users.import-export'   => UsersImportExportController::class,
			'quizzes.import-export' => QuizzesImportExportController::class,
			'tools.utilities'       => UtilitiesController::class,
			'changelog'             => ChangelogController::class,
			'logger'                => LogsController::class,
			'error_reports'         => ErrorReportsController::class,
			'rest-api-auth'         => RestAuthController::class,
			'onboarding'            => OnboardingController::class,
		);
	}

	/**
	 * Return the path to the package.
	 *
	 * @since 1.0.0
	 * @static
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	/**
	 * Return pro addons controller.
	 *
	 * @since 1.6.11
	 *
	 * @return array
	 */
	public static function get_pro_v1_controllers() {
		return array(
			'addons' => AddonsController::class,
		);
	}
}
