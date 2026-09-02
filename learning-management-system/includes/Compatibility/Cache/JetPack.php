<?php
/**
 * Compatibility with JetPack plugin.
 *
 * @since 2.16.0
 */

namespace Masteriyo\Compatibility\Cache;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Abstracts\CachePluginCompatibility;

class JetPack extends CachePluginCompatibility {
	/**
	 * Cache plugin slug.
	 *
	 * @since 2.16.0
	 *
	 * @var string
	 */
	protected $plugin = 'jetpack/jetpack.php';

	/**
	 * Do not page.
	 *
	 * @since 2.16.0
	 */
	public function do_not_cache() {
		masteriyo_maybe_define_constant( 'DONOTCACHEPAGE', 1 );
	}
}
