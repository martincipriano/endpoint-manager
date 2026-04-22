<?php
/**
 * Uninstall script for WPBuoy REST API Manager
 *
 * @package WpbuoyRestApiManager
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'wpbuoy_rest_api_manager_blocked_endpoints' );

// Clean up old option names (legacy support)
delete_option( 'wpbuoy_rest_api_manager_settings' );
