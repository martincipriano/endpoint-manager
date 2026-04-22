<?php
/**
 * Admin sidebar template.
 *
 * @package WpbuoyRestApiManager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wpbuoy-rest-api-manager-sidebar-widget upgrade-widget">
	<h3><?php esc_html_e( 'Upgrade to Pro', 'wpbuoy-rest-api-manager' ); ?></h3>
	<p><?php esc_html_e( 'Upgrade to WPBuoy REST API Manager Pro for advanced features:', 'wpbuoy-rest-api-manager' ); ?></p>
	<ul class="wpbuoy-rest-api-manager-pro-features">
		<li class="wpbuoy-rest-api-manager-pro-feature"><?php esc_html_e( 'Advanced Filters', 'wpbuoy-rest-api-manager' ); ?></li>
		<li class="wpbuoy-rest-api-manager-pro-feature"><?php esc_html_e( 'All Namespaces', 'wpbuoy-rest-api-manager' ); ?></li>
		<li class="wpbuoy-rest-api-manager-pro-feature"><?php esc_html_e( 'Dynamic Endpoint Support', 'wpbuoy-rest-api-manager' ); ?></li>
		<li class="wpbuoy-rest-api-manager-pro-feature"><?php esc_html_e( 'Endpoint Preview', 'wpbuoy-rest-api-manager' ); ?></li>
		<li class="wpbuoy-rest-api-manager-pro-feature"><?php esc_html_e( 'Security Logs', 'wpbuoy-rest-api-manager' ); ?></li>
		<li class="wpbuoy-rest-api-manager-pro-feature"><?php esc_html_e( 'Export Logs', 'wpbuoy-rest-api-manager' ); ?></li>
	</ul>
	<a href="https://wpbuoy.com/product/wpbuoy-rest-api-manager/#pricing" target="_blank" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'wpbuoy-rest-api-manager' ); ?></a>
	<a href="https://wpbuoy.com/product/wpbuoy-rest-api-manager-pro/#features/" target="_blank" class="button button-secondary"><?php esc_html_e( 'View All Features', 'wpbuoy-rest-api-manager' ); ?></a>
</div>

<div class="wpbuoy-rest-api-manager-sidebar-widget support-widget">
	<h3><?php esc_html_e( 'Need Help?', 'wpbuoy-rest-api-manager' ); ?></h3>
	<p><?php esc_html_e( 'Get support and documentation for WPBuoy REST API Manager.', 'wpbuoy-rest-api-manager' ); ?></p>
	<ul class="support-links">
		<li><a href="https://wpbuoy.com/product/wpbuoy-rest-api-manager/#faqs" target="_blank"><?php esc_html_e( 'FAQ', 'wpbuoy-rest-api-manager' ); ?></a></li>
		<li><a href="https://wpbuoy.com/docs/wpbuoy-rest-api-manager" target="_blank"><?php esc_html_e( 'Documentation', 'wpbuoy-rest-api-manager' ); ?></a></li>
		<li><a href="https://wpbuoy.com/support" target="_blank"><?php esc_html_e( 'Helpdesk', 'wpbuoy-rest-api-manager' ); ?></a></li>
	</ul>
</div>
