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
	<h3><?php esc_html_e( 'Upgrade to Pro', 'wpbuoy-endpoint-manager' ); ?></h3>
	<p><?php esc_html_e( 'Upgrade to WPBuoy Endpoint Manager Pro for advanced features:', 'wpbuoy-endpoint-manager' ); ?></p>
	<ul>
		<li><?php esc_html_e( 'Advanced endpoint filtering and search', 'wpbuoy-endpoint-manager' ); ?></li>
		<li><?php esc_html_e( 'Dynamic route management', 'wpbuoy-endpoint-manager' ); ?></li>
		<li><?php esc_html_e( 'Security logs and analytics', 'wpbuoy-endpoint-manager' ); ?></li>
		<li><?php esc_html_e( 'Export logs to CSV', 'wpbuoy-endpoint-manager' ); ?></li>
		<li><?php esc_html_e( 'Automatic plugin updates', 'wpbuoy-endpoint-manager' ); ?></li>
	</ul>
	<a href="https://wpbuoy.com/product/endpoint-manager/" target="_blank" class="button button-primary">
		<?php esc_html_e( 'Upgrade to Pro', 'wpbuoy-endpoint-manager' ); ?>
	</a>
</div>

<div class="wpbuoy-sidebar-widget wpbuoy-support-widget">
	<h3><?php esc_html_e( 'Need Help?', 'wpbuoy-endpoint-manager' ); ?></h3>
	<ul>
		<li><a href="https://wpbuoy.com/product/endpoint-manager/#faqs" target="_blank"><?php esc_html_e( 'FAQ', 'wpbuoy-endpoint-manager' ); ?></a></li>
		<li><a href="https://wpbuoy.com/endpoint-manager/documentation/" target="_blank"><?php esc_html_e( 'Documentation', 'wpbuoy-endpoint-manager' ); ?></a></li>
		<li><a href="https://wpbuoy.com/my-account/support/" target="_blank"><?php esc_html_e( 'Support', 'wpbuoy-endpoint-manager' ); ?></a></li>
	</ul>
</div>
