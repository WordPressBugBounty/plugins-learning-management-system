<?php
/**
 * WordPress core Image block builder.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

use simplehtmldom\HtmlDocument;
use Masteriyo\Addons\Certificate\Models\Setting;

class CoreImage extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function build() {
		$client = new HtmlDocument( '' );
		$html   = $this->block['innerHTML'];

		$client->load( $html );

		$img_dom = $client->find( 'img' );

		/**
		 * Added setting to enable absolute image path due to the issue with the image not showing in the PDF.
		 *
		 * @since 1.13.0
		 */
		$use_absolute_path = masteriyo_bool_to_string( Setting::get( 'use_absolute_img_path' ) );

		if ( ! empty( $img_dom ) && isset( $img_dom[0]->attr['src'] ) ) {
			$src = $img_dom[0]->attr['src'];

			if ( ! empty( $src ) && ! masteriyo_is_certificate_html_inspection_mode() && 'no' === $use_absolute_path ) {
				$html = str_replace( $src, masteriyo_get_image_relative_path( $src ), $html );
			}
		}

		return $html;
	}
}
