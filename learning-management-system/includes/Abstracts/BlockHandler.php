<?php
/**
 * Abstract block handler class.
 *
 * @since 1.18.2
 * @package Masteriyo\Abstracts
 */

namespace Masteriyo\Abstracts;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;

/**
 * Abstract block handler class.
 *
 * @since 1.18.2
 */
abstract class BlockHandler {

	/**
	 * Block namespace.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $namespace = 'masteriyo';

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = '';

	/**
	 * Attributes.
	 *
	 * @since 1.18.2
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Block content.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $content = '';

	/**
	 * Block instance.
	 *
	 * @since 1.18.2
	 * @var \WP_Block
	 */
	protected $block;

	/**
	 * Constructor.
	 *
	 * @since 1.18.2
	 *
	 * @param string $block_name Block name.
	 */
	public function __construct( $block_name = '' ) {
		$this->block_name = empty( $block_name ) ? $this->block_name : $block_name;
		$this->register();
	}

	/**
	 * Register the block.
	 *
	 * @since 1.18.2
	 *
	 * @return void
	 */
	protected function register() {
		if ( empty( $this->block_name ) ) {
			_doing_it_wrong( __CLASS__, esc_html__( 'Block name is not set.', 'learning-management-system' ), '3.1.5' );
			return;
		}

		$metadata = $this->get_metadata_base_dir() . "/$this->block_name/block.json";

		if ( ! file_exists( $metadata ) ) {
			_doing_it_wrong(
				__CLASS__,
				/* Translators: 1: Block name */
				esc_html( sprintf( __( 'Metadata file for %s block does not exist.', 'learning-management-system' ), $this->block_name ) ),
				'3.1.5'
			);
			return;
		}
		register_block_type_from_metadata(
			$metadata,
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Get base metadata path.
	 *
	 * @since 1.18.2
	 *
	 * @return string
	 */
	protected function get_metadata_base_dir() {
		return dirname( MASTERIYO_PLUGIN_FILE ) . '/assets/js/build';
	}

	/**
	 * Get block type.
	 *
	 * @since 1.18.2
	 *
	 * @return string
	 */
	protected function get_block_type() {
		return "$this->namespace/$this->block_name";
	}

	/**
	 * Render callback.
	 *
	 * @since 1.18.2
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block object.
	 *
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$this->attributes = $attributes;
		$this->block      = $block;
		$this->content    = $content;
		$content          = apply_filters(
			"masteriyo_{$this->block_name}_content",
			$this->build_html( $this->content ),
			$this
		);
		return $content;
	}

	/**
	 * Build html.
	 *
	 * @since 1.18.2
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	protected function build_html( $content ) {
		return $content;
	}

	/**
	 * Get a course to use for preview in block editor.
	 *
	 * @since 1.18.2
	 *
	 * @param int $course_id Optional course ID.
	 * @return \Masteriyo\Models\Course|null
	 */
	public function get_block_preview_course( $course_id ) {
		if ( $course_id ) {
			return masteriyo_get_course( $course_id );
		}

		$args  = array(
			'posts_per_page' => 1,
			'post_type'      => PostType::COURSE,
			'author'         => get_current_user_id(),
			'post_status'    => array( PostStatus::PUBLISH, PostStatus::DRAFT ),
		);
		$posts = get_posts( $args );

		return empty( $posts ) ? null : masteriyo_get_course( $posts[0] );
	}


	/**
	 * Checks if the current screen is using the block editor.
	 *
	 * @return bool True if the block editor is active, false otherwise.
	 */
	public function is_block_editor() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			return $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor();
		}

		return false;
	}
}
