<?php
/**
 * Uninstall script for WPBuoy Endpoint Manager
 *
 * @package WPBuoy_Endpoint_Manager
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up all plugin data for a single site.
 *
 * F-26: previously left wpbyem_rate_limit_settings (log retention + role
 * bypass config) and the per-user Logs "items per page" meta behind after
 * an explicit uninstall.
 */
function wpbyem_uninstall_site() {
	global $wpdb;
	delete_option( 'wpbyem_blocked_endpoints' );
	delete_option( 'wpbyem_rate_limit_settings' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpbyem_logs" );

	// Delete per-user meta (object_id ignored, delete_all = true — every user, not just the current one).
	delete_metadata( 'user', 0, 'wpbyem_logs_per_page', '', true );

	// Redundant with plugin_deactivation() clearing the same hook, kept here
	// for the same reason as Pro's uninstall.php: uninstall must not assume
	// deactivation ran cleanly first.
	wp_clear_scheduled_hook( 'wpbyem_cleanup_logs' );
}

if ( is_multisite() ) {
	$sites = get_sites( array( 'number' => 0 ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	foreach ( $sites as $site ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		switch_to_blog( $site->blog_id );
		wpbyem_uninstall_site();
		restore_current_blog();
	}
} else {
	wpbyem_uninstall_site();
}
