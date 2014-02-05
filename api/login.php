<?php

$oauthObject = new OAuthSimple();

// As this is an example, I am not doing any error checking to keep 
// things simple.  Initialize the output in case we get stuck in
// the first step.
$output = 'Authorizing...';

// Fill in your API key/consumer key you received when you registered your 
$signatures = array( 
	'consumer_key'     => $config['car2go_consumer_key'],
	'shared_secret'    => $config['car2go_consumer_secret']
);

// In step 3, a verifier will be submitted.  If it's not there, we must be
// just starting out. Let's do step 1 then.
if (!isset($_GET['oauth_verifier'])) {
    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    // Step 1: Get a Request Token
    //
    // Get a temporary request token to facilitate the user authorization 
    // in step 2. We make a request to the OAuthGetRequestToken endpoint.
    //
    $result = $oauthObject->sign(array(
        'path'      => $config['car2go_oauth_endpoint_root'].'reqtoken',
        'parameters'=> array(
            'oauth_callback'=> 'oac'),
        'signatures'=> $signatures));

    // The above object generates a simple URL that includes a signature, the 
    // needed parameters, and the web page that will handle our request.  I now
    // "load" that web page into a string variable.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
    $r = curl_exec($ch);
    curl_close($ch);

    // We parse the string for the request token and the matching token
    // secret. Again, I'm not handling any errors and just plough ahead 
    // assuming everything is hunky dory.
    parse_str($r, $returned_items);
    $request_token = $returned_items['oauth_token'];
    $request_token_secret = $returned_items['oauth_token_secret'];

    // We will need the request token and secret after the authorization.
    // Google will forward the request token, but not the secret.
    // Set a cookie, so the secret will be available once we return to this page.
    setcookie("oauth_token_secret", $request_token_secret, time()+3600);
    //
    //////////////////////////////////////////////////////////////////////
    
    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    // Step 2: Authorize the Request Token
    //
    // Generate a URL for an authorization request, then redirect to that URL
    // so the user can authorize our access request.  The user could also deny
    // the request, so don't forget to add something to handle that case.
    $result = $oauthObject->sign(array(
        'path'      => $config['car2go_oauth_endpoint_root'].'authorize',
        'parameters'=> array(
            'oauth_token' => $request_token),
        'signatures'=> $signatures));

    // See you in a sec in step 3.
    header("Location:$result[signed_url]");
    exit;
    //////////////////////////////////////////////////////////////////////
}
else {
    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    // Step 3: Exchange the Authorized Request Token for a Long-Term
    //         Access Token.
    //
    // We just returned from the user authorization process on Car2Go's site.
    // The token returned is the same request token we got in step 1.  To 
    // sign this exchange request, we also need the request token secret that
    // we baked into a cookie earlier.
    //

    // Fetch the cookie and amend our signature array with the request
    // token and secret.
    $signatures['oauth_secret'] = $_COOKIE['oauth_token_secret'];
    $signatures['oauth_token'] = $_GET['oauth_token'];
    
    // Build the request-URL...
    $result = $oauthObject->sign(array(
        'path'      => $config['car2go_oauth_endpoint_root'].'accesstoken',
        'parameters'=> array(
            'oauth_verifier' => $_GET['oauth_verifier'],
            'oauth_token'    => $_GET['oauth_token']),
        'signatures'=> $signatures));

    // ... and grab the resulting string again. 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
    $r = curl_exec($ch);
    curl_close($ch);

    // Voila, we've got a long-term access token.
    parse_str($r, $returned_items);        
    $access_token = $returned_items['oauth_token'];
    $access_token_secret = $returned_items['oauth_token_secret'];
    
    $signatures['oauth_token'] = $access_token;
    $signatures['oauth_secret'] = $access_token_secret;
    fSession::set('oauth_signatures[oauth_token]', $signatures['oauth_token']);
    fSession::set('oauth_signatures[oauth_secret]', $signatures['oauth_secret']);
    //////////////////////////////////////////////////////////////////////
    
    // Get and store user's Car2Go account info
    $oauthObject->reset();
    $result2 = $oauthObject->sign(array(
        'path'      => $config['car2go_api_root'].'accounts',
        'parameters'=> array('loc' => 'washingtondc', 'format' => 'json'),
        'signatures'=> $signatures));

    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch2, CURLOPT_URL, $result2['signed_url']);
    $r2 = curl_exec($ch2);

    $userAccounts = fJSON::decode($r2);

    // Set user as logged-in and store account id in session
	fAuthorization::setUserAuthLevel('user');
    fSession::set('user_accounts',$userAccounts);
    fURL::redirect('/');

}
?>