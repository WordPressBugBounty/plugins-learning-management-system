<?php
/**
 * WordPress core Spacer block builder.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

class CoreSpacer extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function build() {
		return $this->block['innerHTML'];
	}
}
