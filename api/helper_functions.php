<?

class XmlToJson {

	public static function Parse ($xml) {

		$xml = str_replace(array("\n", "\r", "\t"), '', $xml);

		$xml = trim(str_replace('"', "'", $xml));

		$simpleXml = simplexml_load_string($xml);

		$json = json_encode($simpleXml);

		return $json;

	}

}

function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit) {

  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);

  if ($unit == "K") {
    return ($miles * 1.609344);
  } else if ($unit == "N") {
      return ($miles * 0.8684);
    } else {
        return $miles;
      }
}

function getPrettyAddress($position) {
	$results = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=$position&sensor=false"));
	return $results->results[0]->address_components[0]->short_name . " " . $results->results[0]->address_components[1]->short_name;
}

function getWalkingTime($origin,$destination) {
	$results = json_decode(file_get_contents("http://maps.googleapis.com/maps/api/distancematrix/json?origins=$origin&destinations=$destination&mode=walking&units=imperial&sensor=false"));
	// return [$results->rows[0]->elements[0]->duration->value,$results->rows[0]->elements[0]->duration->text];
	// return $results->rows[0]->elements[0]->distance->text;
	return $results;
}

?>