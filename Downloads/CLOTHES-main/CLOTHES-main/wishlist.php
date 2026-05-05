<?php
include "db.php";

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // مؤقت للتجربة
}

$user_id = $_SESSION['user_id'];
$lang = $_GET['lang'] ?? 'en';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['remove_favorite'])) {
    $product_id = intval($_POST['product_id']);

    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();

    header("Location: wishlist.php");
    exit();
}

$sql = "
SELECT 
    p.product_id,
    p.price,
    COALESCE(pt.product_name, 'Unnamed Product') AS product_name,
    COALESCE(ct.category_name, 'Category') AS category_name,
    COALESCE(pi.image_url, 'pic/logo1.jpeg') AS image_url,
    p.created_at
FROM favorites f
JOIN products p ON f.product_id = p.product_id
LEFT JOIN product_translations pt 
    ON p.product_id = pt.product_id AND pt.language_code = ?
LEFT JOIN categories c 
    ON p.category_id = c.category_id
LEFT JOIN category_translations ct 
    ON c.category_id = ct.category_id AND ct.language_code = ?
LEFT JOIN product_images pi 
    ON p.product_id = pi.product_id AND pi.is_main = 1
WHERE f.user_id = ?
ORDER BY f.favorite_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $lang, $lang, $user_id);
$stmt->execute();
$wishlist_result = $stmt->get_result();

$count_sql = "SELECT COUNT(*) AS total FROM favorites WHERE user_id = ?";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result()->fetch_assoc();
$wishlist_count = $count_result['total'] ?? 0;

$categories = $conn->query("
SELECT DISTINCT ct.category_name
FROM favorites f
JOIN products p ON f.product_id = p.product_id
JOIN categories c ON p.category_id = c.category_id
JOIN category_translations ct ON c.category_id = ct.category_id
WHERE f.user_id = $user_id AND ct.language_code = '$lang'
");

$suggestions = $conn->query("
SELECT 
    p.product_id,
    p.price,
    COALESCE(pt.product_name, 'Product') AS product_name,
    COALESCE(pi.image_url, 'pic/logo1.jpeg') AS image_url
FROM products p
LEFT JOIN product_translations pt 
    ON p.product_id = pt.product_id AND pt.language_code = '$lang'
LEFT JOIN product_images pi 
    ON p.product_id = pi.product_id AND pi.is_main = 1
ORDER BY p.created_at DESC
LIMIT 8
");
?>

<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>Demoiselle — My Wishlist</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Outfit:wght@200;300;400;500;600&display=swap" rel="stylesheet">

    <style>
        html, body { height: 100%; margin: 0; }
        * { box-sizing: border-box; }
        .font-serif { font-family: 'Cormorant Garamond', Georgia, serif; }
        .font-sans { font-family: 'Outfit', sans-serif; }

        .product-card {
            transition: all 0.4s ease;
            position: relative;
            min-height: 320px;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover { transform: translateY(-6px); }

        .product-img-wrap {
            overflow: hidden;
            position: relative;
            background: #f0f0f0;
            border-radius: 3px;
            width: 100%;
            height: 320px;
        }

        .product-img img {
            width: 100%;
            height: 320px;
            object-fit: cover;
            transition: transform 0.8s ease;
        }

        .product-card:hover .product-img img {
            transform: scale(1.1);
        }

        .product-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0);
            transition: background 0.4s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 16px;
        }

        .product-card:hover .product-overlay {
            background: rgba(0,0,0,0.25);
        }

        .overlay-content {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.4s ease;
        }

        .product-card:hover .overlay-content {
            opacity: 1;
            transform: translateY(0);
        }

        .price-tag {
            background: #000;
            color: white;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            width: fit-content;
        }

        .action-btn {
            flex: 1;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.8);
            background: rgba(0,0,0,0.7);
            color: white;
            font-size: 11px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .action-btn:hover {
            background: white;
            color: black;
        }

        .remove-btn {
            position: absolute;
            top: 12px;
            left: 12px;
            width: 36px;
            height: 36px;
            background: rgba(0,0,0,0.85);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 20;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .product-img-wrap:hover .remove-btn {
            opacity: 1;
        }

        .remove-btn:hover { background: red; }

        .product-name {
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.05em;
            color: #000;
            margin-bottom: 4px;
        }

        .product-category {
            font-size: 10px;
            letter-spacing: 0.08em;
            color: #999;
            text-transform: uppercase;
        }

        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            opacity: 0;
            transition: all 0.4s ease;
            z-index: 100;
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        .suggest-img {
            transition: transform 0.6s ease, filter 0.6s ease;
        }

        .suggest-card:hover .suggest-img {
            transform: scale(1.08);
            filter: brightness(0.88);
        }
    </style>
</head>

<body class="h-full font-sans">

<div id="app" class="w-full min-h-full bg-white" style="color:#1a1a1a">

    <div id="toast" class="toast">
        <div class="bg-black text-white text-sm px-6 py-3 rounded-full shadow-2xl flex items-center gap-2">
            <i data-lucide="check" style="width:16px;height:16px"></i>
            <span id="toast-msg">Done</span>
        </div>
    </div>

    <header class="sticky top-0 z-50 bg-black border-b text-white" style="border-color:#333">
        <div class="max-w-7xl mx-auto px-6 py-2 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3">
                <img src="pic/logo1.jpeg" alt="logo" class="w-20 h-20 object-contain">
            </a>

            <nav class="hidden md:flex items-center gap-8">
                <a href="index.php#collection" class="text-xs tracking-widest uppercase text-gray-300">New In</a>
                <a href="ORDER_2.php" class="text-xs tracking-widest uppercase text-gray-300">Shop</a>
                <a href="new collection_2.php" class="text-xs tracking-widest uppercase text-gray-300">Collections</a>
            </nav>

            <div class="flex items-center gap-5">
                <button aria-label="Search" onclick="toggleSearchBar()">
                    <i data-lucide="search" style="width:20px;height:20px;color:#fff"></i>
                </button>

                <a href="wishlist.php" class="relative" aria-label="Wishlist">
                    <i data-lucide="heart" style="width:20px;height:20px;color:#fff;fill:#fff"></i>
                    <span class="absolute -top-2 -right-2 w-4 h-4 bg-white text-black rounded-full text-xs flex items-center justify-center" style="font-size:9px">
                        <?= $wishlist_count ?>
                    </span>
                </a>

                <a href="cart.php" aria-label="Cart">
                    <i data-lucide="shopping-bag" style="width:20px;height:20px;color:#ddd"></i>
                </a>
            </div>
        </div>
    </header>

    <div id="search-bar" class="hidden bg-white px-6 py-4 border-b">
        <div class="max-w-7xl mx-auto flex items-center gap-3">
            <input id="search-input" type="text" placeholder="Search by name or price..."
                   class="w-full border px-4 py-3 text-sm outline-none"
                   oninput="filterCards()">

            <button onclick="clearSearch()" class="px-4 py-3 text-xs tracking-widest uppercase border">
                Clear
            </button>
        </div>
    </div>

    <section class="py-16 md:py-20 text-center px-6">
        <p class="text-xs tracking-widest uppercase mb-4" style="color:#aaa;letter-spacing:0.3em">♡ Wishlist</p>
        <h1 class="font-serif text-5xl md:text-6xl font-light mb-5">My Wishlist</h1>
        <p class="text-sm tracking-wide max-w-md mx-auto" style="color:#888">Your favorite picks, saved for later</p>
        <div class="w-16 h-px bg-black mx-auto mt-8 opacity-20"></div>
    </section>

    <section class="max-w-7xl mx-auto px-6 mb-10">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b">
            <p class="text-xs tracking-widest uppercase" style="color:#999">
                <span id="items-count"><?= $wishlist_count ?></span> items saved
            </p>

            <div class="flex items-center gap-4">
                <select id="filter-category" class="text-xs tracking-wider uppercase bg-transparent border px-4 py-2.5 cursor-pointer"
                        onchange="filterCards()">
                    <option value="all">All Categories</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars(strtolower($cat['category_name'])) ?>">
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select id="sort-by" class="text-xs tracking-wider uppercase bg-transparent border px-4 py-2.5 cursor-pointer"
                        onchange="sortCards()">
                    <option value="newest">Newest First</option>
                    <option value="price-low">Price: Low → High</option>
                    <option value="price-high">Price: High → Low</option>
                    <option value="name">Name A–Z</option>
                </select>
            </div>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-6 mb-24">
        <div id="wishlist-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;">

            <?php if ($wishlist_result->num_rows === 0): ?>
                <div class="col-span-full text-center py-20">
                    <i data-lucide="heart" style="width:48px;height:48px;color:#ddd;margin:0 auto 16px;display:block"></i>
                    <p class="font-serif text-2xl mb-2" style="color:#999">Your wishlist is empty</p>
                    <p class="text-sm" style="color:#bbb">Start adding your favorite pieces</p>
                </div>
            <?php else: ?>
                <?php while ($item = $wishlist_result->fetch_assoc()): ?>
                    <div class="product-card wishlist-card"
                         data-name="<?= htmlspecialchars(strtolower($item['product_name'])) ?>"
                         data-category="<?= htmlspecialchars(strtolower($item['category_name'])) ?>"
                         data-price="<?= htmlspecialchars($item['price']) ?>"
                         data-date="<?= htmlspecialchars($item['created_at']) ?>">

                        <div class="product-img-wrap">
                            <div class="product-img">
                                <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                     alt="<?= htmlspecialchars($item['product_name']) ?>">
                            </div>

                            <div class="product-overlay">
                                <div class="price-tag">$<?= number_format($item['price'], 2) ?></div>

                                <div class="overlay-content">
                                    <div class="flex gap-3">
                                        <button class="action-btn"
                                                onclick="goToOrder(
                                                    '<?= htmlspecialchars(addslashes($item['product_name'])) ?>',
                                                    '<?= htmlspecialchars(addslashes($item['image_url'])) ?>',
                                                    '<?= htmlspecialchars($item['price']) ?>',
                                                    '<?= htmlspecialchars(addslashes($item['category_name'])) ?>'
                                                    )">
                                            <i data-lucide="shopping-bag" style="width:14px;height:14px"></i>
                                            Add
                                        </button>

                                        <button class="action-btn" onclick="showToast('Added to compare')">
                                            <i data-lucide="filter" style="width:14px;height:14px"></i>
                                            Compare
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button type="submit" name="remove_favorite" class="remove-btn">
                                    <i data-lucide="trash-2" style="width:16px;height:16px;color:#fff"></i>
                                </button>
                            </form>
                        </div>

                        <div class="py-4">
                            <h3 class="product-name"><?= htmlspecialchars($item['product_name']) ?></h3>
                            <p class="product-category"><?= htmlspecialchars($item['category_name']) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

        </div>
    </main>

    <section class="bg-neutral-50 py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <p class="text-xs tracking-widest uppercase mb-3" style="color:#aaa;letter-spacing:0.3em">Curated for you</p>
                    <h2 class="font-serif text-4xl font-light">You may also like</h2>
                </div>
            </div>

            <div class="flex overflow-x-auto gap-6 pb-6">
                <?php while ($s = $suggestions->fetch_assoc()): ?>
                    <div class="suggest-card cursor-pointer flex-shrink-0 w-[calc(50%-12px)] md:w-[calc(25%-18px)]"
                         onclick="goToOrder(
                             '<?= htmlspecialchars(addslashes($s['product_name'])) ?>',
                             '<?= htmlspecialchars(addslashes($s['image_url'])) ?>',
                             '<?= htmlspecialchars($s['price']) ?>',
                             'suggested'
                             )">
                        <div class="overflow-hidden mb-4 aspect-[3/4] rounded-sm">
                            <img src="<?= htmlspecialchars($s['image_url']) ?>"
                                 class="suggest-img w-full h-full object-cover"
                                 alt="<?= htmlspecialchars($s['product_name']) ?>">
                        </div>

                        <p class="text-xs tracking-wider" style="color:#555">
                            <?= htmlspecialchars($s['product_name']) ?>
                        </p>

                        <p class="text-xs mt-1" style="color:#aaa">
                            $<?= number_format($s['price'], 2) ?>
                        </p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <footer style="background:#111;color:#999">
        <div class="max-w-7xl mx-auto px-6 py-16">
            <p class="font-serif text-white text-2xl tracking-widest mb-4">DEMOISELLE</p>
            <p class="text-xs leading-relaxed" style="color:#666">
                Curated fashion for the modern woman. Timeless pieces, minimal design, effortless elegance.
            </p>
            <div class="pt-8 mt-8" style="border-top:1px solid #222">
                <p class="text-xs text-center" style="color:#444">© 2025 DEMOISELLE. All rights reserved.</p>
            </div>
        </div>
    </footer>
</div>

<script>
    function toggleSearchBar() {
        const bar = document.getElementById('search-bar');
        bar.classList.toggle('hidden');
        if (!bar.classList.contains('hidden')) {
            document.getElementById('search-input').focus();
        }
    }

    function clearSearch() {
        document.getElementById('search-input').value = '';
        document.getElementById('filter-category').value = 'all';
        showAllCards();
    }

    function showAllCards() {
        document.querySelectorAll('.wishlist-card').forEach(card => card.style.display = 'block');
        updateVisibleCount();
    }

    function filterCards() {
        const search = document.getElementById('search-input').value.toLowerCase();
        const category = document.getElementById('filter-category').value.toLowerCase();

        document.querySelectorAll('.wishlist-card').forEach(card => {
            const name = card.dataset.name;
            const price = card.dataset.price;
            const cat = card.dataset.category;

            const matchSearch = name.includes(search) || price.includes(search);
            const matchCategory = category === 'all' || cat === category;

            card.style.display = (matchSearch && matchCategory) ? 'block' : 'none';
        });

        updateVisibleCount();
    }

    function sortCards() {
        const grid = document.getElementById('wishlist-grid');
        const cards = Array.from(document.querySelectorAll('.wishlist-card'));
        const sortBy = document.getElementById('sort-by').value;

        cards.sort((a, b) => {
            if (sortBy === 'price-low') return Number(a.dataset.price) - Number(b.dataset.price);
            if (sortBy === 'price-high') return Number(b.dataset.price) - Number(a.dataset.price);
            if (sortBy === 'name') return a.dataset.name.localeCompare(b.dataset.name);
            return new Date(b.dataset.date) - new Date(a.dataset.date);
        });

        cards.forEach(card => grid.appendChild(card));
        filterCards();
    }

    function updateVisibleCount() {
        const visible = Array.from(document.querySelectorAll('.wishlist-card'))
            .filter(card => card.style.display !== 'none').length;

        document.getElementById('items-count').textContent = visible;
    }

    function goToOrder(name, image, price, category) {
        localStorage.setItem('selectedProduct', JSON.stringify({
            name: name,
            image: image,
            price: price,
            category: category
        }));

        window.location.href = 'ORDER_2.php';
    }

    function showToast(msg) {
        const t = document.getElementById('toast');
        document.getElementById('toast-msg').textContent = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2500);
    }

    lucide.createIcons();
</script>

</body>
</html>