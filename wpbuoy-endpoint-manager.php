<?php
/**
 * Plugin Name:       WPBuoy Endpoint Manager
 * Plugin URI:        https://wordpress.org/plugins/wpbuoy-endpoint-manager
 * Description:       Manage and block REST API endpoints to enhance your site's security and performance.
 * Version:           2.4.0
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

// Create logs table on activation.
register_activation_hook(
	__FILE__,
	function () {
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

// If Pro is active, stay dormant — Pro handles everything.
if ( defined( 'WPBYEM_PRO' ) || in_array( 'wpbuoy-endpoint-manager-pro/wpbuoy-endpoint-manager-pro.php', (array) get_option( 'active_plugins', array() ), true ) ) {
	return;
}

/**
 * Current plugin version.
 */
define( 'WPBYEM_VERSION', '2.4.0' );

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
	 * Logs page hook suffix, populated in add_admin_menu() — used by
	 * default_hidden_logs_columns() to scope its column-hiding to the Logs
	 * screen specifically.
	 *
	 * @var string
	 */
	protected $logs_hook = '';

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
		add_action( 'admin_init', array( $this, 'maybe_export_config' ) );
		add_action( 'admin_init', array( $this, 'maybe_import_config' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_import_notice' ) );
		add_filter( 'rest_pre_dispatch', array( $this, 'maybe_block_rest_endpoint' ), 10, 3 );
		add_action( 'wp_ajax_wpbyem_search_logs', array( $this, 'ajax_search_logs' ) );
		add_filter( 'set_screen_option_wpbyem_logs_per_page', array( $this, 'save_logs_per_page_option' ), 10, 3 );

		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );

		// Schedule log cleanup.
		add_action( 'wp', array( $this, 'schedule_log_cleanup' ) );
		add_action( 'wpbyem_cleanup_logs', array( $this, 'cleanup_old_logs' ) );
	}

	/**
	 * Plugin deactivation handler.
	 */
	public function plugin_deactivation() {
		wp_clear_scheduled_hook( 'wpbyem_cleanup_logs' );
	}

	/**
	 * Schedule log cleanup if not already scheduled.
	 */
	public function schedule_log_cleanup() {
		if ( ! wp_next_scheduled( 'wpbyem_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'wpbyem_cleanup_logs' );
		}
	}

	/**
	 * Delete logs older than the configured retention period.
	 */
	public function cleanup_old_logs() {
		global $wpdb;

		$settings       = get_option( 'wpbyem_rate_limit_settings', array() );
		$retention_days = isset( $settings['log_retention_days'] ) ? (int) $settings['log_retention_days'] : 30;
		if ( ! in_array( $retention_days, array( 7, 14, 30, 60, 90 ), true ) ) {
			$retention_days = 30;
		}

		$table    = $wpdb->prefix . 'wpbyem_logs';
		$cutoff   = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is a prefixed table name, never user input
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM $table WHERE blocked_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$cutoff
		) );
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

		// The "Filters" visibility-toggle logic is shared between the
		// Endpoints and Logs pages (see filter-visibility.js) — register it
		// once as a dependency for whichever page-specific script needs it.
		if ( in_array( $hook, array( 'toplevel_page_wpbyem', 'endpoints_page_wpbyem-logs' ), true ) ) {
			wp_enqueue_script(
				'wpbyem-filter-visibility',
				plugin_dir_url( __FILE__ ) . 'assets/js/filter-visibility.js',
				array(),
				WPBYEM_VERSION,
				true
			);
		}

		wp_enqueue_script(
			'wpbyem-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
			'toplevel_page_wpbyem' === $hook ? array( 'wpbyem-filter-visibility' ) : array(),
			WPBYEM_VERSION,
			true
		);

		$rate_settings = get_option( 'wpbyem_rate_limit_settings', array() );
		$exclude_roles = isset( $rate_settings['exclude_roles_endpoints'] ) ? (array) $rate_settings['exclude_roles_endpoints'] : array( 'administrator' );
		wp_localize_script(
			'wpbyem-admin',
			'wpbyemData',
			array(
				'settingsUrl'            => esc_url( admin_url( 'admin.php?page=wpbyem-settings' ) ),
				'excludeAdminsEndpoints' => $this->user_has_bypass_role( $exclude_roles ),
			)
		);

		if ( 'endpoints_page_wpbyem-settings' === $hook ) {
			wp_enqueue_script(
				'wpbyem-settings',
				plugin_dir_url( __FILE__ ) . 'assets/js/settings.js',
				array(),
				WPBYEM_VERSION,
				true
			);
		}

		if ( 'endpoints_page_wpbyem-logs' === $hook ) {
			wp_enqueue_script(
				'wpbyem-logs',
				plugin_dir_url( __FILE__ ) . 'assets/js/logs.js',
				array( 'wpbyem-filter-visibility' ),
				WPBYEM_VERSION,
				true
			);

			wp_localize_script( 'wpbyem-logs', 'wpbyemLogs', array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'wpbyem_logs_nonce' ),
				'logsPageUrl' => admin_url( 'admin.php?page=wpbyem-logs' ),
			) );
		}
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
		add_action( "load-{$hook}", array( $this, 'setup_endpoints_screen_options' ) );

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
		$this->logs_hook     = $logs_hook;
		add_action( "load-{$logs_hook}", array( $this, 'add_help_tabs' ) );
		add_action( "load-{$logs_hook}", array( $this, 'setup_logs_screen_options' ) );

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
	 * Handle configuration export request, before any HTML is output.
	 */
	public function maybe_export_config() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below
		if ( ! isset( $_GET['page'] ) || 'wpbyem-settings' !== $_GET['page'] || ! isset( $_GET['action'] ) || 'export_config' !== $_GET['action'] ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wpbyem_export_config' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wpbuoy-endpoint-manager' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wpbuoy-endpoint-manager' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above
		$sections = isset( $_GET['sections'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_GET['sections'] ) ) : array( 'endpoints', 'settings' );
		$sections = array_intersect( $sections, array( 'endpoints', 'settings' ) );
		if ( empty( $sections ) ) {
			$sections = array( 'endpoints', 'settings' );
		}

		$settings = get_option( 'wpbyem_rate_limit_settings', array() );

		$export = array(
			'schema_version' => 1,
			'generated_at'   => gmdate( 'c' ),
			'site_url'       => home_url(),
		);

		if ( in_array( 'endpoints', $sections, true ) ) {
			$export['blocked_endpoints'] = array_values( get_option( 'wpbyem_blocked_endpoints', array() ) );
		}

		if ( in_array( 'settings', $sections, true ) ) {
			$export['settings'] = array(
				'exclude_roles_endpoints' => isset( $settings['exclude_roles_endpoints'] ) ? array_values( (array) $settings['exclude_roles_endpoints'] ) : array(),
				'log_retention_days'      => isset( $settings['log_retention_days'] ) ? (int) $settings['log_retention_days'] : 30,
			);
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="wpbyem-config-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $export, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Handle configuration import upload.
	 */
	public function maybe_import_config() {
		if ( ! isset( $_POST['wpbyem_import_submit'] ) ) {
			return;
		}

		if ( ! isset( $_POST['wpbyem_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpbyem_import_nonce'] ) ), 'wpbyem_import_config' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wpbuoy-endpoint-manager' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wpbuoy-endpoint-manager' ) );
		}

		$redirect = admin_url( 'admin.php?page=wpbyem-settings' );

		if ( empty( $_FILES['wpbyem_import_file']['tmp_name'] ) || UPLOAD_ERR_OK !== $_FILES['wpbyem_import_file']['error'] ) {
			$this->set_import_notice( __( 'Import failed: no file was uploaded, or the upload failed.', 'wpbuoy-endpoint-manager' ), 'error' );
			wp_safe_redirect( $redirect );
			exit;
		}

		$tmp_name = $_FILES['wpbyem_import_file']['tmp_name'];
		if ( ! is_uploaded_file( $tmp_name ) || filesize( $tmp_name ) > 2 * MB_IN_BYTES ) {
			$this->set_import_notice( __( 'Import failed: invalid or oversized upload.', 'wpbuoy-endpoint-manager' ), 'error' );
			wp_safe_redirect( $redirect );
			exit;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents -- reading a validated PHP upload tmp file, not a remote/user-supplied path
		$data = json_decode( file_get_contents( $tmp_name ), true );

		if ( ! is_array( $data ) || ! isset( $data['schema_version'] ) ) {
			$this->set_import_notice( __( 'Import failed: file is not a recognized WPBuoy Endpoint Manager export.', 'wpbuoy-endpoint-manager' ), 'error' );
			wp_safe_redirect( $redirect );
			exit;
		}

		$imported_count = 0;
		$skipped_count  = 0;

		if ( isset( $data['blocked_endpoints'] ) && is_array( $data['blocked_endpoints'] ) ) {
			$known_routes = array();
			foreach ( $this->get_rest_routes() as $routes ) {
				$known_routes = array_merge( $known_routes, array_keys( $routes ) );
			}

			$candidates = $this->sanitize_endpoints( $data['blocked_endpoints'] );
			$valid      = array_values( array_intersect( $candidates, $known_routes ) );
			$imported_count = count( $valid );
			$skipped_count  = count( $data['blocked_endpoints'] ) - $imported_count;

			update_option( 'wpbyem_blocked_endpoints', $valid );
		}

		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$valid_roles = array_keys( wp_roles()->roles );

			$new_settings = array(
				'exclude_roles_endpoints' => isset( $data['settings']['exclude_roles_endpoints'] ) && is_array( $data['settings']['exclude_roles_endpoints'] )
					? array_values( array_intersect( $data['settings']['exclude_roles_endpoints'], $valid_roles ) )
					: array(),
			);

			$retention = isset( $data['settings']['log_retention_days'] ) ? (int) $data['settings']['log_retention_days'] : null;
			$new_settings['log_retention_days'] = in_array( $retention, array( 7, 14, 30, 60, 90 ), true ) ? $retention : 30;

			// Bypass the registered sanitize_option filter — it expects raw settings-form
			// input (e.g. an 'exclude_admins_endpoints' checkbox key), not this already-built shape.
			remove_filter( 'sanitize_option_wpbyem_rate_limit_settings', array( $this, 'sanitize_admin_bypass_settings' ) );
			update_option( 'wpbyem_rate_limit_settings', $new_settings );
		}

		$message = sprintf(
			/* translators: 1: number of endpoints imported, 2: number skipped */
			__( 'Import complete. %1$d blocked endpoint(s) imported, %2$d skipped (not found on this site).', 'wpbuoy-endpoint-manager' ),
			$imported_count,
			$skipped_count
		);
		$this->set_import_notice( $message, 'updated' );

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Store a one-time admin notice to display after an import redirect.
	 *
	 * @param string $message Notice text.
	 * @param string $type    'updated' or 'error'.
	 */
	private function set_import_notice( $message, $type ) {
		set_transient( 'wpbyem_import_notice_' . get_current_user_id(), array(
			'message' => $message,
			'type'    => $type,
		), MINUTE_IN_SECONDS );
	}

	/**
	 * Display the one-time import notice, if any.
	 */
	public function maybe_show_import_notice() {
		$key    = 'wpbyem_import_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! $notice ) {
			return;
		}
		delete_transient( $key );
		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			'error' === $notice['type'] ? 'notice-error' : 'notice-success',
			esc_html( $notice['message'] )
		);
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
	 * Sanitize admin bypass and log retention settings.
	 * Only touches exclude_roles_endpoints and log_retention_days — preserves any Pro keys already in the option.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_admin_bypass_settings( $input ) {
		$existing                            = get_option( 'wpbyem_rate_limit_settings', array() );
		$existing['exclude_roles_endpoints'] = ! empty( $input['exclude_admins_endpoints'] ) ? array( 'administrator' ) : array();
		unset( $existing['exclude_admins_endpoints'] );

		$retention_days                 = isset( $input['log_retention_days'] ) ? (int) $input['log_retention_days'] : 30;
		$existing['log_retention_days'] = in_array( $retention_days, array( 7, 14, 30, 60, 90 ), true ) ? $retention_days : 30;

		return $existing;
	}

	/**
	 * Check whether the current user holds any of the given excluded roles.
	 *
	 * wp_validate_auth_cookie() reads the cookie directly so browser-based REST
	 * requests without a nonce are still recognised as the logged-in user.
	 *
	 * @param array $excluded_roles Role slugs excluded from the rule being checked.
	 * @return bool
	 */
	private function user_has_bypass_role( $excluded_roles ) {
		if ( empty( $excluded_roles ) ) {
			return false;
		}
		$user_id = wp_validate_auth_cookie( '', 'logged_in' ) ?: get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}
		return (bool) array_intersect( $excluded_roles, (array) $user->roles );
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

			if ( '' === $endpoint || strlen( $endpoint ) > 500 ) {
				continue;
			}

			// Validate the pattern compiles as a regex rather than allowlisting characters —
			// REST route patterns can legitimately contain almost any printable character
			// (e.g. core's global-styles routes use %, @, and " inside character classes).
			$test_pattern = '#^' . $endpoint . '$#';
			$old_limit    = ini_get( 'pcre.backtrack_limit' );
			ini_set( 'pcre.backtrack_limit', '1000' ); // phpcs:ignore WordPress.PHP.IniSet.Risky
			$valid = @preg_match( $test_pattern, '' ) !== false;
			ini_set( 'pcre.backtrack_limit', $old_limit ); // phpcs:ignore WordPress.PHP.IniSet.Risky

			if ( $valid ) {
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

		// Selected roles bypass endpoint blocking entirely.
		$settings      = get_option( 'wpbyem_rate_limit_settings', array() );
		$exclude_roles = isset( $settings['exclude_roles_endpoints'] ) ? (array) $settings['exclude_roles_endpoints'] : array( 'administrator' );
		if ( $this->user_has_bypass_role( $exclude_roles ) ) {
			return $result;
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cleared = isset( $_GET['cleared'] ) && '1' === $_GET['cleared'];

		$filters = $this->get_logs_filters_from_request( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only filter values, no data modification
		$paged   = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$view_data     = $this->prepare_logs_view_data( $filters, $paged );
		$logs_page_url = admin_url( 'admin.php?page=wpbyem-logs' );

		wpbyem_get_plugin_part( 'admin/page', 'logs', array_merge(
			$view_data,
			compact( 'cleared', 'filters', 'logs_page_url' )
		) );
	}

	/**
	 * Compute everything the Logs table view needs — the filtered/paginated
	 * row set plus every summary figure and dropdown option list around it
	 * — from a normalized filters array and a page number. Shared by the
	 * initial page load (render_logs_page(), filters from $_GET) and the
	 * AJAX search handler (ajax_search_logs(), filters from $_POST), so the
	 * two request paths can never compute this differently.
	 *
	 * @param array $filters Normalized filter values, see get_logs_filters_from_request().
	 * @param int   $paged   Requested page number (clamped to the valid range internally).
	 * @return array {
	 *     @type array $logs               Log rows for the current page.
	 *     @type int   $total              Total rows, unfiltered.
	 *     @type int   $filtered_count     Total rows matching $filters.
	 *     @type int   $per_page
	 *     @type int   $paged              Clamped current page.
	 *     @type int   $total_pages
	 *     @type array $unique_ips         Dropdown options — derived from the current page's rows,
	 *                                     same scoping the old client-only-filtering version had.
	 *     @type array $unique_endpoints
	 *     @type array $hidden_columns
	 *     @type bool  $has_active_filters
	 * }
	 */
	private function prepare_logs_view_data( array $filters, $paged ) {
		$per_page     = (int) get_user_option( 'wpbyem_logs_per_page' );
		$per_page     = $per_page > 0 ? $per_page : 15;
		$where_clause = $this->build_logs_where_clause( $filters );

		$total          = $this->get_total_logs_count();
		$filtered_count = $this->get_total_logs_count( $where_clause );
		$total_pages    = max( 1, (int) ceil( $filtered_count / $per_page ) );
		$paged          = max( 1, min( $total_pages, absint( $paged ) ) );
		$offset         = ( $paged - 1 ) * $per_page;

		$logs = $this->get_security_logs( $where_clause, $per_page, $offset );

		$unique_ips       = array_values( array_unique( wp_list_pluck( $logs, 'ip_address' ) ) );
		$unique_endpoints = array_values( array_unique( wp_list_pluck( $logs, 'endpoint' ) ) );

		sort( $unique_ips );
		sort( $unique_endpoints );

		return array(
			'logs'             => $logs,
			'total'            => $total,
			'filtered_count'   => $filtered_count,
			'per_page'         => $per_page,
			'paged'            => $paged,
			'total_pages'      => $total_pages,
			'unique_ips'       => $unique_ips,
			'unique_endpoints' => $unique_endpoints,
			// A screen-id string, not get_current_screen() — this runs from
			// the AJAX handler too, where there's no "current screen" the
			// normal admin-page way; get_hidden_columns() accepts a string
			// and builds the WP_Screen itself either way.
			'hidden_columns'   => get_hidden_columns( 'endpoints_page_wpbyem-logs' ),
			// Picks the empty-state copy in logs-table-rows.php ("No entries
			// match your filters." vs. "No blocked requests logged yet.") —
			// derived from $filters itself so callers can't drift on what
			// "active" means.
			'has_active_filters' => ! empty( array_filter( $filters ) ),
		);
	}

	/**
	 * Normalize raw filter values from either $_GET (initial page load,
	 * bookmarked/shared URLs) or $_POST (AJAX search) into one shape, so
	 * build_logs_where_clause() and prepare_logs_view_data() don't care
	 * which request the values came from. 'all' (the select elements' own
	 * "no filter" sentinel) and '' are both treated as unset.
	 *
	 * @param array $source $_GET or $_POST.
	 * @return array Filters: search, ip, endpoint, date_from, date_to.
	 */
	private function get_logs_filters_from_request( array $source ) {
		$get = function ( $key ) use ( $source ) {
			if ( ! isset( $source[ $key ] ) ) {
				return '';
			}
			$value = sanitize_text_field( wp_unslash( $source[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter values, sourced from either a nonce-checked AJAX POST or a display-only GET link, no data modification either way
			return 'all' === $value ? '' : $value;
		};

		return array(
			'search'    => $get( 'filter_search' ),
			'ip'        => $get( 'filter_ip' ),
			'endpoint'  => $get( 'filter_endpoint' ),
			'date_from' => $get( 'filter_date_from' ),
			'date_to'   => $get( 'filter_date_to' ),
		);
	}

	/**
	 * Build a WHERE clause from a normalized filter-values array (see
	 * get_logs_filters_from_request()). Covers every filter the controls
	 * row exposes — search, IP, endpoint, date range.
	 *
	 * @param array $filters Normalized filter values.
	 * @return string SQL WHERE clause (empty string if no filters active).
	 */
	private function build_logs_where_clause( array $filters ) {
		global $wpdb;

		$where_parts = array();

		if ( ! empty( $filters['ip'] ) ) {
			$where_parts[] = $wpdb->prepare( 'ip_address = %s', $filters['ip'] );
		}

		if ( ! empty( $filters['endpoint'] ) ) {
			$where_parts[] = $wpdb->prepare( 'endpoint = %s', $filters['endpoint'] );
		}

		if ( ! empty( $filters['date_from'] ) && $this->is_valid_log_date( $filters['date_from'] ) ) {
			$where_parts[] = $wpdb->prepare( 'blocked_at >= %s', $filters['date_from'] . ' 00:00:00' );
		}

		if ( ! empty( $filters['date_to'] ) && $this->is_valid_log_date( $filters['date_to'] ) ) {
			$where_parts[] = $wpdb->prepare( 'blocked_at <= %s', $filters['date_to'] . ' 23:59:59' );
		}

		// Free-text search — OR'd across every column a keyword could
		// plausibly be hunting through, ANDed with the exact-match filters
		// above.
		if ( ! empty( $filters['search'] ) ) {
			$like          = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$where_parts[] = $wpdb->prepare(
				'(ip_address LIKE %s OR endpoint LIKE %s OR user_agent LIKE %s)',
				$like,
				$like,
				$like
			);
		}

		return ! empty( $where_parts ) ? 'WHERE ' . implode( ' AND ', $where_parts ) : '';
	}

	/**
	 * Validate a Y-m-d date string before it goes into a date-range filter
	 * — reject malformed input rather than pass it to blocked_at >= '...'
	 * and let MySQL's own (surprisingly permissive) date coercion decide
	 * what it means.
	 *
	 * @param string $date
	 * @return bool
	 */
	private function is_valid_log_date( $date ) {
		$parsed = \DateTime::createFromFormat( 'Y-m-d', $date );
		return $parsed && $parsed->format( 'Y-m-d' ) === $date;
	}

	/**
	 * Get a page of security log rows.
	 *
	 * @param string $where_clause SQL WHERE clause from build_logs_where_clause().
	 * @param int    $limit
	 * @param int    $offset
	 * @return array
	 */
	private function get_security_logs( $where_clause, $limit = 15, $offset = 0 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpbyem_logs';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is a prefixed table name, never user input; $where_clause is built via $wpdb->prepare()
		$sql = "SELECT * FROM $table_name $where_clause ORDER BY blocked_at DESC LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom table, $sql built safely above
		return $wpdb->get_results( $wpdb->prepare( $sql, $limit, $offset ) );
	}

	/**
	 * Get total log count, optionally scoped to a WHERE clause.
	 *
	 * @param string $where_clause SQL WHERE clause from build_logs_where_clause() (empty string for the true unfiltered total).
	 * @return int
	 */
	private function get_total_logs_count( $where_clause = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpbyem_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is a prefixed table name, never user input; $where_clause built via $wpdb->prepare()
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name $where_clause" );
	}

	/**
	 * Screen Options setup for the Logs page — registers Columns and appends
	 * a custom "Filters" + hand-built "Pagination" fieldset after it, mirroring
	 * core's own rendering order (Columns, then per-page, then anything else
	 * the 'screen_settings' filter adds). Registering per_page the normal way
	 * via add_screen_option() would put it between Columns and Filters with no
	 * way to reorder it, so render_logs_pagination_fieldset() builds an
	 * equivalent fieldset by hand instead. The save side is unaffected either
	 * way: set_screen_options() keys off the submitted `wp_screen_options[option]`
	 * field name matching the registered set_screen_option_{$option} filter
	 * (save_logs_per_page_option(), hooked in init_hooks()) — it doesn't care
	 * whether add_screen_option() was ever called. What add_screen_option()
	 * would normally do besides rendering is force the Apply button to show —
	 * replicated explicitly below.
	 */
	public function setup_logs_screen_options() {
		add_filter( 'screen_options_show_submit', '__return_true' );

		register_column_headers( get_current_screen(), array(
			'time'       => __( 'Time', 'wpbuoy-endpoint-manager' ),
			'ip_address' => __( 'IP Address', 'wpbuoy-endpoint-manager' ),
			'endpoint'   => __( 'Endpoint', 'wpbuoy-endpoint-manager' ),
			'status'     => __( 'Response Code', 'wpbuoy-endpoint-manager' ), // Column key stays 'status' — only the label changed — so no one's saved Screen Options break.
			'user_agent' => __( 'User Agent', 'wpbuoy-endpoint-manager' ),
			'actions'    => __( 'Actions', 'wpbuoy-endpoint-manager' ),
		) );

		// Custom "Filters" section alongside the native "Columns" one above —
		// only registered while this page is loading (via the load-{hook}
		// action), so it never appears on any other admin screen.
		add_filter( 'screen_settings', array( $this, 'render_logs_filter_screen_settings' ) );

		// Relabel the "Screen Options" button to "Columns & Filters" now that
		// it also controls filter visibility, not just columns. Core hardcodes
		// this string with no dedicated filter around it (_e('Screen Options')
		// in class-wp-screen.php) — gettext is the general translation-filter
		// mechanism that string still routes through, so it's the correct hook
		// even though it's not purpose-built for this button. Scoped to this
		// page's load only, so it doesn't relabel every other admin screen's
		// Screen Options button too.
		add_filter( 'gettext', array( $this, 'relabel_screen_options_button' ), 10, 3 );

		// Default User Agent to hidden — a drill-into-one-row detail, not
		// needed for a quick scan. Only applies until a user customizes their
		// own column visibility (get_hidden_columns()'s $use_defaults), so
		// this never overrides an existing preference.
		add_filter( 'default_hidden_columns', array( $this, 'default_hidden_logs_columns' ), 10, 2 );
	}

	/**
	 * default_hidden_columns callback — see setup_logs_screen_options().
	 *
	 * @param string[]   $hidden Array of column IDs hidden by default.
	 * @param \WP_Screen $screen WP_Screen object of the current screen.
	 * @return string[]
	 */
	public function default_hidden_logs_columns( $hidden, $screen ) {
		if ( $this->logs_hook === $screen->id ) {
			$hidden[] = 'user_agent';
		}

		return $hidden;
	}

	/**
	 * gettext callback — see setup_logs_screen_options() for why this exists.
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original (untranslated) text.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public function relabel_screen_options_button( $translation, $text, $domain ) {
		if ( 'default' === $domain && 'Screen Options' === $text ) {
			return __( 'Columns & Filters', 'wpbuoy-endpoint-manager' );
		}

		return $translation;
	}

	/**
	 * screen_settings filter callback — appends a "Filters" fieldset (one
	 * checkbox per filter key, toggling that filter's visibility in the
	 * controls row) plus the hand-built "Pagination" fieldset. Actual
	 * show/hide behavior driven by these checkboxes lives in filter-visibility.js.
	 *
	 * @param string $screen_settings Existing screen settings HTML.
	 * @return string
	 */
	public function render_logs_filter_screen_settings( $screen_settings ) {
		$filters = array(
			'ip'       => __( 'IP Address', 'wpbuoy-endpoint-manager' ),
			'endpoint' => __( 'Endpoint', 'wpbuoy-endpoint-manager' ),
			'date'     => __( 'Date Range', 'wpbuoy-endpoint-manager' ),
		);
		$default_visible = array( 'ip', 'endpoint' );

		return $screen_settings
			. $this->render_filter_visibility_fieldset( $filters, $default_visible )
			. $this->render_logs_pagination_fieldset();
	}

	/**
	 * Render a "Filters" fieldset for the Screen Options panel — one checkbox
	 * per filter key, toggling that filter's visibility in the controls row.
	 *
	 * @param array $filters         Map of filter key => label.
	 * @param array $default_visible Filter keys visible by default.
	 * @return string Fieldset HTML.
	 */
	private function render_filter_visibility_fieldset( array $filters, array $default_visible ) {
		ob_start();
		?>
		<fieldset class="metabox-prefs wpbyem-filter-visibility-prefs">
			<legend><?php esc_html_e( 'Filters', 'wpbuoy-endpoint-manager' ); ?></legend>
			<?php foreach ( $filters as $key => $label ) : ?>
				<label>
					<input type="checkbox"
					       class="wpbyem-filter-visibility-toggle"
					       data-filter-key="<?php echo esc_attr( $key ); ?>"
					       <?php checked( in_array( $key, $default_visible, true ) ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
		return ob_get_clean();
	}

	/**
	 * Hand-built equivalent of WP_Screen::render_per_page_options() — same
	 * markup, input names, and default-value logic, but called from here
	 * instead of via add_screen_option('per_page', ...) so it renders after
	 * "Filters" instead of before it. See setup_logs_screen_options() for why.
	 *
	 * @return string Fieldset HTML.
	 */
	private function render_logs_pagination_fieldset() {
		$option   = 'wpbyem_logs_per_page';
		$per_page = (int) get_user_option( $option );
		if ( $per_page < 1 ) {
			$per_page = 15;
		}

		ob_start();
		?>
		<fieldset class="screen-options">
			<legend><?php esc_html_e( 'Pagination', 'wpbuoy-endpoint-manager' ); ?></legend>
			<label for="<?php echo esc_attr( $option ); ?>"><?php esc_html_e( 'Logs per page', 'wpbuoy-endpoint-manager' ); ?></label>
			<input type="number" step="1" min="1" max="999" class="screen-per-page small-text"
			       name="wp_screen_options[value]"
			       id="<?php echo esc_attr( $option ); ?>"
			       value="<?php echo esc_attr( $per_page ); ?>" />
			<input type="hidden" name="wp_screen_options[option]" value="<?php echo esc_attr( $option ); ?>" />
		</fieldset>
		<?php
		return ob_get_clean();
	}

	/**
	 * Save the logs per-page screen option.
	 *
	 * @param mixed  $status Default status.
	 * @param string $option Option name.
	 * @param int    $value  New value.
	 * @return int
	 */
	public function save_logs_per_page_option( $status, $option, $value ) {
		return absint( $value );
	}

	/**
	 * AJAX handler — search/filter/paginate the Logs table without a full
	 * page reload. Reuses prepare_logs_view_data() — the exact same query and
	 * row-set computation the initial page load uses — and renders the same
	 * two partials (logs-table-rows.php, logs-summary-pagination.php) to HTML
	 * strings via wpbyem_return_plugin_part(), so the AJAX-swapped markup can
	 * never drift from what a fresh page load would have produced for the
	 * same filters.
	 */
	public function ajax_search_logs() {
		check_ajax_referer( 'wpbyem_logs_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'wpbuoy-endpoint-manager' ) ) );
		}

		$filters = $this->get_logs_filters_from_request( $_POST );
		$paged   = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

		$view_data     = $this->prepare_logs_view_data( $filters, $paged );
		$logs_page_url = admin_url( 'admin.php?page=wpbyem-logs' );

		$rows_html = wpbyem_return_plugin_part( 'admin/logs-table-rows', array_merge(
			$view_data,
			array( 'logs_page_url' => $logs_page_url )
		) );

		$summary_html = wpbyem_return_plugin_part( 'admin/logs-summary-pagination', array_merge(
			$view_data,
			array(
				'logs_page_url' => $logs_page_url,
				'filters'       => $filters,
			)
		) );

		wp_send_json_success( array(
			'rows_html'          => $rows_html,
			'summary_html'       => $summary_html,
			'filtered_count'     => $view_data['filtered_count'],
			'has_active_filters' => $view_data['has_active_filters'],
		) );
	}

	/**
	 * Register Screen Options for the Endpoints page — currently just the
	 * custom "Filters" visibility fieldset. See setup_logs_screen_options()
	 * for the sibling implementation on the Logs page; unlike that page,
	 * Endpoints has no columns or per-page option, so the fieldset is the
	 * only reason the Screen Options tab appears here at all.
	 */
	public function setup_endpoints_screen_options() {
		add_filter( 'screen_settings', array( $this, 'render_endpoints_filter_screen_settings' ) );

		// Relabel "Screen Options" to "Filters" — this page's panel holds
		// only the filter-visibility fieldset, no columns, so "Columns &
		// Filters" (the Logs page label) would be misleading here. Scoped
		// to this page's load only, via the load-{hook} action.
		add_filter( 'gettext', array( $this, 'relabel_endpoints_screen_options_button' ), 10, 3 );
	}

	/**
	 * gettext callback — see setup_endpoints_screen_options() for why this exists.
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original (untranslated) text.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public function relabel_endpoints_screen_options_button( $translation, $text, $domain ) {
		if ( 'default' === $domain && 'Screen Options' === $text ) {
			return __( 'Filters', 'wpbuoy-endpoint-manager' );
		}

		return $translation;
	}

	/**
	 * Add a "Filters" fieldset to the Screen Options panel on the Endpoints
	 * page, letting users pick which filters show in the controls row.
	 * Status/Type/Method/Namespace are visible by default (existing
	 * behavior, unchanged); Restricted starts hidden since it's a new
	 * addition. Like the Logs page equivalent, this is a per-browser
	 * localStorage preference (see filter-visibility.js), not persisted
	 * server-side.
	 *
	 * @param string $screen_settings Existing screen settings HTML.
	 * @return string
	 */
	public function render_endpoints_filter_screen_settings( $screen_settings ) {
		$filters = array(
			'status'     => __( 'Status', 'wpbuoy-endpoint-manager' ),
			'type'       => __( 'Type', 'wpbuoy-endpoint-manager' ),
			'method'     => __( 'Method', 'wpbuoy-endpoint-manager' ),
			'namespace'  => __( 'Namespace', 'wpbuoy-endpoint-manager' ),
			'restricted' => __( 'Restricted', 'wpbuoy-endpoint-manager' ),
		);
		$default_visible = array( 'status', 'type', 'method', 'namespace' );

		return $screen_settings . $this->render_filter_visibility_fieldset( $filters, $default_visible );
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
