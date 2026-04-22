<?php
/**
 * Plugin Name:       WPBuoy Endpoint Manager
 * Plugin URI:        https://wordpress.org/plugins/wpbuoy-endpoint-manager/
 * Description:       Control which REST API endpoints are accessible on your WordPress site. Enable or disable specific endpoints to enhance security and performance.
 * Version:           1.0.1
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Jose Martin Cipriano
 * Author URI:        https://www.linkedin.com/in/jmcipriano
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpbuoy-endpoint-manager
 * Domain Path:       /languages
 *
 * @package WPBuoy_Endpoint_Manager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// If Pro is active, go dormant — Pro handles everything.
if ( defined( 'WPBUOY_ENDPOINT_MANAGER_PRO' ) ) {
	add_action( 'admin_notices', function() {
		$deactivate_url = wp_nonce_url(
			admin_url( 'plugins.php?action=deactivate&plugin=wpbuoy-endpoint-manager%2Fwpbuoy-endpoint-manager.php' ),
			'deactivate-plugin_wpbuoy-endpoint-manager/wpbuoy-endpoint-manager.php'
		);
		echo '<div class="notice notice-info"><p>' .
			esc_html__( 'WPBuoy Endpoint Manager Pro is active — the free version is dormant and can be safely deactivated.', 'wpbuoy-endpoint-manager' ) .
			' <a href="' . esc_url( $deactivate_url ) . '">' . esc_html__( 'Deactivate free version', 'wpbuoy-endpoint-manager' ) . '</a>' .
		'</p></div>';
	} );
	return;
}

/**
 * Current plugin version.
 */
define( 'WPBUOY_ENDPOINT_MANAGER_VERSION', '1.0.1' );

/**
 * Plugin directory path.
 */
define( 'WPBUOY_ENDPOINT_MANAGER_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'WPBUOY_ENDPOINT_MANAGER_URL', plugin_dir_url( __FILE__ ) );

/**
 * The main plugin class.
 */
class Wpbuoy_Rest_Api_Manager {

	/**
	 * The single instance of the class.
	 *
	 * @var Wpbuoy_Rest_Api_Manager
	 */
	protected static $instance = null;

	/**
	 * Main Wpbuoy_Rest_Api_Manager Instance.
	 *
	 * Ensures only one instance of Wpbuoy_Rest_Api_Manager is loaded or can be loaded.
	 *
	 * @return Wpbuoy_Rest_Api_Manager Main instance.
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
		require_once WPBUOY_ENDPOINT_MANAGER_PATH . 'includes/helpers.php';
		require_once WPBUOY_ENDPOINT_MANAGER_PATH . 'includes/class-admin-sidebar.php';
	}

	/**
	 * Migrate old settings to new option name.
	 */
	private function migrate_old_settings() {
		$old_settings = get_option( 'wpbuoy_endpoint_manager_settings', null );
		if ( ! is_null( $old_settings ) && is_array( $old_settings ) ) {
			// Migrate old settings to new option name
			update_option( 'wpbuoy_endpoint_manager_blocked_endpoints', $old_settings );
			delete_option( 'wpbuoy_endpoint_manager_settings' );
		}
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_encoded_form_submission' ), 5 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'rest_pre_dispatch', array( $this, 'maybe_block_rest_endpoint' ), 10, 3 );
	}

	/**
	 * Enqueue admin styles and scripts.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( 'toplevel_page_wpbuoy-endpoint-manager' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpbuoy-endpoint-manager-admin',
			plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
			array(),
			WPBUOY_ENDPOINT_MANAGER_VERSION
		);

		wp_enqueue_script(
			'wpbuoy-endpoint-manager-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
			array(),
			WPBUOY_ENDPOINT_MANAGER_VERSION,
			true
		);
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'WPBuoy Endpoint Manager', 'wpbuoy-endpoint-manager' ),
			__( 'Endpoint Manager', 'wpbuoy-endpoint-manager' ),
			'manage_options',
			'wpbuoy-endpoint-manager',
			array( $this, 'render_admin_page' ),
			'dashicons-superhero',
			81
		);
	}

	/**
	 * Handle encoded form submission.
	 */
	public function handle_encoded_form_submission() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wpbuoy_endpoint_manager-options' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle encoded form submission
		$raw = isset( $_POST['wpbuoy_endpoint_manager_blocked_endpoints_encoded'] ) ? wp_unslash( $_POST['wpbuoy_endpoint_manager_blocked_endpoints_encoded'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_array( $raw ) ) {
			$decoded_endpoints = array();
			foreach ( $raw as $encoded ) {
				$encoded = sanitize_text_field( $encoded );
				$decoded = base64_decode( $encoded );
				if ( $decoded !== false ) {
					$decoded_endpoints[] = $decoded;
				}
			}
			$_POST['wpbuoy_endpoint_manager_blocked_endpoints'] = $decoded_endpoints;
		}
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'wpbuoy_endpoint_manager',
			'wpbuoy_endpoint_manager_blocked_endpoints',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_endpoints' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			'wpbuoy_endpoint_manager_main',
			'',
			array( $this, 'render_section_description' ),
			'wpbuoy-endpoint-manager'
		);

		add_settings_field(
			'blocked_endpoints',
			__( 'Manage Endpoints', 'wpbuoy-endpoint-manager' ),
			array( $this, 'render_endpoints_field' ),
			'wpbuoy-endpoint-manager',
			'wpbuoy_endpoint_manager_main'
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- set by WP options.php after its own nonce-verified save
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'wpbuoy_endpoint_manager_messages',
				'wpbuoy_endpoint_manager_message',
				__( 'Settings Saved', 'wpbuoy-endpoint-manager' ),
				'updated'
			);
		}

		settings_errors( 'wpbuoy_endpoint_manager_messages' );

		wpb_em_get_plugin_part( 'admin/page', 'main' );
	}

	/**
	 * Render section description.
	 */
	public function render_section_description() {
		wpb_em_get_plugin_part( 'admin/section', 'description' );
	}

	/**
	 * Render endpoints field.
	 */
	public function render_endpoints_field() {
		$blocked_endpoints = get_option( 'wpbuoy_endpoint_manager_blocked_endpoints', array() );
		$all_routes        = $this->get_rest_routes();

		$routes_data = array();
		foreach ( $all_routes as $namespace => $routes ) {
			$disabled_count   = 0;
			$namespace_routes = array();

			foreach ( $routes as $route => $route_data ) {
				$is_blocked = $this->is_route_blocked( $route, $blocked_endpoints );

				if ( $is_blocked ) {
					$disabled_count++;
				}

				$namespace_routes[ $route ] = array(
					'field_id'      => 'endpoint_' . md5( $route ),
					'route_encoded' => base64_encode( $route ),
					'is_blocked'    => $is_blocked,
				);
			}

			$routes_data[ $namespace ] = array(
				'disabled_count' => $disabled_count,
				'routes'         => $namespace_routes,
			);
		}

		wpb_em_get_plugin_part( 'admin/form', 'endpoints', compact( 'routes_data' ) );
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

		$blocked_endpoints = get_option( 'wpbuoy_endpoint_manager_blocked_endpoints', array() );
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
					__( 'This REST API endpoint has been disabled.', 'wpbuoy-endpoint-manager' ),
					array( 'status' => 403 )
				);
			}
		}

		return $result;
	}
}

/**
 * Returns the main instance of Wpbuoy_Rest_Api_Manager.
 *
 * @return Wpbuoy_Rest_Api_Manager
 */
function wpbuoy_endpoint_manager() {
	return Wpbuoy_Rest_Api_Manager::instance();
}

// Initialize the plugin
wpbuoy_endpoint_manager();
