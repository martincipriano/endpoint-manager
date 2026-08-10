<?php
/**
 * Security logs summary + pagination — the content of
 * .wpbuoy-em-logs-actions-left. Shared by the initial page render and the
 * AJAX search handler, for the same reason as logs-table-rows.php.
 *
 * Prev/Next hrefs carry every active filter forward (not just `paged`) so
 * they're real, working links even without JS — right-click/open-in-new-tab
 * or a plain page load both land on the correct filtered page. `data-page`
 * duplicates the target page number in a form logs.js can read directly,
 * without parsing it back out of the href.
 *
 * @package WPBuoy_Endpoint_Manager
 *
 * @var int    $total         Total log row count, unfiltered.
 * @var int    $paged         Current page number.
 * @var int    $total_pages   Total number of pages matching the active filters.
 * @var string $logs_page_url Base URL for the logs admin page.
 * @var array  $filters       Normalized active filter values, preserved on Prev/Next links.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$wpbyem_pagination_url = function ( $page ) use ( $logs_page_url, $filters ) {
	$query_map = array(
		'search'    => 'filter_search',
		'ip'        => 'filter_ip',
		'endpoint'  => 'filter_endpoint',
		'date_from' => 'filter_date_from',
		'date_to'   => 'filter_date_to',
	);

	$args = array( 'paged' => $page );
	foreach ( $query_map as $filter_key => $query_key ) {
		if ( ! empty( $filters[ $filter_key ] ) ) {
			$args[ $query_key ] = $filters[ $filter_key ];
		}
	}

	return add_query_arg( $args, $logs_page_url );
};
?>
<span class="wpbuoy-em-logs-total">
	<?php echo esc_html( sprintf(
		/* translators: %d: total number of blocked requests */
		__( 'Total blocked requests: %d', 'wpbuoy-endpoint-manager' ),
		$total
	) ); ?>
</span>

<?php if ( $total_pages > 1 ) : ?>
<div class="wpbuoy-em-pagination">
	<?php if ( $paged > 1 ) : ?>
		<a href="<?php echo esc_url( $wpbyem_pagination_url( $paged - 1 ) ); ?>" class="button button-secondary wpbuoy-em-page-prev" data-page="<?php echo esc_attr( $paged - 1 ); ?>" aria-label="<?php esc_attr_e( 'Previous page', 'wpbuoy-endpoint-manager' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor" aria-hidden="true" focusable="false"><path d="M407.65-72.35 0-480l407.65-407.65L486.3-809l-329 329 329 329-78.65 78.65Z"/></svg></a>
	<?php else : ?>
		<button type="button" class="button button-secondary wpbuoy-em-page-prev" disabled aria-label="<?php esc_attr_e( 'Previous page', 'wpbuoy-endpoint-manager' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor" aria-hidden="true" focusable="false"><path d="M407.65-72.35 0-480l407.65-407.65L486.3-809l-329 329 329 329-78.65 78.65Z"/></svg></button>
	<?php endif; ?>

	<span class="wpbuoy-em-page-info">
		<?php echo esc_html( sprintf(
			/* translators: 1: current page, 2: total pages */
			__( '%1$d of %2$d', 'wpbuoy-endpoint-manager' ),
			$paged,
			$total_pages
		) ); ?>
	</span>

	<?php if ( $paged < $total_pages ) : ?>
		<a href="<?php echo esc_url( $wpbyem_pagination_url( $paged + 1 ) ); ?>" class="button button-secondary wpbuoy-em-page-next" data-page="<?php echo esc_attr( $paged + 1 ); ?>" aria-label="<?php esc_attr_e( 'Next page', 'wpbuoy-endpoint-manager' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor" aria-hidden="true" focusable="false"><path d="M321-72.35 242.35-151l329-329-329-329L321-887.65 728.65-480 321-72.35Z"/></svg></a>
	<?php else : ?>
		<button type="button" class="button button-secondary wpbuoy-em-page-next" disabled aria-label="<?php esc_attr_e( 'Next page', 'wpbuoy-endpoint-manager' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor" aria-hidden="true" focusable="false"><path d="M321-72.35 242.35-151l329-329-329-329L321-887.65 728.65-480 321-72.35Z"/></svg></button>
	<?php endif; ?>
</div>
<?php endif; ?>
