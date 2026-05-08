<?php
global $conn;
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");

    $admin_sql = "SELECT * FROM admin WHERE email = ? LIMIT 1";
    $stmt_admin = $conn->prepare($admin_sql);
    $stmt_admin->bind_param("s", $email);
    $stmt_admin->execute();
    $admin_result = $stmt_admin->get_result();

    if ($admin_result->num_rows === 1) {
        $admin = $admin_result->fetch_assoc();

        if ($password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['full_name'] = $admin['full_name'];
            $_SESSION['role'] = 'admin';

            header("Location: admin.php");
            exit;
        }
    }

    $user_sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $stmt_user = $conn->prepare($user_sql);
    $stmt_user->bind_param("s", $email);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();

    if ($user_result->num_rows === 1) {
        $user = $user_result->fetch_assoc();

        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'user';

            header("Location: index.php");
            exit;
        }
    }

    $error = "Wrong email or password";
}
?>

<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demoiselle Sign In</title>

    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Outfit:wght@200;300;400;500&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }

        .fashion-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: 100%;
            width: 100%;
        }

        @media (max-width: 768px) {
            .fashion-grid { grid-template-columns: 1fr; }
            .visual-side { display: none; }
        }

        .visual-side {
            position: relative;
            overflow: hidden;
            background: #000;
        }

        .geo-line {
            position: absolute;
            background: rgba(255,255,255,0.06);
        }

        .diagonal-stripe {
            position: absolute;
            width: 200%;
            height: 1px;
            background: rgba(255,255,255,0.1);
            transform-origin: 0 0;
        }

        .silhouette-container {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .silhouette {
            width: 280px;
            height: 420px;
            position: relative;
            opacity: 0;
            animation: silhouetteFadeIn 1.8s ease forwards 0.5s;
        }

        @keyframes silhouetteFadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .silhouette-body {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 320px;
            background: linear-gradient(180deg, #fff 0%, #e0e0e0 100%);
            clip-path: polygon(30% 0%, 70% 0%, 85% 25%, 80% 60%, 90% 100%, 10% 100%, 20% 60%, 15% 25%);
        }

        .silhouette-head {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 75px;
            background: #fff;
            border-radius: 50% 50% 45% 45%;
        }

        .silhouette-hat {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 30px;
            background: #fff;
            border-radius: 50%;
        }

        .silhouette-hat::after {
            content: '';
            position: absolute;
            top: -18px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 30px;
            background: #fff;
            border-radius: 20px 20px 0 0;
        }

        .editorial-text {
            position: absolute;
            bottom: 60px;
            left: 40px;
            right: 40px;
            z-index: 10;
            opacity: 0;
            animation: slideUp 1s ease forwards 1.2s;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .editorial-year {
            font-family: 'Outfit', sans-serif;
            font-weight: 200;
            font-size: 11px;
            letter-spacing: 6px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
        }

        .editorial-headline {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-style: italic;
            font-size: 42px;
            color: #fff;
            line-height: 1.1;
            margin-top: 8px;
        }

        .form-side {
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
            overflow-y: auto;
        }

        .form-wrapper {
            width: 100%;
            max-width: 380px;
            opacity: 0;
            animation: formFadeIn 1s ease forwards 0.3s;
        }

        @keyframes formFadeIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .brand-logo {
            font-family: 'Outfit', sans-serif;
            font-weight: 200;
            font-size: 13px;
            letter-spacing: 8px;
            text-transform: uppercase;
            color: #000;
            margin-bottom: 60px;
        }

        .welcome-text {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 38px;
            color: #000;
            line-height: 1.15;
            margin-bottom: 6px;
        }

        .welcome-sub {
            font-family: 'Outfit', sans-serif;
            font-weight: 200;
            font-size: 13px;
            color: #999;
            letter-spacing: 1px;
            margin-bottom: 30px;
        }

        .fashion-input-group {
            position: relative;
            margin-bottom: 28px;
        }

        .fashion-label {
            display: block;
            font-family: 'Outfit', sans-serif;
            font-weight: 300;
            font-size: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #aaa;
            margin-bottom: 8px;
        }

        .fashion-input {
            width: 100%;
            padding: 14px 0;
            border: none;
            border-bottom: 1px solid #e0e0e0;
            background: transparent;
            font-family: 'Outfit', sans-serif;
            font-weight: 300;
            font-size: 15px;
            color: #000;
            outline: none;
        }

        .fashion-input:focus {
            border-bottom-color: #000;
        }

        .input-line {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #000;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s;
        }

        .fashion-input:focus ~ .input-line {
            transform: scaleX(1);
        }

        .fashion-btn {
            width: 100%;
            padding: 18px;
            margin-top: 16px;
            background: #000;
            color: #fff;
            border: 1px solid #000;
            font-family: 'Outfit', sans-serif;
            font-weight: 300;
            font-size: 11px;
            letter-spacing: 4px;
            text-transform: uppercase;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .fashion-btn span {
            position: relative;
            z-index: 1;
        }

        .fashion-link {
            font-family: 'Outfit', sans-serif;
            font-weight: 300;
            font-size: 11px;
            color: #aaa;
            text-decoration: none;
            letter-spacing: 1px;
        }

        .fashion-link:hover {
            color: #000;
        }

        .corner-mark {
            position: absolute;
            width: 30px;
            height: 30px;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .corner-tl { top: 30px; left: 30px; border-right: none; border-bottom: none; }
        .corner-tr { top: 30px; right: 30px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: 30px; left: 30px; border-right: none; border-top: none; }
        .corner-br { bottom: 30px; right: 30px; border-left: none; border-top: none; }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            animation: float linear infinite;
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-400px) rotate(360deg); opacity: 0; }
        }

        .error-msg {
            color: #b91c1c;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="h-full" style="width:100%;">

<div class="fashion-grid">

    <div class="visual-side">
        <div class="corner-mark corner-tl"></div>
        <div class="corner-mark corner-tr"></div>
        <div class="corner-mark corner-bl"></div>
        <div class="corner-mark corner-br"></div>

        <div class="geo-line" style="top:0;left:33.3%;width:1px;height:100%;"></div>
        <div class="geo-line" style="top:0;left:66.6%;width:1px;height:100%;"></div>
        <div class="diagonal-stripe" style="top:40%;left:0;transform:rotate(-25deg);"></div>
        <div class="diagonal-stripe" style="top:70%;left:0;transform:rotate(-25deg);"></div>

        <div id="particles"></div>

        <div class="silhouette-container">
            <div class="silhouette">
                <div class="silhouette-hat"></div>
                <div class="silhouette-head"></div>
                <div class="silhouette-body"></div>
            </div>
        </div>

        <div class="editorial-text">
            <div class="editorial-year">Autumn / Winter 2025</div>
            <div class="editorial-headline">
                The Art<br>
                of Being.
            </div>
        </div>
    </div>

    <div class="form-side">
        <div class="form-wrapper">

            <div class="brand-logo">DEMOISELLE</div>

            <h1 class="welcome-text">Welcome Back</h1>
            <p class="welcome-sub">Where elegance meets edge.</p>

            <?php if ($error): ?>
                <p class="error-msg"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form id="fashionForm" method="POST" action="">

                <div class="fashion-input-group">
                    <label class="fashion-label" for="email">Email Address</label>
                    <input
                        class="fashion-input"
                        type="email"
                        name="email"
                        id="email"
                        placeholder="you@example.com"
                        required
                    >
                    <div class="input-line"></div>
                </div>

                <div class="fashion-input-group">
                    <label class="fashion-label" for="password">Password</label>
                    <input
                        class="fashion-input"
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Your password"
                        required
                    >
                    <div class="input-line"></div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">

                    <a href="forget.php" class="fashion-link">
                        Forgot Password?
                    </a>

                    <a href="signup.php" class="fashion-link">
                        Create account
                    </a>

                </div>

                <button type="submit" class="fashion-btn">
                    <span>Enter the Collection</span>
                </button>

            </form>

            <div style="margin-top:48px;display:flex;gap:20px;justify-content:center;">
                <a href="terms.php" class="fashion-link" style="font-size:10px;letter-spacing:2px;">Terms</a>
                <span style="color:#e0e0e0;font-size:10px;">|</span>
                <a href="privacy.php" class="fashion-link" style="font-size:10px;letter-spacing:2px;">Privacy</a>
                <span style="color:#e0e0e0;font-size:10px;">|</span>
                <a href="index.php" class="fashion-link" style="font-size:10px;letter-spacing:2px;">Home</a>
            </div>

        </div>
    </div>

</div>

<script>
    const particleContainer = document.getElementById('particles');

    for (let i = 0; i < 15; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.bottom = '-10px';
        p.style.animationDuration = (6 + Math.random() * 8) + 's';
        p.style.animationDelay = (Math.random() * 10) + 's';
        p.style.width = p.style.height = (1 + Math.random() * 2) + 'px';
        particleContainer.appendChild(p);
    }
</script>

</body>
</html>