<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEMOISELLE</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <script src="/_sdk/element_sdk.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&amp;family=Outfit:wght@300;400;500;600&amp;display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; }
        .font-display { font-family: 'Playfair Display', serif; }
        .app-root { height: 100%; width: 100%; overflow-y: auto; overflow-x: hidden; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .anim-fade-up { animation: fadeUp 0.8s ease forwards; opacity: 0; }
        .anim-fade-in { animation: fadeIn 0.6s ease forwards; opacity: 0; }
        .anim-slide-down { animation: slideDown 0.5s ease forwards; opacity: 0; }
        .anim-scale-in { animation: scaleIn 0.6s ease forwards; opacity: 0; }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }
        .delay-6 { animation-delay: 0.6s; }
        .delay-7 { animation-delay: 0.7s; }
        .delay-8 { animation-delay: 0.8s; }

        .product-card:hover .product-img {
            transform: scale(1.05);
        }
        .product-card:hover .product-overlay {
            opacity: 1;
        }
        .product-card:hover .quick-actions {
            transform: translateY(0);
            opacity: 1;
        }
        .product-img {
            transition: transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .product-overlay {
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        .quick-actions {
            transform: translateY(15px);
            opacity: 0;
            transition: all 0.4s ease;
        }

        .filter-btn.active {
            background: #000 !important;
            color: #fff !important;
        }

        .marquee-track {
            display: flex;
            width: max-content;
            animation: marquee 20s linear infinite;
        }

        .fav-beat {
            animation: scaleIn 0.3s ease;
        }

        .cart-badge-pop {
            animation: scaleIn 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .toast-msg {
            animation: slideDown 0.4s ease forwards, fadeIn 0.3s ease forwards;
        }

        .hero-pattern {
            background-image: repeating-linear-gradient(
                    -45deg,
                    transparent,
                    transparent 40px,
                    rgba(255,255,255,0.02) 40px,
                    rgba(255,255,255,0.02) 80px
            );
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f5f5f5; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #999; }
    </style>
    <style>body { box-sizing: border-box; }</style>
    <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
</head>
<body class="h-full">
<div class="app-root bg-white text-black" id="appRoot"><!-- Toast -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 flex flex-col gap-2" style="pointer-events:none;"></div><!-- Cart Sidebar -->
    <div id="cartOverlay" class="fixed inset-0 z-40 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="toggleCart()"></div>
        <div id="cartPanel" class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl flex flex-col" style="transform:translateX(100%);transition:transform 0.4s cubic-bezier(.4,0,.2,1)">
            <div class="flex items-center justify-between p-6 border-b border-black/10">
                <h2 class="text-lg font-medium tracking-widest uppercase">Your Bag</h2><button onclick="toggleCart()" class="p-2 hover:bg-black/5 rounded-full transition"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div id="cartItems" class="flex-1 overflow-y-auto p-6"></div>
            <div id="cartFooter" class="border-t border-black/10 p-6 hidden">
                <div class="flex justify-between mb-4 text-sm tracking-wider uppercase">
                    <span>Total</span><span id="cartTotal" class="font-medium">$0</span>
                </div><button class="w-full bg-black text-white py-4 text-sm tracking-widest uppercase hover:bg-black/80 transition">Checkout</button>
            </div>
        </div>
    </div><!-- Navbar -->
    <nav class="fixed top-0 left-0 w-full z-30 bg-white/90 backdrop-blur-md border-b border-black/5 anim-slide-down">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 flex items-center justify-between h-16">
            <div class="flex items-center gap-8"><a href="#" class="font-display text-2xl font-bold tracking-wider" id="logoText">
                    <img src="pic/logo2.png" alt="logo" style="height:65px; object-fit:contain;">
                </a>
                <div class="hidden md:flex items-center gap-6 text-xs tracking-widest uppercase"><a href="#" class="hover:opacity-50 transition">New In</a> <a href="#" class="hover:opacity-50 transition">Women</a> <a href="#" class="hover:opacity-50 transition">Men</a> <a href="#" class="hover:opacity-50 transition">Sale</a>
                </div>
            </div>
            <div class="flex items-center gap-4"><button class="p-2 hover:bg-black/5 rounded-full transition hidden sm:block"><i data-lucide="search" class="w-5 h-5"></i></button> <button class="p-2 hover:bg-black/5 rounded-full transition hidden sm:block"><i data-lucide="user" class="w-5 h-5"></i></button> <button id="favNavBtn" onclick="scrollToFavs()" class="p-2 hover:bg-black/5 rounded-full transition relative"> <i data-lucide="heart" class="w-5 h-5"></i> <span id="favCount" class="absolute -top-1 -right-1 w-5 h-5 bg-black text-white text-[10px] flex items-center justify-center rounded-full hidden">0</span> </button> <button onclick="toggleCart()" class="p-2 hover:bg-black/5 rounded-full transition relative"> <i data-lucide="shopping-bag" class="w-5 h-5"></i> <span id="cartCount" class="absolute -top-1 -right-1 w-5 h-5 bg-black text-white text-[10px] flex items-center justify-center rounded-full hidden">0</span> </button>
            </div>
        </div>
    </nav><!-- Hero -->
    <section class="relative bg-black text-white overflow-hidden" style="margin-top:64px;">
        <div class="hero-pattern absolute inset-0"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-8 py-20 sm:py-32 flex flex-col items-center text-center">
            <p class="text-xs tracking-[0.4em] uppercase mb-4 anim-fade-up delay-1 opacity-50" id="heroSubtext">Autumn / Winter 2025</p>
            <h1 class="font-display text-5xl sm:text-7xl lg:text-8xl font-bold leading-none mb-6 anim-fade-up delay-2" id="heroHeading">NEW<br>
                COLLECTION</h1>
            <p class="text-sm tracking-wider max-w-md mx-auto mb-8 opacity-60 anim-fade-up delay-3">Redefining minimalism through precision tailoring and contemporary silhouettes.</p><a href="#products" class="inline-flex items-center gap-3 bg-white text-black px-8 py-4 text-xs tracking-widest uppercase hover:bg-white/90 transition anim-fade-up delay-4"> Shop Now <i data-lucide="arrow-right" class="w-4 h-4"></i> </a> <!-- Decorative -->
        </div>
    </section><!-- Marquee -->
    <div class="border-y border-black/10 py-3 overflow-hidden bg-black/[0.02]">
        <div class="marquee-track"><span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">Free Shipping Over $150</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">★</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">New Arrivals Weekly</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">★</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">Sustainable Fashion</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">★</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">Easy Returns Within 30 Days</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">★</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">Free Shipping Over $150</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">★</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">New Arrivals Weekly</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">★</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">Sustainable Fashion</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">★</span> <span class="text-xs tracking-[0.3em] uppercase whitespace-nowrap px-8 opacity-40">Easy Returns Within 30 Days</span>
        </div>
    </div><!-- Products Section -->
    <section id="products" class="max-w-7xl mx-auto px-4 sm:px-8 py-16 sm:py-24">
        <div class="text-center mb-12">
            <h2 class="font-display text-3xl sm:text-4xl font-bold mb-2">Curated For You</h2>
            <p class="text-sm opacity-50 tracking-wider">Handpicked pieces from our latest drop</p>
        </div><!-- Filters -->
        <div class="flex flex-wrap justify-center gap-2 mb-12" id="filterBar"><button class="filter-btn active px-5 py-2 text-xs tracking-widest uppercase border border-black/20 rounded-full transition hover:bg-black hover:text-white" data-filter="all" onclick="setFilter('all')">All</button> <button class="filter-btn px-5 py-2 text-xs tracking-widest uppercase border border-black/20 rounded-full transition hover:bg-black hover:text-white" data-filter="tops" onclick="setFilter('tops')">Tops</button> <button class="filter-btn px-5 py-2 text-xs tracking-widest uppercase border border-black/20 rounded-full transition hover:bg-black hover:text-white" data-filter="bottoms" onclick="setFilter('bottoms')">Bottoms</button> <button class="filter-btn px-5 py-2 text-xs tracking-widest uppercase border border-black/20 rounded-full transition hover:bg-black hover:text-white" data-filter="outerwear" onclick="setFilter('outerwear')">Outerwear</button> <button class="filter-btn px-5 py-2 text-xs tracking-widest uppercase border border-black/20 rounded-full transition hover:bg-black hover:text-white" data-filter="dresses" onclick="setFilter('dresses')">Dresses</button> <button class="filter-btn px-5 py-2 text-xs tracking-widest uppercase border border-black/20 rounded-full transition hover:bg-black hover:text-white" data-filter="accessories" onclick="setFilter('accessories')">Accessories</button>
        </div><!-- Product Grid -->
        <div id="productGrid" class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6"></div>
    </section><!-- Favorites Section -->
    <section id="favSection" class="bg-black/[0.02] py-16 sm:py-24 hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-8">
            <div class="text-center mb-12">
                <h2 class="font-display text-3xl sm:text-4xl font-bold mb-2">Your Wishlist</h2>
                <p class="text-sm opacity-50 tracking-wider">Items you've saved for later</p>
            </div>
            <div id="favGrid" class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6"></div>
        </div>
    </section><!-- Footer -->
    <footer class="bg-black text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 py-16">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">
                <div>
                    <h3 class="font-display text-xl font-bold mb-4" id="footerLogo">
                        <img src="pic/logo2.png" alt="logo" style="height:40px;">
                    </h3>
                    <p class="text-xs opacity-50 leading-relaxed">Contemporary fashion for the modern individual. Crafted with intention.</p>
                </div>
                <div>
                    <h4 class="text-xs tracking-widest uppercase mb-4 opacity-70">Shop</h4>
                    <div class="flex flex-col gap-2 text-xs opacity-40"><a href="#" class="hover:opacity-100 transition">New Arrivals</a> <a href="#" class="hover:opacity-100 transition">Bestsellers</a> <a href="#" class="hover:opacity-100 transition">Sale</a>
                    </div>
                </div>
                <div>
                    <h4 class="text-xs tracking-widest uppercase mb-4 opacity-70">Info</h4>
                    <div class="flex flex-col gap-2 text-xs opacity-40"><a href="#" class="hover:opacity-100 transition">About</a> <a href="#" class="hover:opacity-100 transition">Sustainability</a> <a href="#" class="hover:opacity-100 transition">Careers</a>
                    </div>
                </div>
                <div>
                    <h4 class="text-xs tracking-widest uppercase mb-4 opacity-70">Follow</h4>
                    <div class="flex gap-3"><a href="#" class="w-9 h-9 border border-white/20 rounded-full flex items-center justify-center hover:bg-white hover:text-black transition"><i data-lucide="instagram" class="w-4 h-4"></i></a> <a href="#" class="w-9 h-9 border border-white/20 rounded-full flex items-center justify-center hover:bg-white hover:text-black transition"><i data-lucide="twitter" class="w-4 h-4"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 pt-8 text-center text-[10px] tracking-widest uppercase opacity-30">
                © 2025 NOIR. All rights reserved.
            </div>
        </div>
    </footer>
</div>
<script>
    // Product data with SVG placeholders
    const products = [
        { id:1, name:"Oversized Wool Blazer", price:285, category:"outerwear", tag:"New", color:"#1a1a1a", accent:"#e0e0e0" },
        { id:2, name:"Silk Camisole Top", price:120, category:"tops", tag:"Best", color:"#f5f5f5", accent:"#ccc" },
        { id:3, name:"Wide Leg Trousers", price:195, category:"bottoms", tag:"", color:"#2a2a2a", accent:"#999" },
        { id:4, name:"Cashmere Turtleneck", price:240, category:"tops", tag:"New", color:"#0d0d0d", accent:"#666" },
        { id:5, name:"Midi Wrap Dress", price:310, category:"dresses", tag:"", color:"#e8e8e8", accent:"#bbb" },
        { id:6, name:"Leather Belt", price:85, category:"accessories", tag:"", color:"#1f1f1f", accent:"#888" },
        { id:7, name:"Cropped Denim Jacket", price:225, category:"outerwear", tag:"Sale", color:"#d5d5d5", accent:"#aaa" },
        { id:8, name:"Pleated Midi Skirt", price:165, category:"bottoms", tag:"New", color:"#f0f0f0", accent:"#ddd" },
        { id:9, name:"Structured Tote Bag", price:195, category:"accessories", tag:"Best", color:"#0a0a0a", accent:"#555" },
        { id:10, name:"Linen Blend Shirt", price:135, category:"tops", tag:"", color:"#fafafa", accent:"#ccc" },
        { id:11, name:"Slip Dress", price:275, category:"dresses", tag:"Sale", color:"#151515", accent:"#777" },
        { id:12, name:"Tailored Coat", price:420, category:"outerwear", tag:"New", color:"#222", accent:"#999" },
    ];

    // Generate unique SVG for each product
    function productSVG(p) {
        const isLight = parseInt(p.color.replace('#',''), 16) > 0x888888;
        const textColor = isLight ? '#333' : '#eee';
        const shapes = [
            `<rect x="40" y="30" width="120" height="160" rx="4" fill="${p.accent}" opacity="0.3"/>
     <rect x="55" y="50" width="90" height="120" rx="2" fill="${p.accent}" opacity="0.5"/>
     <line x1="100" y1="50" x2="100" y2="170" stroke="${textColor}" opacity="0.1" stroke-width="0.5"/>`,
            `<circle cx="100" cy="100" r="60" fill="${p.accent}" opacity="0.25"/>
     <circle cx="100" cy="100" r="35" fill="${p.accent}" opacity="0.4"/>
     <circle cx="100" cy="100" r="12" fill="${textColor}" opacity="0.15"/>`,
            `<polygon points="100,25 165,175 35,175" fill="${p.accent}" opacity="0.3"/>
     <polygon points="100,55 145,155 55,155" fill="${p.accent}" opacity="0.2"/>`,
            `<rect x="25" y="60" width="150" height="100" rx="50" fill="${p.accent}" opacity="0.3"/>
     <ellipse cx="100" cy="110" rx="45" ry="30" fill="${p.accent}" opacity="0.4"/>`,
            `<path d="M50,170 Q75,30 100,100 Q125,170 150,40" fill="none" stroke="${p.accent}" stroke-width="3" opacity="0.5"/>
     <circle cx="75" cy="80" r="20" fill="${p.accent}" opacity="0.2"/>
     <circle cx="130" cy="120" r="15" fill="${p.accent}" opacity="0.3"/>`,
            `<rect x="30" y="40" width="60" height="80" rx="3" fill="${p.accent}" opacity="0.35"/>
     <rect x="110" y="100" width="60" height="80" rx="3" fill="${p.accent}" opacity="0.25"/>
     <rect x="70" y="70" width="60" height="80" rx="3" fill="${p.accent}" opacity="0.15"/>`,
        ];
        const shape = shapes[p.id % shapes.length];
        return `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 220"><rect width="200" height="220" fill="${p.color}"/>${shape}<text x="100" y="205" text-anchor="middle" font-family="serif" font-size="8" fill="${textColor}" opacity="0.4">NOIR</text></svg>`)}`;
    }

    let favorites = new Set();
    let cart = [];
    let currentFilter = 'all';

    function setFilter(f) {
        currentFilter = f;
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.toggle('active', b.dataset.filter === f));
        renderProducts();
    }

    function renderProducts() {
        const grid = document.getElementById('productGrid');
        const filtered = currentFilter === 'all' ? products : products.filter(p => p.category === currentFilter);
        grid.innerHTML = '';
        filtered.forEach((p, i) => {
            const isFav = favorites.has(p.id);
            const tagColor = p.tag === 'Sale' ? 'bg-black text-white' : p.tag === 'New' ? 'bg-black text-white' : 'bg-black/80 text-white';
            const card = document.createElement('div');
            card.className = `product-card group cursor-pointer anim-fade-up`;
            card.style.animationDelay = `${i * 0.08}s`;
            card.innerHTML = `
      <div class="relative overflow-hidden rounded-lg mb-3 aspect-[3/4] bg-gray-100">
        <img src="${productSVG(p)}" alt="${p.name}" class="product-img w-full h-full object-cover" loading="lazy">
        <div class="product-overlay absolute inset-0 bg-black/10"></div>
        ${p.tag ? `<span class="absolute top-3 left-3 ${tagColor} text-[10px] tracking-widest uppercase px-3 py-1 rounded-full">${p.tag}</span>` : ''}
        <div class="quick-actions absolute bottom-3 left-3 right-3 flex gap-2">
          <button onclick="event.stopPropagation();addToCart(${p.id})" class="flex-1 bg-white text-black py-2.5 text-[10px] tracking-widest uppercase rounded-md hover:bg-black hover:text-white transition flex items-center justify-center gap-2">
            <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i> Add to Bag
          </button>
          <button onclick="event.stopPropagation();toggleFav(${p.id})" class="w-10 bg-white text-black rounded-md hover:bg-black hover:text-white transition flex items-center justify-center ${isFav ? '!bg-black !text-white' : ''}">
            <i data-lucide="heart" class="w-4 h-4 ${isFav ? 'fill-current' : ''}"></i>
          </button>
        </div>
      </div>
      <h3 class="text-sm font-medium mb-1 tracking-wide">${p.name}</h3>
      <p class="text-sm opacity-50">${p.tag === 'Sale' ? `<span class="line-through mr-2">$${p.price}</span><span class="text-black font-medium">$${Math.round(p.price * 0.7)}</span>` : `$${p.price}`}</p>
    `;
            grid.appendChild(card);
        });
        lucide.createIcons();
    }

    function toggleFav(id) {
        if (favorites.has(id)) favorites.delete(id);
        else favorites.add(id);
        updateFavCount();
        renderProducts();
        renderFavorites();
    }

    function updateFavCount() {
        const el = document.getElementById('favCount');
        if (favorites.size > 0) { el.textContent = favorites.size; el.classList.remove('hidden'); el.classList.add('cart-badge-pop'); }
        else el.classList.add('hidden');
        const sec = document.getElementById('favSection');
        if (favorites.size > 0) sec.classList.remove('hidden');
        else sec.classList.add('hidden');
    }

    function renderFavorites() {
        const grid = document.getElementById('favGrid');
        grid.innerHTML = '';
        products.filter(p => favorites.has(p.id)).forEach(p => {
            const card = document.createElement('div');
            card.className = 'product-card group anim-scale-in';
            card.innerHTML = `
      <div class="relative overflow-hidden rounded-lg mb-3 aspect-[3/4] bg-gray-100">
        <img src="${productSVG(p)}" alt="${p.name}" class="product-img w-full h-full object-cover" loading="lazy">
        <div class="product-overlay absolute inset-0 bg-black/10"></div>
        <div class="quick-actions absolute bottom-3 left-3 right-3 flex gap-2">
          <button onclick="addToCart(${p.id})" class="flex-1 bg-white text-black py-2.5 text-[10px] tracking-widest uppercase rounded-md hover:bg-black hover:text-white transition flex items-center justify-center gap-2">
            <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i> Add to Bag
          </button>
          <button onclick="toggleFav(${p.id})" class="w-10 bg-black text-white rounded-md flex items-center justify-center">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
      <h3 class="text-sm font-medium mb-1 tracking-wide">${p.name}</h3>
      <p class="text-sm opacity-50">$${p.price}</p>
    `;
            grid.appendChild(card);
        });
        lucide.createIcons();
    }

    function addToCart(id) {
        const p = products.find(x => x.id === id);
        const existing = cart.find(x => x.id === id);
        if (existing) existing.qty++;
        else cart.push({ ...p, qty: 1 });
        updateCartCount();
        renderCart();
        showToast(`${p.name} added to bag`);
    }

    function removeFromCart(id) {
        cart = cart.filter(x => x.id !== id);
        updateCartCount();
        renderCart();
    }

    function updateCartCount() {
        const el = document.getElementById('cartCount');
        const total = cart.reduce((s, c) => s + c.qty, 0);
        if (total > 0) { el.textContent = total; el.classList.remove('hidden'); el.classList.add('cart-badge-pop'); }
        else el.classList.add('hidden');
    }

    function renderCart() {
        const container = document.getElementById('cartItems');
        const footer = document.getElementById('cartFooter');
        if (cart.length === 0) {
            container.innerHTML = `<div class="flex flex-col items-center justify-center h-full opacity-40">
      <i data-lucide="shopping-bag" class="w-12 h-12 mb-4"></i>
      <p class="text-sm tracking-wider">Your bag is empty</p>
    </div>`;
            footer.classList.add('hidden');
            lucide.createIcons();
            return;
        }
        footer.classList.remove('hidden');
        container.innerHTML = cart.map(item => {
            const actualPrice = item.tag === 'Sale' ? Math.round(item.price * 0.7) : item.price;
            return `<div class="flex gap-4 mb-6 pb-6 border-b border-black/5">
      <div class="w-20 h-24 rounded bg-gray-100 overflow-hidden flex-shrink-0">
        <img src="${productSVG(item)}" alt="${item.name}" class="w-full h-full object-cover" loading="lazy">
      </div>
      <div class="flex-1 min-w-0">
        <h4 class="text-sm font-medium mb-1 truncate">${item.name}</h4>
        <p class="text-sm opacity-50 mb-2">$${actualPrice}</p>
        <div class="flex items-center gap-3">
          <button onclick="changeQty(${item.id},-1)" class="w-7 h-7 border border-black/20 rounded-full flex items-center justify-center text-xs hover:bg-black hover:text-white transition">−</button>
          <span class="text-sm w-4 text-center">${item.qty}</span>
          <button onclick="changeQty(${item.id},1)" class="w-7 h-7 border border-black/20 rounded-full flex items-center justify-center text-xs hover:bg-black hover:text-white transition">+</button>
          <button onclick="removeFromCart(${item.id})" class="ml-auto p-1 opacity-40 hover:opacity-100 transition"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
        </div>
      </div>
    </div>`;
        }).join('');
        const total = cart.reduce((s, c) => s + c.qty * (c.tag === 'Sale' ? Math.round(c.price * 0.7) : c.price), 0);
        document.getElementById('cartTotal').textContent = `$${total}`;
        lucide.createIcons();
    }

    function changeQty(id, delta) {
        const item = cart.find(x => x.id === id);
        if (!item) return;
        item.qty += delta;
        if (item.qty <= 0) cart = cart.filter(x => x.id !== id);
        updateCartCount();
        renderCart();
    }

    function toggleCart() {
        const overlay = document.getElementById('cartOverlay');
        const panel = document.getElementById('cartPanel');
        const isOpen = !overlay.classList.contains('hidden');
        if (isOpen) {
            panel.style.transform = 'translateX(100%)';
            setTimeout(() => overlay.classList.add('hidden'), 400);
        } else {
            overlay.classList.remove('hidden');
            requestAnimationFrame(() => panel.style.transform = 'translateX(0)');
            renderCart();
        }
    }

    function scrollToFavs() {
        const sec = document.getElementById('favSection');
        if (!sec.classList.contains('hidden')) sec.scrollIntoView({ behavior: 'smooth' });
    }

    function showToast(msg) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast-msg bg-black text-white px-5 py-3 rounded-lg text-xs tracking-wider shadow-xl flex items-center gap-2';
        toast.style.pointerEvents = 'auto';
        toast.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i> ${msg}`;
        container.appendChild(toast);
        lucide.createIcons();
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 2500);
    }

    // Element SDK
    const defaultConfig = {
        background_color: '#ffffff',
        surface_color: '#000000',
        text_color: '#000000',
        primary_action_color: '#000000',
        secondary_action_color: '#666666',
        font_family: 'Outfit',
        font_size: 14,
        brand_name: 'NOIR',
        hero_heading: 'NEW COLLECTION',
        hero_subtext: 'Autumn / Winter 2025',
    };

    function applyConfig(config) {
        const bg = config.background_color || defaultConfig.background_color;
        const surface = config.surface_color || defaultConfig.surface_color;
        const text = config.text_color || defaultConfig.text_color;
        const primary = config.primary_action_color || defaultConfig.primary_action_color;
        const font = config.font_family || defaultConfig.font_family;
        const size = config.font_size || defaultConfig.font_size;

        const root = document.getElementById('appRoot');
        root.style.backgroundColor = bg;
        root.style.color = text;
        root.style.fontFamily = `${font}, Outfit, sans-serif`;
        root.style.fontSize = `${size}px`;

        document.getElementById('logoText').textContent = config.brand_name || defaultConfig.brand_name;
        document.getElementById('footerLogo').textContent = config.brand_name || defaultConfig.brand_name;
        document.getElementById('heroHeading').innerHTML = (config.hero_heading || defaultConfig.hero_heading).replace(' ', '<br>');
        document.getElementById('heroSubtext').textContent = config.hero_subtext || defaultConfig.hero_subtext;

        document.querySelectorAll('.font-display').forEach(el => el.style.fontFamily = `Playfair Display, ${font}, serif`);
    }

    if (window.elementSdk) {
        window.elementSdk.init({
            defaultConfig,
            onConfigChange: async (config) => applyConfig(config),
            mapToCapabilities: (config) => ({
                recolorables: [
                    { get: () => config.background_color || defaultConfig.background_color, set: v => { config.background_color = v; window.elementSdk.setConfig({ background_color: v }); }},
                    { get: () => config.surface_color || defaultConfig.surface_color, set: v => { config.surface_color = v; window.elementSdk.setConfig({ surface_color: v }); }},
                    { get: () => config.text_color || defaultConfig.text_color, set: v => { config.text_color = v; window.elementSdk.setConfig({ text_color: v }); }},
                    { get: () => config.primary_action_color || defaultConfig.primary_action_color, set: v => { config.primary_action_color = v; window.elementSdk.setConfig({ primary_action_color: v }); }},
                    { get: () => config.secondary_action_color || defaultConfig.secondary_action_color, set: v => { config.secondary_action_color = v; window.elementSdk.setConfig({ secondary_action_color: v }); }},
                ],
                borderables: [],
                fontEditable: {
                    get: () => config.font_family || defaultConfig.font_family,
                    set: v => { config.font_family = v; window.elementSdk.setConfig({ font_family: v }); }
                },
                fontSizeable: {
                    get: () => config.font_size || defaultConfig.font_size,
                    set: v => { config.font_size = v; window.elementSdk.setConfig({ font_size: v }); }
                }
            }),
            mapToEditPanelValues: (config) => new Map([
                ['brand_name', config.brand_name || defaultConfig.brand_name],
                ['hero_heading', config.hero_heading || defaultConfig.hero_heading],
                ['hero_subtext', config.hero_subtext || defaultConfig.hero_subtext],
            ])
        });
    }

    // Init
    renderProducts();
    lucide.createIcons();
</script>
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'9eee0e6c9510146b',t:'MTc3NjYyNDIyMi4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>