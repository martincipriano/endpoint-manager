<?php
/**
 * Template helpers.
 *
 * @package WpbuoyEndpointManager
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Load a plugin template part.
 *
 * Mirrors WordPress get_template_part() naming convention:
 *   wpbuoy_em_get_plugin_part( 'admin/page', 'main', $args )
 *   → partials/admin/page-main.php
 *
 * Also accepts the two-argument form when no name suffix is needed:
 *   wpbuoy_em_get_plugin_part( 'admin/sidebar', $args )
 *   → partials/admin/sidebar.php
 *
 * @param string       $slug Base template slug relative to /partials/ (without .php).
 * @param string|array $name Name suffix appended as "{slug}-{name}.php", or $args array when omitted.
 * @param array        $args Variables to extract into template scope.
 */
function wpbuoy_em_get_plugin_part( $slug, $name = '', $args = array() ) {
	if ( is_array( $name ) ) {
		$args = $name;
		$name = '';
	}
	$path = ltrim( $slug, '/' ) . ( $name !== '' ? '-' . $name : '' );
	$file = WPBUOY_ENDPOINT_MANAGER_PATH . 'partials/' . $path . '.php';
	if ( ! file_exists( $file ) ) {
		return;
	}
	if ( ! empty( $args ) ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $args, EXTR_SKIP );
	}
	include $file;
}

/**
 * Return a plugin template part as a string.
 *
 * @param string       $slug Base template slug relative to /partials/ (without .php).
 * @param string|array $name Name suffix or $args array when omitted.
 * @param array        $args Variables to extract into template scope.
 * @return string Rendered HTML.
 */
function wpbuoy_em_return_plugin_part( $slug, $name = '', $args = array() ) {
	ob_start();
	wpbuoy_em_get_plugin_part( $slug, $name, $args );
	return ob_get_clean();
}
