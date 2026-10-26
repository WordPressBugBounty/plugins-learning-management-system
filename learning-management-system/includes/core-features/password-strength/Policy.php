<?php
/**
 * The password policy, in one place.
 *
 * The checklist script and the server-side check both read their rules from
 * here, so the two can never disagree about what the policy says.
 *
 * @package Masteriyo\CoreFeatures\PasswordStrength
 */

namespace Masteriyo\CoreFeatures\PasswordStrength;

use Masteriyo\CoreFeatures\PasswordStrength\Enums\PasswordStrength as Strength;

defined( 'ABSPATH' ) || exit;

/**
 * Password policy.
 */
class Policy {

	/**
	 * Policy settings.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param array $settings Password strength settings.
	 */
	public function __construct( $settings = array() ) {
		$this->settings = wp_parse_args(
			(array) $settings,
			array(
				'min_length' => 4,
				'max_length' => 24,
				'strength'   => Strength::VERY_LOW,
			)
		);
	}

	/**
	 * The messages the policy speaks in, keyed the way the script expects them.
	 *
	 * @return array
	 */
	public static function messages() {
		return array(
			/* translators: %d: number of characters (minimum). */
			'minCharacters'       => __( 'Minimum of %d characters', 'learning-management-system' ),
			/* translators: %d: number of characters (maximum). */
			'maxCharacters'       => __( 'Maximum of %d characters', 'learning-management-system' ),
			'atLeastOneUppercase' => __( 'At least one uppercase letter', 'learning-management-system' ),
			'atLeastOneNumber'    => __( 'At least one number', 'learning-management-system' ),
			'atLeastOneSpecial'   => __( 'At least one special character', 'learning-management-system' ),
			/* translators: %s: comma-separated list of the password rules that were not met. */
			'policyNotMet'        => __( 'Your password does not meet the requirements: %s', 'learning-management-system' ),
		);
	}

	/**
	 * The rules this policy applies, as rule type => the label the checklist shows.
	 *
	 * @return array
	 */
	public function rules() {
		$messages = self::messages();
		$strength = $this->settings['strength'];

		$rules = array(
			'min-length' => sprintf( $messages['minCharacters'], absint( $this->settings['min_length'] ) ),
			'max-length' => sprintf( $messages['maxCharacters'], absint( $this->settings['max_length'] ) ),
		);

		if ( in_array( $strength, array( Strength::LOW, Strength::MEDIUM, Strength::HIGH ), true ) ) {
			$rules['uppercase'] = $messages['atLeastOneUppercase'];
		}

		if ( in_array( $strength, array( Strength::MEDIUM, Strength::HIGH ), true ) ) {
			$rules['number'] = $messages['atLeastOneNumber'];
		}

		if ( Strength::HIGH === $strength ) {
			$rules['special-character'] = $messages['atLeastOneSpecial'];
		}

		return $rules;
	}

	/**
	 * The rules the password breaks, as rule type => label. Empty means it passes.
	 *
	 * @param string $password Password to check.
	 * @return array
	 */
	public function failures( $password ) {
		$password = (string) $password;
		$length   = function_exists( 'mb_strlen' ) ? mb_strlen( $password ) : strlen( $password );
		$failed   = array();

		foreach ( $this->rules() as $type => $label ) {
			if ( ! $this->passes( $type, $password, $length ) ) {
				$failed[ $type ] = $label;
			}
		}

		return $failed;
	}

	/**
	 * Whether the password satisfies one rule.
	 *
	 * @param string $type Rule type.
	 * @param string $password Password to check.
	 * @param int $length Length of the password.
	 * @return boolean
	 */
	protected function passes( $type, $password, $length ) {
		switch ( $type ) {
			case 'min-length':
				return $length >= absint( $this->settings['min_length'] );
			case 'max-length':
				return $length <= absint( $this->settings['max_length'] );
			case 'uppercase':
				return 1 === preg_match( '/[A-Z]/', $password );
			case 'number':
				return 1 === preg_match( '/\d/', $password );
			case 'special-character':
				return 1 === preg_match( '/\W/', $password );
		}

		return true;
	}
}
