<?php
$client_id = '23QCHL';
$redirect_uri = urlencode('http://localhost/Health_tracker-main/fitbit-callback.php');

$scope = 'activity';
$auth_url = "https://www.fitbit.com/oauth2/authorize?response_type=code&client_id=$client_id&redirect_uri=$redirect_uri&scope=$scope&expires_in=604800";
$_SESSION['fitbit_access_token'] = $access_token;
$_SESSION['fitbit_refresh_token'] = $refresh_token;
$_SESSION['fitbit_token_created_at'] = time();
$_SESSION['fitbit_expires_in'] = $token_info['expires_in']; 

header("Location: $auth_url");
exit();
