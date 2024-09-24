<?php
/**
 * Block builder abstract class.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

use simplehtmldom\HtmlDocument;

abstract class BlockBuilder {
	/**
	 * Certificate PDF class instance that's using this block builder.
	 *
	 * @since 1.13.0
	 *
	 * @var \Masteriyo\Addons\Certificate\PDF\CertificatePDF
	 */
	protected $pdf;

	/**
	 * Block data.
	 *
	 * @since 1.13.0
	 *
	 * @var array
	 */
	protected $block;

	/**
	 * Available width for this block to render.
	 *
	 * @since 1.13.0
	 *
	 * @var null|float
	 */
	protected $available_width = null;

	/**
	 * Index or position (that starts with 0) among its siblings.
	 *
	 * @since 1.13.0
	 *
	 * @var null|integer
	 */
	protected $sibling_index = null;

	/**
	 * Number of siblings of this block (This block is also counted).
	 *
	 * @since 1.13.0
	 *
	 * @var integer
	 */
	protected $siblings_count = 1;

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 *
	 * @param array $block_data The block data.
	 * @param \Masteriyo\Addons\Certificate\PDF\CertificatePDF $pdf The certificate PDF class instance that's using this block builder.
	 */
	public function __construct( $block_data, $pdf ) {
		$this->set_block_data( $block_data );
		$this->set_pdf( $pdf );
	}

	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	abstract public function build();

	/**
	 * Apply some fixes like add ID, classes.
	 *
	 * @since 1.13.0
	 */
	protected function apply_fixes() {
		$this->block['id'] = uniqid( 'block_' );
		$client            = new HtmlDocument( '' );

		if ( empty( $this->block['innerHTML'] ) ) {
			return;
		}

		$client->load( $this->block['innerHTML'] );

		if ( is_object( $client->lastChild() ) ) {
			$class = $client->lastChild()->getAttribute( 'class' );

			$client->lastChild()->setAttribute( 'id', $this->get_id() );
			$client->lastChild()->setAttribute( 'class', $class . ' cb-block' );

			$this->block['innerHTML'] = $client->save();
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set block data.
	 *
	 * @since 1.13.0
	 *
	 * @param array $data
	 */
	public function set_block_data( $data ) {
		$this->block = $data;
		$this->apply_fixes();
	}

	/**
	 * Set available width for this block to render.
	 *
	 * @since 1.13.0
	 *
	 * @param float $available_width
	 */
	public function set_available_width( $available_width ) {
		$this->available_width = $available_width;
	}

	/**
	 * Set siblings count.
	 *
	 * @since 1.13.0
	 *
	 * @param integer $siblings_count
	 */
	public function set_siblings_count( $siblings_count ) {
		$this->siblings_count = $siblings_count;
	}

	/**
	 * Set sibling index.
	 *
	 * @since 1.13.0
	 *
	 * @param integer $sibling_index
	 */
	public function set_sibling_index( $sibling_index ) {
		$this->sibling_index = $sibling_index;
	}

	/**
	 * Set the certificate PDF.
	 *
	 * @since 1.13.0
	 *
	 * @param \Masteriyo\Addons\Certificate\PDF\CertificatePDF $pdf
	 */
	public function set_pdf( $pdf ) {
		$this->pdf = $pdf;
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get block ID.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_id() {
		if ( isset( $this->block['id'] ) ) {
			return $this->block['id'];
		}
		return '';
	}

	/**
	 * Get the block data.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public function get_block_data() {
		return $this->block;
	}

	/**
	 * Get the available width for this block to render.
	 *
	 * @since 1.13.0
	 *
	 * @return float
	 */
	public function get_available_width() {
		return $this->available_width;
	}

	/**
	 * Get the sibling index.
	 *
	 * @since 1.13.0
	 *
	 * @return integer
	 */
	public function get_sibling_index() {
		return $this->sibling_index;
	}

	/**
	 * Get the siblings count.
	 *
	 * @since 1.13.0
	 *
	 * @return integer
	 */
	public function get_siblings_count() {
		return $this->siblings_count;
	}

	/**
	 * Get the certificate PDF.
	 *
	 * @since 1.13.0
	 *
	 * @return \Masteriyo\Addons\Certificate\PDF\CertificatePDF
	 */
	public function get_pdf() {
		return $this->pdf;
	}
}
