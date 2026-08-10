<?php
/**
 * Security Logs admin page.
 *
 * @package WPBuoy_Endpoint_Manager
 * @var array   $logs               Log rows for the current page.
 * @var int     $total              Total number of log entries in the database.
 * @var int     $filtered_count     Total logs matching active filters.
 * @var int     $per_page           Logs per page.
 * @var int     $paged              Current page number.
 * @var int     $total_pages        Total number of pages.
 * @var array   $unique_ips         Unique IP addresses from current log set.
 * @var array   $unique_endpoints   Unique endpoints from current log set.
 * @var array   $hidden_columns     Column keys the user has hidden via Screen Options.
 * @var bool    $has_active_filters Whether any filter is currently active.
 * @var bool    $cleared            Whether logs were just cleared.
 * @var array   $filters            Normalized active filter values (see get_logs_filters_from_request()).
 * @var string  $logs_page_url      Base URL for the logs admin page.
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

	<?php if ( $total > 0 ) : ?>
		<div class="rest-api-controls-container">
			<div class="rest-api-controls-row">
				<div class="control-group">
					<label for="logs-search"><?php esc_html_e( 'Search', 'wpbuoy-endpoint-manager' ); ?></label>
					<div class="rest-api-search-input-wrapper">
						<input type="text" id="logs-search" class="rest-api-search" placeholder="<?php esc_attr_e( 'Search IP, endpoint, user agent...', 'wpbuoy-endpoint-manager' ); ?>" value="<?php echo esc_attr( $filters['search'] ); ?>">
						<button type="button" id="logs-search-clear" class="rest-api-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'wpbuoy-endpoint-manager' ); ?>"></button>
					</div>
				</div>

				<div class="control-group" data-filter-key="ip">
					<label for="logs-ip-filter"><?php esc_html_e( 'IP Address', 'wpbuoy-endpoint-manager' ); ?></label>
					<select id="logs-ip-filter" class="rest-api-filter-select">
						<option value="all"><?php esc_html_e( 'All IPs', 'wpbuoy-endpoint-manager' ); ?></option>
						<?php foreach ( $unique_ips as $ip ) : ?>
							<option value="<?php echo esc_attr( $ip ); ?>" <?php selected( $filters['ip'], $ip ); ?>><?php echo esc_html( $ip ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="control-group" data-filter-key="endpoint">
					<label for="logs-endpoint-filter"><?php esc_html_e( 'Endpoint', 'wpbuoy-endpoint-manager' ); ?></label>
					<select id="logs-endpoint-filter" class="rest-api-filter-select">
						<option value="all"><?php esc_html_e( 'All Endpoints', 'wpbuoy-endpoint-manager' ); ?></option>
						<?php foreach ( $unique_endpoints as $endpoint ) : ?>
							<option value="<?php echo esc_attr( $endpoint ); ?>" <?php selected( $filters['endpoint'], $endpoint ); ?>><?php echo esc_html( $endpoint ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="control-group" data-filter-key="date" style="display:none">
					<label for="logs-date-from"><?php esc_html_e( 'From', 'wpbuoy-endpoint-manager' ); ?></label>
					<input type="date" id="logs-date-from" class="rest-api-filter-date" value="<?php echo esc_attr( $filters['date_from'] ); ?>">
				</div>

				<div class="control-group" data-filter-key="date" style="display:none">
					<label for="logs-date-to"><?php esc_html_e( 'To', 'wpbuoy-endpoint-manager' ); ?></label>
					<input type="date" id="logs-date-to" class="rest-api-filter-date" value="<?php echo esc_attr( $filters['date_to'] ); ?>">
				</div>

				<div class="control-group">
					<button type="button" id="logs-clear-filters" class="rest-api-clear-filters" data-clear-url="<?php echo esc_url( $logs_page_url ); ?>">
						<?php esc_html_e( 'Clear Filters', 'wpbuoy-endpoint-manager' ); ?>
					</button>
				</div>
			</div>

			<div class="search-results-info">
				<span class="search-results-count"></span>
			</div>
		</div>
	<?php endif; ?>

	<?php
	$wpbyem_col = function ( $key ) use ( $hidden_columns ) {
		return in_array( $key, $hidden_columns, true ) ? ' hidden' : '';
	};
	?>
	<table class="wp-list-table widefat fixed striped wpbuoy-em-logs-table">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-time<?php echo esc_attr( $wpbyem_col( 'time' ) ); ?>"><?php esc_html_e( 'Time', 'wpbuoy-endpoint-manager' ); ?></th>
				<th scope="col" class="manage-column column-ip_address<?php echo esc_attr( $wpbyem_col( 'ip_address' ) ); ?>"><?php esc_html_e( 'IP Address', 'wpbuoy-endpoint-manager' ); ?></th>
				<th scope="col" class="manage-column column-endpoint<?php echo esc_attr( $wpbyem_col( 'endpoint' ) ); ?>"><?php esc_html_e( 'Endpoint', 'wpbuoy-endpoint-manager' ); ?></th>
				<th scope="col" class="manage-column column-status<?php echo esc_attr( $wpbyem_col( 'status' ) ); ?>"><?php esc_html_e( 'Response Code', 'wpbuoy-endpoint-manager' ); ?></th>
				<th scope="col" class="manage-column column-user_agent<?php echo esc_attr( $wpbyem_col( 'user_agent' ) ); ?>"><?php esc_html_e( 'User Agent', 'wpbuoy-endpoint-manager' ); ?></th>
				<th scope="col" class="manage-column column-actions<?php echo esc_attr( $wpbyem_col( 'actions' ) ); ?>" style="width: 90px;"><?php esc_html_e( 'Action', 'wpbuoy-endpoint-manager' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			wpbyem_get_plugin_part( 'admin/logs-table-rows', compact(
				'logs',
				'hidden_columns',
				'logs_page_url',
				'has_active_filters'
			) );
			?>
		</tbody>
	</table>

	<?php if ( $total > 0 ) : ?>
		<div class="wpbuoy-em-logs-actions">

			<div class="wpbuoy-em-logs-actions-left">
				<?php
				wpbyem_get_plugin_part( 'admin/logs-summary-pagination', compact(
					'total',
					'paged',
					'total_pages',
					'logs_page_url',
					'filters'
				) );
				?>
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
