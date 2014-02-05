<?php

fAuthorization::requireAuthLevel('user');

// OAuth request building helper functions
function buildBaseString($baseURI, $method, $params) {
    $r = array();
    ksort($params);
    foreach($params as $key=>$value){
        $r[] = "$key=" . rawurlencode($value); 
    }

    return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r)); //return complete base string
}
function buildAuthorizationHeader($oauth) {
    $r = 'Authorization: OAuth ';
    $values = array();
    foreach($oauth as $key=>$value)
        $values[] = "$key=\"" . rawurlencode($value) . "\""; 

    $r .= implode(', ', $values); 
    return $r; 
}


// Set default request parameters
$is_post = false;
$header = array();

// Additional and override parameters for canceling booking
if ($page === 'booking' && $query) {
	$page = 'booking/'.$query;
	$curl_method = 'DELETE';
}
// Additional and override parameters for creating a booking
else if ($page === 'bookings' && $query) {
	$curl_method = 'POST';
	$is_post = true;
	$args = array(
		'loc' 	  => $config['car2go_location'],
		'format'  => $config['car2go_data_format'],
		'vin'     => strtoupper($query),
		'account' => $subquery
	);
}


// Set OAuth request parameters
$url 					   = $config['car2go_api_root'].$page;
$oauth_access_token 	   = fSession::get('oauth_signatures[oauth_token]');
$oauth_access_token_secret = fSession::get('oauth_signatures[oauth_secret]');
$consumer_key 			   = $config['car2go_consumer_key'];
$consumer_secret 		   = $config['car2go_consumer_secret'];

$oauth = array(
	'oauth_consumer_key'     => $consumer_key,
    'oauth_nonce' 			 => time(),
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_token' 			 => $oauth_access_token,
    'oauth_timestamp' 		 => time(),
    'oauth_version' 		 => '1.0'
);

// Generate OAuth signature
$params = $is_post ? array_merge($oauth,$args) : $oauth;
$base_info = buildBaseString($url, $curl_method, $params);
$composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
$oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
$oauth['oauth_signature'] = $oauth_signature;

$header = array(buildAuthorizationHeader($oauth));
$options = array( 
	CURLOPT_HTTPHEADER => $header,
	CURLOPT_HEADER => false,
	CURLOPT_URL => $url,
	CURLOPT_CUSTOMREQUEST => $curl_method,
	CURLOPT_RETURNTRANSFER => true
);
$ch = curl_init();
curl_setopt_array($ch, $options);
if ($is_post) { curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args)); }
$response = curl_exec($ch);
if(!$response){
	die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
}
curl_close($ch);
if (!$is_post) {
	$response = XmlToJson::Parse($response);
}
echo $response;

?>