<?php
/**
 * Blocks class.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate;

defined( 'ABSPATH' ) || exit;

class Blocks {
	/**
	 * Init.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register all the blocks.
	 *
	 * @since 1.13.0
	 */
	public function register_blocks() {
		register_block_type(
			'masteriyo/certificate',
			array(
				'attributes'    => array(
					'blockCSS'           => array(
						'type' => 'string',
					),
					'backgroundImageURL' => array(
						'type' => 'string',
					),
					'backgroundImageID'  => array(
						'type' => 'number',
					),
					'containerWidth'     => array(
						'type' => 'number',
					),
					'paddingTop'         => array(
						'type'    => 'object',
						'default' => array(
							'value' => 100,
							'unit'  => 'px',
						),
					),
					'pageSize'           => array(
						'type' => 'string',
					),
					'pageOrientation'    => array(
						'type' => 'string',
					),
				),
				'style'         => 'masteriyo-public',
				'editor_script' => 'masteriyo-certificate-blocks',
				'editor_style'  => 'masteriyo-public',
			)
		);

		register_block_type(
			'masteriyo/course-title',
			array(
				'attributes'    => array(
					'clientId'  => array(
						'type' => 'string',
					),
					'blockCSS'  => array(
						'type' => 'string',
					),
					'alignment' => array(
						'type' => 'object',
					),
					'fontSize'  => array(
						'type' => 'object',
					),
					'textColor' => array(
						'type' => 'string',
					),
				),
				'style'         => 'masteriyo-public',
				'editor_script' => 'masteriyo-certificate-blocks',
				'editor_style'  => 'masteriyo-public',
			)
		);

		register_block_type(
			'masteriyo/student-name',
			array(
				'attributes'    => array(
					'clientId'   => array(
						'type' => 'string',
					),
					'blockCSS'   => array(
						'type' => 'string',
					),
					'alignment'  => array(
						'type' => 'object',
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
				),
				'style'         => 'masteriyo-public',
				'editor_script' => 'masteriyo-certificate-blocks',
				'editor_style'  => 'masteriyo-public',
			)
		);

		register_block_type(
			'masteriyo/course-completion-date',
			array(
				'attributes'    => array(
					'clientId'   => array(
						'type' => 'string',
					),
					'blockCSS'   => array(
						'type' => 'string',
					),
					'alignment'  => array(
						'type' => 'object',
					),
					'fontSize'   => array(
						'type' => 'object',
					),
					'textColor'  => array(
						'type' => 'string',
					),
					'dateFormat' => array(
						'type'    => 'string',
						'default' => 'F j, Y',
					),
				),
				'style'         => 'masteriyo-public',
				'editor_script' => 'masteriyo-certificate-blocks',
				'editor_style'  => 'masteriyo-public',
			)
		);
	}
}
