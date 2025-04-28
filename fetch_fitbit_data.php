<?php
session_start();
if (!isset($_SESSION['fitbit_access_token'])) {
    echo json_encode(["error" => "Access token missing in session"]);
    exit;
}

if (isset($_SESSION['fitbit_access_token'])) {
    $access_token = $_SESSION['fitbit_access_token'];
    $date = date('Y-m-d');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.fitbit.com/1/user/-/activities/date/$date.json");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
    echo $response; 
} else {
    echo json_encode(["error" => "Not connected to Fitbit"]);
}
