<?php
/**
 * Masteriyo certificate service providers.
 *
 * @since 1.13.0
 */

use Masteriyo\Addons\Certificate\Providers\CertificateServiceProvider;

return array_unique(
	array(
		CertificateServiceProvider::class,
	)
);
