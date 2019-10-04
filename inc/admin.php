<?php

// enqueue admin script
use MongoDB\Driver\Exception\ExecutionTimeoutException;

add_action( 'admin_enqueue_scripts', function( $hook ) {
	if ( $hook != 'posts_page_facebook-feed' ) return;
	wp_enqueue_style( 'fbfeed-settings', FBFEED_PLUGIN_URL . 'css/admin.css', array(), '20190926' );

	wp_enqueue_script( 'fbfeed-settings', FBFEED_PLUGIN_URL . 'js/admin.min.js', array(), '20190926', true );
	wp_add_inline_script( 'fbfeed-settings', "FBFEED_APP_ID = '" . FBFEED_APP_ID ."';
FBFEED_REDIRECT_URI = '" . apply_filters( 'fbfeed_redirect_uri', 'https://www.artcom-venture.de/wordpress-plugin-facebook-feed.php' ) ."';
FBFEED_MENU_PAGE_URL = '" . menu_page_url( 'facebook-feed', false ) . "';" );
} );

// register settings options
add_action( 'admin_init', function() {
	register_setting( 'fbfeed', 'fbfeed' );
	register_setting( 'fbfeed', 'fbfeed_bearer_token' );
	register_setting( 'fbfeed', 'fbfeed_in_posts' );
	register_setting( 'fbfeed', 'fbfeed_cache_lifetime' );
} );

// register settings page
add_action( 'admin_menu', function() {
	add_submenu_page(
		'edit.php',
		__( 'Facebook Feed', 'fbfeed' ),
		__( 'Facebook Feed', 'fbfeed' ),
		'manage_options',
		'facebook-feed',
		function() {
			$fb = fbfeed_get_sdk();

			$connecting = false;

			// redirect from Facebook with access token
		    if ( isset($_GET['access_token']) ) {
			    $connecting = true;

		        try {
			        // get token data
			        $token_data = $fb->get( add_query_arg( array( 'input_token' => $_GET['access_token'] ), '/debug_token' ), $_GET['access_token'] );
			        $token_data = $token_data->getDecodedBody()['data'];

			        // get long time access token
			        $access_token = $fb->get( add_query_arg( array(
				        'client_id' => FBFEED_APP_ID,
				        'client_secret' => FBFEED_APP_SECRET,
				        'grant_type' => 'fb_exchange_token',
				        'fb_exchange_token' => $_GET['access_token']
			        ), '/oauth/access_token' ), $_GET['access_token'] );
			        $access_token = $access_token->getDecodedBody()['access_token'];

			        update_option( 'fbfeed_bearer_token', $access_token );

			        // get selected pages
			        $pages = $fb->get( '/' . $token_data['user_id'] . '/accounts', $access_token );
			        foreach ( $pages = $pages->getDecodedBody()['data'] as $i => $page ) {
			            $pages[$i] = array(
			                'page_id' => $page['id'],
			                'access_token' => $page['access_token'],
                        );
                    }
                }
                catch( Exception $e ) {
	                $connecting = false;
		            $pages = array();
                }
            }
		    else $pages = array_filter(array(array_filter(fbfeed_settings()))); ?>

			<div class="wrap">
				<h1><?php _e( 'Connect to your Facebook Page', 'fbfeed' ); ?></h1>

                <p class="description">
                    <?php _e( '... and display the public content here in your WordPress page.', 'fbfeed' ); ?>
                </p>

                <div class="submit">
                    <button type="submit" name="fb-connect" id="fb-connect" class="button button-hero">
                        <span class="dashicons dashicons-facebook"></span>
                        <?php _e( 'Connect your Facebook Page', 'fbfeed' ); ?>
                    </button>
                    <span class="dashicons dashicons-info"></span>
                    <p class="description">
                        <?php _e( "Although it can be understood that way: <b>This doesn't give us permission to manage your Facebook pages!</b> It simply allows the plugin to see a list of your pages and retrieve an access token.", 'fbfeed' ); ?>
                    </p>
                </div>

                <form id="fbfeed-settings-form" method="post" action="options.php">
	                <?php settings_fields( 'fbfeed' ); ?>

                        <?php if ( $pages ) {
                            $current_page_id = fbfeed_setting( 'page_id' );
                            // current page id isn't in pages anymore
                            if ( !in_array( $current_page_id, array_column( $pages, 'page_id' ) ) ) $current_page_id = '';

                            if ( count($pages) > 1 ) {
                                echo '<p class="description">' . __( "Select the page you want to display the content of. If your page isn't in the list simply click on the <i>blue button</i> again.", 'fbfeed' ) . '</p>';
                            }

	                        foreach ( $pages as $i => $page ) {
		                        try {
			                        $info = $fb->get( add_query_arg( array( 'fields' => 'name,link,picture{url}' ), '/' . $page['page_id'] ), $page['access_token'] );
			                        $info = $info->getDecodedBody();

			                        echo '<input' . checked( $page['page_id'], $current_page_id ?: !$i ? $page['page_id'] : '', false ) . ' type="radio" name="fbfeed[page_id]" value="' . $page['page_id'] . '" id="fbfeed-page-' . $page['page_id'] . '" />';
			                        echo '<label class="vcard" for="fbfeed-page-' . $page['page_id'] . '">';
			                        echo '<span class="dashicons dashicons-yes"></span>';
			                        echo '<img src="' . $info['picture']['data']['url'] . '" />';
			                        echo '<div><h3>' . $info['name'] . '</h3><a href="' . $info['link'] . '" target="_blank">' . $info['link'] . '</a></div>';
			                        echo '<span class="dashicons dashicons-trash" title="' . __( 'Delete' ) . '"></span>';

			                        $debug = $fb->get( add_query_arg( array( 'input_token' => $page['access_token'] ), '/debug_token' ), $page['access_token'] );
			                        $debug = $debug->getDecodedBody();

			                        if ( $debug['data']['expires_at'] ) {
			                            echo '<span class="dashicons dashicons-info" title="' . sprintf( __( 'Access token only valid till %s', 'fbfeed' ), date_i18n( get_option( 'date_format' ), $debug['data']['expires_at'] ) ) . '"></span>';
			                        }

			                        echo '</label>';

			                        echo '<input type="hidden" name="fbfeed[access_token][' . $page['page_id'] . ']" value="' . $page['access_token'] . '" />';
		                        }
		                        catch ( Exception $e ) {
			                        echo '<div class="error inline"><p>'
			                             . sprintf( __( '<strong>ERROR</strong>: %s', 'fbfeed' ), $e->getMessage() )
			                             . ' <b>' . __( 'Try to re-connect your Facebook page by clicking on the <i>blue button</i>.', 'fbfeed' ) . '</b>'
			                             . '</p></div>';
		                        }
	                        }
                        } ?>

                    <?php if ( count($pages) ) : ?>

                        <table class="form-table">
                            <tbody>

                            <tr valign="top">
                                <th scope="row"><label for="fbfeed-cache-lifetime"><?php _e( 'Cache Lifetime', 'fbfeed' ); ?></label></th>
                                <td>
				                    <?php $default_cache_lifetime = apply_filters( 'wp_feed_cache_transient_lifetime', 12 * HOUR_IN_SECONDS, 'fbfeed' );
				                    $fbfeed_cache_lifetime = fbfeed_cache_lifetime();
				                    printf( __( '%s seconds' ), '<input type="number" class="small-text" name="fbfeed_cache_lifetime" placeholder="' . $default_cache_lifetime . '" value="' . (get_option( 'fbfeed_cache_lifetime') ?: '') . '" />' ); ?>
                                    <?php $span = array();
                                    if ( $days = floor($fbfeed_cache_lifetime/DAY_IN_SECONDS) ) {
	                                    $fbfeed_cache_lifetime -= $days * DAY_IN_SECONDS;
	                                    $span[] = sprintf( _n('%s day', '%s days', $days ), $days );
                                    } ?>

	                                <?php if ( $hours = floor($fbfeed_cache_lifetime/HOUR_IN_SECONDS) ) {
		                                $fbfeed_cache_lifetime -= $hours * HOUR_IN_SECONDS;
		                                $span[] = sprintf( _n('%s hour', '%s hours', $hours ), $hours );
	                                }

	                                if ( $minutes = floor($fbfeed_cache_lifetime/MINUTE_IN_SECONDS) ) {
		                                $fbfeed_cache_lifetime -= $minutes * MINUTE_IN_SECONDS;
		                                $span[] = sprintf( _n('%s minute', '%s minutes', $minutes ), $minutes );
	                                }

	                                if ( $seconds = $fbfeed_cache_lifetime ) {
		                                $span[] = sprintf( _n('%s second', '%s seconds', $seconds ), $seconds );
	                                }

	                                // format 'XX, XX, and XX'
	                                $last_span = array_pop($span);
	                                if ( $span ) $span = sprintf( __( '%s and %s', 'fbfeed' ), implode( ', ', $span ), $last_span );
	                                else $span = $last_span; ?>

                                    <p class="description">
                                        <?php printf(
						                    __( 'Only after the expiration of %s, a new request will be made to Facebook and (possibly) new content will be loaded.', 'fbfeed' ),
		                                    $span );
                                        printf( __( '... or %s', 'fbfeed' ),
		                                    '<button id="fbfeed-flush-cache" class="button button-small">' . __( 'flush the cache right now', 'fbfeed' ) . '</button><span class="dashicons dashicons-update"></span>'
	                                    ); ?>
                                    </p>
                                </td>
                            </tr>

                            </tbody>
                        </table>

                    <p class="submit">
	                    <?php submit_button( '', 'primary', 'submit', false );
	                    if ( $connecting )
	                        echo '&nbsp;&nbsp;<strong class="text-danger"><span class="dashicons dashicons-arrow-left-alt"></span> ' . __( "Don't forget to save!", 'fbfeed' ) . '</strong>'; ?>
                    </p>

                        <h2><?php _e( 'Display', 'fbfeed' ); ?></h2>

	                    <?php echo '<p hidden><label><input type="checkbox" name="fbfeed_in_posts" value="1"' . checked( 1, get_option( 'fbfeed_in_posts', 0 ) ) . ' /> ' .
	                               __( "Merge the Facebook posts into WordPress' news stream.", 'fbfeed' ) .  '</label></p>';

	                    $manual = 'To display the content of your connected Facebook page add the following shortcodes directly into the text box where it should be displayed. Use %s for posts and %s for events.';
	                    echo '<p>' . sprintf( __( $manual, 'fbfeed' ), '<code class="copy-me">[fbfeed]</code>', '<code class="copy-me">[fbevents]</code>' ) . '</p>'; ?>

	                <?php endif; ?>
                </form>
			</div>

		<?php }
	);
} );

add_filter( 'pre_update_option', function( $value, $option, $old_value ) {
    if ( $option == 'fbfeed' && isset($value['access_token']) && is_array($value['access_token']) ) {
	    $value['access_token'] = $value['access_token'][$value['page_id']];
    }

    return $value;
}, 10, 3 );

// flush all fb content transients
add_action( 'wp_ajax_fbfeed-flush-cache', 'fbfeed_flush_cache' );
function fbfeed_flush_cache() {
	global $wpdb;
	foreach ( $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_transient_fbevents-%' OR option_name LIKE '_transient_fbfeed-%'" ) as $transient ) {
		delete_transient( preg_replace( '/^_transient_/', '', $transient->option_name ) );
	}

	if ( wp_doing_ajax() ) wp_die();
}
