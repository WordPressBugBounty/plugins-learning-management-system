<?php
/**
 * WordPress core Paragraph block builder.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

defined( 'ABSPATH' ) || exit;


class CoreParagraph extends CoreHeading {
	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function build() {
		$this->build_css();
		$html = do_shortcode( $this->block['innerHTML'] );
		return $html;
	}

}
