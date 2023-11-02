<?php
require 'vendor/autoload.php';

$tokenEnv = file_get_contents('token.env');
$envVariables = explode("\n", $tokenEnv);
$variables = [];

foreach ($envVariables as $envVariable) {
  $parts = explode('=', $envVariable);
  if (count($parts) === 2) {
    $variables[$parts[0]] = $parts[1];
  }
}

if (isset($variables['BEARER_TOKEN'])) {
  //manually feed token in since i wasn't able to get it to fee dproperly. fine to be in github while we are testing since the tokens are only valid for 30 minutes
  $bearerToken = "eyJhbGciOiJIUzI1NiJ9.eyJhdXRoZW50aWNhdGVkLWFwcCI6IkdFTkVSSUMiLCJhdXRoZW50aWNhdGlvbi10eXBlIjoiSlNTIiwiZ3JvdXBzIjpbXSwic3ViamVjdC10eXBlIjoiSlNTX1VTRVJfSUQiLCJ0b2tlbi11dWlkIjoiZDYwODU0YTctMjY2YS00MmMzLTk1MGYtOWVkZWVjYTJmOWFjIiwibGRhcC1zZXJ2ZXItaWQiOi0xLCJzdWIiOiIxNyIsImV4cCI6MTY4NTA0ODU3MH0.iftEaRK8Ds69QazdST9A-KaCy08XWl53O1KCjUYs3Ew";
  $curl = curl_init();
  $baseAPI = "https://provoschooldistrict.jamfcloud.com/api/v1/";
  $filtering = "computers-inventory?section=GENERAL&section=HARDWARE&section=OPERATING_SYSTEM";
  curl_setopt_array($curl, [
    CURLOPT_URL =>  $baseAPI.$filtering,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
      "Authorization: Bearer $bearerToken",
      "accept: application/json"
    ],
  ]);

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);

  if ($err) {
    echo "cURL Error #:" . $err;
  } else {
    echo $response;
  }
} else {
  echo 'Bearer token not found in the environment variables.';
}
