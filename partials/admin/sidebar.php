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
		<li class="wpbuoy-endpoint-manager-pro-feature"><?php esc_html_e( 'Endpoint Preview', 'wpbuoy-endpoint-manager' ); ?></li>
		<li class="wpbuoy-endpoint-manager-pro-feature"><?php esc_html_e( 'Security Logs', 'wpbuoy-endpoint-manager' ); ?></li>
		<li class="wpbuoy-endpoint-manager-pro-feature"><?php esc_html_e( 'Export Logs', 'wpbuoy-endpoint-manager' ); ?></li>
	</ul>
	<a href="https://wpbuoy.com/product/endpoint-manager/" target="_blank" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'wpbuoy-endpoint-manager' ); ?></a>
</div>

<div class="wpbuoy-endpoint-manager-sidebar-widget faq-widget">
	<h3><?php esc_html_e( 'FAQ', 'wpbuoy-endpoint-manager' ); ?></h3>
	<ul class="wpbuoy-endpoint-manager-faq-list">
		<li class="wpbuoy-endpoint-manager-faq-item">
			<button class="wpbuoy-endpoint-manager-faq-question" aria-expanded="false">
				<?php esc_html_e( 'Will disabling endpoints break my site?', 'wpbuoy-endpoint-manager' ); ?>
			</button>
			<div class="wpbuoy-endpoint-manager-faq-answer" hidden>
				<?php esc_html_e( 'Disabling certain endpoints may affect WordPress functionality, plugins, or themes that depend on the REST API. Always test thoroughly after making changes.', 'wpbuoy-endpoint-manager' ); ?>
			</div>
		</li>
		<li class="wpbuoy-endpoint-manager-faq-item">
			<button class="wpbuoy-endpoint-manager-faq-question" aria-expanded="false">
				<?php esc_html_e( 'Can I manage endpoints from plugins and themes?', 'wpbuoy-endpoint-manager' ); ?>
			</button>
			<div class="wpbuoy-endpoint-manager-faq-answer" hidden>
				<?php esc_html_e( 'Plugin and theme endpoints are available in the Pro version. The free version manages WordPress core endpoints only.', 'wpbuoy-endpoint-manager' ); ?>
			</div>
		</li>
		<li class="wpbuoy-endpoint-manager-faq-item">
			<button class="wpbuoy-endpoint-manager-faq-question" aria-expanded="false">
				<?php esc_html_e( 'Does this work with WordPress multisite?', 'wpbuoy-endpoint-manager' ); ?>
			</button>
			<div class="wpbuoy-endpoint-manager-faq-answer" hidden>
				<?php esc_html_e( 'Yes, but the plugin must be activated on each site individually. Network activation is not currently supported.', 'wpbuoy-endpoint-manager' ); ?>
			</div>
		</li>
	</ul>
</div>

<div class="wpbuoy-endpoint-manager-sidebar-widget support-widget">
	<h3><?php esc_html_e( 'Need Help?', 'wpbuoy-endpoint-manager' ); ?></h3>
	<ul class="support-links">
		<li><a href="https://wpbuoy.com/product/endpoint-manager/#faqs" target="_blank"><?php esc_html_e( 'FAQ', 'wpbuoy-endpoint-manager' ); ?></a></li>
		<li><a href="https://wpbuoy.com/product/endpoint-manager/" target="_blank"><?php esc_html_e( 'Documentation', 'wpbuoy-endpoint-manager' ); ?></a></li>
		<li><a href="https://wpbuoy.com/my-account/support" target="_blank"><?php esc_html_e( 'Support', 'wpbuoy-endpoint-manager' ); ?></a></li>
	</ul>
</div>
