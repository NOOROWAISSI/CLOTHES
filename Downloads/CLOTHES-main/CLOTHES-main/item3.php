<?php
global $conn;
include "db.php";

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function colorCss($color) {
    $c = strtolower(trim((string)$color));
    $map = [
            'black'=>'#111111','white'=>'#f4f4f4','blue'=>'#2563eb',
            'navy'=>'#1e3a8a','red'=>'#ef4444','pink'=>'#ec4899',
            'green'=>'#22c55e','gray'=>'#71717a','grey'=>'#71717a',
            'brown'=>'#92400e','beige'=>'#d6b98c','purple'=>'#a855f7',
            'yellow'=>'#eab308','gold'=>'#fbbf24'
    ];
    return $map[$c] ?? '#111111';
}

$lang = $_GET['lang'] ?? 'en';
$lang = in_array($lang, ['en','ar','he']) ? $lang : 'en';

$product_id = (int)($_GET['product_id'] ?? 0);

if ($product_id <= 0) {
    die("Product not found");
}

$stmt = $conn->prepare("
SELECT
    p.product_id,
    p.price,
    COALESCE(pt.product_name, CONCAT('Product #', p.product_id)) AS product_name,
    COALESCE(pt.description, '') AS description,
    COALESCE(ct.category_name, c.category_key, 'Category') AS category_name
FROM products p
LEFT JOIN product_translations pt
    ON p.product_id = pt.product_id AND pt.language_code = ?
LEFT JOIN categories c
    ON p.category_id = c.category_id
LEFT JOIN category_translations ct
    ON c.category_id = ct.category_id AND ct.language_code = ?
WHERE p.product_id = ?
LIMIT 1
");

$stmt->bind_param("ssi", $lang, $lang, $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found");
}

$isLoggedIn = isset($_SESSION['user_id']);
$isLiked = false;

if ($isLoggedIn) {
    $uid = (int)$_SESSION['user_id'];
    $favQ = $conn->prepare("SELECT favorite_id FROM favorites WHERE user_id=? AND product_id=? LIMIT 1");
    $favQ->bind_param("ii", $uid, $product_id);
    $favQ->execute();
    $isLiked = $favQ->get_result()->num_rows > 0;
}

$images = [];

$imgQ = $conn->prepare("
SELECT image_url
FROM product_images
WHERE product_id = ?
ORDER BY is_main DESC, image_id ASC
");
$imgQ->bind_param("i", $product_id);
$imgQ->execute();
$imgRes = $imgQ->get_result();

while ($img = $imgRes->fetch_assoc()) {
    if (!empty($img['image_url'])) {
        $images[] = $img['image_url'];
    }
}

$varQ = $conn->prepare("
SELECT variant_id, color, size, quantity, variant_image_url
FROM product_variants
WHERE product_id = ?
ORDER BY variant_id ASC
");
$varQ->bind_param("i", $product_id);
$varQ->execute();
$varRes = $varQ->get_result();

$variants = [];
$colors = [];
$sizes = [];

while ($v = $varRes->fetch_assoc()) {
    $variants[] = [
            'variant_id' => (int)$v['variant_id'],
            'color' => (string)$v['color'],
            'size' => (string)$v['size'],
            'quantity' => (int)$v['quantity'],
            'variant_image_url' => (string)$v['variant_image_url']
    ];

    if (!empty($v['variant_image_url'])) {
        $images[] = $v['variant_image_url'];
    }

    if (!empty($v['color'])) {
        $colors[$v['color']] = $v['color'];
    }

    if (!empty($v['size'])) {
        $sizes[$v['size']] = $v['size'];
    }
}

$images = array_values(array_unique($images));
if (count($images) === 0) {
    $images[] = "pic/default.jpg";
}

$firstSize = count($sizes) ? array_values($sizes)[0] : '';
$firstColor = count($colors) ? array_values($colors)[0] : '';
?>
<!doctype html>
<html lang="<?= h($lang) ?>" <?= ($lang === 'ar' || $lang === 'he') ? 'dir="rtl"' : 'dir="ltr"' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($product['product_name']) ?> - Demoiselle</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        :root{--bg:#fff;--surface:#f6f6f6;--surface-2:#efefef;--text:#111;--muted:#777;--border:#e3e3e3}
        body{font-family:'Poppins',sans-serif;background:#fff;color:#111;overflow-x:hidden}
        img{display:block;width:100%}
        button,input,select{font-family:inherit}
        a{text-decoration:none;color:inherit}

        .page-wrap{min-height:100vh;background:linear-gradient(to bottom,#fff 0%,#fafafa 100%)}
        .topbar{background:#111;color:#fff;overflow:hidden;padding:8px 0;font-size:11px;letter-spacing:3px;text-transform:uppercase;white-space:nowrap}
        .topbar-track{display:inline-flex;gap:50px;padding-left:100%;animation:marquee 20s linear infinite}
        @keyframes marquee{0%{transform:translateX(0)}100%{transform:translateX(-100%)}}

        .header{position:sticky;top:0;z-index:1000;background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border)}
        .header-inner{max-width:1380px;margin:auto;padding:18px 22px;display:flex;align-items:center;justify-content:space-between;position:relative}
        .header-left,.header-right{display:flex;align-items:center;gap:18px;min-width:150px}
        .header-right{justify-content:flex-end}
        .brand{position:absolute;left:50%;transform:translateX(-50%);display:flex;align-items:center;justify-content:center}
        .brand-logo{width:90px;height:90px;object-fit:contain}
        .icon-btn{border:none;background:transparent;color:#111;cursor:pointer;transition:.25s;position:relative;width:34px;height:34px;display:flex;align-items:center;justify-content:center}
        .icon-btn:hover{opacity:.65;transform:translateY(-1px)}
        .icon-btn i{width:19px;height:19px;stroke-width:1.6}
        .cart-badge{position:absolute;top:-4px;right:-6px;width:18px;height:18px;border-radius:50%;background:#111;color:#fff;font-size:10px;display:none;align-items:center;justify-content:center;font-weight:600}

        .container{max-width:1380px;margin:auto;padding:36px 22px 0}
        .product-layout{display:grid;grid-template-columns:1.05fr .95fr;gap:54px;align-items:start}
        .gallery-col{display:flex;flex-direction:column;gap:14px}
        .main-image-wrap{position:relative;background:#f6f6f6;overflow:hidden;border-radius:4px;aspect-ratio:3/4;border:1px solid #f0f0f0}
        .main-image{width:100%;height:100%;object-fit:cover;transition:transform .18s ease-out;transform-origin:center;user-select:none;pointer-events:none}
        .product-logo-badge{position:absolute;top:18px;right:18px;width:72px;height:72px;border-radius:50%;overflow:hidden;background:rgba(255,255,255,.95);border:2px solid #fff;box-shadow:0 8px 22px rgba(0,0,0,.14);z-index:5}
        .product-logo-badge img{width:100%;height:100%;object-fit:cover}
        .thumb-row{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
        .thumb{border:2px solid transparent;border-radius:4px;overflow:hidden;cursor:pointer;opacity:.72;background:#efefef;transition:.25s;aspect-ratio:1/1}
        .thumb:hover{opacity:1;transform:translateY(-2px)}
        .thumb.active{border-color:#111;opacity:1}
        .thumb img{height:100%;object-fit:cover}

        .details-col{padding-top:6px}
        .eyebrow{font-size:11px;letter-spacing:.24em;text-transform:uppercase;color:#777;margin-bottom:12px;font-weight:600}
        .product-title{font-family:'Cormorant Garamond',serif;font-size:50px;line-height:1.06;margin-bottom:12px;font-weight:400;letter-spacing:.04em}
        .product-price{font-size:24px;font-weight:600;margin-bottom:18px}
        .product-desc{color:#5e5e5e;font-size:14px;line-height:1.9;max-width:540px;margin-bottom:26px}
        .option-block{margin-bottom:26px}
        .option-title{font-size:11px;letter-spacing:.18em;text-transform:uppercase;margin-bottom:12px;font-weight:600}
        .color-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
        .color-dot{width:30px;height:30px;border-radius:50%;border:2px solid #ddd;cursor:pointer;transition:.25s}
        .color-dot:hover,.color-dot.active{border-color:#111;transform:scale(1.06)}
        .sizes{display:grid;grid-template-columns:repeat(5,minmax(52px,1fr));gap:8px;max-width:390px}
        .size-btn{padding:12px 10px;background:#fff;border:1px solid #e3e3e3;cursor:pointer;font-size:12px;letter-spacing:.08em;transition:.25s;border-radius:3px}
        .size-btn:hover{border-color:#111;background:#f7f7f7}
        .size-btn.active{background:#111;border-color:#111;color:#fff}
        .size-btn.disabled{opacity:.35;cursor:not-allowed;text-decoration:line-through}

        .actions{display:flex;gap:12px;align-items:stretch;margin-top:8px}
        .add-btn{flex:1;border:none;background:#111;color:#fff;padding:16px 22px;cursor:pointer;text-transform:uppercase;letter-spacing:.18em;font-weight:600;font-size:12px;border-radius:3px;transition:.25s}
        .add-btn:hover{background:#2a2a2a}
        .wishlist-btn{width:56px;border:1px solid #e3e3e3;background:#fff;color:#111;cursor:pointer;border-radius:3px;transition:.25s;display:flex;align-items:center;justify-content:center}
        .wishlist-btn:hover{border-color:#111}
        .wishlist-btn.active{background:#111;color:#fff;border-color:#111}
        .feedback{margin-top:14px;background:#111;color:#fff;display:none;padding:12px 15px;font-size:12px;border-radius:3px;letter-spacing:.04em;width:fit-content}

        .details-accordion{margin-top:26px;border-top:1px solid #e3e3e3}
        .acc-item{border-bottom:1px solid #e3e3e3}
        .acc-trigger{width:100%;background:transparent;border:none;cursor:pointer;padding:18px 0;display:flex;justify-content:space-between;align-items:center;text-align:left;font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#111}
        .acc-icon{font-size:16px;transition:.3s}
        .acc-item.open .acc-icon{transform:rotate(180deg)}
        .acc-content{max-height:0;overflow:hidden;transition:max-height .35s ease}
        .acc-inner{padding:0 0 18px;color:#666;font-size:13px;line-height:1.9}

        .recommend-wrap{margin-top:90px;padding-top:60px;border-top:1px solid #e3e3e3}
        .recommend-head{text-align:center;margin-bottom:42px}
        .recommend-head p{font-size:11px;text-transform:uppercase;letter-spacing:.28em;color:#777;margin-bottom:10px;font-weight:600}
        .recommend-head h2{font-family:'Cormorant Garamond',serif;font-size:38px;font-weight:400;letter-spacing:.08em}
        .recommend-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
        .rec-card{position:relative;overflow:hidden;border-radius:4px;background:#ececec;cursor:pointer;aspect-ratio:3/4}
        .rec-card img{width:100%;height:100%;object-fit:cover;transition:.55s}
        .rec-card:hover img{transform:scale(1.06)}

        .footer{margin-top:90px;padding:40px 22px;background:#fafafa;border-top:1px solid #e3e3e3;text-align:center;color:#777;font-size:12px}
        .sticky-bar{position:fixed;left:0;right:0;bottom:0;z-index:1200;transform:translateY(120%);transition:.4s;padding:0 16px 16px;pointer-events:none}
        .sticky-bar.show{transform:translateY(0)}
        .sticky-card{max-width:820px;margin:auto;background:#111;color:#fff;border-radius:6px;padding:14px 16px;display:flex;align-items:center;gap:14px;box-shadow:0 18px 40px rgba(0,0,0,.16);pointer-events:auto;flex-wrap:wrap}
        .sticky-img{width:58px;height:58px;border-radius:4px;overflow:hidden;background:#2a2a2a}
        .sticky-img img{height:100%;object-fit:cover}
        .sticky-info{flex:1;min-width:140px}
        .sticky-name{font-size:13px;font-weight:500;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .sticky-meta{font-size:11px;color:#c7c7c7}
        .qty-btn{width:28px;height:28px;border-radius:50%;border:1px solid #5e5e5e;background:transparent;color:#fff;cursor:pointer}
        .qty-text{width:22px;text-align:center;font-size:12px}
        .remove-mini{border:none;background:transparent;color:#aaa;font-size:16px;cursor:pointer}
        .sticky-controls{display:flex;align-items:center;gap:8px}

        @media(max-width:1120px){.product-layout{grid-template-columns:1fr}.recommend-grid{grid-template-columns:1fr 1fr}}
        @media(max-width:760px){.brand-logo{width:62px;height:62px}.container{padding:24px 14px 0}.product-title{font-size:36px}.recommend-grid{grid-template-columns:1fr}.header-left,.header-right{min-width:unset;gap:12px}}
    </style>
</head>

<body>
<div class="page-wrap">

    <div class="topbar">
        <div class="topbar-track">
            <span>New Collection Available</span>
            <span>Curated Essentials</span>
            <span>Timeless Clothing Drops</span>
            <span>New Collection Available</span>
        </div>
    </div>

    <header class="header">
        <div class="header-inner">
            <div class="header-left">
                <button onclick="location.href='index.php?lang=<?= h($lang) ?>'" class="icon-btn"><i data-lucide="home"></i></button>
                <button onclick="location.href='search.php?lang=<?= h($lang) ?>'" class="icon-btn"><i data-lucide="search"></i></button>
            </div>

            <div class="brand">
                <img src="./pic/logo.jpeg" alt="Brand Logo" class="brand-logo" onerror="this.src='pic/lolo1.png'">
            </div>

            <div class="header-right">
                <button class="icon-btn <?= $isLiked ? 'active' : '' ?>" id="header-heart">
                    <i data-lucide="heart" <?= $isLiked ? 'fill="currentColor"' : '' ?>></i>
                </button>
                <button onclick="location.href='profile.php?lang=<?= h($lang) ?>'" class="icon-btn"><i data-lucide="user"></i></button>
                <button onclick="location.href='cart.php?lang=<?= h($lang) ?>'" class="icon-btn" id="header-cart">
                    <i data-lucide="shopping-bag"></i>
                    <span class="cart-badge" id="cart-badge">0</span>
                </button>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="product-layout">
            <div class="gallery-col">
                <div class="main-image-wrap" id="zoom-area">
                    <img id="main-image" class="main-image" alt="<?= h($product['product_name']) ?>">
                    <div class="product-logo-badge">
                        <img src="./pic/logo2.png" alt="Round Logo" onerror="this.src='pic/lolo1.png'">
                    </div>
                </div>
                <div class="thumb-row" id="thumb-row"></div>
            </div>

            <div class="details-col">
                <p class="eyebrow"><?= h($product['category_name']) ?></p>
                <h1 class="product-title"><?= h($product['product_name']) ?></h1>
                <p class="product-price">$<?= h($product['price']) ?></p>

                <p class="product-desc">
                    <?= h($product['description'] ?: 'A refined piece designed with timeless elegance and modern detail.') ?>
                </p>

                <div class="option-block">
                    <p class="option-title">Color</p>
                    <div class="color-row" id="color-row">
                        <?php $first = true; ?>
                        <?php foreach ($colors as $c): ?>
                            <button
                                    type="button"
                                    class="color-dot <?= $first ? 'active' : '' ?>"
                                    data-color="<?= h($c) ?>"
                                    title="<?= h($c) ?>"
                                    style="background:<?= h(colorCss($c)) ?>;">
                            </button>
                            <?php $first = false; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="option-block">
                    <p class="option-title">Size</p>
                    <div class="sizes" id="size-buttons">
                        <?php $first = true; ?>
                        <?php foreach ($sizes as $s): ?>
                            <button type="button" class="size-btn <?= $first ? 'active' : '' ?>" data-size="<?= h($s) ?>">
                                <?= h($s) ?>
                            </button>
                            <?php $first = false; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="actions">
                    <button class="add-btn" id="add-btn">Add to Bag</button>
                    <button class="wishlist-btn <?= $isLiked ? 'active' : '' ?>" id="wishlist-btn">
                        <i data-lucide="heart" <?= $isLiked ? 'fill="currentColor"' : '' ?>></i>
                    </button>
                </div>

                <div class="feedback" id="feedback">✓ Added to your bag</div>

                <div class="details-accordion">
                    <div class="acc-item">
                        <button class="acc-trigger"><span>Size & Fit</span><span class="acc-icon">⌄</span></button>
                        <div class="acc-content"><div class="acc-inner">Choose your available size and color before adding to bag.</div></div>
                    </div>
                    <div class="acc-item">
                        <button class="acc-trigger"><span>Shipping</span><span class="acc-icon">⌄</span></button>
                        <div class="acc-content"><div class="acc-inner">Standard delivery and express delivery are available.</div></div>
                    </div>
                    <div class="acc-item">
                        <button class="acc-trigger"><span>Returns & Exchanges</span><span class="acc-icon">⌄</span></button>
                        <div class="acc-content"><div class="acc-inner">Items must be unworn with original tags attached.</div></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="recommend-wrap">
            <div class="recommend-head">
                <p>You May Also Like</p>
                <h2>Recommended Pieces</h2>
            </div>
            <div class="recommend-grid">
                <?php foreach (array_slice($images, 0, 4) as $img): ?>
                    <article class="rec-card">
                        <img src="<?= h($img) ?>" alt="Recommended">
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer class="footer">
        © 2025 DEMOISELLE. All rights reserved.
    </footer>

    <div class="sticky-bar" id="sticky-bar">
        <div class="sticky-card">
            <div class="sticky-img">
                <img id="sticky-image" alt="Selected product small preview">
            </div>
            <div class="sticky-info">
                <div class="sticky-name"><?= h($product['product_name']) ?></div>
                <div class="sticky-meta">
                    Size <span id="sticky-size-text"><?= h($firstSize) ?></span> · Qty <span id="sticky-qty-text">1</span>
                </div>
            </div>
            <div class="sticky-controls">
                <button class="qty-btn" id="qty-minus">−</button>
                <div class="qty-text" id="qty-text">1</div>
                <button class="qty-btn" id="qty-plus">+</button>
                <button class="remove-mini" id="remove-mini">✕</button>
            </div>
        </div>
    </div>

</div>

<script>
    const PRODUCT_ID = <?= (int)$product_id ?>;
    const IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
    const currentLang = "<?= h($lang) ?>";
    const VARIANTS = <?= json_encode($variants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const productImages = <?= json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    const mainImage = document.getElementById('main-image');
    const stickyImage = document.getElementById('sticky-image');
    const thumbRow = document.getElementById('thumb-row');
    const sizeButtons = document.querySelectorAll('.size-btn');
    const colorButtons = document.querySelectorAll('.color-dot');
    const addBtn = document.getElementById('add-btn');
    const feedback = document.getElementById('feedback');
    const stickyBar = document.getElementById('sticky-bar');
    const cartBadge = document.getElementById('cart-badge');
    const wishlistBtn = document.getElementById('wishlist-btn');
    const headerHeart = document.getElementById('header-heart');
    const stickySizeText = document.getElementById('sticky-size-text');
    const stickyQtyText = document.getElementById('sticky-qty-text');
    const qtyText = document.getElementById('qty-text');

    let currentImageIndex = 0;
    let selectedColor = VARIANTS.length ? String(VARIANTS[0].color || '') : '';
    let selectedSize = VARIANTS.length ? String(VARIANTS[0].size || '') : '';
    let selectedVariantId = VARIANTS.length ? parseInt(VARIANTS[0].variant_id) : 0;
    let quantity = 1;
    let liked = <?= $isLiked ? 'true' : 'false' ?>;

    function setFallbackImage(img) {
        img.onerror = null;
        img.src = 'pic/default.jpg';
    }

    function renderThumbs() {
        thumbRow.innerHTML = '';

        productImages.forEach((src, index) => {
            const div = document.createElement('div');
            div.className = 'thumb' + (index === currentImageIndex ? ' active' : '');

            const img = document.createElement('img');
            img.src = src;
            img.alt = 'Thumbnail';
            img.onerror = function(){ setFallbackImage(this); };

            div.appendChild(img);
            div.addEventListener('click', () => {
                currentImageIndex = index;
                updateMainImage();
                renderThumbs();
            });

            thumbRow.appendChild(div);
        });
    }

    function updateMainImage() {
        mainImage.src = productImages[currentImageIndex] || 'pic/default.jpg';
        stickyImage.src = productImages[currentImageIndex] || 'pic/default.jpg';

        mainImage.onerror = function(){ setFallbackImage(this); };
        stickyImage.onerror = function(){ setFallbackImage(this); };
    }

    function findSelectedVariant() {
        const v = VARIANTS.find(item =>
            String(item.color || '').toLowerCase() === String(selectedColor).toLowerCase() &&
            String(item.size || '').toLowerCase() === String(selectedSize).toLowerCase() &&
            parseInt(item.quantity || 0) > 0
        );

        selectedVariantId = v ? parseInt(v.variant_id) : 0;
        return v;
    }

    function updateSelectedImageByVariant(variant) {
        if (variant && variant.variant_image_url) {
            mainImage.src = variant.variant_image_url;
            stickyImage.src = variant.variant_image_url;
        }
    }

    function refreshSizeAvailability() {
        sizeButtons.forEach(btn => {
            const size = btn.dataset.size;
            const available = VARIANTS.some(v =>
                String(v.color || '').toLowerCase() === String(selectedColor).toLowerCase() &&
                String(v.size || '').toLowerCase() === String(size).toLowerCase() &&
                parseInt(v.quantity || 0) > 0
            );

            btn.classList.toggle('disabled', !available);
            btn.disabled = !available;
        });

        const currentAvailable = VARIANTS.some(v =>
            String(v.color || '').toLowerCase() === String(selectedColor).toLowerCase() &&
            String(v.size || '').toLowerCase() === String(selectedSize).toLowerCase() &&
            parseInt(v.quantity || 0) > 0
        );

        if (!currentAvailable) {
            const firstAvailable = Array.from(sizeButtons).find(b => !b.disabled);
            if (firstAvailable) {
                sizeButtons.forEach(b => b.classList.remove('active'));
                firstAvailable.classList.add('active');
                selectedSize = firstAvailable.dataset.size;
                stickySizeText.textContent = selectedSize;
            }
        }

        const variant = findSelectedVariant();
        updateSelectedImageByVariant(variant);
    }

    updateMainImage();
    renderThumbs();
    refreshSizeAvailability();

    colorButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            colorButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedColor = btn.dataset.color;
            refreshSizeAvailability();
        });
    });

    sizeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;

            sizeButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            selectedSize = btn.dataset.size;
            stickySizeText.textContent = selectedSize;

            const variant = findSelectedVariant();
            updateSelectedImageByVariant(variant);
        });
    });

    function updateCartUI() {
        stickySizeText.textContent = selectedSize;
        stickyQtyText.textContent = quantity;
        qtyText.textContent = quantity;
        cartBadge.style.display = 'flex';
        cartBadge.textContent = quantity;
    }

    function showFeedback(msg = '✓ Added to your bag') {
        feedback.textContent = msg;
        feedback.style.display = 'block';

        clearTimeout(showFeedback.timer);
        showFeedback.timer = setTimeout(() => {
            feedback.style.display = 'none';
        }, 2200);
    }

    addBtn.addEventListener('click', () => {
        if (!IS_LOGGED_IN) {
            window.location.href = `signin.php?lang=${currentLang}`;
            return;
        }

        const variant = findSelectedVariant();

        if (!variant || !selectedVariantId) {
            showFeedback('Choose available color and size');
            return;
        }

        const body = new URLSearchParams();
        body.append('variant_id', selectedVariantId);

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
                    alert('Error in add_to_cart.php: ' + text);
                    return;
                }

                if (data.status === 'login') {
                    window.location.href = `signin.php?lang=${currentLang}`;
                    return;
                }

                if (data.status === 'added') {
                    quantity = 1;
                    updateCartUI();
                    stickyBar.classList.add('show');
                    showFeedback('✓ Added to your bag');
                } else {
                    showFeedback(data.message || 'Cart error');
                }
            });
    });

    function toggleLike() {
        if (!IS_LOGGED_IN) {
            window.location.href = `signin.php?lang=${currentLang}`;
            return;
        }

        fetch('toggle_wishlist.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'product_id=' + encodeURIComponent(PRODUCT_ID)
        })
            .then(r => r.text())
            .then(text => {
                let data;

                try {
                    data = JSON.parse(text);
                } catch (e) {
                    alert('Error in toggle_wishlist.php: ' + text);
                    return;
                }

                if (data.status === 'login') {
                    window.location.href = `signin.php?lang=${currentLang}`;
                    return;
                }

                if (data.status === 'added') {
                    liked = true;
                    showFeedback('✓ Added to wishlist');
                } else if (data.status === 'removed') {
                    liked = false;
                    showFeedback('Removed from wishlist');
                } else {
                    showFeedback(data.message || 'Wishlist error');
                    return;
                }

                wishlistBtn.classList.toggle('active', liked);
                headerHeart.classList.toggle('active', liked);

                const pageHeart = wishlistBtn.querySelector('i');
                const navHeart = headerHeart.querySelector('i');

                if (liked) {
                    pageHeart.setAttribute('fill', 'currentColor');
                    navHeart.setAttribute('fill', 'currentColor');
                } else {
                    pageHeart.removeAttribute('fill');
                    navHeart.removeAttribute('fill');
                }
            });
    }

    wishlistBtn.addEventListener('click', toggleLike);
    headerHeart.addEventListener('click', toggleLike);

    document.getElementById('qty-minus').addEventListener('click', () => {
        quantity = Math.max(1, quantity - 1);
        updateCartUI();
    });

    document.getElementById('qty-plus').addEventListener('click', () => {
        quantity = Math.min(10, quantity + 1);
        updateCartUI();
    });

    document.getElementById('remove-mini').addEventListener('click', () => {
        stickyBar.classList.remove('show');
        cartBadge.style.display = 'none';
    });

    document.querySelectorAll('.acc-trigger').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const item = trigger.parentElement;
            const content = item.querySelector('.acc-content');
            const isOpen = item.classList.contains('open');

            document.querySelectorAll('.acc-item').forEach(other => {
                other.classList.remove('open');
                other.querySelector('.acc-content').style.maxHeight = null;
            });

            if (!isOpen) {
                item.classList.add('open');
                content.style.maxHeight = content.scrollHeight + 'px';
            }
        });
    });

    const zoomArea = document.getElementById('zoom-area');

    zoomArea.addEventListener('mousemove', (e) => {
        const rect = zoomArea.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;
        mainImage.style.transformOrigin = `${x}% ${y}%`;
        mainImage.style.transform = 'scale(1.55)';
    });

    zoomArea.addEventListener('mouseleave', () => {
        mainImage.style.transformOrigin = 'center center';
        mainImage.style.transform = 'scale(1)';
    });

    lucide.createIcons();
</script>
</body>
</html>