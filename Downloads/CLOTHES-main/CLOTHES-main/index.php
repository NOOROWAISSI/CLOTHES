<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "fashion_store";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserName = $_SESSION['full_name'] ?? "";

$lang = $_GET['lang'] ?? 'en';

$products_sql = "
SELECT 
    p.product_id,
    p.price,
    pt.product_name,
    pt.description,
    pi.image_url
FROM products p
JOIN product_translations pt 
    ON p.product_id = pt.product_id
LEFT JOIN product_images pi 
    ON p.product_id = pi.product_id AND pi.is_main = 1
WHERE pt.language_code = ?
ORDER BY p.created_at DESC
LIMIT 4
";

$stmt = $conn->prepare($products_sql);
$stmt->bind_param("s", $lang);
$stmt->execute();
$products_result = $stmt->get_result();

$categories_sql = "
SELECT 
    c.category_key,
    ct.category_name
FROM categories c
JOIN category_translations ct 
    ON c.category_id = ct.category_id
WHERE ct.language_code = ?
ORDER BY c.category_id
";

$stmt2 = $conn->prepare($categories_sql);
$stmt2->bind_param("s", $lang);
$stmt2->execute();
$categories_result = $stmt2->get_result();

function imgName($key) {
    $map = [
            'jeans' => 'jeans.jpg',
            'pants' => 'Pants.png',
            'blouses' => 'Blouses.jpg',
            'shirts' => 'Shirts.jpg',
            'dresses' => 'Dresses.jpg',
            'formal' => 'Formal.jpg',
            'jackets' => 'Jackets.jpg',
            'abaya' => 'Abaya.jpg',
            'skirts' => 'Skirts.jpg',
            'bags' => 'Bags.jpg',
            'belts' => 'Belts.jpg',
            'vests' => 'Vests.jpg',
            'overalls' => 'Overalls.jpg',
            'outfits' => 'Full.png',
            'casual' => 'Casual.jpg',
            'blazers' => 'Blazers.jpg'
    ];

    return $map[$key] ?? 'default.jpg';
}
?>

<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>" class="h-full" <?= $lang === 'ar' || $lang === 'he' ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>demoiselle — Timeless Feminine Fashion</title>

    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            min-height: 100vh;
            overflow-y: auto;
        }

        .font-display { font-family: 'Cormorant Garamond', serif; }
        .font-body { font-family: 'Outfit', sans-serif; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .anim-fade-up { animation: fadeUp 0.8s ease forwards; }
        .anim-slide-down { animation: slideDown 0.6s ease forwards; }

        .delay-1 { animation-delay: 0.1s; opacity: 0; }
        .delay-2 { animation-delay: 0.25s; opacity: 0; }
        .delay-3 { animation-delay: 0.4s; opacity: 0; }
        .delay-4 { animation-delay: 0.55s; opacity: 0; }
        .delay-5 { animation-delay: 0.7s; opacity: 0; }

        .nav-link { position: relative; }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: currentColor;
            transition: width 0.3s ease;
        }

        .nav-link:hover::after { width: 100%; }

        .btn-noir {
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
        }

        .btn-noir:hover { transform: translateY(-2px); }

        #hero {
            background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.55)), url('pic/home1.jpeg') center/cover no-repeat fixed;
            min-height: 100vh;
        }

        #logo-img {
            height: 62px;
            width: auto;
            max-width: 200px;
        }

        @media (max-width: 768px) {
            #logo-img { height: 50px; }
        }

        .mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, opacity 0.3s ease;
            opacity: 0;
        }

        .mobile-menu.open {
            max-height: 600px;
            opacity: 1;
        }

        .marquee-track {
            display: flex;
            width: max-content;
            animation: marquee 25s linear infinite;
        }

        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .product-card:hover .product-overlay { opacity: 1; }
        .product-card:hover .main-img { transform: scale(1.05); }

        .product-image-container {
            position: relative;
            overflow: hidden;
        }

        .logo-watermark {
            position: absolute;
            top: 18px;
            left: 18px;
            width: 78px;
            height: 78px;
            opacity: 0.85;
            z-index: 10;
            pointer-events: none;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.45));
        }

        .new-collection-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.48);
            z-index: 5;
            transition: opacity 0.7s ease;
        }

        .product-card:hover .new-collection-overlay {
            opacity: 0;
        }

        .new-tag {
            position: absolute;
            top: 14px;
            right: 14px;
            background: #fff;
            color: #000;
            font-size: 9px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            z-index: 15;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .cats-marquee {
            width: 100vw;
            overflow-x: hidden;
            overflow-y: visible;
            position: relative;
            padding: 70px 0 50px;
        }

        .cats-track {
            display: flex;
            align-items: center;
            width: max-content;
            padding-top: 30px;
            padding-bottom: 30px;
            animation: catsMove 35s linear infinite;
        }

        @keyframes catsMove {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .cat-card {
            position: relative;
            width: 160px;
            height: 300px;
            margin-right: -28px;
            overflow: hidden;
            border-radius: 20px;
            cursor: pointer;
            flex: 0 0 auto;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            transform-origin: center center;
        }

        .cat-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .cat-card span {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.5);
            color: #fff;
            font-size: 18px;
            opacity: 0;
            transition: opacity 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cat-card:hover {
            transform: translateY(-12px) scale(1.12);
            z-index: 30;
            box-shadow: 0 18px 34px rgba(0,0,0,0.28);
        }

        .cat-card:hover span { opacity: 1; }

        .cats-marquee:hover .cats-track {
            animation-play-state: paused;
        }

        @media (max-width: 768px) {
            .cat-card {
                width: 130px;
                height: 170px;
                margin-right: -18px;
            }

            .cat-card span { font-size: 14px; }
        }

        @media (max-width: 480px) {
            .cat-card {
                width: 110px;
                height: 145px;
                margin-right: -12px;
                border-radius: 16px;
            }

            .cat-card:hover {
                transform: scale(1.12) translateY(-6px);
            }
        }

        .about-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .about-image-container:hover .about-image {
            transform: scale(1.03);
        }

        .dropdown-link {
            color: rgba(255,255,255,0.75);
            transition: 0.25s ease;
        }

        .dropdown-link:hover {
            color: #fff;
            transform: translateX(3px);
        }

        svg { stroke: #fff !important; }


        /* ===== ACCESSIBILITY ===== */
        .accessibility-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 300;
            background: #fff;
            color: #000;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.4);
            cursor: pointer;
        }

        .accessibility-panel {
            position: fixed;
            bottom: 95px;
            right: 25px;
            width: 280px;
            background: #fff;
            color: #000;
            padding: 18px;
            border-radius: 18px;
            display: none;
            z-index: 300;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        .accessibility-panel.open {
            display: block;
        }

        .accessibility-panel button {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #f7f7f7;
            cursor: pointer;
        }

        .accessibility-panel button:hover {
            background: #000;
            color: #fff;
        }

        /* modes */
        body.large-text { font-size: 120%; }
        body.high-contrast { filter: contrast(1.5); }
        body.no-motion * { animation:none!important; transition:none!important; }
        body.readable-font * { font-family: Arial!important; }
    </style>
</head>

<body class="font-body" style="background:#000; color:#fff;">

<div id="app-wrapper" class="w-full overflow-auto" style="background:#000;">

    <!-- HEADER -->
    <header class="w-full fixed top-0 left-0 z-50 anim-slide-down" style="background:rgba(0,0,0,0.9); backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,0.08);">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">

            <a href="index.php" class="flex items-center">
                <img src="pic/lolo1.png" alt="demoiselle Logo" id="logo-img">
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm tracking-wider uppercase font-light" style="color:rgba(255,255,255,0.7);">
                <a href="#hero" class="nav-link hover:text-white transition-colors">Home</a>
                <a href="#collection" class="nav-link hover:text-white transition-colors">Collection</a>
                <a href="#bestsellers" class="nav-link hover:text-white transition-colors">Categories</a>
                <a href="#about" class="nav-link hover:text-white transition-colors">Our Story</a>
                <a href="#footer" class="nav-link hover:text-white transition-colors">Contact</a>
            </nav>

            <div class="hidden md:flex items-center gap-4 relative">

                <!-- ثلاث شحطات واحدة فيها اللغات + الكاتيجوري + الصفحات -->
                <button id="main-dropdown-btn" title="Menu">
                    <i data-lucide="menu" style="width:24px;height:24px;"></i>
                </button>

                <div id="main-dropdown" class="hidden absolute top-10 right-0 w-96 max-h-[80vh] overflow-y-auto bg-black border border-white/10 rounded-2xl p-6 shadow-2xl z-[200] text-left">

                    <h4 class="text-xs tracking-[0.3em] uppercase text-white/40 mb-3">Languages</h4>

                    <div class="flex gap-3 mb-6">
                        <a href="index.php?lang=en" class="px-3 py-1 border border-white/20 rounded-full text-xs hover:bg-white hover:text-black transition">EN</a>
                        <a href="index.php?lang=ar" class="px-3 py-1 border border-white/20 rounded-full text-xs hover:bg-white hover:text-black transition">AR</a>
                        <a href="index.php?lang=he" class="px-3 py-1 border border-white/20 rounded-full text-xs hover:bg-white hover:text-black transition">HE</a>
                    </div>

                    <h4 class="text-xs tracking-[0.3em] uppercase text-white/40 mb-3">Categories</h4>

                    <div class="grid grid-cols-2 gap-3 text-sm mb-6">
                        <a class="dropdown-link" href="jeans.php?lang=<?= htmlspecialchars($lang) ?>">Jeans</a>
                        <a class="dropdown-link" href="pants.php?lang=<?= htmlspecialchars($lang) ?>">Pants</a>
                        <a class="dropdown-link" href="blouses.php?lang=<?= htmlspecialchars($lang) ?>">Blouses</a>
                        <a class="dropdown-link" href="shirts.php?lang=<?= htmlspecialchars($lang) ?>">Shirts</a>
                        <a class="dropdown-link" href="dresses.php?lang=<?= htmlspecialchars($lang) ?>">Dresses</a>
                        <a class="dropdown-link" href="formal.php?lang=<?= htmlspecialchars($lang) ?>">Formal</a>
                        <a class="dropdown-link" href="jackets.php?lang=<?= htmlspecialchars($lang) ?>">Jackets</a>
                        <a class="dropdown-link" href="abaya.php?lang=<?= htmlspecialchars($lang) ?>">Abaya</a>
                        <a class="dropdown-link" href="skirts.php?lang=<?= htmlspecialchars($lang) ?>">Skirts</a>
                        <a class="dropdown-link" href="bags.php?lang=<?= htmlspecialchars($lang) ?>">Bags</a>
                        <a class="dropdown-link" href="belts.php?lang=<?= htmlspecialchars($lang) ?>">Belts</a>
                        <a class="dropdown-link" href="vests.php?lang=<?= htmlspecialchars($lang) ?>">Vests</a>
                        <a class="dropdown-link" href="overalls.php?lang=<?= htmlspecialchars($lang) ?>">Overalls</a>
                        <a class="dropdown-link" href="outfits.php?lang=<?= htmlspecialchars($lang) ?>">Outfits</a>
                        <a class="dropdown-link" href="casual.php?lang=<?= htmlspecialchars($lang) ?>">Casual</a>
                        <a class="dropdown-link" href="blazers.php?lang=<?= htmlspecialchars($lang) ?>">Blazers</a>
                    </div>

                    <h4 class="text-xs tracking-[0.3em] uppercase text-white/40 mb-3">Pages</h4>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <a class="dropdown-link" href="index.php">Home</a>
                        <a class="dropdown-link" href="new_collection.php">New Collection</a>
                        <a class="dropdown-link" href="search.php">Search</a>
                        <a class="dropdown-link" href="cart.php">Cart</a>
                        <a class="dropdown-link" href="wishlist.php">Wishlist</a>
                        <a class="dropdown-link" href="order.php">Order</a>
                        <a class="dropdown-link" href="profile.php">Profile</a>
                        <a class="dropdown-link" href="contact.php">Contact</a>
                        <a class="dropdown-link" href="about.php">About</a>
                        <a class="dropdown-link" href="signin.php">Sign In</a>
                        <a class="dropdown-link" href="signup.php">Sign Up</a>
                        <a class="dropdown-link" href="logout.php">Logout</a>
                    </div>

                </div>

                <button onclick="goPage('search.php', true)">
                    <i data-lucide="search" style="width:18px;height:18px;"></i>
                </button>

                <!-- إذا داخل يروح profile، إذا مش داخل signin -->
                <button onclick="goUserPage()">
                    <i data-lucide="user" style="width:18px;height:18px;"></i>
                </button>

                <button onclick="goPage('wishlist.php', true)" id="wishlist-btn" class="relative">
                    <i data-lucide="heart" style="width:18px;height:18px;"></i>
                    <span id="wishlist-count" class="absolute -top-1 -right-2 text-[10px] w-4 h-4 rounded-full flex items-center justify-center bg-white text-black hidden">0</span>
                </button>

                <button onclick="goPage('cart.php', true)" id="cart-btn" class="relative">
                    <i data-lucide="shopping-bag" style="width:18px;height:18px;"></i>
                    <span id="cart-count" class="absolute -top-1 -right-2 text-[10px] w-4 h-4 rounded-full flex items-center justify-center bg-white text-black">0</span>
                </button>

                <?php if ($isLoggedIn): ?>
                    <span class="text-xs text-white/60">
                        <?= htmlspecialchars($currentUserName) ?>
                    </span>
                <?php endif; ?>
            </div>

            <button class="md:hidden text-white" id="mobile-toggle">
                <i data-lucide="menu" style="width:24px;height:24px;"></i>
            </button>
        </div>

        <!-- MOBILE MENU -->
        <div class="mobile-menu md:hidden px-6 pb-4" id="mobile-menu">
            <nav class="flex flex-col gap-4 text-sm tracking-wider uppercase font-light pt-2" style="color:rgba(255,255,255,0.7); border-top:1px solid rgba(255,255,255,0.08);">
                <a href="#hero">Home</a>
                <a href="#collection">Collection</a>
                <a href="#bestsellers">Categories</a>
                <a href="#about">Our Story</a>
                <a href="#footer">Contact</a>

                <div class="flex gap-3 pt-2">
                    <a href="index.php?lang=en">EN</a>
                    <a href="index.php?lang=ar">AR</a>
                    <a href="index.php?lang=he">HE</a>
                </div>

                <button onclick="goUserPage()" class="text-left">User / Sign In</button>
                <button onclick="goPage('cart.php', true)" class="text-left">Cart</button>
                <button onclick="goPage('wishlist.php', true)" class="text-left">Wishlist</button>
            </nav>
        </div>
    </header>

    <!-- HERO -->
    <section id="hero" class="w-full relative flex items-center justify-center">
        <div class="relative z-10 text-center px-6 max-w-4xl mx-auto pt-20 pb-16">
            <p class="text-xs tracking-[0.4em] uppercase mb-6 anim-fade-up delay-1" style="color:rgba(255,255,255,0.85);">
                Autumn / Winter 2025
            </p>

            <h1 class="font-display text-5xl sm:text-7xl md:text-8xl font-light leading-[0.95] mb-6 anim-fade-up delay-2" style="color:#fff;">
                Elegance<br>Redefined
            </h1>

            <p class="text-sm sm:text-base font-light tracking-wide max-w-md mx-auto mb-10 anim-fade-up delay-3" style="color:rgba(255,255,255,0.9);">
                Timeless feminine pieces crafted for the modern woman who values grace, quality, and intention.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center anim-fade-up delay-4">
                <a href="#collection" class="btn-noir inline-block px-10 py-4 text-xs tracking-[0.3em] uppercase border" style="background:#fff; color:#000; border-color:#fff;">
                    Shop New Collection
                </a>
                <a href="#bestsellers" class="btn-noir inline-block px-10 py-4 text-xs tracking-[0.3em] uppercase border" style="background:transparent; color:#fff; border-color:rgba(255,255,255,0.6);">
                    Shop Categories
                </a>
            </div>
        </div>
    </section>

    <!-- MARQUEE -->
    <div class="w-full py-4 overflow-hidden" style="background:#fff; color:#000;">
        <div class="marquee-track">
            <span class="font-display text-lg tracking-[0.3em] uppercase mx-8 whitespace-nowrap">Free Shipping on Orders Over $150</span>
            <span class="mx-4 opacity-30">✦</span>
            <span class="font-display text-lg tracking-[0.3em] uppercase mx-8 whitespace-nowrap">Ethically Sourced Materials</span>
            <span class="mx-4 opacity-30">✦</span>
            <span class="font-display text-lg tracking-[0.3em] uppercase mx-8 whitespace-nowrap">Timeless & Sustainable</span>
            <span class="mx-4 opacity-30">✦</span>
            <span class="font-display text-lg tracking-[0.3em] uppercase mx-8 whitespace-nowrap">New Collection Now Available</span>
        </div>
    </div>

    <!-- NEW COLLECTION FROM SQL -->
    <section id="collection" class="w-full py-20 px-6" style="background:#0a0a0a;">
        <div class="max-w-7xl mx-auto">

            <div class="text-center mb-16">
                <p class="text-xs tracking-[0.4em] uppercase mb-3" style="color:rgba(255,255,255,0.35);">Just Arrived</p>
                <h2 class="font-display text-4xl sm:text-5xl font-light" style="color:#fff;">New Collection</h2>
                <div class="w-12 h-px mx-auto mt-6" style="background:rgba(255,255,255,0.2);"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

                <?php if ($products_result->num_rows > 0): ?>
                    <?php while($product = $products_result->fetch_assoc()): ?>
                        <div class="product-card group cursor-pointer new-collection">

                            <div class="product-image-container relative aspect-[3/4] overflow-hidden mb-4">
                                <img
                                        src="<?= htmlspecialchars($product['image_url'] ?: 'pic/default.jpg') ?>"
                                        alt="<?= htmlspecialchars($product['product_name']) ?>"
                                        class="main-img w-full h-full object-cover transition-transform duration-500"
                                >

                                <img src="pic/logo2.png" class="logo-watermark" alt="demoiselle">
                                <div class="new-collection-overlay"></div>
                                <span class="new-tag">NEW</span>

                                <div class="product-overlay absolute inset-0 flex items-end justify-center pb-6 opacity-0 transition-opacity duration-300" style="background:rgba(0,0,0,0.65);">
                                    <button
                                            onclick="quickAdd(<?= (int)$product['product_id'] ?>)"
                                            class="px-8 py-3 text-[10px] tracking-[0.2em] uppercase transition-all hover:opacity-80"
                                            style="background:#fff; color:#000;">
                                        Quick Add
                                    </button>
                                </div>
                            </div>

                            <h3 class="text-sm font-light mb-1" style="color:#fff;">
                                <?= htmlspecialchars($product['product_name']) ?>
                            </h3>

                            <p class="text-xs" style="color:rgba(255,255,255,0.45);">
                                $<?= htmlspecialchars($product['price']) ?>
                            </p>

                            <button
                                    onclick="toggleWishlist(<?= (int)$product['product_id'] ?>, this)"
                                    class="mt-3 text-xl wishlist-btn">
                                ♡
                            </button>

                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center col-span-4 text-white/50">No products found.</p>
                <?php endif; ?>

            </div>
        </div>
    </section>

    <!-- CATEGORIES FROM SQL -->
    <section id="bestsellers" class="py-24 bg-white text-black text-center overflow-hidden">
        <div class="px-6">
            <h2 class="font-display text-5xl mb-16">Shop by Category</h2>

            <div class="cats-marquee">
                <div class="cats-track">

                    <?php if ($categories_result->num_rows > 0): ?>
                        <?php while($cat = $categories_result->fetch_assoc()): ?>
                            <a href="<?= htmlspecialchars($cat['category_key']) ?>.php?lang=<?= htmlspecialchars($lang) ?>" class="cat-card">
                                <img
                                        src="pic/<?= htmlspecialchars(imgName($cat['category_key'])) ?>"
                                        alt="<?= htmlspecialchars($cat['category_name']) ?>"
                                >
                                <span><?= htmlspecialchars($cat['category_name']) ?></span>
                            </a>
                        <?php endwhile; ?>

                        <?php
                        $categories_result->data_seek(0);
                        while($cat = $categories_result->fetch_assoc()):
                            ?>
                            <a href="<?= htmlspecialchars($cat['category_key']) ?>.php?lang=<?= htmlspecialchars($lang) ?>" class="cat-card">
                                <img
                                        src="pic/<?= htmlspecialchars(imgName($cat['category_key'])) ?>"
                                        alt="<?= htmlspecialchars($cat['category_name']) ?>"
                                >
                                <span><?= htmlspecialchars($cat['category_name']) ?></span>
                            </a>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <p>No categories found.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </section>

    <!-- ABOUT -->
    <section id="about" class="w-full py-24 px-6" style="background:#000;">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-16 items-center">

            <div class="about-image-container relative overflow-hidden aspect-[3/4] border border-white/10">
                <img src="pic/about1.jpg" alt="Demoiselle Story" class="about-image">

                <div class="absolute bottom-6 left-6 right-6">
                    <p class="text-[10px] tracking-[0.3em] uppercase" style="color:rgba(255,255,255,0.25);">
                        Est. 2019 • Palestine
                    </p>
                </div>
            </div>

            <div>
                <p class="text-xs tracking-[0.4em] uppercase mb-4" style="color:rgba(255,255,255,0.35);">Our Story</p>

                <h2 class="font-display text-4xl sm:text-5xl font-light mb-8" style="color:#fff;">
                    Demoiselle
                </h2>

                <p class="text-sm leading-relaxed mb-6 font-light" style="color:rgba(255,255,255,0.55);">
                    Founded in the heart of Palestine in 2019, demoiselle was born from a deep love for feminine elegance and conscious design.
                </p>

                <p class="text-sm leading-relaxed mb-10 font-light" style="color:rgba(255,255,255,0.55);">
                    We create timeless wardrobe essentials that celebrate the modern woman — refined silhouettes, premium fabrics, and meticulous attention to detail.
                </p>
            </div>
        </div>
    </section>

    <!-- NEWSLETTER -->
    <section class="w-full py-20 px-6" style="background:#111; border-top:1px solid rgba(255,255,255,0.05); border-bottom:1px solid rgba(255,255,255,0.05);">
        <div class="max-w-xl mx-auto text-center">
            <h3 class="font-display text-3xl font-light mb-3" style="color:#fff;">Join the Circle</h3>

            <p class="text-sm font-light mb-8" style="color:rgba(255,255,255,0.4);">
                Subscribe to receive early access to new collections, exclusive offers, and style inspiration.
            </p>

            <form id="newsletter-form" class="flex gap-3 max-w-md mx-auto">
                <input
                        id="email-input"
                        type="email"
                        placeholder="your@email.com"
                        class="flex-1 px-4 py-3 text-sm font-light outline-none"
                        style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#fff;"
                        required
                >

                <button
                        type="submit"
                        class="px-6 py-3 text-xs tracking-[0.2em] uppercase transition-all hover:opacity-80"
                        style="background:#fff; color:#000;">
                    Subscribe
                </button>
            </form>

            <p id="newsletter-msg" class="text-xs mt-4 hidden" style="color:rgba(255,255,255,0.5);"></p>
        </div>
    </section>
    <!-- Accessibility Button -->
    <button class="accessibility-btn" id="accessibility-btn">♿</button>

    <!-- Accessibility Panel -->

    <div class="accessibility-panel" id="accessibility-panel">
        <h3 style="font-size:18px; margin-bottom:6px;">Accessibility Tools</h3>
        <p style="font-size:12px; color:#555;">Customize your experience</p>

        <button onclick="toggleLargeText()">Big Text</button>
        <button onclick="toggleContrast()">Contrast</button>
        <button onclick="toggleMotion()">No Motion</button>
        <button onclick="toggleFont()">Readable Font</button>

        <button onclick="toggleClickRead()">👆 Read Clicked Text</button>
        <button onclick="readAllPage()">🔊 Read All Page</button>
        <button onclick="stopRead()">⛔ Stop Reading</button>

        <button onclick="resetAccessibility()">Reset</button>
    </div>
    <!-- FOOTER -->
    <footer id="footer" class="w-full py-16 px-6" style="background:#000; border-top:1px solid rgba(255,255,255,0.06);">
        <div class="max-w-7xl mx-auto">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-14">
                <div>
                    <a href="index.php" class="inline-block mb-4">
                        <img src="pic/lolo1.png" alt="demoiselle Logo" class="h-10 w-auto">
                    </a>

                    <p class="text-xs font-light leading-relaxed" style="color:rgba(255,255,255,0.35);">
                        Timeless elegance. Conscious fashion.
                    </p>
                </div>

                <div>
                    <h4 class="text-[10px] tracking-[0.3em] uppercase mb-5" style="color:rgba(255,255,255,0.5);">Shop</h4>
                    <nav class="flex flex-col gap-3">
                        <a href="#collection" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);">New Arrivals</a>
                        <a href="#bestsellers" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);">Categories</a>
                    </nav>
                </div>

                <div>
                    <h4 class="text-[10px] tracking-[0.3em] uppercase mb-5" style="color:rgba(255,255,255,0.5);">Company</h4>
                    <nav class="flex flex-col gap-3">
                        <a href="#about" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);">Our Story</a>
                    </nav>
                </div>

                <div>
                    <h4 class="text-[10px] tracking-[0.3em] uppercase mb-5" style="color:rgba(255,255,255,0.5);">Support</h4>
                    <nav class="flex flex-col gap-3">
                        <a href="https://www.instagram.com/demoisellepal" target="_blank" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);">Instagram</a>
                        <a href="contact.php" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);">Contact Us</a>
                    </nav>
                </div>
            </div>

            <div class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4" style="border-top:1px solid rgba(255,255,255,0.06);">
                <p class="text-[10px] tracking-[0.2em]" style="color:rgba(255,255,255,0.25);">
                    © 2025 demoiselle. All rights reserved.
                </p>

                <p class="text-[10px] tracking-[0.2em]" style="color:rgba(255,255,255,0.25);">
                    Made with love in Palestine
                </p>
            </div>

            <div class="mt-8 flex flex-wrap gap-4">
                <button onclick="goPage('order.php', true)" class="px-4 py-2 bg-white text-black">Order</button>
                <button onclick="window.location.href='new_collection.php'" class="px-4 py-2 bg-white text-black">New Collection</button>
                <button onclick="goPage('wishlist.php', true)" class="px-4 py-2 bg-white text-black">Wishlist</button>
                <button onclick="goUserPage()" class="px-4 py-2 bg-white text-black">User</button>
                <button onclick="window.location.href='item.php'" class="px-4 py-2 bg-white text-black">Item</button>
            </div>

        </div>
    </footer>

</div>

<script>
    lucide.createIcons();

    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

    let cart = [];
    let wishlist = [];

    function goPage(page, protectedPage = false) {
        if (protectedPage && !isLoggedIn) {
            window.location.href = 'signin.php';
            return;
        }

        window.location.href = page;
    }

    function goUserPage() {
        if (isLoggedIn) {
            window.location.href = 'profile.php';
        } else {
            window.location.href = 'signin.php';
        }
    }

    const dropdownBtn = document.getElementById('main-dropdown-btn');
    const dropdown = document.getElementById('main-dropdown');

    dropdownBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
    });

    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    document.addEventListener('click', function() {
        dropdown.classList.add('hidden');
    });

    window.quickAdd = function(id) {
        if (!isLoggedIn) {
            window.location.href = 'signin.php';
            return;
        }

        cart.push(id);
        document.getElementById('cart-count').textContent = cart.length;
        showToast('Added to cart ✓');
    };

    window.toggleWishlist = function(id, btn) {
        if (!isLoggedIn) {
            window.location.href = 'signin.php';
            return;
        }

        if (wishlist.includes(id)) {
            wishlist = wishlist.filter(item => item !== id);
            btn.textContent = '♡';
            btn.style.color = '';
        } else {
            wishlist.push(id);
            btn.textContent = '♥';
            btn.style.color = '#ef4444';
        }

        const countEl = document.getElementById('wishlist-count');
        countEl.textContent = wishlist.length;
        countEl.classList.toggle('hidden', wishlist.length === 0);
    };

    function showToast(msg) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 bg-white text-black px-6 py-3 rounded-full text-sm shadow-xl z-[100]';
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    }

    document.getElementById('mobile-toggle').addEventListener('click', () => {
        document.getElementById('mobile-menu').classList.toggle('open');
    });

    document.getElementById('newsletter-form').addEventListener('submit', (e) => {
        e.preventDefault();

        if (!isLoggedIn) {
            window.location.href = 'signin.php';
            return;
        }

        const msg = document.getElementById('newsletter-msg');
        msg.textContent = 'Thank you! You’ve been added to our list.';
        msg.classList.remove('hidden');

        e.target.reset();

        setTimeout(() => msg.classList.add('hidden'), 4000);
    });

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));

            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('mobile-menu').classList.remove('open');
            }
        });
    });

    // ===== ACCESSIBILITY =====
    const accBtn = document.getElementById('accessibility-btn');
    const accPanel = document.getElementById('accessibility-panel');

    accBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        accPanel.classList.toggle('open');
    });

    accPanel.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    document.addEventListener('click', function() {
        accPanel.classList.remove('open');
    });

    function toggleLargeText() {
        document.body.classList.toggle('large-text');
        localStorage.setItem('largeText', document.body.classList.contains('large-text'));
    }

    function toggleContrast() {
        document.body.classList.toggle('high-contrast');
        localStorage.setItem('contrast', document.body.classList.contains('high-contrast'));
    }

    function toggleMotion() {
        document.body.classList.toggle('no-motion');
        localStorage.setItem('motion', document.body.classList.contains('no-motion'));
    }

    function toggleFont() {
        document.body.classList.toggle('readable-font');
        localStorage.setItem('font', document.body.classList.contains('readable-font'));
    }

    function resetAccessibility() {
        document.body.classList.remove('large-text','high-contrast','no-motion','readable-font');
        localStorage.removeItem('largeText');
        localStorage.removeItem('contrast');
        localStorage.removeItem('motion');
        localStorage.removeItem('font');

        clickReadEnabled = false;
        speechSynthesis.cancel();
    }
    /* ===== VOICE FEATURE ===== */

    let clickReadEnabled = false;

    function getVoiceLang() {
        let lang = document.documentElement.lang;

        if (lang === 'ar') return 'ar-SA';
        if (lang === 'he') return 'he-IL';
        return 'en-US';
    }

    function speakText(text) {
        if (!('speechSynthesis' in window)) {
            alert("Your browser does not support voice reading.");
            return;
        }

        if (!text || text.trim().length === 0) return;

        speechSynthesis.cancel();

        let speech = new SpeechSynthesisUtterance(text.trim());
        speech.lang = getVoiceLang();
        speech.rate = 0.9;
        speech.pitch = 1;

        speechSynthesis.speak(speech);
    }

    function toggleClickRead() {
        clickReadEnabled = !clickReadEnabled;

        if (clickReadEnabled) {
            showToast('Click-to-read is ON');
        } else {
            showToast('Click-to-read is OFF');
            speechSynthesis.cancel();
        }
    }

    function readAllPage() {
        let text = document.body.innerText;
        speakText(text);
    }

    function stopRead() {
        speechSynthesis.cancel();
    }

    document.addEventListener('click', function(e) {
        if (!clickReadEnabled) return;

        if (
            e.target.closest('#accessibility-panel') ||
            e.target.closest('#accessibility-btn') ||
            e.target.closest('button') ||
            e.target.closest('a')
        ) {
            return;
        }

        let text = e.target.innerText || e.target.textContent;
        speakText(text);
    });

    /* restore */
    if(localStorage.getItem('largeText')==='true') document.body.classList.add('large-text');
    if(localStorage.getItem('contrast')==='true') document.body.classList.add('high-contrast');
    if(localStorage.getItem('motion')==='true') document.body.classList.add('no-motion');
    if(localStorage.getItem('font')==='true') document.body.classList.add('readable-font');
</script>

</body>
</html>