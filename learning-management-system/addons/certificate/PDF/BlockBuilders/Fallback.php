<?php
/**
 * Default block builder that is used in case there is no specific builder class for a block.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

class Fallback extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function build() {
		return do_shortcode( trim( $this->block['innerHTML'] ) );
	}
}
