<?php

fAuthorization::requireAuthLevel('user');

$oauthObject = new OAuthSimple();
$result_parameters = array(
	'loc' 	 => $config['car2go_location'],
	'format' => $config['car2go_data_format']
);

$signatures = array(
	'consumer_key'     => $config['car2go_consumer_key'],
	'shared_secret'    => $config['car2go_consumer_secret'],
	'oauth_secret' 	   => fSession::get('oauth_signatures[oauth_secret]'),
	'oauth_token'	   => fSession::get('oauth_signatures[oauth_token]')
);

$result = $oauthObject->sign(array(
    'path'       => $config['car2go_api_root'].$page,
    'parameters' => $result_parameters,
    'signatures' => $signatures)
);

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_URL, $result['signed_url']);

$resp = curl_exec($curl);
if(!$resp){
	die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
}
curl_close($curl);
echo($resp);

?>