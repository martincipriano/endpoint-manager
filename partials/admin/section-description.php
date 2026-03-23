<?php
/**
 * Admin section description template.
 *
 * @package RestApiManager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="notice notice-warning">
	<p>
		<strong><?php esc_html_e( 'Warning:', 'rest-api-manager' ); ?></strong>
		<?php esc_html_e( 'Disabling certain endpoints may affect WordPress functionality, plugins, or themes that depend on the REST API. Test thoroughly after making changes.', 'rest-api-manager' ); ?>
	</p>
</div>
