<?php

$userLat = fRequest::get('lat');
$userLon = fRequest::get('lon');

// Get latest available vehicles JSON from car2go API
$curl = curl_init();
curl_setopt_array($curl, array(
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => $config['car2go_api_root'].'vehicles?loc='.$config['car2go_location'].'&oauth_consumer_key='.$config['car2go_consumer_key'].'&format='.$config['car2go_data_format']
));
$resp = curl_exec($curl);
if(!$resp){
	die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
}
curl_close($curl);
$json = json_decode($resp);
// print_r($json);

$maxWalkingTime = 600;
$userPosition = $userLat . ',' . $userLon;

foreach ($json->placemarks as $vehicle) {
	$distance = calculateDistance($userLat, $userLon, $vehicle->coordinates[1], $vehicle->coordinates[0], 'M');
	$vehicles[$vehicle->vin] = $vehicle;
	$vehiclesDistance[$vehicle->vin] = $distance;
}

asort($vehiclesDistance);

$i = 0;
$manyVehicles = false;
foreach ($vehiclesDistance as $vin => $distance) {
	$vehiclePosition = $vehicles[$vin]->coordinates[1] . ',' . $vehicles[$vin]->coordinates[0];
	$walkingTime = getWalkingTime($userPosition,$vehiclePosition);
	if ($walkingTime->rows[0]->elements[0]->duration->value < $maxWalkingTime) {
		$nearbyVehicles[$vehicles[$vin]->vin] = array(
			'vehicle'  => $vehicles[$vin],
			'distance' => $walkingTime
		);
	}
	if ($i == 9) {
		$manyVehicles = false;
		break;
	}
	$i++;
}

$nearbyVehicleCount = 'are '.count($nearbyVehicles).' car2gos';
if ($manyVehicles) {
	$nearbyVehicleCount = 'are 10+ car2gos';
}
else if (count($nearbyVehicles) == 1) {
	$nearbyVehicleCount = 'is 1 car2go';
}
else if (count($nearbyVehicles) == 0) {
	$nearbyVehicleCount = 'are no car2gos';
}

$glassData[0]['html'] =
	'<article class="cover-only" style="color:#fff">'.
		 '<section>'.
   			'<p>There '. $nearbyVehicleCount .' within 10 minutes of you.</p>'.
		 '</section>'.
	'</article>'
;
$glassData[0]['location'] = array(
	'lat' => $userLat,
	'lon' => $userLon
);
$glassData[0]['isCover'] = true;

$i = 1;
foreach ($nearbyVehicles as $vehicle) {
	$prettyAddress = getPrettyAddress($vehicle['vehicle']->coordinates[1] . ',' . $vehicle['vehicle']->coordinates[0]);
	$glassData[$i]['html'] = 
		'<article style="color:#fff">'.
			 '<section>'.
			   '<p>'.$vehicle['vehicle']->name.' is '.$vehicle['distance']->rows[0]->elements[0]->duration->text.' away at '.$prettyAddress.'</p>'.
			 '</section>'.
		'</article>'
	;
	$glassData[$i]['location'] = array(
		'lat' => $vehicle['vehicle']->coordinates[1],
		'lon' => $vehicle['vehicle']->coordinates[0]
	);
	$glassData[$i]['isCover'] = false;
	$i++;
}

echo json_encode($glassData);
// print_r($glassData);

?>