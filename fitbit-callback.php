<?php
$client_id = "23QCHL";
$client_secret = "89b082cf17650a9967cec0c819b76a1e";
$redirect_uri = "http://localhost/fitbit-callback.php"; 


if (!isset($_GET['code'])) {
    die("Authorization failed.");
}

$code = $_GET['code']; 

// Exchange code for an access token
$token_url = "https://api.fitbit.com/oauth2/token";
$headers = [
    "Authorization: Basic " . base64_encode("$client_id:$client_secret"),
    "Content-Type: application/x-www-form-urlencoded"
];

$data = http_build_query([
    "client_id" => $client_id,
    "grant_type" => "authorization_code",
    "redirect_uri" => $redirect_uri,
    "code" => $code
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['access_token'])) {
  
    file_put_contents("fitbit_token.json", json_encode($result));
    echo "Authorization successful! You can now fetch Fitbit data.";
} else {
    die("Error getting access token: " . $response);
}
?>
