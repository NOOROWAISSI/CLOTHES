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
$delta = (int)($_POST['delta'] ?? 0);

$q = $conn->prepare("
SELECT quantity
FROM cart
WHERE cart_id=? AND user_id=?
");

$q->bind_param("ii", $cart_id, $user_id);
$q->execute();

$res = $q->get_result();

if(!$row = $res->fetch_assoc()){
    echo json_encode(["status"=>"error"]);
    exit;
}

$newQty = (int)$row['quantity'] + $delta;

if($newQty < 1){
    $newQty = 1;
}

$u = $conn->prepare("
UPDATE cart
SET quantity=?
WHERE cart_id=? AND user_id=?
");

$u->bind_param("iii", $newQty, $cart_id, $user_id);
$u->execute();

echo json_encode([
    "status"=>"success",
    "qty"=>$newQty
]);
?>