<?php
global $conn;
include "db.php";

$clientID = "YOUR_CLIENT_ID";
$clientSecret = "YOUR_CLIENT_SECRET";
$redirect_uri = "http://localhost/CLOTHES-main/CLOTHES-main/google_callback.php";

if (!isset($_GET['code'])) {
    die("Google login failed");
}

$code = $_GET['code'];

$token_url = "https://oauth2.googleapis.com/token";

$data = [
    "code" => $code,
    "client_id" => $client_id,
    "client_secret" => $client_secret,
    "redirect_uri" => $redirect_uri,
    "grant_type" => "authorization_code"
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$token = json_decode($response, true);

if (!isset($token['access_token'])) {
    die("Could not get Google access token");
}

$user_info_url = "https://www.googleapis.com/oauth2/v2/userinfo";

$ch = curl_init($user_info_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $token['access_token']
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$userInfoResponse = curl_exec($ch);
curl_close($ch);

$googleUser = json_decode($userInfoResponse, true);

$email = $googleUser['email'] ?? "";
$full_name = $googleUser['name'] ?? "Google User";

if ($email === "") {
    die("Google email not found");
}

$check = $conn->prepare("SELECT user_id, full_name FROM users WHERE email=? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $email;
    $_SESSION['role'] = "user";

    header("Location: index.php");
    exit;
}

$password = "google_user";
$phone = "";

$insert = $conn->prepare("
    INSERT INTO users (full_name, email, phone, password)
    VALUES (?, ?, ?, ?)
");

$insert->bind_param("ssss", $full_name, $email, $phone, $password);
$insert->execute();

$_SESSION['user_id'] = $insert->insert_id;
$_SESSION['full_name'] = $full_name;
$_SESSION['email'] = $email;
$_SESSION['role'] = "user";

header("Location: index.php");
exit;
?>