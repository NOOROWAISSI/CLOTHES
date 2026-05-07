<?php
global $conn;
include_once "db.php";

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$lang = $_GET['lang'] ?? 'en';
$lang = in_array($lang, ['en','ar','he']) ? $lang : 'en';

$text = [
        'en' => [
                'home'=>'Home','collection'=>'Collection','categories'=>'Categories','our_story'=>'Our Story','contact'=>'Contact',
                'languages'=>'Languages','pages'=>'Pages','shop'=>'Shop','company'=>'Company','support'=>'Support',
                'new_arrivals'=>'New Arrivals','instagram'=>'Instagram','contact_us'=>'Contact Us',
                'new_collection'=>'New Collection','search'=>'Search','cart'=>'Cart','wishlist'=>'Wishlist','order'=>'Order',
                'profile'=>'Profile','about'=>'About','sign_in'=>'Sign In','sign_up'=>'Sign Up','logout'=>'Logout',
                'user_signin'=>'User / Sign In','footer_desc'=>'Timeless elegance. Conscious fashion.',
                'rights'=>'© 2025 demoiselle. All rights reserved.','made'=>'Made with love in Palestine'
        ],
        'ar' => [
                'home'=>'الرئيسية','collection'=>'المجموعة','categories'=>'الأقسام','our_story'=>'قصتنا','contact'=>'تواصل معنا',
                'languages'=>'اللغات','pages'=>'الصفحات','shop'=>'تسوق','company'=>'الشركة','support'=>'الدعم',
                'new_arrivals'=>'وصل حديثًا','instagram'=>'إنستغرام','contact_us'=>'تواصل معنا',
                'new_collection'=>'المجموعة الجديدة','search'=>'البحث','cart'=>'السلة','wishlist'=>'المفضلة','order'=>'الطلب',
                'profile'=>'الملف الشخصي','about'=>'من نحن','sign_in'=>'تسجيل الدخول','sign_up'=>'إنشاء حساب','logout'=>'تسجيل الخروج',
                'user_signin'=>'المستخدم / تسجيل الدخول','footer_desc'=>'أناقة خالدة. أزياء واعية.',
                'rights'=>'© 2025 ديموازيل. جميع الحقوق محفوظة.','made'=>'صنع بحب في فلسطين'
        ],
        'he' => [
                'home'=>'בית','collection'=>'קולקציה','categories'=>'קטגוריות','our_story'=>'הסיפור שלנו','contact'=>'צור קשר',
                'languages'=>'שפות','pages'=>'עמודים','shop'=>'חנות','company'=>'חברה','support'=>'תמיכה',
                'new_arrivals'=>'חדשים','instagram'=>'אינסטגרם','contact_us'=>'צור קשר',
                'new_collection'=>'קולקציה חדשה','search'=>'חיפוש','cart'=>'עגלה','wishlist'=>'מועדפים','order'=>'הזמנה',
                'profile'=>'פרופיל','about'=>'אודות','sign_in'=>'התחברות','sign_up'=>'הרשמה','logout'=>'התנתקות',
                'user_signin'=>'משתמש / התחברות','footer_desc'=>'אלגנטיות על־זמנית. אופנה מודעת.',
                'rights'=>'© 2025 demoiselle. כל הזכויות שמורות.','made'=>'נוצר באהבה בפלסטין'
        ]
];

$t = $text[$lang];

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: signin.php?lang=" . urlencode($lang));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? ($_SESSION['full_name'] ?? '');

function langUrl($newLang) {
    $params = $_GET;
    $params['lang'] = $newLang;
    return basename($_SERVER['PHP_SELF']) . '?' . http_build_query($params);
}

/* Wishlist count */
$wishlistCount = 0;
$wq = $conn->prepare("SELECT COUNT(*) AS cnt FROM favorites WHERE user_id=?");
$wq->bind_param("i", $user_id);
$wq->execute();
$wishlistCount = (int)$wq->get_result()->fetch_assoc()['cnt'];

/* Cart count */
$cartTopCount = 0;
$cq = $conn->prepare("
    SELECT COALESCE(SUM(quantity),0) AS cnt
    FROM cart
    WHERE user_id=?
");
$cq->bind_param("i", $user_id);
$cq->execute();
$cartTopCount = (int)$cq->get_result()->fetch_assoc()['cnt'];

/* Categories for dropdown */
$categories_result = null;
$catStmt = $conn->prepare("
    SELECT c.category_key, ct.category_name
    FROM categories c
    JOIN category_translations ct ON c.category_id = ct.category_id
    WHERE ct.language_code = ?
    ORDER BY c.category_id
");
$catStmt->bind_param("s", $lang);
$catStmt->execute();
$categories_result = $catStmt->get_result();

/* UPDATE QTY */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_qty'])) {
    $cart_id = (int)$_POST['cart_id'];
    $qty = max(1, (int)$_POST['quantity']);

    $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE cart_id=? AND user_id=?");
    $stmt->bind_param("iii", $qty, $cart_id, $user_id);
    $stmt->execute();

    header("Location: cart.php?lang=" . urlencode($lang));
    exit;
}

/* DELETE ITEM */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_item'])) {
    $cart_id = (int)$_POST['cart_id'];

    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id=? AND user_id=?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();

    header("Location: cart.php?lang=" . urlencode($lang));
    exit;
}

/* CART DATA */
$sql = "
SELECT
    c.cart_id,
    c.quantity AS cart_qty,
    pv.variant_id,
    pv.color,
    pv.size,
    pv.variant_image_url,
    p.product_id,
    p.price,
    COALESCE(pt.product_name, CONCAT('Product #', p.product_id)) AS product_name,
    COALESCE(pi.image_url, '') AS main_image
FROM cart c
JOIN product_variants pv ON c.variant_id = pv.variant_id
JOIN products p ON pv.product_id = p.product_id
LEFT JOIN product_translations pt
    ON p.product_id = pt.product_id AND pt.language_code = ?
LEFT JOIN product_images pi
    ON p.product_id = pi.product_id AND pi.is_main = 1
WHERE c.user_id = ?
ORDER BY c.cart_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $lang, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$subtotal = 0;
$totalQty = 0;

while ($row = $result->fetch_assoc()) {
    $row['image'] = $row['variant_image_url'] ?: ($row['main_image'] ?: 'pic/default.jpg');
    $row['line_total'] = (float)$row['price'] * (int)$row['cart_qty'];
    $subtotal += $row['line_total'];
    $totalQty += (int)$row['cart_qty'];
    $cartItems[] = $row;
}

$shipping = $subtotal >= 250 || $subtotal == 0 ? 0 : 15;
$total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" class="h-full" <?= ($lang === 'ar' || $lang === 'he') ? 'dir="rtl"' : 'dir="ltr"' ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shopping Bag | Demoiselle</title>

    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --black:#000;--white:#fff;--soft-white:#fafafa;--light-gray:#efefef;
            --text-gray:#777;--dark-gray:#1a1a1a;--transition:.3s ease;
            --shadow:0 12px 30px rgba(0,0,0,.08)
        }

        html,body{min-height:100vh;overflow-y:auto}
        body{font-family:"Outfit","Helvetica Neue",Arial,sans-serif;background:#fff;color:#000;line-height:1.5}
        a{text-decoration:none;color:inherit}
        button,input{font-family:inherit}
        img{width:100%;display:block}
        .font-display{font-family:'Cormorant Garamond',serif}

        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        @keyframes slideDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}

        .anim-slide-down{animation:slideDown .6s ease forwards}
        .nav-link{position:relative}
        .nav-link::after{content:'';position:absolute;bottom:-2px;left:0;width:0;height:1px;background:currentColor;transition:.3s}
        html[dir="rtl"] .nav-link::after{left:auto;right:0}
        .nav-link:hover::after{width:100%}

        #logo-img{height:62px;width:auto;max-width:200px}
        .mobile-menu{max-height:0;overflow:hidden;transition:max-height .4s ease,opacity .3s ease;opacity:0}
        .mobile-menu.open{max-height:900px;opacity:1}
        .dropdown-link{color:rgba(255,255,255,.75);transition:.25s}
        .dropdown-link:hover{color:#fff;transform:translateX(3px)}
        html[dir="rtl"] .dropdown-link:hover{transform:translateX(-3px)}
        .site-header svg{stroke:#fff!important}

        .page-hero{padding:150px 5% 30px;display:flex;justify-content:space-between;align-items:end;gap:20px;flex-wrap:wrap}
        .page-hero h1{font-size:clamp(32px,5vw,64px);font-weight:500;letter-spacing:2px;text-transform:uppercase;line-height:1}
        .page-hero p{max-width:500px;color:var(--text-gray);font-size:15px}

        .cart-wrapper{display:grid;grid-template-columns:1.6fr .9fr;gap:48px;padding:20px 5% 80px;align-items:start}
        .cart-count{font-size:13px;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-gray);margin-bottom:18px}
        .cart-actions-top{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:24px}
        .select-all-wrap{display:flex;align-items:center;gap:10px;font-size:13px;text-transform:uppercase;letter-spacing:1.2px;color:var(--dark-gray)}
        .cart-list{display:flex;flex-direction:column;gap:22px}

        .cart-item{display:grid;grid-template-columns:auto 140px 1fr;gap:22px;padding:20px;border:1px solid #efefef;border-radius:18px;background:#fff;transition:.3s;animation:fadeUp .5s ease}
        .cart-item:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
        .cart-item.selected{border-color:#000;box-shadow:0 12px 28px rgba(0,0,0,.09)}
        .item-check-wrap{display:flex;align-items:start;justify-content:center;padding-top:6px}
        .item-check{width:20px;height:20px;accent-color:black;cursor:pointer}
        .cart-item-image{width:100%;height:180px;border-radius:14px;overflow:hidden;background:#f6f6f6}
        .cart-item-image img{height:100%;object-fit:cover}
        .cart-item-info{display:flex;flex-direction:column;justify-content:space-between;gap:14px}
        .item-top{display:flex;justify-content:space-between;gap:20px}
        .item-title{font-size:20px;font-weight:500;letter-spacing:.6px}
        .remove-btn{border:none;background:transparent;font-size:24px;cursor:pointer;color:#999;transition:.3s}
        .remove-btn:hover{color:#000;transform:scale(1.08)}
        .item-meta{display:flex;flex-wrap:wrap;gap:18px;color:var(--text-gray);font-size:14px;text-transform:uppercase;letter-spacing:1.2px}
        .item-bottom{display:flex;justify-content:space-between;align-items:end;gap:20px;flex-wrap:wrap}
        .qty-box{display:inline-flex;align-items:center;border:1px solid #ddd;border-radius:999px;overflow:hidden;background:#fff}
        .qty-btn{width:42px;height:42px;border:none;background:transparent;font-size:18px;cursor:pointer;transition:.3s}
        .qty-btn:hover{background:#f5f5f5}
        .qty-value{width:55px;text-align:center;border:0;outline:none;font-size:15px;font-weight:500}
        .item-prices{text-align:right}
        html[dir="rtl"] .item-prices{text-align:left}
        .unit-price{color:var(--text-gray);font-size:13px;letter-spacing:1px;text-transform:uppercase}
        .line-total{font-size:22px;font-weight:600;margin-top:4px}

        .summary-section{position:sticky;top:110px}
        .summary-card{border:1px solid #ececec;border-radius:20px;padding:28px;background:#fafafa;box-shadow:0 8px 30px rgba(0,0,0,.04);animation:fadeUp .5s ease}
        .summary-title{font-size:24px;font-weight:500;margin-bottom:24px;text-transform:uppercase;letter-spacing:1.5px}
        .promo-box{display:flex;gap:10px;margin-bottom:24px}
        .promo-input{flex:1;height:48px;border:1px solid #dcdcdc;border-radius:999px;background:#fff;padding:0 16px;font-size:14px;outline:none}
        .promo-btn{height:48px;padding:0 22px;border:none;border-radius:999px;background:#000;color:#fff;cursor:pointer;font-size:13px;letter-spacing:1px;text-transform:uppercase}
        .summary-row{display:flex;justify-content:space-between;gap:15px;padding:12px 0;color:#1a1a1a;font-size:15px;border-bottom:1px solid #e8e8e8}
        .summary-row.total{border-bottom:none;padding-top:18px;margin-top:4px;font-size:20px;font-weight:700}
        .delivery-note,.selected-note{margin-top:14px;font-size:13px;color:#777;letter-spacing:.5px}
        .selected-note{text-transform:uppercase;letter-spacing:1px;font-size:12px}
        .checkout-btn,.continue-btn{width:100%;height:54px;border-radius:999px;font-size:14px;letter-spacing:1.4px;text-transform:uppercase;cursor:pointer;transition:.3s;margin-top:16px}
        .checkout-btn{background:#000;color:#fff;border:none;font-weight:600}
        .checkout-btn:hover{transform:translateY(-2px);opacity:.94}
        .continue-btn{background:transparent;color:#000;border:1px solid #000}
        .continue-btn:hover{background:#000;color:#fff}
        .secure-note{margin-top:18px;text-align:center;font-size:12px;color:#777;letter-spacing:1px;text-transform:uppercase}
        .trust-badges{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:22px}
        .trust-badge{border:1px solid #e7e7e7;border-radius:14px;background:#fff;padding:14px 10px;text-align:center;font-size:12px;color:#777;line-height:1.4}
        .trust-badge strong{display:block;color:#000;font-size:12px;margin-bottom:3px;text-transform:uppercase;letter-spacing:1px}

        .empty-cart{display:none;border:1px solid #ededed;border-radius:24px;padding:50px 24px;text-align:center;background:linear-gradient(to bottom,#fff,#fafafa);animation:fadeUp .4s ease}
        .empty-cart.show{display:block}
        .empty-cart h2{font-size:34px;margin-bottom:10px;font-weight:500;letter-spacing:1px;text-transform:uppercase}
        .empty-cart p{max-width:520px;margin:0 auto 28px;color:#777;font-size:15px}
        .empty-cart .continue-btn{width:auto;min-width:220px;padding:0 26px;display:inline-block}

        @media(max-width:1100px){
            .cart-wrapper{grid-template-columns:1fr}
            .summary-section{position:static}
        }
        @media(max-width:768px){
            #logo-img{height:50px}
            .page-hero{padding-top:120px}
            .cart-item{grid-template-columns:1fr}
            .item-check-wrap{justify-content:flex-start;padding-top:0}
            .cart-item-image{height:260px}
            .item-prices{text-align:left}
            .promo-box{flex-direction:column}
            .trust-badges{grid-template-columns:1fr}
        }
    </style>
</head>

<body>

<header class="site-header w-full fixed top-0 left-0 z-50 anim-slide-down" style="background:rgba(0,0,0,0.9); backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,0.08);">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">

        <a href="index.php?lang=<?= h($lang) ?>" class="flex items-center">
            <img src="pic/lolo1.png" alt="demoiselle Logo" id="logo-img">
        </a>

        <nav class="hidden md:flex items-center gap-8 text-sm tracking-wider uppercase font-light" style="color:rgba(255,255,255,0.7);">
            <a href="index.php?lang=<?= h($lang) ?>" class="nav-link hover:text-white transition-colors"><?= h($t['home']) ?></a>
            <a href="newcolc.php?lang=<?= h($lang) ?>" class="nav-link hover:text-white transition-colors"><?= h($t['collection']) ?></a>
            <a href="about.php?lang=<?= h($lang) ?>" class="nav-link hover:text-white transition-colors"><?= h($t['our_story']) ?></a>
            <a href="contact.php?lang=<?= h($lang) ?>" class="nav-link hover:text-white transition-colors"><?= h($t['contact']) ?></a>
        </nav>

        <div class="hidden md:flex items-center gap-4 relative text-white">
            <button id="main-dropdown-btn" title="Menu">
                <i data-lucide="menu" style="width:24px;height:24px;"></i>
            </button>

            <div id="main-dropdown" class="hidden absolute top-10 <?= ($lang === 'ar' || $lang === 'he') ? 'left-0 text-right' : 'right-0 text-left' ?> w-96 max-h-[80vh] overflow-y-auto bg-black border border-white/10 rounded-2xl p-6 shadow-2xl z-[200]">
                <h4 class="text-xs tracking-[0.3em] uppercase text-white/40 mb-3"><?= h($t['languages']) ?></h4>

                <div class="flex gap-3 mb-6">
                    <a href="<?= h(langUrl('en')) ?>" class="px-3 py-1 border border-white/20 rounded-full text-xs hover:bg-white hover:text-black transition">EN</a>
                    <a href="<?= h(langUrl('ar')) ?>" class="px-3 py-1 border border-white/20 rounded-full text-xs hover:bg-white hover:text-black transition">AR</a>
                    <a href="<?= h(langUrl('he')) ?>" class="px-3 py-1 border border-white/20 rounded-full text-xs hover:bg-white hover:text-black transition">HE</a>
                </div>

                <h4 class="text-xs tracking-[0.3em] uppercase text-white/40 mb-3"><?= h($t['categories']) ?></h4>

                <div class="grid grid-cols-2 gap-3 text-sm mb-6">
                    <?php
                    $menuCats = [
                            'jeans' => 'Jeans',
                            'pants' => 'Pants',
                            'blouses' => 'Blouses',
                            'shirts' => 'Shirts',
                            'dresses' => 'Dresses',
                            'formal' => 'Formal',
                            'jackets' => 'Jackets',
                            'abaya' => 'Abaya',
                            'skirts' => 'Skirts',
                            'bags' => 'Bags',
                            'belts' => 'Belts',
                            'vests' => 'Vests',
                            'overalls' => 'Overalls',
                            'outfits' => 'Outfits',
                            'casual' => 'Casual',
                            'blazers' => 'Blazers'
                    ];

                    foreach ($menuCats as $key => $value):
                        ?>
                        <a class="dropdown-link"
                           href="newcolc.php?lang=<?= htmlspecialchars($lang) ?>&category=<?= urlencode($key) ?>">
                            <?= htmlspecialchars($value) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <h4 class="text-xs tracking-[0.3em] uppercase text-white/40 mb-3"><?= h($t['pages']) ?></h4>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <a class="dropdown-link" href="index.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['home'] ?></a>

                    <a class="dropdown-link" href="newcolc.php?lang=<?= htmlspecialchars($lang) ?>">
                        <?= $t['shop'] ?>
                    </a>

                    <a class="dropdown-link" href="newcolc.php?lang=<?= htmlspecialchars($lang) ?>&new=1">
                        <?= $t['new_collection'] ?>
                    </a>

                    <a class="dropdown-link" href="cart.php?lang=<?= htmlspecialchars($lang) ?>">
                        <?= $t['cart'] ?>
                    </a>

                    <a class="dropdown-link" href="wishlist.php?lang=<?= htmlspecialchars($lang) ?>">
                        <?= $t['wishlist'] ?>
                    </a>

                    <a class="dropdown-link" href="order.php?lang=<?= htmlspecialchars($lang) ?>">
                        <?= $t['order'] ?>
                    </a>

                    <a class="dropdown-link" href="profile.php?lang=<?= htmlspecialchars($lang) ?>">
                        <?= $t['profile'] ?>
                    </a>

                    <?php if ($isLoggedIn): ?>
                        <a class="dropdown-link" href="logout.php">
                            <?= $t['logout'] ?>
                        </a>
                    <?php else: ?>
                        <a class="dropdown-link" href="signin.php?lang=<?= htmlspecialchars($lang) ?>">
                            <?= $t['sign_in'] ?>
                        </a>

                        <a class="dropdown-link" href="signup.php?lang=<?= htmlspecialchars($lang) ?>">
                            <?= $t['sign_up'] ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <button onclick="goPage('search.php?lang=<?= h($lang) ?>', true)">
                <i data-lucide="search" style="width:18px;height:18px;"></i>
            </button>

            <button onclick="goUserPage()">
                <i data-lucide="user" style="width:18px;height:18px;"></i>
            </button>

            <button onclick="goPage('wishlist.php?lang=<?= h($lang) ?>', true)" class="relative">
                <i data-lucide="heart" style="width:18px;height:18px;"></i>
                <span id="wishlist-count" class="absolute -top-1 -right-2 text-[10px] w-4 h-4 rounded-full flex items-center justify-center bg-white text-black <?= $wishlistCount > 0 ? '' : 'hidden' ?>">
                    <?= $wishlistCount ?>
                </span>
            </button>

            <button onclick="goPage('cart.php?lang=<?= h($lang) ?>', true)" class="relative">
                <i data-lucide="shopping-bag" style="width:18px;height:18px;"></i>
                <span id="cart-count" class="absolute -top-1 -right-2 text-[10px] w-4 h-4 rounded-full flex items-center justify-center bg-white text-black <?= $totalQty > 0 ? '' : 'hidden' ?>">
                    <?= $totalQty ?>
                </span>
            </button>

            <?php if ($isLoggedIn): ?>
                <span class="text-xs text-white/60"><?= h($currentUserName) ?></span>
            <?php endif; ?>
        </div>

        <button class="md:hidden text-white" id="mobile-toggle">
            <i data-lucide="menu" style="width:24px;height:24px;"></i>
        </button>
    </div>

    <div class="mobile-menu md:hidden px-6 pb-4" id="mobile-menu">
        <nav class="flex flex-col gap-4 text-sm tracking-wider uppercase font-light pt-2" style="color:rgba(255,255,255,0.7); border-top:1px solid rgba(255,255,255,0.08);">
            <a href="index.php?lang=<?= h($lang) ?>"><?= h($t['home']) ?></a>
            <a href="new_collection.php?lang=<?= h($lang) ?>"><?= h($t['collection']) ?></a>
            <a href="shope.php?lang=<?= h($lang) ?>"><?= h($t['shop']) ?></a>
            <a href="about.php?lang=<?= h($lang) ?>"><?= h($t['our_story']) ?></a>
            <a href="contact.php?lang=<?= h($lang) ?>"><?= h($t['contact']) ?></a>

            <div class="flex gap-3 pt-2">
                <a href="<?= h(langUrl('en')) ?>">EN</a>
                <a href="<?= h(langUrl('ar')) ?>">AR</a>
                <a href="<?= h(langUrl('he')) ?>">HE</a>
            </div>

            <button onclick="goUserPage()" class="text-left"><?= h($t['user_signin']) ?></button>
            <button onclick="goPage('cart.php?lang=<?= h($lang) ?>', true)" class="text-left"><?= h($t['cart']) ?> (<?= $totalQty ?>)</button>
            <button onclick="goPage('wishlist.php?lang=<?= h($lang) ?>', true)" class="text-left"><?= h($t['wishlist']) ?> (<?= $wishlistCount ?>)</button>
        </nav>
    </div>
</header>

<section class="page-hero">
    <div>
        <h1>Your Bag</h1>
    </div>
    <p>
        Review your selected pieces, choose which items to purchase,
        and continue to a secure luxury checkout experience.
    </p>
</section>

<main class="cart-wrapper">
    <section class="cart-section">
        <div class="cart-count" id="cartCount">
            <?= $totalQty ?> <?= $totalQty == 1 ? 'item' : 'items' ?> in your bag
        </div>

        <div class="cart-actions-top">
            <label class="select-all-wrap">
                <input type="checkbox" id="selectAll" checked onchange="toggleSelectAll(this)">
                <span>Select All Items</span>
            </label>
        </div>

        <div class="empty-cart <?= empty($cartItems) ? 'show' : '' ?>" id="emptyCart">
            <h2>Your Bag Is Empty</h2>
            <p>You haven’t added anything yet. Discover timeless essentials and elevated pieces.</p>
            <button class="continue-btn" onclick="goShopping()">Continue Shopping</button>
        </div>

        <div class="cart-list" id="cartList" style="<?= empty($cartItems) ? 'display:none;' : '' ?>">
            <?php foreach ($cartItems as $item): ?>
                <article class="cart-item selected"
                         data-price="<?= h($item['price']) ?>"
                         data-cart-id="<?= (int)$item['cart_id'] ?>">

                    <div class="item-check-wrap">
                        <input type="checkbox" class="item-check" checked onchange="updateCart()">
                    </div>

                    <div class="cart-item-image">
                        <img src="<?= h($item['image']) ?>" alt="<?= h($item['product_name']) ?>">
                    </div>

                    <div class="cart-item-info">
                        <div class="item-top">
                            <div>
                                <h3 class="item-title"><?= h($item['product_name']) ?></h3>
                                <div class="item-meta">
                                    <span>Size: <?= h($item['size']) ?></span>
                                    <span>Color: <?= h($item['color']) ?></span>
                                </div>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                <button class="remove-btn" name="delete_item" type="submit">×</button>
                            </form>
                        </div>

                        <div class="item-bottom">
                            <form method="POST" class="qty-box">
                                <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">

                                <button type="button" class="qty-btn" onclick="changeQty(this, -1)">−</button>

                                <input class="qty-value"
                                       name="quantity"
                                       value="<?= (int)$item['cart_qty'] ?>"
                                       readonly>

                                <button type="button" class="qty-btn" onclick="changeQty(this, 1)">+</button>

                                <button name="update_qty" type="submit" style="display:none;"></button>
                            </form>

                            <div class="item-prices">
                                <div class="unit-price">$<?= number_format((float)$item['price'], 2) ?> each</div>
                                <div class="line-total">$<?= number_format((float)$item['line_total'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <aside class="summary-section">
        <div class="summary-card">
            <h2 class="summary-title">Order Summary</h2>

            <div class="promo-box">
                <input type="text" class="promo-input" id="promoInput" placeholder="Promo code">
                <button class="promo-btn" onclick="applyPromo()">Apply</button>
            </div>

            <div class="summary-row">
                <span>Selected Items</span>
                <span id="selectedItemsCount"><?= $totalQty ?></span>
            </div>

            <div class="summary-row">
                <span>Subtotal</span>
                <span id="subtotal">$<?= number_format($subtotal, 2) ?></span>
            </div>

            <div class="summary-row">
                <span>Shipping</span>
                <span id="shipping"><?= $shipping == 0 ? 'Free' : '$' . number_format($shipping, 2) ?></span>
            </div>

            <div class="summary-row">
                <span>Discount</span>
                <span id="discount">$0</span>
            </div>

            <div class="summary-row total">
                <span>Total</span>
                <span id="total">$<?= number_format($total, 2) ?></span>
            </div>

            <div class="delivery-note">Estimated delivery: 3–5 business days</div>
            <div class="selected-note">Checkout applies only to selected products</div>

            <button class="checkout-btn" onclick="goToCheckout()">Checkout</button>
            <button class="continue-btn" onclick="goShopping()">Continue Shopping</button>

            <div class="secure-note">Secure Checkout • Encrypted Payment</div>

            <div class="trust-badges">
                <div class="trust-badge"><strong>Free Returns</strong>30-day easy returns</div>
                <div class="trust-badge"><strong>Secure Pay</strong>Protected transactions</div>
                <div class="trust-badge"><strong>Fast Dispatch</strong>Ships within 24h</div>
            </div>
        </div>
    </aside>
</main>

<footer id="footer" class="w-full py-16 px-6" style="background:#000; border-top:1px solid rgba(255,255,255,0.06); color:#fff;">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-14">
            <div>
                <a href="index.php?lang=<?= h($lang) ?>" class="inline-block mb-4">
                    <img src="pic/lolo1.png" alt="demoiselle Logo" class="h-10 w-auto">
                </a>

                <p class="text-xs font-light leading-relaxed" style="color:rgba(255,255,255,0.35);">
                    <?= h($t['footer_desc']) ?>
                </p>
            </div>

            <div>
                <h4 class="text-[10px] tracking-[0.3em] uppercase mb-5" style="color:rgba(255,255,255,0.5);"><?= h($t['shop']) ?></h4>
                <nav class="flex flex-col gap-3">
                    <a href="shope.php?lang=<?= h($lang) ?>" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= h($t['shop']) ?></a>
                    <a href="new_collection.php?lang=<?= h($lang) ?>" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= h($t['new_arrivals']) ?></a>
                    <a href="wishlist.php?lang=<?= h($lang) ?>" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= h($t['wishlist']) ?></a>
                </nav>
            </div>

            <div>
                <h4 class="text-[10px] tracking-[0.3em] uppercase mb-5" style="color:rgba(255,255,255,0.5);"><?= h($t['company']) ?></h4>
                <nav class="flex flex-col gap-3">
                    <a href="about.php?lang=<?= h($lang) ?>" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= h($t['our_story']) ?></a>
                    <a href="index.php?lang=<?= h($lang) ?>" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= h($t['home']) ?></a>
                </nav>
            </div>

            <div>
                <h4 class="text-[10px] tracking-[0.3em] uppercase mb-5" style="color:rgba(255,255,255,0.5);"><?= h($t['support']) ?></h4>
                <nav class="flex flex-col gap-3">
                    <a href="https://www.instagram.com/demoisellepal" target="_blank" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= h($t['instagram']) ?></a>
                    <a href="contact.php?lang=<?= h($lang) ?>" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= h($t['contact_us']) ?></a>
                    <a href="profile.php?lang=<?= h($lang) ?>" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= h($t['profile']) ?></a>
                </nav>
            </div>
        </div>

        <div class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4" style="border-top:1px solid rgba(255,255,255,0.06);">
            <p class="text-[10px] tracking-[0.2em]" style="color:rgba(255,255,255,0.25);">
                <?= h($t['rights']) ?>
            </p>

            <p class="text-[10px] tracking-[0.2em]" style="color:rgba(255,255,255,0.25);">
                <?= h($t['made']) ?>
            </p>
        </div>

        <div class="mt-8 flex flex-wrap gap-4">
            <button onclick="goPage('order.php?lang=<?= h($lang) ?>', true)" class="px-4 py-2 bg-white text-black"><?= h($t['order']) ?></button>
            <button onclick="window.location.href='new_collection.php?lang=<?= h($lang) ?>'" class="px-4 py-2 bg-white text-black"><?= h($t['new_collection']) ?></button>
            <button onclick="goPage('wishlist.php?lang=<?= h($lang) ?>', true)" class="px-4 py-2 bg-white text-black"><?= h($t['wishlist']) ?></button>
            <button onclick="goUserPage()" class="px-4 py-2 bg-white text-black"><?= h($t['profile']) ?></button>
            <button onclick="window.location.href='shope.php?lang=<?= h($lang) ?>'" class="px-4 py-2 bg-white text-black"><?= h($t['shop']) ?></button>
        </div>
    </div>
</footer>

<script>
    lucide.createIcons();

    const currentLang = "<?= h($lang) ?>";
    const isLoggedIn = true;
    let promoDiscount = 0;

    function withLang(page) {
        if (page.includes('?')) return page + '&lang=' + currentLang;
        return page + '?lang=' + currentLang;
    }

    function goPage(page, protectedPage = false) {
        if (protectedPage && !isLoggedIn) {
            window.location.href = withLang('signin.php');
            return;
        }
        window.location.href = page;
    }

    function goUserPage() {
        window.location.href = withLang('profile.php');
    }

    const dropdownBtn = document.getElementById('main-dropdown-btn');
    const dropdown = document.getElementById('main-dropdown');

    if (dropdownBtn && dropdown) {
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
    }

    document.getElementById('mobile-toggle')?.addEventListener('click', () => {
        document.getElementById('mobile-menu').classList.toggle('open');
    });

    function formatPrice(value) {
        return "$" + value.toFixed(2);
    }

    function updateSelectedStyle() {
        document.querySelectorAll(".cart-item").forEach(item => {
            const checkbox = item.querySelector(".item-check");
            item.classList.toggle("selected", checkbox.checked);
        });
    }

    function updateSelectAllState() {
        const checks = document.querySelectorAll(".item-check");
        const checked = document.querySelectorAll(".item-check:checked");
        const selectAll = document.getElementById("selectAll");
        if (selectAll) selectAll.checked = checks.length > 0 && checks.length === checked.length;
    }

    function updateCart() {
        const items = document.querySelectorAll(".cart-item");
        let subtotal = 0;
        let totalItemsInBag = 0;
        let selectedItemsCount = 0;

        items.forEach(item => {
            const price = parseFloat(item.dataset.price);
            const qtyInput = item.querySelector(".qty-value");
            const qty = parseInt(qtyInput.value);
            const checked = item.querySelector(".item-check").checked;
            const lineTotal = price * qty;

            item.querySelector(".line-total").textContent = formatPrice(lineTotal);
            totalItemsInBag += qty;

            if (checked) {
                subtotal += lineTotal;
                selectedItemsCount += qty;
            }
        });

        if (promoDiscount > subtotal) promoDiscount = 0;

        const shippingValue = subtotal > 0 ? (subtotal >= 250 ? 0 : 15) : 0;
        const total = Math.max(subtotal + shippingValue - promoDiscount, 0);

        document.getElementById("subtotal").textContent = formatPrice(subtotal);
        document.getElementById("discount").textContent = promoDiscount > 0 ? "-" + formatPrice(promoDiscount) : "$0";
        document.getElementById("shipping").textContent = shippingValue === 0 ? "Free" : formatPrice(shippingValue);
        document.getElementById("total").textContent = formatPrice(total);
        document.getElementById("selectedItemsCount").textContent = selectedItemsCount;

        document.getElementById("cartCount").textContent =
            totalItemsInBag + (totalItemsInBag === 1 ? " item in your bag" : " items in your bag");

        const topCart = document.getElementById("cart-count");
        if (topCart) {
            topCart.textContent = totalItemsInBag;
            topCart.classList.toggle("hidden", totalItemsInBag === 0);
        }

        updateSelectedStyle();
        updateSelectAllState();
    }

    function changeQty(button, change) {
        const form = button.closest("form");
        const input = form.querySelector(".qty-value");
        let qty = parseInt(input.value);

        qty += change;
        if (qty < 1) qty = 1;

        input.value = qty;
        updateCart();

        form.querySelector("[name='update_qty']").click();
    }

    function toggleSelectAll(master) {
        document.querySelectorAll(".item-check").forEach(check => {
            check.checked = master.checked;
        });
        updateCart();
    }

    function applyPromo() {
        const code = document.getElementById("promoInput").value.trim().toUpperCase();
        const subtotal = parseFloat(document.getElementById("subtotal").textContent.replace("$", ""));

        if (code === "WELCOME10") {
            promoDiscount = subtotal * 0.10;
        } else if (code === "LUXE20") {
            promoDiscount = 20;
        } else if (code === "") {
            promoDiscount = 0;
        } else {
            alert("Invalid promo code");
            return;
        }

        updateCart();
    }
    function goShopping() {
        window.location.href = "newcolc.php?lang=<?= h($lang) ?>";
    }

    function goToCheckout() {
        const selected = [];

        document.querySelectorAll(".cart-item").forEach(item => {
            if (item.querySelector(".item-check").checked) {
                selected.push(item.dataset.cartId);
            }
        });

        if (selected.length === 0) {
            alert("Please select at least one item before checkout.");
            return;
        }

        localStorage.setItem("selectedCartIds", JSON.stringify(selected));
        localStorage.setItem("promoDiscount", promoDiscount);

        window.location.href = "ORDER_2.php?lang=<?= h($lang) ?>";
    }
    updateCart();
</script>

</body>
</html>