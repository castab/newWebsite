<?php
header('Content-Type: application/json');

// prepare request url
include_once('access.php'); // $apikey and $placeid
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
        http_response_code(200);
        echo $json;
    } else {
        http_response_code(500);
        echo $json;
    }
}

?>