<?php
defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo password strength service providers.
 *
 * @since 2.3.0
 */
return array_unique(
	array(
		'Masteriyo\CoreFeatures\PasswordStrength\Providers\PasswordStrengthServiceProvider',
	)
);
