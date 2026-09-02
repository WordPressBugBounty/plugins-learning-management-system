<?php
/**
 * Default block builder that is used in case there is no specific builder class for a block.
 *
 * @since 2.3.7
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

defined( 'ABSPATH' ) || exit;


class Fallback extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 2.3.7
	 *
	 * @return string
	 */
	public function build() {
		return do_shortcode( trim( $this->block['innerHTML'] ) );
	}
}
