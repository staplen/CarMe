<?php

$vehicles = json_decode(getCurrentLocationVehicles());
$vehicles = $vehicles->placemarks;

foreach ($vehicles as $vehicle) {
	$vin = $vehicle->vin;
	$fuel = $vehicle->fuel;
	$latitude = $vehicle->coordinates[0];
	$longitude = $vehicle->coordinates[1];

	$carme_db->execute("INSERT INTO `carme_historic_data` (`vin`, `fuel`, `latitude`, `longitude`) VALUES ('$vin', $fuel, $latitude, $longitude);");
}

?>