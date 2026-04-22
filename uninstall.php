<?php
/**
 * Uninstall script for WPBuoy Endpoint Manager
 *
 * @package WpbuoyEndpointManager
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'wpbuoy_endpoint_manager_blocked_endpoints' );

// Clean up old option names (legacy support)
delete_option( 'wpbuoy_endpoint_manager_settings' );
