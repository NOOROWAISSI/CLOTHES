<?php
include "db.php";

if (!isset($_SESSION['user_id'])) {
    // مؤقتًا للتجربة فقط، لأنه عندك user_id = 1 موجود بالـ SQL
    $_SESSION['user_id'] = 1;
}

$user_id = $_SESSION['user_id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $city = trim($_POST['city']);
    $area = trim($_POST['area']);
    $street = trim($_POST['street']);
    $building = trim($_POST['building']);

    $sql = "UPDATE users 
            SET full_name=?, email=?, phone=?, city=?, area=?, street=?, building=?
            WHERE user_id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $full_name, $email, $phone, $city, $area, $street, $building, $user_id);

    if ($stmt->execute()) {
        $message = "Profile updated successfully";
    } else {
        $message = "Error updating profile";
    }
}

$user_sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$summary_sql = "SELECT COUNT(*) AS orders_count, COALESCE(SUM(total_price), 0) AS total_spent
                FROM orders
                WHERE user_id = ?";
$stmt = $conn->prepare($summary_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

$orders_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_id DESC";
$stmt = $conn->prepare($orders_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
?>

<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demoiselle — My Profile</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Outfit:wght@200;300;400;500;600&display=swap" rel="stylesheet">

    <style>
        html, body { height: 100%; margin: 0; }
        * { box-sizing: border-box; }
        .font-serif { font-family: 'Cormorant Garamond', serif; }
        .font-sans { font-family: 'Outfit', sans-serif; }
        .profile-bg { background: linear-gradient(135deg, #f8f8f8 0%, #f2f2f2 100%); }
        .card-soft {
            background: rgba(255,255,255,0.96);
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 18px 40px rgba(0,0,0,0.06);
            border-radius: 24px;
        }
        .input-clean {
            width: 100%;
            border: 1px solid #e5e5e5;
            background: #fff;
            border-radius: 16px;
            padding: 14px 16px;
            font-size: 15px;
            color: #111;
            outline: none;
            transition: all 0.3s ease;
        }
        .input-clean:focus {
            border-color: #000;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
        }
        .label-mini {
            display: block;
            font-size: 11px;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #777;
            margin-bottom: 8px;
        }
        .btn-dark {
            background: #000;
            color: #fff;
            border: 1px solid #000;
            border-radius: 16px;
            padding: 14px 18px;
            font-size: 12px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }
        .btn-dark:hover { background: #fff; color: #000; }
        .btn-light {
            background: #fff;
            color: #000;
            border: 1px solid #ddd;
            border-radius: 16px;
            padding: 14px 18px;
            font-size: 12px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }
        .btn-light:hover { border-color: #000; }
        .order-card {
            border: 1px solid #eee;
            border-radius: 20px;
            background: #fff;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0,0,0,0.05);
        }
    </style>
</head>

<body class="profile-bg font-sans text-black">

<div class="min-h-screen">

    <header class="sticky top-0 z-40 bg-black border-b border-white/10">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3">
                <img src="pic/lolo1.png" alt="Demoiselle" class="w-50 h-20 object-contain">
            </a>

            <div class="flex items-center gap-3">
                <a href="index.php" class="btn-light">Home</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <section class="lg:col-span-1">
                <div class="card-soft p-8">
                    <div class="flex flex-col items-center text-center mb-8">
                        <div class="w-24 h-24 rounded-full bg-black text-white flex items-center justify-center mb-4">
                            <i data-lucide="user" style="width:34px;height:34px"></i>
                        </div>

                        <h1 class="font-serif text-4xl font-light">
                            <?= htmlspecialchars($user['full_name'] ?? 'My Profile') ?>
                        </h1>

                        <p class="text-sm text-gray-500 mt-2">
                            <?= htmlspecialchars($user['email'] ?? 'your@email.com') ?>
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-2xl bg-[#fafafa] p-4 border border-black/5">
                            <p class="text-xs uppercase tracking-[0.25em] text-gray-400 mb-2">Account created</p>
                            <p class="text-sm text-black/70">
                                <?= htmlspecialchars($user['created_at'] ?? '—') ?>
                            </p>
                        </div>

                        <div class="rounded-2xl bg-[#fafafa] p-4 border border-black/5">
                            <p class="text-xs uppercase tracking-[0.25em] text-gray-400 mb-2">Total Orders</p>
                            <p class="text-2xl font-serif">
                                <?= htmlspecialchars($summary['orders_count'] ?? 0) ?>
                            </p>
                        </div>

                        <div class="rounded-2xl bg-[#fafafa] p-4 border border-black/5">
                            <p class="text-xs uppercase tracking-[0.25em] text-gray-400 mb-2">Total Spent</p>
                            <p class="text-2xl font-serif">
                                $<?= number_format($summary['total_spent'] ?? 0, 2) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="lg:col-span-2 space-y-8">

                <div class="card-soft p-8">
                    <div class="mb-8">
                        <p class="text-xs uppercase tracking-[0.25em] text-gray-400 mb-2">Personal Information</p>
                        <h2 class="font-serif text-4xl font-light">Edit Profile</h2>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="mb-5 bg-black text-white px-5 py-3 rounded-2xl text-sm">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">

                        <div>
                            <label class="label-mini">Full Name</label>
                            <input name="full_name" type="text" class="input-clean"
                                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                        </div>

                        <div>
                            <label class="label-mini">Email Address</label>
                            <input name="email" type="email" class="input-clean"
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>

                        <div>
                            <label class="label-mini">Phone Number</label>
                            <input name="phone" type="text" class="input-clean"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="label-mini">City</label>
                                <input name="city" type="text" class="input-clean"
                                       value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                            </div>

                            <div>
                                <label class="label-mini">Area</label>
                                <input name="area" type="text" class="input-clean"
                                       value="<?= htmlspecialchars($user['area'] ?? '') ?>">
                            </div>
                        </div>

                        <div>
                            <label class="label-mini">Street</label>
                            <input name="street" type="text" class="input-clean"
                                   value="<?= htmlspecialchars($user['street'] ?? '') ?>">
                        </div>

                        <div>
                            <label class="label-mini">Building</label>
                            <input name="building" type="text" class="input-clean"
                                   value="<?= htmlspecialchars($user['building'] ?? '') ?>">
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button type="submit" class="btn-dark">Save Changes</button>
                            <a href="profile.php" class="btn-light text-center">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card-soft p-8">
                    <div class="mb-8">
                        <p class="text-xs uppercase tracking-[0.25em] text-gray-400 mb-2">Purchase History</p>
                        <h2 class="font-serif text-4xl font-light">My Orders</h2>
                    </div>

                    <div class="space-y-4">

                        <?php if ($orders_result->num_rows === 0): ?>
                            <div class="text-center py-14 border border-dashed border-black/10 rounded-3xl bg-[#fcfcfc]">
                                <i data-lucide="shopping-bag" style="width:36px;height:36px" class="mx-auto text-gray-300"></i>
                                <p class="mt-4 text-lg font-serif">No orders yet</p>
                                <p class="mt-2 text-sm text-gray-500">Your completed purchases will appear here.</p>
                            </div>
                        <?php else: ?>

                            <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <div class="order-card p-5">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.22em] text-gray-400 mb-2">Order Number</p>
                                            <p class="text-base font-medium">
                                                #<?= htmlspecialchars($order['order_id']) ?>
                                            </p>
                                        </div>

                                        <div class="text-left md:text-right">
                                            <p class="text-xs uppercase tracking-[0.22em] text-gray-400 mb-2">Status</p>
                                            <p class="text-sm text-black/70">
                                                <?= htmlspecialchars($order['order_status']) ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <i data-lucide="truck" style="width:16px;height:16px"></i>
                                            <span>Delivery: $<?= number_format($order['delivery_price'] ?? 0, 2) ?></span>
                                        </div>

                                        <div class="text-lg font-serif">
                                            Total: $<?= number_format($order['total_price'] ?? 0, 2) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>

                        <?php endif; ?>

                    </div>
                </div>

            </section>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();
</script>

</body>
</html>