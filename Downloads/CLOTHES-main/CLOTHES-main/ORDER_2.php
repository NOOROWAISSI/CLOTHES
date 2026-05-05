<?php
include "db.php";

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // مؤقت للتجربة
}

$user_id = $_SESSION['user_id'];
$success_message = "";

$areas = $conn->query("SELECT * FROM delivery_areas");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['place_order'])) {
    $area_id = intval($_POST['area_id']);
    $subtotal = floatval($_POST['subtotal']);
    $payment_method = $_POST['payment_method'];

    $area_stmt = $conn->prepare("SELECT delivery_price FROM delivery_areas WHERE area_id=?");
    $area_stmt->bind_param("i", $area_id);
    $area_stmt->execute();
    $area = $area_stmt->get_result()->fetch_assoc();

    $delivery_price = $area ? floatval($area['delivery_price']) : 0;
    $total_price = $subtotal + $delivery_price;

    $order_sql = "INSERT INTO orders 
                  (user_id, area_id, subtotal, delivery_price, total_price, order_status)
                  VALUES (?, ?, ?, ?, ?, 'NEW')";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("iiddd", $user_id, $area_id, $subtotal, $delivery_price, $total_price);
    $stmt->execute();

    $order_id = $conn->insert_id;

    $db_payment_method = ($payment_method === "cod") ? "CASH" : "CARD";

    $payment_sql = "INSERT INTO payments (order_id, method, status)
                    VALUES (?, ?, 'PENDING')";
    $stmt = $conn->prepare($payment_sql);
    $stmt->bind_param("is", $order_id, $db_payment_method);
    $stmt->execute();

    $success_message = "Order placed successfully! Order ID: #" . $order_id;
}
?>

<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOIR — Order</title>

    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body { font-family: 'DM Sans', sans-serif; }
        .font-display { font-family: 'Playfair Display', serif; }

        @keyframes slideIn { from { opacity:0; transform: translateY(30px); } to { opacity:1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes scaleIn { from { opacity:0; transform: scale(0.9); } to { opacity:1; transform: scale(1); } }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        @keyframes checkPop { 0% { transform: scale(0) rotate(-45deg); } 60% { transform: scale(1.2) rotate(0); } 100% { transform: scale(1) rotate(0); } }

        .anim-slide { animation: slideIn 0.6s ease forwards; }
        .anim-fade { animation: fadeIn 0.5s ease forwards; }
        .anim-scale { animation: scaleIn 0.5s ease forwards; }
        .anim-check { animation: checkPop 0.5s ease forwards; }

        .step-hidden { display: none; }
        .step-active { display: flex; }

        .marquee-track { display: flex; animation: marquee 20s linear infinite; }
        .marquee-track:hover { animation-play-state: paused; }

        .cloth-card { transition: all 0.4s cubic-bezier(0.25,0.8,0.25,1); }
        .cloth-card:hover { transform: translateY(-6px); }

        .btn-main { transition: all 0.3s ease; position: relative; overflow: hidden; }
        .btn-main::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(-50%,-50%);
            transition: width 0.5s, height 0.5s;
        }
        .btn-main:hover::after { width: 300px; height: 300px; }

        .radio-custom:checked + label { border-color: #000; background: #000; color: #fff; }
        .radio-custom:checked + label .radio-dot { background: #fff; }

        .notification-bar { animation: slideIn 0.4s ease forwards; }
        .progress-line { transition: width 0.5s ease; }

        input:focus, select:focus { outline: none; border-color: #000; }

        .item-remove { transition: all 0.3s ease; }
        .item-remove:hover { background: #000; color: #fff; }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: #f5f5f5; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 2px; }

        .stagger-1 { animation-delay: 0.1s; opacity: 0; }
        .stagger-2 { animation-delay: 0.2s; opacity: 0; }
        .stagger-3 { animation-delay: 0.3s; opacity: 0; }
        .stagger-4 { animation-delay: 0.4s; opacity: 0; }
        .stagger-5 { animation-delay: 0.5s; opacity: 0; }
    </style>
</head>

<body class="h-full bg-white text-black">

<div id="app" class="h-full w-full flex flex-col overflow-auto">

    <!-- HEADER -->
    <header class="w-full border-b border-black/10 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-3">
            <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                <rect width="36" height="36" rx="2" fill="#000" />
                <path d="M8 26V10h4l6 10 6-10h4v16h-4V16l-6 10-6-10v10H8z" fill="#fff" />
            </svg>
            <span class="font-display text-xl font-bold tracking-tight">MAISON NOIR</span>
        </div>

        <div class="flex items-center gap-2 text-xs text-black/50">
            <i data-lucide="lock" style="width:14px;height:14px"></i>
            <span>Secure Checkout</span>
        </div>
    </header>

    <!-- PROGRESS BAR -->
    <div class="w-full px-6 py-3 flex-shrink-0">
        <div class="flex items-center justify-between mb-2">
            <span id="step-label" class="text-xs font-medium tracking-widest uppercase">Order Review</span>
            <span id="step-count" class="text-xs text-black/40">Step 1 of 4</span>
        </div>

        <div class="w-full h-1 bg-black/10 rounded-full overflow-hidden">
            <div id="progress-bar" class="h-full bg-black rounded-full progress-line" style="width:25%"></div>
        </div>
    </div>

    <div id="notification-area" class="px-6 flex-shrink-0"></div>

    <?php if (!empty($success_message)): ?>
        <div class="mx-6 mt-3 bg-black text-white rounded-lg px-4 py-3 text-sm">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <!-- STEP 1 -->
    <div id="step-1" class="step-active flex-1 flex flex-col px-6 pb-6">
        <div class="flex-1 overflow-auto">

            <div class="w-full overflow-hidden border-y border-black/10 py-2 my-4">
                <div class="marquee-track whitespace-nowrap">
                    <span class="text-xs tracking-[0.3em] uppercase text-black/30 mx-8">FREE SHIPPING OVER $100</span>
                    <span class="text-xs tracking-[0.3em] uppercase text-black/30 mx-8">✦</span>
                    <span class="text-xs tracking-[0.3em] uppercase text-black/30 mx-8">14-DAY RETURNS</span>
                    <span class="text-xs tracking-[0.3em] uppercase text-black/30 mx-8">✦</span>
                    <span class="text-xs tracking-[0.3em] uppercase text-black/30 mx-8">WORLDWIDE DELIVERY</span>
                    <span class="text-xs tracking-[0.3em] uppercase text-black/30 mx-8">✦</span>
                    <span class="text-xs tracking-[0.3em] uppercase text-black/30 mx-8">FREE SHIPPING OVER $100</span>
                </div>
            </div>

            <h2 class="font-display text-2xl font-bold mb-1 anim-slide">Your Bag</h2>
            <p class="text-sm text-black/50 mb-5 anim-slide stagger-1">Review your selected pieces</p>

            <div id="order-items" class="space-y-4"></div>

            <div class="mt-6 border-t border-black/10 pt-4 space-y-2 anim-slide stagger-3">
                <div class="flex justify-between text-sm">
                    <span class="text-black/50">Subtotal</span>
                    <span id="subtotal">$0</span>
                </div>

                <div class="flex justify-between text-sm">
                    <span class="text-black/50">Delivery</span>
                    <span id="delivery-cost" class="text-black/50">Calculated next</span>
                </div>

                <div class="flex justify-between text-base font-bold border-t border-black/10 pt-3 mt-3">
                    <span>Total</span>
                    <span id="total">$0</span>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-4 flex-shrink-0">
            <button onclick="showNotification('🛍️ Keep shopping — your bag is saved!', 'info')"
                    class="flex-1 border border-black/20 text-black py-3 text-sm font-medium tracking-wider uppercase btn-main hover:bg-black/5 rounded">
                Continue Shopping
            </button>

            <button onclick="goToStep(2)"
                    class="flex-1 bg-black text-white py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">
                Proceed
            </button>
        </div>
    </div>

    <!-- STEP 2 -->
    <div id="step-2" class="step-hidden flex-1 flex flex-col px-6 pb-6">
        <div class="flex-1 overflow-auto">
            <h2 class="font-display text-2xl font-bold mb-1 anim-slide">Payment Method</h2>
            <p class="text-sm text-black/50 mb-6 anim-slide stagger-1">Choose how you'd like to pay</p>

            <div class="space-y-3" id="payment-methods">

                <div class="anim-slide stagger-1">
                    <input type="radio" name="payment" id="pay-card" value="card" class="radio-custom sr-only" checked>
                    <label for="pay-card" class="flex items-center gap-4 border-2 border-black/10 rounded-lg p-4 cursor-pointer transition-all">
                        <div class="w-10 h-10 rounded-full border-2 border-black/20 flex items-center justify-center flex-shrink-0">
                            <div class="radio-dot w-4 h-4 rounded-full transition-all"></div>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm">Credit / Debit Card</div>
                            <div class="text-xs text-black/40 mt-0.5">Visa, Mastercard, Amex</div>
                        </div>
                    </label>
                </div>

                <div class="anim-slide stagger-2">
                    <input type="radio" name="payment" id="pay-cod" value="cod" class="radio-custom sr-only">
                    <label for="pay-cod" class="flex items-center gap-4 border-2 border-black/10 rounded-lg p-4 cursor-pointer transition-all">
                        <div class="w-10 h-10 rounded-full border-2 border-black/20 flex items-center justify-center flex-shrink-0">
                            <div class="radio-dot w-4 h-4 rounded-full transition-all"></div>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm">Cash on Delivery</div>
                            <div class="text-xs text-black/40 mt-0.5">Pay when your order arrives</div>
                        </div>
                        <i data-lucide="banknote" style="width:24px;height:24px;color:#999"></i>
                    </label>
                </div>

                <div class="anim-slide stagger-3">
                    <input type="radio" name="payment" id="pay-wallet" value="wallet" class="radio-custom sr-only">
                    <label for="pay-wallet" class="flex items-center gap-4 border-2 border-black/10 rounded-lg p-4 cursor-pointer transition-all">
                        <div class="w-10 h-10 rounded-full border-2 border-black/20 flex items-center justify-center flex-shrink-0">
                            <div class="radio-dot w-4 h-4 rounded-full transition-all"></div>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm">Digital Wallet</div>
                            <div class="text-xs text-black/40 mt-0.5">Apple Pay, Google Pay</div>
                        </div>
                        <i data-lucide="wallet" style="width:24px;height:24px;color:#999"></i>
                    </label>
                </div>

                <div class="anim-slide stagger-4">
                    <input type="radio" name="payment" id="pay-transfer" value="transfer" class="radio-custom sr-only">
                    <label for="pay-transfer" class="flex items-center gap-4 border-2 border-black/10 rounded-lg p-4 cursor-pointer transition-all">
                        <div class="w-10 h-10 rounded-full border-2 border-black/20 flex items-center justify-center flex-shrink-0">
                            <div class="radio-dot w-4 h-4 rounded-full transition-all"></div>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm">Bank Transfer</div>
                            <div class="text-xs text-black/40 mt-0.5">Direct bank payment</div>
                        </div>
                        <i data-lucide="landmark" style="width:24px;height:24px;color:#999"></i>
                    </label>
                </div>
            </div>

            <div id="card-details" class="mt-5 p-4 border border-black/10 rounded-lg bg-black/[0.02]">
                <p class="text-xs text-black/40 mb-3 flex items-center gap-1">
                    <i data-lucide="info" style="width:12px;height:12px"></i>
                    Demo — no real card data stored
                </p>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium tracking-wider uppercase text-black/60 block mb-1">Cardholder Name</label>
                        <input type="text" placeholder="Jane Doe" class="w-full border border-black/15 rounded px-3 py-2.5 text-sm bg-white">
                    </div>

                    <div>
                        <label class="text-xs font-medium tracking-wider uppercase text-black/60 block mb-1">Card Number</label>
                        <input type="text" placeholder="•••• •••• •••• ••••" maxlength="19" class="w-full border border-black/15 rounded px-3 py-2.5 text-sm bg-white">
                    </div>

                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="text-xs font-medium tracking-wider uppercase text-black/60 block mb-1">Expiry</label>
                            <input type="text" placeholder="MM/YY" maxlength="5" class="w-full border border-black/15 rounded px-3 py-2.5 text-sm bg-white">
                        </div>

                        <div class="flex-1">
                            <label class="text-xs font-medium tracking-wider uppercase text-black/60 block mb-1">CVV</label>
                            <input type="text" placeholder="•••" maxlength="3" class="w-full border border-black/15 rounded px-3 py-2.5 text-sm bg-white">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-4 flex-shrink-0">
            <button onclick="goToStep(1)" class="flex-1 border border-black/20 py-3 text-sm font-medium tracking-wider uppercase btn-main hover:bg-black/5 rounded">
                Back
            </button>

            <button onclick="goToStep(3)" class="flex-1 bg-black text-white py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">
                Continue
            </button>
        </div>
    </div>

    <!-- STEP 3 -->
    <div id="step-3" class="step-hidden flex-1 flex flex-col px-6 pb-6">
        <div class="flex-1 overflow-auto">

            <h2 class="font-display text-2xl font-bold mb-1 anim-slide">Delivery & Policy</h2>
            <p class="text-sm text-black/50 mb-6 anim-slide stagger-1">Important information before you confirm</p>

            <div class="anim-slide stagger-1 border border-black/10 rounded-lg overflow-hidden mb-4">
                <div class="bg-black text-white px-4 py-3 flex items-center gap-2">
                    <i data-lucide="truck" style="width:18px;height:18px"></i>
                    <span class="text-sm font-medium tracking-wider uppercase">Delivery Pricing By Area</span>
                </div>

                <div class="p-4">
                    <label class="text-xs font-medium tracking-wider uppercase text-black/60 block mb-2">
                        Select Your Area
                    </label>

                    <select id="area_id" class="w-full border border-black/15 rounded px-3 py-2.5 text-sm bg-white" onchange="updateTotals()">
                        <?php while ($row = $areas->fetch_assoc()): ?>
                            <option value="<?= $row['area_id'] ?>" data-price="<?= $row['delivery_price'] ?>">
                                <?= htmlspecialchars($row['city']) ?> - <?= htmlspecialchars($row['area_name']) ?>
                                | Delivery: $<?= number_format($row['delivery_price'], 2) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="anim-slide stagger-2 border border-black/10 rounded-lg overflow-hidden mb-4">
                <div class="bg-black text-white px-4 py-3 flex items-center gap-2">
                    <i data-lucide="refresh-cw" style="width:18px;height:18px"></i>
                    <span class="text-sm font-medium tracking-wider uppercase">Exchange & Return Policy</span>
                </div>

                <div class="p-4 space-y-3 text-sm text-black/70 leading-relaxed">
                    <p>✦ <strong>14-day return window</strong> from the date of delivery for all unworn items with tags attached.</p>
                    <p>✦ <strong>Free exchanges</strong> on all orders — simply request a size or color swap.</p>
                    <p>✦ <strong>Refunds</strong> processed within 5 business days of receiving the returned item.</p>
                    <p>✦ <strong>Sale items</strong> are eligible for exchange only, no refunds.</p>
                    <p>✦ <strong>Damaged items</strong> — contact us within 48 hours for immediate replacement.</p>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-4 flex-shrink-0">
            <button onclick="goToStep(2)" class="flex-1 border border-black/20 py-3 text-sm font-medium tracking-wider uppercase btn-main hover:bg-black/5 rounded">
                Back
            </button>

            <button onclick="goToStep(4)" class="flex-1 bg-black text-white py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">
                Confirm Order
            </button>
        </div>
    </div>

    <!-- STEP 4 -->
    <div id="step-4" class="step-hidden flex-1 flex flex-col items-center justify-center px-6 pb-6 text-center">
        <div class="anim-scale">
            <div class="w-20 h-20 rounded-full bg-black flex items-center justify-center mx-auto mb-6">
                <svg class="anim-check" width="36" height="36" viewBox="0 0 36 36" fill="none">
                    <path d="M10 18l6 6 10-12" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
        </div>

        <h2 class="font-display text-3xl font-bold mb-2 anim-slide stagger-1">Order Confirmed</h2>
        <p class="text-sm text-black/50 mb-2 anim-slide stagger-2">Thank you for shopping with us</p>
        <p class="text-xs text-black/30 mb-6 anim-slide stagger-3" id="order-number">Order #MN-2026-00001</p>

        <div class="w-full max-w-xs space-y-3 anim-slide stagger-4">
            <div class="border border-black/10 rounded-lg p-4 text-left">
                <div class="text-xs text-black/40 uppercase tracking-wider mb-2">Order Summary</div>
                <div id="confirm-summary" class="space-y-1 text-sm"></div>

                <div class="border-t border-black/10 mt-3 pt-3 flex justify-between font-bold text-sm">
                    <span>Total</span>
                    <span id="confirm-total">$0</span>
                </div>
            </div>

            <div class="border border-black/10 rounded-lg p-4 text-left">
                <div class="text-xs text-black/40 uppercase tracking-wider mb-2">Delivery</div>
                <div class="text-sm" id="confirm-delivery">Area delivery</div>
                <div class="text-sm mt-1" id="confirm-payment">Payment method</div>
            </div>
        </div>

        <div class="flex gap-3 mt-6 w-full max-w-xs anim-slide stagger-5">
            <form method="POST" class="flex-1">
                <input type="hidden" name="place_order" value="1">
                <input type="hidden" name="subtotal" id="subtotal-input">
                <input type="hidden" name="payment_method" id="payment-method-input">
                <input type="hidden" name="area_id" id="area-id-input">

                <button type="submit"
                        onclick="prepareOrderSubmit()"
                        class="w-full border border-black/20 py-3 text-sm font-medium tracking-wider uppercase btn-main hover:bg-black/5 rounded flex items-center justify-center gap-2">
                    <i data-lucide="bookmark" style="width:16px;height:16px"></i>
                    Save
                </button>
            </form>

            <button onclick="resetOrder()" class="flex-1 bg-black text-white py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">
                New Order
            </button>
        </div>
    </div>
</div>

<script>
    let currentStep = 1;

    const clothesData = [
        { id: 1, name: 'Oversized Wool Coat', color: '#2a2a2a', accent: '#555', price: 189, size: 'M',
            pattern: (ctx,w,h)=>{ ctx.fillStyle='#2a2a2a'; ctx.fillRect(0,0,w,h); ctx.fillStyle='#444'; ctx.fillRect(w*0.2,h*0.25,w*0.6,h*0.5); }},
        { id: 2, name: 'Silk Drape Blouse', color: '#f5f0eb', accent: '#d4ccc3', price: 78, size: 'S',
            pattern: (ctx,w,h)=>{ ctx.fillStyle='#f5f0eb'; ctx.fillRect(0,0,w,h); ctx.fillStyle='#ebe3db'; ctx.fillRect(w*0.25,h*0.2,w*0.5,h*0.55); }},
        { id: 3, name: 'Tailored Linen Trousers', color: '#e8e4de', accent: '#c8c2b8', price: 112, size: 'L',
            pattern: (ctx,w,h)=>{ ctx.fillStyle='#e8e4de'; ctx.fillRect(0,0,w,h); ctx.fillStyle='#d5d0c8'; ctx.fillRect(w*0.2,h*0.1,w*0.25,h*0.8); ctx.fillRect(w*0.55,h*0.1,w*0.25,h*0.8); }},
        { id: 4, name: 'Merino Knit Sweater', color: '#1a1a1a', accent: '#333', price: 95, size: 'M',
            pattern: (ctx,w,h)=>{ ctx.fillStyle='#1a1a1a'; ctx.fillRect(0,0,w,h); ctx.fillStyle='#252525'; ctx.fillRect(w*0.2,h*0.15,w*0.6,h*0.55); }},
    ];

    const selectedProduct = getSelectedProductFromStorage();
    let orderItems = selectedProduct ? [selectedProduct] : clothesData.map(c => ({...c, qty: 1}));

    function getSelectedProductFromStorage() {
        const raw = localStorage.getItem('selectedProduct');
        if (!raw) return null;

        try {
            const product = JSON.parse(raw);
            return {
                id: Date.now(),
                name: product.name || 'Selected Product',
                image: product.image || '',
                price: Number(product.price) || 0,
                category: product.category || '',
                size: 'M',
                qty: 1
            };
        } catch (e) {
            return null;
        }
    }

    function generateClothImage(item, size=120) {
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size * 1.3;

        const ctx = canvas.getContext('2d');
        item.pattern(ctx, canvas.width, canvas.height);

        ctx.fillStyle = 'rgba(255,255,255,0.15)';
        ctx.font = `bold ${size*0.08}px "DM Sans", sans-serif`;
        ctx.textAlign = 'center';
        ctx.fillText('MN', canvas.width/2, canvas.height - size*0.1);

        return canvas.toDataURL();
    }

    function renderOrderItems() {
        const container = document.getElementById('order-items');
        container.innerHTML = '';

        orderItems.forEach((item, idx) => {
            const imgSrc = item.image ? item.image : generateClothImage(item);

            const div = document.createElement('div');
            div.className = 'cloth-card flex gap-4 border border-black/10 rounded-lg p-3 anim-fade stagger-' + Math.min(idx+2, 5);

            div.innerHTML = `
        <img src="${imgSrc}" alt="${item.name}" class="w-20 h-26 object-cover rounded" loading="lazy">
        <div class="flex-1 min-w-0">
          <div class="flex justify-between items-start">
            <div>
              <h3 class="text-sm font-medium truncate">${item.name}</h3>
              <p class="text-xs text-black/40 mt-0.5">Size ${item.size}</p>
            </div>

            <button onclick="removeItem(${item.id})" class="item-remove w-7 h-7 rounded-full border border-black/15 flex items-center justify-center flex-shrink-0">
              <i data-lucide="x" style="width:14px;height:14px"></i>
            </button>
          </div>

          <div class="flex items-center justify-between mt-3">
            <div class="flex items-center border border-black/15 rounded overflow-hidden">
              <button onclick="changeQty(${item.id},-1)" class="px-2 py-1 text-xs hover:bg-black/5">−</button>
              <span class="px-3 py-1 text-xs font-medium border-x border-black/15">${item.qty}</span>
              <button onclick="changeQty(${item.id},1)" class="px-2 py-1 text-xs hover:bg-black/5">+</button>
            </div>

            <span class="text-sm font-bold">$${(item.price * item.qty).toFixed(2)}</span>
          </div>
        </div>
      `;

            container.appendChild(div);
        });

        updateTotals();
        lucide.createIcons();
    }

    function removeItem(id) {
        orderItems = orderItems.filter(i => i.id !== id);

        if (orderItems.length === 0) {
            showNotification('Your bag is empty — add items to continue', 'warning');
        }

        renderOrderItems();
    }

    function changeQty(id, delta) {
        const item = orderItems.find(i => i.id === id);

        if (item) {
            item.qty = Math.max(1, Math.min(10, item.qty + delta));
            renderOrderItems();
        }
    }

    function getSelectedAreaPrice() {
        const areaSelect = document.getElementById('area_id');
        const selected = areaSelect?.options[areaSelect.selectedIndex];
        return Number(selected?.dataset.price || 0);
    }

    function getSelectedAreaText() {
        const areaSelect = document.getElementById('area_id');
        const selected = areaSelect?.options[areaSelect.selectedIndex];
        return selected?.textContent.trim() || 'Area delivery';
    }

    function updateTotals() {
        const subtotal = orderItems.reduce((s, i) => s + i.price * i.qty, 0);
        const delivery = getSelectedAreaPrice();
        const total = subtotal + delivery;

        document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('delivery-cost').textContent = '$' + delivery.toFixed(2);
        document.getElementById('total').textContent = '$' + total.toFixed(2);
    }

    function goToStep(step) {
        if (step === 2 && orderItems.length === 0) {
            showNotification('Add at least one item to your bag first', 'warning');
            return;
        }

        document.getElementById('step-' + currentStep).className = 'step-hidden';
        document.getElementById('step-' + step).className = 'step-active flex-1 flex flex-col px-6 pb-6';

        if (step === 4) {
            document.getElementById('step-' + step).className = 'step-active flex-1 flex flex-col items-center justify-center px-6 pb-6 text-center';
        }

        currentStep = step;

        const labels = ['Order Review', 'Payment', 'Delivery & Policy', 'Confirmed'];
        document.getElementById('step-label').textContent = labels[step - 1];
        document.getElementById('step-count').textContent = `Step ${step} of 4`;
        document.getElementById('progress-bar').style.width = (step * 25) + '%';

        if (step === 2) updatePaymentView();
        if (step === 4) buildConfirmation();

        lucide.createIcons();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updatePaymentView() {
        document.querySelectorAll('input[name="payment"]').forEach(r => {
            r.addEventListener('change', () => {
                document.getElementById('card-details').style.display = r.value === 'card' ? 'block' : 'none';
            });
        });
    }

    function buildConfirmation() {
        const summary = document.getElementById('confirm-summary');
        summary.innerHTML = '';

        orderItems.forEach(item => {
            const div = document.createElement('div');
            div.className = 'flex justify-between';
            div.innerHTML = `<span class="text-black/60">${item.name} ×${item.qty}</span><span>$${(item.price*item.qty).toFixed(2)}</span>`;
            summary.appendChild(div);
        });

        const subtotal = orderItems.reduce((s,i) => s + i.price * i.qty, 0);
        const delivery = getSelectedAreaPrice();
        const total = subtotal + delivery;

        document.getElementById('confirm-total').textContent = '$' + total.toFixed(2);
        document.getElementById('confirm-delivery').textContent = getSelectedAreaText();

        const payMethod = document.querySelector('input[name="payment"]:checked');
        const payLabels = {
            card: 'Credit / Debit Card',
            cod: 'Cash on Delivery',
            wallet: 'Digital Wallet',
            transfer: 'Bank Transfer'
        };

        document.getElementById('confirm-payment').textContent = 'Payment: ' + (payLabels[payMethod?.value] || 'Credit Card');

        document.getElementById('order-number').textContent =
            'Order #MN-' + new Date().getFullYear() + '-' + String(Math.floor(Math.random()*99999)).padStart(5,'0');

        showNotification('✅ Your order is ready to save!', 'success');
    }

    function prepareOrderSubmit() {
        const subtotal = orderItems.reduce((s, i) => s + i.price * i.qty, 0);
        const payment = document.querySelector('input[name="payment"]:checked')?.value || 'cod';
        const areaId = document.getElementById('area_id')?.value || 1;

        document.getElementById('subtotal-input').value = subtotal.toFixed(2);
        document.getElementById('payment-method-input').value = payment;
        document.getElementById('area-id-input').value = areaId;
    }

    let notifTimeout;

    function showNotification(message, type='info') {
        clearTimeout(notifTimeout);

        const area = document.getElementById('notification-area');

        const colors = {
            success: 'bg-black text-white',
            warning: 'bg-white text-black border border-black',
            info: 'bg-black/90 text-white'
        };

        const icons = {
            success: 'check-circle',
            warning: 'alert-triangle',
            info: 'info'
        };

        area.innerHTML = `
      <div class="notification-bar ${colors[type]} rounded-lg px-4 py-3 mb-3 flex items-center gap-3 text-sm">
        <i data-lucide="${icons[type]}" style="width:18px;height:18px;flex-shrink:0"></i>
        <span class="flex-1">${message}</span>
        <button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100">
          <i data-lucide="x" style="width:14px;height:14px"></i>
        </button>
      </div>
    `;

        lucide.createIcons();

        notifTimeout = setTimeout(() => {
            area.innerHTML = '';
        }, 5000);
    }

    function resetOrder() {
        orderItems = clothesData.map(c => ({...c, qty: 1}));
        renderOrderItems();
        goToStep(1);
        showNotification('🛍️ Fresh bag ready — happy shopping!', 'info');
    }

    document.addEventListener('change', function(e) {
        if (e.target.name === 'payment') {
            document.getElementById('card-details').style.display =
                e.target.value === 'card' ? 'block' : 'none';
        }
    });

    renderOrderItems();
    lucide.createIcons();
</script>

</body>
</html>