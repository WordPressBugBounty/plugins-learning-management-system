<?php
/**
 * Blocks class service provider.
 *
 * @since 1.18.2
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Constants;
use Masteriyo\Blocks;

/**
 * Registers and initializes block types and categories for Masteriyo LMS.
 *
 * @since 1.18.2
 */
class BlocksServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {

	/**
	 * Services provided by this service provider.
	 *
	 * @since 1.18.2
	 * @var array
	 */
	protected $provides = array();

	/**
	 * Register services in the container.
	 *
	 * @since 1.18.2
	 * @return void
	 */
	public function register() {
		// No container services to register for now.
	}

	/**
	 * Boot the block service provider.
	 * Registers block types, categories, and editor assets.
	 *
	 * @since 1.18.2
	 * @return void
	 */
	public function boot() {
		if ( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ) {
			add_filter( 'block_categories_all', array( $this, 'block_categories' ), 999999, 2 );
		} else {
			add_filter( 'block_categories', array( $this, 'block_categories' ), 999999, 2 );
		}

		add_action( 'init', array( $this, 'register_block_types' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Enqueue block editor JS and CSS.
	 *
	 * @since 1.18.2
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		global $pagenow;
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$dependencies = array(
			'wp-blocks',
			'wp-element',
			'wp-i18n',
			'wp-editor',
			'wp-components',
			'react',
			'react-dom',
			'tooltipster',
		);

		if ( 'widgets.php' === $pagenow ) {
			unset( $dependencies[ array_search( 'wp-editor', $dependencies, true ) ] );
		}

		wp_register_script(
			'masteriyo-blocks-editor',
			Constants::get( 'MASTERIYO_ASSETS' ) . '/js/build/blocks.js',
			$dependencies,
			MASTERIYO_VERSION,
			true
		);
		wp_enqueue_script( 'masteriyo-blocks-editor' );

		wp_register_script(
			'masteriyo-blocks-editor-js',
			plugins_url( 'assets/js/build/masteriyo-blocks' . $suffix . '.js', Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ),
			array( 'wp-blocks', 'wp-dom-ready' ),
			MASTERIYO_VERSION,
			true
		);

		wp_enqueue_script( 'masteriyo-blocks-editor-js' );

		wp_register_style(
			'masteriyo-blocks-editor',
			plugins_url( 'assets/css/block.css', Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ),
			array(),
			Constants::get( 'MASTERIYO_VERSION' ),
			'all'
		);
		wp_enqueue_style( 'masteriyo-blocks-editor' );
	}

	/**
	 * Add "Masteriyo" categories to the block editor.
	 *
	 * @since 1.18.2
	 *
	 * @param array $block_categories Existing block categories.
	 * @return array Modified block categories.
	 */
	public function block_categories( $block_categories ) {
		array_unshift(
			$block_categories,
			array(
				'slug'  => 'masteriyo-single-course',
				'title' => esc_html__( 'Masteriyo LMS Single Course', 'learning-management-system' ),
			)
		);

		array_unshift(
			$block_categories,
			array(
				'slug'  => 'masteriyo',
				'title' => esc_html__( 'Masteriyo LMS', 'learning-management-system' ),
			)
		);

		return $block_categories;
	}

	/**
	 * Register all block types.
	 *
	 * @since 1.18.2
	 * @return void
	 */
	public function register_block_types() {
		$block_types = $this->get_block_types();
		foreach ( $block_types as $block_type ) {
			new $block_type();
		}
	}

	/**
	 * Get all Masteriyo block classes.
	 *
	 * @since 1.18.2
	 * @return array Array of fully-qualified block class names.
	 */
	private function get_block_types() {
		$lms_block_classes = array(
			Blocks\SingleCourse::class,
			Blocks\SingleCourseTitle::class,
			Blocks\CourseFeatureImage::class,
			Blocks\CourseAuthor::class,
			Blocks\CourseContent::class,
			Blocks\CoursePrice::class,
			Blocks\CourseEnrollButton::class,
			Blocks\CourseStats::class,
			Blocks\CourseHighlight::class,
			Blocks\CourseSearchForm::class,
			Blocks\Courses::class,
			Blocks\CourseCategories::class,
			Blocks\CourseCurriculum::class,
			Blocks\CourseReviews::class,
			Blocks\CourseOverview::class,
			Blocks\CourseComingSoon::class,
			Blocks\CourseCategory::class,
			Blocks\GroupPriceButton::class,
			Blocks\CourseUserProgress::class,
		);

		/**
		 * Filter the list of Masteriyo block types to register.
		 *
		 * @since 1.18.2
		 *
		 * @param array $lms_block_classes Block class names.
		 */
		return apply_filters( 'masteriyo_block_types', $lms_block_classes );
	}
}
