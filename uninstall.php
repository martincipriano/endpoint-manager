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
 */
function wpbyem_uninstall_site() {
	delete_option( 'wpbyem_blocked_endpoints' );
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
