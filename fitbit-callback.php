<?php
$client_id = '23QCHL';
$client_secret = '89b082cf17650a9967cec0c819b76a1e';
$redirect_uri = 'http://localhost/Health_tracker-main/fitbit-callback.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $post_data = http_build_query([
        'client_id' => $client_id,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirect_uri,
        'code' => $code
    ]);

    $headers = [
        "Authorization: Basic " . base64_encode("$client_id:$client_secret"),
        "Content-Type: application/x-www-form-urlencoded"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.fitbit.com/oauth2/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    

    if (isset($result['access_token'])) {
        session_start();
        $_SESSION['fitbit_access_token'] = $result['access_token'];
        header("Location: dashboard.php");
    } else {
        echo "Failed to get access token:<br><pre>" . print_r($result, true) . "</pre>";
    }
} else {
    echo "Authorization code not found.";
}
