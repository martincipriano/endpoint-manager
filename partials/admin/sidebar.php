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
<div class="wpbuoy-sidebar-widget wpbuoy-upgrade-widget">
	<h3><?php esc_html_e( 'Upgrade to Pro', 'wpbyem' ); ?></h3>
	<p><?php esc_html_e( 'Upgrade to WPBuoy Endpoint Manager Pro for advanced features:', 'wpbyem' ); ?></p>
	<ul>
		<li><?php esc_html_e( 'Advanced Filters', 'wpbyem' ); ?></li>
		<li><?php esc_html_e( 'Security Logs', 'wpbyem' ); ?></li>
		<li><?php esc_html_e( 'Export Logs', 'wpbyem' ); ?></li>
	</ul>
	<a href="https://wpbuoy.com/product/endpoint-manager/" target="_blank" class="button button-primary">
		<?php esc_html_e( 'Upgrade to Pro', 'wpbyem' ); ?>
	</a>
</div>

<div class="wpbuoy-sidebar-widget wpbuoy-support-widget">
	<h3><?php esc_html_e( 'Need Help?', 'wpbyem' ); ?></h3>
	<ul>
		<li><a href="https://wpbuoy.com/product/endpoint-manager/#faqs" target="_blank"><?php esc_html_e( 'FAQ', 'wpbyem' ); ?></a></li>
		<li><a href="https://wpbuoy.com/endpoint-manager/documentation/" target="_blank"><?php esc_html_e( 'Documentation', 'wpbyem' ); ?></a></li>
		<li><a href="https://wpbuoy.com/my-account/support/" target="_blank"><?php esc_html_e( 'Support', 'wpbyem' ); ?></a></li>
	</ul>
</div>
