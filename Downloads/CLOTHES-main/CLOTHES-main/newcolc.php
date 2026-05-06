<?php
global $conn;
include_once "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function normalizeColor($color) {
    $c = strtolower(trim((string)$color));

    $map = [
            'black'=>'Black','اسود'=>'Black','أسود'=>'Black',
            'white'=>'White','ابيض'=>'White','أبيض'=>'White',
            'blue'=>'Blue','navy'=>'Navy','navy blue'=>'Navy',
            'dark blue'=>'Dark Blue','light blue'=>'Light Blue',
            'red'=>'Red','pink'=>'Pink','green'=>'Green',
            'gray'=>'Gray','grey'=>'Gray','brown'=>'Brown',
            'beige'=>'Beige','purple'=>'Purple','yellow'=>'Yellow','gold'=>'Gold'
    ];

    return $map[$c] ?? ucwords($c);
}

function colorCss($color) {
    $c = strtolower(trim((string)$color));

    $map = [
            'black'=>'#000000','white'=>'#ffffff','blue'=>'#2563eb',
            'navy'=>'#1e3a8a','navy blue'=>'#1e3a8a',
            'dark blue'=>'#1e40af','light blue'=>'#93c5fd',
            'red'=>'#ef4444','pink'=>'#ec4899','green'=>'#22c55e',
            'gray'=>'#71717a','grey'=>'#71717a','brown'=>'#92400e',
            'beige'=>'#d6b98c','purple'=>'#a855f7',
            'yellow'=>'#eab308','gold'=>'#fbbf24'
    ];

    return $map[$c] ?? '#777777';
}

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserName = $_SESSION['user_name'] ?? ($_SESSION['full_name'] ?? '');

$lang = $_GET['lang'] ?? 'en';
$lang = in_array($lang, ['en','ar','he']) ? $lang : 'en';

$wishlistIds = [];

if ($isLoggedIn) {
    $uid = (int)$_SESSION['user_id'];

    $wq = $conn->prepare("SELECT product_id FROM favorites WHERE user_id=?");
    $wq->bind_param("i", $uid);
    $wq->execute();
    $wr = $wq->get_result();

    while ($r = $wr->fetch_assoc()) {
        $wishlistIds[] = (int)$r['product_id'];
    }
}

$products = [];

$sql = "
SELECT
    p.product_id,
    p.price,
    p.category_id,
    p.collection_id,
    COALESCE(cl.is_new, 0) AS is_new,

    COALESCE(pt.product_name, CONCAT('Product #', p.product_id)) AS product_name,
    COALESCE(pt.description, '') AS description,

    COALESCE(ct.category_name, c.category_key, 'No Category') AS category_name,
    COALESCE(clt.collection_name, cl.collection_key, 'No Collection') AS collection_name,

    pv.variant_id,
    pv.color,
    pv.size,
    pv.quantity,
    COALESCE(pv.variant_image_url, '') AS variant_image_url,

    COALESCE(pi.image_url, '') AS image_url

FROM products p

LEFT JOIN product_translations pt
    ON p.product_id = pt.product_id AND pt.language_code = ?

LEFT JOIN categories c
    ON p.category_id = c.category_id

LEFT JOIN category_translations ct
    ON c.category_id = ct.category_id AND ct.language_code = ?

LEFT JOIN collections cl
    ON p.collection_id = cl.collection_id

LEFT JOIN collection_translations clt
    ON cl.collection_id = clt.collection_id AND clt.language_code = ?

LEFT JOIN product_variants pv
    ON p.product_id = pv.product_id

LEFT JOIN product_images pi
    ON p.product_id = pi.product_id AND pi.is_main = 1

ORDER BY p.created_at DESC, p.product_id DESC, pv.variant_id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $lang, $lang, $lang);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
    $pid = (int)$r['product_id'];

    if (!isset($products[$pid])) {
        $products[$pid] = [
                'id' => $pid,
                'name' => $r['product_name'],
                'price' => (float)$r['price'],
                'category' => $r['category_name'],
                'collection' => $r['collection_name'],
                'is_new' => (int)$r['is_new'],
                'main_image' => $r['image_url'] ?: 'pic/default.jpg',
                'variants' => [],
                'colors' => [],
                'sizes' => []
        ];
    }

    if (!empty($r['variant_id'])) {
        $colorName = normalizeColor($r['color']);
        $sizeName = trim((string)$r['size']);
        $img = $r['variant_image_url'] ?: ($r['image_url'] ?: 'pic/default.jpg');

        $products[$pid]['variants'][] = [
                'variant_id' => (int)$r['variant_id'],
                'color' => $colorName,
                'size' => $sizeName,
                'quantity' => (int)$r['quantity'],
                'image' => $img
        ];

        if (!isset($products[$pid]['colors'][$colorName])) {
            $products[$pid]['colors'][$colorName] = [
                    'name' => $colorName,
                    'css' => colorCss($r['color']),
                    'image' => $img
            ];
        }

        if ($sizeName !== '') {
            $products[$pid]['sizes'][$sizeName] = $sizeName;
        }
    }
}

$products = array_values($products);

$categories = [];
$collections = [];
$colors = [];
$sizes = [];

foreach ($products as $p) {
    $categories[$p['category']] = $p['category'];
    $collections[$p['collection']] = $p['collection'];

    foreach ($p['colors'] as $c) {
        $colors[$c['name']] = $c;
    }

    foreach ($p['sizes'] as $s) {
        $sizes[$s] = $s;
    }
}

ksort($categories);
ksort($collections);
ksort($colors);
ksort($sizes);
?>
<!doctype html>
<html lang="<?= h($lang) ?>" class="h-full" <?= ($lang === 'ar' || $lang === 'he') ? 'dir="rtl"' : 'dir="ltr"' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demoiselle — Shop</title>

    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Outfit:wght@200;300;400;500;600&display=swap" rel="stylesheet">

    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        html, body { min-height:100%; }
        body { font-family:'Outfit', sans-serif; background:#fafafa; color:#111; }
        .font-display { font-family:'Cormorant Garamond', serif; }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(30px); }
            to { opacity:1; transform:translateY(0); }
        }

        @keyframes marquee {
            0% { transform:translateX(0); }
            100% { transform:translateX(-50%); }
        }

        .anim-fade { animation:fadeUp .8s ease forwards; opacity:0; }
        .marquee-track { display:flex; width:max-content; animation:marquee 20s linear infinite; }

        .product-card { transition:all .4s cubic-bezier(.25,.46,.45,.94); }
        .product-card:hover { transform:translateY(-4px); }
        .product-card:hover .product-img { transform:scale(1.05); }
        .product-card:hover .quick-actions { opacity:1; transform:translateY(0); }

        .product-img { transition:transform .6s cubic-bezier(.25,.46,.45,.94); }
        .quick-actions { opacity:0; transform:translateY(10px); transition:all .3s ease; }

        .new-ribbon {
            position:absolute;
            top:18px;
            right:-43px;
            width:160px;
            transform:rotate(45deg);
            background:#dc2626;
            color:#fff;
            text-align:center;
            font-size:10px;
            font-weight:700;
            letter-spacing:.18em;
            padding:6px 0;
            z-index:20;
            box-shadow:0 6px 18px rgba(0,0,0,.25);
        }

        .filter-drawer {
            position:fixed;
            top:0;
            right:0;
            width:380px;
            max-width:92vw;
            height:100vh;
            background:#fff;
            z-index:100;
            transform:translateX(100%);
            transition:.35s ease;
            box-shadow:-20px 0 60px rgba(0,0,0,.22);
            overflow:auto;
        }

        html[dir="rtl"] .filter-drawer {
            right:auto;
            left:0;
            transform:translateX(-100%);
        }

        .filter-drawer.open { transform:translateX(0); }

        .filter-overlay {
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.45);
            z-index:90;
            opacity:0;
            pointer-events:none;
            transition:.3s ease;
        }

        .filter-overlay.open {
            opacity:1;
            pointer-events:auto;
        }

        .check-box {
            display:flex;
            align-items:center;
            gap:10px;
            border:1px solid #e5e5e5;
            padding:10px;
            border-radius:12px;
            cursor:pointer;
            font-size:13px;
            background:#fff;
        }

        .check-box:hover { border-color:#111; }
        .check-box input { accent-color:#000; }

        .color-dot {
            width:14px;
            height:14px;
            border-radius:50%;
            border:1px solid rgba(0,0,0,.3);
            display:inline-block;
        }

        .size-btn.active {
            background:#000;
            color:#fff;
            border-color:#000;
        }

        .heart-btn.liked svg { fill:#000; }

        .bag-panel {
            transform:translateX(100%);
            transition:.35s ease;
        }

        html[dir="rtl"] .bag-panel {
            right:auto;
            left:0;
            transform:translateX(-100%);
        }

        .bag-panel.open { transform:translateX(0); }

        .bag-overlay {
            opacity:0;
            pointer-events:none;
            transition:.3s;
        }

        .bag-overlay.open {
            opacity:1;
            pointer-events:auto;
        }

        .toast {
            transform:translateY(100px);
            opacity:0;
            transition:.35s;
        }

        .toast.show {
            transform:translateY(0);
            opacity:1;
        }

        .nav-link { position:relative; }

        .nav-link::after {
            content:'';
            position:absolute;
            bottom:-2px;
            left:0;
            width:0;
            height:1px;
            background:currentColor;
            transition:.3s;
        }

        html[dir="rtl"] .nav-link::after {
            left:auto;
            right:0;
        }

        .nav-link:hover::after { width:100%; }

        .mobile-menu {
            max-height:0;
            overflow:hidden;
            transition:.4s;
            opacity:0;
        }

        .mobile-menu.open {
            max-height:900px;
            opacity:1;
        }

        .dropdown-link {
            color:rgba(255,255,255,.75);
            transition:.25s;
        }

        .dropdown-link:hover {
            color:#fff;
            transform:translateX(3px);
        }

        html[dir="rtl"] .dropdown-link:hover {
            transform:translateX(-3px);
        }

        #logo-img {
            height:62px;
            width:auto;
            max-width:200px;
        }

        .selected-color {
            outline:2px solid #000;
            outline-offset:2px;
        }
    </style>
</head>

<body>

<div id="app-wrapper" class="w-full min-h-screen overflow-auto">

    <header class="w-full fixed top-0 left-0 z-50" style="background:rgba(0,0,0,0.9); backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,.08);">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="index.php?lang=<?= h($lang) ?>" class="flex items-center">
                <img src="pic/lolo1.png" alt="Demoiselle Logo" id="logo-img">
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm tracking-wider uppercase font-light" style="color:rgba(255,255,255,.7);">
                <a href="index.php?lang=<?= h($lang) ?>" class="nav-link">Home</a>
                <a href="shope.php?lang=<?= h($lang) ?>" class="nav-link">Shop</a>
                <a href="newcolc.php?lang=<?= h($lang) ?>" class="nav-link">Collection</a>
                <a href="about.php?lang=<?= h($lang) ?>" class="nav-link">Our Story</a>
                <a href="contact.php?lang=<?= h($lang) ?>" class="nav-link">Contact</a>
            </nav>

            <div class="hidden md:flex items-center gap-4 relative text-white">
                <button id="main-dropdown-btn">
                    <i data-lucide="menu" style="width:24px;height:24px;"></i>
                </button>

                <div id="main-dropdown" class="hidden absolute top-10 right-0 w-96 max-h-[80vh] overflow-y-auto bg-black border border-white/10 rounded-2xl p-6 shadow-2xl z-[200]">
                    <h4 class="text-xs tracking-[.3em] uppercase text-white/40 mb-3">Pages</h4>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <a class="dropdown-link" href="index.php?lang=<?= h($lang) ?>">Home</a>
                        <a class="dropdown-link" href="shope.php?lang=<?= h($lang) ?>">Shop</a>
                        <a class="dropdown-link" href="newcolc.php?lang=<?= h($lang) ?>">New Collection</a>
                        <a class="dropdown-link" href="search.php?lang=<?= h($lang) ?>">Search</a>
                        <a class="dropdown-link" href="cart.php?lang=<?= h($lang) ?>">Cart</a>
                        <a class="dropdown-link" href="wishlist.php?lang=<?= h($lang) ?>">Wishlist</a>
                        <a class="dropdown-link" href="profile.php?lang=<?= h($lang) ?>">Profile</a>
                        <a class="dropdown-link" href="logout.php">Logout</a>
                    </div>
                </div>

                <button onclick="location.href='search.php?lang=<?= h($lang) ?>'">
                    <i data-lucide="search" style="width:18px;height:18px;"></i>
                </button>

                <button onclick="goUserPage()">
                    <i data-lucide="user" style="width:18px;height:18px;"></i>
                </button>

                <button onclick="location.href='wishlist.php?lang=<?= h($lang) ?>'" class="relative">
                    <i data-lucide="heart" style="width:18px;height:18px;"></i>
                    <span id="fav-count" class="absolute -top-1 -right-2 text-[10px] w-4 h-4 rounded-full flex items-center justify-center bg-white text-black <?= count($wishlistIds) > 0 ? '' : 'hidden' ?>">
                        <?= count($wishlistIds) ?>
                    </span>
                </button>

                <button onclick="location.href='cart.php?lang=<?= h($lang) ?>'" id="bag-btn" class="relative">
                    <i data-lucide="shopping-bag" style="width:18px;height:18px;"></i>
                    <span id="cart-count" class="absolute -top-1 -right-2 text-[10px] w-4 h-4 rounded-full flex items-center justify-center bg-white text-black hidden">0</span>
                </button>

                <?php if ($isLoggedIn): ?>
                    <span class="text-xs text-white/60"><?= h($currentUserName) ?></span>
                <?php endif; ?>
            </div>

            <button class="md:hidden text-white" id="mobile-toggle">
                <i data-lucide="menu" style="width:24px;height:24px;"></i>
            </button>
        </div>

        <div class="mobile-menu md:hidden px-6 pb-4 text-white/70" id="mobile-menu">
            <nav class="flex flex-col gap-4 text-sm tracking-wider uppercase font-light pt-2 border-t border-white/10">
                <a href="index.php?lang=<?= h($lang) ?>">Home</a>
                <a href="shope.php?lang=<?= h($lang) ?>">Shop</a>
                <a href="wishlist.php?lang=<?= h($lang) ?>">Wishlist</a>
                <a href="cart.php?lang=<?= h($lang) ?>">Cart</a>
                <button onclick="goUserPage()" class="text-left">User / Sign In</button>
            </nav>
        </div>
    </header>

    <div style="height:94px;"></div>

    <section class="w-full relative overflow-hidden anim-fade" style="background:#000; color:#fff;">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 py-16 sm:py-24 flex flex-col items-center text-center">
            <p class="text-xs tracking-widest uppercase mb-4" style="color:#888;">Autumn / Winter 2024</p>
            <h1 class="font-display text-4xl sm:text-6xl lg:text-7xl font-light italic leading-tight">Redefine Your Silence</h1>
            <p class="mt-6 text-sm tracking-wide" style="color:#999; max-width:420px;">
                Where minimalism speaks volumes. Curated pieces for the unapologetically intentional.
            </p>
        </div>
    </section>

    <div class="w-full overflow-hidden py-3" style="background:#000; border-bottom:1px solid #222;">
        <div class="marquee-track text-xs tracking-widest uppercase" style="color:#666;">
            <span class="mx-8">Free Shipping Over $150</span><span class="mx-8">◆</span>
            <span class="mx-8">New Collection Available</span><span class="mx-8">◆</span>
            <span class="mx-8">Sustainable Materials</span><span class="mx-8">◆</span>
            <span class="mx-8">Free Shipping Over $150</span><span class="mx-8">◆</span>
            <span class="mx-8">New Collection Available</span><span class="mx-8">◆</span>
        </div>
    </div>

    <section class="w-full max-w-7xl mx-auto px-4 sm:px-8 pt-12 pb-6">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <p class="text-xs tracking-widest uppercase" style="color:#999;">Curated Selection</p>
                <h2 class="font-display text-3xl sm:text-4xl font-light mt-1">THE EDIT — AW24</h2>
            </div>

            <div class="flex gap-3 items-center">
                <p id="product-count-label" class="text-xs tracking-wide" style="color:#888;">Showing <?= count($products) ?> pieces</p>

                <button onclick="openFilter()" class="px-5 py-2 text-xs tracking-widest uppercase border border-black rounded-full hover:bg-black hover:text-white transition">
                    Filter
                </button>
            </div>
        </div>
    </section>

    <section class="w-full max-w-7xl mx-auto px-4 sm:px-8 pb-16">
        <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6"></div>
    </section>

    <div id="filterOverlay" class="filter-overlay" onclick="closeFilter()"></div>

    <aside id="filterDrawer" class="filter-drawer p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-display text-3xl">Filters</h3>
            <button onclick="closeFilter()"><i data-lucide="x"></i></button>
        </div>

        <input id="searchInput" oninput="applyFilters()" placeholder="Search by name..."
               class="w-full border border-zinc-200 rounded-xl px-4 py-3 mb-5 outline-none">

        <div class="mb-6">
            <h4 class="text-xs tracking-widest uppercase mb-3">Category</h4>
            <div class="space-y-2">
                <?php foreach ($categories as $cat): ?>
                    <label class="check-box">
                        <input type="checkbox" class="filter-category" value="<?= h(strtolower($cat)) ?>" onchange="applyFilters()">
                        <?= h($cat) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-6">
            <h4 class="text-xs tracking-widest uppercase mb-3">Collection</h4>
            <div class="space-y-2">
                <?php foreach ($collections as $col): ?>
                    <label class="check-box">
                        <input type="checkbox" class="filter-collection" value="<?= h(strtolower($col)) ?>" onchange="applyFilters()">
                        <?= h($col) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-6">
            <h4 class="text-xs tracking-widest uppercase mb-3">Colors</h4>
            <div class="grid grid-cols-2 gap-2">
                <?php foreach ($colors as $c): ?>
                    <label class="check-box">
                        <input type="checkbox" class="filter-color" value="<?= h(strtolower($c['name'])) ?>" onchange="applyFilters()">
                        <span class="color-dot" style="background:<?= h($c['css']) ?>"></span>
                        <?= h($c['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-6">
            <h4 class="text-xs tracking-widest uppercase mb-3">Sizes</h4>
            <div class="grid grid-cols-3 gap-2">
                <?php foreach ($sizes as $s): ?>
                    <label class="check-box justify-center">
                        <input type="checkbox" class="filter-size" value="<?= h(strtolower($s)) ?>" onchange="applyFilters()">
                        <?= h($s) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-6">
            <h4 class="text-xs tracking-widest uppercase mb-3">Price</h4>
            <div class="grid grid-cols-2 gap-2">
                <input id="minPrice" type="number" placeholder="Min" oninput="applyFilters()" class="border rounded-xl px-3 py-2">
                <input id="maxPrice" type="number" placeholder="Max" oninput="applyFilters()" class="border rounded-xl px-3 py-2">
            </div>
        </div>

        <button onclick="resetFilters()" class="w-full py-3 bg-black text-white rounded-xl text-xs tracking-widest uppercase">
            Reset Filters
        </button>
    </aside>

    <div id="bag-overlay" class="bag-overlay fixed inset-0 z-50 bg-black/50" onclick="closeBag()"></div>

    <div id="bag-panel" class="bag-panel fixed top-0 right-0 z-50 h-full w-full sm:w-96 flex flex-col bg-white">
        <div class="flex items-center justify-between p-6 border-b">
            <h3 class="font-display text-xl tracking-wide">Your Bag</h3>
            <button onclick="closeBag()"><i data-lucide="x"></i></button>
        </div>

        <div id="bag-items" class="flex-1 overflow-auto p-6">
            <div class="flex flex-col items-center justify-center h-full text-center text-zinc-400">
                <i data-lucide="shopping-bag" style="width:40px;height:40px;"></i>
                <p class="mt-3 text-xs">Items added here will be saved in your cart page.</p>
            </div>
        </div>

        <div class="p-6 border-t">
            <button onclick="location.href='cart.php?lang=<?= h($lang) ?>'" class="w-full py-3 text-xs tracking-widest uppercase bg-black text-white">
                Go To Cart
            </button>
        </div>
    </div>

    <div id="toast" class="toast fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-full text-xs tracking-wide flex items-center gap-2 bg-black text-white shadow-xl">
        <i data-lucide="check" style="width:14px;height:14px;"></i>
        <span id="toast-msg">Added</span>
    </div>

    <footer class="w-full py-16 px-6" style="background:#000; border-top:1px solid rgba(255,255,255,.06); color:#fff;">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-14">
                <div>
                    <a href="index.php?lang=<?= h($lang) ?>" class="inline-block mb-4">
                        <img src="pic/lolo1.png" alt="Demoiselle Logo" class="h-10 w-auto">
                    </a>
                    <p class="text-xs font-light leading-relaxed text-white/35">Timeless elegance. Conscious fashion.</p>
                </div>

                <div>
                    <h4 class="text-[10px] tracking-[.3em] uppercase mb-5 text-white/50">Shop</h4>
                    <nav class="flex flex-col gap-3">
                        <a href="shope.php?lang=<?= h($lang) ?>" class="text-xs text-white/35 hover:text-white">Shop</a>
                        <a href="newcolc.php?lang=<?= h($lang) ?>" class="text-xs text-white/35 hover:text-white">New Collection</a>
                    </nav>
                </div>

                <div>
                    <h4 class="text-[10px] tracking-[.3em] uppercase mb-5 text-white/50">Company</h4>
                    <nav class="flex flex-col gap-3">
                        <a href="about.php?lang=<?= h($lang) ?>" class="text-xs text-white/35 hover:text-white">Our Story</a>
                    </nav>
                </div>

                <div>
                    <h4 class="text-[10px] tracking-[.3em] uppercase mb-5 text-white/50">Support</h4>
                    <nav class="flex flex-col gap-3">
                        <a href="contact.php?lang=<?= h($lang) ?>" class="text-xs text-white/35 hover:text-white">Contact Us</a>
                        <a href="wishlist.php?lang=<?= h($lang) ?>" class="text-xs text-white/35 hover:text-white">Wishlist</a>
                    </nav>
                </div>
            </div>

            <div class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-white/10">
                <p class="text-[10px] tracking-[.2em] text-white/25">© 2025 demoiselle. All rights reserved.</p>
                <p class="text-[10px] tracking-[.2em] text-white/25">Made with love in Palestine</p>
            </div>
        </div>
    </footer>

</div>

<script>
    const PRODUCTS = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const WISHLIST_IDS = <?= json_encode($wishlistIds) ?>;
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    const currentLang = "<?= h($lang) ?>";

    let selectedOptions = {};
    let cartCounter = 0;

    function withLang(page) {
        return page.includes('?') ? page + '&lang=' + currentLang : page + '?lang=' + currentLang;
    }

    function goUserPage() {
        location.href = isLoggedIn ? withLang('profile.php') : withLang('signin.php');
    }

    function openFilter() {
        document.getElementById('filterDrawer').classList.add('open');
        document.getElementById('filterOverlay').classList.add('open');
    }

    function closeFilter() {
        document.getElementById('filterDrawer').classList.remove('open');
        document.getElementById('filterOverlay').classList.remove('open');
    }

    function checkedValues(cls) {
        return Array.from(document.querySelectorAll('.' + cls + ':checked')).map(x => x.value.toLowerCase());
    }

    function applyFilters() {
        const search = document.getElementById('searchInput').value.toLowerCase().trim();
        const cats = checkedValues('filter-category');
        const cols = checkedValues('filter-collection');
        const colors = checkedValues('filter-color');
        const sizes = checkedValues('filter-size');
        const min = parseFloat(document.getElementById('minPrice').value || 0);
        const max = parseFloat(document.getElementById('maxPrice').value || 999999);

        const filtered = PRODUCTS.filter(p => {
            const nameOk = p.name.toLowerCase().includes(search);
            const catOk = cats.length === 0 || cats.includes(String(p.category).toLowerCase());
            const colOk = cols.length === 0 || cols.includes(String(p.collection).toLowerCase());
            const priceOk = parseFloat(p.price) >= min && parseFloat(p.price) <= max;
            const colorOk = colors.length === 0 || Object.keys(p.colors || {}).some(c => colors.includes(c.toLowerCase()));
            const sizeOk = sizes.length === 0 || Object.keys(p.sizes || {}).some(s => sizes.includes(String(s).toLowerCase()));

            return nameOk && catOk && colOk && priceOk && colorOk && sizeOk;
        });

        renderProducts(filtered);
    }

    function resetFilters() {
        document.querySelectorAll('#filterDrawer input').forEach(i => {
            if (i.type === 'checkbox') i.checked = false;
            else i.value = '';
        });

        renderProducts(PRODUCTS);
    }

    function firstImage(p) {
        const colors = Object.values(p.colors || {});
        return colors.length ? colors[0].image : p.main_image;
    }

    function safeKey(value) {
        return String(value).replaceAll(' ', '_').replaceAll('/', '_').replaceAll('#', '');
    }

    function findVariant(productId) {
        const p = PRODUCTS.find(x => parseInt(x.id) === parseInt(productId));
        if (!p) return null;

        const opt = selectedOptions[productId] || {};
        if (!opt.color || !opt.size) return null;

        return p.variants.find(v =>
            String(v.color).toLowerCase() === String(opt.color).toLowerCase() &&
            String(v.size).toLowerCase() === String(opt.size).toLowerCase() &&
            parseInt(v.quantity) > 0
        );
    }

    function selectColor(productId, color, image) {
        const img = document.getElementById('img-' + productId);
        if (img) img.src = image;

        document.querySelectorAll('.color-btn-' + productId).forEach(b => {
            b.classList.remove('ring-2', 'ring-black');
        });

        event.currentTarget.classList.add('ring-2', 'ring-black');

        selectedOptions[productId] = selectedOptions[productId] || {};
        selectedOptions[productId].color = color;

        const variant = findVariant(productId);
        if (variant) selectedOptions[productId].variant_id = variant.variant_id;
    }

    function selectSize(productId, size) {
        document.querySelectorAll('.size-btn-' + productId).forEach(b => {
            b.classList.remove('active');
        });

        event.currentTarget.classList.add('active');

        selectedOptions[productId] = selectedOptions[productId] || {};
        selectedOptions[productId].size = size;

        const variant = findVariant(productId);
        if (variant) {
            selectedOptions[productId].variant_id = variant.variant_id;
        } else {
            selectedOptions[productId].variant_id = null;
        }
    }

    function addToCart(productId) {
        if (!isLoggedIn) {
            location.href = withLang('signin.php');
            return;
        }

        const opt = selectedOptions[productId] || {};

        if (!opt.color || !opt.size) {
            showToast('Choose color and size first');
            return;
        }

        if (!opt.variant_id) {
            showToast('This color/size is sold out');
            return;
        }

        const body = new URLSearchParams();
        body.append('variant_id', opt.variant_id);

        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body.toString()
        })
            .then(r => r.text())
            .then(text => {
                let data;

                try {
                    data = JSON.parse(text);
                } catch (e) {
                    alert('في خطأ داخل add_to_cart.php: ' + text);
                    return;
                }

                if (data.status === 'login') {
                    location.href = withLang('signin.php');
                } else if (data.status === 'added') {
                    let count = document.getElementById('cart-count');
                    let n = parseInt(count.textContent || '0');
                    n++;

                    count.textContent = n;
                    count.classList.remove('hidden');

                    showToast('Added to bag ✓');
                } else {
                    showToast(data.message || 'Cart error');
                }
            });
    }


    function renderProducts(items) {
        const grid = document.getElementById('product-grid');
        grid.innerHTML = '';

        document.getElementById('product-count-label').textContent =
            `Showing ${items.length} piece${items.length !== 1 ? 's' : ''}`;

        items.forEach(p => {
            const liked = WISHLIST_IDS.includes(parseInt(p.id));
            const colors = Object.values(p.colors || {});
            const sizes = Object.values(p.sizes || {});

            const card = document.createElement('div');
            card.className = 'product-card';

            card.innerHTML = `
                <div class="relative overflow-hidden rounded-sm bg-zinc-100" style="aspect-ratio:3/4;">
                    <img id="img-${p.id}" src="${firstImage(p)}" class="product-img w-full h-full object-cover" alt="${p.name}">

                    ${parseInt(p.is_new) === 1 ? `<div class="new-ribbon">NEW</div>` : ''}

                    <span class="absolute top-3 left-3 px-2 py-1 uppercase font-medium text-[9px] tracking-widest bg-black text-white z-10">
                        ${colors.length} Color${colors.length !== 1 ? 's' : ''}
                    </span>

                    <button class="heart-btn ${liked ? 'liked' : ''} absolute top-3 right-3 w-8 h-8 rounded-full flex items-center justify-center bg-white/90 z-30"
                            onclick="toggleWishlist(${p.id}, this)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="${liked ? '#000' : 'none'}" stroke="#000" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                        </svg>
                    </button>

                    <div class="quick-actions absolute bottom-3 left-3 right-3 z-30">
                        <button onclick="addToCart(${p.id})" class="w-full py-2.5 text-xs tracking-widest uppercase rounded-sm bg-black text-white">
                            Add to Bag
                        </button>
                    </div>
                </div>

                <div class="mt-3 px-0.5">
                    <h3 class="text-sm font-light">${p.name}</h3>
                    <p class="text-xs mt-1 text-zinc-500">$${p.price}</p>
                    <p class="text-[11px] mt-1 text-zinc-400">${p.category} · ${p.collection}</p>

                    <div class="flex gap-2 mt-3 flex-wrap">
                       ${colors.map(c => `
    <button
        class="color-btn-${p.id} w-7 h-7 rounded-full border border-zinc-300"
        style="background:${c.css}"
        onclick="selectColor(${p.id}, '${c.name}', '${c.image}')">
    </button>
`).join('')}
                    </div>

                    <div class="flex gap-2 mt-3 flex-wrap">
                      ${sizes.map(s => `
    <button id="size-${p.id}-${String(s).replaceAll(' ','_')}"
            onclick="selectSize(${p.id}, '${s}')"
            class="size-btn size-btn-${p.id} px-3 py-1 border border-zinc-300 rounded-full text-xs">
        ${s}
    </button>
`).join('')}
                    </div>
                </div>
            `;

            grid.appendChild(card);
        });

        lucide.createIcons();
    }



    function updateCartBadge() {
        const el = document.getElementById('cart-count');
        el.textContent = cartCounter;
        el.classList.toggle('hidden', cartCounter === 0);
    }

    function toggleWishlist(id, btn) {
        if (!isLoggedIn) {
            location.href = withLang('signin.php');
            return;
        }

        fetch('toggle_wishlist.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'product_id=' + encodeURIComponent(id)
        })
            .then(r => r.text())
            .then(text => {
                let data;

                try {
                    data = JSON.parse(text);
                } catch(e) {
                    alert('في خطأ داخل toggle_wishlist.php: ' + text);
                    return;
                }

                const count = document.getElementById('fav-count');
                let n = parseInt(count.textContent || '0');

                if (data.status === 'added') {
                    btn.classList.add('liked');
                    btn.querySelector('svg').setAttribute('fill','#000');
                    n++;
                    showToast('Added to wishlist ✓');
                } else if (data.status === 'removed') {
                    btn.classList.remove('liked');
                    btn.querySelector('svg').setAttribute('fill','none');
                    n = Math.max(0, n - 1);
                    showToast('Removed from wishlist');
                } else if (data.status === 'login') {
                    location.href = withLang('signin.php');
                } else {
                    showToast(data.message || 'Wishlist error');
                }

                count.textContent = n;
                count.classList.toggle('hidden', n === 0);
            });
    }

    function openBag() {
        document.getElementById('bag-panel').classList.add('open');
        document.getElementById('bag-overlay').classList.add('open');
    }

    function closeBag() {
        document.getElementById('bag-panel').classList.remove('open');
        document.getElementById('bag-overlay').classList.remove('open');
    }

    function showToast(msg) {
        const t = document.getElementById('toast');
        document.getElementById('toast-msg').textContent = msg;
        t.classList.add('show');

        setTimeout(() => t.classList.remove('show'), 2200);
    }



    document.getElementById('main-dropdown-btn')?.addEventListener('click', e => {
        e.stopPropagation();
        document.getElementById('main-dropdown').classList.toggle('hidden');
    });

    document.getElementById('main-dropdown')?.addEventListener('click', e => e.stopPropagation());

    document.addEventListener('click', () => {
        document.getElementById('main-dropdown')?.classList.add('hidden');
    });

    document.getElementById('mobile-toggle')?.addEventListener('click', () => {
        document.getElementById('mobile-menu').classList.toggle('open');
    });

    renderProducts(PRODUCTS);
    lucide.createIcons();
</script>

</body>
</html>