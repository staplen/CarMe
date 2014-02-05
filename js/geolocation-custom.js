function geolocationError(error) {
  var errors = { 
    1: 'Permission denied',
    2: 'Position unavailable',
    3: 'Request timeout'
  };
  console.log("Geolocation Error: " + errors[error.code]);
  if (initializeMap) {
    initialize(defaultPosition);
  }
}

function geolocationSuccess(position) {
  userPosition = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
  if (initializeMap) {
    initialize(userPosition);
  }
}

function requestUserLocation() {
  if (navigator.geolocation) {
    var timeoutVal = 10 * 1000 * 1000;
    navigator.geolocation.getCurrentPosition(
      geolocationSuccess, 
      geolocationError,
      { enableHighAccuracy: true, timeout: timeoutVal, maximumAge: 120000 }
    );
  }
  else {
    if (initializeMap) {
      initialize(defaultPosition);
    }
  }
}