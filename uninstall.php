<?php
/**
 * Uninstall script for REST API Manager
 *
 * @package RestApiManager
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'rest_api_manager_blocked_endpoints' );

// Clean up old option names (legacy support)
delete_option( 'rest_api_manager_settings' );
