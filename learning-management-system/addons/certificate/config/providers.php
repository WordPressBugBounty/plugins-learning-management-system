<?php

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo certificate service providers.
 *
 * @since 2.3.7
 */

use Masteriyo\Addons\Certificate\Providers\CertificateServiceProvider;

return array_unique(
	array(
		CertificateServiceProvider::class,
	)
);
