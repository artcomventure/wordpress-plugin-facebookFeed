<?php

// enqueue admin styles and script
add_action( 'admin_enqueue_scripts', function( $hook ) {
	if ( $hook != 'posts_page_facebook-feed' ) return;

	wp_enqueue_style( 'fbfeed-settings', FBFEED_PLUGIN_URL . 'css/admin.css', array(), get_plugin_data( FBFEED_PLUGIN_FILE )['Version'] );

	wp_enqueue_script( 'fbfeed-settings', FBFEED_PLUGIN_URL . 'js/admin.min.js', array(), get_plugin_data( FBFEED_PLUGIN_FILE )['Version'], true );
	wp_add_inline_script( 'fbfeed-settings', "FBFEED_APP_ID = '" . FBFEED_APP_ID ."';
FBFEED_REDIRECT_URI = '" . apply_filters( 'fbfeed_redirect_uri', 'https://www.artcom-venture.de/wordpress-plugin-facebook-feed.php' ) ."';
FBFEED_MENU_PAGE_URL = '" . menu_page_url( 'facebook-feed', false ) . "';" );
} );

// register options
add_action( 'admin_init', function() {
	register_setting( 'fbfeed', 'fbfeed_page_id' );
	register_setting( 'fbfeed', 'fbfeed_access_token' );
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

			// redirect from Facebook with access token
		    if ( $connecting = isset($_GET['access_token']) ) {
		        try {
			        // get long time access token
			        $access_token = $fb->get( add_query_arg( array(
				        'client_id' => FBFEED_APP_ID,
				        'client_secret' => FBFEED_APP_SECRET,
				        'grant_type' => 'fb_exchange_token',
				        'fb_exchange_token' => $_GET['access_token']
			        ), '/oauth/access_token' ), $_GET['access_token'] );

			        update_option( 'fbfeed_access_token', $access_token = $access_token->getDecodedBody()['access_token'] );
			        // set/re-new just requested access token
			        $fb->setDefaultAccessToken( $access_token );

			        // get users app-connected pages
			        $pages = $fb->get( '/me/accounts' );
			        $pages = array_column( $pages->getDecodedBody()['data'], 'id' );
                }
                catch( Exception $e ) {}
            } ?>

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
                        <?php _e( "Although it can be understood that way: <b>This doesn't give us permission to manage your Facebook pages!</b> You simply allow <i>us</i> to see a list of your pages and retrieve an access token.", 'fbfeed' ); ?>
                    </p>
                </div>

                <form id="fbfeed-settings-form" method="post" action="options.php">
	                <?php settings_fields( 'fbfeed' );

	                // no pages => not connecting
                    // ... so maybe get current Facebook page ID
                    if ( empty($pages) && $page_id = get_option( 'fbfeed_page_id' ) ) $pages = array( $page_id );

	                if ( $pages ) {
		                if ( count($pages) > 1 ) {
			                echo '<p class="description">' . __( "Select the page you want to display the content of. If your page isn't in the list simply click on the <i>blue button</i> again.", 'fbfeed' ) . '</p>';
		                }

		                foreach ( $pages as $i => $page_id ) {
			                try {
			                    // get page data
				                $page_data = $fb->get( add_query_arg( array( 'fields' => 'name,link,picture{url}' ), "/{$page_id}" ) );
				                $page_data = $page_data->getDecodedBody(); ?>

                                <input<?php checked( $page_id, get_option( 'fbfeed_page_id', $i ?: $page_id ) ) ?>
                                    type="radio" name="fbfeed_page_id" value="<?php echo $page_id ?>" id="fbfeed-page-<?php echo $page_id ?>" />

				                <label class="vcard" for="fbfeed-page-<?php echo $page_id ?>">

                                    <span class="dashicons dashicons-yes"></span>
                                    <img src="<?php echo $page_data['picture']['data']['url'] ?>" />

                                    <div class="page-info">
                                        <h3><?php echo $page_data['name'] ?></h3>
                                        <a href="<?php echo $page_data['link'] ?>" target="_blank"><?php echo $page_data['link'] ?></a>
                                    </div>

                                    <div class="page-edit">
                                        <b><?php _e( 'Facebook Page ID or Name', 'fbfeed' ) ?></b>
                                        <input type="text" value="" class="regular-text" name="fbfeed_page_id_override" placeholder="<?php echo $page_id ?>" />
                                        <span class="dashicons dashicons-no-alt" title="<?php _e( 'Cancel' ) ?>"></span>
                                    </div>

                                    <?php if ( !$connecting ) : ?>
                                    <div class="actions">
                                        <span class="dashicons dashicons-edit" title="<?php _e( 'Edit' ) ?>"></span>
                                        <span class="dashicons dashicons-trash" title="<?php _e( 'Delete' ) ?>"></span>
                                    </div>
                                    <?php endif; ?>

				                </label>

                            <?php if ( !$connecting ) {
                                    echo '<p class="description">' . __( 'You can display posts from <b>any</b> public Facebook page. Simply click on the edit icon on the right and enter your desired page.', 'fbfeed' ) . '</p>';
                                }
			                }
			                catch ( Exception $e ) {
				                echo '<div class="error inline"><p>'
				                     . sprintf( __( '<strong>ERROR</strong>: %s', 'fbfeed' ), $e->getMessage() )
				                     . ' <b>' . __( 'Try to re-connect your Facebook page by clicking on the <i>blue button</i>.', 'fbfeed' ) . '</b>'
				                     . '</p></div>';
			                }
		                } ?>

                        <table class="form-table">
                            <tbody>

                            <tr hidden valign="top">
                                <th scope="row"><label for="fbfeed-access-token"><?php _e( 'Access Token', 'fbfeed' ); ?></label></th>
                                <td><textarea readonly="readonly" id="fbfeed-access-token" name="fbfeed_access_token" cols="50" rows="4"><?php
                                    echo get_option( 'fbfeed_access_token' );
                                ?></textarea></td>
                            </tr>

                            <tr valign="top">
                                <th scope="row"><label for="fbfeed-cache-lifetime"><?php _e( 'Cache Lifetime', 'fbfeed' ); ?></label></th>
                                <td>
					                <?php $default_cache_lifetime = apply_filters( 'wp_feed_cache_transient_lifetime', 12 * HOUR_IN_SECONDS, 'fbfeed' );
					                $fbfeed_cache_lifetime = fbfeed_cache_lifetime();
					                printf( __( '%s seconds' ), '<input id="fbfeed-cache-lifetime" type="number" class="small-text" name="fbfeed_cache_lifetime" placeholder="' . $default_cache_lifetime . '" value="' . get_option( 'fbfeed_cache_lifetime', '') . '" />' ); ?>
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
			                echo '&nbsp;&nbsp;<strong id="save-note"' . (!$connecting ? ' hidden' : '') . ' class="text-danger"><span class="dashicons dashicons-arrow-left-alt"></span> ' . __( "Don't forget to save!", 'fbfeed' ) . '</strong>'; ?>
                        </p>

                        <h2><?php _e( 'Display', 'fbfeed' ); ?></h2>

		                <?php echo '<p hidden><label><input type="checkbox" name="fbfeed_in_posts" value="1"' . checked( 1, get_option( 'fbfeed_in_posts', 0 ) ) . ' /> ' .
		                           __( "Merge the Facebook posts into WordPress' news stream.", 'fbfeed' ) .  '</label></p>';

		                $manual = 'To display the content of your connected Facebook page add the following shortcodes directly into the text box where it should be displayed. Use %s for posts and %s for events.';
		                echo '<p>' . sprintf( __( $manual, 'fbfeed' ), '<code class="copy-me">[fbfeed]</code>', '<code class="copy-me">[fbevents]</code>' ) . '</p>'; ?>

	                <?php } ?>
                </form>
			</div>

		<?php }
	);
} );

add_filter( 'pre_update_option', function( $value, $option, $old_value ) {
    if ( $option == 'fbfeed_page_id' && !empty($_POST['fbfeed_page_id_override']) ) {
	    $value = $_POST['fbfeed_page_id_override'];
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
