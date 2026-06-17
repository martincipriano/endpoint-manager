<?php
/**
 * Plugin Name:       WPBuoy Endpoint Manager
 * Plugin URI:        https://wordpress.org/plugins/wpbuoy-endpoint-manager
 * Description:       Manage and block REST API endpoints to enhance your site's security and performance.
 * Version:           2.0.1
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

// Block activation when Pro is already active; create logs table.
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

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();
		$table           = $wpdb->prefix . 'wpbyem_logs';
		$sql             = "CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			endpoint varchar(255) NOT NULL,
			ip_address varchar(45) NOT NULL DEFAULT '',
			user_agent text NOT NULL,
			blocked_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY blocked_at (blocked_at)
		) $charset_collate;";
		dbDelta( $sql );
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
define( 'WPBYEM_VERSION', '2.0.1' );

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
	 * Admin page hook suffixes, populated in add_admin_menu().
	 *
	 * @var array
	 */
	protected $admin_hooks = array();

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
	}


	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_encoded_form_submission' ), 5 );
		add_action( 'admin_init', array( $this, 'handle_clear_logs' ) );
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
		if ( ! in_array( $hook, $this->admin_hooks, true ) ) {
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

		wp_localize_script(
			'wpbyem-admin',
			'wpbyemData',
			array(
				'settingsUrl' => esc_url( admin_url( 'admin.php?page=wpbyem-settings' ) ),
			)
		);
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		$hook                = add_menu_page(
			__( 'Endpoint Manager', 'wpbuoy-endpoint-manager' ),
			__( 'Endpoints', 'wpbuoy-endpoint-manager' ),
			'manage_options',
			'wpbyem',
			array( $this, 'render_admin_page' ),
			'dashicons-superhero',
			81
		);
		$this->admin_hooks[] = $hook;
		add_action( "load-{$hook}", array( $this, 'add_help_tabs' ) );

		$block_list_hook     = add_submenu_page(
			'wpbyem',
			__( 'Block List', 'wpbuoy-endpoint-manager' ),
			__( 'Block List', 'wpbuoy-endpoint-manager' ),
			'manage_options',
			'wpbyem-block-list',
			array( $this, 'render_block_list_page' )
		);
		$this->admin_hooks[] = $block_list_hook;
		add_action( "load-{$block_list_hook}", array( $this, 'add_help_tabs' ) );

		$logs_hook           = add_submenu_page(
			'wpbyem',
			__( 'Security Logs', 'wpbuoy-endpoint-manager' ),
			__( 'Logs', 'wpbuoy-endpoint-manager' ),
			'manage_options',
			'wpbyem-logs',
			array( $this, 'render_logs_page' )
		);
		$this->admin_hooks[] = $logs_hook;
		add_action( "load-{$logs_hook}", array( $this, 'add_help_tabs' ) );

		$settings_hook       = add_submenu_page(
			'wpbyem',
			__( 'Settings', 'wpbuoy-endpoint-manager' ),
			__( 'Settings', 'wpbuoy-endpoint-manager' ),
			'manage_options',
			'wpbyem-settings',
			array( $this, 'render_settings_page' )
		);
		$this->admin_hooks[] = $settings_hook;
		add_action( "load-{$settings_hook}", array( $this, 'add_help_tabs' ) );
	}

	/**
	 * Register Help tab content for the plugin admin page.
	 */
	public function add_help_tabs() {
		$screen = get_current_screen();
		$kb     = 'https://wpbuoy.com/endpoint-manager/knowledge-base/';

		$screen->add_help_tab( array(
			'id'      => 'wpbyem-help-getting-started',
			'title'   => __( 'Getting Started', 'wpbuoy-endpoint-manager' ),
			'content' =>
				'<h2>' . esc_html__( 'Getting Started', 'wpbuoy-endpoint-manager' ) . '</h2>' .
				'<ul>' .
					'<li><a href="' . esc_url( $kb . 'getting-started/#what-is-a-rest-api-endpoint' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'What is a REST API endpoint?', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'getting-started/#free-vs-pro' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Free vs Pro', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'getting-started/#static-vs-dynamic-endpoints' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Static vs dynamic endpoints', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'getting-started/#minimum-requirements' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Minimum requirements', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'getting-started/#initial-configuration' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Initial configuration', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'getting-started/#will-this-break-my-site' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Will this break my site?', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'getting-started/#privacy-and-data-collection' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Privacy and data collection', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
				'</ul>',
		) );

		$screen->add_help_tab( array(
			'id'      => 'wpbyem-help-features',
			'title'   => __( 'Features & Usage', 'wpbuoy-endpoint-manager' ),
			'content' =>
				'<h2>' . esc_html__( 'Features & Usage', 'wpbuoy-endpoint-manager' ) . '</h2>' .
				'<ul>' .
					'<li><a href="' . esc_url( $kb . 'features-and-usage/#which-endpoints-are-safe-to-disable' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Which endpoints are safe to disable?', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'features-and-usage/#endpoint-preview' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Endpoint preview', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'features-and-usage/#search-and-filters' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Search and filters', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'features-and-usage/#security-logging' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Security logging', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'features-and-usage/#log-filters' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Log filters', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'features-and-usage/#rate-limiting-pro' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Rate limiting', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'features-and-usage/#ip-block-list-pro' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'IP Block List', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'features-and-usage/#shared-ips-and-auto-block-pro' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Shared IPs and auto-block', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'features-and-usage/#csv-export-pro' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'CSV export', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'features-and-usage/#compatibility' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Compatibility', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
				'</ul>',
		) );

		$screen->add_help_tab( array(
			'id'      => 'wpbyem-help-licensing',
			'title'   => __( 'Licensing & Billing', 'wpbuoy-endpoint-manager' ),
			'content' =>
				'<h2>' . esc_html__( 'Licensing & Billing', 'wpbuoy-endpoint-manager' ) . '</h2>' .
				'<ul>' .
					'<li><a href="' . esc_url( $kb . 'licensing-and-billing/#how-to-activate-your-license' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'How to activate your license', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'licensing-and-billing/#using-one-license-on-multiple-sites' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Using one license on multiple sites', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'licensing-and-billing/#what-happens-when-your-license-expires' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'What happens when your license expires', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'licensing-and-billing/#what-does-pro-features-paused-mean' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'What does "Pro Features Paused" mean?', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'licensing-and-billing/#refunds' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Refunds', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
				'</ul>',
		) );

		$screen->add_help_tab( array(
			'id'      => 'wpbyem-help-troubleshooting',
			'title'   => __( 'Troubleshooting', 'wpbuoy-endpoint-manager' ),
			'content' =>
				'<h2>' . esc_html__( 'Troubleshooting', 'wpbuoy-endpoint-manager' ) . '</h2>' .
				'<ul>' .
					'<li><a href="' . esc_url( $kb . 'troubleshooting/#i-disabled-an-endpoint-and-now-my-site-isnt-working' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'I disabled an endpoint and now my site isn\'t working', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'troubleshooting/#i-cant-activate-my-license' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( "I can't activate my license", 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'troubleshooting/#security-logs-arent-showing-any-data-pro' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( "Security logs aren't showing any data", 'wpbuoy-endpoint-manager' ) . '</a></li>' .
					'<li><a href="' . esc_url( $kb . 'troubleshooting/#how-to-get-support' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'How to get support', 'wpbuoy-endpoint-manager' ) . '</a></li>' .
				'</ul>',
		) );

		$screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:', 'wpbuoy-endpoint-manager' ) . '</strong></p>' .
			'<p><a href="' . esc_url( $kb ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Knowledge Base', 'wpbuoy-endpoint-manager' ) . '</a></p>' .
			'<p><a href="https://wpbuoy.com/my-account/support/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'wpbuoy-endpoint-manager' ) . '</a></p>'
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
			// wp_slash() re-adds slashes that wp_unslash() in options.php will strip,
			// preserving literal backslashes in regex patterns (e.g. \d in dynamic routes).
			$_POST['wpbyem_blocked_endpoints'] = wp_slash( $decoded_endpoints );
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

		// Admin Bypass — stored in the same option as Pro for seamless upgrade path.
		register_setting(
			'wpbyem-settings',
			'wpbyem_rate_limit_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_admin_bypass_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize admin bypass settings.
	 * Only touches exclude_admins_endpoints — preserves any Pro keys already in the option.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_admin_bypass_settings( $input ) {
		$existing                              = get_option( 'wpbyem_rate_limit_settings', array() );
		$existing['exclude_admins_endpoints']  = ! empty( $input['exclude_admins_endpoints'] );
		return $existing;
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
			$endpoint = trim( (string) $endpoint );
			// Allowlist characters valid in REST route patterns, including regex named-group syntax (<>).
			if ( ! empty( $endpoint ) && preg_match( '#^[a-zA-Z0-9/_\-\.\(\)\?\<\>\[\]\+\*\^\$\{\}\|\\\\: ]+$#', $endpoint ) ) {
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
					'is_dynamic'        => $this->is_regex_route( $route ),
					'is_restricted'     => $is_restricted,
					'restricted_source' => $restricted_source,
					'preview_url'       => $this->is_regex_route( $route ) ? $this->get_dynamic_preview_url( $route ) : rest_url( $route ),
					'preview_params'    => $this->is_regex_route( $route ) ? $this->get_dynamic_preview_params( $route ) : array(),
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
	 * Convert a WordPress REST route pattern to a standard PHP regex.
	 *
	 * @param string $route WordPress route pattern with named capture groups.
	 * @return string Compiled regex pattern with delimiters and anchors.
	 */
	private function convert_route_to_regex( $route ) {
		$pattern = preg_replace( '/\(\?P<\w+>/', '(', $route );
		$parts   = preg_split( '/(\([^)]+\))/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE );

		foreach ( $parts as $i => $part ) {
			if ( $i % 2 === 0 ) {
				$parts[ $i ] = preg_quote( $part, '#' );
			}
		}

		return '#^' . implode( '', $parts ) . '$#';
	}

	/**
	 * Build a basic preview URL for a dynamic route by substituting capture groups with a default value.
	 *
	 * @param string $route WordPress REST route pattern.
	 * @return string Resolved REST URL suitable for a browser preview.
	 */
	private function get_dynamic_preview_url( $route ) {
		$resolved = preg_replace_callback(
			'/\(\?P<([^>]+)>[^)]+\)/',
			function( $matches ) {
				return '__' . $matches[1] . '__';
			},
			$route
		);
		return rest_url( $resolved );
	}

	/**
	 * Extract named capture group params from a dynamic route for the preview modal.
	 *
	 * @param string $route WordPress REST route pattern.
	 * @return array Associative array of param name => default value.
	 */
	private function get_dynamic_preview_params( $route ) {
		preg_match_all( '/\(\?P<([^>]+)>[^)]+\)/', $route, $matches );
		$params = array();
		foreach ( $matches[1] as $name ) {
			$params[ $name ] = 'id' === $name ? (string) $this->resolve_route_id( $route ) : '1';
		}
		return $params;
	}

	/**
	 * Resolve a real available ID for a dynamic REST route by inspecting post types, taxonomies, users, and comments.
	 *
	 * @param string $route WordPress REST route pattern.
	 * @return int Resolved ID, or 1 as fallback.
	 */
	private function resolve_route_id( $route ) {
		$path     = preg_replace( '/\(\?P<[^>]+>[^)]+\).*/', '', $route );
		$path     = rtrim( $path, '/' );
		$parts    = explode( '/', trim( $path, '/' ) );
		$resource = end( $parts );

		// Post types with a known REST base.
		$post_types = get_post_types( array( 'show_in_rest' => true ), 'objects' );
		foreach ( $post_types as $pt ) {
			$rest_base = $pt->rest_base ?: $pt->name;
			if ( $rest_base === $resource ) {
				$posts = get_posts( array(
					'post_type'      => $pt->name,
					'posts_per_page' => 1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
				) );
				if ( ! empty( $posts ) ) {
					return (int) $posts[0];
				}
			}
		}

		// Taxonomies.
		$taxonomies = get_taxonomies( array( 'show_in_rest' => true ), 'objects' );
		foreach ( $taxonomies as $tax ) {
			$rest_base = $tax->rest_base ?: $tax->name;
			if ( $rest_base === $resource ) {
				$terms = get_terms( array(
					'taxonomy'   => $tax->name,
					'number'     => 1,
					'hide_empty' => false,
					'fields'     => 'ids',
				) );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					return (int) $terms[0];
				}
			}
		}

		// Users.
		if ( 'users' === $resource ) {
			$users = get_users( array( 'number' => 1, 'fields' => 'ID' ) );
			if ( ! empty( $users ) ) {
				return (int) $users[0];
			}
		}

		// Comments.
		if ( 'comments' === $resource ) {
			$comments = get_comments( array( 'number' => 1, 'fields' => 'ids' ) );
			if ( ! empty( $comments ) ) {
				return (int) $comments[0];
			}
		}

		return 1;
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

		// When "Exclude admins from blocked endpoints" is on (default), admins bypass enforcement.
		$settings          = get_option( 'wpbyem_rate_limit_settings', array() );
		$exclude_endpoints = ! isset( $settings['exclude_admins_endpoints'] ) || ! empty( $settings['exclude_admins_endpoints'] );
		if ( $exclude_endpoints ) {
			$user_id = wp_validate_auth_cookie( '', 'logged_in' ) ?: get_current_user_id();
			if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
				return $result;
			}
		}

		$blocked_endpoints = get_option( 'wpbyem_blocked_endpoints', array() );
		$current_route = $request->get_route();
		$current_route = rtrim( $current_route, '/' );

		if ( empty( $blocked_endpoints ) ) {
			return $result;
		}

		// Repair patterns corrupted by wp_unslash() stripping backslashes before wp_slash() was applied.
		$blocked_endpoints = array_map( function( $endpoint ) {
			if ( strpos( $endpoint, '(?P[' ) !== false && strpos( $endpoint, '(?P<' ) === false ) {
				$endpoint = str_replace( '(?P[', '(?P<id>[', $endpoint );
			}
			if ( strpos( $endpoint, '[d]+' ) !== false ) {
				$endpoint = str_replace( '[d]+', '[\d]+', $endpoint );
			}
			return $endpoint;
		}, $blocked_endpoints );

		foreach ( $blocked_endpoints as $blocked_pattern ) {
			$blocked_pattern = rtrim( $blocked_pattern, '/' );

			$matched = false;

			if ( $this->is_regex_route( $blocked_pattern ) ) {
				$regex   = $this->convert_route_to_regex( $blocked_pattern );
				$matched = preg_match( $regex, $current_route ) === 1;
			} else {
				$matched = $current_route === $blocked_pattern;
			}

			if ( $matched ) {
				$this->log_blocked_request( $current_route );
				return new WP_Error(
					'rest_forbidden',
					__( 'This REST API endpoint has been disabled.', 'wpbuoy-endpoint-manager' ),
					array( 'status' => 403 )
				);
			}
		}

		return $result;
	}

	/**
	 * Render Block List page — Pro feature teaser.
	 */
	public function render_block_list_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wpbyem_get_plugin_part(
			'admin/upgrade',
			'banner',
			array(
				'heading'     => __( 'Keep your REST API protected with IP blocking', 'wpbuoy-endpoint-manager' ),
				'description' => __( 'Block malicious IPs, auto-block repeat offenders, and maintain an allowlist for trusted sources — all from one place.', 'wpbuoy-endpoint-manager' ),
				'features' => array(
					__('Block any REST API endpoint with a configurable response code and message', 'wpbuoy-endpoint-manager'),
					__('Rate limiting — global and per-endpoint request thresholds', 'wpbuoy-endpoint-manager'),
					__('IP Block List — manual blocks, auto-block, and allowlist', 'wpbuoy-endpoint-manager'),
					__('Endpoint preview — inspect live API responses in an inline modal', 'wpbuoy-endpoint-manager'),
					__('Intuitive admin UI — namespace accordion, live search, and multi-criteria filters', 'wpbuoy-endpoint-manager'),
				),
				'cta_url'  => 'https://wpbuoy.com/product/endpoint-manager/#pricing',
			)
		);
	}

	/**
	 * Render Logs page.
	 */
	public function render_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wpbyem_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$logs = $wpdb->get_results( "SELECT * FROM $table ORDER BY blocked_at DESC LIMIT 500", ARRAY_A );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cleared          = isset( $_GET['cleared'] ) && '1' === $_GET['cleared'];
		$unique_ips       = array_values( array_unique( array_column( $logs, 'ip_address' ) ) );
		$unique_endpoints = array_values( array_unique( array_column( $logs, 'endpoint' ) ) );
		$logs_page_url    = admin_url( 'admin.php?page=wpbyem-logs' );

		sort( $unique_ips );
		sort( $unique_endpoints );

		wpbyem_get_plugin_part( 'admin/page', 'logs', compact( 'logs', 'total', 'cleared', 'unique_ips', 'unique_endpoints', 'logs_page_url' ) );
	}

	/**
	 * Render Settings page — Pro feature teaser.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- set by WP options.php after its own nonce-verified save
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'wpbyem_settings_messages',
				'wpbyem_settings_message',
				__( 'Settings Saved', 'wpbuoy-endpoint-manager' ),
				'updated'
			);
		}

		settings_errors( 'wpbyem_settings_messages' );

		wpbyem_get_plugin_part( 'admin/page', 'settings' );
	}

	/**
	 * Log a blocked REST request to the database.
	 *
	 * @param string $route The blocked route path.
	 */
	private function log_blocked_request( $route ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'wpbyem_logs',
			array(
				'endpoint'   => $route,
				'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'blocked_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Handle Clear Logs form submission.
	 */
	public function handle_clear_logs() {
		if ( ! isset( $_POST['wpbyem_clear_logs_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpbyem_clear_logs_nonce'] ) ), 'wpbyem_clear_logs' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wpbyem_logs" );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wpbyem-logs',
					'cleared' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
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
