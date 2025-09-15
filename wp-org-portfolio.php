<?php
/**
 * Plugin Name:       WP.org Portfolio
 * Description:       Display all plugins and themes from a WordPress.org username
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:      	  iconick
 * Author URI:        https://iconick.io
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-org-portfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST API endpoints
 */
function wporg_author_showcase_register_rest_routes() {
	register_rest_route(
		'wporg-showcase/v1',
		'/author/(?P<username>[a-zA-Z0-9_-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'wporg_author_showcase_get_author_data',
			'permission_callback' => '__return_true',
			'args'                => array(
				'username' => array(
					'required' => true,
					'type'     => 'string',
				),
				'refresh' => array(
					'required' => false,
					'type'     => 'bool',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'wporg_author_showcase_register_rest_routes' );

/**
 * Robust logging helper.
 */
if ( ! function_exists('was_author_showcase_log')) {
	function was_author_showcase_log( $msg ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			@error_log( '[WP.org Showcase LOG] ' . print_r( $msg, true ) );
		}
	}
}

/**
 * Get author plugins and themes data, with intensive logging
 */
function wporg_author_showcase_get_author_data( WP_REST_Request $request ) {
	$username = sanitize_text_field( $request->get_param( 'username' ) );
	$username_lc = strtolower( $username );
	$refresh = $request->get_param( 'refresh' );
	if ( empty( $username_lc ) ) {
		return new WP_Error( 'no_username', 'Username is required', array( 'status' => 400 ) );
	}

	$cache_key = 'wporg_showcase_' . md5( $username_lc );
	if ( ! $refresh ) {
		$cached = get_transient( $cache_key );
		if ( $cached !== false && is_array($cached) && isset($cached['username']) && strtolower($cached['username']) === $username_lc ) {
			was_author_showcase_log( '[REST] Returning cached data for ' . $username_lc );
			return rest_ensure_response( $cached );
		}
	} else {
		delete_transient( $cache_key );
	}

	// Fetch plugins
	$plugins_url = add_query_arg( array(
		'action' => 'query_plugins',
		'author' => $username_lc,
		'per_page' => 50,
	), 'https://api.wordpress.org/plugins/info/1.2/' );
	// Fetch themes
	$themes_url = add_query_arg( array(
		'action' => 'query_themes',
		'author' => $username_lc,
		'per_page' => 50,
	), 'https://api.wordpress.org/themes/info/1.2/' );

	was_author_showcase_log( '[REST] Plugin API url: ' . $plugins_url );
	was_author_showcase_log( '[REST] Theme API url: ' . $themes_url );

	$plugins_response = wp_remote_get( $plugins_url, array( 'timeout' => 15 ) );
	$themes_response = wp_remote_get( $themes_url, array( 'timeout' => 15 ) );

	// LOG: Dump raw API response bodies and parsed JSON
	was_author_showcase_log( '[REST] raw plugin API body: ' . wp_remote_retrieve_body( $plugins_response ) );
	was_author_showcase_log( '[REST] raw theme API body: ' . wp_remote_retrieve_body( $themes_response ) );

	$data = array(
		'plugins' => array(),
		'themes'  => array(),
		'username' => $username_lc,
	);

	// Process plugins response
	if ( ! is_wp_error( $plugins_response ) && wp_remote_retrieve_response_code( $plugins_response ) === 200 ) {
		$plugins_body = wp_remote_retrieve_body( $plugins_response );
		$plugins_data = json_decode( $plugins_body, true );
		was_author_showcase_log( '[REST] plugin API parsed json: ' . print_r( $plugins_data, true ) );
		if ( isset( $plugins_data['plugins'] ) && is_array($plugins_data['plugins']) ) {
			$data['plugins'] = $plugins_data['plugins'];
		}
	}

	// Process themes response
	if ( ! is_wp_error( $themes_response ) && wp_remote_retrieve_response_code( $themes_response ) === 200 ) {
		$themes_body = wp_remote_retrieve_body( $themes_response );
		$themes_data = json_decode( $themes_body, true );
		was_author_showcase_log( '[REST] theme API parsed json: ' . print_r( $themes_data, true ) );
		if ( isset( $themes_data['themes'] ) && is_array($themes_data['themes']) ) {
			$data['themes'] = $themes_data['themes'];
		}
	}

	// If absolutely nothing is found, return empty arrays
	if ( empty($data['plugins']) && empty($data['themes']) ) {
		$data['plugins'] = array();
		$data['themes'] = array();
	}
	$data['username'] = $username_lc;

	was_author_showcase_log( '[REST] final REST return for ' . $username_lc . ': ' . print_r( $data, true ) );

	// Cache for 12 hours ONLY if not forced refresh
	if ( ! $refresh ) {
		set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
	}

	return rest_ensure_response( $data );
}

/**
 * Registers the block (with render-callback from render.php)
 */
function w0_wp_org_portfolio_block_init() {
	$render_path = __DIR__ . '/build/render.php';
	if ( file_exists( $render_path ) ) {
		require_once $render_path;
	}
	register_block_type( __DIR__ . '/build', array(
		'render_callback' => 'wporg_author_showcase_render_block',
	));
}
add_action( 'init', 'w0_wp_org_portfolio_block_init' );