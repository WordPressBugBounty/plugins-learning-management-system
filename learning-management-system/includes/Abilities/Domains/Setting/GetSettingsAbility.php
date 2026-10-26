<?php
/**
 * Get Settings ability.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: read all Masteriyo settings.
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

	/** The settings are one object, not a page of records. */
	protected function returns_collection(): bool {
		return false;
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
		return sprintf(
			'Read all %s LMS settings. Accepts no required input. Returns the full settings object including general, payments, emails, course, quiz, and advanced configuration groups.',
			masteriyo_get_plugin_name()
		);
	}
}
