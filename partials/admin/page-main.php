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
		do_settings_sections( 'wpbyem' );
		?>

		<hr>

		<div class="wpbyem-footer">
			<?php submit_button( __( 'Save Changes', 'wpbyem' ), 'primary', 'submit', true ); ?>
		</div>
	</form>
</div>
