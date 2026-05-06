<?php
global $conn;
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "login"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(["status" => "error", "message" => "No product id"]);
    exit;
}

$check = $conn->prepare("
    SELECT favorite_id
    FROM favorites
    WHERE user_id = ? AND product_id = ?
");

$check->bind_param("ii", $user_id, $product_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $del = $conn->prepare("
        DELETE FROM favorites
        WHERE user_id = ? AND product_id = ?
    ");

    $del->bind_param("ii", $user_id, $product_id);

    if ($del->execute()) {
        echo json_encode(["status" => "removed"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }

    exit;
}

$ins = $conn->prepare("
    INSERT INTO favorites (user_id, product_id)
    VALUES (?, ?)
");

$ins->bind_param("ii", $user_id, $product_id);

if ($ins->execute()) {
    echo json_encode(["status" => "added"]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}

exit;
?>