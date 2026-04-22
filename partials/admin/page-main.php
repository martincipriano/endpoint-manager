<?php
/**
 * Main admin page template.
 *
 * @package WPBuoy_Endpoint_Manager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form action="options.php" method="post" id="wpbuoy-endpoint-manager-form">
		<?php
		settings_fields( 'wpbuoy_endpoint_manager' );
		do_settings_sections( 'wpbuoy-endpoint-manager' );
		?>

		<hr>

		<div class="wpbuoy-endpoint-manager-footer">
			<?php submit_button( __( 'Save Changes', 'wpbuoy-endpoint-manager' ), 'primary', 'submit', true ); ?>
		</div>
	</form>
</div>
