<?php
/**
 * Plugin Name:       REST API Manager
 * Plugin URI:        https://wordpress.org/plugins/rest-api-manager/
 * Description:       Control which REST API endpoints are accessible on your WordPress site. Enable or disable specific endpoints to enhance security and performance.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Jose Martin Cipriano
 * Author URI:        https://www.linkedin.com/in/jmcipriano
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rest-api-manager
 * Domain Path:       /languages
 *
 * @package RestApiManager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'REST_API_MANAGER_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'REST_API_MANAGER_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'REST_API_MANAGER_URL', plugin_dir_url( __FILE__ ) );

/**
 * The main plugin class.
 */
class REST_API_Manager {

	/**
	 * The single instance of the class.
	 *
	 * @var REST_API_Manager
	 */
	protected static $instance = null;

	/**
	 * Main REST_API_Manager Instance.
	 *
	 * Ensures only one instance of REST_API_Manager is loaded or can be loaded.
	 *
	 * @return REST_API_Manager Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->migrate_old_settings();
		$this->init_hooks();
	}

	/**
	 * Load plugin dependencies.
	 */
	private function load_dependencies() {
		// Load feature manager
		require_once REST_API_MANAGER_PATH . 'includes/class-feature-manager.php';
	}

	/**
	 * Migrate old settings to new option name.
	 */
	private function migrate_old_settings() {
		$old_settings = get_option( 'rest_api_manager_settings', null );
		if ( ! is_null( $old_settings ) && is_array( $old_settings ) ) {
			// Migrate old settings to new option name
			update_option( 'rest_api_manager_blocked_endpoints', $old_settings );
			delete_option( 'rest_api_manager_settings' );
		}
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_encoded_form_submission' ), 5 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'rest_pre_dispatch', array( $this, 'maybe_block_rest_endpoint' ), 10, 3 );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'rest-api-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Enqueue admin styles and scripts.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( 'toplevel_page_rest-api-manager' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'rest-api-manager-admin',
			plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
			array(),
			REST_API_MANAGER_VERSION
		);

		wp_enqueue_script(
			'rest-api-manager-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
			array(),
			REST_API_MANAGER_VERSION,
			true
		);
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'REST API Manager', 'rest-api-manager' ),
			__( 'API Manager', 'rest-api-manager' ),
			'manage_options',
			'rest-api-manager',
			array( $this, 'render_admin_page' ),
			'dashicons-superhero',
			81
		);
	}

	/**
	 * Handle encoded form submission.
	 */
	public function handle_encoded_form_submission() {
		// Handle encoded form submission
		if ( isset( $_POST['rest_api_manager_blocked_endpoints_encoded'] ) && is_array( $_POST['rest_api_manager_blocked_endpoints_encoded'] ) ) {
			$decoded_endpoints = array();
			foreach ( $_POST['rest_api_manager_blocked_endpoints_encoded'] as $encoded ) {
				$decoded = base64_decode( $encoded );
				if ( $decoded !== false ) {
					$decoded_endpoints[] = $decoded;
				}
			}
			$_POST['rest_api_manager_blocked_endpoints'] = $decoded_endpoints;
		}
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'rest_api_manager',
			'rest_api_manager_blocked_endpoints',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_endpoints' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			'rest_api_manager_main',
			'',
			array( $this, 'render_section_description' ),
			'rest-api-manager'
		);

		add_settings_field(
			'blocked_endpoints',
			__( 'Manage Endpoints', 'rest-api-manager' ),
			array( $this, 'render_endpoints_field' ),
			'rest-api-manager',
			'rest_api_manager_main'
		);
	}

	/**
	 * Sanitize endpoints.
	 *
	 * @param array $input Raw input from settings form.
	 * @return array Sanitized endpoints.
	 */
	public function sanitize_endpoints( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $input as $endpoint ) {
			// Only trim whitespace and remove null bytes
			$endpoint = trim( $endpoint );
			$endpoint = str_replace( "\0", '', $endpoint );
			if ( ! empty( $endpoint ) ) {
				$sanitized[] = $endpoint;
			}
		}

		return $sanitized;
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if settings were updated
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'rest_api_manager_messages',
				'rest_api_manager_message',
				__( 'Settings Saved', 'rest-api-manager' ),
				'updated'
			);
		}

		settings_errors( 'rest_api_manager_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="rest-api-manager-container">
				<div class="rest-api-manager-content">
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

				<div class="rest-api-manager-sidebar">
					<?php $this->render_sidebar(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the admin sidebar.
	 */
	private function render_sidebar() { ?>

		<div class="rest-api-manager-sidebar-widget upgrade-widget">
			<h3><?php esc_html_e( 'Upgrade to Pro', 'rest-api-manager' ); ?></h3>
			<p><?php esc_html_e( 'Upgrade to REST API Manager Pro for advanced features:', 'rest-api-manager' ); ?></p>
			<ul class="rest-api-manager-pro-features">
				<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'Advanced Filters', 'rest-api-manager' ); ?></li>
				<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'All Namespaces', 'rest-api-manager' ); ?></li>
				<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'Dynamic Endpoint Support', 'rest-api-manager' ); ?></li>
				<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'Endpoint Preview', 'rest-api-manager' ); ?></li>
				<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'Security Logs', 'rest-api-manager' ); ?></li>
				<li class="rest-api-manager-pro-feature"><?php esc_html_e( 'Export Logs', 'rest-api-manager' ); ?></li>
			</ul>
			<a href="https://wpbuoy.com/product/rest-api-manager/#pricing" target="_blank" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'rest-api-manager' ); ?></a>
			<a href="https://wpbuoy.com/product/rest-api-manager-pro/#features/" target="_blank" class="button button-secondary"><?php esc_html_e( 'View All Features', 'rest-api-manager' ); ?></a>
		</div>

		<div class="rest-api-manager-sidebar-widget support-widget">
			<h3><?php esc_html_e( 'Need Help?', 'rest-api-manager' ); ?></h3>
			<p><?php esc_html_e( 'Get support and documentation for REST API Manager Pro.', 'rest-api-manager' ); ?></p>
			<ul class="support-links">
				<li><a href="https://wpbuoy.com/product/rest-api-manager/#faqs" target="_blank"><?php esc_html_e( 'FAQ', 'rest-api-manager' ); ?></a></li>
				<li><a href="https://wpbuoy.com/docs/rest-api-manager" target="_blank"><?php esc_html_e( 'Documentation', 'rest-api-manager' ); ?></a></li>
				<li><a href="https://wpbuoy.com/support" target="_blank"><?php esc_html_e( 'Helpdesk', 'rest-api-manager' ); ?></a></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render section description.
	 */
	public function render_section_description() { ?>
		<div class="notice notice-warning">
			<p><strong>Warning:</strong> Disabling certain endpoints may affect WordPress functionality, plugins, or themes that depend on the REST API. Test thoroughly after making changes.</p>
		</div>
	<?php }

	/**
	 * Render endpoints field.
	 */
	public function render_endpoints_field() {
		$blocked_endpoints = get_option( 'rest_api_manager_blocked_endpoints', array() );
		$all_routes        = $this->get_rest_routes();

		// For checkbox state, check if the current route matches any stored pattern
		$patterns_to_check = array();
		foreach ( $blocked_endpoints as $stored_pattern ) {
			$patterns_to_check[] = $stored_pattern;
		}

		foreach ( $all_routes as $namespace => $routes ) {
			// Count disabled endpoints in this namespace
			$disabled_count = 0;
			foreach ( $routes as $route => $route_data ) {
				if ( $this->is_route_blocked( $route, $patterns_to_check ) ) {
					$disabled_count++;
				}
			}
			?>
			<div class="rest-api-namespace">
				<div class="namespace-header" data-namespace="<?php echo esc_attr( $namespace ); ?>">
					<div class="namespace-title">
						<h3><?php echo esc_html( $namespace ); ?></h3>
						<?php if ( $disabled_count > 0 ) : ?>
							<span class="disabled-count"><?php echo esc_html( sprintf( _n( '%d disabled', '%d disabled', $disabled_count, 'rest-api-manager' ), $disabled_count ) ); ?></span>
						<?php endif; ?>
					</div>
					<button type="button" class="namespace-toggle" aria-expanded="false">
						<span class="toggle-icon"></span>
						<span class="sr-only"><?php esc_html_e( 'Toggle namespace', 'rest-api-manager' ); ?></span>
					</button>
				</div>
				<div class="rest-api-routes" style="display: none;">
					<?php
					foreach ( $routes as $route => $route_data ) {
						$field_id = 'endpoint_' . md5( $route );

						// Use base64 encoding to preserve exact route patterns
						$route_encoded = base64_encode( $route );

						// Check if this route should be considered blocked
						$is_checked = $this->is_route_blocked( $route, $patterns_to_check );
						$checked = $is_checked ? 'checked' : '';
						?>
						<div class="rest-api-route">
							<label for="<?php echo esc_attr( $field_id ); ?>">
								<input type="checkbox"
									   id="<?php echo esc_attr( $field_id ); ?>"
									   name="rest_api_manager_blocked_endpoints_encoded[]"
									   value="<?php echo esc_attr( $route_encoded ); ?>"
									   <?php echo esc_attr( $checked ); ?> />
								<span class="toggle-switch"></span>
								<div class="route-info">
									<span class="route-path"><?php echo esc_html( $route ); ?></span>
								</div>
							</label>
							<span class="route-status <?php echo $is_checked ? 'disabled' : 'enabled'; ?>">
								<?php echo $is_checked ? esc_html__( 'Disabled', 'rest-api-manager' ) : esc_html__( 'Enabled', 'rest-api-manager' ); ?>
							</span>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Get all REST routes grouped by namespace.
	 * Free version only shows WordPress core endpoints and static routes.
	 *
	 * @return array Grouped routes.
	 */
	private function get_rest_routes() {
		$server  = rest_get_server();
		$routes  = $server->get_routes();
		$grouped = array();

		// WordPress core namespaces only in free version
		$wp_namespaces = array( 'wp/v2', 'oembed/1.0', 'wp-site-health/v1', 'wp-block-editor/v1' );

		foreach ( $routes as $route => $route_data ) {
			// Skip the root endpoint
			if ( '/' === $route ) {
				continue;
			}

			// Skip dynamic/regex routes in free version
			if ( $this->is_regex_route( $route ) ) {
				continue;
			}

			// Extract namespace from route
			$parts = explode( '/', trim( $route, '/' ) );
			if ( count( $parts ) >= 2 ) {
				$namespace = $parts[0] . '/' . $parts[1];
			} else {
				$namespace = $parts[0];
			}

			// Only show WordPress core namespaces in free version
			if ( ! in_array( $namespace, $wp_namespaces, true ) ) {
				continue;
			}

			if ( ! isset( $grouped[ $namespace ] ) ) {
				$grouped[ $namespace ] = array();
			}

			$grouped[ $namespace ][ $route ] = $route_data;
		}

		// Sort namespaces and routes
		ksort( $grouped );
		foreach ( $grouped as $namespace => &$routes ) {
			ksort( $routes );
		}

		return $grouped;
	}

	/**
	 * Check if a route contains regex patterns.
	 *
	 * @param string $route The route to check.
	 * @return bool True if route contains regex patterns.
	 */
	private function is_regex_route( $route ) {
		return strpos( $route, '(?P<' ) !== false;
	}

	/**
	 * Check if a route is blocked by any of the stored patterns.
	 *
	 * @param string $route The current route.
	 * @param array  $patterns The patterns to check against.
	 * @return bool True if route is blocked.
	 */
	private function is_route_blocked( $route, $patterns ) {
		foreach ( $patterns as $pattern ) {
			// Exact match
			if ( $route === $pattern ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Maybe block REST endpoint.
	 *
	 * @param mixed            $result  Response to replace the requested version with.
	 * @param WP_REST_Server   $server  Server instance.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 * @return mixed Response or WP_Error if blocked.
	 */
	public function maybe_block_rest_endpoint( $result, $server, $request ) {
		// If there's already an error, return it
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$blocked_endpoints = get_option( 'rest_api_manager_blocked_endpoints', array() );
		$current_route = $request->get_route();
		$current_route = rtrim( $current_route, '/' );

		if ( empty( $blocked_endpoints ) ) {
			return $result;
		}

		foreach ( $blocked_endpoints as $blocked_pattern ) {
			$blocked_pattern = rtrim( $blocked_pattern, '/' );

			// Simple string comparison for non-regex routes
			if ( $current_route === $blocked_pattern ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'This REST API endpoint has been disabled.', 'rest-api-manager' ),
					array( 'status' => 403 )
				);
			}
		}

		return $result;
	}
}

/**
 * Returns the main instance of REST_API_Manager.
 *
 * @return REST_API_Manager
 */
function rest_api_manager() {
	return REST_API_Manager::instance();
}

// Initialize the plugin
rest_api_manager();
