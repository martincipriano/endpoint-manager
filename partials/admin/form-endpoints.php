<?php
/**
 * Endpoints field template.
 *
 * @package WPBuoy_Endpoint_Manager
 *
 * @var array $routes_data Keyed by namespace; each entry has 'disabled_count' and 'routes'.
 *                         Each route entry: field_id, route_encoded, is_blocked.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<?php foreach ( $routes_data as $em_namespace => $em_namespace_data ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
<div class="rest-api-namespace">
	<div class="namespace-header" data-namespace="<?php echo esc_attr( $em_namespace ); ?>">
		<div class="namespace-title">
			<h3><?php echo esc_html( $em_namespace ); ?></h3>
			<?php if ( $em_namespace_data['disabled_count'] > 0 ) : ?>
				<span class="disabled-count">
					<?php
					/* translators: %d: number of disabled endpoints */
					echo esc_html( sprintf( _n( '%d disabled', '%d disabled', $em_namespace_data['disabled_count'], 'wpbuoy-endpoint-manager' ), $em_namespace_data['disabled_count'] ) );
					?>
				</span>
			<?php endif; ?>
		</div>
		<button type="button" class="namespace-toggle" aria-expanded="false">
			<span class="toggle-icon"></span>
			<span class="sr-only"><?php esc_html_e( 'Toggle namespace', 'wpbuoy-endpoint-manager' ); ?></span>
		</button>
	</div>
	<div class="rest-api-routes" style="display: none;">
		<?php foreach ( $em_namespace_data['routes'] as $em_route => $em_route_data ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
		<div class="rest-api-route">
			<label for="<?php echo esc_attr( $em_route_data['field_id'] ); ?>">
				<input type="checkbox"
					   id="<?php echo esc_attr( $em_route_data['field_id'] ); ?>"
					   name="wpbyem_blocked_endpoints_encoded[]"
					   value="<?php echo esc_attr( $em_route_data['route_encoded'] ); ?>"
					   <?php checked( $em_route_data['is_blocked'] ); ?> />
				<span class="toggle-switch"></span>
				<div class="route-info">
					<span class="route-path"><?php echo esc_html( $em_route ); ?></span>
				</div>
			</label>
			<span class="route-status <?php echo esc_attr( $em_route_data['is_blocked'] ? 'disabled' : 'enabled' ); ?>">
				<?php echo $em_route_data['is_blocked'] ? esc_html__( 'Disabled', 'wpbuoy-endpoint-manager' ) : esc_html__( 'Enabled', 'wpbuoy-endpoint-manager' ); ?>
			</span>
			<a href="<?php echo esc_url( $em_route_data['preview_url'] ); ?>"
			   target="_blank"
			   rel="noopener noreferrer"
			   class="route-preview">
				<?php esc_html_e( 'Preview', 'wpbuoy-endpoint-manager' ); ?>
			</a>
		</div>
		<?php endforeach; ?>
	</div>
</div>
<?php endforeach; ?>
