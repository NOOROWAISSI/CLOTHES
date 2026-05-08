<?php
if (
        !isset($_SESSION['admin_id']) ||
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'admin'
) {
    header("Location: signin.php");
    exit;
}

global $conn;
include "db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statusBadgeClass($status) {
    if ($status === 'DONE') return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
    if ($status === 'NEW') return 'bg-blue-100 text-blue-700 border border-blue-200';
    if ($status === 'CANCELLED') return 'bg-red-100 text-red-700 border border-red-200';
    return 'bg-zinc-100 text-zinc-500 border border-zinc-200';
}

function statusDotClass($status) {
    if ($status === 'DONE') return 'bg-emerald-500';
    if ($status === 'NEW') return 'bg-blue-500';
    if ($status === 'CANCELLED') return 'bg-red-500';
    return 'bg-zinc-500';
}

function statusLabel($status) {
    if ($status === 'DONE') return 'Completed';
    if ($status === 'NEW') return 'New / Pending';
    if ($status === 'CANCELLED') return 'Cancelled';
    return $status;
}

function colorToCss($color) {
    $c = strtolower(trim((string)$color));
    $map = [
            'black' => '#000000',
            'white' => '#ffffff',
            'red' => '#ef4444',
            'blue' => '#3b82f6',
            'navy' => '#1e3a8a',
            'navy blue' => '#1e3a8a',
            'pink' => '#ec4899',
            'green' => '#22c55e',
            'gray' => '#71717a',
            'grey' => '#71717a',
            'charcoal' => '#3f3f46',
            'brown' => '#92400e',
            'beige' => '#d6b98c',
            'purple' => '#a855f7',
            'yellow' => '#eab308',
            'gold' => '#fbbf24',
            'light blue' => '#93c5fd',
            'dark blue' => '#1e40af',
    ];
    return $map[$c] ?? '#777777';
}

/* ================= POST ACTIONS ================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['order_status'];

    if (in_array($new_status, ['NEW', 'DONE', 'CANCELLED'])) {
        $stmt = $conn->prepare("UPDATE orders SET order_status=? WHERE order_id=?");
        $stmt->bind_param("si", $new_status, $order_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin.php#orders");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    $variant_id = intval($_POST['variant_id']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);

    if ($quantity < 0) $quantity = 0;

    $stmt = $conn->prepare("UPDATE products SET price=? WHERE product_id=?");
    $stmt->bind_param("di", $price, $product_id);
    $stmt->execute();
    $stmt->close();

    if ($variant_id > 0) {
        $stmt = $conn->prepare("UPDATE product_variants SET quantity=? WHERE variant_id=?");
        $stmt->bind_param("ii", $quantity, $variant_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin.php#inventory");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);

    $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php#inventory");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_discount'])) {
    $product_id = intval($_POST['product_id']);
    $has_discount = isset($_POST['has_discount']) ? 1 : 0;
    $discount_percent = floatval($_POST['discount_percent']);
    $discount_label = trim($_POST['discount_label']);

    if ($discount_percent < 0) $discount_percent = 0;
    if ($discount_percent > 90) $discount_percent = 90;
    if ($discount_label === '') $discount_label = 'SALE';

    if ($has_discount === 0) {
        $discount_percent = 0;
        $discount_label = 'SALE';
    }

    $stmt = $conn->prepare("
        UPDATE products
        SET has_discount=?, discount_percent=?, discount_label=?
        WHERE product_id=?
    ");
    $stmt->bind_param("idsi", $has_discount, $discount_percent, $discount_label, $product_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php#inventory");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_collection'])) {
    $collection_key = trim($_POST['collection_key']);
    $collection_name = trim($_POST['collection_name']);
    $description = trim($_POST['description']);

    if ($collection_key !== '' && $collection_name !== '') {
        $stmt = $conn->prepare("INSERT INTO collections (collection_key) VALUES (?)");
        $stmt->bind_param("s", $collection_key);
        $stmt->execute();
        $collection_id = $stmt->insert_id;
        $stmt->close();

        $lang = 'en';
        $stmt = $conn->prepare("
            INSERT INTO collection_translations (collection_id, language_code, collection_name, description)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $collection_id, $lang, $collection_name, $description);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin.php#collections");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_collection'])) {
    $collection_id = intval($_POST['collection_id']);

    $stmt = $conn->prepare("DELETE FROM collections WHERE collection_id=?");
    $stmt->bind_param("i", $collection_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php#collections");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_collection_new'])) {
    $collection_id = intval($_POST['collection_id']);
    $is_new = isset($_POST['is_new']) ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE collections
        SET is_new=?
        WHERE collection_id=?
    ");
    $stmt->bind_param("ii", $is_new, $collection_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php#collections");
    exit();
}

/* ================= STATS ================= */

$totalRevenue = $conn->query("
    SELECT COALESCE(SUM(total_price),0) AS total
    FROM orders
    WHERE order_status='DONE'
")->fetch_assoc()['total'];

$totalOrders = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$totalProducts = $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$totalCustomers = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$totalCollections = $conn->query("SELECT COUNT(*) AS c FROM collections")->fetch_assoc()['c'];

$newOrders = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status='NEW'")->fetch_assoc()['c'];
$doneOrders = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status='DONE'")->fetch_assoc()['c'];
$cancelledOrders = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status='CANCELLED'")->fetch_assoc()['c'];

$totalStock = $conn->query("SELECT COALESCE(SUM(quantity),0) AS total FROM product_variants")->fetch_assoc()['total'];

/* ================= HEALTH SYSTEM ================= */

$stockPercent = 0;

if ($totalProducts > 0) {
    $stockPercent = min(100, ($totalStock / ($totalProducts * 10)) * 100);
}

$orderSuccessPercent = 0;

if ($totalOrders > 0) {
    $orderSuccessPercent = ($doneOrders / $totalOrders) * 100;
}

$catalogPercent = min(100, ($totalProducts * 5) + ($totalCollections * 10));

$health = round(
        ($stockPercent * 0.4) +
        ($orderSuccessPercent * 0.4) +
        ($catalogPercent * 0.2)
);

if ($health > 100) {
    $health = 100;
}

$gaugeOffset = 126 - (126 * $health / 100);

$collectionsPercent = 0;
if ($totalProducts > 0) {
    $collectionsPercent = min(100, ($totalCollections / max(1, $totalProducts)) * 100);
}

/* ================= DASHBOARD CHARTS FROM REAL DATA ================= */

/* Revenue by order status */
$orderStatusLabels = ['NEW', 'DONE', 'CANCELLED'];
$orderStatusValues = [
        (int)$newOrders,
        (int)$doneOrders,
        (int)$cancelledOrders
];

/* Revenue from DONE orders */
$revenueLabels = [];
$revenueValues = [];

$revenueQuery = $conn->query("
    SELECT 
        CONCAT('Order #', order_id) AS label,
        COALESCE(total_price,0) AS total
    FROM orders
    WHERE order_status='DONE'
    ORDER BY order_id DESC
    LIMIT 6
");

if ($revenueQuery) {
    while ($row = $revenueQuery->fetch_assoc()) {
        $revenueLabels[] = $row['label'];
        $revenueValues[] = (float)$row['total'];
    }
}

$revenueLabels = array_reverse($revenueLabels);
$revenueValues = array_reverse($revenueValues);

if (count($revenueLabels) === 0) {
    $revenueLabels = ['No completed orders'];
    $revenueValues = [1];
}

/* Products by category */
$categoryLabels = [];
$categoryValues = [];

$categoryChartQuery = $conn->query("
    SELECT
        COALESCE(ct.category_name, c.category_key) AS category_name,
        COUNT(p.product_id) AS total_products
    FROM categories c
    LEFT JOIN category_translations ct 
        ON c.category_id = ct.category_id AND ct.language_code='en'
    LEFT JOIN products p 
        ON c.category_id = p.category_id
    GROUP BY c.category_id
    HAVING total_products > 0
    ORDER BY total_products DESC
    LIMIT 8
");

if ($categoryChartQuery) {
    while ($row = $categoryChartQuery->fetch_assoc()) {
        $categoryLabels[] = $row['category_name'];
        $categoryValues[] = (int)$row['total_products'];
    }
}

if (count($categoryLabels) === 0) {
    $categoryLabels = ['No products'];
    $categoryValues = [1];
}

/* Stock by status */
$stockIn = 0;
$stockOut = 0;

$stockQuery = $conn->query("
    SELECT
        SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) AS in_stock,
        SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) AS sold_out
    FROM product_variants
");

if ($stockQuery) {
    $stockRow = $stockQuery->fetch_assoc();
    $stockIn = (int)($stockRow['in_stock'] ?? 0);
    $stockOut = (int)($stockRow['sold_out'] ?? 0);
}

$stockLabels = ['In Stock', 'Sold Out'];
$stockValues = [$stockIn, $stockOut];

if ($stockIn + $stockOut === 0) {
    $stockValues = [1, 0];
}

/* ================= FILTER VALUES ================= */

$filter_search = strtolower(trim($_GET['search'] ?? ''));
$filter_collection = $_GET['collection'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_color = strtolower(trim($_GET['color'] ?? ''));
$filter_size = strtoupper(trim($_GET['size'] ?? ''));
$filter_stock = $_GET['stock'] ?? '';

$where = [];

if ($filter_search !== '') {
    $safe = $conn->real_escape_string($filter_search);
    $where[] = "LOWER(COALESCE(pt.product_name, CONCAT('Product #', p.product_id))) LIKE '%$safe%'";
}

if ($filter_collection !== '') {
    $where[] = "p.collection_id = " . intval($filter_collection);
}

if ($filter_category !== '') {
    $where[] = "p.category_id = " . intval($filter_category);
}

if ($filter_color !== '') {
    $safe = $conn->real_escape_string($filter_color);
    $where[] = "LOWER(pv.color) = '$safe'";
}

if ($filter_size !== '') {
    $safe = $conn->real_escape_string($filter_size);
    $where[] = "UPPER(pv.size) = '$safe'";
}

if ($filter_stock === 'in') {
    $where[] = "COALESCE(pv.quantity, 0) > 0";
}

if ($filter_stock === 'out') {
    $where[] = "COALESCE(pv.quantity, 0) <= 0";
}

$where_sql = "";
if (!empty($where)) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}

/* ================= QUERIES ================= */

$categoriesForSelect = $conn->query("
    SELECT 
        c.category_id,
        COALESCE(ct.category_name, CONCAT('Category #', c.category_id)) AS category_name
    FROM categories c
    LEFT JOIN category_translations ct 
        ON c.category_id = ct.category_id AND ct.language_code='en'
    ORDER BY c.category_id ASC
");

$collectionsForSelect = $conn->query("
    SELECT
        c.collection_id,
        COALESCE(ct.collection_name, c.collection_key) AS collection_name
    FROM collections c
    LEFT JOIN collection_translations ct 
        ON c.collection_id = ct.collection_id AND ct.language_code='en'
    ORDER BY c.collection_id DESC
");

$productsForSelect = $conn->query("
    SELECT 
        p.product_id,
        COALESCE(pt.product_name, CONCAT('Product #', p.product_id)) AS product_name
    FROM products p
    LEFT JOIN product_translations pt 
        ON p.product_id = pt.product_id AND pt.language_code='en'
    ORDER BY p.product_id DESC
");

$productsResult = $conn->query("
    SELECT
        p.product_id,
        p.price,
        COALESCE(p.has_discount,0) AS has_discount,
        COALESCE(p.discount_percent,0) AS discount_percent,
        COALESCE(p.discount_label,'SALE') AS discount_label,
        COALESCE(pt.product_name, CONCAT('Product #', p.product_id)) AS product_name,

        COALESCE(ct.category_name, 'No Type') AS category_name,
        COALESCE(p.category_id, 0) AS category_id,

        COALESCE(clt.collection_name, 'No Collection') AS collection_name,
        COALESCE(p.collection_id, 0) AS collection_id,

        COALESCE(pv.variant_id, 0) AS variant_id,
        COALESCE(pv.color, '-') AS color,
        COALESCE(pv.size, '-') AS size,
        COALESCE(pv.quantity, 0) AS quantity,
        COALESCE(pv.variant_image_url, '') AS variant_image_url,
        COALESCE(pi.image_url, '') AS image_url

    FROM products p

    LEFT JOIN product_translations pt 
        ON p.product_id = pt.product_id AND pt.language_code='en'

    LEFT JOIN categories c 
        ON p.category_id = c.category_id

    LEFT JOIN category_translations ct 
        ON c.category_id = ct.category_id AND ct.language_code='en'

    LEFT JOIN collections cl
        ON p.collection_id = cl.collection_id

    LEFT JOIN collection_translations clt
        ON cl.collection_id = clt.collection_id AND clt.language_code='en'

    LEFT JOIN product_variants pv 
        ON p.product_id = pv.product_id

    LEFT JOIN product_images pi 
        ON p.product_id = pi.product_id AND pi.is_main=1

    $where_sql

    ORDER BY p.product_id DESC, pv.variant_id DESC
");

$collectionsResult = $conn->query("
    SELECT
        c.collection_id,
        c.collection_key,
        COALESCE(c.is_new,0) AS is_new,
        COALESCE(ct.collection_name, c.collection_key) AS collection_name,
        COALESCE(ct.description, '') AS description,
        COUNT(p.product_id) AS items_count
    FROM collections c
    LEFT JOIN collection_translations ct 
        ON c.collection_id = ct.collection_id AND ct.language_code='en'
    LEFT JOIN products p 
        ON c.collection_id = p.collection_id
    GROUP BY c.collection_id
    ORDER BY c.collection_id DESC
");

$customersResult = $conn->query("
    SELECT 
        u.*,
        COUNT(o.order_id) AS orders_count
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    GROUP BY u.user_id
    ORDER BY u.user_id DESC
");

$ordersResult = $conn->query("
    SELECT 
        o.order_id,
        o.user_id,
        o.area_id,
        COALESCE(o.subtotal,0) AS subtotal,
        COALESCE(o.delivery_price,0) AS delivery_price,
        COALESCE(o.total_price,0) AS total_price,
        o.order_status,
        COALESCE(u.full_name, 'Unknown Customer') AS full_name,
        COALESCE(u.email, '-') AS email,
        COALESCE(da.city, '-') AS city,
        COALESCE(da.area_name, '-') AS area_name,
        GROUP_CONCAT(
            CONCAT(
                COALESCE(pt.product_name, 'Product'),
                '||',
                COALESCE(ct.category_name, 'No Type'),
                '||',
                COALESCE(pv.color, '-'),
                '||',
                COALESCE(pv.size, '-'),
                '||',
                COALESCE(oi.quantity, 1)
            )
            SEPARATOR '##'
        ) AS items
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN delivery_areas da ON o.area_id = da.area_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
    LEFT JOIN products p ON pv.product_id = p.product_id
    LEFT JOIN product_translations pt 
        ON p.product_id = pt.product_id AND pt.language_code='en'
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN category_translations ct 
        ON c.category_id = ct.category_id AND ct.language_code='en'
    GROUP BY o.order_id
    ORDER BY o.order_id DESC
");

$activityResult = $conn->query("
    SELECT 'order' AS type, CONCAT('Order #', order_id) AS title, order_id AS ref_id
    FROM orders
    UNION ALL
    SELECT 'product' AS type, COALESCE(pt.product_name, CONCAT('Product #', p.product_id)) AS title, p.product_id AS ref_id
    FROM products p
    LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.language_code='en'
    ORDER BY ref_id DESC
    LIMIT 5
");
?>
<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demoiselle — Admin</title>

    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        html, body { height: 100%; margin: 0; }
        * { box-sizing: border-box; }

        .app-shell {
            height: 100%;
            overflow: auto;
            background: #f7f7f7;
            color: #111;
            font-family: 'DM Sans', sans-serif;
        }

        .font-display { font-family: 'Playfair Display', serif; }

        aside { background: #fff; color: #111; border-color: #ddd !important; }

        .logo-img { width: 70px; height: 70px; object-fit: contain; }

        .card-hover {
            transition: all 0.3s ease;
            border: 1px solid #ddd;
            background: #fff;
            color: #111;
        }

        .card-hover:hover { border-color: #333; transform: translateY(-2px); }

        .btn-primary { background: #000; color: #fff; transition: all 0.2s; }
        .btn-primary:hover { background: #333; }

        .btn-danger {
            background: #fff;
            color: #111;
            border: 1px solid #ddd;
            transition: all 0.2s;
        }

        .btn-danger:hover { border-color: #111; color: #111; }

        .btn-ghost {
            background: transparent;
            color: #555;
            border: 1px solid #ddd;
            transition: all 0.2s;
        }

        .btn-ghost:hover { color: #111; border-color: #111; }

        .tab-btn {
            position: relative;
            padding: 12px 0;
            color: #555;
            transition: color 0.3s;
            cursor: pointer;
            background: none;
            border: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .tab-btn::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 1px;
            background: #111;
            transition: width 0.3s;
        }

        .tab-btn.active { color: #111; }
        .tab-btn.active::after { width: 100%; }

        .input-noir {
            background: #fff;
            border: 1px solid #ddd;
            color: #111;
            padding: 10px 14px;
            border-radius: 6px;
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.2s;
            width: 100%;
        }

        .input-noir:focus { outline: none; border-color: #111; }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .clothes-img {
            width: 100%;
            aspect-ratio: 3/4;
            background: linear-gradient(135deg, #f7f7f7 0%, #eee 50%, #ddd 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .stat-card { position: relative; overflow: hidden; border-left: 4px solid #111; }

        .order-row { transition: background 0.2s; }
        .order-row:hover { background: #f3f3f3; }

        .gauge-ring { transition: stroke-dashoffset 1s ease; }

        .status-filter-hidden { display: none; }

        .color-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
            border: 1px solid rgba(0,0,0,.25);
        }

        .chart-box {
            height: 180px;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .sale-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 20;
            background: #dc2626;
            color: white;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 10px;
            border-radius: 999px;
            box-shadow: 0 10px 25px rgba(0,0,0,.25);
            letter-spacing: .5px;
        }

        .soldout-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 20;
            background: #111;
            color: #fff;
            border: 1px solid #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 10px;
            border-radius: 999px;
            letter-spacing: .5px;
        }

        .soldout-img { filter: grayscale(100%) brightness(0.75); }
    </style>
</head>

<body class="h-full">
<div class="app-shell" id="app">
    <div class="flex h-full">
        <aside class="w-64 border-r border-zinc-200 flex-shrink-0 flex flex-col" style="min-height:100%">
            <div class="p-6 border-b border-zinc-200 flex items-center justify-between">
                <div class="flex items-center gap-3 mb-1">
                    <img src="pic/lolo1.png" alt="Demoiselle Logo" class="logo-img" id="logoImg">
                    <div>
                        <div class="font-display font-black text-lg tracking-tight leading-none">Demoiselle</div>
                        <div class="text-[10px] tracking-[3px] text-zinc-500 uppercase">Admin Panel</div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-4 space-y-1">
                <button onclick="switchSection('dashboard')" class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded text-sm text-left transition-all hover:bg-zinc-100" data-section="dashboard">
                    <i data-lucide="layout-dashboard" style="width:16px;height:16px"></i> Dashboard
                </button>
                <button onclick="switchSection('inventory')" class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded text-sm text-left transition-all hover:bg-zinc-100" data-section="inventory">
                    <i data-lucide="shirt" style="width:16px;height:16px"></i> Inventory
                </button>
                <button onclick="switchSection('collections')" class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded text-sm text-left transition-all hover:bg-zinc-100" data-section="collections">
                    <i data-lucide="layers" style="width:16px;height:16px"></i> Collections
                </button>
                <button onclick="switchSection('orders')" class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded text-sm text-left transition-all hover:bg-zinc-100" data-section="orders">
                    <i data-lucide="shopping-bag" style="width:16px;height:16px"></i> Orders
                </button>
                <button onclick="switchSection('customers')" class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded text-sm text-left transition-all hover:bg-zinc-100" data-section="customers">
                    <i data-lucide="users" style="width:16px;height:16px"></i> Customers
                </button>
            </nav>

            <div class="p-5 border-t border-zinc-200">
                <div class="text-[10px] tracking-[2px] text-zinc-500 uppercase mb-3">Shop Pulse</div>
                <div class="flex items-center justify-center">
                    <svg width="100" height="60" viewbox="0 0 100 60">
                        <path d="M10 55 A 40 40 0 0 1 90 55" fill="none" stroke="#e5e5e5" stroke-width="6" stroke-linecap="round" />
                        <path id="gaugeArc" d="M10 55 A 40 40 0 0 1 90 55" fill="none" stroke="#111" stroke-width="6" stroke-linecap="round" stroke-dasharray="126" stroke-dashoffset="<?= $gaugeOffset ?>" class="gauge-ring" />
                        <text x="50" y="48" text-anchor="middle" fill="#111" font-size="16" font-weight="700" font-family="Playfair Display"><?= $health ?>%</text>
                        <text x="50" y="58" text-anchor="middle" fill="#777" font-size="7" font-family="DM Sans" letter-spacing="1">HEALTH</text>
                    </svg>
                </div>
            </div>
        </aside>

        <main class="flex-1 overflow-auto p-8">
            <section id="sec-dashboard" class="section-panel">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <h1 class="font-display text-4xl font-black tracking-tight">Dashboard</h1>
                        <p class="text-zinc-500 text-sm mt-1">Your empire at a glance</p>
                    </div>
                    <div class="text-right text-xs text-zinc-600" id="dashDate"></div>
                </div>

                <div class="grid grid-cols-5 gap-4 mb-8">
                    <div class="stat-card card-hover rounded-lg p-5" style="border-left-color:#fbbf24">
                        <div class="text-[10px] tracking-[2px] text-zinc-500 uppercase mb-2">Revenue</div>
                        <div class="font-display text-3xl font-black" style="color:#fbbf24">$<?= number_format($totalRevenue, 2) ?></div>
                    </div>

                    <div class="stat-card card-hover rounded-lg p-5" style="border-left-color:#3b82f6">
                        <div class="text-[10px] tracking-[2px] text-zinc-500 uppercase mb-2">Orders</div>
                        <div class="font-display text-3xl font-black" style="color:#3b82f6"><?= $totalOrders ?></div>
                    </div>

                    <div class="stat-card card-hover rounded-lg p-5" style="border-left-color:#ec4899">
                        <div class="text-[10px] tracking-[2px] text-zinc-500 uppercase mb-2">Products</div>
                        <div class="font-display text-3xl font-black" style="color:#ec4899"><?= $totalProducts ?></div>
                    </div>

                    <div class="stat-card card-hover rounded-lg p-5" style="border-left-color:#10b981">
                        <div class="text-[10px] tracking-[2px] text-zinc-500 uppercase mb-2">Customers</div>
                        <div class="font-display text-3xl font-black" style="color:#10b981"><?= $totalCustomers ?></div>
                    </div>

                    <div class="stat-card card-hover rounded-lg p-5" style="border-left-color:#00d4ff">
                        <div class="text-[10px] tracking-[2px] text-zinc-500 uppercase mb-2">Collections</div>
                        <div class="font-display text-3xl font-black" style="color:#00a8cc"><?= $totalCollections ?></div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 mt-8">
                    <div class="card-hover rounded-lg p-6">
                        <div class="text-[10px] tracking-[3px] text-zinc-500 uppercase mb-4">
                            Revenue From Completed Orders
                        </div>
                        <div class="chart-box">
                            <canvas id="revenueChart"></canvas>
                        </div>
                        <div class="text-xs text-zinc-500 mt-2">
                            Based on DONE orders total_price
                        </div>
                    </div>

                    <div class="card-hover rounded-lg p-6">
                        <div class="text-[10px] tracking-[3px] text-zinc-500 uppercase mb-4">
                            Products By Category
                        </div>
                        <div class="chart-box">
                            <canvas id="categoryChart"></canvas>
                        </div>
                        <div class="text-xs text-zinc-500 mt-2">
                            Count of products in each category
                        </div>
                    </div>

                    <div class="card-hover rounded-lg p-6">
                        <div class="text-[10px] tracking-[3px] text-zinc-500 uppercase mb-4">
                            Stock Status
                        </div>
                        <div class="chart-box">
                            <canvas id="stockChart"></canvas>
                        </div>
                        <div class="text-xs text-zinc-500 mt-2">
                            Based on product variants quantity
                        </div>
                    </div>
                </div>
            </section>

            <section id="sec-inventory" class="section-panel hidden">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <h1 class="font-display text-4xl font-black tracking-tight">Inventory</h1>
                        <p class="text-zinc-500 text-sm mt-1">Manage your garments</p>
                    </div>

                    <button onclick="openModal('addItem')" class="btn-primary px-5 py-2.5 rounded text-sm font-medium flex items-center gap-2">
                        <i data-lucide="plus" style="width:14px;height:14px"></i> Add Item
                    </button>
                </div>

                <form method="GET" action="admin.php#inventory" class="card-hover rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                        <input class="input-noir text-sm" name="search" placeholder="Search item..." value="<?= h($filter_search) ?>">

                        <select class="input-noir text-sm" name="category">
                            <option value="">All Categories</option>
                            <?php
                            $catFilter = $conn->query("
                                SELECT c.category_id, COALESCE(ct.category_name, c.category_key) AS category_name
                                FROM categories c
                                LEFT JOIN category_translations ct ON c.category_id = ct.category_id AND ct.language_code='en'
                                ORDER BY category_name
                            ");
                            while($cat = $catFilter->fetch_assoc()):
                                ?>
                                <option value="<?= h($cat['category_id']) ?>" <?= $filter_category == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= h($cat['category_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <select class="input-noir text-sm" name="collection">
                            <option value="">All Collections</option>
                            <?php
                            $colFilter = $conn->query("
                                SELECT c.collection_id, COALESCE(ct.collection_name, c.collection_key) AS collection_name
                                FROM collections c
                                LEFT JOIN collection_translations ct ON c.collection_id = ct.collection_id AND ct.language_code='en'
                                ORDER BY collection_name
                            ");
                            while($col = $colFilter->fetch_assoc()):
                                ?>
                                <option value="<?= h($col['collection_id']) ?>" <?= $filter_collection == $col['collection_id'] ? 'selected' : '' ?>>
                                    <?= h($col['collection_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <select class="input-noir text-sm" name="size">
                            <option value="">All Sizes</option>
                            <?php foreach(['XS','S','M','L','XL','XXL'] as $sz): ?>
                                <option value="<?= $sz ?>" <?= $filter_size === $sz ? 'selected' : '' ?>><?= $sz ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select class="input-noir text-sm" name="color">
                            <option value="">All Colors</option>
                            <?php
                            $colorFilter = $conn->query("
                                SELECT DISTINCT color FROM product_variants
                                WHERE color IS NOT NULL AND color <> ''
                                ORDER BY color
                            ");
                            while($co = $colorFilter->fetch_assoc()):
                                $cv = strtolower($co['color']);
                                ?>
                                <option value="<?= h($cv) ?>" <?= $filter_color === $cv ? 'selected' : '' ?>>
                                    <?= h($co['color']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <select class="input-noir text-sm" name="stock">
                            <option value="">All Stock</option>
                            <option value="in" <?= $filter_stock === 'in' ? 'selected' : '' ?>>In Stock</option>
                            <option value="out" <?= $filter_stock === 'out' ? 'selected' : '' ?>>Sold Out</option>
                        </select>
                    </div>

                    <div class="flex gap-3 mt-3">
                        <button class="btn-primary px-4 py-2 rounded text-sm">Filter</button>
                        <a href="admin.php#inventory" class="btn-ghost px-4 py-2 rounded text-sm">Reset</a>
                    </div>
                </form>

                <div id="inventoryGrid" class="grid grid-cols-3 gap-5">
                    <?php if ($productsResult && $productsResult->num_rows > 0): ?>
                        <?php while($p = $productsResult->fetch_assoc()): ?>
                            <?php
                            $img = !empty($p['variant_image_url']) ? $p['variant_image_url'] : $p['image_url'];
                            $oldPrice = (float)$p['price'];
                            $discountPercent = (float)($p['discount_percent'] ?? 0);
                            $newPrice = $oldPrice;

                            if ((int)$p['has_discount'] === 1 && $discountPercent > 0) {
                                $newPrice = $oldPrice - ($oldPrice * $discountPercent / 100);
                            }
                            ?>
                            <div class="card-hover rounded-lg overflow-hidden relative">
                                <?php if ((int)$p['has_discount'] === 1): ?>
                                    <div class="sale-badge"><?= h($p['discount_label']) ?> <?= h($p['discount_percent']) ?>%</div>
                                <?php endif; ?>

                                <?php if ((int)$p['quantity'] <= 0): ?>
                                    <div class="soldout-badge">SOLD OUT</div>
                                <?php endif; ?>

                                <div class="clothes-img">
                                    <?php if (!empty($img)): ?>
                                        <img src="<?= h($img) ?>" alt="<?= h($p['product_name']) ?>" class="w-full h-full object-cover <?= (int)$p['quantity'] <= 0 ? 'soldout-img' : '' ?>">
                                    <?php else: ?>
                                        <i data-lucide="shirt" style="width:52px;height:52px" class="text-zinc-400"></i>
                                    <?php endif; ?>
                                </div>

                                <div class="p-4">
                                    <div class="flex justify-between items-start mb-1">
                                        <h3 class="font-display font-bold text-sm"><?= h($p['product_name']) ?></h3>
                                        <span class="text-xs text-zinc-500 bg-zinc-100 border border-zinc-200 px-2 py-1 rounded"><?= h($p['category_name']) ?></span>
                                    </div>

                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <span class="text-xs text-zinc-500 bg-zinc-50 border border-zinc-200 px-2 py-1 rounded"><?= h($p['collection_name']) ?></span>
                                        <span class="text-xs text-zinc-500 bg-zinc-50 border border-zinc-200 px-2 py-1 rounded">Product #<?= h($p['product_id']) ?></span>
                                    </div>

                                    <div class="text-zinc-500 text-xs mb-3 flex gap-2 flex-wrap items-center">
                                        <span class="color-dot" style="background:<?= h(colorToCss($p['color'])) ?>"></span>
                                        <span><?= h($p['color']) ?></span>
                                        <span>·</span>
                                        <span><?= h($p['size']) ?></span>
                                        <span>·</span>
                                        <span>Variant #<?= h($p['variant_id']) ?></span>

                                        <?php if ((int)$p['quantity'] <= 0): ?>
                                            <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-[10px] font-bold">SOLD OUT</span>
                                        <?php else: ?>
                                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-[10px] font-bold">IN STOCK</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ((int)$p['has_discount'] === 1): ?>
                                        <div class="mb-3">
                                            <span class="line-through text-zinc-500 text-sm">$<?= number_format($oldPrice, 2) ?></span>
                                            <span class="font-bold text-red-500 ml-2">$<?= number_format($newPrice, 2) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="font-bold mb-3">$<?= number_format($oldPrice, 2) ?></div>
                                    <?php endif; ?>

                                    <form method="POST" class="space-y-2 mb-3">
                                        <input type="hidden" name="product_id" value="<?= h($p['product_id']) ?>">
                                        <input type="hidden" name="variant_id" value="<?= h($p['variant_id']) ?>">

                                        <div class="grid grid-cols-2 gap-2">
                                            <input class="input-noir text-xs py-1" name="price" type="number" step="0.01" value="<?= h($p['price']) ?>">
                                            <input class="input-noir text-xs py-1" name="quantity" type="number" min="0" value="<?= h($p['quantity']) ?>">
                                        </div>

                                        <button name="update_product" class="btn-primary px-3 py-1 rounded text-xs w-full">
                                            Update Price / Qty
                                        </button>
                                    </form>

                                    <form method="POST" class="space-y-2 mb-3 border border-zinc-200 rounded p-2 bg-zinc-50">
                                        <input type="hidden" name="product_id" value="<?= h($p['product_id']) ?>">

                                        <label class="flex items-center gap-2 text-xs">
                                            <input type="checkbox" name="has_discount" <?= (int)$p['has_discount'] === 1 ? 'checked' : '' ?>>
                                            Has Discount
                                        </label>

                                        <input class="input-noir text-xs py-1" name="discount_percent" type="number" step="0.01" min="0" max="90" placeholder="Discount %" value="<?= h($p['discount_percent']) ?>">
                                        <input class="input-noir text-xs py-1" name="discount_label" placeholder="SALE / OFFER" value="<?= h($p['discount_label']) ?>">

                                        <button name="update_discount" class="btn-primary px-3 py-1 rounded text-xs w-full">
                                            Save Discount
                                        </button>
                                    </form>

                                    <form method="POST" onsubmit="return confirm('Delete this item?');">
                                        <input type="hidden" name="product_id" value="<?= h($p['product_id']) ?>">
                                        <button name="delete_product" class="btn-danger px-3 py-1 rounded text-xs w-full">
                                            Delete Item
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-zinc-600 text-sm italic col-span-3 text-center py-20">
                            No matching items found.
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="sec-collections" class="section-panel hidden">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <h1 class="font-display text-4xl font-black tracking-tight">Collections</h1>
                        <p class="text-zinc-500 text-sm mt-1">Curate your drops</p>
                    </div>

                    <button onclick="openModal('addCollection')" class="btn-primary px-5 py-2.5 rounded text-sm font-medium flex items-center gap-2">
                        <i data-lucide="plus" style="width:14px;height:14px"></i> New Collection
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <?php if ($collectionsResult && $collectionsResult->num_rows > 0): ?>
                        <?php while($col = $collectionsResult->fetch_assoc()): ?>
                            <div class="card-hover rounded-lg p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <div>
                                            <h3 class="font-display text-xl font-bold">
                                                <?= h($col['collection_name']) ?>
                                            </h3>

                                            <?php if ((int)$col['is_new'] === 1): ?>
                                                <span class="inline-block mt-2 bg-black text-white text-[10px] px-2 py-1 rounded-full font-bold">
            NEW
        </span>
                                            <?php endif; ?>

                                            <p class="text-zinc-500 text-xs mt-1">
                                                <?= h($col['items_count']) ?> item<?= $col['items_count'] != 1 ? 's' : '' ?>
                                            </p>
                                        </div>

                                        <p class="text-zinc-500 text-xs mt-1"><?= h($col['items_count']) ?> item<?= $col['items_count'] != 1 ? 's' : '' ?></p>
                                    </div>

                                    <form method="POST" onsubmit="return confirm('Delete this collection?');">
                                        <input type="hidden" name="collection_id" value="<?= h($col['collection_id']) ?>">
                                        <button name="delete_collection" class="btn-danger px-3 py-1 rounded text-xs">Delete</button>
                                    </form>
                                </div>

                                <div class="h-px bg-zinc-200 mb-3"></div>
                                <div class="text-zinc-600 text-xs italic">
                                    <?= !empty($col['description']) ? h($col['description']) : 'No description yet' ?>
                                </div>
                                <form method="POST" class="mt-4 border border-zinc-200 rounded p-2 bg-zinc-50">
                                    <input type="hidden" name="collection_id" value="<?= h($col['collection_id']) ?>">

                                    <label class="flex items-center gap-2 text-xs">
                                        <input type="checkbox" name="is_new" <?= (int)$col['is_new'] === 1 ? 'checked' : '' ?>>
                                        Show this collection as NEW on Home
                                    </label>

                                    <button name="update_collection_new" class="btn-primary px-3 py-1 rounded text-xs w-full mt-2">
                                        Save NEW Status
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-zinc-600 text-sm italic col-span-2 text-center py-20">No collections yet</div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="sec-orders" class="section-panel hidden">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <h1 class="font-display text-4xl font-black tracking-tight">Orders</h1>
                        <p class="text-zinc-500 text-sm mt-1">Track every transaction</p>
                    </div>

                    <button onclick="openModal('addOrder')" class="btn-primary px-5 py-2.5 rounded text-sm font-medium flex items-center gap-2">
                        <i data-lucide="plus" style="width:14px;height:14px"></i> New Order
                    </button>
                </div>

                <div class="flex gap-3 mb-6">
                    <button onclick="filterOrders('all')" class="tab-btn active" data-filter="all">All</button>
                    <button onclick="filterOrders('NEW')" class="tab-btn" data-filter="NEW">New / Pending</button>
                    <button onclick="filterOrders('DONE')" class="tab-btn" data-filter="DONE">Completed</button>
                    <button onclick="filterOrders('CANCELLED')" class="tab-btn" data-filter="CANCELLED">Cancelled</button>
                </div>

                <div class="card-hover rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="border-b border-zinc-200 text-[10px] tracking-[2px] text-zinc-500 uppercase">
                            <th class="text-left p-4">Customer</th>
                            <th class="text-left p-4">Item / Type / Color</th>
                            <th class="text-left p-4">Total</th>
                            <th class="text-left p-4">Status</th>
                            <th class="text-left p-4">Date</th>
                            <th class="text-right p-4">Actions</th>
                        </tr>
                        </thead>

                        <tbody id="ordersTableBody">
                        <?php if ($ordersResult && $ordersResult->num_rows > 0): ?>
                            <?php while($o = $ordersResult->fetch_assoc()): ?>
                                <tr class="order-row border-b border-zinc-100" data-status="<?= h($o['order_status']) ?>">
                                    <td class="p-4">
                                        <div class="font-medium text-sm"><?= h($o['full_name']) ?></div>
                                        <div class="text-zinc-600 text-xs"><?= h($o['email']) ?></div>
                                        <div class="text-zinc-500 text-[11px] mt-1"><?= h($o['city']) ?> / <?= h($o['area_name']) ?></div>
                                    </td>

                                    <td class="p-4">
                                        <?php if (!empty($o['items'])): ?>
                                            <div class="space-y-2">
                                                <?php
                                                $items = explode('##', $o['items']);
                                                foreach ($items as $item):
                                                    $parts = explode('||', $item);
                                                    $productName = $parts[0] ?? 'Product';
                                                    $typeName = $parts[1] ?? '-';
                                                    $colorName = $parts[2] ?? '-';
                                                    $sizeName = $parts[3] ?? '-';
                                                    $qty = $parts[4] ?? '1';
                                                    ?>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="font-medium"><?= h($productName) ?></span>
                                                        <span class="text-[10px] px-2 py-1 rounded bg-zinc-100 text-zinc-500"><?= h($typeName) ?></span>
                                                        <span class="color-dot" style="background:<?= h(colorToCss($colorName)) ?>"></span>
                                                        <span class="text-xs text-zinc-500"><?= h($colorName) ?></span>
                                                        <span class="text-xs text-zinc-500">Size: <?= h($sizeName) ?></span>
                                                        <span class="text-xs text-zinc-500">Qty: <?= h($qty) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-zinc-600">No items</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold">$<?= number_format((float)$o['total_price'], 2) ?></div>
                                        <div class="text-zinc-500 text-xs">Delivery $<?= number_format((float)$o['delivery_price'], 2) ?></div>
                                    </td>

                                    <td class="p-4">
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium <?= statusBadgeClass($o['order_status']) ?>">
                                            <span class="w-2 h-2 rounded-full <?= statusDotClass($o['order_status']) ?>"></span>
                                            <?= statusLabel($o['order_status']) ?>
                                        </div>
                                    </td>

                                    <td class="p-4 text-zinc-500 text-xs">Order #<?= h($o['order_id']) ?></td>

                                    <td class="p-4 text-right">
                                        <form method="POST" class="flex items-center gap-2 justify-end">
                                            <input type="hidden" name="order_id" value="<?= h($o['order_id']) ?>">

                                            <select name="order_status" class="input-noir text-xs py-1 px-2 w-32">
                                                <option value="NEW" <?= $o['order_status'] === 'NEW' ? 'selected' : '' ?>>New / Pending</option>
                                                <option value="DONE" <?= $o['order_status'] === 'DONE' ? 'selected' : '' ?>>Completed</option>
                                                <option value="CANCELLED" <?= $o['order_status'] === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>

                                            <button name="update_order_status" class="btn-primary px-3 py-1 rounded text-xs">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="p-8 text-center text-zinc-600 italic">No orders yet</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="sec-customers" class="section-panel hidden">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <h1 class="font-display text-4xl font-black tracking-tight">Customers</h1>
                        <p class="text-zinc-500 text-sm mt-1">Your community</p>
                    </div>

                    <button onclick="openModal('addCustomer')" class="btn-primary px-5 py-2.5 rounded text-sm font-medium flex items-center gap-2">
                        <i data-lucide="plus" style="width:14px;height:14px"></i> Add Customer
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <?php if ($customersResult && $customersResult->num_rows > 0): ?>
                        <?php while($c = $customersResult->fetch_assoc()): ?>
                            <div class="card-hover rounded-lg p-5">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center font-display font-black text-sm">
                                        <?= h(strtoupper(substr($c['full_name'], 0, 1))) ?>
                                    </div>

                                    <div>
                                        <div class="font-medium text-sm"><?= h($c['full_name']) ?></div>
                                        <div class="text-zinc-600 text-xs"><?= h($c['email']) ?></div>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-zinc-500 text-xs"><?= h($c['orders_count']) ?> order<?= $c['orders_count'] != 1 ? 's' : '' ?></span>
                                    <span class="text-zinc-600 text-xs"><?= h($c['city'] ?? '-') ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-zinc-600 text-sm italic col-span-3 text-center py-20">No registered customers</div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <div id="modalOverlay" class="modal-overlay hidden">
        <div class="modal-box bg-white border border-zinc-200 rounded-lg w-full max-w-md p-6 max-h-[90vh] overflow-auto" id="modalContent"></div>
    </div>
</div>

<script>
    let currentSection = 'dashboard';

    const categoriesOptions = `
        <?php
    $categoriesForSelect2 = $conn->query("
            SELECT c.category_id, COALESCE(ct.category_name, CONCAT('Category #', c.category_id)) AS category_name
            FROM categories c
            LEFT JOIN category_translations ct ON c.category_id = ct.category_id AND ct.language_code='en'
            ORDER BY c.category_id ASC
        ");
    if ($categoriesForSelect2 && $categoriesForSelect2->num_rows > 0):
    while($cat = $categoriesForSelect2->fetch_assoc()):
    ?>
            <option value="<?= h($cat['category_id']) ?>"><?= h($cat['category_name']) ?></option>
        <?php endwhile; endif; ?>
    `;

    const collectionsOptions = `
        <option value="">No Collection</option>
        <?php
    $collectionsForSelect2 = $conn->query("
            SELECT c.collection_id, COALESCE(ct.collection_name, c.collection_key) AS collection_name
            FROM collections c
            LEFT JOIN collection_translations ct ON c.collection_id = ct.collection_id AND ct.language_code='en'
            ORDER BY c.collection_id DESC
        ");
    if ($collectionsForSelect2 && $collectionsForSelect2->num_rows > 0):
    while($co = $collectionsForSelect2->fetch_assoc()):
    ?>
            <option value="<?= h($co['collection_id']) ?>"><?= h($co['collection_name']) ?></option>
        <?php endwhile; endif; ?>
    `;

    const productsOptions = `
        <?php if ($productsForSelect && $productsForSelect->num_rows > 0): ?>
            <?php while($pr = $productsForSelect->fetch_assoc()): ?>
                <option value="<?= h($pr['product_id']) ?>">#<?= h($pr['product_id']) ?> - <?= h($pr['product_name']) ?></option>
            <?php endwhile; ?>
        <?php endif; ?>
    `;

    function switchSection(name) {
        currentSection = name;

        document.querySelectorAll('.section-panel').forEach(s => s.classList.add('hidden'));
        document.getElementById('sec-' + name)?.classList.remove('hidden');

        document.querySelectorAll('.nav-btn').forEach(b => {
            const active = b.dataset.section === name;
            b.classList.remove('bg-black', 'text-white', 'text-zinc-500');

            if (active) b.classList.add('bg-black', 'text-white');
            else b.classList.add('text-zinc-500');
        });

        if (name === 'orders') location.hash = 'orders';
        else if (name === 'inventory') location.hash = 'inventory';
        else if (name === 'collections') location.hash = 'collections';
        else history.replaceState(null, '', 'admin.php');
    }

    function filterOrders(filter) {
        document.querySelectorAll('[data-filter]').forEach(b => {
            b.classList.toggle('active', b.dataset.filter === filter);
        });

        document.querySelectorAll('#ordersTableBody tr[data-status]').forEach(row => {
            if (filter === 'all' || row.dataset.status === filter) row.classList.remove('status-filter-hidden');
            else row.classList.add('status-filter-hidden');
        });
    }

    function openModal(type) {
        const overlay = document.getElementById('modalOverlay');
        const content = document.getElementById('modalContent');
        overlay.classList.remove('hidden');

        let html = '';

        if (type === 'addItem') {
            html = `
                <h2 class="font-display text-xl font-bold mb-5">Add Item / Add Color</h2>

                <form method="POST" action="add_item.php" enctype="multipart/form-data" class="space-y-4">
                    <label class="text-xs text-zinc-500 block">
                        Choose New Product or add color to existing product
                    </label>

                    <select class="input-noir" name="existing_product_id">
                        <option value="">New Product</option>
                        ${productsOptions}
                    </select>

                    <input class="input-noir" name="product_name" placeholder="Product name if new item">

                    <select class="input-noir" name="category_id" required>
                        <option value="">Select Category</option>
                        ${categoriesOptions}
                    </select>

                    <select class="input-noir" name="collection_id">
                        ${collectionsOptions}
                    </select>

                    <input class="input-noir" name="price" type="number" step="0.01" placeholder="Price" required>

                    <input class="input-noir" name="color" placeholder="Color e.g. Blue / Black" required>

                    <label class="text-xs text-zinc-500 block">Sizes</label>
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <label><input type="checkbox" name="sizes[]" value="XS"> XS</label>
                        <label><input type="checkbox" name="sizes[]" value="S"> S</label>
                        <label><input type="checkbox" name="sizes[]" value="M"> M</label>
                        <label><input type="checkbox" name="sizes[]" value="L"> L</label>
                        <label><input type="checkbox" name="sizes[]" value="XL"> XL</label>
                        <label><input type="checkbox" name="sizes[]" value="XXL"> XXL</label>
                    </div>

                    <input class="input-noir" name="quantity" type="number" min="0" placeholder="Quantity" required>

                    <label class="text-xs text-zinc-500 block">Choose image from laptop</label>
                    <input class="input-noir" name="variant_image" type="file" accept="image/*" required>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="has_discount">
                        Add discount
                    </label>

                    <input class="input-noir" name="discount_percent" type="number" step="0.01" min="0" max="90" placeholder="Discount %">
                    <input class="input-noir" name="discount_label" placeholder="Discount label e.g. SALE">

                    <div class="flex gap-3 justify-end mt-5">
                        <button type="button" onclick="closeModal()" class="btn-ghost px-4 py-2 rounded text-sm">Cancel</button>
                        <button type="submit" class="btn-primary px-5 py-2 rounded text-sm font-medium">Save Item</button>
                    </div>
                </form>
            `;
        }

        else if (type === 'addCollection') {
            html = `
                <h2 class="font-display text-xl font-bold mb-5">New Collection</h2>

                <form method="POST" class="space-y-4">
                    <input class="input-noir" name="collection_key" placeholder="Collection key e.g. summer_2026" required>
                    <input class="input-noir" name="collection_name" placeholder="Collection name" required>
                    <textarea class="input-noir" name="description" placeholder="Description"></textarea>

                    <div class="flex gap-3 justify-end mt-5">
                        <button type="button" onclick="closeModal()" class="btn-ghost px-4 py-2 rounded text-sm">Cancel</button>
                        <button type="submit" name="add_collection" class="btn-primary px-5 py-2 rounded text-sm font-medium">Create</button>
                    </div>
                </form>
            `;
        }

        else if (type === 'addOrder') {
            html = `
                <h2 class="font-display text-xl font-bold mb-5">New Order</h2>

                <form method="POST" action="add_order.php" class="space-y-4">
                    <input class="input-noir" name="user_id" placeholder="User ID" type="number" required>
                    <input class="input-noir" name="variant_id" placeholder="Variant ID" type="number" required>
                    <input class="input-noir" name="area_id" placeholder="Area ID" type="number" required>
                    <input class="input-noir" name="quantity" placeholder="Quantity" type="number" required>

                    <div class="grid grid-cols-2 gap-3">
                        <input class="input-noir" name="subtotal" placeholder="Subtotal" type="number" step="0.01" required>
                        <input class="input-noir" name="delivery_price" placeholder="Delivery" type="number" step="0.01" required>
                    </div>

                    <input class="input-noir" name="total_price" placeholder="Total" type="number" step="0.01" required>

                    <select class="input-noir" name="order_status">
                        <option value="NEW">New / Pending</option>
                        <option value="DONE">Completed</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>

                    <div class="flex gap-3 justify-end mt-5">
                        <button type="button" onclick="closeModal()" class="btn-ghost px-4 py-2 rounded text-sm">Cancel</button>
                        <button type="submit" class="btn-primary px-5 py-2 rounded text-sm font-medium">Create Order</button>
                    </div>
                </form>
            `;
        }

        else if (type === 'addCustomer') {
            html = `
                <h2 class="font-display text-xl font-bold mb-5">Register Customer</h2>

                <form method="POST" action="add_customer.php" class="space-y-4">
                    <input class="input-noir" name="full_name" placeholder="Full name" required>
                    <input class="input-noir" name="email" placeholder="Email" type="email" required>
                    <input class="input-noir" name="password" placeholder="Password" type="password" required>
                    <input class="input-noir" name="phone" placeholder="Phone">

                    <div class="grid grid-cols-2 gap-3">
                        <input class="input-noir" name="city" placeholder="City">
                        <input class="input-noir" name="area" placeholder="Area">
                    </div>

                    <input class="input-noir" name="street" placeholder="Street">
                    <input class="input-noir" name="building" placeholder="Building">

                    <div class="flex gap-3 justify-end mt-5">
                        <button type="button" onclick="closeModal()" class="btn-ghost px-4 py-2 rounded text-sm">Cancel</button>
                        <button type="submit" class="btn-primary px-5 py-2 rounded text-sm font-medium">Register</button>
                    </div>
                </form>
            `;
        }

        content.innerHTML = html;
        lucide.createIcons();
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.add('hidden');
    }

    document.getElementById('modalOverlay').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeModal();
    });

    document.getElementById('dashDate').textContent = new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($revenueLabels) ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode($revenueValues) ?>,
                backgroundColor: '#111',
                borderColor: '#111',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#555' },
                    grid: { color: '#eee' }
                },
                x: {
                    ticks: { color: '#555' },
                    grid: { display: false }
                }
            }
        }
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($categoryLabels) ?>,
            datasets: [{
                data: <?= json_encode($categoryValues) ?>,
                backgroundColor: [
                    '#111827',
                    '#374151',
                    '#6b7280',
                    '#9ca3af',
                    '#d1d5db',
                    '#fbbf24',
                    '#60a5fa',
                    '#f472b6'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#333',
                        boxWidth: 10,
                        padding: 10
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('stockChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($stockLabels) ?>,
            datasets: [{
                data: <?= json_encode($stockValues) ?>,
                backgroundColor: [
                    '#10b981',
                    '#ef4444'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#333',
                        boxWidth: 10,
                        padding: 10
                    }
                }
            }
        }
    });
    if (window.location.hash === '#orders') {
        switchSection('orders');
    } else if (window.location.hash === '#inventory') {
        switchSection('inventory');
    } else if (window.location.hash === '#collections') {
        switchSection('collections');
    } else {
        switchSection('dashboard');
    }

    lucide.createIcons();
</script>
</body>
</html>