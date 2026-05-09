<?php
include "db.php";

$clientID = "YOUR_CLIENT_ID";
$redirect_uri = "http://localhost/CLOTHES-main/CLOTHES-main/google_callback.php";

$scope = urlencode("email profile");

$url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        "client_id" => $client_id,
        "redirect_uri" => $redirect_uri,
        "response_type" => "code",
        "scope" => "email profile",
        "access_type" => "online",
        "prompt" => "select_account"
    ]);

header("Location: " . $url);
exit;
?>