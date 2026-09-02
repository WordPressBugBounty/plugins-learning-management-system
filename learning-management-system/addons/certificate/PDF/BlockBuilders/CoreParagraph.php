<?php
/**
 * WordPress core Paragraph block builder.
 *
 * @since 2.3.7
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

defined( 'ABSPATH' ) || exit;


use simplehtmldom\HtmlDocument;

class CoreParagraph extends CoreHeading {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 2.14.0
	 *
	 * @return string
	 */
	public function build() {
		$this->build_css();
		$html = do_shortcode( $this->block['innerHTML'] );
		return $html;
	}
}
