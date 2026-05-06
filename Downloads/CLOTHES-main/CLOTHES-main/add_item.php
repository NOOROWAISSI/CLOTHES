<?php
global $conn;
include "db.php";

function clean($value) {
    return trim((string)$value);
}

$product_id = (int)($_POST['existing_product_id'] ?? 0);

$category_id = (int)($_POST['category_id'] ?? 0);
$collection_id = !empty($_POST['collection_id']) ? (int)$_POST['collection_id'] : null;

$product_name = clean($_POST['product_name'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$color = clean($_POST['color'] ?? '');
$sizes = $_POST['sizes'] ?? [];
$quantity = (int)($_POST['quantity'] ?? 0);

$has_discount = isset($_POST['has_discount']) ? 1 : 0;
$discount_percent = (float)($_POST['discount_percent'] ?? 0);
$discount_label = clean($_POST['discount_label'] ?? 'SALE');

if ($quantity < 0) $quantity = 0;
if ($discount_percent < 0) $discount_percent = 0;
if ($discount_percent > 90) $discount_percent = 90;
if ($discount_label === '') $discount_label = 'SALE';

if ($color === '') {
    die("Color is required.");
}

if (empty($sizes)) {
    die("Choose at least one size.");
}

/* Upload image from laptop */
if (!isset($_FILES['variant_image']) || $_FILES['variant_image']['error'] !== UPLOAD_ERR_OK) {
    die("Image is required.");
}

$uploadDir = __DIR__ . "/pic/uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = strtolower(pathinfo($_FILES['variant_image']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowed)) {
    die("Only JPG, JPEG, PNG, WEBP are allowed.");
}

$imageName = "product_" . time() . "_" . rand(1000, 9999) . "." . $ext;
$serverPath = $uploadDir . $imageName;
$dbPath = "pic/uploads/" . $imageName;

if (!move_uploaded_file($_FILES['variant_image']['tmp_name'], $serverPath)) {
    die("Image upload failed.");
}

/* New Product */
if ($product_id <= 0) {
    if ($product_name === '') {
        die("Product name is required for new product.");
    }

    if ($collection_id === null) {
        $stmt = $conn->prepare("
            INSERT INTO products 
            (price, category_id, collection_id, has_discount, discount_percent, discount_label)
            VALUES (?, ?, NULL, ?, ?, ?)
        ");
        $stmt->bind_param("diids", $price, $category_id, $has_discount, $discount_percent, $discount_label);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO products 
            (price, category_id, collection_id, has_discount, discount_percent, discount_label)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("diiids", $price, $category_id, $collection_id, $has_discount, $discount_percent, $discount_label);
    }

    $stmt->execute();
    $product_id = $stmt->insert_id;
    $stmt->close();

    $desc = "Product description";

    $stmt = $conn->prepare("
        INSERT INTO product_translations 
        (product_id, language_code, product_name, description)
        VALUES
        (?, 'en', ?, ?),
        (?, 'ar', ?, ?),
        (?, 'he', ?, ?)
    ");

    $stmt->bind_param(
        "issississ",
        $product_id, $product_name, $desc,
        $product_id, $product_name, $desc,
        $product_id, $product_name, $desc
    );

    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("
        INSERT INTO product_images (product_id, image_url, is_main)
        VALUES (?, ?, 1)
    ");
    $stmt->bind_param("is", $product_id, $dbPath);
    $stmt->execute();
    $stmt->close();
}

/* Add selected sizes as variants */
$stmt = $conn->prepare("
    INSERT INTO product_variants 
    (product_id, color, size, quantity, variant_image_url)
    VALUES (?, ?, ?, ?, ?)
");

foreach ($sizes as $size) {
    $size = clean($size);

    if ($size === '') {
        continue;
    }

    $stmt->bind_param("issis", $product_id, $color, $size, $quantity, $dbPath);

    if (!$stmt->execute()) {
        continue;
    }
}

$stmt->close();

header("Location: admin.php#inventory");
exit;
?>