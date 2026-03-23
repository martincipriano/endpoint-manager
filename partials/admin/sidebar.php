<?php
/**
 * Admin sidebar template.
 *
 * @package RestApiManager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="rest-api-manager-sidebar-widget upgrade-widget">
	<h3><?php esc_html_e( 'Upgrade to Pro', 'rest-api-manager' ); ?></h3>
	<p><?php esc_html_e( 'Upgrade to REST API Manager Pro for advanced features:', 'rest-api-manager' ); ?></p>
	<ul class="rest-api-manager-pro-features">
		<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'Advanced Filters', 'rest-api-manager' ); ?></li>
		<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'All Namespaces', 'rest-api-manager' ); ?></li>
		<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'Dynamic Endpoint Support', 'rest-api-manager' ); ?></li>
		<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'Endpoint Preview', 'rest-api-manager' ); ?></li>
		<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'Security Logs', 'rest-api-manager' ); ?></li>
		<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'Export Logs', 'rest-api-manager' ); ?></li>
	</ul>
	<a href="https://wpbuoy.com/product/rest-api-manager/#pricing" target="_blank" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'rest-api-manager' ); ?></a>
	<a href="https://wpbuoy.com/product/rest-api-manager-pro/#features/" target="_blank" class="button button-secondary"><?php esc_html_e( 'View All Features', 'rest-api-manager' ); ?></a>
</div>

<div class="rest-api-manager-sidebar-widget support-widget">
	<h3><?php esc_html_e( 'Need Help?', 'rest-api-manager' ); ?></h3>
	<p><?php esc_html_e( 'Get support and documentation for REST API Manager.', 'rest-api-manager' ); ?></p>
	<ul class="support-links">
		<li><a href="https://wpbuoy.com/product/rest-api-manager/#faqs" target="_blank"><?php esc_html_e( 'FAQ', 'rest-api-manager' ); ?></a></li>
		<li><a href="https://wpbuoy.com/docs/rest-api-manager" target="_blank"><?php esc_html_e( 'Documentation', 'rest-api-manager' ); ?></a></li>
		<li><a href="https://wpbuoy.com/support" target="_blank"><?php esc_html_e( 'Helpdesk', 'rest-api-manager' ); ?></a></li>
	</ul>
</div>
