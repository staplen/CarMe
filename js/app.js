$(function() {

  // Set default values and variables
  var defaultPosition = new google.maps.LatLng(38.9093728, -77.0435439);
  var infowindow = new google.maps.InfoWindow({
    content: '&nbsp;'
  });
  var infoWindowTemplate = _.template($('#infoWindow-template').html());
  var initializeMap = true;
  var userAuthenticated, rebookPostCancel = false;
  var userName = null;
  var userId = null;
  var activeBooking = {
    active: false
  };
  var map, userMarker, bookingMarker, userPosition;
  var carMarkers = [];
  var availableCars = [];
  var availableCarsVins = [];
  var retinaDisplay = window.devicePixelRatio > 1 ? true : false;
     
  /**
   * Create map object with various options
   * calls updateUserBookings() if user is authenticated, renderCarMarkers() otherwise
   *
   * @param  position - user's position or default position to center map at
  */   
  function initialize(position) {
    var mapOptions = {
      center: position,
      zoom: 15,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      mapTypeControl: false,
      streetViewControl: false
    };
    map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
    var GeoMarker = new GeolocationMarker(map);
    google.maps.event.addListener(GeoMarker, 'position_changed', function(e) {
      map.panTo(GeoMarker.getPosition());
      google.maps.event.clearListeners(GeoMarker, 'position_changed');
    });
    if (userAuthenticated) {
      updateUserBookings(false,false);
    }
    else {
      renderCarMarkers();
    }
  }

  function renderCarMarkers() {
    console.log('renderCarMarkers called');

    $.get( "/api/vehicles", function( data ) {
        var iconImage = '/img/spotlight-waypoint-a.png';
        var tempAvailableCarsVins = [];

        function addAvailableCar(carData) {
          availableCars[carData.vin] = {latlng: carData.coordinates[1]+','+carData.coordinates[0]};
          availableCarsVins.push(carData.vin);
          var latLng = new google.maps.LatLng(carData.coordinates[1],carData.coordinates[0]);
          carData.numberPlate = carData.name;
          placeMarker(latLng,iconImage,carData);
        }

        var cleanAvailableCars = _.after(data.placemarks.length, function() {
          var diff = availableCarsVins.filter(function(x) { return tempAvailableCarsVins.indexOf(x) < 0 });
          _.each(diff, function(key) {
            var index = availableCarsVins.indexOf(key);
            // if the newly unavailable vehicle is not the vehicle currently booked by the user
            if (availableCarsVins[index] !== activeBooking.vin) {
              removeMarker(carMarkers[key]);
            }
            availableCarsVins.splice(index, 1);
          });
          setTimeout(function() {
            if (userAuthenticated) {
              updateUserBookings(false,false);
            }
            else {
              renderCarMarkers();
            }
          },20000);
        });

      _.each(data.placemarks, function(value, key, list) {
        tempAvailableCarsVins.push(value.vin);
        // if the car is not in our master list of available cars,
        // add it to the master list and add a marker to the map
        if (availableCarsVins.indexOf(value.vin) < 0) {
          addAvailableCar(value);
        }
        // else if the car is in our master list already,
        // but the car's position has changed, remove the old marker marker
        // and update the latlng in our master list
        else if (availableCars[value.vin].latlng != value.coordinates[1]+','+value.coordinates[0]) {
          removeMarker(carMarkers[value.vin]);
          addAvailableCar(value);
        }
        
        cleanAvailableCars();
      });
    }, 'json');
  }

  function placeMarker(position,icon,carMeData) {
    var myIcon = new google.maps.MarkerImage(icon, null, null, null, new google.maps.Size(22,40));
    var marker = new google.maps.Marker({
      position: position,
      icon: myIcon,
      map: map,
      carMe: carMeData
    });
    carMarkers[carMeData.vin] = marker;
    google.maps.event.addListener(marker, 'click', processMarkerClick);
    if (activeBooking.vin === marker.carMe.vin) {
      // initializeMap = false;
      // map.panTo(position);
      new google.maps.event.trigger( marker, 'click' );
    }
  }

  function removeMarker(marker) {
    marker.setMap(null);
    marker = null;
  }

  function placeBookingMarker(position,carMeData) {
    console.log('placeBookingMarker called');
    carMeDataCopy = carMeData.vehicle;
    carMeDataCopy.name = carMeData.vehicle.numberPlate;
    if (activeBooking.vin && carMarkers.length > 0) {
      removeMarker(carMarkers[activeBooking.vin]);
      console.log('removed old booking marker');
    }
    placeMarker(position,'/img/spotlight-waypoint-b.png',carMeDataCopy);    
  }

  function processMarkerClick(e) {
    var carMeData = this.carMe;
    var marker = this;
    var displayAddress = prettyAddress(this.position,carMeData,marker,renderInfoWindow);
  }

  function renderInfoWindow(carMeData,marker,displayAddress) {
    var content = carMeData;
    content.displayAddress = displayAddress;
    content.activeBooking = activeBooking;
    content.bookedVehicle = activeBooking.vin === marker.carMe.vin ? true : false;
    content.userAuthenticated = userAuthenticated;
    var infoWindowContent = infoWindowTemplate(content);
    infowindow.setContent(infoWindowContent);
    infowindow.open(map, marker);
    if (retinaDisplay) {
      $($('.gm-style-iw').siblings()[1]).html('&times;').css({"font-size": "26px", "height": "26px", "opacity": "0.4", "top": "1px"});
    }
    $('.booking-trigger').click(initiateBooking);
    $('.booking-cancel').click(cancelBooking);
  }

  function initiateBooking(e) {
    e.preventDefault();
    if (!userAuthenticated) {
      window.location.assign('/api/login');
    }
    else if (activeBooking.active) {
      rebookPostCancel = true;
      cancelBooking(e);
    }
    else {
      var vin = $(e.target).data('vin');
      console.log('initiating booking for '+vin);
      $('.booking-trigger').hide();
      $('.booking-status').text('Reserving...').show();
      $.get( "/api/bookings/"+vin+"/"+userId, function( data ) {
        if (data.returnValue.code == 0) {
          alert('Reservation successful!');
          $('.booking-status').text('Reserved!');
          updateUserBookings(false,false);
        }
        else {
          alert(data.returnValue.description);
          $('.booking-trigger').html('Re-try reservation &rarr;').show();
        }
        $('.booking-status').hide();
      }, 'json');
    }
  }

  function cancelBooking(e) {
    e.preventDefault();
    console.log('canceling booking '+activeBooking.id);
    $('.booking-cancel, .booking-trigger, .booking-info').hide();
    $('.booking-status').text('Canceling...').show();
    $.get( "/api/booking/"+activeBooking.id, function( data ) {
      if (data.returnValue.code == 0) {
        alert('Reservation canceled!');
        $('.booking-status').text('Canceled!');
        updateUserBookings(data,true);
        if (rebookPostCancel) {
          rebookPostCancel = false;
          setTimeout(initiateBooking(e),3000);
        }
      }
      else {
        alert(data.returnValue.description);
        $('.booking-cancel').text('Re-try cancelation &rarr;');
      }
      $('.booking-status').hide();
    }, 'json');
  }

  function updateUserBookings(data,reset) {
    console.log('updateUserBookings called');
    if (data && !reset) {
      update(data);
    }
    else if (reset) {
      resetObj();
    }
    else {
      $.get( "/api/bookings", function( data ) {
        if (data.booking.length > 0) {
          update(data)
        }
        else {
          resetObj();
        }
      }, 'json');
    }
    function update(data) {
      console.log('update called');
      if (data.booking[0].bookingId !== activeBooking.id) {
        console.log('new booking found');
        $('body').addClass('activeBooking');
        activeBooking.active = true;
        activeBooking.id = data.booking[0].bookingId;
        activeBooking.vin = data.booking[0].vehicle.vin;
        activeBooking.expires = new Date(data.booking[0].reservationTime.timeInMillis + 900000);
        var minutes = (activeBooking.expires.getMinutes() < 10? '0' : '') + activeBooking.expires.getMinutes();
        activeBooking.expiresPretty = activeBooking.expires.getHours() + ':' + minutes;
        var position = new google.maps.LatLng(data.booking[0].bookingposition.latitude,data.booking[0].bookingposition.longitude);
        placeBookingMarker(position,data.booking[0]);
      }
      renderCarMarkers();
    }
    function resetObj() {
      console.log('resetObj called');
      infowindow.close();
      if (activeBooking.vin) {
        removeMarker(carMarkers[activeBooking.vin]);
      }
      $('body').removeClass('active-booking');
      activeBooking = {
        active: false,
        vin: null
      };
      renderCarMarkers();
    }
  }

  function prettyAddress(position,carMeData,marker,callback) {
    var displayAddress = '';
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({'latLng': position}, function(results, status) {
      if (status == google.maps.GeocoderStatus.OK) {
        if (results[0]) {
          displayAddress = results[0].address_components[0].short_name + ' ' + results[0].address_components[1].short_name;
        }
      } else {
        console.log("Geocoder failed due to: " + status);
        displayAddress = carMeData.address;
      }
      if (callback) {
        callback(carMeData,marker,displayAddress);
      }
      else {
        return displayAddress;
      }
    });
  }

  if (navigator.userAgent.indexOf('iPhone') != -1 || navigator.userAgent.indexOf('Android') != -1 ) {
    $('body').addClass('mobile');
  }
  $.get( "/api/getuserinfo", function( data ) {
    if (data.isAuthenticated) {
      userAuthenticated = true;
      window.carmeAccounts = data.accounts;
      console.log(data.accounts);

      if (data.accounts.account.length > 1) {
        var userHtml = '';
        for (var i=0; i<data.accounts.account.length; i++) {
          userName = data.accounts.account[i].description.split(' ');
          userHtml += '<option value="'+data.accounts.account[i].accountId+'">'+userName[0]+'</option>';
        }
        $('#userName').html('bill to: <select id="user-accounts-list">'+userHtml+'</select>');
        userId = data.accounts.account[0].accountId;
        $('#user-accounts-list').change(function() {
          userId = $('#user-accounts-list option:selected').val();
          console.log('userId: '+userId);
        });
      }
      else {
        userName = data.accounts.account[0].description.split(' ');
        $('#userName').text('car2go user: '+userName[0]);
        userId = data.accounts.account[0].accountId;
      }
      $('body').addClass('authenticated-user');
    }
    initialize(defaultPosition);
  }, 'json');



});