<?php
/**
 * Feature Manager Class
 *
 * Handles feature configuration for REST API Manager (Free Version)
 *
 * @package RestApiManager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class REST_API_Manager_Feature_Manager {

	/**
	 * Instance of this class
	 */
	protected static $instance = null;

	/**
	 * Available features configuration
	 */
	private $features = array();

	/**
	 * Get instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init_features();
	}

	/**
	 * Initialize features for free version
	 */
	private function init_features() {
		// Free version features only
		$this->features = array(
			'static_endpoints' => array(
				'enabled'     => true,
				'label'       => __( 'Static Endpoints', 'rest-api-manager' ),
				'description' => __( 'Manage static REST API endpoints', 'rest-api-manager' ),
				'pro_only'    => false,
			),
			'wordpress_endpoints' => array(
				'enabled'     => true,
				'label'       => __( 'WordPress Core Endpoints', 'rest-api-manager' ),
				'description' => __( 'Manage WordPress core REST API endpoints', 'rest-api-manager' ),
				'pro_only'    => false,
			),
			// Pro features are disabled in free version
			'endpoint_filters' => array(
				'enabled'     => false,
				'label'       => __( 'Endpoint Filters', 'rest-api-manager' ),
				'description' => __( 'Filter endpoints by namespace, type, and status', 'rest-api-manager' ),
				'pro_only'    => true,
			),
			'endpoint_preview' => array(
				'enabled'     => false,
				'label'       => __( 'Endpoint Preview', 'rest-api-manager' ),
				'description' => __( 'Preview endpoints with sample data', 'rest-api-manager' ),
				'pro_only'    => true,
			),
			'endpoint_summary' => array(
				'enabled'     => false,
				'label'       => __( 'Endpoint Summary', 'rest-api-manager' ),
				'description' => __( 'Display endpoint statistics and summary', 'rest-api-manager' ),
				'pro_only'    => true,
			),
			'all_namespaces' => array(
				'enabled'     => false,
				'label'       => __( 'All Namespaces', 'rest-api-manager' ),
				'description' => __( 'Include all namespaces including plugin endpoints', 'rest-api-manager' ),
				'pro_only'    => true,
			),
			'dynamic_endpoints' => array(
				'enabled'     => false,
				'label'       => __( 'Dynamic Endpoints', 'rest-api-manager' ),
				'description' => __( 'Show and manage dynamic endpoints', 'rest-api-manager' ),
				'pro_only'    => true,
			),
			'security_logs' => array(
				'enabled'     => false,
				'label'       => __( 'Security Logs', 'rest-api-manager' ),
				'description' => __( 'Log blocked requests and security events', 'rest-api-manager' ),
				'pro_only'    => true,
			),
			'export_logs' => array(
				'enabled'     => false,
				'label'       => __( 'Export Logs', 'rest-api-manager' ),
				'description' => __( 'Export security logs to CSV', 'rest-api-manager' ),
				'pro_only'    => true,
			),
			'log_cleanup' => array(
				'enabled'     => false,
				'label'       => __( 'Log Cleanup', 'rest-api-manager' ),
				'description' => __( 'Automatically cleanup old logs', 'rest-api-manager' ),
				'pro_only'    => true,
			),
		);

		// Allow features to be modified via filter
		$this->features = apply_filters( 'rest_api_manager_features', $this->features );
	}

	/**
	 * Check if a feature is enabled
	 *
	 * @param string $feature_key Feature key
	 * @return bool True if feature is enabled
	 */
	public function is_enabled( $feature_key ) {
		if ( ! isset( $this->features[ $feature_key ] ) ) {
			return false;
		}

		// In free version, pro features are always disabled
		if ( $this->features[ $feature_key ]['pro_only'] ) {
			return false;
		}

		return (bool) $this->features[ $feature_key ]['enabled'];
	}

	/**
	 * Get all features
	 *
	 * @return array All features
	 */
	public function get_features() {
		return $this->features;
	}

	/**
	 * Get feature info
	 *
	 * @param string $feature_key Feature key
	 * @return array|false Feature info or false if not found
	 */
	public function get_feature( $feature_key ) {
		if ( ! isset( $this->features[ $feature_key ] ) ) {
			return false;
		}

		return $this->features[ $feature_key ];
	}

	/**
	 * Check if feature is pro-only
	 *
	 * @param string $feature_key Feature key
	 * @return bool True if feature is pro-only
	 */
	public function is_pro_feature( $feature_key ) {
		if ( ! isset( $this->features[ $feature_key ] ) ) {
			return false;
		}

		return (bool) $this->features[ $feature_key ]['pro_only'];
	}
}

// Initialize feature manager
REST_API_Manager_Feature_Manager::instance();
