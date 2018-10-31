<?php
header('Content-Type: application/json');

// Function to figure out the difference between to dates in days (%a)
function dateDifference($date_1 , $date_2 , $differenceFormat = '%i' )
{
    $datetime1 = date_create($date_1);
    $datetime2 = date_create($date_2);
    
    $interval = date_diff($datetime1, $datetime2);
    
    return ($interval->format($differenceFormat));
    
}

// Function to hit the API for a refreshed JSON payload
function getNewWeatherUpdate() {
    // prepare request url
    include_once('access.php'); // $apikey and $cityId
    $request_url = 'http://api.openweathermap.org/data/2.5/weather?appid=' . $apikey . '&id=' . $cityId;
    // Shoot off the request
    if (($json = @file_get_contents($request_url)) === FALSE) {
        // GET failed
        $errorResponse = new stdClass();
        $errorResponse->status = 'NEW_REQUEST_FAILED';
        http_response_code(500);
        echo json_encode($errorResponse);
    } else {
        // Got a response, check if status is "OK"
        $response = json_decode($json);
        if ($response->{'base'} == "stations") {
            $response->date_stored = date(DATE_RFC2822);
            // Write/update file to reviews.json
            if (saveWeatherUpdate($response)) {
                // Write success
                http_response_code(200);
            } else {
                // Write failed; but this is not complete failure
                // Push message as 202 code
                http_response_code(202);
            }
            // Return new reviews payload
            echo json_encode($response, JSON_UNESCAPED_SLASHES);
        } else {
            // Status is not OK
            // No need to append the status variable since
            // one is already provided with the response by default
            http_response_code(500);
            echo $json;
        }
    }
}

// Store a payload
// Input is a PHP-native object
function saveWeatherUpdate($newJSON) {
    // Wrap up the php object to JSON
    $newJSON = json_encode($newJSON, JSON_UNESCAPED_SLASHES);
    // Try opening the file
    $fp = fopen('weather.json', 'w');
    if ( ($fp === false) || (fwrite($fp, $newJSON) === FALSE) ) {
        // File either didn't open or write successfully
        return FALSE;
    } else {
        // All good!
        return TRUE;
    }
}

// ** Main process ** //
// Try loading the stored JSON payload; check for existing before
// getting a new payload from Google
if (!($json = @file_get_contents('weather.json')) === FALSE) {
    // File exists; check for a date
    $response = json_decode($json);
    if (isset($response->{'date_stored'})) {
        // Date exists, check if it's within 30 days
        if (dateDifference($response->{'date_stored'}, date(DATE_RFC2822)) >= 10) {
            // Saved payload is at least 30 days old, get a new copy
            getNewWeatherUpdate();
        } else {
            // Saved payload is within date range, output as JSON
            $response->minutes_until_refresh = (10 - dateDifference($response->{'date_stored'}, date(DATE_RFC2822)));
            http_response_code(200);
            echo json_encode($response, JSON_UNESCAPED_SLASHES);
        }
    } else {
        // Date is invalid
        getNewWeatherUpdate();
    }
} else {
    // File doesn't exist or can't be opened
    getNewWeatherUpdate();
}

?>