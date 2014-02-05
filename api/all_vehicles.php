<?php

$placemarksData = [];

foreach ($config['car2go_locations'] as $value) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_HEADER => false,
		CURLOPT_URL => $config['car2go_api_root'].'vehicles'.'?loc='.$value.'&oauth_consumer_key='.$config['car2go_consumer_key'].'&format='.$config['car2go_data_format']
	));
	$resp = curl_exec($curl);
	if(!$resp){
		die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
	}
	curl_close($curl);

	$resp = json_decode($resp);
	// echo $resp;

	foreach ($resp->placemarks as $value) {
		$placemarksData[] = $value;
	}
}

echo('{ "placemarks":'. json_encode($placemarksData) . '}');

?>