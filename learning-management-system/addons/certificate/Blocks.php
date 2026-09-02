<?php
/**
 * Blocks class.
 *
 * @since 2.3.7
 */

namespace Masteriyo\Addons\Certificate;

defined( 'ABSPATH' ) || exit;

class Blocks {
	/**
	 * Init.
	 *
	 * @since 2.3.7
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Constructor.
	 *
	 * @since 2.3.7
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register all the blocks.
	 *
	 * @since 2.3.7
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

		/**
		 * Fires after the shared certificate blocks are registered.
		 *
		 * The blocks that ship only with pro register here. Their builders live
		 * in pro's own namespace, which pro appends to
		 * `masteriyo_certificate_block_builder_namespaces`, so a block registered
		 * from here renders in the PDF as well as in the editor.
		 */
		do_action( 'masteriyo_certificate_register_blocks' );
	}
}
