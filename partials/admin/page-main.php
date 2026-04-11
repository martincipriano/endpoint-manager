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
			<label class="rest-api-manager-save-confirmation">
				<input type="checkbox" id="rest-api-manager-confirm" name="rest_api_manager_confirm" value="1">
				<span><?php esc_html_e( 'I acknowledge the risks and accept full responsibility for any impact these changes may cause.', 'rest-api-manager' ); ?></span>
			</label>
			<?php submit_button( __( 'Save Changes', 'rest-api-manager' ), 'primary', 'submit', true, array( 'disabled' => 'disabled' ) ); ?>
		</div>
	</form>
</div>
