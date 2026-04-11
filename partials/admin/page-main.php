<?php
/**
 * Main admin page template.
 *
 * @package RestApiManager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form action="options.php" method="post" id="rest-api-manager-form">
		<?php
		settings_fields( 'rest_api_manager' );
		do_settings_sections( 'rest-api-manager' );
		?>

		<hr>

		<div class="rest-api-manager-footer">
			<?php submit_button( __( 'Save Changes', 'rest-api-manager' ), 'primary', 'submit', true ); ?>
		</div>
	</form>
</div>
