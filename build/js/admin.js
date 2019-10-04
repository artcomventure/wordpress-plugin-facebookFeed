( function( $ ) {

    $(document).on( 'ready', function() {
        $( '#fbfeed-settings-form' ).on( 'click', 'span.dashicons-trash', function() {
            var $vcard = $(this).closest( '.vcard' );
            $vcard.add( $vcard.prev() ).add( $vcard.next() ).remove();
        } ).find( 'input[name="_wp_http_referer"]' ).val( FBFEED_MENU_PAGE_URL );
    } );

    // connect facebook page(s)
    // (get each access token and page id)
    $( '#fb-connect' ).on( 'click', function() {
        // https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow?locale=de_DE
        window.open('https://www.facebook.com/dialog/oauth?' + $.param({
            client_id: FBFEED_APP_ID,
            redirect_uri: FBFEED_REDIRECT_URI,
            scope: 'manage_pages',
            state: encodeURIComponent(window.location),
            response_type: 'code token',
            display: 'popup'
        }), 'fbfeed', [
            'width=600',
            'height=680',
            'toolbar=no'
        ].join(','));
    } );

    // immediately flush fbfeed cache
    var fbfeed_flush_cache_icon_timeout;
    $( '#fbfeed-flush-cache' ).on( 'click', function( e ) {
        e.preventDefault();

        if ( fbfeed_flush_cache_icon_timeout )
            fbfeed_flush_cache_icon_timeout = clearTimeout( fbfeed_flush_cache_icon_timeout );

        var $button = $(this).addClass( 'flushing' ),
            $icon = $button.next().show();

        $.get( ajaxurl, {
            action: 'fbfeed-flush-cache'
        }, function() {
            $button.removeClass( 'flushing' );
            $icon.addClass( 'dashicons-yes' );

            fbfeed_flush_cache_icon_timeout = setTimeout( function() {
                $icon.fadeOut( function() {
                    $icon.removeClass( 'dashicons-yes' );
                } );
            }, 1000 )
        } );
    } );

    // select node for easy copy
    $( document ).on( 'click', '.copy-me', function () {
        var range = document.createRange();
        range.selectNodeContents( this );
        window.getSelection().addRange( range );
    } );

} )( jQuery );