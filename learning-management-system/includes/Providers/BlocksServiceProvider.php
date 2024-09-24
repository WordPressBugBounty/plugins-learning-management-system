<?php
/**
 * Blocks class service provider.
 *
 * @since 1.3.0
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\Query\CourseCategoryQuery;
use Masteriyo\Query\CourseQuery;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Constants;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
class BlocksServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * @since 1.3.0
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
	* @since 1.3.0
	*/
	public function register() {
	}

	/**
	 * In much the same way, this method has access to the container
	 * itself and can interact with it however you wish, the difference
	 * is that the boot method is invoked as soon as you register
	 * the service provider with the container meaning that everything
	 * in this method is eagerly loaded.
	 *
	 * If you wish to apply inflectors or register further service providers
	 * from this one, it must be from a bootable service provider like
	 * this one, otherwise they will be ignored.
	 *
	 * @since 1.5.43
	 */
	public function boot() {
		if ( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ) {
			add_filter( 'block_categories_all', array( $this, 'block_categories' ), 999999, 2 );
		} else {
			add_filter( 'block_categories', array( $this, 'block_categories' ), 999999, 2 );
		}

		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'my_custom_editor_inline_styles' ) );
	}

	/**
	 * custom editor inline styles for single course blocks
	 *
	 * @since 1.5.43
	 *
	 * @return void
	 */
	public function my_custom_editor_inline_styles() {
		$custom_css = "
        .editor-styles-wrapper .wp-block[data-type='masteriyo/course-price'] {
            margin: 0;
        }
				.editor-styles-wrapper .wp-block[data-type='masteriyo/course-enroll-button'] {
            margin: 0;
        }
    ";
		wp_add_inline_style( 'wp-block-editor', $custom_css );
	}

	/**
	 * Add "Masteriyo" category to the the blocks listing in post edit screen.
	 *
	 * @since 1.5.43
	 *
	 * @param array $block_categories
	 *
	 * @return array
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
	 * Register all the blocks.
	 *
	 * @since 1.5.43
	 */
	public function register_blocks() {
		$this->register_courses_block();
		$this->register_course_categories_block();
		$this->register_course_author();
		$this->register_course_price();
		$this->register_course_title();
		$this->register_course_enroll_button();
		$this->register_course_feature_image();
		$this->register_course_overview();
		$this->register_course_curriculum();
		$this->register_course_highlights();
		$this->register_course_contents();
		$this->register_course_reviews();
		$this->register_course_search_form();
		$this->register_course_stats();
		$this->register_single_course_block();
	}

	/**
	 * Register the single course block.
	 *
	 * @since 1.12.2
	 */
	private function register_single_course_block() {
		register_block_type(
			'masteriyo/single-course',
			array(
				'attributes'      => array(
					'clientId' => array(
						'type'    => 'string',
						'default' => '',
					),
					'blockCSS' => array(
						'type' => 'string',
					),
					'courseId' => array(
						'type' => 'number',
					),
					'margin'   => array(
						'type' => 'string',
					),
					'padding'  => array(
						'type' => 'object',
					),
				),
				'providesContext' => array(
					'masteriyo/course_id' => 'courseId',
				),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_single_course_block' ),
			)
		);
	}

		/**
	 * Render callback for the course categories block.
	 *
	 * @since 1.12.2
	 */
	public function render_single_course_block( $attr, $context ) {
		$block_css = $attr['blockCSS'] ?? '';
		$client_id = esc_attr( $attr['clientId'] ?? 0 );
		\ob_start();
		do_action( 'masteriyo_blocks_before_single_course', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
			/* .entry-content a{text-decoration: none;} */ 
			.masteriyo-single-course:not(.elementor){
			display: contents;
		}
		.wp-block-columns .masteriyo-time-btn .wp-block[data-type="masteriyo/course-price"],
.wp-block-columns .masteriyo-time-btn .wp-block[data-type="masteriyo/course-enroll-button"] {
	margin: 0px 0px; /* Adjust this value to your needs */
}
		.wp-block-column{
			word-break: normal;
			display: block;
		} 
			.masteriyo-hidden{
				display: none !important;
			}
			.entry-content>style{
				visibility: hidden;
				position:absolute;
				left:0px;
			}
		
			</style>
		<?php
		printf( '<div class="masteriyo-single-course masteriyo-block masteriyo-single-course-block--%s">', esc_attr( $client_id ) );
		echo $context;//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';

			/**
			 * Fires after rendering course course-feature-image in course-feature-image block.
			 *
			 * @since 1.12.2
			 *
			 * @param array $attr Block attributes.
			 */
			do_action( 'masteriyo_blocks_after_single_course', $attr );
			return \ob_get_clean();
	}

	/**
	 * Register the course categories block.
	 *
	 * @since 1.5.43
	 */
	private function register_course_stats() {
		register_block_type(
			'masteriyo/course-stats',
			array(
				'attributes'      => array(
					'clientId' => array(
						'type'    => 'string',
						'default' => '',
					),
					'blockCSS' => array(
						'type' => 'string',
					),
					'courseId' => array(
						'type' => 'number',
					),
					'minWidth' => array(
						'type' => 'object',
					),
				),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_stats' ),
			)
		);
	}

	/**
	 * Register the course categories block.
	 *
	 * @since 1.5.43
	 */
	private function register_course_categories_block() {
		register_block_type(
			'masteriyo/course-categories',
			array(
				'attributes'      => array(
					'clientId'               => array(
						'type'    => 'string',
						'default' => '',
					),
					'count'                  => array(
						'type'    => 'number',
						'default' => 12,
					),
					'columns'                => array(
						'type'    => 'number',
						'default' => 3,
					),
					'hide_courses_count'     => array(
						'type'    => 'string',
						'default' => 'no',
					),
					'include_sub_categories' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_categories_block' ),
			)
		);
	}


	/**
	 * Register the course author block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_author() {
		register_block_type(
			'masteriyo/course-author',
			array(
				'attributes'      => array(
					'clientId'          => array(
						'type'    => 'string',
						'default' => '',
					),
					'blockCSS'          => array(
						'type' => 'string',
					),
					'authors'           => array(
						'type'    => 'array',
						'default' => array(),
					),
					'hideAuthorsAvatar' => array(
						'type'    => 'string',
						'default' => 'no',
					),
					'hideAuthorsName'   => array(
						'type'    => 'string',
						'default' => 'no',
					),
					'height_n_width'    => array(
						'type' => 'object',
					),
					'margin'            => array(
						'type' => 'string',
					),
					'courseId'          => array(
						'type' => 'number',
					),
				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_author_block' ),
			)
		);
	}

	/**
	 * Register the course price block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_price() {
		register_block_type(
			'masteriyo/course-price',
			array(
				'attributes'      => array(
					'clientId'   => array(
						'type' => 'string',
					),
					'blockCSS'   => array(
						'type' => 'string',
					),
					'alignment'  => array(
						'type' => 'string',
					),
					'fontSize'   => array(
						'type' => 'object',
					),
					'textColor'  => array(
						'type' => 'string',
					),
					'nameFormat' => array(
						'type' => 'string',
					),
					'price'      => array(
						'type' => 'string',
					),
					'courseId'   => array(
						'type' => 'number',
					),

				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_price_block' ),
			)
		);
	}

	/**
	 * Register the course title block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_title() {
		register_block_type(
			'masteriyo/single-course-title',
			array(
				'attributes'      => array(
					'clientId'   => array(
						'type' => 'string',
					),
					'blockCSS'   => array(
						'type' => 'string',
					),
					'alignment'  => array(
						'type' => 'string',
					),
					'fontSize'   => array(
						'type'    => 'object',
						'default' => array(
							'value' => '24',
							'unit'  => 'px',
						),
					),
					'textColor'  => array(
						'type' => 'string',
					),
					'nameFormat' => array(
						'type' => 'string',
					),
					'title'      => array(
						'type' => 'string',
					),
					'courseId'   => array(
						'type' => 'number',
					),
				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_title_block' ),
			)
		);
	}

	/**
	 * Register the courses enroll button block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_enroll_button() {
		register_block_type(
			'masteriyo/course-enroll-button',
			array(
				'attributes'      => array(
					'clientId'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'alignment' => array(
						'type'    => 'string',
						'default' => 'left',
					),
					'blockCSS'  => array(
						'type' => 'string',
					),
					'courseId'  => array(
						'type' => 'number',
					),
				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_enroll_button' ),
			)
		);
	}

	/**
	 * Register the course feature image button block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_feature_image() {
		register_block_type(
			'masteriyo/course-feature-image',
			array(
				'attributes'      => array(
					'clientId' => array(
						'type'    => 'string',
						'default' => '',
					),
					'blockCSS' => array(
						'type' => 'string',
					),
					'courseId' => array(
						'type' => 'number',
					),
				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_feature_image' ),
			)
		);
	}

	/**
	 * Register the courses enroll button block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_overview() {
		register_block_type(
			'masteriyo/course-overview',
			array(
				'attributes'      => array(
					'clientId' => array(
						'type'    => 'string',
						'default' => '',
					),
					'courseId' => array(
						'type' => 'number',
					),
					'blockCSS' => array(
						'type' => 'string',
					),
				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_overview' ),
			)
		);
	}


		/**
	 * Render callback for the course stats block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_stats( $attr ) {
		$style     = '';
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );
		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}
		$course = $this->get_block_preview_course( $course_id );

		\ob_start();
		// course-stats
		/**
		 * Fires before rendering course course-stats in course-stats block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_stats', $attr );
		?>
			<style>
				<?php
				echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
				?>
		</style>
		<?php
		printf( '<div class="masteriyo-single-course masteriyo-block masteriyo-course-stats-block--%s">', esc_attr( $client_id ) );
		$comments_count = masteriyo_count_course_comments( $course );

		masteriyo_get_template(
			'single-course/course-stats.php',
			array(
				'course'                    => $course,
				'comments_count'            => $comments_count,
				'enrolled_users_count'      => masteriyo_count_enrolled_users( $course->get_id() ) + $course->get_fake_enrolled_count(),
				'remaining_available_seats' => $course->get_enrollment_limit() > 0 ? $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() ) : 0,
			)
		);
		echo '</div>';
		?>
		<?php

		/**
		 * Fires after rendering course course-feature-image in course-feature-image block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_stats', $attr );
		return \ob_get_clean();
	}


	/**
	 * Render callback for the course feature image block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_overview( $attr ) {
		$style     = '';
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}

		$course = $this->get_block_preview_course( $course_id );
		if ( isset( $attr['height'] ) ) {
			$height = $attr['height'];
			$style .= " height: $height;";
		}
		if ( isset( $attr['width'] ) ) {
			$width = $attr['width'];

			$style .= " width: $width;";
		} else {
			$width = $attr['width'];

			$style .= ' width: 700px;';
		}
		\ob_start();
		// tab-content course-overview
		/**
		 * Fires before rendering course course-feature-image in course-course-feature-image block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $course-feature-image The course enroll button objects.
		 */
		do_action( 'masteriyo_blocks_before_course_overview', $attr );
		?>
		<?php
			printf( '<div class="masteriyo-block masteriyo-course-overview-block--%s">', esc_attr( $client_id ) );
			masteriyo_single_course_overview( $course );
			echo '</div>';
		?>
		<?php

			/**
			 * Fires after rendering course course-feature-image in course-feature-image block.
			 *
			 * @since 1.12.2
			 *
			 * @param array $attr Block attributes.
			 */
			do_action( 'masteriyo_blocks_after_course_overview', $attr );
			return \ob_get_clean();
	}


	/**
	 * Register the courses curriculum button block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_curriculum() {
		register_block_type(
			'masteriyo/course-curriculum',
			array(
				'attributes'      => array(
					'clientId' => array(
						'type'    => 'string',
						'default' => '',
					),
					'courseId' => array(
						'type' => 'number',
					),
					'blockCSS' => array(
						'type' => 'string',
					),
				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'usesContext'     => array( 'masteriyo/single_course' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'view_script'     => 'masteriyo-single-course',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_curriculum' ),
			)
		);
	}

	/**
	 * Render callback for the course curriculum block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_curriculum( $attr, $context, $block ) {

		$style     = '';
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}
		$course = $this->get_block_preview_course( $course_id );

		\ob_start();

		/**
		 * Fires before rendering course curriculum in course-course-curriculum block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $course-curriculum The course enroll button objects.
		 */
		do_action( 'masteriyo_blocks_before_course_curriculum', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>

		<?php
		if ( $course->get_show_curriculum() || masteriyo_can_start_course( $course ) ) {
			$sections = masteriyo_get_course_structure( $course->get_id() );
			printf( '<div class="masteriyo-block masteriyo-course-curriculum-block--%s">', esc_attr( $client_id ) );
			masteriyo_get_template(
				'single-course/curriculum.php',
				array(
					'course'    => $course,
					'sections'  => $sections,
					'is_hidden' => false,
				)
			);
			echo '</div>';
		}
		?>
		<?php

			/**
			 * Fires after rendering course curriculum in course curriculum block.
			 *
			 * @since 1.12.2
			 *
			 * @param array $attr Block attributes.
			 */
			do_action( 'masteriyo_blocks_after_course_curriculum', $attr );
			return \ob_get_clean();
	}

	/**
	 * Register the courses highlights button block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_highlights() {
		register_block_type(
			'masteriyo/course-highlights',
			array(
				'attributes'      => array(
					'clientId' => array(
						'type'    => 'string',
						'default' => '',
					),
					'blockCSS' => array(
						'type' => 'string',
					),
					'courseId' => array(
						'type' => 'number',
					),
				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_highlights' ),
			)
		);
	}

	/**
	 * Render callback for the course highlights block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_highlights( $attr ) {
		$style     = '';
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}
		$course = $this->get_block_preview_course( $course_id );
		// if ( isset( $attr['height'] ) ) {
		// 	$height = $attr['height'];
		// 	$style .= " height: $height;";
		// }
		// if ( isset( $attr['width'] ) ) {
		// 	$width = $attr['width'];

		// 	$style .= " width: $width;";
		// }

		\ob_start();

		/**
		 * Fires before rendering course highlights in course-course-highlights block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $course-highlights The course enroll button objects.
		 */
		do_action( 'masteriyo_blocks_before_course_highlights', $attr );
		?>
		<style>
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>

		<?php
		printf( '<div class="masteriyo-block masteriyo-course-highlights-block--%s">', esc_attr( $client_id ) );
		masteriyo_single_course_highlights( $course );
		echo '</div>'
		?>

		<?php
				/**
				 * Fires after rendering course highlights in course_highlights block.
				 *
				 * @since 1.12.2
				 *
				 * @param array $attr Block attributes.
				 */
				do_action( 'masteriyo_blocks_after_course_highlights', $attr );
				return \ob_get_clean();
	}

	/**
	 * Register the courses contents button block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_contents() {
		register_block_type(
			'masteriyo/course-contents',
			array(
				'attributes'      => array(
					'clientId' => array(
						'type'    => 'string',
						'default' => '',
					),
					'course'   => array(
						'type' => 'string',
					),
					'courseId' => array(
						'type' => 'number',
					),
					'blockCSS' => array(
						'type' => 'string',
					),
				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'view_script'     => 'masteriyo-single-course',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_contents' ),
			)
		);
	}

	/**
	 * Render callback for the course contents block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_contents( $attr ) {
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}
		$course = $this->get_block_preview_course( $course_id );

		\ob_start();

		/**
		 * Fires before rendering course contents in course-course-contents block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $course-contents The course enroll button objects.
		 */
		do_action( 'masteriyo_blocks_before_course_contents', $attr );
		?>
				<style>
		<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>  

		</style> 
		<?php
		$class[] = 'masteriyo-block masteriyo-course-content-block--%s';

			printf( '<div class="masteriyo-block masteriyo-course-content-block--%s">', esc_attr( $client_id ) );
			masteriyo_get_template(
				'single-course/main-content.php',
				array(
					'course' => $course,
				)
			);
			echo '</div>'
		?>
		<?php
				/**
				 * Fires after rendering course highlights in course_highlights block.
				 *
				 * @since 1.12.2
				 *
				 * @param array $attr Block attributes.
				 */
				do_action( 'masteriyo_blocks_after_course_contents', $attr );
				return \ob_get_clean();
	}





	/**
	 * Register the courses review block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_reviews() {
		register_block_type(
			'masteriyo/course-reviews',
			array(
				'attributes'      => array(
					'clientId' => array(
						'type'    => 'string',
						'default' => '',
					),
					'blockCSS' => array(
						'type' => 'string',
					),
					'courseId' => array(
						'type' => 'number',
					),
				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'view_script'     => 'masteriyo-single-course',
				'editor_style'    => 'masteriyo-public',
				// 'parent'          => array( 'masteriyo/single-course' ),
				'render_callback' => array( $this, 'render_course_reviews' ),
			)
		);
	}

	/**
	 * Render callback for the course reviews block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_reviews( $attr ) {
		$style     = '';
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}

		$course = $this->get_block_preview_course( $course_id );

		\ob_start();

		/**
		 * Fires before rendering course reviews in course-course-reviews block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $course-reviews The course reviews objects.
		 */
		do_action( 'masteriyo_blocks_before_course_reviews', $attr );
		?>

		<style>
		<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>  
		</style> 

		<?php
		if ( $course->is_review_allowed() ) {
			$reviews_and_replies = masteriyo_get_course_reviews_and_replies( $course );
			printf( '<div class="masteriyo-block masteriyo-course-reviews-block--%s">', esc_attr( $client_id ) );
			masteriyo_get_template(
				'single-course/reviews.php',
				array(
					'course'         => $course,
					'course_reviews' => $reviews_and_replies['reviews'],
					'replies'        => $reviews_and_replies['replies'],
					'is_hidden'      => false,
				)
			);
			echo '</div>';
		} else {
			null;
		}

				/**
				 * Fires after rendering course highlights in course_reviews block.
				 *
				 * @since 1.12.2
				 *
				 * @param array $attr Block attributes.
				 */
				do_action( 'masteriyo_blocks_after_course_reviews', $attr );
				return \ob_get_clean();
	}

	/**
	 * Register the courses review block.
	 *
	 * @since 1.12.2
	 */
	private function register_course_search_form() {
		register_block_type(
			'masteriyo/course-search-form',
			array(
				'attributes'      => array(
					'clientId'       => array(
						'type'    => 'string',
						'default' => '',
					),
					'height_n_width' => array(
						'type' => 'object',
					),
					'courseId'       => array(
						'type' => 'number',
					),
				),
				'usesContext'     => array( 'masteriyo/course_id' ),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_course_search_form' ),
			)
		);
	}

	/**
	 * Render callback for the course reviews block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_search_form( $attr ) {
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		$style = '';
		if ( isset( $attr['height'] ) ) {
			$height = $attr['height'];
			$style .= " height: $height;";
		}
		if ( isset( $attr['width'] ) ) {
			$width = $attr['width'];

			$style .= " width: $width;";
		}

		\ob_start();

		/**
		 * Fires before rendering course search form in course-course-search form block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $course-search form The course search form objects.
		 */
		do_action( 'masteriyo_blocks_before_course_search_form', $attr );

				masteriyo_course_search_form();
				/**
				 * Fires after rendering course highlights in course_search_form block.
				 *
				 * @since 1.12.2
				 *
				 * @param array $attr Block attributes.
				 */
				do_action( 'masteriyo_blocks_after_course_search_form', $attr );
				return \ob_get_clean();
	}



	/**
	 * Render callback for the course feature image block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_feature_image( $attr ) {
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}

		$course = $this->get_block_preview_course( $course_id );
		$height = '';
		$width  = '';

		if ( isset( $attr['height_n_width'] ) ) {
			$height = $attr['height_n_width']['height'];
		}

		if ( isset( $attr['height_n_width'] ) ) {
			$width = $attr['height_n_width']['width'];
		}
		\ob_start();
		/**
		 * Fires before rendering course course-feature-image in course-course-feature-image block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $course-feature-image The course enroll button objects.
		 */
		do_action( 'masteriyo_blocks_before_course_feature_image', $attr );

		?>
		<style>
		<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>  
		.masteriyo-feature-img{
			height: <?php echo esc_attr( $height ); ?>;
			width: <?php echo esc_attr( $width ); ?>;
		}
		</style> 
		<?php

		printf( '<div class="masteriyo-block masteriyo-course-featured-image--%s">', esc_attr( $client_id ) );
		masteriyo_get_template(
			'single-course/featured-image.php',
			array(
				'course'     => $course,
				'difficulty' => $course->get_difficulty(),
			)
		);
		?>
	</div>
		<?php

		/**
		 * Fires after rendering course course-feature-image in course-feature-image block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_feature_image', $attr );
		return \ob_get_clean();
	}


	/**
	 * Render callback for the course enroll button block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_enroll_button( $attr ) {
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}

		$course = $this->get_block_preview_course( $course_id );

		\ob_start();
		/**
		 * Fires before rendering course enroll_button in course-enroll_button block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_before_course_enroll_button', $attr );
		?>
		<style>
		<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>  
			/* .masteriyo-single-course--btn{
			text-wrap: nowrap;
			} */
		  
		</style> 
		<?php
		printf( '<div class="masteriyo-block masteriyo-enroll-button-block--%s">', esc_attr( $attr ['clientId'] ) );
		do_action( 'masteriyo_template_enroll_button', $course );
		printf( '</div>' );

		/**
		 * Fires after rendering course enroll_button in course-enroll-button block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_enroll_button', $attr );
		return \ob_get_clean();
	}

	/**
	 * Render callback for the course title block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_title_block( $attr ) {
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}

		$course = $this->get_block_preview_course( $course_id );
		if ( ! $course ) {
			return;
		}

		$client_id = esc_attr( $attr['clientId'] ?? '' );

		\ob_start();

		/**
		 * Fires before rendering course title in course-title block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $title The course title objects.
		 */
		do_action( 'masteriyo_blocks_before_course_title', $attr );
		?>
		<style>
		<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>  
		</style> 
		<?php
		printf( '<h1 class="masteriyo-block masteriyo-title-block--%s" >', esc_attr( $client_id ) );
		?>
		<?php echo esc_html( $course->get_title() ); ?>
		<?php
		printf( '</h1>' );

		/**
		 * Fires after rendering course title in course-title block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_title', $attr );
		return \ob_get_clean();
	}

	/**
	 * Render callback for the course title block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_price_block( $attr, $content, $block ) {
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}

		$course = $this->get_block_preview_course( $course_id );
		if ( ! $course ) {
			return;
		}

		$client_id = esc_attr( $attr['clientId'] ?? '' );

		\ob_start();

		/**
		 * Fires before rendering course price in course-price block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $price The course price objects.
		 */
		do_action( 'masteriyo_blocks_before_course_price', $attr );
		?>
			<style> 
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
			.amount{
				text-wrap: nowrap;
			}
			</style>
		<?php

		printf( '<div class="masteriyo-block masteriyo-price-block--%s" >', esc_attr( $client_id ) );
		?>
		<div class="masteriyo-course-price">
		<?php if ( ! empty( $course->get_sale_price() ) ) : ?>
			<del class="old-amount"><?php echo wp_kses_post( masteriyo_price( $course->get_regular_price() ) ); ?></del>
		<?php endif; ?>
		<span class="current-amount"><?php echo wp_kses_post( masteriyo_price( $course->get_price() ) ); ?></span>
		</div>	
		<?php
		printf( '</div>' );

		/**
		 * Fires after rendering course price in course-price block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_price', $attr );
		return \ob_get_clean();
	}

	/**
	 * Render callback for the course author block.
	 *
	 * @since 1.12.2
	 */
	public function render_course_author_block( $attr ) {
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $attr['courseId'] ) {
			\ob_start();
			?>
				<div style="color:red;padding-left:60px">Please ensure that only individual course elements are added inside the single course block container.</div>
			<?php
			return \ob_get_clean();
		}

		$course              = $this->get_block_preview_course( $course_id );
		$client_id           = esc_attr( $attr['clientId'] ?? '' );
		$hide_authors_name   = 'yes' === $attr['hideAuthorsName'] ? 'none' : 'inline-block';
		$hide_authors_avatar = 'yes' === $attr['hideAuthorsAvatar'] ? 'none' : 'inline-block';
		\ob_start();

		/**
		 * Fires before rendering course author in course-author block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $author The course author objects.
		 */
		do_action( 'masteriyo_blocks_before_course_author', $attr );
		?>
		<style> 
			<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
			.masteriyo-single-course .masteriyo-course-author img{
				display:<?php $hide_authors_avatar; ?>
			}
			.masteriyo-single-course .masteriyo-course-author .masteriyo-course-author--name{
				display:<?php $hide_authors_name; ?>
			}
			.masteriyo-single-course .masteriyo-course-author a{
				text-decoration: none;
			}
		</style>
		<?php
		printf( '<div class="masteriyo-block masteriyo-title-block--%s" >', esc_attr( $client_id ) );
		// $card_class = empty( $settings['card_hover_animation'] ) ? '' : sprintf( 'elementor-animation-%s', $settings['card_hover_animation'] );

		masteriyo_get_template(
			'single-course/author-and-rating.php',
			array(
				'course' => $course,
				'author' => masteriyo_get_user( $course->get_author_id() ),
			)
		);

					printf( '</div >' );
		?>
		<?php

		/**
		 * Fires after rendering course author in course-author block.
		 *
		 * @since 1.12.2
		 *
		 * @param array $attr Block attributes.
		 */
		do_action( 'masteriyo_blocks_after_course_author', $attr );
		return \ob_get_clean();
	}

	/**
	 * Render callback for the course categories block.
	 *
	 * @since 1.5.43
	 */
	public function render_course_categories_block( $attr ) {
		$columns = absint( $attr['columns'] );

		if ( 0 === $columns ) {
			$columns = 1;
		}
		if ( 4 < $columns ) {
			$columns = 4;
		}
		$attr['columns']    = $columns;
		$categories         = $this->get_categories( $attr );
		$attr['categories'] = $categories;

		\ob_start();

		/**
		 * Fires before rendering course categories in course-categories block.
		 *
		 * @since 1.3.0
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $categories The course categories objects.
		 */
		do_action( 'masteriyo_blocks_before_course_categories', $attr, $categories );

		masteriyo_get_template( 'shortcodes/course-categories/list.php', $attr );

		/**
		 * Fires after rendering course categories in course-categories block.
		 *
		 * @since 1.3.0
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $categories The course categories objects.
		 */
		do_action( 'masteriyo_blocks_after_course_categories', $attr, $categories );
		return \ob_get_clean();
	}

	/**
	 * Get categories to display for the shortcode.
	 *
	 * @since 1.5.43
	 *
	 * @return \Masteriyo\Models\CourseCategory[]
	 */
	protected function get_categories( $attr ) {
		$args = array(
			'order'   => 'ASC',
			'orderby' => 'name',
			'number'  => absint( $attr['count'] ),
		);

		if ( ! masteriyo_string_to_bool( $attr['include_sub_categories'] ) ) {
			$args['parent'] = 0;
		}

		$query      = new CourseCategoryQuery( $args );
		$categories = $query->get_categories();

		/**
		 * Filters categories to display in course categories block.
		 *
		 * @since 1.3.0
		 *
		 * @param \Masteriyo\Models\CourseCategory[] $categories Categories list.
		 * @param mixed[] $args Query args.
		 * @param \Masteriyo\Query\CourseCategoryQuery $query The course category query object.
		 */
		return apply_filters( 'masteriyo_shortcode_course_categories', $categories, $args, $query );
	}

	/**
	 * Register the courses block.
	 *
	 * @since 1.5.43
	 */
	private function register_courses_block() {
		register_block_type(
			'masteriyo/courses',
			array(
				'attributes'      => array(
					'clientId'                => array(
						'type'    => 'string',
						'default' => '',
					),
					'count'                   => array(
						'type'    => 'number',
						'default' => 12,
					),
					'columns'                 => array(
						'type'    => 'number',
						'default' => 3,
					),
					'categoryIds'             => array(
						'type'    => 'array',
						'default' => array(),
					),
					'sortBy'                  => array(
						'type'    => 'string',
						'default' => 'date',
					),
					'sortOrder'               => array(
						'type'    => 'string',
						'default' => 'desc',
					),
					'startCourseButtonBorder' => array(
						'type' => 'object',
					),
				),
				'style'           => 'masteriyo-public',
				'editor_script'   => 'masteriyo-blocks',
				'editor_style'    => 'masteriyo-public',
				'render_callback' => array( $this, 'render_courses_block' ),
			)
		);
	}

	/**
	 * Render callback for the courses block.
	 *
	 * @since 1.5.43
	 */
	public function render_courses_block( $attr ) {
			$args = array(
				'limit'    => absint( $attr['count'] ),
				'order'    => 'DESC',
				'orderby'  => 'date',
				'category' => empty( $attr['categoryIds'] ) ? array() : $attr['categoryIds'],
				'status'   => PostStatus::PUBLISH,
			);

			$course_query = new CourseQuery( $args );
			$client_id    = (string) $attr['clientId'];

			/**
			 * Filters courses to display in courses block.
			 *
			 * @since 1.3.0
			 *
			 * @param \Masteriyo\Models\Course[]|\Masteriyo\Models\Course $courses Single course object or list of course objects.
			 */
			$courses = apply_filters( 'masteriyo_shortcode_courses_result', $course_query->get_courses() );

			masteriyo_set_loop_prop( 'columns', absint( $attr['columns'] ) );

			\ob_start();

			printf( '<div class="masteriyo-block masteriyo-block-%s masteriyo-courses-block">', esc_attr( $client_id ) );

			if ( count( $courses ) > 0 ) {
				$original_course = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;

				/**
				 * Fires before rendering courses in the courses block.
				 *
				 * @since 1.3.0
				 *
				 * @param array $attr The courses block attributes.
				 * @param \Masteriyo\Models\Course[] $courses The course objects.
				 */
				do_action( 'masteriyo_blocks_before_courses_loop', $attr, $courses );
				masteriyo_course_loop_start();

				foreach ( $courses as $course ) {
					$GLOBALS['course'] = $course;

					\masteriyo_get_template_part( 'content', 'course' );
				}

				$GLOBALS['course'] = $original_course;

				masteriyo_course_loop_end();

				/**
				 * Fires before rendering courses in the courses block.
				 *
				 * @since 1.3.0
				 *
				 * @param array $attr The courses block attributes.
				 * @param \Masteriyo\Models\Course[] $courses The course objects.
				 */
				do_action( 'masteriyo_blocks_after_courses_loop', $attr, $courses );
				masteriyo_reset_loop();
			} else {
				/**
				 * Fires when there are no courses found in the courses block.
				 *
				 * @since 1.3.0
				 */
				do_action( 'masteriyo_blocks_no_courses_found' );
			}
			echo '</div>';

			return \ob_get_clean();
	}



	/**
		 * Get a course to use for preview in block editor.
		 *
		 * @since 1.12.2
		 *
		 * @return \Masteriyo\Models\Course|null
		 */
	public function get_block_preview_course( $course_id ) {
		global $course;
		if ( empty( $course ) ) {
			if ( 0 !== $course_id ) {
				$course = masteriyo_get_course( $course_id );
			}

			if ( ! $course_id ) {
				$args   = array(
					'posts_per_page' => 1,
					'post_type'      => PostType::COURSE,
					'author'         => get_current_user_id(),
					'post_status'    => array( PostStatus::PUBLISH, PostStatus::DRAFT ),
				);
				$posts  = get_posts(
					$args
				);
				$course = empty( $posts ) ? null : masteriyo_get_course( $posts[0] );
			}
		}
		return $course;
	}
}
