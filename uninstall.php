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

// Delete plugin options
delete_option( 'wpbyem_blocked_endpoints' );

// Clean up old option names (legacy support)
delete_option( 'wpbyem_settings' );
