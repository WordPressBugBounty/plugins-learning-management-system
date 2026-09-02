<?php

defined( 'ABSPATH' ) || exit;

/**
 * Create the application.
 *
 * @since 1.0.0
 */

use League\Container\Container;

$masteriyo = new Container();

/**
 * Enable the auto wiring.
 */
$masteriyo->delegate(
	new League\Container\ReflectionContainer()
);

$masteriyo_service_providers = require_once dirname( __DIR__ ) . '/config/app.php';

foreach ( $masteriyo_service_providers as $p ) {
	$masteriyo->addServiceProvider( new $p() );
}

return $masteriyo;
