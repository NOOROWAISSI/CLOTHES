<?php
global $conn;
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = trim($_POST['first_name'] ?? "");
    $last_name = trim($_POST['last_name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");

    $full_name = $first_name . " " . $last_name;

    $check_sql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "This email already exists";
    } else {
        $insert_sql = "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sss", $full_name, $email, $password);

        if ($insert_stmt->execute()) {
            $_SESSION['user_id'] = $insert_stmt->insert_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'user';

            header("Location: index.php");
            exit;
        } else {
            $error = "Something went wrong";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Luxury Sign Up</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@300;400;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --black: #000;
            --white: #fff;
            --bg: #f7f7f5;
            --line: #dddddd;
            --text: #1a1a1a;
            --muted: #7b7b7b;
            --error: #c96a6a;
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            --radius: 16px;
            --header-h: 74px;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* HEADER */
        .header {
            height: var(--header-h);
            background: #000;
            color: #fff;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            padding: 0 28px;
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .menu-icon {
            font-size: 20px;
            cursor: pointer;
            transition: 0.3s ease;
        }

        .menu-icon:hover {
            opacity: 0.7;
        }

        .logo {
            justify-self: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            letter-spacing: 9px;
            font-weight: 500;
        }

        .header-icons {
            justify-self: end;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .header-icons a {
            color: #fff;
            font-size: 1.1rem;
            transition: transform 0.25s ease, opacity 0.25s ease;
        }

        .header-icons a:hover {
            transform: translateY(-2px);
            opacity: 0.75;
        }

        .bag-icon {
            position: relative;
        }

        .bag-count {
            position: absolute;
            top: -7px;
            right: -8px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #fff;
            color: #000;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* MAIN */
        .signup-page {
            height: calc(100vh - var(--header-h));
            display: grid;
            grid-template-columns: 1.02fr 1fr;
        }

        /* LEFT PANEL */
        .left-panel {
            position: relative;
            height: 100%;
            overflow: hidden;
            background: #ece8e3;
        }

        .left-panel img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            filter: grayscale(100%);
        }

        .vertical-text {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%) rotate(180deg);
            writing-mode: vertical-rl;
            font-size: 0.78rem;
            letter-spacing: 6px;
            color: rgba(0, 0, 0, 0.75);
            font-weight: 400;
        }

        .vertical-line {
            position: absolute;
            left: 52px;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 90px;
            background: rgba(0, 0, 0, 0.3);
        }

        /* RIGHT PANEL */
        .right-panel {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 34px;
            background: #fbfbfa;
            overflow: hidden;
        }

        .form-wrap {
            width: 100%;
            max-width: 470px;
        }

        .form-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.6rem;
            letter-spacing: 3px;
            font-weight: 500;
            line-height: 1;
            margin-bottom: 8px;
        }

        .form-subtitle {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 18px;
            max-width: 390px;
        }

        .form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* INPUTS */
        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            height: 54px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: #fff;
            padding: 20px 42px 8px 46px;
            font-size: 0.93rem;
            color: var(--text);
            outline: none;
            transition: 0.28s ease;
            box-shadow: 0 0 0 rgba(0,0,0,0);
        }

        .input-group input:focus {
            border-color: #000;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }

        .input-group label {
            position: absolute;
            top: 9px;
            left: 46px;
            font-size: 0.64rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #111;
            font-weight: 600;
            pointer-events: none;
        }

        .input-icon {
            position: absolute;
            left: 17px;
            top: 50%;
            transform: translateY(-50%);
            color: #555;
            font-size: 0.92rem;
        }

        .eye-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: pointer;
            transition: 0.3s ease;
            font-size: 0.95rem;
        }

        .eye-icon:hover {
            color: #000;
        }

        .error-text {
            font-size: 0.82rem;
            color: var(--error);
            margin: -2px 0 0 6px;
        }

        /* CHECKBOX */
        .checkbox-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 2px;
            line-height: 1.5;
            color: #444;
            font-size: 0.85rem;
        }

        .checkbox-row input {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 1.4px solid #bbb;
            border-radius: 5px;
            margin-top: 1px;
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
            background: #fff;
            transition: 0.25s ease;
        }

        .checkbox-row input:checked {
            background: #000;
            border-color: #000;
        }

        .checkbox-row input:checked::after {
            content: "✓";
            position: absolute;
            color: #fff;
            font-size: 11px;
            left: 4px;
            top: -1px;
        }

        .checkbox-row a {
            text-decoration: underline;
            text-underline-offset: 3px;
            transition: 0.25s ease;
        }

        .checkbox-row a:hover {
            opacity: 0.7;
        }

        /* BUTTONS */
        .signup-btn {
            height: 52px;
            border: none;
            border-radius: 14px;
            background: #000;
            color: #fff;
            font-size: 0.9rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s ease;
            margin-top: 2px;
            box-shadow: 0 14px 26px rgba(0, 0, 0, 0.12);
        }

        .signup-btn:hover {
            transform: translateY(-2px);
            background: #111;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 2px 0;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #d8d8d8;
        }

        .divider span {
            font-size: 0.66rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #555;
            white-space: nowrap;
        }

        .social-btn {
            width: 100%;
            height: 48px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 0.82rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.3s ease;
            box-shadow: var(--shadow);
        }

        .social-btn:hover {
            border-color: #000;
            transform: translateY(-2px);
        }

        .social-btn.google i {
            color: #db4437;
            font-size: 1.05rem;
        }

        .social-btn.apple i {
            color: #000;
            font-size: 1.1rem;
        }

        .signin-text {
            text-align: center;
            margin-top: 4px;
            font-size: 0.9rem;
            color: #444;
        }

        .signin-text a {
            text-decoration: underline;
            text-underline-offset: 4px;
            transition: 0.25s ease;
        }

        .signin-text a:hover {
            opacity: 0.75;
        }

        /* Laptop fit */
        @media (max-height: 800px) {
            .form-title {
                font-size: 2.3rem;
            }

            .form-subtitle {
                margin-bottom: 14px;
                font-size: 0.9rem;
            }

            .form {
                gap: 8px;
            }

            .input-group input {
                height: 50px;
            }

            .signup-btn {
                height: 48px;
            }

            .social-btn {
                height: 44px;
            }

            .vertical-text,
            .vertical-line {
                display: none;
            }
        }

        /* Mobile */
        @media (max-width: 900px) {
            html, body {
                overflow: auto;
            }

            .signup-page {
                height: auto;
                min-height: calc(100vh - var(--header-h));
                grid-template-columns: 1fr;
            }

            .left-panel {
                min-height: 280px;
            }

            .right-panel {
                padding: 28px 20px 34px;
            }

            .form-wrap {
                max-width: 100%;
            }

            .vertical-text,
            .vertical-line {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .header {
                padding: 0 16px;
            }

            .logo {
                font-size: 1.45rem;
                letter-spacing: 5px;
            }

            .header-icons {
                gap: 12px;
            }

            .form-title {
                font-size: 2rem;
            }

            .form-subtitle {
                font-size: 0.88rem;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-left">
        <i class="fa-solid fa-bars menu-icon"></i>
    </div>

    <div class="logo">DEMOISELLE</div>

    <div class="header-icons">
        <a href="#"><i class="fa-solid fa-magnifying-glass"></i></a>
        <a href="#"><i class="fa-regular fa-user"></i></a>
        <a href="#" class="bag-icon">
            <i class="fa-solid fa-bag-shopping"></i>
            <span class="bag-count">0</span>
        </a>
    </div>
</header>

<main class="signup-page">
    <section class="left-panel">
        <!-- حطي الصورة هون -->
        <img src="pic/signup4.jpg" alt="Luxury Fashion Model">

        <div class="vertical-text">TIMELESS ELEGANCE &nbsp; NEW COLLECTION</div>
        <div class="vertical-line"></div>
    </section>

    <section class="right-panel">
        <div class="form-wrap">
            <h1 class="form-title">CREATE ACCOUNT</h1>
            <p class="form-subtitle">
                Join Maryam and discover a world of timeless fashion.
            </p>

            <form class="form">
                <div class="input-group">
                    <i class="fa-regular fa-user input-icon"></i>
                    <label>Full Name</label>
                    <input type="text" placeholder="Enter your full name">
                </div>

                <div class="input-group">
                    <i class="fa-regular fa-envelope input-icon"></i>
                    <label>Email Address</label>
                    <input type="email" placeholder="Enter your email">
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <label>Password</label>
                    <input type="password" placeholder="Enter your password">
                    <i class="fa-regular fa-eye eye-icon"></i>
                </div>

                <p class="error-text">Password must be at least 8 characters.</p>

                <div class="input-group">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <label>Confirm Password</label>
                    <input type="password" placeholder="Confirm your password">
                    <i class="fa-regular fa-eye eye-icon"></i>
                </div>

                <label class="checkbox-row">
                    <input type="checkbox">
                    <span>
              I agree to the <a href="#">Terms & Conditions</a> and
              <a href="#">Privacy Policy</a>.
            </span>
                </label>

                <button type="submit" class="signup-btn">Sign Up</button>

                <div class="divider">
                    <span>Or sign up with</span>
                </div>

                <button type="button" class="social-btn google">
                    <i class="fa-brands fa-google"></i>
                    <span>Continue with Google</span>
                </button>

                <button type="button" class="social-btn apple">
                    <i class="fa-brands fa-apple"></i>
                    <span>Continue with Apple</span>
                </button>

                <p class="signin-text">
                    Already have an account? <a href="#">Sign in</a>
                </p>
            </form>
        </div>
    </section>
</main>

</body>
</html>