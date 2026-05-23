<?php
/**
 * Plugin Name:       WPBuoy Endpoint Manager
 * Plugin URI:        https://wordpress.org/plugins/wpbuoy-endpoint-manager
 * Description:       Manage and block REST API endpoints to enhance your site's security and performance.
 * Version:           1.1.4
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            WPBuoy
 * Author URI:        https://wpbuoy.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpbuoy-endpoint-manager
 * Domain Path:       /languages
 *
 * @package Wpbyem_Endpoint_Manager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Block activation when Pro is already active.
register_activation_hook(
	__FILE__,
	function () {
		if ( defined( 'WPBYEM_PRO' ) || in_array( 'endpoint-manager-pro/wpbuoy-endpoint-manager-pro.php', (array) get_option( 'active_plugins', array() ), true ) ) {
			wp_die(
				esc_html__( 'WPBuoy Endpoint Manager cannot be activated while the Pro version is active.', 'wpbuoy-endpoint-manager' ),
				esc_html__( 'Plugin Activation Error', 'wpbuoy-endpoint-manager' ),
				array( 'back_link' => true )
			);
		}
	}
);

// If Pro is active, go dormant — Pro handles everything.
if ( defined( 'WPBYEM_PRO' ) ) {
	add_action(
		'admin_init',
		function () {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
	);
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-warning is-dismissible"><p>' .
				esc_html__( 'WPBuoy Endpoint Manager (free) has been automatically deactivated because the Pro version is active.', 'wpbuoy-endpoint-manager' ) .
			'</p></div>';
		}
	);
	return;
}

// Safety net: deactivate free if pro is in the active plugins list.
if ( in_array( 'endpoint-manager-pro/wpbuoy-endpoint-manager-pro.php', (array) get_option( 'active_plugins', array() ), true ) ) {
	add_action(
		'admin_init',
		function () {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
	);
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-warning is-dismissible"><p>' .
				esc_html__( 'WPBuoy Endpoint Manager (free) has been automatically deactivated because the Pro version is active.', 'wpbuoy-endpoint-manager' ) .
			'</p></div>';
		}
	);
	return;
}

/**
 * Current plugin version.
 */
define( 'WPBYEM_VERSION', '1.1.4' );

/**
 * Plugin directory path.
 */
define( 'WPBYEM_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'WPBYEM_URL', plugin_dir_url( __FILE__ ) );

/**
 * The main plugin class.
 */
class Wpbyem_Endpoint_Manager {

	/**
	 * The single instance of the class.
	 *
	 * @var Wpbyem_Endpoint_Manager
	 */
	protected static $instance = null;

	/**
	 * Main Wpbyem_Endpoint_Manager Instance.
	 *
	 * Ensures only one instance of Wpbyem_Endpoint_Manager is loaded or can be loaded.
	 *
	 * @return Wpbyem_Endpoint_Manager Main instance.
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
	private function __construct() {
		$this->load_dependencies();

		$this->init_hooks();
	}

	/**
	 * Load plugin dependencies.
	 */
	private function load_dependencies() {
		require_once WPBYEM_PATH . 'includes/helpers.php';
		require_once WPBYEM_PATH . 'includes/class-admin-sidebar.php';
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
	 * Load plugin text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wpbuoy-endpoint-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Enqueue admin styles and scripts.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( 'toplevel_page_wpbyem' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpbyem-admin',
			plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
			array(),
			WPBYEM_VERSION
		);

		wp_enqueue_script(
			'wpbyem-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
			array(),
			WPBYEM_VERSION,
			true
		);
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		$hook = add_menu_page(
			__( 'WPBuoy Endpoint Manager', 'wpbuoy-endpoint-manager' ),
			__( 'Endpoints', 'wpbuoy-endpoint-manager' ),
			'manage_options',
			'wpbyem',
			array( $this, 'render_admin_page' ),
			'dashicons-superhero',
			81
		);

		add_action( "load-{$hook}", array( $this, 'add_help_tabs' ) );
	}

	/**
	 * Register Help tab content for the plugin admin page.
	 */
	public function add_help_tabs() {
		$screen = get_current_screen();

		$screen->add_help_tab(
			array(
				'id'      => 'wpbyem-help-support',
				'title'   => __( 'Need Help?', 'wpbuoy-endpoint-manager' ),
				'content' =>
					'<h2>' . esc_html__( 'Need Help?', 'wpbuoy-endpoint-manager' ) . '</h2>' .
					'<ul>' .
						'<li><a href="https://wpbuoy.com/product/endpoint-manager/#faqs" target="_blank" rel="noopener noreferrer">' . esc_html__( 'FAQ', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
						'<li><a href="https://wpbuoy.com/endpoint-manager/knowledge-base/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Knowledge Base', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
						'<li><a href="https://wpbuoy.com/my-account/support/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'</ul>',
			)
		);
	}

	/**
	 * Handle encoded form submission.
	 */
	public function handle_encoded_form_submission() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wpbyem-options' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle encoded form submission
		$raw = isset( $_POST['wpbyem_blocked_endpoints_encoded'] )
			? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['wpbyem_blocked_endpoints_encoded'] ) )
			: array();
		if ( is_array( $raw ) ) {
			$decoded_endpoints = array();
			foreach ( $raw as $encoded ) {
				$decoded = base64_decode( $encoded );
				if ( $decoded !== false ) {
					$decoded_endpoints[] = $decoded;
				}
			}
			$_POST['wpbyem_blocked_endpoints'] = $decoded_endpoints;
		}
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'wpbyem',
			'wpbyem_blocked_endpoints',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_endpoints' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			'wpbyem_main',
			'',
			array( $this, 'render_section_description' ),
			'wpbyem'
		);

		add_settings_field(
			'blocked_endpoints',
			__( 'Manage Endpoints', 'wpbuoy-endpoint-manager' ),
			array( $this, 'render_endpoints_field' ),
			'wpbyem',
			'wpbyem_main'
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
			$endpoint = sanitize_text_field( $endpoint );
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
				'wpbyem_messages',
				'wpbyem_message',
				__( 'Settings Saved', 'wpbuoy-endpoint-manager' ),
				'updated'
			);
		}

		settings_errors( 'wpbyem_messages' );

		wpbyem_get_plugin_part( 'admin/page', 'main' );
	}

	/**
	 * Render section description.
	 */
	public function render_section_description() {
		wpbyem_get_plugin_part( 'admin/section', 'description' );
	}

	/**
	 * Render endpoints field.
	 */
	public function render_endpoints_field() {
		$blocked_endpoints = get_option( 'wpbyem_blocked_endpoints', array() );
		$all_routes        = $this->get_rest_routes();

		$routes_data = array();
		$all_methods = array();
		$namespaces  = array_keys( $all_routes );

		foreach ( $all_routes as $namespace => $routes ) {
			$disabled_count   = 0;
			$namespace_routes = array();

			foreach ( $routes as $route => $route_data ) {
				$is_blocked = $this->is_route_blocked( $route, $blocked_endpoints );

				if ( $is_blocked ) {
					$disabled_count++;
				}

				// Extract HTTP methods and check permission callbacks for all endpoint definitions.
				$methods           = array();
				$is_restricted     = true;
				$restricted_source = null;
				foreach ( $route_data as $endpoint ) {
					if ( isset( $endpoint['methods'] ) ) {
						$methods = array_merge( $methods, array_keys( $endpoint['methods'] ) );
					}
					$cb = $endpoint['permission_callback'] ?? null;
					if ( '__return_true' === $cb || null === $cb ) {
						$is_restricted = false;
					} elseif ( $is_restricted && null === $restricted_source && $cb ) {
						$restricted_source = $this->get_restricted_source( $cb );
					}
				}
				$methods     = array_unique( $methods );
				sort( $methods );
				$all_methods = array_merge( $all_methods, $methods );

				$namespace_routes[ $route ] = array(
					'field_id'          => 'endpoint_' . md5( $route ),
					'route_encoded'     => base64_encode( $route ),
					'is_blocked'        => $is_blocked,
					'is_restricted'     => $is_restricted,
					'restricted_source' => $restricted_source,
					'preview_url'       => rest_url( $route ),
					'methods'           => $methods,
				);
			}

			$routes_data[ $namespace ] = array(
				'disabled_count' => $disabled_count,
				'routes'         => $namespace_routes,
			);
		}

		$all_methods = array_unique( $all_methods );
		sort( $all_methods );

		wpbyem_get_plugin_part( 'admin/form', 'endpoints', compact( 'routes_data', 'namespaces', 'all_methods' ) );
	}

	/**
	 * Get all registered static REST routes grouped by namespace.
	 *
	 * @return array Grouped routes.
	 */
	private function get_rest_routes() {
		$server  = rest_get_server();
		$routes  = $server->get_routes();
		$grouped = array();

		foreach ( $routes as $route => $route_data ) {
			// Skip the root endpoint.
			if ( '/' === $route ) {
				continue;
			}

			// Skip dynamic/regex routes.
			if ( $this->is_regex_route( $route ) ) {
				continue;
			}

			// Extract namespace from route.
			$parts = explode( '/', trim( $route, '/' ) );
			if ( count( $parts ) >= 2 ) {
				$namespace = $parts[0] . '/' . $parts[1];
			} else {
				$namespace = $parts[0];
			}

			if ( ! isset( $grouped[ $namespace ] ) ) {
				$grouped[ $namespace ] = array();
			}

			$grouped[ $namespace ][ $route ] = $route_data;
		}

		// Sort namespaces and routes.
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
	 * Resolve the plugin or theme name that registered a given permission callback.
	 *
	 * @param callable $callback The permission_callback to inspect.
	 * @return string|null Plugin/theme name, or null if it can't be determined.
	 */
	private function get_restricted_source( $callback ) {
		if ( ! $callback || '__return_true' === $callback ) {
			return null;
		}

		try {
			if ( is_array( $callback ) ) {
				$ref = new ReflectionMethod( $callback[0], $callback[1] );
			} elseif ( is_string( $callback ) && strpos( $callback, '::' ) !== false ) {
				list( $class, $method ) = explode( '::', $callback, 2 );
				$ref = new ReflectionMethod( $class, $method );
			} else {
				$ref = new ReflectionFunction( $callback );
			}

			$file = wp_normalize_path( (string) $ref->getFileName() );
			if ( ! $file ) {
				return null;
			}

			$plugins_dir = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) );
			if ( str_starts_with( $file, $plugins_dir ) ) {
				$relative     = substr( $file, strlen( $plugins_dir ) );
				$plugin_folder = strtok( $relative, '/' );
				foreach ( get_plugins() as $plugin_file => $plugin_data ) {
					if ( str_starts_with( $plugin_file, $plugin_folder . '/' ) ) {
						return $plugin_data['Name'];
					}
				}
				return $plugin_folder;
			}

			$themes_dir = trailingslashit( wp_normalize_path( get_theme_root() ) );
			if ( str_starts_with( $file, $themes_dir ) ) {
				return wp_get_theme()->get( 'Name' );
			}
		} catch ( Exception $e ) {
			return null;
		}

		return null;
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

		$blocked_endpoints = get_option( 'wpbyem_blocked_endpoints', array() );
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
 * Returns the main instance of Wpbyem_Endpoint_Manager.
 *
 * @return Wpbyem_Endpoint_Manager
 */
function wpbyem() {
	return Wpbyem_Endpoint_Manager::instance();
}

// Initialize the plugin
wpbyem();
