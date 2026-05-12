<?php
/**
 * Abstract base for scoped settings-update abilities.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Base class for abilities that update a single settings sub-tree.
 *
 * Subclasses declare settings_section() to identify which top-level settings
 * key they own. get_input_schema() extracts only that section from the full
 * controller schema so callers cannot write to other sections.
 *
 * @since x.x.x
 */
abstract class AbstractScopedSettingsAbility extends RestProxyAbility {

	/**
	 * The top-level settings section key this ability is scoped to.
	 * One of: general | payments | emails | quiz | advance
	 *
	 * @since x.x.x
	 * @return string
	 */
	abstract protected function settings_section(): string;

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

	/**
	 * {@inheritdoc}
	 * Returns a schema containing only this ability's section property so the
	 * AI caller cannot accidentally write to other sections in one call.
	 */
	public function get_input_schema(): array {
		$full    = parent::get_input_schema();
		$section = $this->settings_section();

		$section_schema = isset( $full['properties'][ $section ] ) ? $full['properties'][ $section ] : array( 'type' => 'object' );

		return array(
			'type'                 => 'object',
			'properties'           => array(
				$section => $section_schema,
			),
			'additionalProperties' => false,
		);
	}
}
