<?php
/**
 * WordPress core Heading block builder.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

use simplehtmldom\HtmlDocument;

class CoreHeading extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function build() {
		$this->build_css();

		$client = new HtmlDocument( '' );
		$html   = do_shortcode( $this->block['innerHTML'] );

		$client->load( $html );

		$last_child = $client->lastChild();
		$html       = str_replace( $last_child->innertext, str_replace( ' ', '&nbsp;', $last_child->innertext ), $html );

		return $html;
	}

	/**
	 * Loop through the attributes and build css.
	 *
	 * @since 1.13.0
	 */
	protected function build_css() {
		$attrs = $this->block['attrs'];

		$this->pdf->add_style( '#' . $this->get_id(), 'position', 'relative' );

		if ( isset( $attrs['fontSize'] ) ) {
			switch ( $attrs['fontSize'] ) {
				case 'small':
					$this->pdf->add_style( '#' . $this->get_id(), 'font-size', '13px' );
					break;
				case 'medium':
					$this->pdf->add_style( '#' . $this->get_id(), 'font-size', '20px' );
					break;
				case 'large':
					$this->pdf->add_style( '#' . $this->get_id(), 'font-size', '36px' );
					break;
				case 'x-large':
					$this->pdf->add_style( '#' . $this->get_id(), 'font-size', '42px' );
					break;
			}
		}
		if ( isset( $attrs['textAlign'] ) ) {
			$this->pdf->add_style( '#' . $this->get_id(), 'text-align', $attrs['textAlign'] );
		}
		if ( isset( $attrs['textColor'] ) ) {
			if ( strpos( $attrs['textColor'], '#' ) === 0 ) {
				$this->pdf->add_style( '#' . $this->get_id(), 'color', $attrs['textColor'] );
			}
		}
		if ( isset( $attrs['backgroundColor'] ) ) {
			if ( strpos( $attrs['textColor'], '#' ) === 0 ) {
				$this->pdf->add_style( '#' . $this->get_id(), 'background-color', $attrs['backgroundColor'] );
			}
		}
	}
}
