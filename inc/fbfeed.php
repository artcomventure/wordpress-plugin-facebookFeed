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
		try {
			$fb = fbfeed_get_sdk();

			// get recent fb posts
			$posts = $fb->get( fbfeed_get_endpoint( 'posts', array( 'limit' => $atts['limit'] ) ) );
			$posts = $posts->getDecodedBody()['data'];
		}
		catch( Exception $e ) {
			$posts = array();
		}

		foreach ( $posts as &$post ) {
			$post_id = 0;

			// fb
			if ( isset( $post['created_time'] ) ) {
				$post = explode( '_', $post['id'] );
				$url = 'https://www.facebook.com/' . $post[0] . '/posts/' . $post[1];
			}
			// WP
			else {
				$post_id = $post['ID'];
				$url = get_permalink( $post_id );
			}

			$post = wp_oembed_get( $url, ( $atts = wp_parse_args( $atts, wp_embed_defaults( $url ) ) ) );
			$post = apply_filters( 'embed_oembed_html', $post, $url, $atts, $post_id );
		}

		set_transient( $transient_key, $posts = do_shortcode( implode( "\n", $posts ) ), fbfeed_cache_lifetime() );
	}

	return $posts;
}
