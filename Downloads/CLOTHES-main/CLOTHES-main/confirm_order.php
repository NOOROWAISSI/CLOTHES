<?php
global $conn;
include "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "login"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$payment = $_POST['payment'] ?? '';
$delivery = $_POST['delivery'] ?? 'standard';

if ($payment === '') {
    echo json_encode(["status" => "error", "message" => "Choose payment method"]);
    exit;
}

$payment_method = ($payment === "card") ? "CARD" : "CASH";

$q = $conn->prepare("
SELECT 
    c.cart_id,
    c.quantity,
    pv.variant_id,
    pv.quantity AS stock_qty,
    p.price
FROM cart c
JOIN product_variants pv ON c.variant_id = pv.variant_id
JOIN products p ON pv.product_id = p.product_id
WHERE c.user_id = ?
");

$q->bind_param("i", $user_id);
$q->execute();
$res = $q->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["status" => "empty"]);
    exit;
}

$items = [];
$subtotal = 0;

while ($row = $res->fetch_assoc()) {
    $qty = (int)$row['quantity'];
    $price = (float)$row['price'];

    if ((int)$row['stock_qty'] < $qty) {
        echo json_encode([
            "status" => "error",
            "message" => "Some items are out of stock"
        ]);
        exit;
    }

    $subtotal += $price * $qty;

    $items[] = [
        "variant_id" => (int)$row['variant_id'],
        "quantity" => $qty,
        "price" => $price
    ];
}

$delivery_price = 0;

if ($subtotal < 100) {
    $delivery_price = ($delivery === "express") ? 9.99 : 4.99;
}

$total_price = $subtotal + $delivery_price;

$area_id = null;
$areaQ = $conn->prepare("SELECT area_id FROM delivery_areas LIMIT 1");
$areaQ->execute();
$areaRes = $areaQ->get_result();

if ($areaRow = $areaRes->fetch_assoc()) {
    $area_id = (int)$areaRow['area_id'];
}

$order_number = "DM-" . date("Y") . "-" . rand(10000, 99999);

$conn->begin_transaction();

try {

    $order_stmt = $conn->prepare("
        INSERT INTO orders 
        (order_number, user_id, area_id, subtotal, delivery_price, total_price, order_status)
        VALUES (?, ?, ?, ?, ?, ?, 'NEW')
    ");

    $order_stmt->bind_param(
        "siiddd",
        $order_number,
        $user_id,
        $area_id,
        $subtotal,
        $delivery_price,
        $total_price
    );

    $order_stmt->execute();
    $order_id = $order_stmt->insert_id;

    $item_stmt = $conn->prepare("
        INSERT INTO order_items
        (order_id, variant_id, quantity, price_at_order)
        VALUES (?, ?, ?, ?)
    ");

    $stock_stmt = $conn->prepare("
        UPDATE product_variants
        SET quantity = quantity - ?
        WHERE variant_id = ?
    ");

    foreach ($items as $item) {
        $variant_id = (int)$item['variant_id'];
        $qty = (int)$item['quantity'];
        $price = (float)$item['price'];

        $item_stmt->bind_param("iiid", $order_id, $variant_id, $qty, $price);
        $item_stmt->execute();

        $stock_stmt->bind_param("ii", $qty, $variant_id);
        $stock_stmt->execute();
    }

    $payment_status = ($payment_method === "CARD") ? "PAID" : "PENDING";

    $payment_stmt = $conn->prepare("
        INSERT INTO payments
        (order_id, method, status)
        VALUES (?, ?, ?)
    ");

    $payment_stmt->bind_param("iss", $order_id, $payment_method, $payment_status);
    $payment_stmt->execute();

    $clear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear->bind_param("i", $user_id);
    $clear->execute();

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "order_id" => $order_id,
        "order_number" => $order_number
    ]);
    exit;

} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}
?>