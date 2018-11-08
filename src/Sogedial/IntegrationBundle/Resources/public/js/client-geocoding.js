// Define the map marker.
// Keeping a reference to it is mandatory to be able to delete/replace it with another one (see the deletePreviousMarker function).
var mapMarker = null;

// Store all of the fields relevant to build the client's address.
// These fields can be aggregated in the aggregateAddressFields function.
var addressFields = {
    address: document.querySelector("#address-address > input"),
    postalCode: document.querySelector("#address-postal-code > input"),
    city: document.querySelector("#address-city > input"),
    country: document.querySelector("#address-country > input")
};
// Defines the order in which to put the address parts (every array item must match a key from the addressFields object).
var addressFieldsOrder = ["address", "postalCode", "city", "country"];

// Store latitude and longitude fields' references to read/update them at a later time.
var addressLatitudeField = document.querySelector("#address-lat > input");
var addressLongitudeField = document.querySelector("#address-lng > input");

/**
 * Initializes the map provided by the Google Maps API.
 * Uses the initial fields' information to hydrate into the map.
 */
function initMap() {
    // Create GPS coordinates object to use with the map.
    var latlng = aggregateGpsCoordinatesFields(
        addressLatitudeField,
        addressLongitudeField
    );

    // Initialize the map
    var map = new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        center: latlng
    });
    mapMarker = new google.maps.Marker({
        map: map,
        position: latlng
    });

    // Bind onclick events to both form buttons.
    var geocoder = new google.maps.Geocoder();
    var infowindow = new google.maps.InfoWindow();
    document
        .getElementById("address-submit-address")
        .addEventListener("click", function() {
            geocodeAddress(geocoder, map);
        });
    document
        .getElementById("address-submit-latlng")
        .addEventListener("click", function() {
            geocodeLatLng(geocoder, map, infowindow);
        });
}

/**
 * Converts an address to GPS coordinates and updates a given map accordingly.
 * @param {*} geocoder Google Maps geocoder handling the address-to-coordinates conversion.
 * @param {*} resultsMap Google Maps map that will ingest the geocoding's data.
 */
function geocodeAddress(geocoder, resultsMap) {
    var address = aggregateAddressFields(addressFields, addressFieldsOrder);
    geocoder.geocode({ address: address }, function(results, status) {
        if (status === "OK") {
            resultsMap.setCenter(results[0].geometry.location);
            resultsMap.setZoom(12);

            // If a marker already exists on the map, delete it, so that there are not multiple markers on the map after several searches.
            if (mapMarker) {
                deleteMarker(mapMarker);
            }
            mapMarker = new google.maps.Marker({
                map: resultsMap,
                position: results[0].geometry.location
            });

            // Modify GPS coordinates fields based on the Google Maps API response.
            updateGpsCoordinatesFields(
                results[0].geometry.location.lat(),
                results[0].geometry.location.lng()
            );
        } else {
            alert("Le geocoding a retourné l'erreur suivante : " + status);
        }
    });
}

/**
 * Converts GPS coordinates to an address and updates a given map accordingly.
 * @param {*} geocoder Google Maps geocoder handling the coordinates-to-address conversion.
 * @param {*} map Google Maps map that will ingest the geocoding's data.
 * @param {*} infowindow Google Maps tooltip that will later display the address found from geocoding GPS coordinates.
 */
function geocodeLatLng(geocoder, map, infowindow) {
    var latlng = aggregateGpsCoordinatesFields(
        addressLatitudeField,
        addressLongitudeField
    );
    geocoder.geocode({ location: latlng }, function(results, status) {
        if (status === "OK") {
            if (results[0]) {
                map.setZoom(12);
                // If a marker already exists on the map, delete it, so that there are not multiple markers on the map after several searches.
                if (mapMarker) {
                    deleteMarker(mapMarker);
                }
                mapMarker = new google.maps.Marker({
                    position: latlng,
                    map: map
                });
                console.log(results[0]);
                infowindow.setContent(results[0].formatted_address);
                infowindow.open(map, mapMarker);

                // Modify address fields based on the Google Maps API response.
                updateAddressFields(
                    results[0].address_components,
                    addressFields
                );
            } else {
                window.alert("Le geocoding n'a trouvé aucun résultat.");
            }
        } else {
            window.alert(
                "Le geocoding a retourné l'erreur suivante : " + status
            );
        }
    });
}

/**
 * Deletes the passed map marker from its original map, and voids its reference.
 * @param {*} marker Reference to the map marker.
 */
function deleteMarker(marker) {
    marker.setMap(null);
    marker = null;
}

/**
 * Creates a GPS coordinates object, containing the latitude and the longitude.
 * @param {Element} latitude
 * @param {Element} longitude
 */
function aggregateGpsCoordinatesFields(latitude, longitude) {
    return {
        lat: parseFloat(latitude.value),
        lng: parseFloat(longitude.value)
    };
}

/**
 * Modifies the value of the latitude and longitude fields based on the Google Maps API response.
 * @param {Number} latitude
 * @param {Number} longitude
 */
function updateGpsCoordinatesFields(latitude, longitude) {
    addressLatitudeField.value = latitude;
    addressLongitudeField.value = longitude;
}

/**
 * Retrieves all address parts from the passed address fields, forming a single address string.
 * @param {Object.<string, HTMLElement>} addressFields All address fields from which to retrieve partial address parts.
 * @param {String[]} addressFieldsOrder Order to put the address fields in.
 * @returns {string} Complete address build from the partial fields.
 */
function aggregateAddressFields(addressFields, addressFieldsOrder) {
    var completeAddress = "";
    for (var i = 0, l = addressFieldsOrder.length; i < l; i++) {
        // Fill in the address in the correct order.
        completeAddress += addressFields[addressFieldsOrder[i]].value + " ";
    }
    return completeAddress;
}

/**
 * Modifies the value of the address fields based on the Google Maps API response.
 * @param {Object[]} addressComponents Separate parts of a given address, containing a 'long_name' string property and a 'types' array of strings describing the current part of the address.
 * @param {Object.<string, HTMLElement>} addressFields All address fields in which to write partial address parts.
 */
function updateAddressFields(addressComponents, addressFields) {
    // Wipe the old address fields' values
    deleteAddressFields(addressFields);

    for (var i = 0, li = addressComponents.length; i < li; i++) {
        var addressComponent = addressComponents[i];
        var addressComponentValue = addressComponent.long_name;
        var addressComponentTypes = addressComponent.types;

        // Parse the type(s) of each address component (some are useful, others are useless in our case).
        for (var j = 0, lj = addressComponentTypes.length; j < lj; j++) {
            var type = addressComponent.types[j];

            // If the type is deemed useful and worth saving in the address, we must save it in the right address field.
            switch (type) {
                case "street_number":
                    // Prepend the street number before a potential street name
                    addressFields.address.value = addressComponentValue + " " + addressFields.address.value;
                    break;
                case "route":
                    // Append the street name to a potential street number
                    addressFields.address.value += addressComponentValue;
                    break;
                case "postal_code":
                    addressFields.postalCode.value = addressComponentValue;
                    break;
                case "locality":
                    addressFields.city.value = addressComponentValue;
                    break;
                case "country":
                    addressFields.country.value = addressComponentValue;
                    break;
                default:
                    break;
            }
        }
    }
}

/**
 *
 * @param {Object.<string, HTMLElement>} addressFields All address fields whose value must be erased.
 */
function deleteAddressFields(addressFields) {
    var addressKeys = Object.keys(addressFields);
    for (var i = 0, l = addressKeys.length; i < l; i++) {
        addressFields[addressKeys[i]].value = "";
    }
}
