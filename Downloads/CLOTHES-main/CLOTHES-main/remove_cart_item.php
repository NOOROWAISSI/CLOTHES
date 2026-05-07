<?php
global $conn;
include "db.php";

header("Content-Type: application/json");

if(!isset($_SESSION['user_id'])){
    echo json_encode(["status"=>"login"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$cart_id = (int)($_POST['cart_id'] ?? 0);

$stmt = $conn->prepare("
DELETE FROM cart
WHERE cart_id=? AND user_id=?
");

$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();

echo json_encode(["status"=>"success"]);
?>