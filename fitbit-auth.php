<?php
$client_id = "23QCHL"; 
$redirect_uri = "http://localhost/fitbit-callback.php";
$scope = urlencode("activity heartrate sleep profile");

$auth_url = "https://www.fitbit.com/oauth2/authorize?"
    . "response_type=code"
    . "&client_id=" . $client_id
    . "&redirect_uri=" . urlencode($redirect_uri)
    . "&scope=activity%20heartrate%20sleep"
    . "&expires_in=604800"; 


header("Location: $auth_url");
exit();
?>
