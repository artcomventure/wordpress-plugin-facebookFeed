<?php

// This is the OAuth redirect file (redirect_uri) for a manually build Facebook login flow
// @see https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/

// This file should be places on a _https server_ and whitelisted in Facebook's app login settings.

?><!DOCTYPE html>
<html lang="de">
<head>
	<title>WordPress Plugin Facebook Feed OAuth Redirect</title>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">

	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style type="text/css">
        #toast { opacity: 1; }
        #toast .spinner-border, #toast svg { width: 1em; height: 1em; }
        #toast .spinner-border { border-width: 2px; }
        #toast .spinner-border + svg { display: none; }
    </style>
</head>

<body>

<div aria-live="polite" aria-atomic="true" class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <svg class="rounded-circle" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img">
                <rect width="100%" height="100%" fill="#d9534f"></rect>
            </svg>
            <strong class="mr-auto ml-2">WP Plugin Facebook Feed OAuth Redirect</strong>
        </div>
        <div class="toast-body">
            <small class="d-block">You'll be redirected to your WordPress page ...</small>
            <small class="d-block mt-1">
                For more information see <a href="https://github.com/artcomventure/wordpress-plugin-facebookFeed"><i>us</i> on Github</a>.
            </small>
        </div>
    </div>
</div>

<script type="text/javascript">
	( function() {
        var parameters = {};
        location.hash.slice(1).split( '&' ).forEach( function( parameter ) {
            parameter = parameter.split( '=' );
            parameters[parameter[0]] = parameter[1];
        } );

        var url;
        if ( !!parameters['state'] ) url = decodeURIComponent(decodeURIComponent(parameters['state']));

        if ( url && !!parameters['access_token'] ) {
            window.opener.location.href = url + (/\?/.test( url ) ? '&' : '?') + 'access_token=' + parameters['access_token'];
            return self.close();
        }

		var $toast = document.getElementById( 'toast' ),
            $spinner = $toast.querySelector( 'div.spinner-border' ),
            $text = $toast.querySelector( 'small' );

        if ( $spinner ) $spinner.parentNode.removeChild( $spinner );

        if ( $text ) {
            var missing = [url ? '' : 'Redirect URL', !parameters['access_token'] ? 'Access Token' : ''];

            $text.className += ' text-danger';
            $text.innerHTML = '<strong>ERROR:</strong> ' + missing.join( ' and ' ) + ' ' + (missing.length > 1 ? 'are' : 'is') +  ' missing :/';
        }
	} )();
</script>

</body>

</html>