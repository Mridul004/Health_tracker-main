<?php
$token_data = json_decode(file_get_contents("fitbit_token.json"), true);
$access_token = $token_data['access_token'];

$url = "https://api.fitbit.com/1/user/-/activities/heart/date/today/7d.json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $access_token
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
echo json_encode($data);
?>
