<?php
global $conn;
include_once "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST['full_name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");
    $confirm_password = trim($_POST['confirm_password'] ?? "");
    $agree = $_POST['agree'] ?? "";

    if (
            empty($full_name) ||
            empty($email) ||
            empty($password) ||
            empty($confirm_password)
    ) {

        $error = "Please fill all fields";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Invalid email address";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match";

    } elseif ($agree !== "yes") {

        $error = "Please agree to Terms & Privacy Policy";

    } else {

        $check = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = "This email already exists";

        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare("
                INSERT INTO users (full_name, email, password)
                VALUES (?, ?, ?)
            ");

            $insert->bind_param(
                    "sss",
                    $full_name,
                    $email,
                    $hashed_password
            );

            if ($insert->execute()) {

                $_SESSION['user_id'] = $insert->insert_id;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = "user";

                header("Location: index.php");
                exit;

            } else {

                $error = "Something went wrong";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <title>Demoiselle — Sign Up</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        :root{
            --black:#000;
            --white:#fff;
            --bg:#f6f6f4;
            --line:#ddd;
            --text:#111;
            --muted:#777;
            --radius:16px;
        }

        body{
            font-family:'Inter',sans-serif;
            background:var(--bg);
            color:var(--text);
            overflow-x:hidden;
        }

        a{
            text-decoration:none;
            color:inherit;
        }

        .page{
            min-height:100vh;
            display:grid;
            grid-template-columns:1fr 1fr;
        }

        .left{
            position:relative;
            overflow:hidden;
            background:#eae7e2;
        }

        .left img{
            width:100%;
            height:100%;
            object-fit:cover;
            filter:grayscale(100%);
        }

        .overlay-text{
            position:absolute;
            bottom:40px;
            left:40px;
            color:#fff;
        }

        .overlay-text h2{
            font-family:'Cormorant Garamond',serif;
            font-size:3rem;
            font-weight:500;
            letter-spacing:4px;
        }

        .overlay-text p{
            margin-top:10px;
            letter-spacing:3px;
            font-size:.8rem;
        }

        .right{
            display:flex;
            align-items:center;
            justify-content:center;
            padding:50px 30px;
            background:#fbfbfa;
        }

        .form-box{
            width:100%;
            max-width:470px;
        }

        .logo{
            font-family:'Cormorant Garamond',serif;
            font-size:2rem;
            letter-spacing:7px;
            margin-bottom:35px;
            text-align:center;
        }

        .title{
            font-family:'Cormorant Garamond',serif;
            font-size:2.6rem;
            margin-bottom:8px;
            letter-spacing:2px;
        }

        .subtitle{
            color:var(--muted);
            margin-bottom:28px;
            line-height:1.6;
            font-size:.95rem;
        }

        form{
            display:flex;
            flex-direction:column;
            gap:14px;
        }

        .input-group{
            position:relative;
        }

        .input-group input{
            width:100%;
            height:56px;
            border:1px solid var(--line);
            border-radius:var(--radius);
            background:#fff;
            padding:18px 48px 8px 48px;
            outline:none;
            font-size:.93rem;
            transition:.25s;
        }

        .input-group input:focus{
            border-color:#000;
        }

        .input-group label{
            position:absolute;
            top:8px;
            left:48px;
            font-size:.65rem;
            text-transform:uppercase;
            letter-spacing:2px;
            font-weight:600;
        }

        .input-icon{
            position:absolute;
            left:18px;
            top:50%;
            transform:translateY(-50%);
            color:#666;
        }

        .eye-icon{
            position:absolute;
            right:18px;
            top:50%;
            transform:translateY(-50%);
            cursor:pointer;
            color:#666;
        }

        .error{
            background:#fff0f0;
            color:#b44;
            border:1px solid #f1c6c6;
            padding:14px;
            border-radius:14px;
            font-size:.9rem;
        }

        .checkbox{
            display:flex;
            gap:10px;
            font-size:.85rem;
            color:#555;
            line-height:1.5;
            margin-top:2px;
        }

        .checkbox input{
            margin-top:3px;
        }

        .checkbox a{
            text-decoration:underline;
        }

        .signup-btn{
            height:54px;
            border:none;
            border-radius:14px;
            background:#000;
            color:#fff;
            cursor:pointer;
            font-size:.88rem;
            letter-spacing:4px;
            text-transform:uppercase;
            transition:.25s;
        }

        .signup-btn:hover{
            transform:translateY(-2px);
        }

        .divider{
            display:flex;
            align-items:center;
            gap:10px;
            margin:6px 0;
        }

        .divider::before,
        .divider::after{
            content:"";
            flex:1;
            height:1px;
            background:#ddd;
        }

        .divider span{
            font-size:.7rem;
            letter-spacing:2px;
            color:#666;
            text-transform:uppercase;
        }

        .social-btn{
            width:100%;
            height:50px;
            border:1px solid #ddd;
            background:#fff;
            border-radius:14px;
            cursor:pointer;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            transition:.25s;
            font-size:.82rem;
            letter-spacing:2px;
            text-transform:uppercase;
        }

        .social-btn:hover{
            border-color:#000;
            transform:translateY(-2px);
        }

        .signin-link{
            margin-top:6px;
            text-align:center;
            color:#555;
            font-size:.92rem;
        }

        .signin-link a{
            text-decoration:underline;
        }

        @media(max-width:950px){

            .page{
                grid-template-columns:1fr;
            }

            .left{
                height:320px;
            }

            .overlay-text h2{
                font-size:2.2rem;
            }
        }

        @media(max-width:600px){

            .right{
                padding:40px 18px;
            }

            .title{
                font-size:2rem;
            }

            .logo{
                font-size:1.6rem;
            }
        }

    </style>
</head>
<body>

<div class="page">

    <section class="left">

        <img src="pic/signup4.jpg" alt="Fashion">

        <div class="overlay-text">
            <h2>DEMOISELLE</h2>
            <p>TIMELESS ELEGANCE</p>
        </div>

    </section>

    <section class="right">

        <div class="form-box">

            <div class="logo">DEMOISELLE</div>

            <h1 class="title">Create Account</h1>

            <p class="subtitle">
                Join Demoiselle and discover timeless feminine fashion.
            </p>

            <?php if(!empty($error)): ?>
                <div class="error">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="input-group">
                    <i class="fa-regular fa-user input-icon"></i>

                    <label>Full Name</label>

                    <input
                            type="text"
                            name="full_name"
                            placeholder="Enter your full name"
                            required
                    >
                </div>

                <div class="input-group">
                    <i class="fa-regular fa-envelope input-icon"></i>

                    <label>Email Address</label>

                    <input
                            type="email"
                            name="email"
                            placeholder="Enter your email"
                            required
                    >
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock input-icon"></i>

                    <label>Password</label>

                    <input
                            type="password"
                            name="password"
                            class="password-input"
                            placeholder="Enter your password"
                            required
                    >

                    <i class="fa-regular fa-eye eye-icon"></i>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock input-icon"></i>

                    <label>Confirm Password</label>

                    <input
                            type="password"
                            name="confirm_password"
                            class="password-input"
                            placeholder="Confirm your password"
                            required
                    >

                    <i class="fa-regular fa-eye eye-icon"></i>
                </div>

                <label class="checkbox">

                    <input
                            type="checkbox"
                            name="agree"
                            value="yes"
                            required
                    >

                    <span>
                        I agree to the
                        <a href="terms_conditions.php">Terms & Conditions</a>
                        and
                        <a href="privacy_policy.php">Privacy Policy</a>.
                    </span>

                </label>

                <button type="submit" class="signup-btn">
                    Sign Up
                </button>

                <div class="divider">
                    <span>Or Continue With</span>
                </div>

                <button
                        type="button"
                        class="social-btn"
                        onclick="alert('Google login will be added later')"
                >
                    <i class="fa-brands fa-google"></i>
                    Continue with Google
                </button>

                <button
                        type="button"
                        class="social-btn"
                        onclick="alert('Apple login will be added later')"
                >
                    <i class="fa-brands fa-apple"></i>
                    Continue with Apple
                </button>

                <p class="signin-link">
                    Already have an account?
                    <a href="signin.php">Sign In</a>
                </p>

            </form>

        </div>

    </section>

</div>

<script>

    document.querySelectorAll(".eye-icon").forEach(icon => {

        icon.addEventListener("click", function () {

            const input = this.parentElement.querySelector(".password-input");

            if (input.type === "password") {

                input.type = "text";

                this.classList.remove("fa-eye");
                this.classList.add("fa-eye-slash");

            } else {

                input.type = "password";

                this.classList.remove("fa-eye-slash");
                this.classList.add("fa-eye");
            }

        });

    });

</script>

</body>
</html>