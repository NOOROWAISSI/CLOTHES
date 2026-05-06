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
$lang = in_array($lang, ['en', 'ar', 'he']) ? $lang : 'en';

$text = [
        'en' => [
                'home' => 'Home',
                'collection' => 'Collection',
                'categories' => 'Categories',
                'our_story' => 'Our Story',
                'contact' => 'Contact',
                'languages' => 'Languages',
                'pages' => 'Pages',
                'shop' => 'Shop',
                'company' => 'Company',
                'support' => 'Support',
                'new_arrivals' => 'New Arrivals',
                'instagram' => 'Instagram',
                'contact_us' => 'Contact Us',
                'shop_new' => 'Shop New Collection',
                'shop_categories' => 'Shop Categories',
                'season' => 'Autumn / Winter 2025',
                'hero_title' => 'Elegance<br>Redefined',
                'hero_desc' => 'Timeless feminine pieces crafted for the modern woman who values grace, quality, and intention.',
                'free_shipping' => 'Free Shipping on Orders Over $150',
                'ethical' => 'Ethically Sourced Materials',
                'sustainable' => 'Timeless & Sustainable',
                'available' => 'New Collection Now Available',
                'just_arrived' => 'Just Arrived',
                'new_collection' => 'New Collection',
                'shop_by_category' => 'Shop by Category',
                'est' => 'Est. 2019 • Palestine',
                'about_title' => 'Demoiselle',
                'about_p1' => 'Founded in the heart of Palestine in 2019, demoiselle was born from a deep love for feminine elegance and conscious design.',
                'about_p2' => 'We create timeless wardrobe essentials that celebrate the modern woman — refined silhouettes, premium fabrics, and meticulous attention to detail.',
                'join_circle' => 'Join the Circle',
                'subscribe_text' => 'Subscribe to receive early access to new collections, exclusive offers, and style inspiration.',
                'subscribe' => 'Subscribe',
                'email_placeholder' => 'your@email.com',
                'accessibility' => 'Accessibility Tools',
                'customize' => 'Customize your experience',
                'big_text' => 'Big Text',
                'contrast' => 'Contrast',
                'no_motion' => 'No Motion',
                'readable_font' => 'Readable Font',
                'read_clicked' => '👆 Read Clicked Text',
                'read_all' => '🔊 Read All Page',
                'stop_reading' => '⛔ Stop Reading',
                'reset' => 'Reset',
                'quick_add' => 'Quick Add',
                'no_products' => 'No products found.',
                'no_categories' => 'No categories found.',
                'thank_you' => 'Thank you! You’ve been added to our list.',
                'added_cart' => 'Added to cart ✓',
                'click_on' => 'Click-to-read is ON',
                'click_off' => 'Click-to-read is OFF',
                'voice_not_supported' => 'Your browser does not support voice reading.',
                'rights' => '© 2025 demoiselle. All rights reserved.',
                'made' => 'Made with love in Palestine',
                'order' => 'Order',
                'wishlist' => 'Wishlist',
                'user' => 'User',
                'item' => 'Item',
                'search' => 'Search',
                'cart' => 'Cart',
                'profile' => 'Profile',
                'about' => 'About',
                'sign_in' => 'Sign In',
                'sign_up' => 'Sign Up',
                'logout' => 'Logout',
                'user_signin' => 'User / Sign In',
                'footer_desc' => 'Timeless elegance. Conscious fashion.'
        ],

        'ar' => [
                'home' => 'الرئيسية',
                'collection' => 'المجموعة',
                'categories' => 'الأقسام',
                'our_story' => 'قصتنا',
                'contact' => 'تواصل معنا',
                'languages' => 'اللغات',
                'pages' => 'الصفحات',
                'shop' => 'تسوق',
                'company' => 'الشركة',
                'support' => 'الدعم',
                'new_arrivals' => 'وصل حديثًا',
                'instagram' => 'إنستغرام',
                'contact_us' => 'تواصل معنا',
                'shop_new' => 'تسوقي المجموعة الجديدة',
                'shop_categories' => 'تسوقي الأقسام',
                'season' => 'خريف / شتاء 2025',
                'hero_title' => 'أناقة<br>متجددة',
                'hero_desc' => 'قطع أنثوية خالدة مصممة للمرأة العصرية التي تهتم بالأناقة والجودة والتميز.',
                'free_shipping' => 'توصيل مجاني للطلبات فوق 150$',
                'ethical' => 'مواد مختارة بعناية',
                'sustainable' => 'أناقة خالدة ومستدامة',
                'available' => 'المجموعة الجديدة متوفرة الآن',
                'just_arrived' => 'وصل حديثًا',
                'new_collection' => 'المجموعة الجديدة',
                'shop_by_category' => 'تسوقي حسب القسم',
                'est' => 'تأسست 2019 • فلسطين',
                'about_title' => 'ديموازيل',
                'about_p1' => 'تأسست ديموازيل في قلب فلسطين عام 2019، من حب عميق للأناقة الأنثوية والتصميم الواعي.',
                'about_p2' => 'نصمم قطعًا أساسية خالدة تحتفي بالمرأة العصرية من خلال قصات راقية وأقمشة فاخرة واهتمام دقيق بالتفاصيل.',
                'join_circle' => 'انضمي إلينا',
                'subscribe_text' => 'اشتركي للحصول على وصول مبكر للمجموعات الجديدة والعروض الحصرية ونصائح الأناقة.',
                'subscribe' => 'اشتراك',
                'email_placeholder' => 'بريدك الإلكتروني',
                'accessibility' => 'أدوات الوصول',
                'customize' => 'خصصي تجربتك',
                'big_text' => 'تكبير النص',
                'contrast' => 'تباين',
                'no_motion' => 'إيقاف الحركة',
                'readable_font' => 'خط واضح',
                'read_clicked' => '👆 قراءة النص المضغوط',
                'read_all' => '🔊 قراءة الصفحة كاملة',
                'stop_reading' => '⛔ إيقاف القراءة',
                'reset' => 'إعادة ضبط',
                'quick_add' => 'إضافة سريعة',
                'no_products' => 'لا توجد منتجات.',
                'no_categories' => 'لا توجد أقسام.',
                'thank_you' => 'شكرًا لكِ! تمت إضافتك إلى قائمتنا.',
                'added_cart' => 'تمت الإضافة إلى السلة ✓',
                'click_on' => 'تم تشغيل القراءة عند الضغط',
                'click_off' => 'تم إيقاف القراءة عند الضغط',
                'voice_not_supported' => 'المتصفح لا يدعم القراءة الصوتية.',
                'rights' => '© 2025 ديموازيل. جميع الحقوق محفوظة.',
                'made' => 'صنع بحب في فلسطين',
                'order' => 'الطلب',
                'wishlist' => 'المفضلة',
                'user' => 'المستخدم',
                'item' => 'المنتج',
                'search' => 'البحث',
                'cart' => 'السلة',
                'profile' => 'الملف الشخصي',
                'about' => 'من نحن',
                'sign_in' => 'تسجيل الدخول',
                'sign_up' => 'إنشاء حساب',
                'logout' => 'تسجيل الخروج',
                'user_signin' => 'المستخدم / تسجيل الدخول',
                'footer_desc' => 'أناقة خالدة. أزياء واعية.'
        ],

        'he' => [
                'home' => 'בית',
                'collection' => 'קולקציה',
                'categories' => 'קטגוריות',
                'our_story' => 'הסיפור שלנו',
                'contact' => 'צור קשר',
                'languages' => 'שפות',
                'pages' => 'עמודים',
                'shop' => 'חנות',
                'company' => 'חברה',
                'support' => 'תמיכה',
                'new_arrivals' => 'חדשים',
                'instagram' => 'אינסטגרם',
                'contact_us' => 'צור קשר',
                'shop_new' => 'קנו קולקציה חדשה',
                'shop_categories' => 'קנו לפי קטגוריה',
                'season' => 'סתיו / חורף 2025',
                'hero_title' => 'אלגנטיות<br>מחודשת',
                'hero_desc' => 'פריטים נשיים ועל־זמניים שנוצרו לאישה המודרנית שמעריכה אלגנטיות, איכות וכוונה.',
                'free_shipping' => 'משלוח חינם בהזמנות מעל $150',
                'ethical' => 'חומרים שנבחרו באחריות',
                'sustainable' => 'על־זמני ובר קיימא',
                'available' => 'הקולקציה החדשה זמינה עכשיו',
                'just_arrived' => 'חדש באתר',
                'new_collection' => 'קולקציה חדשה',
                'shop_by_category' => 'קנייה לפי קטגוריה',
                'est' => 'נוסד 2019 • פלסטין',
                'about_title' => 'Demoiselle',
                'about_p1' => 'Demoiselle נוסדה בלב פלסטין בשנת 2019 מתוך אהבה עמוקה לאלגנטיות נשית ולעיצוב מודע.',
                'about_p2' => 'אנחנו יוצרות פריטי מלתחה על־זמניים שחוגגים את האישה המודרנית עם גזרות מעודנות, בדים איכותיים ותשומת לב לפרטים.',
                'join_circle' => 'הצטרפו אלינו',
                'subscribe_text' => 'הירשמו לקבלת גישה מוקדמת לקולקציות חדשות, מבצעים בלעדיים והשראה לסטייל.',
                'subscribe' => 'הרשמה',
                'email_placeholder' => 'האימייל שלך',
                'accessibility' => 'כלי נגישות',
                'customize' => 'התאימו את החוויה שלכם',
                'big_text' => 'טקסט גדול',
                'contrast' => 'ניגודיות',
                'no_motion' => 'ללא תנועה',
                'readable_font' => 'גופן קריא',
                'read_clicked' => '👆 קריאת טקסט בלחיצה',
                'read_all' => '🔊 קריאת כל העמוד',
                'stop_reading' => '⛔ עצירת קריאה',
                'reset' => 'איפוס',
                'quick_add' => 'הוספה מהירה',
                'no_products' => 'לא נמצאו מוצרים.',
                'no_categories' => 'לא נמצאו קטגוריות.',
                'thank_you' => 'תודה! נוספת לרשימה שלנו.',
                'added_cart' => 'נוסף לסל ✓',
                'click_on' => 'קריאה בלחיצה הופעלה',
                'click_off' => 'קריאה בלחיצה כובתה',
                'voice_not_supported' => 'הדפדפן שלך אינו תומך בקריאה קולית.',
                'rights' => '© 2025 demoiselle. כל הזכויות שמורות.',
                'made' => 'נוצר באהבה בפלסטין',
                'order' => 'הזמנה',
                'wishlist' => 'מועדפים',
                'user' => 'משתמש',
                'item' => 'פריט',
                'search' => 'חיפוש',
                'cart' => 'עגלה',
                'profile' => 'פרופיל',
                'about' => 'אודות',
                'sign_in' => 'התחברות',
                'sign_up' => 'הרשמה',
                'logout' => 'התנתקות',
                'user_signin' => 'משתמש / התחברות',
                'footer_desc' => 'אלגנטיות על־זמנית. אופנה מודעת.'
        ]
];

$t = $text[$lang];

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

function langUrl($newLang) {
    $params = $_GET;
    $params['lang'] = $newLang;
    return basename($_SERVER['PHP_SELF']) . '?' . http_build_query($params);
}
?>

<!doctype html>
<html lang="<?= htmlspecialchars($lang) ?>" class="h-full" <?= ($lang === 'ar' || $lang === 'he') ? 'dir="rtl"' : 'dir="ltr"' ?>>
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

        html[lang="ar"] body,
        html[lang="he"] body {
            font-family: Arial, sans-serif;
        }

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

        html[dir="rtl"] .nav-link::after {
            left: auto;
            right: 0;
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
            max-height: 900px;
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

        html[dir="rtl"] .logo-watermark {
            left: auto;
            right: 18px;
        }

        .new-collection-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.48);
            z-index: 5;
            transition: opacity 0.7s ease;
        }

        .product-card:hover .new-collection-overlay { opacity: 0; }

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

        html[dir="rtl"] .new-tag {
            right: auto;
            left: 14px;
        }

        .cats-marquee {
            width: 100vw;
            overflow-x: hidden;
            overflow-y: visible;
            position: relative;
            padding: 70px 0 50px;
            direction: ltr;
        }

        .cats-track {
            display: flex;
            align-items: center;
            width: max-content;
            padding-top: 30px;
            padding-bottom: 30px;
            animation: catsMove 35s linear infinite;
            direction: ltr;
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
            margin-left: 0;
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
            text-align: center;
            padding: 8px;
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
                margin-left: 0;
            }

            .cat-card span { font-size: 14px; }
        }

        @media (max-width: 480px) {
            .cat-card {
                width: 110px;
                height: 145px;
                margin-right: -12px;
                margin-left: 0;
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

        html[dir="rtl"] .dropdown-link:hover {
            transform: translateX(-3px);
        }

        svg { stroke: #fff !important; }

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

        html[dir="rtl"] .accessibility-btn {
            right: auto;
            left: 25px;
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

        html[dir="rtl"] .accessibility-panel {
            right: auto;
            left: 25px;
            text-align: right;
        }

        .accessibility-panel.open { display: block; }

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

        body.large-text { font-size: 120%; }
        body.high-contrast { filter: contrast(1.5); }
        body.no-motion * { animation:none!important; transition:none!important; }
        body.readable-font * { font-family: Arial!important; }
    </style>
</head>

<body class="font-body" style="background:#000; color:#fff;">

<div id="app-wrapper" class="w-full overflow-auto" style="background:#000;">

    <header class="w-full fixed top-0 left-0 z-50 anim-slide-down" style="background:rgba(0,0,0,0.9); backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,0.08);">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">

            <a href="index.php?lang=<?= htmlspecialchars($lang) ?>" class="flex items-center">
                <img src="pic/lolo1.png" alt="demoiselle Logo" id="logo-img">
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm tracking-wider uppercase font-light" style="color:rgba(255,255,255,0.7);">
                <a href="#hero" class="nav-link hover:text-white transition-colors"><?= $t['home'] ?></a>
                <a href="#collection" class="nav-link hover:text-white transition-colors"><?= $t['collection'] ?></a>
                <a href="#bestsellers" class="nav-link hover:text-white transition-colors"><?= $t['categories'] ?></a>
                <a href="#about" class="nav-link hover:text-white transition-colors"><?= $t['our_story'] ?></a>
                <a href="#footer" class="nav-link hover:text-white transition-colors"><?= $t['contact'] ?></a>
            </nav>

            <div class="hidden md:flex items-center gap-4 relative">

                <button id="main-dropdown-btn" title="Menu">
                    <i data-lucide="menu" style="width:24px;height:24px;"></i>
                </button>

                <div id="main-dropdown" class="hidden absolute top-10 <?= ($lang === 'ar' || $lang === 'he') ? 'left-0 text-right' : 'right-0 text-left' ?> w-96 max-h-[80vh] overflow-y-auto bg-black border border-white/10 rounded-2xl p-6 shadow-2xl z-[200]">

                    <h4 class="text-xs tracking-[0.3em] uppercase text-white/40 mb-3"><?= $t['languages'] ?></h4>

                    <div class="flex gap-3 mb-6">
                        <a href="<?= langUrl('en') ?>" class="px-3 py-1 border border-white/20 rounded-full text-xs hover:bg-white hover:text-black transition">EN</a>
                        <a href="<?= langUrl('ar') ?>" class="px-3 py-1 border border-white/20 rounded-full text-xs hover:bg-white hover:text-black transition">AR</a>
                        <a href="<?= langUrl('he') ?>" class="px-3 py-1 border border-white/20 rounded-full text-xs hover:bg-white hover:text-black transition">HE</a>
                    </div>

                    <h4 class="text-xs tracking-[0.3em] uppercase text-white/40 mb-3"><?= $t['categories'] ?></h4>

                    <div class="grid grid-cols-2 gap-3 text-sm mb-6">
                        <?php
                        $menuCats = [
                                'jeans' => 'Jeans', 'pants' => 'Pants', 'blouses' => 'Blouses', 'shirts' => 'Shirts',
                                'dresses' => 'Dresses', 'formal' => 'Formal', 'jackets' => 'Jackets', 'abaya' => 'Abaya',
                                'skirts' => 'Skirts', 'bags' => 'Bags', 'belts' => 'Belts', 'vests' => 'Vests',
                                'overalls' => 'Overalls', 'outfits' => 'Outfits', 'casual' => 'Casual', 'blazers' => 'Blazers'
                        ];
                        foreach ($menuCats as $key => $value):
                            ?>
                            <a class="dropdown-link" href="<?= $key ?>.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($value) ?></a>
                        <?php endforeach; ?>
                    </div>

                    <h4 class="text-xs tracking-[0.3em] uppercase text-white/40 mb-3"><?= $t['pages'] ?></h4>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <a class="dropdown-link" href="index.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['home'] ?></a>
                        <a class="dropdown-link" href="new_collection.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['new_collection'] ?></a>
                        <a class="dropdown-link" href="search.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['search'] ?></a>
                        <a class="dropdown-link" href="cart.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['cart'] ?></a>
                        <a class="dropdown-link" href="wishlist.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['wishlist'] ?></a>
                        <a class="dropdown-link" href="order.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['order'] ?></a>
                        <a class="dropdown-link" href="profile.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['profile'] ?></a>
                        <a class="dropdown-link" href="contact.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['contact'] ?></a>
                        <a class="dropdown-link" href="about.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['about'] ?></a>
                        <a class="dropdown-link" href="signin.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['sign_in'] ?></a>
                        <a class="dropdown-link" href="signup.php?lang=<?= htmlspecialchars($lang) ?>"><?= $t['sign_up'] ?></a>
                        <a class="dropdown-link" href="logout.php"><?= $t['logout'] ?></a>
                    </div>
                </div>

                <button onclick="goPage('search.php?lang=<?= htmlspecialchars($lang) ?>', true)">
                    <i data-lucide="search" style="width:18px;height:18px;"></i>
                </button>

                <button onclick="goUserPage()">
                    <i data-lucide="user" style="width:18px;height:18px;"></i>
                </button>

                <button onclick="goPage('wishlist.php?lang=<?= htmlspecialchars($lang) ?>', true)" id="wishlist-btn" class="relative">
                    <i data-lucide="heart" style="width:18px;height:18px;"></i>
                    <span id="wishlist-count" class="absolute -top-1 -right-2 text-[10px] w-4 h-4 rounded-full flex items-center justify-center bg-white text-black hidden">0</span>
                </button>

                <button onclick="goPage('cart.php?lang=<?= htmlspecialchars($lang) ?>', true)" id="cart-btn" class="relative">
                    <i data-lucide="shopping-bag" style="width:18px;height:18px;"></i>
                    <span id="cart-count" class="absolute -top-1 -right-2 text-[10px] w-4 h-4 rounded-full flex items-center justify-center bg-white text-black">0</span>
                </button>

                <?php if ($isLoggedIn): ?>
                    <span class="text-xs text-white/60"><?= htmlspecialchars($currentUserName) ?></span>
                <?php endif; ?>
            </div>

            <button class="md:hidden text-white" id="mobile-toggle">
                <i data-lucide="menu" style="width:24px;height:24px;"></i>
            </button>
        </div>

        <div class="mobile-menu md:hidden px-6 pb-4" id="mobile-menu">
            <nav class="flex flex-col gap-4 text-sm tracking-wider uppercase font-light pt-2" style="color:rgba(255,255,255,0.7); border-top:1px solid rgba(255,255,255,0.08);">
                <a href="#hero"><?= $t['home'] ?></a>
                <a href="#collection"><?= $t['collection'] ?></a>
                <a href="#bestsellers"><?= $t['categories'] ?></a>
                <a href="#about"><?= $t['our_story'] ?></a>
                <a href="#footer"><?= $t['contact'] ?></a>

                <div class="flex gap-3 pt-2">
                    <a href="<?= langUrl('en') ?>">EN</a>
                    <a href="<?= langUrl('ar') ?>">AR</a>
                    <a href="<?= langUrl('he') ?>">HE</a>
                </div>

                <button onclick="goUserPage()" class="text-left"><?= $t['user_signin'] ?></button>
                <button onclick="goPage('cart.php?lang=<?= htmlspecialchars($lang) ?>', true)" class="text-left"><?= $t['cart'] ?></button>
                <button onclick="goPage('wishlist.php?lang=<?= htmlspecialchars($lang) ?>', true)" class="text-left"><?= $t['wishlist'] ?></button>
            </nav>
        </div>
    </header>

    <section id="hero" class="w-full relative flex items-center justify-center">
        <div class="relative z-10 text-center px-6 max-w-4xl mx-auto pt-20 pb-16">
            <p class="text-xs tracking-[0.4em] uppercase mb-6 anim-fade-up delay-1" style="color:rgba(255,255,255,0.85);">
                <?= $t['season'] ?>
            </p>

            <h1 class="font-display text-5xl sm:text-7xl md:text-8xl font-light leading-[0.95] mb-6 anim-fade-up delay-2" style="color:#fff;">
                <?= $t['hero_title'] ?>
            </h1>

            <p class="text-sm sm:text-base font-light tracking-wide max-w-md mx-auto mb-10 anim-fade-up delay-3" style="color:rgba(255,255,255,0.9);">
                <?= $t['hero_desc'] ?>
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center anim-fade-up delay-4">
                <a href="#collection" class="btn-noir inline-block px-10 py-4 text-xs tracking-[0.3em] uppercase border" style="background:#fff; color:#000; border-color:#fff;">
                    <?= $t['shop_new'] ?>
                </a>
                <a href="#bestsellers" class="btn-noir inline-block px-10 py-4 text-xs tracking-[0.3em] uppercase border" style="background:transparent; color:#fff; border-color:rgba(255,255,255,0.6);">
                    <?= $t['shop_categories'] ?>
                </a>
            </div>
        </div>
    </section>

    <div class="w-full py-4 overflow-hidden" style="background:#fff; color:#000;">
        <div class="marquee-track">
            <span class="font-display text-lg tracking-[0.3em] uppercase mx-8 whitespace-nowrap"><?= $t['free_shipping'] ?></span>
            <span class="mx-4 opacity-30">✦</span>
            <span class="font-display text-lg tracking-[0.3em] uppercase mx-8 whitespace-nowrap"><?= $t['ethical'] ?></span>
            <span class="mx-4 opacity-30">✦</span>
            <span class="font-display text-lg tracking-[0.3em] uppercase mx-8 whitespace-nowrap"><?= $t['sustainable'] ?></span>
            <span class="mx-4 opacity-30">✦</span>
            <span class="font-display text-lg tracking-[0.3em] uppercase mx-8 whitespace-nowrap"><?= $t['available'] ?></span>
        </div>
    </div>

    <section id="collection" class="w-full py-20 px-6" style="background:#0a0a0a;">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <p class="text-xs tracking-[0.4em] uppercase mb-3" style="color:rgba(255,255,255,0.35);"><?= $t['just_arrived'] ?></p>
                <h2 class="font-display text-4xl sm:text-5xl font-light" style="color:#fff;"><?= $t['new_collection'] ?></h2>
                <div class="w-12 h-px mx-auto mt-6" style="background:rgba(255,255,255,0.2);"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php if ($products_result->num_rows > 0): ?>
                    <?php while($product = $products_result->fetch_assoc()): ?>
                        <div class="product-card group cursor-pointer new-collection">
                            <div class="product-image-container relative aspect-[3/4] overflow-hidden mb-4">
                                <img src="<?= htmlspecialchars($product['image_url'] ?: 'pic/default.jpg') ?>"
                                     alt="<?= htmlspecialchars($product['product_name']) ?>"
                                     class="main-img w-full h-full object-cover transition-transform duration-500">

                                <img src="pic/logo2.png" class="logo-watermark" alt="demoiselle">
                                <div class="new-collection-overlay"></div>
                                <span class="new-tag">NEW</span>

                                <div class="product-overlay absolute inset-0 flex items-end justify-center pb-6 opacity-0 transition-opacity duration-300" style="background:rgba(0,0,0,0.65);">
                                    <button onclick="quickAdd(<?= (int)$product['product_id'] ?>)"
                                            class="px-8 py-3 text-[10px] tracking-[0.2em] uppercase transition-all hover:opacity-80"
                                            style="background:#fff; color:#000;">
                                        <?= $t['quick_add'] ?>
                                    </button>
                                </div>
                            </div>

                            <h3 class="text-sm font-light mb-1" style="color:#fff;">
                                <?= htmlspecialchars($product['product_name']) ?>
                            </h3>

                            <p class="text-xs" style="color:rgba(255,255,255,0.45);">
                                $<?= htmlspecialchars($product['price']) ?>
                            </p>

                            <button onclick="toggleWishlist(<?= (int)$product['product_id'] ?>, this)"
                                    class="mt-3 text-xl wishlist-btn">♡</button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center col-span-4 text-white/50"><?= $t['no_products'] ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="bestsellers" class="py-24 bg-white text-black text-center overflow-hidden">
        <div class="px-6">
            <h2 class="font-display text-5xl mb-16"><?= $t['shop_by_category'] ?></h2>

            <div class="cats-marquee">
                <div class="cats-track">
                    <?php if ($categories_result->num_rows > 0): ?>
                        <?php while($cat = $categories_result->fetch_assoc()): ?>
                            <a href="<?= htmlspecialchars($cat['category_key']) ?>.php?lang=<?= htmlspecialchars($lang) ?>" class="cat-card">
                                <img src="pic/<?= htmlspecialchars(imgName($cat['category_key'])) ?>"
                                     alt="<?= htmlspecialchars($cat['category_name']) ?>">
                                <span><?= htmlspecialchars($cat['category_name']) ?></span>
                            </a>
                        <?php endwhile; ?>

                        <?php
                        $categories_result->data_seek(0);
                        while($cat = $categories_result->fetch_assoc()):
                            ?>
                            <a href="<?= htmlspecialchars($cat['category_key']) ?>.php?lang=<?= htmlspecialchars($lang) ?>" class="cat-card">
                                <img src="pic/<?= htmlspecialchars(imgName($cat['category_key'])) ?>"
                                     alt="<?= htmlspecialchars($cat['category_name']) ?>">
                                <span><?= htmlspecialchars($cat['category_name']) ?></span>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p><?= $t['no_categories'] ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="w-full py-24 px-6" style="background:#000;">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
            <div class="about-image-container relative overflow-hidden aspect-[3/4] border border-white/10">
                <img src="pic/about1.jpg" alt="Demoiselle Story" class="about-image">

                <div class="absolute bottom-6 left-6 right-6">
                    <p class="text-[10px] tracking-[0.3em] uppercase" style="color:rgba(255,255,255,0.25);">
                        <?= $t['est'] ?>
                    </p>
                </div>
            </div>

            <div>
                <p class="text-xs tracking-[0.4em] uppercase mb-4" style="color:rgba(255,255,255,0.35);"><?= $t['our_story'] ?></p>

                <h2 class="font-display text-4xl sm:text-5xl font-light mb-8" style="color:#fff;">
                    <?= $t['about_title'] ?>
                </h2>

                <p class="text-sm leading-relaxed mb-6 font-light" style="color:rgba(255,255,255,0.55);">
                    <?= $t['about_p1'] ?>
                </p>

                <p class="text-sm leading-relaxed mb-10 font-light" style="color:rgba(255,255,255,0.55);">
                    <?= $t['about_p2'] ?>
                </p>
            </div>
        </div>
    </section>

    <section class="w-full py-20 px-6" style="background:#111; border-top:1px solid rgba(255,255,255,0.05); border-bottom:1px solid rgba(255,255,255,0.05);">
        <div class="max-w-xl mx-auto text-center">
            <h3 class="font-display text-3xl font-light mb-3" style="color:#fff;"><?= $t['join_circle'] ?></h3>

            <p class="text-sm font-light mb-8" style="color:rgba(255,255,255,0.4);">
                <?= $t['subscribe_text'] ?>
            </p>

            <form id="newsletter-form" class="flex gap-3 max-w-md mx-auto">
                <input id="email-input"
                       type="email"
                       placeholder="<?= $t['email_placeholder'] ?>"
                       class="flex-1 px-4 py-3 text-sm font-light outline-none"
                       style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#fff;"
                       required>

                <button type="submit"
                        class="px-6 py-3 text-xs tracking-[0.2em] uppercase transition-all hover:opacity-80"
                        style="background:#fff; color:#000;">
                    <?= $t['subscribe'] ?>
                </button>
            </form>

            <p id="newsletter-msg" class="text-xs mt-4 hidden" style="color:rgba(255,255,255,0.5);"></p>
        </div>
    </section>

    <button class="accessibility-btn" id="accessibility-btn">♿</button>

    <div class="accessibility-panel" id="accessibility-panel">
        <h3 style="font-size:18px; margin-bottom:6px;"><?= $t['accessibility'] ?></h3>
        <p style="font-size:12px; color:#555;"><?= $t['customize'] ?></p>

        <button onclick="toggleLargeText()"><?= $t['big_text'] ?></button>
        <button onclick="toggleContrast()"><?= $t['contrast'] ?></button>
        <button onclick="toggleMotion()"><?= $t['no_motion'] ?></button>
        <button onclick="toggleFont()"><?= $t['readable_font'] ?></button>

        <button onclick="toggleClickRead()"><?= $t['read_clicked'] ?></button>
        <button onclick="readAllPage()"><?= $t['read_all'] ?></button>
        <button onclick="stopRead()"><?= $t['stop_reading'] ?></button>

        <button onclick="resetAccessibility()"><?= $t['reset'] ?></button>
    </div>

    <footer id="footer" class="w-full py-16 px-6" style="background:#000; border-top:1px solid rgba(255,255,255,0.06);">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-14">
                <div>
                    <a href="index.php?lang=<?= htmlspecialchars($lang) ?>" class="inline-block mb-4">
                        <img src="pic/lolo1.png" alt="demoiselle Logo" class="h-10 w-auto">
                    </a>

                    <p class="text-xs font-light leading-relaxed" style="color:rgba(255,255,255,0.35);">
                        <?= $t['footer_desc'] ?>
                    </p>
                </div>

                <div>
                    <h4 class="text-[10px] tracking-[0.3em] uppercase mb-5" style="color:rgba(255,255,255,0.5);"><?= $t['shop'] ?></h4>
                    <nav class="flex flex-col gap-3">
                        <a href="#collection" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= $t['new_arrivals'] ?></a>
                        <a href="#bestsellers" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= $t['categories'] ?></a>
                    </nav>
                </div>

                <div>
                    <h4 class="text-[10px] tracking-[0.3em] uppercase mb-5" style="color:rgba(255,255,255,0.5);"><?= $t['company'] ?></h4>
                    <nav class="flex flex-col gap-3">
                        <a href="#about" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= $t['our_story'] ?></a>
                    </nav>
                </div>

                <div>
                    <h4 class="text-[10px] tracking-[0.3em] uppercase mb-5" style="color:rgba(255,255,255,0.5);"><?= $t['support'] ?></h4>
                    <nav class="flex flex-col gap-3">
                        <a href="https://www.instagram.com/demoisellepal" target="_blank" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= $t['instagram'] ?></a>
                        <a href="contact.php?lang=<?= htmlspecialchars($lang) ?>" class="text-xs font-light hover:text-white" style="color:rgba(255,255,255,0.35);"><?= $t['contact_us'] ?></a>
                    </nav>
                </div>
            </div>

            <div class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4" style="border-top:1px solid rgba(255,255,255,0.06);">
                <p class="text-[10px] tracking-[0.2em]" style="color:rgba(255,255,255,0.25);">
                    <?= $t['rights'] ?>
                </p>

                <p class="text-[10px] tracking-[0.2em]" style="color:rgba(255,255,255,0.25);">
                    <?= $t['made'] ?>
                </p>
            </div>

            <div class="mt-8 flex flex-wrap gap-4">
                <button onclick="goPage('order.php?lang=<?= htmlspecialchars($lang) ?>', true)" class="px-4 py-2 bg-white text-black"><?= $t['order'] ?></button>
                <button onclick="window.location.href='new_collection.php?lang=<?= htmlspecialchars($lang) ?>'" class="px-4 py-2 bg-white text-black"><?= $t['new_collection'] ?></button>
                <button onclick="goPage('wishlist.php?lang=<?= htmlspecialchars($lang) ?>', true)" class="px-4 py-2 bg-white text-black"><?= $t['wishlist'] ?></button>
                <button onclick="goUserPage()" class="px-4 py-2 bg-white text-black"><?= $t['user'] ?></button>
                <button onclick="window.location.href='item.php?lang=<?= htmlspecialchars($lang) ?>'" class="px-4 py-2 bg-white text-black"><?= $t['item'] ?></button>
            </div>
        </div>
    </footer>

</div>

<script>
    lucide.createIcons();

    const T = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
    const currentLang = "<?= htmlspecialchars($lang) ?>";
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

    let cart = [];
    let wishlist = [];

    function withLang(page) {
        if (page.includes('?')) {
            return page + '&lang=' + currentLang;
        }
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
        if (isLoggedIn) {
            window.location.href = withLang('profile.php');
        } else {
            window.location.href = withLang('signin.php');
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
            window.location.href = withLang('signin.php');
            return;
        }

        cart.push(id);
        document.getElementById('cart-count').textContent = cart.length;
        showToast(T.added_cart);
    };

    window.toggleWishlist = function(id, btn) {
        if (!isLoggedIn) {
            window.location.href = withLang('signin.php');
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
            window.location.href = withLang('signin.php');
            return;
        }

        const msg = document.getElementById('newsletter-msg');
        msg.textContent = T.thank_you;
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

    let clickReadEnabled = false;

    function getVoiceLang() {
        if (currentLang === 'ar') return 'ar-SA';
        if (currentLang === 'he') return 'he-IL';
        return 'en-US';
    }

    function speakText(text) {
        if (!('speechSynthesis' in window)) {
            alert(T.voice_not_supported);
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
            showToast(T.click_on);
        } else {
            showToast(T.click_off);
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
            e.target.closest('#accessibility-btn')
        ) {
            return;
        }

        let text = e.target.innerText || e.target.textContent || e.target.getAttribute('aria-label') || e.target.getAttribute('title');
        speakText(text);
    });

    if(localStorage.getItem('largeText') === 'true') document.body.classList.add('large-text');
    if(localStorage.getItem('contrast') === 'true') document.body.classList.add('high-contrast');
    if(localStorage.getItem('motion') === 'true') document.body.classList.add('no-motion');
    if(localStorage.getItem('font') === 'true') document.body.classList.add('readable-font');
</script>

</body>
</html>