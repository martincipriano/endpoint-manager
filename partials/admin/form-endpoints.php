<?php
/**
 * Endpoints field template.
 *
 * @package WPBuoy_Endpoint_Manager
 *
 * @var array $routes_data Keyed by namespace; each entry has 'disabled_count' and 'routes'.
 *                         Each route entry: field_id, route_encoded, is_blocked, preview_url, methods.
 * @var array $namespaces  List of namespace strings for the namespace filter dropdown.
 * @var array $all_methods List of unique HTTP method strings for the method filter dropdown.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="rest-api-controls-container">
	<div class="rest-api-controls-row no-clear-button">
		<div class="control-group">
			<label for="rest-api-search"><?php esc_html_e( 'Search', 'wpbuoy-endpoint-manager' ); ?></label>
			<div class="rest-api-search-input-wrapper">
				<input type="text" id="rest-api-search" class="rest-api-search" placeholder="<?php esc_attr_e( 'Search endpoints...', 'wpbuoy-endpoint-manager' ); ?>" />
				<button type="button" id="rest-api-search-clear" class="rest-api-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'wpbuoy-endpoint-manager' ); ?>"></button>
			</div>
		</div>

		<div class="control-group">
			<label for="status-filter"><?php esc_html_e( 'Status', 'wpbuoy-endpoint-manager' ); ?></label>
			<select id="status-filter" class="rest-api-filter-select">
				<option value="all"><?php esc_html_e( 'Show All', 'wpbuoy-endpoint-manager' ); ?></option>
				<option value="enabled"><?php esc_html_e( 'Enabled Only', 'wpbuoy-endpoint-manager' ); ?></option>
				<option value="disabled"><?php esc_html_e( 'Disabled Only', 'wpbuoy-endpoint-manager' ); ?></option>
			</select>
		</div>

		<div class="control-group">
			<label for="method-filter"><?php esc_html_e( 'Method', 'wpbuoy-endpoint-manager' ); ?></label>
			<select id="method-filter" class="rest-api-filter-select">
				<option value="all"><?php esc_html_e( 'All Methods', 'wpbuoy-endpoint-manager' ); ?></option>
				<?php foreach ( $all_methods as $em_method ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
					<option value="<?php echo esc_attr( $em_method ); ?>"><?php echo esc_html( $em_method ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="control-group">
			<label for="namespace-filter"><?php esc_html_e( 'Namespace', 'wpbuoy-endpoint-manager' ); ?></label>
			<select id="namespace-filter" class="rest-api-filter-select">
				<option value="all"><?php esc_html_e( 'All Namespaces', 'wpbuoy-endpoint-manager' ); ?></option>
				<?php foreach ( $namespaces as $em_namespace_option ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
					<option value="<?php echo esc_attr( $em_namespace_option ); ?>"><?php echo esc_html( $em_namespace_option ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="control-group">
			<label>&nbsp;</label>
			<button type="button" id="clear-filters" class="rest-api-clear-filters">
				<?php esc_html_e( 'Clear Filters', 'wpbuoy-endpoint-manager' ); ?>
			</button>
		</div>
	</div>

	<div class="search-results-info">
		<span class="search-results-count"></span>
	</div>
</div>

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
			<span class="screen-reader-text"><?php esc_html_e( 'Toggle namespace', 'wpbuoy-endpoint-manager' ); ?></span>
		</button>
	</div>
	<div class="rest-api-routes" style="display: none;">
		<?php foreach ( $em_namespace_data['routes'] as $em_route => $em_route_data ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
		<div class="rest-api-route" data-methods="<?php echo esc_attr( implode( ',', $em_route_data['methods'] ) ); ?>">
			<div class="route-row">
				<label for="<?php echo esc_attr( $em_route_data['field_id'] ); ?>">
					<input type="checkbox"
							id="<?php echo esc_attr( $em_route_data['field_id'] ); ?>"
							name="wpbyem_blocked_endpoints_encoded[]"
							value="<?php echo esc_attr( $em_route_data['route_encoded'] ); ?>"
							<?php checked( $em_route_data['is_blocked'] ); ?> />
					<span class="toggle-switch"></span>
					<div class="route-info">
						<span class="route-path"><?php echo esc_html( $em_route ); ?></span>
						<?php if ( $em_route_data['is_restricted'] ) : ?>
							<?php
							$em_restricted_source = $em_route_data['restricted_source'] ?? null; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
							$em_restricted_tooltip = $em_restricted_source // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
								/* translators: %s: plugin or theme name */
								? sprintf( __( 'Restricted by %s — public requests may be denied (403).', 'wpbuoy-endpoint-manager' ), $em_restricted_source )
								: __( 'Restricted by its plugin or theme — public requests may be denied (403).', 'wpbuoy-endpoint-manager' );
							?>
							<span class="route-restricted"
							      data-tooltip="<?php echo esc_attr( $em_restricted_tooltip ); ?>"
							      aria-label="<?php esc_attr_e( 'Restricted endpoint', 'wpbuoy-endpoint-manager' ); ?>">
								<?php esc_html_e( 'Restricted', 'wpbuoy-endpoint-manager' ); ?>
							</span>
						<?php endif; ?>
						<span class="route-methods">
							<?php foreach ( $em_route_data['methods'] as $em_method ) : ?>
								<?php if ( 'GET' === $em_method ) : continue; endif; ?>
								<span class="method-badge method-<?php echo esc_attr( strtolower( $em_method ) ); ?>"><?php echo esc_html( $em_method ); ?></span>
							<?php endforeach; ?>
						</span>
					</div>
				</label>
				<button type="button" class="route-rate-limit-toggle" disabled data-tooltip="<?php esc_attr_e( 'Rate Limit (Pro)', 'wpbuoy-endpoint-manager' ); ?>" aria-label="<?php esc_attr_e( 'Rate Limit (Pro)', 'wpbuoy-endpoint-manager' ); ?>">
					<svg width="24px" height="19px" viewBox="0 0 24 19" xmlns="http://www.w3.org/2000/svg"><path d="M11.9692485,14.3792 C12.6759596,14.3564 13.1945723,14.1006389 13.5250866,13.6119167 L20.3796792,3.65401667 L10.42541,10.5035167 C9.93005958,10.8349611 9.66249037,11.3444778 9.62270235,12.0320667 C9.58312484,12.7198667 9.78753845,13.2887056 10.2359432,13.7385833 C10.6845584,14.18825 11.2623269,14.4017889 11.9692485,14.3792 Z M11.9995632,0 C13.2012037,0 14.3270574,0.157911111 15.3771245,0.473733333 C16.427402,0.789555556 17.4415756,1.26740556 18.4196453,1.90728333 L16.6389733,3.06185 C15.9200521,2.67868333 15.174816,2.38925 14.4032652,2.19355 C13.6315038,1.99785 12.8302698,1.9 11.9995632,1.9 C9.19966511,1.9 6.81554138,2.88694444 4.84719197,4.86083333 C2.87884257,6.83472222 1.89466787,9.22555556 1.89466787,12.0333333 C1.89466787,12.92 2.0157161,13.7961111 2.25781255,14.6616667 C2.499909,15.5272222 2.84200181,16.34 3.28409098,17.1 L20.7150354,17.1 C21.1992283,16.2977778 21.5518471,15.4638889 21.7728916,14.5983333 C21.9939362,13.7327778 22.1044585,12.8355556 22.1044585,11.9066667 C22.1044585,11.1466667 22.0109882,10.3773778 21.8240477,9.5988 C21.6368966,8.82001111 21.3481703,8.0807 20.9578687,7.38086667 L22.1091952,5.59518333 C22.7327514,6.61991667 23.2004186,7.64507222 23.5121967,8.67065 C23.8237643,9.69622778 23.9860742,10.7619167 23.9991264,11.8677167 C24.011968,13.0142611 23.8832359,14.0977889 23.6129299,15.1183 C23.3424135,16.1390222 22.9351651,17.1373667 22.3911849,18.1133333 C22.2242436,18.3877778 21.9894101,18.6041667 21.6866842,18.7625 C21.3837479,18.9208333 21.059865,19 20.7150354,19 L3.28409098,19 C2.94726114,19 2.62948323,18.9188278 2.33075726,18.7564833 C2.03203129,18.5939278 1.7910927,18.3632889 1.60794147,18.0645667 C1.12543272,17.2201222 0.736815284,16.2993611 0.44208917,15.3022833 C0.147363057,14.3052056 0,13.2155556 0,12.0333333 C0,10.3786444 0.313357014,8.82254444 0.940071043,7.36503333 C1.56678507,5.90752222 2.42138554,4.63188333 3.50387245,3.53811667 C4.58656988,2.44435 5.85978669,1.58122222 7.32352288,0.948733333 C8.78746959,0.316244444 10.3461497,0 11.9995632,0 Z" fill="currentColor" fill-rule="nonzero"/></svg>
				</button>
				<a href="<?php echo esc_url( $em_route_data['preview_url'] ); ?>"
					target="_blank" rel="noopener noreferrer"
					class="route-preview"
					data-tooltip="<?php esc_attr_e( 'Preview', 'wpbuoy-endpoint-manager' ); ?>"
					aria-label="<?php esc_attr_e( 'Preview', 'wpbuoy-endpoint-manager' ); ?>">
					<svg width="26px" height="18px" viewBox="0 0 26 18" xmlns="http://www.w3.org/2000/svg"><path d="M16.5906419,12.7121786 C17.5744077,11.6923929 18.0662907,10.4540357 18.0662907,8.99710714 C18.0662907,7.54017857 17.5734758,6.30278571 16.5878459,5.28492857 C15.6022161,4.26707143 14.4053354,3.75814286 12.997204,3.75814286 C11.5890726,3.75814286 10.393124,4.26803571 9.40935812,5.28782143 C8.42559225,6.30760714 7.93370932,7.54596429 7.93370932,9.00289286 C7.93370932,10.4598214 8.42652424,11.6972143 9.41215409,12.7150714 C10.3977839,13.7329286 11.5946646,14.2418571 13.002796,14.2418571 C14.4109274,14.2418571 15.606876,13.7319643 16.5906419,12.7121786 Z M10.6234288,11.4589286 C9.97103666,10.7839286 9.64484061,9.96428571 9.64484061,9 C9.64484061,8.03571429 9.97103666,7.21607143 10.6234288,6.54107143 C11.2758209,5.86607143 12.0680113,5.52857143 13,5.52857143 C13.9319887,5.52857143 14.7241791,5.86607143 15.3765712,6.54107143 C16.0289633,7.21607143 16.3551594,8.03571429 16.3551594,9 C16.3551594,9.96428571 16.0289633,10.7839286 15.3765712,11.4589286 C14.7241791,12.1339286 13.9319887,12.4714286 13,12.4714286 C12.0680113,12.4714286 11.2758209,12.1339286 10.6234288,11.4589286 Z M5.19055585,15.5532857 C2.84070162,13.9223571 1.11051634,11.7379286 0,9 C1.11051634,6.26207143 2.84008029,4.07764286 5.18869187,2.44671429 C7.53751055,0.815571429 10.1407622,0 12.9984467,0 C15.8559241,0 18.4595899,0.815571429 20.8094442,2.44671429 C23.1592984,4.07764286 24.8894837,6.26207143 26,9 C24.8894837,11.7379286 23.1599197,13.9223571 20.8113081,15.5532857 C18.4624894,17.1844286 15.8592378,18 13.0015533,18 C10.1440759,18 7.54041008,17.1844286 5.19055585,15.5532857 Z M19.4462553,14.1589286 C21.4034316,12.8839286 22.8997913,11.1642857 23.9353343,9 C22.8997913,6.83571429 21.4034316,5.11607143 19.4462553,3.84107143 C17.489079,2.56607143 15.3403272,1.92857143 13,1.92857143 C10.6596728,1.92857143 8.510921,2.56607143 6.55374468,3.84107143 C4.59656837,5.11607143 3.1002087,6.83571429 2.06466568,9 C3.1002087,11.1642857 4.59656837,12.8839286 6.55374468,14.1589286 C8.510921,15.4339286 10.6596728,16.0714286 13,16.0714286 C15.3403272,16.0714286 17.489079,15.4339286 19.4462553,14.1589286 Z" fill="currentColor" fill-rule="nonzero"/></svg>
				</a>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
</div>
<?php endforeach; ?>
