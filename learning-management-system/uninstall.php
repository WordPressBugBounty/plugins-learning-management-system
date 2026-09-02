<?php

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo Uninstall
 *
 * Uninstalling Masteriyo deletes user roles, pages, tables, and options.
 *
 * @package Masteriyo\Uninstaller
 * @version 1.0.0
 */

use Masteriyo\Roles;
use Masteriyo\Install;
use Masteriyo\Enums\CommentType;
use Masteriyo\PostType\PostType;
use Masteriyo\Taxonomy\Taxonomy;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// A sibling Masteriyo (free or pro) is active: its runtime — this same code —
// is already loaded, so a second autoloader fatals and the data below is still
// in use. Removal belongs to the last Masteriyo out.
if ( function_exists( 'masteriyo' ) ) {
	return;
}

defined( 'MASTERIYO_SLUG' ) || define( 'MASTERIYO_SLUG', 'learning-management-system' );

/*
 * The version, read rather than restated.
 *
 * Both products ship this one file, so a literal here would be one product's
 * number in the other product's build. Each product states its version exactly
 * once — in its entry point's plugin header — and WordPress defines
 * WP_UNINSTALL_PLUGIN as the basename of the plugin it is uninstalling, so that
 * header is reachable from here without knowing which product this is.
 */
if ( ! defined( 'MASTERIYO_VERSION' ) ) {
	$masteriyo_entry_file   = WP_PLUGIN_DIR . '/' . WP_UNINSTALL_PLUGIN;
	$masteriyo_entry_header = is_readable( $masteriyo_entry_file ) ? get_file_data( $masteriyo_entry_file, array( 'Version' => 'Version' ) ) : array();

	// A sentinel rather than a guess: nothing in the uninstall path branches on
	// the version, and an unreadable header must not masquerade as a release.
	define( 'MASTERIYO_VERSION', empty( $masteriyo_entry_header['Version'] ) ? '0.0.0' : $masteriyo_entry_header['Version'] );

	unset( $masteriyo_entry_file, $masteriyo_entry_header );
}

defined( 'MASTERIYO_PLUGIN_FILE' ) || define( 'MASTERIYO_PLUGIN_FILE', __FILE__ );
defined( 'MASTERIYO_PLUGIN_BASENAME' ) || define( 'MASTERIYO_PLUGIN_BASENAME', plugin_basename( MASTERIYO_PLUGIN_FILE ) );
defined( 'MASTERIYO_PLUGIN_DIR' ) || define( 'MASTERIYO_PLUGIN_DIR', dirname( MASTERIYO_PLUGIN_FILE ) );
defined( 'MASTERIYO_ASSETS' ) || define( 'MASTERIYO_ASSETS', dirname( MASTERIYO_PLUGIN_FILE ) . '/assets' );
defined( 'MASTERIYO_TEMPLATES' ) || define( 'MASTERIYO_TEMPLATES', dirname( MASTERIYO_PLUGIN_FILE ) . '/templates' );
defined( 'MASTERIYO_LANGUAGES' ) || define( 'MASTERIYO_LANGUAGES', dirname( MASTERIYO_PLUGIN_FILE ) . '/i18n/languages' );

require_once __DIR__ . '/vendor/autoload.php';

// Keep the declaration conditional: an unconditional top-level function is
// compile-time bound and would fatal before the guard above runs.
if ( ! function_exists( 'masteriyo' ) ) {
	$GLOBALS['masteriyo'] = require_once __DIR__ . '/bootstrap/app.php';

	/**
	 * Return the service container.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class Class name or alias.
	 *
	 * @return \Masteriyo\Masteriyo
	 */
	function masteriyo( $class = 'app' ) {
		global $masteriyo;
		return empty( $class ) ? $masteriyo : $masteriyo->get( $class );
	}
}

if ( masteriyo_string_to_bool( masteriyo_get_setting( 'advance.uninstall.remove_data' ) ) ) {
	global $wpdb;

	Roles::remove_all();

	// Pages.
	wp_trash_post( masteriyo_get_setting( 'general.pages.courses_page_id' ) );
	wp_trash_post( masteriyo_get_setting( 'general.pages.account_page_id' ) );
	wp_trash_post( masteriyo_get_setting( 'general.pages.checkout_page_id' ) );
	wp_trash_post( masteriyo_get_setting( 'general.pages.learn_page_id' ) );
	wp_trash_post( masteriyo_get_setting( 'general.pages.instructor_registration_page_id' ) );
	wp_trash_post( masteriyo_get_setting( 'general.pages.instructors_list_page_id' ) );
	wp_trash_post( masteriyo_get_setting( 'general.pages.course_bundles_page_id' ) );

	// Tables.
	$tables = Install::get_tables();

	foreach ( $tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'masteriyo\_%';" );

	// Delete usermeta.
	$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'masteriyo\_%';" );

	// Delete our data from the post and post meta tables, and remove any additional tables we created.
	$post_types = masteriyo_array_join( ( new PostType() )->all(), ', ', "'{value}'" );
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( {$post_types} );" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

	$comment_types = masteriyo_array_join( CommentType::all(), ', ', "'{value}'" );
	$wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_type IN ( {$comment_types} );" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DELETE meta FROM {$wpdb->commentmeta} meta LEFT JOIN {$wpdb->comments} comments ON comments.comment_ID = meta.comment_id WHERE comments.comment_ID IS NULL;" );

	// Delete term taxonomies.
	foreach ( Taxonomy::all() as $_taxonomy ) {
		$wpdb->delete(
			$wpdb->term_taxonomy,
			array(
				'taxonomy' => $_taxonomy,
			)
		);
	}

	// Delete orphan relationships.
	$wpdb->query( "DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} posts ON posts.ID = tr.object_id WHERE posts.ID IS NULL;" );

	// Delete orphan terms.
	$wpdb->query( "DELETE t FROM {$wpdb->terms} t LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.term_id IS NULL;" );

	// Delete orphan term meta.
	if ( ! empty( $wpdb->termmeta ) ) {
		$wpdb->query( "DELETE tm FROM {$wpdb->termmeta} tm LEFT JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id WHERE tt.term_id IS NULL;" );
	}

	// Delete users associated with the masteriyo roles.
	$roles        = array( 'masteriyo_student', 'masteriyo_instructor' );
	$placeholders = implode( ' OR ', array_fill( 0, count( $roles ), 'meta_value LIKE %s' ) );
	$query        = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'wp_capabilities' AND ($placeholders)";
	$like_roles   = array_map( fn( $role ) => '%' . $role . '%', $roles );
	$user_ids     = $wpdb->get_col( $wpdb->prepare( $query, ...$like_roles ) ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( ! empty( $user_ids ) ) {
		foreach ( $user_ids as $user_id ) {
			wp_delete_user( $user_id );
		}
	}

	// Clear any cached data that has been removed.
	wp_cache_flush();
}
