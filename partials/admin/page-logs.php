<?php
/**
 * Security Logs admin page.
 *
 * @package WPBuoy_Endpoint_Manager
 * @var array  $logs             Log entries (up to 500, most recent first).
 * @var int    $total            Total number of log entries in the database.
 * @var bool   $cleared          Whether logs were just cleared.
 * @var array  $unique_ips       Unique IP addresses from log set.
 * @var array  $unique_endpoints Unique endpoints from log set.
 * @var string $logs_page_url    Base URL for the logs admin page.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap">
	<h1 style="margin-bottom: 16px;"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( $cleared ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Logs cleared.', 'wpbuoy-endpoint-manager' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $logs ) ) : ?>
		<div class="rest-api-controls-container">
			<div class="rest-api-controls-row">
				<div class="control-group">
					<label for="logs-search"><?php esc_html_e( 'Search', 'wpbuoy-endpoint-manager' ); ?></label>
					<div class="rest-api-search-input-wrapper">
						<input type="text" id="logs-search" class="rest-api-search" placeholder="<?php esc_attr_e( 'Search IP, endpoint, user agent...', 'wpbuoy-endpoint-manager' ); ?>">
						<button type="button" id="logs-search-clear" class="rest-api-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'wpbuoy-endpoint-manager' ); ?>"></button>
					</div>
				</div>

				<div class="control-group">
					<label for="logs-ip-filter"><?php esc_html_e( 'IP Address', 'wpbuoy-endpoint-manager' ); ?></label>
					<select id="logs-ip-filter" class="rest-api-filter-select">
						<option value="all"><?php esc_html_e( 'All IPs', 'wpbuoy-endpoint-manager' ); ?></option>
						<?php foreach ( $unique_ips as $ip ) : ?>
							<option value="<?php echo esc_attr( $ip ); ?>"><?php echo esc_html( $ip ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="control-group">
					<label for="logs-endpoint-filter"><?php esc_html_e( 'Endpoint', 'wpbuoy-endpoint-manager' ); ?></label>
					<select id="logs-endpoint-filter" class="rest-api-filter-select">
						<option value="all"><?php esc_html_e( 'All Endpoints', 'wpbuoy-endpoint-manager' ); ?></option>
						<?php foreach ( $unique_endpoints as $endpoint ) : ?>
							<option value="<?php echo esc_attr( $endpoint ); ?>"><?php echo esc_html( $endpoint ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="control-group">
					<label for="logs-date-from"><?php esc_html_e( 'From', 'wpbuoy-endpoint-manager' ); ?></label>
					<input type="date" id="logs-date-from" class="rest-api-filter-date">
				</div>

				<div class="control-group">
					<label for="logs-date-to"><?php esc_html_e( 'To', 'wpbuoy-endpoint-manager' ); ?></label>
					<input type="date" id="logs-date-to" class="rest-api-filter-date">
				</div>

				<div class="control-group">
					<label>&nbsp;</label>
					<button type="button" id="logs-clear-filters" class="rest-api-clear-filters">
						<?php esc_html_e( 'Clear Filters', 'wpbuoy-endpoint-manager' ); ?>
					</button>
				</div>
			</div>

			<div class="search-results-info">
				<span class="search-results-count"></span>
			</div>
		</div>
	<?php endif; ?>

	<table class="wp-list-table widefat fixed striped wpbuoy-em-logs-table">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-time"><?php esc_html_e( 'Time', 'wpbuoy-endpoint-manager' ); ?></th>
				<th scope="col" class="manage-column column-ip_address"><?php esc_html_e( 'IP Address', 'wpbuoy-endpoint-manager' ); ?></th>
				<th scope="col" class="manage-column column-endpoint"><?php esc_html_e( 'Endpoint', 'wpbuoy-endpoint-manager' ); ?></th>
				<th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'wpbuoy-endpoint-manager' ); ?></th>
				<th scope="col" class="manage-column column-user_agent"><?php esc_html_e( 'User Agent', 'wpbuoy-endpoint-manager' ); ?></th>
				<th scope="col" class="manage-column column-actions" style="width: 90px;"><?php esc_html_e( 'Action', 'wpbuoy-endpoint-manager' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $logs ) ) : ?>
				<tr>
					<td colspan="6" style="color: #72777c; font-style: italic;">
						<?php esc_html_e( 'No blocked requests logged yet.', 'wpbuoy-endpoint-manager' ); ?>
					</td>
				</tr>
			<?php endif; ?>
			<?php foreach ( $logs as $log ) : ?>
				<tr data-timestamp="<?php echo esc_attr( $log['blocked_at'] ); ?>">
					<td class="column-time"><?php echo esc_html( human_time_diff( strtotime( $log['blocked_at'] ), time() ) . ' ago' ); ?></td>
					<td class="column-ip_address"><?php echo esc_html( $log['ip_address'] ); ?></td>
					<td class="column-endpoint"><?php echo esc_html( $log['endpoint'] ); ?></td>
					<td class="column-status">
						<span class="log-reason log-reason--403" data-tooltip="<?php esc_attr_e( 'Blocked', 'wpbuoy-endpoint-manager' ); ?>">
							403
						</span>
					</td>
					<td class="log-user-agent column-user_agent"><?php echo esc_html( $log['user_agent'] ); ?></td>
					<td class="log-actions column-actions">
						<button type="button" class="button button-small" disabled
							data-tooltip="<?php esc_attr_e( 'IP Blocking (Pro)', 'wpbuoy-endpoint-manager' ); ?>">
							<?php esc_html_e( 'Block', 'wpbuoy-endpoint-manager' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( ! empty( $logs ) ) : ?>
		<div class="wpbuoy-em-logs-actions">

			<div class="wpbuoy-em-logs-actions-left">
				<span class="wpbuoy-em-logs-total">
					<?php echo esc_html( sprintf(
						/* translators: %d: total number of blocked requests */
						__( 'Total blocked requests: %d', 'wpbuoy-endpoint-manager' ),
						$total
					) ); ?>
				</span>
			</div>

			<div class="wpbuoy-em-logs-actions-right">
				<button class="button" disabled data-tooltip="<?php esc_attr_e( 'CSV Log Export (Pro)', 'wpbuoy-endpoint-manager' ); ?>">
					<?php esc_html_e( 'Export CSV', 'wpbuoy-endpoint-manager' ); ?>
				</button>

				<form
					method="post"
					class="wpbuoy-em-logs-clear-form"
					onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs? This cannot be undone.', 'wpbuoy-endpoint-manager' ) ); ?>')"
				>
					<?php wp_nonce_field( 'wpbyem_clear_logs', 'wpbyem_clear_logs_nonce' ); ?>
					<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Clear All Logs', 'wpbuoy-endpoint-manager' ); ?>">
				</form>
			</div>

		</div>
	<?php endif; ?>
</div>
