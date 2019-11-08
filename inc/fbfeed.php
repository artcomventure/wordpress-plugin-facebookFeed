<?php

/**
 * [fbfeed] shortcode.
 */
add_shortcode( 'fbfeed', 'fbfeed_shortcode' );
function fbfeed_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'limit' => get_option( 'posts_per_page' )
	), $atts, 'fbfeed' );

	$transient_key = 'fbfeed-' . md5(get_option( 'fbfeed_page_id' ) . serialize($atts));

	if ( !$posts = get_transient( $transient_key ) ) {
		// locate template
		$template = 'content-fbpost.php';
		if ( file_exists( STYLESHEETPATH . '/template-parts/' . $template ) ) $template = STYLESHEETPATH . '/template-parts/' . $template;
		elseif ( file_exists( STYLESHEETPATH . '/' . $template ) ) $template = STYLESHEETPATH . '/' . $template;
		elseif ( file_exists( TEMPLATEPATH . '/template-parts/' . $template ) ) $template = TEMPLATEPATH . '/template-parts/' . $template;
		elseif ( file_exists( TEMPLATEPATH . '/' . $template ) ) $template = TEMPLATEPATH . '/' . $template;
		elseif ( !file_exists( $template = FBFEED_PLUGIN_DIR . 'template-parts/' . $template ) ) $template = false;

		try {
			$fb = fbfeed_get_sdk();

			// get recent fb posts
			$query = array( 'limit' => $atts['limit'] );
			if ( $template ) $query += array( 'fields' => 'permalink_url,message,created_time,full_picture,from{name,link}' );
			$posts = $fb->get( fbfeed_get_endpoint( 'posts', $query ) );
			$posts = $posts->getDecodedBody()['data'];
		}
		catch( Exception $e ) {
			$posts = array();
		}

		foreach ( $posts as &$post ) {
			$post_id = 0;

			if ( $template ) {
				ob_start();
				require( $template );
				$post = ob_get_contents();
				ob_end_clean();
			}
			else { // oEmbed
				$post = explode( '_', $post['id'] );
				$url = 'https://www.facebook.com/' . $post[0] . '/posts/' . $post[1];

				$post = wp_oembed_get( $url, ( $atts = wp_parse_args( $atts, wp_embed_defaults( $url ) ) ) );
				$post = apply_filters( 'embed_oembed_html', $post, $url, $atts, $post_id );
			}
		}

		set_transient( $transient_key, $posts = trim(do_shortcode( implode( "\n", $posts ) )), fbfeed_cache_lifetime() );
	}

	if ( $posts ) $posts = '<div class="fbfeed">' . $posts . '</div>';
	return $posts;
}
