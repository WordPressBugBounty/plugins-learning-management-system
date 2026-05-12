<?php
/**
 * Update Settings ability.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: write Masteriyo settings.
 *
 * @since x.x.x
 */
class UpdateSettingsAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'setting.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'create';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'settings';
	}

	/** {@inheritdoc} */
	public function is_idempotent(): bool {
		return true;
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/settings-update';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Update Settings', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Write Masteriyo LMS settings. Accepts a partial or full settings object. Only provided keys are updated; omitted keys retain their current values. Returns the updated settings object.', 'learning-management-system' );
	}
}
