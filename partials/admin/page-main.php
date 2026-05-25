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

	<form action="options.php" method="post" id="wpbyem-form">
		<?php
		settings_fields( 'wpbyem' );
		wpbyem()->render_endpoints_field();
		?>

		<hr>

		<div class="wpbyem-footer">
			<?php submit_button( __( 'Save Changes', 'wpbuoy-endpoint-manager' ), 'primary', 'submit', true ); ?>
		</div>
	</form>
</div>
