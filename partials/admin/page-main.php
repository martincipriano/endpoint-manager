<?php
/**
 * Main admin page template.
 *
 * @package WpbuoyRestApiManager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form action="options.php" method="post" id="wpbuoy-rest-api-manager-form">
		<?php
		settings_fields( 'wpbuoy_rest_api_manager' );
		do_settings_sections( 'wpbuoy-rest-api-manager' );
		?>

		<hr>

		<div class="wpbuoy-rest-api-manager-footer">
			<?php submit_button( __( 'Save Changes', 'wpbuoy-rest-api-manager' ), 'primary', 'submit', true ); ?>
		</div>
	</form>
</div>
