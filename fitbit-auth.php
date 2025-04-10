<?php
$client_id = '23QCHL';
$redirect_uri = urlencode('http://localhost/Health_tracker-main/fitbit-callback.php');

$scope = 'activity';
$auth_url = "https://www.fitbit.com/oauth2/authorize?response_type=code&client_id=$client_id&redirect_uri=$redirect_uri&scope=$scope&expires_in=604800";

header("Location: $auth_url");
exit();
