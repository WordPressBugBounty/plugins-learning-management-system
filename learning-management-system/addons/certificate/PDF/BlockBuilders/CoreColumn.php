<?php
/**
 * WordPress core Column block builder.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

class CoreColumn extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function build() {
		$attrs      = $this->block['attrs'];
		$width      = isset( $attrs['width'] ) ? $attrs['width'] : round( 100 / $this->siblings_count ) . '%';
		$width      = masteriyo_percent_to_amount( absint( $width ), $this->get_available_width() );
		$inner_html = '';

		$this->pdf->add_style( '#' . $this->get_id(), 'width', absint( $width ) );
		$this->pdf->add_style( '#' . $this->get_id(), 'float', 'left' );

		$background_color = empty( $attrs['backgroundColor'] ) ? masteriyo_array_get( $attrs, 'style.color.background', '' ) : $attrs['backgroundColor'];
		$text_color       = empty( $attrs['textColor'] ) ? masteriyo_array_get( $attrs, 'style.color.text', '' ) : $attrs['textColor'];

		if ( ! empty( $background_color ) ) {
			$this->pdf->add_style( '#' . $this->get_id(), 'background-color', masteriyo_certificate_process_color( $background_color ) );
		}
		if ( ! empty( $text_color ) ) {
			$this->pdf->add_style( '#' . $this->get_id(), 'color', masteriyo_certificate_process_color( $text_color ) );
		}

		if ( ! is_null( $this->sibling_index ) && $this->sibling_index > 0 ) {
			$this->pdf->add_style( '#' . $this->get_id(), 'margin-left', '31px' );
		}

		if ( empty( $this->block['innerBlocks'] ) ) {
			return '<div id="' . $this->get_id() . '"><div style="height:1px;"></div></div>';
		}

		foreach ( $this->block['innerBlocks'] as $index => &$block ) {
			$block_builder = masteriyo_make_block_builder_instance( $block, $this->pdf );

			$block_builder->set_available_width( $width );
			$block_builder->set_sibling_index( $index );
			$block_builder->set_siblings_count( count( $this->block['innerBlocks'] ) );

			$inner_html .= $block_builder->build();
		}

		return '<div id="' . $this->get_id() . '">' . $inner_html . '</div>';
	}
}
