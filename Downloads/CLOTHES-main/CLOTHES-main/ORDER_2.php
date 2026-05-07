<?php
global $conn;
include "db.php";

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['full_name'] ?? null;

if (!$isLoggedIn) {
    header("Location: signin.php");
    exit;
}


$user_id = (int)$_SESSION['user_id'];

$sql = "
SELECT
    c.cart_id,
    c.quantity,
    p.product_id,
    p.price,
    COALESCE(pt.product_name, CONCAT('Product #', p.product_id)) AS product_name,
    COALESCE(pi.image_url, pv.variant_image_url, 'pic/default.jpg') AS image_url,
    pv.size,
    pv.color
FROM cart c
JOIN product_variants pv ON c.variant_id = pv.variant_id
JOIN products p ON pv.product_id = p.product_id
LEFT JOIN product_translations pt
    ON p.product_id = pt.product_id AND pt.language_code='en'
LEFT JOIN product_images pi
    ON p.product_id = pi.product_id AND pi.is_main=1
WHERE c.user_id=?
ORDER BY c.cart_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$items = [];

while($row = $res->fetch_assoc()){
    $items[] = [
            "id" => (int)$row['cart_id'],
            "product_id" => (int)$row['product_id'],
            "name" => $row['product_name'],
            "image" => $row['image_url'] ?: "pic/default.jpg",
            "price" => (float)$row['price'],
            "size" => $row['size'],
            "color" => $row['color'],
            "qty" => (int)$row['quantity']
    ];
}
?>
<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demoiselle — Order</title>

    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        *{box-sizing:border-box}
        html,body{height:100%;margin:0}
        body{font-family:'DM Sans',sans-serif}
        .font-display{font-family:'Playfair Display',serif}

        @keyframes slideIn{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        @keyframes scaleIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
        @keyframes marquee{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
        @keyframes checkPop{0%{transform:scale(0) rotate(-45deg)}60%{transform:scale(1.2) rotate(0)}100%{transform:scale(1) rotate(0)}}

        .anim-slide{animation:slideIn .6s ease forwards}
        .anim-scale{animation:scaleIn .5s ease forwards}
        .anim-check{animation:checkPop .5s ease forwards}

        .step-hidden{display:none}
        .step-active{display:flex}

        .marquee-track{display:flex;animation:marquee 20s linear infinite}
        .marquee-track:hover{animation-play-state:paused}

        .cloth-card{transition:all .4s cubic-bezier(.25,.8,.25,1)}
        .cloth-card:hover{transform:translateY(-6px)}

        .btn-main{transition:all .3s ease;position:relative;overflow:hidden}
        .btn-main:hover{opacity:.9}

        .radio-custom:checked + label{border-color:#000;background:#000;color:#fff}
        .radio-custom:checked + label .radio-dot{background:#fff}

        .notification-bar{animation:slideIn .4s ease forwards}
        .progress-line{transition:width .5s ease}

        input:focus,select:focus{outline:none;border-color:#000}

        .item-remove{transition:all .3s ease}
        .item-remove:hover{background:#000;color:#fff}

        ::-webkit-scrollbar{width:4px}
        ::-webkit-scrollbar-track{background:#f5f5f5}
        ::-webkit-scrollbar-thumb{background:#ccc;border-radius:2px}
    </style>
</head>

<body class="h-full bg-white text-black">

<div id="app" class="h-full w-full flex flex-col overflow-auto">

    <header class="w-full border-b border-black/10 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-3">
            <a href="index.php">
                <img src="pic/lolo1.png" alt="Demoiselle" style="height:42px;width:auto;">
            </a>
            <span class="font-display text-xl font-bold tracking-tight">DEMOISELLE</span>
        </div>

        <div class="flex items-center gap-2 text-xs text-black/50">
            <i data-lucide="lock" style="width:14px;height:14px"></i>
            <span>Secure Checkout</span>
        </div>
    </header>

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

    <div id="step-1" class="step-active flex-1 flex-col px-6 pb-6">
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
            <p class="text-sm text-black/50 mb-5 anim-slide">Review your selected pieces</p>

            <div id="order-items" class="space-y-4"></div>

            <div class="mt-6 border-t border-black/10 pt-4 space-y-2 anim-slide">
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
            <button onclick="location.href='shope.php'" class="flex-1 border border-black/20 text-black py-3 text-sm font-medium tracking-wider uppercase btn-main hover:bg-black/5 rounded">
                Continue Shopping
            </button>

            <button onclick="goToStep(2)" class="flex-1 bg-black text-white py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">
                Proceed
            </button>
        </div>
    </div>

    <div id="step-2" class="step-hidden flex-1 flex-col px-6 pb-6">
        <div class="flex-1 overflow-auto">
            <h2 class="font-display text-2xl font-bold mb-1 anim-slide">Payment Method</h2>
            <p class="text-sm text-black/50 mb-6 anim-slide">Choose how you'd like to pay</p>

            <div class="space-y-3">

                <div>
                    <input type="radio" name="payment" id="pay-card" value="card" class="radio-custom sr-only" checked>
                    <label for="pay-card" class="flex items-center gap-4 border-2 border-black/10 rounded-lg p-4 cursor-pointer transition-all">
                        <div class="w-10 h-10 rounded-full border-2 border-black/20 flex items-center justify-center">
                            <div class="radio-dot w-4 h-4 rounded-full transition-all"></div>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm">Credit / Debit Card</div>
                            <div class="text-xs text-black/40 mt-0.5">Visa, Mastercard</div>
                        </div>
                        <i data-lucide="credit-card"></i>
                    </label>
                </div>

                <div>
                    <input type="radio" name="payment" id="pay-cod" value="cod" class="radio-custom sr-only">
                    <label for="pay-cod" class="flex items-center gap-4 border-2 border-black/10 rounded-lg p-4 cursor-pointer transition-all">
                        <div class="w-10 h-10 rounded-full border-2 border-black/20 flex items-center justify-center">
                            <div class="radio-dot w-4 h-4 rounded-full transition-all"></div>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm">Cash on Delivery</div>
                            <div class="text-xs text-black/40 mt-0.5">Pay when your order arrives</div>
                        </div>
                        <i data-lucide="banknote"></i>
                    </label>
                </div>

                <div>
                    <input type="radio" name="payment" id="pay-wallet" value="wallet" class="radio-custom sr-only">
                    <label for="pay-wallet" class="flex items-center gap-4 border-2 border-black/10 rounded-lg p-4 cursor-pointer transition-all">
                        <div class="w-10 h-10 rounded-full border-2 border-black/20 flex items-center justify-center">
                            <div class="radio-dot w-4 h-4 rounded-full transition-all"></div>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm">Digital Wallet</div>
                            <div class="text-xs text-black/40 mt-0.5">Apple Pay, Google Pay</div>
                        </div>
                        <i data-lucide="wallet"></i>
                    </label>
                </div>

            </div>

            <div id="card-details" class="mt-5 p-4 border border-black/10 rounded-lg bg-black/[0.02]">
                <p class="text-xs text-black/40 mb-3">Demo — no real data collected</p>

                <div class="space-y-3">
                    <input id="card-name" type="text" placeholder="Cardholder Name" class="w-full border border-black/15 rounded px-3 py-2.5 text-sm bg-white">
                    <input id="card-num" type="text" placeholder="Card Number" maxlength="19" class="w-full border border-black/15 rounded px-3 py-2.5 text-sm bg-white">
                    <div class="flex gap-3">
                        <input id="card-exp" type="text" placeholder="MM/YY" maxlength="5" class="w-full border border-black/15 rounded px-3 py-2.5 text-sm bg-white">
                        <input id="card-cvv" type="text" placeholder="CVV" maxlength="3" class="w-full border border-black/15 rounded px-3 py-2.5 text-sm bg-white">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-4 flex-shrink-0">
            <button onclick="goToStep(1)" class="flex-1 border border-black/20 py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">Back</button>
            <button onclick="goToStep(3)" class="flex-1 bg-black text-white py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">Continue</button>
        </div>
    </div>

    <div id="step-3" class="step-hidden flex-1 flex-col px-6 pb-6">
        <div class="flex-1 overflow-auto">
            <h2 class="font-display text-2xl font-bold mb-1 anim-slide">Delivery & Policy</h2>
            <p class="text-sm text-black/50 mb-6 anim-slide">Important information before you confirm</p>

            <div class="border border-black/10 rounded-lg overflow-hidden mb-4">
                <div class="bg-black text-white px-4 py-3 flex items-center gap-2">
                    <i data-lucide="truck"></i>
                    <span class="text-sm font-medium tracking-wider uppercase">Delivery Pricing</span>
                </div>

                <div class="p-4 space-y-3">
                    <div class="flex justify-between border-b border-black/5 pb-3">
                        <div>
                            <div class="text-sm font-medium">Standard Delivery</div>
                            <div class="text-xs text-black/40">5–7 business days</div>
                        </div>
                        <span class="text-sm font-bold">$4.99</span>
                    </div>

                    <div class="flex justify-between border-b border-black/5 pb-3">
                        <div>
                            <div class="text-sm font-medium">Express Delivery</div>
                            <div class="text-xs text-black/40">2–3 business days</div>
                        </div>
                        <span class="text-sm font-bold">$9.99</span>
                    </div>

                    <div class="flex justify-between">
                        <div>
                            <div class="text-sm font-medium">Free Shipping</div>
                            <div class="text-xs text-black/40">Orders over $100</div>
                        </div>
                        <span class="text-sm font-bold text-black/40">$0.00</span>
                    </div>
                </div>
            </div>

            <div class="border border-black/10 rounded-lg overflow-hidden mb-4">
                <div class="bg-black text-white px-4 py-3 flex items-center gap-2">
                    <i data-lucide="refresh-cw"></i>
                    <span class="text-sm font-medium tracking-wider uppercase">Exchange & Return Policy</span>
                </div>

                <div class="p-4 space-y-3 text-sm text-black/70 leading-relaxed">
                    <p>✦ <strong>14-day return window</strong> from delivery.</p>
                    <p>✦ <strong>Free exchanges</strong> for size or color swap.</p>
                    <p>✦ <strong>Refunds</strong> processed within 5 business days.</p>
                    <p>✦ <strong>Sale items</strong> exchange only.</p>
                </div>
            </div>

            <p class="text-xs font-medium tracking-wider uppercase text-black/60 mb-2">Select Delivery Speed</p>

            <div class="space-y-2">
                <label class="flex items-center gap-3 border border-black/10 rounded-lg p-3 cursor-pointer">
                    <input type="radio" name="delivery" value="standard" checked class="accent-black w-4 h-4">
                    <span class="text-sm">Standard — $4.99</span>
                </label>

                <label class="flex items-center gap-3 border border-black/10 rounded-lg p-3 cursor-pointer">
                    <input type="radio" name="delivery" value="express" class="accent-black w-4 h-4">
                    <span class="text-sm">Express — $9.99</span>
                </label>
            </div>
        </div>

        <div class="flex gap-3 mt-4 flex-shrink-0">
            <button onclick="goToStep(2)" class="flex-1 border border-black/20 py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">Back</button>
            <button onclick="confirmOrder()" class="flex-1 bg-black text-white py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">Confirm Order</button>
        </div>
    </div>

    <div id="step-4" class="step-hidden flex-1 flex-col items-center justify-center px-6 pb-6 text-center">
        <div class="anim-scale">
            <div class="w-20 h-20 rounded-full bg-black flex items-center justify-center mx-auto mb-6">
                <svg class="anim-check" width="36" height="36" viewBox="0 0 36 36" fill="none">
                    <path d="M10 18l6 6 10-12" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <h2 class="font-display text-3xl font-bold mb-2">Order Confirmed</h2>
        <p class="text-sm text-black/50 mb-2">Thank you for shopping with us</p>
        <p class="text-xs text-black/30 mb-6" id="order-number">Order #</p>

        <div class="w-full max-w-xs space-y-3">
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
                <div class="text-sm" id="confirm-delivery"></div>
                <div class="text-sm mt-1" id="confirm-payment"></div>
            </div>
        </div>

        <div class="flex gap-3 mt-6 w-full max-w-xs">
            <button onclick="location.href='index.php'" class="flex-1 border border-black/20 py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">
                Home
            </button>

            <button onclick="location.href='newcolc.php'" class="flex-1 bg-black text-white py-3 text-sm font-medium tracking-wider uppercase btn-main rounded">
                Shop
            </button>
        </div>
    </div>

</div>

<script>
    const orderItemsFromPhp = <?= json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    let orderItems = orderItemsFromPhp;
    let currentStep = 1;

    function renderOrderItems(){
        const container = document.getElementById('order-items');
        container.innerHTML = '';

        if(orderItems.length === 0){
            container.innerHTML = `
            <div class="border border-black/10 rounded-lg p-8 text-center">
                <p class="text-sm text-black/50">Your bag is empty.</p>
                <button onclick="location.href='shope.php'" class="mt-4 bg-black text-white px-6 py-3 text-xs tracking-widest uppercase rounded">
                    Go Shopping
                </button>
            </div>
        `;
            updateTotals();
            return;
        }

        orderItems.forEach(item => {
            const div = document.createElement('div');
            div.className = 'cloth-card flex gap-4 border border-black/10 rounded-lg p-3';

            div.innerHTML = `
            <img src="${item.image}" alt="${item.name}" class="w-20 h-28 object-cover rounded" onerror="this.src='pic/default.jpg'">

            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-sm font-medium truncate">${item.name}</h3>
                        <p class="text-xs text-black/40 mt-0.5">Size: ${item.size || '-'}</p>
                        <p class="text-xs text-black/40">Color: ${item.color || '-'}</p>
                    </div>

                    <button onclick="removeItem(${item.id})" class="item-remove w-7 h-7 rounded-full border border-black/15 flex items-center justify-center">
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

    function updateTotals(){
        const subtotal = orderItems.reduce((sum,item)=>sum + item.price * item.qty, 0);
        const delivery = subtotal === 0 ? 0 : subtotal >= 100 ? 0 : 4.99;
        const total = subtotal + delivery;

        document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('delivery-cost').textContent = delivery === 0 ? 'FREE' : '$' + delivery.toFixed(2);
        document.getElementById('total').textContent = '$' + total.toFixed(2);
    }

    function removeItem(cartId){
        fetch('remove_cart_item.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'cart_id=' + encodeURIComponent(cartId)
        })
            .then(res=>res.json())
            .then(data=>{
                if(data.status === 'success'){
                    orderItems = orderItems.filter(i => parseInt(i.id) !== parseInt(cartId));
                    renderOrderItems();
                    showNotification('Item removed from bag', 'info');
                }
            });
    }

    function changeQty(cartId, delta){
        fetch('update_cart_qty.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'cart_id=' + encodeURIComponent(cartId) + '&delta=' + encodeURIComponent(delta)
        })
            .then(res=>res.json())
            .then(data=>{
                if(data.status === 'success'){
                    const item = orderItems.find(i => parseInt(i.id) === parseInt(cartId));
                    if(item){
                        item.qty = parseInt(data.qty);
                    }
                    renderOrderItems();
                }
            });
    }

    function goToStep(step){
        if(step === 2 && orderItems.length === 0){
            showNotification('Add at least one item first', 'warning');
            return;
        }

        document.getElementById('step-' + currentStep).className = 'step-hidden';
        document.getElementById('step-' + step).className = 'step-active flex-1 flex-col px-6 pb-6';

        if(step === 4){
            document.getElementById('step-' + step).className = 'step-active flex-1 flex-col items-center justify-center px-6 pb-6 text-center';
        }

        currentStep = step;

        const labels = ['Order Review','Payment','Delivery & Policy','Confirmed'];
        document.getElementById('step-label').textContent = labels[step - 1];
        document.getElementById('step-count').textContent = `Step ${step} of 4`;
        document.getElementById('progress-bar').style.width = (step * 25) + '%';

        if(step === 4){
            buildConfirmation();
        }

        lucide.createIcons();
    }

    function buildConfirmation(){
        const summary = document.getElementById('confirm-summary');
        summary.innerHTML = '';

        orderItems.forEach(item=>{
            const div = document.createElement('div');
            div.className = 'flex justify-between';
            div.innerHTML = `
            <span class="text-black/60">${item.name} ×${item.qty}</span>
            <span>$${(item.price * item.qty).toFixed(2)}</span>
        `;
            summary.appendChild(div);
        });

        const subtotal = orderItems.reduce((sum,item)=>sum + item.price * item.qty, 0);
        const deliveryRadio = document.querySelector('input[name="delivery"]:checked');
        const isExpress = deliveryRadio && deliveryRadio.value === 'express';
        const delivery = subtotal >= 100 ? 0 : (isExpress ? 9.99 : 4.99);
        const total = subtotal + delivery;

        document.getElementById('confirm-total').textContent = '$' + total.toFixed(2);
        document.getElementById('confirm-delivery').textContent = isExpress ? 'Express — 2-3 business days' : 'Standard — 5-7 business days';

        const pay = document.querySelector('input[name="payment"]:checked')?.value || 'card';
        const payNames = {
            card:'Credit / Debit Card',
            cod:'Cash on Delivery',
            wallet:'Digital Wallet'
        };

        document.getElementById('confirm-payment').textContent = 'Payment: ' + payNames[pay];
    }

    function confirmOrder(){
        if(orderItems.length === 0){
            showNotification('Your bag is empty. Add items first.', 'warning');
            return;
        }

        const pay = document.querySelector('input[name="payment"]:checked')?.value || '';

        if(pay === 'card'){
            const cardName = document.getElementById('card-name').value.trim();
            const cardNum = document.getElementById('card-num').value.trim();
            const cardExp = document.getElementById('card-exp').value.trim();
            const cardCvv = document.getElementById('card-cvv').value.trim();

            if(cardName === '' || cardNum === '' || cardExp === '' || cardCvv === ''){
                showNotification('Please fill all card details.', 'warning');
                return;
            }
        }

        const delivery = document.querySelector('input[name="delivery"]:checked')?.value || 'standard';

        const body = new URLSearchParams();
        body.append('payment', pay);
        body.append('delivery', delivery);

        fetch('confirm_order.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: body.toString()
        })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'login'){
                    location.href = 'signin.php';
                    return;
                }

                if(data.status === 'empty'){
                    showNotification('Your bag is empty. Add items first.', 'warning');
                    return;
                }

                if(data.status === 'success'){
                    document.getElementById('order-number').textContent = 'Order #' + data.order_number;
                    goToStep(4);
                    return;
                }

                showNotification(data.message || 'Order error', 'warning');
            })
            .catch(() => {
                showNotification('Connection error. Try again.', 'warning');
            });
    }

    function showNotification(message,type='info'){
        const area = document.getElementById('notification-area');

        let cls = 'bg-black/90 text-white';
        if(type === 'warning') cls = 'bg-white text-black border border-black';

        area.innerHTML = `
        <div class="notification-bar ${cls} rounded-lg px-4 py-3 mb-3 flex items-center gap-3 text-sm">
            <span class="flex-1">${message}</span>
            <button onclick="this.parentElement.remove()" class="opacity-70">×</button>
        </div>
    `;

        setTimeout(()=>{
            area.innerHTML = '';
        },3000);
    }

    document.querySelectorAll('input[name="payment"]').forEach(r=>{
        r.addEventListener('change',()=>{
            document.getElementById('card-details').style.display =
                document.getElementById('pay-card').checked ? 'block' : 'none';
        });
    });

    renderOrderItems();
    lucide.createIcons();
</script>

</body>
</html>