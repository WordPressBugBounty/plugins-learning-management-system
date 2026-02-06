<?php
/**
 * Password Strength service provider.
 *
 * @since 2.3.0
 */

namespace Masteriyo\CoreFeatures\PasswordStrength\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\CoreFeatures\PasswordStrength\PasswordStrength;

/**
 * Registers the Password Strength addon in the container.
 *
 * @since 2.1.0
 */
class PasswordStrengthServiceProvider extends AbstractServiceProvider {

	/**
	 * The services provided by this provider.
	 *
	 * @var array
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(
				'core-features.password-strength',
				PasswordStrength::class,
			),
			true
		);
	}


	/**
	 * Register the service.
	 *
	 * @since 2.1.0
	 */
	public function register(): void {
		// Bind using the container reference, no closure argument.
		$this->getContainer()->add(
			'core-features.password-strength',
			function() {
				return new PasswordStrength( $this->getContainer() );
			}
		);
		$this->getContainer()->add(
			PasswordStrength::class,
			function() {
				return $this->getContainer()->get( 'core-features.password-strength' );
			}
		);
	}
}
