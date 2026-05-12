<?php
/**
 * Abilities service provider.
 *
 * Registers all DI bindings for the Abilities layer and lazily populates the
 * AbilityRegistry inside wp_abilities_api_init so objects are only created when
 * the WordPress Abilities API plugin is actually present.
 *
 * @package Masteriyo\Abilities\Providers
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Abilities\Registry\AbilityRegistrar;
use Masteriyo\Abilities\Registry\AbilityRegistry;
use Masteriyo\Abilities\Support\SchemaTranslator;
use Masteriyo\Helper\Permission;
use Masteriyo\RestApi\Controllers\Version1\CourseBuilderController;
use Masteriyo\RestApi\Controllers\Version1\CourseChildrenController;
use Masteriyo\RestApi\Controllers\Version1\QuizBuilderController;
use Masteriyo\RestApi\Controllers\Version1\SectionChildrenController;
use Masteriyo\RestApi\Controllers\Version1\UserCoursesController;

// ── Course ────────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\Course\ListCoursesAbility;
use Masteriyo\Abilities\Domains\Course\GetCourseAbility;
use Masteriyo\Abilities\Domains\Course\CreateCourseAbility;
use Masteriyo\Abilities\Domains\Course\UpdateCourseAbility;
use Masteriyo\Abilities\Domains\Course\DeleteCourseAbility;
use Masteriyo\Abilities\Domains\Course\RestoreCourseAbility;
use Masteriyo\Abilities\Domains\Course\GetCourseChildrenAbility;

// ── CourseBuilder ─────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\CourseBuilder\GetCourseBuilderAbility;
use Masteriyo\Abilities\Domains\CourseBuilder\SaveCourseBuilderAbility;

// ── Section ───────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\Section\ListSectionsAbility;
use Masteriyo\Abilities\Domains\Section\GetSectionAbility;
use Masteriyo\Abilities\Domains\Section\CreateSectionAbility;
use Masteriyo\Abilities\Domains\Section\UpdateSectionAbility;
use Masteriyo\Abilities\Domains\Section\DeleteSectionAbility;
use Masteriyo\Abilities\Domains\Section\GetSectionChildrenAbility;

// ── Lesson ────────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\Lesson\ListLessonsAbility;
use Masteriyo\Abilities\Domains\Lesson\GetLessonAbility;
use Masteriyo\Abilities\Domains\Lesson\CreateLessonAbility;
use Masteriyo\Abilities\Domains\Lesson\UpdateLessonAbility;
use Masteriyo\Abilities\Domains\Lesson\DeleteLessonAbility;
use Masteriyo\Abilities\Domains\Lesson\RestoreLessonAbility;
use Masteriyo\Abilities\Domains\Lesson\CloneLessonAbility;

// ── Quiz ──────────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\Quiz\ListQuizzesAbility;
use Masteriyo\Abilities\Domains\Quiz\GetQuizAbility;
use Masteriyo\Abilities\Domains\Quiz\CreateQuizAbility;
use Masteriyo\Abilities\Domains\Quiz\UpdateQuizAbility;
use Masteriyo\Abilities\Domains\Quiz\DeleteQuizAbility;
use Masteriyo\Abilities\Domains\Quiz\CloneQuizAbility;

// ── QuizBuilder ───────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\QuizBuilder\GetQuizBuilderAbility;
use Masteriyo\Abilities\Domains\QuizBuilder\SaveQuizBuilderAbility;

// ── Question ──────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\Question\ListQuestionsAbility;
use Masteriyo\Abilities\Domains\Question\GetQuestionAbility;
use Masteriyo\Abilities\Domains\Question\CreateQuestionAbility;
use Masteriyo\Abilities\Domains\Question\UpdateQuestionAbility;
use Masteriyo\Abilities\Domains\Question\DeleteQuestionAbility;

// ── CourseCategory ────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\CourseCategory\ListCourseCategoriesAbility;
use Masteriyo\Abilities\Domains\CourseCategory\GetCourseCategoryAbility;
use Masteriyo\Abilities\Domains\CourseCategory\CreateCourseCategoryAbility;
use Masteriyo\Abilities\Domains\CourseCategory\UpdateCourseCategoryAbility;
use Masteriyo\Abilities\Domains\CourseCategory\DeleteCourseCategoryAbility;

// ── CourseTag ─────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\CourseTag\ListCourseTagsAbility;
use Masteriyo\Abilities\Domains\CourseTag\GetCourseTagAbility;
use Masteriyo\Abilities\Domains\CourseTag\CreateCourseTagAbility;
use Masteriyo\Abilities\Domains\CourseTag\UpdateCourseTagAbility;
use Masteriyo\Abilities\Domains\CourseTag\DeleteCourseTagAbility;

// ── CourseDifficulty ──────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\CourseDifficulty\ListCourseDifficultiesAbility;
use Masteriyo\Abilities\Domains\CourseDifficulty\GetCourseDifficultyAbility;
use Masteriyo\Abilities\Domains\CourseDifficulty\CreateCourseDifficultyAbility;
use Masteriyo\Abilities\Domains\CourseDifficulty\UpdateCourseDifficultyAbility;
use Masteriyo\Abilities\Domains\CourseDifficulty\DeleteCourseDifficultyAbility;

// ── CourseReview ──────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\CourseReview\ListCourseReviewsAbility;
use Masteriyo\Abilities\Domains\CourseReview\GetCourseReviewAbility;
use Masteriyo\Abilities\Domains\CourseReview\CreateCourseReviewAbility;
use Masteriyo\Abilities\Domains\CourseReview\UpdateCourseReviewAbility;
use Masteriyo\Abilities\Domains\CourseReview\DeleteCourseReviewAbility;

// ── CourseQA ──────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\CourseQA\ListCourseQAsAbility;
use Masteriyo\Abilities\Domains\CourseQA\GetCourseQAAbility;
use Masteriyo\Abilities\Domains\CourseQA\CreateCourseQAAbility;
use Masteriyo\Abilities\Domains\CourseQA\UpdateCourseQAAbility;
use Masteriyo\Abilities\Domains\CourseQA\DeleteCourseQAAbility;
use Masteriyo\Abilities\Domains\CourseQA\RestoreCourseQAAbility;

// ── Enrollment ────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\Enrollment\ListEnrollmentsAbility;
use Masteriyo\Abilities\Domains\Enrollment\GetEnrollmentAbility;
use Masteriyo\Abilities\Domains\Enrollment\CreateEnrollmentAbility;
use Masteriyo\Abilities\Domains\Enrollment\UpdateEnrollmentAbility;
use Masteriyo\Abilities\Domains\Enrollment\DeleteEnrollmentAbility;

// ── CourseProgress ────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\CourseProgress\GetCourseProgressAbility;
use Masteriyo\Abilities\Domains\CourseProgress\UpdateCourseProgressAbility;

// ── Order ─────────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\Order\ListOrdersAbility;
use Masteriyo\Abilities\Domains\Order\GetOrderAbility;
use Masteriyo\Abilities\Domains\Order\CreateOrderAbility;
use Masteriyo\Abilities\Domains\Order\UpdateOrderAbility;
use Masteriyo\Abilities\Domains\Order\DeleteOrderAbility;

// ── User ──────────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\User\ListUsersAbility;
use Masteriyo\Abilities\Domains\User\GetUserAbility;
use Masteriyo\Abilities\Domains\User\CreateUserAbility;
use Masteriyo\Abilities\Domains\User\UpdateUserAbility;
use Masteriyo\Abilities\Domains\User\DeleteUserAbility;

// ── Setting ───────────────────────────────────────────────────────────────────
use Masteriyo\Abilities\Domains\Setting\GetSettingsAbility;
use Masteriyo\Abilities\Domains\Setting\UpdateGeneralSettingsAbility;
use Masteriyo\Abilities\Domains\Setting\UpdatePaymentSettingsAbility;
use Masteriyo\Abilities\Domains\Setting\UpdateEmailSettingsAbility;
use Masteriyo\Abilities\Domains\Setting\UpdateQuizSettingsAbility;
use Masteriyo\Abilities\Domains\Setting\UpdateAdvanceSettingsAbility;

/**
 * Registers the abilities infrastructure and populates the registry.
 *
 * @since x.x.x
 */
class AbilitiesServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {

	/**
	 * Register DI bindings.
	 *
	 * Binds controllers that are consumed by abilities but not already bound
	 * by their own domain service providers (CourseBuilder, QuizBuilder,
	 * and the children list controllers), plus the shared SchemaTranslator.
	 *
	 * @since x.x.x
	 */
	public function register(): void {
		$container = $this->getContainer();

		// Extra controller bindings needed by abilities but absent from domain providers.
		// addShared() so that boot()→register() + the container's own lazy register() call
		// both resolve to the same singleton rather than stacking duplicate definitions.
		$container->addShared( 'course_builder.rest', CourseBuilderController::class )
			->addArgument( 'permission' );

		$container->addShared( 'quiz_builder.rest', QuizBuilderController::class )
			->addArgument( 'permission' );

		$container->addShared( 'course_children.rest', CourseChildrenController::class )
			->addArgument( 'permission' );

		$container->addShared( 'section_children.rest', SectionChildrenController::class )
			->addArgument( 'permission' );

		// UserCoursesController is only referenced as a route class in RestApi.php, not bound as a DI service.
		$container->addShared( 'users.courses', UserCoursesController::class )
			->addArgument( 'permission' );

		// Shared schema translator singleton — one instance/cache for the whole request.
		$container->addShared( 'abilities.schema_translator', SchemaTranslator::class );

		// Shared registry (singleton across the request).
		$container->addShared( 'ability.registry', AbilityRegistry::class );

		// Registrar depends on registry.
		$container->addShared( 'ability.registrar', AbilityRegistrar::class )
			->addArgument( 'ability.registry' );
	}

	/**
	 * Boot the provider — subscribe to WP hooks.
	 *
	 * Ability objects are instantiated lazily inside the wp_abilities_api_init
	 * hook (priority 5) so they are only allocated on sites that have the
	 * Abilities API plugin installed. The registrar fires at priority 10.
	 *
	 * @since x.x.x
	 */
	public function boot(): void {
		$this->register();

		$container = $this->getContainer();

		// Populate the registry only when the Abilities API is present.
		add_action(
			'wp_abilities_api_init',
			function () use ( $container ) {
				$registry = $container->get( 'ability.registry' );

				// ── Course ──────────────────────────────────────────────────────
				$registry->add( new ListCoursesAbility() );
				$registry->add( new GetCourseAbility() );
				$registry->add( new CreateCourseAbility() );
				$registry->add( new UpdateCourseAbility() );
				$registry->add( new DeleteCourseAbility() );
				$registry->add( new RestoreCourseAbility() );
				$registry->add( new GetCourseChildrenAbility() );

				// ── CourseBuilder ────────────────────────────────────────────────
				$registry->add( new GetCourseBuilderAbility() );
				$registry->add( new SaveCourseBuilderAbility() );

				// ── Section ──────────────────────────────────────────────────────
				$registry->add( new ListSectionsAbility() );
				$registry->add( new GetSectionAbility() );
				$registry->add( new CreateSectionAbility() );
				$registry->add( new UpdateSectionAbility() );
				$registry->add( new DeleteSectionAbility() );
				$registry->add( new GetSectionChildrenAbility() );

				// ── Lesson ───────────────────────────────────────────────────────
				$registry->add( new ListLessonsAbility() );
				$registry->add( new GetLessonAbility() );
				$registry->add( new CreateLessonAbility() );
				$registry->add( new UpdateLessonAbility() );
				$registry->add( new DeleteLessonAbility() );
				$registry->add( new RestoreLessonAbility() );
				$registry->add( new CloneLessonAbility() );

				// ── Quiz ─────────────────────────────────────────────────────────
				$registry->add( new ListQuizzesAbility() );
				$registry->add( new GetQuizAbility() );
				$registry->add( new CreateQuizAbility() );
				$registry->add( new UpdateQuizAbility() );
				$registry->add( new DeleteQuizAbility() );
				$registry->add( new CloneQuizAbility() );

				// ── QuizBuilder ──────────────────────────────────────────────────
				$registry->add( new GetQuizBuilderAbility() );
				$registry->add( new SaveQuizBuilderAbility() );

				// ── Question ─────────────────────────────────────────────────────
				$registry->add( new ListQuestionsAbility() );
				$registry->add( new GetQuestionAbility() );
				$registry->add( new CreateQuestionAbility() );
				$registry->add( new UpdateQuestionAbility() );
				$registry->add( new DeleteQuestionAbility() );

				// ── CourseCategory ───────────────────────────────────────────────
				$registry->add( new ListCourseCategoriesAbility() );
				$registry->add( new GetCourseCategoryAbility() );
				$registry->add( new CreateCourseCategoryAbility() );
				$registry->add( new UpdateCourseCategoryAbility() );
				$registry->add( new DeleteCourseCategoryAbility() );

				// ── CourseTag ────────────────────────────────────────────────────
				$registry->add( new ListCourseTagsAbility() );
				$registry->add( new GetCourseTagAbility() );
				$registry->add( new CreateCourseTagAbility() );
				$registry->add( new UpdateCourseTagAbility() );
				$registry->add( new DeleteCourseTagAbility() );

				// ── CourseDifficulty ─────────────────────────────────────────────
				$registry->add( new ListCourseDifficultiesAbility() );
				$registry->add( new GetCourseDifficultyAbility() );
				$registry->add( new CreateCourseDifficultyAbility() );
				$registry->add( new UpdateCourseDifficultyAbility() );
				$registry->add( new DeleteCourseDifficultyAbility() );

				// ── CourseReview ─────────────────────────────────────────────────
				$registry->add( new ListCourseReviewsAbility() );
				$registry->add( new GetCourseReviewAbility() );
				$registry->add( new CreateCourseReviewAbility() );
				$registry->add( new UpdateCourseReviewAbility() );
				$registry->add( new DeleteCourseReviewAbility() );

				// ── CourseQA ─────────────────────────────────────────────────────
				$registry->add( new ListCourseQAsAbility() );
				$registry->add( new GetCourseQAAbility() );
				$registry->add( new CreateCourseQAAbility() );
				$registry->add( new UpdateCourseQAAbility() );
				$registry->add( new DeleteCourseQAAbility() );
				$registry->add( new RestoreCourseQAAbility() );

				// ── Enrollment ───────────────────────────────────────────────────
				$registry->add( new ListEnrollmentsAbility() );
				$registry->add( new GetEnrollmentAbility() );
				$registry->add( new CreateEnrollmentAbility() );
				$registry->add( new UpdateEnrollmentAbility() );
				$registry->add( new DeleteEnrollmentAbility() );

				// ── CourseProgress ───────────────────────────────────────────────
				$registry->add( new GetCourseProgressAbility() );
				$registry->add( new UpdateCourseProgressAbility() );

				// ── Order ────────────────────────────────────────────────────────
				$registry->add( new ListOrdersAbility() );
				$registry->add( new GetOrderAbility() );
				$registry->add( new CreateOrderAbility() );
				$registry->add( new UpdateOrderAbility() );
				$registry->add( new DeleteOrderAbility() );

				// ── User ─────────────────────────────────────────────────────────
				$registry->add( new ListUsersAbility() );
				$registry->add( new GetUserAbility() );
				$registry->add( new CreateUserAbility() );
				$registry->add( new UpdateUserAbility() );
				$registry->add( new DeleteUserAbility() );

				// ── Setting ──────────────────────────────────────────────────────
				$registry->add( new GetSettingsAbility() );
				$registry->add( new UpdateGeneralSettingsAbility() );
				$registry->add( new UpdatePaymentSettingsAbility() );
				$registry->add( new UpdateEmailSettingsAbility() );
				$registry->add( new UpdateQuizSettingsAbility() );
				$registry->add( new UpdateAdvanceSettingsAbility() );
			},
			5 // Before AbilityRegistrar::register_all() at priority 10.
		);

		// Subscribe to WordPress Abilities API hooks.
		$container->get( 'ability.registrar' )->init();
	}

	/**
	 * Declare which service IDs this provider is responsible for.
	 *
	 * @since x.x.x
	 * @param string $id Service identifier to check.
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(
				'ability.registry',
				'ability.registrar',
				'abilities.schema_translator',
				'course_builder.rest',
				'quiz_builder.rest',
				'course_children.rest',
				'section_children.rest',
				'users.courses',
			),
			true
		);
	}
}
