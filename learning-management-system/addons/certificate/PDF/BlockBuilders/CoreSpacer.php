<?php
/**
 * WordPress core Spacer block builder.
 *
 * @since 2.3.7
 */

namespace Masteriyo\Addons\Certificate\PDF\BlockBuilders;

defined( 'ABSPATH' ) || exit;


class CoreSpacer extends BlockBuilder {

	/**
	 * Build and return the block HTML.
	 *
	 * @since 2.3.7
	 *
	 * @return string
	 */
	public function build() {
		return $this->block['innerHTML'];
	}
}
