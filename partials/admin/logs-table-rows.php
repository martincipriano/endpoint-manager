<?php
/**
 * Security logs table rows — the <tbody> content only. Shared by the
 * initial page render (included from page-logs.php) and the AJAX search
 * handler (ajax_search_logs(), via wpbyem_return_plugin_part()), so both
 * paths render identical row markup with no duplicated logic to drift.
 *
 * data-filter-ip / data-filter-endpoint on the clickable cells let logs.js
 * read the value to filter by directly, without parsing it back out of the
 * href — the href itself stays a real, working link (right-click/open-in-
 * new-tab, or a plain page load if JS is off) pointing at the same filtered
 * view.
 *
 * @package WPBuoy_Endpoint_Manager
 *
 * @var array  $logs               Log rows for the current page.
 * @var array  $hidden_columns     Column keys the user has hidden via Screen Options.
 * @var string $logs_page_url      Base URL for the logs admin page.
 * @var bool   $has_active_filters Whether any filter is currently active — picks the empty-state message.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$wpbyem_col = function ( $key ) use ( $hidden_columns ) {
	return in_array( $key, $hidden_columns, true ) ? ' hidden' : '';
};
?>
<?php if ( empty( $logs ) ) : ?>
	<tr>
		<td colspan="6" style="color: #72777c; font-style: italic;">
			<?php if ( $has_active_filters ) : ?>
				<?php esc_html_e( 'No entries match your filters.', 'wpbuoy-endpoint-manager' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'No blocked requests logged yet.', 'wpbuoy-endpoint-manager' ); ?>
			<?php endif; ?>
		</td>
	</tr>
<?php endif; ?>
<?php foreach ( $logs as $log ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
	<tr data-timestamp="<?php echo esc_attr( $log->blocked_at ); ?>">
		<td class="column-time<?php echo esc_attr( $wpbyem_col( 'time' ) ); ?>"><?php echo esc_html( human_time_diff( strtotime( $log->blocked_at ), time() ) . ' ago' ); ?></td>
		<td class="column-ip_address<?php echo esc_attr( $wpbyem_col( 'ip_address' ) ); ?>">
			<a href="<?php echo esc_url( add_query_arg( 'filter_ip', $log->ip_address, $logs_page_url ) ); ?>" data-filter-ip="<?php echo esc_attr( $log->ip_address ); ?>">
				<?php echo esc_html( $log->ip_address ); ?>
			</a>
		</td>
		<td class="column-endpoint<?php echo esc_attr( $wpbyem_col( 'endpoint' ) ); ?>">
			<a href="<?php echo esc_url( add_query_arg( 'filter_endpoint', $log->endpoint, $logs_page_url ) ); ?>" data-filter-endpoint="<?php echo esc_attr( $log->endpoint ); ?>">
				<?php echo esc_html( $log->endpoint ); ?>
			</a>
		</td>
		<td class="column-status<?php echo esc_attr( $wpbyem_col( 'status' ) ); ?>">
			<span class="log-reason log-reason--error" data-tooltip="<?php esc_attr_e( 'Blocked', 'wpbuoy-endpoint-manager' ); ?>">
				403
			</span>
		</td>
		<td class="log-user-agent column-user_agent<?php echo esc_attr( $wpbyem_col( 'user_agent' ) ); ?>"><?php echo esc_html( $log->user_agent ); ?></td>
		<td class="log-actions column-actions<?php echo esc_attr( $wpbyem_col( 'actions' ) ); ?>">
			<button type="button" class="button button-small" disabled
				data-tooltip="<?php esc_attr_e( 'IP Blocking (Pro)', 'wpbuoy-endpoint-manager' ); ?>">
				<?php esc_html_e( 'Block', 'wpbuoy-endpoint-manager' ); ?>
			</button>
		</td>
	</tr>
<?php endforeach; ?>
