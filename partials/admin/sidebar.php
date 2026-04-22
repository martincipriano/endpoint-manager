<?php
/**
 * Admin sidebar template.
 *
 * @package WPBuoy_Endpoint_Manager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wpbuoy-endpoint-manager-sidebar-widget upgrade-widget">
	<h3><?php esc_html_e( 'Upgrade to Pro', 'wpbuoy-endpoint-manager' ); ?></h3>
	<p><?php esc_html_e( 'Upgrade to WPBuoy Endpoint Manager Pro for advanced features:', 'wpbuoy-endpoint-manager' ); ?></p>
	<ul class="wpbuoy-endpoint-manager-pro-features">
		<li class="wpbuoy-endpoint-manager-pro-feature"><?php esc_html_e( 'Advanced Filters', 'wpbuoy-endpoint-manager' ); ?></li>
		<li class="wpbuoy-endpoint-manager-pro-feature"><?php esc_html_e( 'All Namespaces', 'wpbuoy-endpoint-manager' ); ?></li>
		<li class="wpbuoy-endpoint-manager-pro-feature"><?php esc_html_e( 'Dynamic Endpoint Support', 'wpbuoy-endpoint-manager' ); ?></li>
		<li class="wpbuoy-endpoint-manager-pro-feature"><?php esc_html_e( 'Endpoint Preview', 'wpbuoy-endpoint-manager' ); ?></li>
		<li class="wpbuoy-endpoint-manager-pro-feature"><?php esc_html_e( 'Security Logs', 'wpbuoy-endpoint-manager' ); ?></li>
		<li class="wpbuoy-endpoint-manager-pro-feature"><?php esc_html_e( 'Export Logs', 'wpbuoy-endpoint-manager' ); ?></li>
	</ul>
	<a href="https://wpbuoy.com/product/wpbuoy-endpoint-manager/#pricing" target="_blank" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'wpbuoy-endpoint-manager' ); ?></a>
	<a href="https://wpbuoy.com/product/wpbuoy-endpoint-manager-pro/#features/" target="_blank" class="button button-secondary"><?php esc_html_e( 'View All Features', 'wpbuoy-endpoint-manager' ); ?></a>
</div>

<div class="wpbuoy-endpoint-manager-sidebar-widget support-widget">
	<h3><?php esc_html_e( 'Need Help?', 'wpbuoy-endpoint-manager' ); ?></h3>
	<p><?php esc_html_e( 'Get support and documentation for WPBuoy Endpoint Manager.', 'wpbuoy-endpoint-manager' ); ?></p>
	<ul class="support-links">
		<li><a href="https://wpbuoy.com/product/wpbuoy-endpoint-manager/#faqs" target="_blank"><?php esc_html_e( 'FAQ', 'wpbuoy-endpoint-manager' ); ?></a></li>
		<li><a href="https://wpbuoy.com/docs/wpbuoy-endpoint-manager" target="_blank"><?php esc_html_e( 'Documentation', 'wpbuoy-endpoint-manager' ); ?></a></li>
		<li><a href="https://wpbuoy.com/support" target="_blank"><?php esc_html_e( 'Helpdesk', 'wpbuoy-endpoint-manager' ); ?></a></li>
	</ul>
</div>
