<?php
/**
 * Get Settings ability.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: read all Masteriyo settings.
 *
 * @since x.x.x
 */
class GetSettingsAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'setting.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'list';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'settings';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/settings-get';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Get Settings', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Read all Masteriyo LMS settings. Accepts no required input. Returns the full settings object including general, payments, emails, course, quiz, and advanced configuration groups.', 'learning-management-system' );
	}
}
