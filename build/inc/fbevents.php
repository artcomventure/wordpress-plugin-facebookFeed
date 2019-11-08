<?php

/**
 * [fbevents] shortcode.
 */
add_shortcode( 'fbevents', 'fbevents_shortcode' );
function fbevents_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'limit' => -1,
		'height' => '' // max-height auto
	), $atts, 'fbevents' );

	$transient_key = 'fbevents-' . md5(get_option( 'fbfeed_page_id' ) . serialize($atts));

	// get cached data
	if ( !$events = get_transient( $transient_key ) ) {
		$events = array();

		try {
			$fb = fbfeed_get_sdk();

			// get `$atts['limit']` events
			// https://developers.facebook.com/docs/graph-api/reference/event
			$request = array(
				'paging' => array(
					'cursors' => array( 'after' => '' ),
					'next' => ''
				)
			);

			while ( isset( $request['paging']['next'] ) && ($atts['limit'] < 0  || count($events) < $atts['limit'] ) ) {
				$request = $fb->get( fbfeed_get_endpoint( 'events', array_filter( array(
					'fields' => 'id,name,cover,place,start_time,ticket_uri,attending_count,interested_count,maybe_count',
					'since'  => current_time( 'timestamp', true ),
					'after'  => $request['paging']['cursors']['after']
				) ) ) );

				foreach ( ($request = $request->getDecodedBody())['data'] as $event ) {
					$events[] = $event;

					if ( $atts['limit'] > -1 && count($events) >= $atts['limit'] ) break;
				}
			}

			// sort by start time
			usort( $events, function ( $a, $b ) {
				return $a['start_time'] > $b['start_time'];
			} );

			set_transient( $transient_key, $events, fbfeed_cache_lifetime() );
		} catch ( Exception $e ) {}
	}

	// locate template
	$template = 'fbevent.php';
	if ( file_exists( STYLESHEETPATH . '/template-parts/' . $template ) ) $template = STYLESHEETPATH . '/template-parts/' . $template;
	elseif ( file_exists( STYLESHEETPATH . '/' . $template ) ) $template = STYLESHEETPATH . '/' . $template;
	elseif ( file_exists( TEMPLATEPATH . '/template-parts/' . $template ) ) $template = TEMPLATEPATH . '/template-parts/' . $template;
	elseif ( file_exists( TEMPLATEPATH . '/' . $template ) ) $template = TEMPLATEPATH . '/' . $template;
	else $template = FBFEED_PLUGIN_DIR . 'template-parts/' . $template;

	foreach ( $events as $nb => &$event ) {
		ob_start();
		require( $template );
		$event = ob_get_contents();
		ob_end_clean();
	}

	return '<div class="fbevents"' . (is_numeric($atts['height']) ? ' style="max-height: ' . $atts['height'] . 'px;"' : '') . '>' . implode( "\n", $events ) . '</div>';
}
