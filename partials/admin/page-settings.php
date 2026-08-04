<?php
/**
 * Settings page template.
 *
 * @package WPBuoy_Endpoint_Manager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$settings          = get_option( 'wpbyem_rate_limit_settings', array() );
$exclude_roles     = isset( $settings['exclude_roles_endpoints'] ) ? (array) $settings['exclude_roles_endpoints'] : array( 'administrator' );
$exclude_endpoints = in_array( 'administrator', $exclude_roles, true );
$log_retention     = isset( $settings['log_retention_days'] ) ? (int) $settings['log_retention_days'] : 30;
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<nav class="wpbyem-settings-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'wpbuoy-endpoint-manager' ); ?>">
		<button type="button" class="wpbyem-tab-button active" data-tab="general"
		        role="tab" aria-selected="true" aria-controls="wpbyem-tab-general">
			<?php esc_html_e( 'General', 'wpbuoy-endpoint-manager' ); ?>
		</button>
		<button type="button" class="wpbyem-tab-button" data-tab="export-import"
		        role="tab" aria-selected="false" aria-controls="wpbyem-tab-export-import">
			<?php esc_html_e( 'Export/Import', 'wpbuoy-endpoint-manager' ); ?>
		</button>
	</nav>

	<div id="wpbyem-tab-general" class="wpbyem-tab-panel" role="tabpanel">
		<form action="options.php" method="post">
			<?php settings_fields( 'wpbyem-settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Admin Bypass', 'wpbuoy-endpoint-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbyem_rate_limit_settings[exclude_admins_endpoints]" value="1" <?php checked( $exclude_endpoints ); ?>>
							<?php esc_html_e( 'Exclude admins from blocked endpoints', 'wpbuoy-endpoint-manager' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Admins bypass all endpoint blocking — all endpoints remain accessible even if toggled off. Useful for previewing blocked endpoints.', 'wpbuoy-endpoint-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Log Retention', 'wpbuoy-endpoint-manager' ); ?></th>
					<td>
						<select name="wpbyem_rate_limit_settings[log_retention_days]">
							<?php foreach ( array( 7, 14, 30, 60, 90 ) as $days ) : ?>
								<option value="<?php echo esc_attr( $days ); ?>" <?php selected( $log_retention, $days ); ?>>
									<?php
									echo esc_html(
										/* translators: %d: number of days */
										sprintf( _n( '%d day', '%d days', $days, 'wpbuoy-endpoint-manager' ), $days )
									);
									?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Security logs older than this are automatically deleted daily via WP-Cron.', 'wpbuoy-endpoint-manager' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>

	<div id="wpbyem-tab-export-import" class="wpbyem-tab-panel" role="tabpanel" hidden>
		<h2><?php esc_html_e( 'Export Configuration', 'wpbuoy-endpoint-manager' ); ?></h2>
		<p><?php esc_html_e( 'What to export', 'wpbuoy-endpoint-manager' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
			<input type="hidden" name="page" value="wpbyem-settings">
			<input type="hidden" name="action" value="export_config">
			<?php wp_nonce_field( 'wpbyem_export_config', '_wpnonce', false ); ?>
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'What to export', 'wpbuoy-endpoint-manager' ); ?></legend>
				<p>
					<label>
						<input type="checkbox" name="sections[]" value="endpoints" checked>
						<?php esc_html_e( 'Blocked Endpoints (your blocked endpoints)', 'wpbuoy-endpoint-manager' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="sections[]" value="settings" checked>
						<?php esc_html_e( 'Settings (Admin Bypass, Log Retention)', 'wpbuoy-endpoint-manager' ); ?>
					</label>
				</p>
			</fieldset>
			<p class="wpbyem-eim-submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Export Configuration (JSON)', 'wpbuoy-endpoint-manager' ); ?></button></p>
		</form>

		<hr>

		<h2><?php esc_html_e( 'Import Configuration', 'wpbuoy-endpoint-manager' ); ?></h2>
		<p><?php esc_html_e( 'Upload a previously exported JSON file. This replaces the Blocked Endpoints and Settings on this site with the contents of the file — intended for moving configuration from one WordPress install to another.', 'wpbuoy-endpoint-manager' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin.php?page=wpbyem-settings' ) ); ?>" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'wpbyem_import_config', 'wpbyem_import_nonce' ); ?>
			<input type="hidden" name="wpbyem_import_submit" value="1">
			<input type="file" id="wpbyem-import-file" class="wpbyem-import-file" name="wpbyem_import_file" accept="application/json" required>
			<p class="wpbyem-eim-submit">
				<button type="button" class="button button-primary" id="wpbyem-import-trigger">
					<?php esc_html_e( 'Import Configuration', 'wpbuoy-endpoint-manager' ); ?>
				</button>
			</p>
		</form>
	</div>

	<div class="wpby-upgrade-banner">
		<div class="wpby-upgrade-banner__wrap">
			<div class="wpby-upgrade-banner__grid">
				<div class="wpby-upgrade-value-prop">
					<h2 class="wpby-upgrade-banner__heading"><?php esc_html_e( 'Fine-tune how your REST API handles threats', 'wpbuoy-endpoint-manager' ); ?></h2>
					<p class="wpby-upgrade-banner__desc"><?php esc_html_e( 'Set rate limits, configure auto-blocking thresholds, manage your IP allowlist, and control exactly what blocked endpoints return.', 'wpbuoy-endpoint-manager' ); ?></p>
					<div class="wpby-upgrade-banner__ctas">
						<a class="button" href="https://wpbuoy.com/endpoint-manager/knowledge-base/" rel="noopener noreferrer" target="_blank"><?php esc_html_e( 'Learn More', 'wpbuoy-endpoint-manager' ); ?></a>
						<a class="button button-primary" href="https://wpbuoy.com/product/endpoint-manager/#pricing" rel="noopener noreferrer" target="_blank"><?php esc_html_e( 'Upgrade to Pro', 'wpbuoy-endpoint-manager' ); ?></a>
					</div>
				</div>
				<div class="wpby-upgrade-features">
					<ul class="wpby-upgrade-banner__features">
						<li><?php esc_html_e( 'Block any REST API endpoint with a configurable response code and message', 'wpbuoy-endpoint-manager' ); ?></li>
						<li><?php esc_html_e( 'Rate limiting — global and per-endpoint request thresholds', 'wpbuoy-endpoint-manager' ); ?></li>
						<li><?php esc_html_e( 'IP Block List — manual blocks, auto-block, and allowlist', 'wpbuoy-endpoint-manager' ); ?></li>
						<li><?php esc_html_e( 'Endpoint preview — inspect live API responses in an inline modal', 'wpbuoy-endpoint-manager' ); ?></li>
						<li><?php esc_html_e( 'Intuitive admin UI — namespace accordion, live search, and multi-criteria filters', 'wpbuoy-endpoint-manager' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<div class="wpby-upgrade-banner__footer">
			<div class="wpby-upgrade-banner__wrap">
				<a href="https://wpbuoy.com" rel="noopener noreferrer" target="_blank" aria-label="<?php esc_attr_e( 'WPBuoy', 'wpbuoy-endpoint-manager' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="87" height="24" aria-hidden="true" focusable="false"><path fill="#fff" fill-rule="evenodd" d="m6.083 13.477 1.943-6.613h3.795l1.875 6.692 1.94-6.692h3.914l-3.756 12.517h-4.349l-1.634-5.793-1.706 5.793h-4.35L0 6.864h4.23zm71.695 5.953L72.607 6.863h4.351l2.954 7.582 2.987-7.582h4.026l-5.21 12.924q-.628 1.594-1.438 2.496-.81.9-1.8 1.264a6.3 6.3 0 0 1-2.179.365 6.5 6.5 0 0 1-1.934-.302q-.973-.303-1.583-.82l1.47-3.028q.393.333.892.53.5.198.986.197.696 0 1.094-.321.312-.252.555-.738m-53.696-1.244v5.525h-4.23V6.863h4.027v1.236q.373-.45.856-.754 1.084-.683 2.699-.683 1.65 0 2.994.786t2.138 2.22q.795 1.437.795 3.407 0 1.972-.795 3.406-.794 1.436-2.138 2.214-1.343.777-2.994.778-1.54 0-2.631-.691a3.7 3.7 0 0 1-.72-.596m2.46-2.173q.715 0 1.285-.342t.91-1.004.34-1.592q0-.951-.34-1.61a2.43 2.43 0 0 0-.91-1.002 2.45 2.45 0 0 0-1.285-.342 2.45 2.45 0 0 0-1.285.342q-.57.342-.91 1.002t-.34 1.61q0 .93.34 1.592t.91 1.004q.57.342 1.285.342m8.034.783L31.19.866l4.14-.88 1.9 8.943q.247-.405.581-.732.924-.902 2.43-1.222 1.615-.344 3.09.138 1.477.481 2.552 1.72Q46.96 10.073 47.37 12t-.07 3.497q-.113.375-.266.718a62 62 0 0 0-4.834-.273q.159-.106.3-.237.485-.453.68-1.18.192-.726-.001-1.634-.198-.931-.666-1.498a2.44 2.44 0 0 0-1.096-.782 2.45 2.45 0 0 0-1.328-.067q-.7.149-1.186.601a2.44 2.44 0 0 0-.684 1.161q-.195.708.001 1.639.193.908.665 1.493.274.34.6.555-2.916.162-4.908.803m13.611-.332q-.414-1.114-.414-2.67V6.863h4.231v6.223q0 1.44.534 2.067.534.625 1.483.625.632 0 1.142-.3t.815-.954.303-1.674V6.863h4.232v11.381a76 76 0 0 1-5.66-.786q-3.582-.625-6.666-.994m15.52 2.019a6.2 6.2 0 0 1-2.126-2.108q-.882-1.447-.882-3.317 0-1.877.883-3.32a6.2 6.2 0 0 1 2.416-2.26q1.532-.816 3.52-.816 1.989 0 3.532.816a6.1 6.1 0 0 1 2.416 2.258q.87 1.444.871 3.322 0 1.564-.607 2.83-3.066 2.606-10.023 2.595m3.811-2.47q.723 0 1.293-.342t.906-1.012.336-1.6q0-.95-.336-1.603a2.4 2.4 0 0 0-.906-.993 2.46 2.46 0 0 0-1.29-.342q-.718 0-1.288.342-.57.343-.91.993-.34.652-.34 1.602 0 .93.34 1.6.34.672.91 1.013.569.342 1.285.342"/></svg></a>
			</div>
		</div>
	</div>
</div>
