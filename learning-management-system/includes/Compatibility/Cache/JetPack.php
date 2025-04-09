<?php
/**
 * Compatibility with JetPack plugin.
 *
 * @since 1.15.0
 */

namespace Masteriyo\Compatibility\Cache;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Abstracts\CachePluginCompatibility;

class JetPack extends CachePluginCompatibility {
	/**
	 * Cache plugin slug.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $plugin = 'jetpack/jetpack.php';

	/**
	 * Do not page.
	 *
	 * @since 1.15.0
	 */
	public function do_not_cache() {
		masteriyo_maybe_define_constant( 'DONOTCACHEPAGE', 1 );
	}
}
