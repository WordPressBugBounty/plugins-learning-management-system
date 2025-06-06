<?php
/**
 * Job service provider.
 *
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Models\Setting;
use Masteriyo\Jobs\SendTrackingInfoJob;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Jobs\CheckCourseEndDateJob;
use Masteriyo\Jobs\CoursesExportJob;
use Masteriyo\Jobs\CoursesImportJob;
use Masteriyo\Jobs\CreateCourseContentJob;
use Masteriyo\Jobs\CreateLessonsContentJob;
use Masteriyo\Jobs\CreateQuizzesForSectionsJob;
use Masteriyo\Jobs\SendAddonsTrackingInfoJob;
use Masteriyo\Jobs\WebhookDeliveryJob;

/**
 * Service provider for job-related services.
 *
 * @since 1.6.0
 */
class JobServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	protected $provides = array();

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.6.0
	 */
	public function register() {
		// Register any services or dependencies here.
	}

	/**
	 * Bootstraps the application by scheduling a recurring action and registering the job.
	 *
	 * This method is called after all service providers are registered.
	 *
	 * @since 1.6.0
	 */
	public function boot() {
		$this->register_webhook_delivery_job();

		// Course creation using AI.
		$this->register_create_course_content_job();
		$this->register_create_lessons_content_job();
		$this->register_create_quizzes_for_sections_job();

		// Check the course end date job.
		$this->register_check_course_end_date_job();

		// Register courses export/import job.
		$this->register_courses_export_job();
		$this->register_courses_import_job();
	}


	/**
	 * Register webhook delivery job.
	 *
	 * @since 1.6.9
	 */
	public function register_webhook_delivery_job() {
		( new WebhookDeliveryJob() )->init();
	}

	/**
	 * Register create_course_content_job.
	 *
	 * @since 1.6.15
	 */
	public function register_create_course_content_job() {
		( new CreateCourseContentJob() )->register();
	}

	/**
	 * Register create_lessons_content_job.
	 *
	 * @since 1.6.15
	 */
	public function register_create_lessons_content_job() {
		( new CreateLessonsContentJob() )->register();
	}

	/**
	 * Register create_quizzes_for_sections_job.
	 *
	 * @since 1.6.15
	 */
	public function register_create_quizzes_for_sections_job() {
		( new CreateQuizzesForSectionsJob() )->register();
	}

	/**
	 * Register check_course_end_date_job.
	 *
	 * @since 1.7.0
	 */
	public function register_check_course_end_date_job() {
		( new CheckCourseEndDateJob() )->register();
	}

	/**
	 * Register  courses_export_job.
	 *
	 * @since 1.14.0
	 */
	public function register_courses_export_job() {
		( new CoursesExportJob() )->register();
	}

	/**
	 * Register courses_import_job.
	 *
	 * @since 1.14.0
	 */
	public function register_courses_import_job() {
		( new CoursesImportJob() )->register();
	}
}
