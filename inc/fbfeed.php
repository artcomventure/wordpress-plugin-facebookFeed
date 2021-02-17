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
			$query = array( 'limit' => $atts['limit'], 'fields' => 'permalink_url' );
			if ( $template ) $query['fields'] .= ($query['fields'] ? ',' : '') . 'message,created_time,full_picture,from{name,link}';
			// https://developers.facebook.com/docs/graph-api/reference/v9.0/page/feed
			$posts = $fb->get( fbfeed_get_endpoint( 'posts', $query ) );
			$posts = $posts->getDecodedBody()['data'];
		}
		catch( Exception $e ) {
			$posts = array();
		}

		foreach ( $posts as &$post ) {
			if ( $template ) {
				ob_start();
				require( $template );
				$post = ob_get_contents();
				ob_end_clean();
			}
			else { // oEmbed
				try {
					// https://developers.facebook.com/docs/plugins/oembed
					$post = $fb->get( add_query_arg( array(
						'url' => $post['permalink_url']
					), '/oembed_post' ) );
					$post = $post->getDecodedBody()['html'];
				}
				catch( Exception $e ) {
					$post = null;
				}
			}
		}

		set_transient( $transient_key, $posts = trim(do_shortcode( implode( "\n", $posts ) )), fbfeed_cache_lifetime() );
	}

	if ( $posts ) $posts = '<div class="fbfeed">' . $posts . '</div>';
	return $posts;
}

// change oembed fb posts' fixed width to auto (responsive)
add_filter( 'embed_oembed_html', 'fbfeed_responsive_post', 10, 4 );
function fbfeed_responsive_post( $html, $url, $attr, $post_id ) {
	if ( preg_match( '#^https?://www\.facebook\.com/#i', $url ) ) {
		$html = preg_replace( '/ data-width="\d+"/', ' data-width="auto"', $html );
	}

	return $html;
}
