<?php
/**
 * WordPress core Columns block builder.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

use simplehtmldom\HtmlDocument;

class CoreColumns extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function build() {
		$padding          = 32;
		$attrs            = $this->block['attrs'];
		$text_color       = empty( $attrs['textColor'] ) ? masteriyo_array_get( $attrs, 'style.color.text', '' ) : $attrs['textColor'];
		$background_color = empty( $attrs['backgroundColor'] ) ? masteriyo_array_get( $attrs, 'style.color.background', '' ) : $attrs['backgroundColor'];
		$gradient_bg      = empty( $attrs['gradient'] ) ? masteriyo_array_get( $attrs, 'style.color.gradient', '' ) : $attrs['gradient'];

		if ( ! empty( $text_color ) ) {
			$this->pdf->add_style( '#' . $this->get_id(), 'color', masteriyo_certificate_process_color( $text_color ) );
		}

		if ( ! empty( $gradient_bg ) ) {
			$this->pdf->add_style( '#' . $this->get_id(), 'background', masteriyo_certificate_process_color( $gradient_bg ) );
		} elseif ( ! empty( $background_color ) ) {
			$this->pdf->add_style( '#' . $this->get_id(), 'background-color', masteriyo_certificate_process_color( $background_color ) );
		}

		if ( ! empty( $background_color ) || ! empty( $gradient_bg ) ) {
			$this->pdf->add_style( '#' . $this->get_id(), 'padding-top', '22px' );
			$this->pdf->add_style( '#' . $this->get_id(), 'padding-bottom', '22px' );
			$this->pdf->add_style( '#' . $this->get_id(), 'padding-left', '40px' );
			$this->pdf->add_style( '#' . $this->get_id(), 'padding-right', '40px' );

			$padding += 40 * 2;
		}

		$inner_html    = '';
		$total_padding = $padding * ( count( $this->block['innerBlocks'] ) - 1 );

		foreach ( $this->block['innerBlocks'] as $index => &$column ) {
			$block_builder = masteriyo_make_block_builder_instance( $column, $this->pdf );

			$block_builder->set_available_width( $this->get_available_width() - $total_padding );
			$block_builder->set_sibling_index( $index );
			$block_builder->set_siblings_count( count( $this->block['innerBlocks'] ) );

			$inner_html .= $block_builder->build();
		}

		$client = new HtmlDocument( '' );
		$client->load( $this->block['innerHTML'] );
		$client->lastChild()->innertext = $inner_html;

		return $client->save();
	}
}
