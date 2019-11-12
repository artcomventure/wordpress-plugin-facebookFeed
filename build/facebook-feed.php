<?php

/**
 * Plugin Name: Facebook Feed
 * Description: Auto-embed your (public) Facebook posts.
 * Version: 1.3.2
 * Text Domain: fbfeed
 * Author: artcom venture GmbH
 * Author URI: http://www.artcom-venture.de/
 */

if ( ! defined( 'FBFEED_PLUGIN_FILE' ) ) define( 'FBFEED_PLUGIN_FILE', __FILE__ );
if ( ! defined( 'FBFEED_PLUGIN_URL' ) ) define( 'FBFEED_PLUGIN_URL', plugin_dir_url( FBFEED_PLUGIN_FILE ) );
if ( ! defined( 'FBFEED_PLUGIN_DIR' ) ) define( 'FBFEED_PLUGIN_DIR', plugin_dir_path( FBFEED_PLUGIN_FILE ) );
if ( ! defined( 'FBFEED_PLUGIN_BASENAME' ) ) define( 'FBFEED_PLUGIN_BASENAME', plugin_basename( FBFEED_PLUGIN_FILE ) );

if ( ! defined( 'FBFEED_APP_ID' ) ) define( 'FBFEED_APP_ID', '147188245874270' );
if ( ! defined( 'FBFEED_APP_SECRET' ) ) define( 'FBFEED_APP_SECRET', '6b0b180fd2b499769ff203af2e280bcf' );
if ( ! defined( 'FB_GRAPH_VERSION' ) ) define( 'FB_GRAPH_VERSION', 'v4.0' );

// i18n
add_action( 'after_setup_theme', function() {
	load_theme_textdomain( 'fbfeed', FBFEED_PLUGIN_DIR . 'languages' );
} );

// get Facebook's SDK
function fbfeed_get_sdk() {
	require_once( FBFEED_PLUGIN_DIR . 'facebook-php-graph-sdk/autoload.php' );

	$fb = new Facebook\Facebook( array(
		'app_id' => FBFEED_APP_ID,
		'app_secret' => FBFEED_APP_SECRET,
		'default_graph_version' => FB_GRAPH_VERSION,
	) );

	if ( $access_token = get_option( 'fbfeed_access_token' ) )
		$fb->setDefaultAccessToken( $access_token );

	return $fb;
}

function fbfeed_get_endpoint( $endpoint, $query = array() ) {
	return add_query_arg( $query, '/' . get_option( 'fbfeed_page_id' ) . "/{$endpoint}" );
}

function fbfeed_cache_lifetime() {
	return get_option( 'fbfeed_cache_lifetime') ?: apply_filters( 'wp_feed_cache_transient_lifetime', 12 * HOUR_IN_SECONDS, 'fbfeed' );
}

function fbfeed_version() {
	if ( !function_exists('get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return get_plugin_data( FBFEED_PLUGIN_FILE )['Version'];
}

// enqueue scripts and styles
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'fbevents', FBFEED_PLUGIN_URL . '/css/fbevents.min.css', array(), fbfeed_version() );
} );

// auto-include first level /inc/ files
if ( $inc = opendir( $path = FBFEED_PLUGIN_DIR . 'inc' ) ) {
	while ( ($file = readdir( $inc )) !== false ) {
		if ( !preg_match( '/\.php$/', $file ) ) continue;
		require $path . '/' . $file;
	}

	closedir( $inc );
}

// remove update notification (since this plugin isn't listed on https://wordpress.org/plugins/)
add_filter( 'site_transient_update_plugins', function ( $value ) {
	if ( isset( $value->response[ FBFEED_PLUGIN_FILE ] ) ) {
		unset( $value->response[ FBFEED_PLUGIN_FILE ] );
	}

	return $value;
} );

// plugin uninstallation
//register_deactivation_hook( FBFEED_PLUGIN_FILE, 'fbfeed_on_uninstall' );
register_uninstall_hook( FBFEED_PLUGIN_FILE, 'fbfeed_on_uninstall' );
function fbfeed_on_uninstall() {
	delete_option( 'fbfeed_page_id' );
	delete_option( 'fbfeed_access_token' );
	delete_option( 'fbfeed_in_posts' );
	delete_option( 'fbfeed_cache_lifetime' );
	fbfeed_flush_cache();
}