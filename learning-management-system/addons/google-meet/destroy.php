<?php
/**
 * Destroy/tear down google_meet addon setup.
 *
 * @since 1.11.0
 */

$instructor_caps = array(
	'publish_google_meets',
	'edit_google_meets',
	'edit_private_google_meets',
	'edit_published_google_meets',
	'delete_google_meets',
	'delete_published_google_meets',
	'delete_private_google_meets',
);

$instructor = get_role( 'masteriyo_instructor' );
if ( $instructor ) {
	foreach ( $instructor_caps as $cap ) {
		$instructor->remove_cap( $cap );
	}
}

$manager_caps = array_merge(
	$instructor_caps,
	array(
		'edit_others_google_meets',
		'delete_others_google_meets',
	)
);

$manager = get_role( 'masteriyo_manager' );
if ( $manager ) {
	foreach ( $manager_caps as $cap ) {
		$manager->remove_cap( $cap );

	}
}

$administrator_caps = $manager_caps;

$administrator = get_role( 'administrator' );
if ( $administrator ) {
	foreach ( $administrator_caps as $cap ) {
		$administrator->remove_cap( $cap );

	}
}
