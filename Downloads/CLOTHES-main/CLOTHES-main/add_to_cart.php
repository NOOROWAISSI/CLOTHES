<?php
global $conn;
include_once "db.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "login"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$variant_id = (int)($_POST['variant_id'] ?? 0);

if ($variant_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Choose color and size"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT variant_id, quantity
    FROM product_variants
    WHERE variant_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $variant_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Variant not found"]);
    exit;
}

$row = $res->fetch_assoc();

if ((int)$row['quantity'] <= 0) {
    echo json_encode(["status" => "soldout", "message" => "Sold out"]);
    exit;
}

$cart = $conn->prepare("
    INSERT INTO cart (user_id, variant_id, quantity)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE quantity = quantity + 1
");
$cart->bind_param("ii", $user_id, $variant_id);

if ($cart->execute()) {
    echo json_encode(["status" => "added"]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}

exit;
?>