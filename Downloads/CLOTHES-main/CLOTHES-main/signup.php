<?php
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

<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demoiselle — Create Account</title>

    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Outfit:wght@200;300;400;500;600&display=swap" rel="stylesheet">

    <style>
        html, body { height: 100%; margin: 0; }
        .font-serif { font-family: 'Cormorant Garamond', serif; }
        .font-sans { font-family: 'Outfit', sans-serif; }

        .signup-bg {
            background-image: url('pic/signupback.png');
            background-size: cover;
            background-position: center 40%;
            background-attachment: fixed;
        }

        .overlay {
            background: linear-gradient(rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0.75));
        }

        .form-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            border-radius: 24px;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.3);
        }

        .vertical-brand-left {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            letter-spacing: 10px;
            color: rgba(255,255,255,0.28);
        }

        .vertical-brand-right {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            letter-spacing: 10px;
            color: rgba(255,255,255,0.18);
        }
    </style>
</head>

<body class="signup-bg font-sans">

<div class="min-h-screen flex items-center justify-center relative overlay">

    <div class="absolute right-10 top-1/2 -translate-y-1/2 z-20 hidden md:flex flex-col items-center">
        <span class="vertical-brand-right">DEMOISELLE</span>
        <div class="w-px h-24 bg-white/20 mt-4"></div>
    </div>

    <div class="absolute left-10 top-1/2 -translate-y-1/2 z-20 hidden md:flex flex-col items-center">
        <span class="vertical-brand-left">DEMOISELLE</span>
        <div class="w-px h-24 bg-white/20 mt-4"></div>
    </div>

    <div class="form-card w-full max-w-md mx-6 p-10">
        <div class="text-center mb-8">
            <h1 class="font-serif text-5xl font-light text-black">Create Account</h1>
            <p class="text-gray-600 mt-3">Join Demoiselle and enjoy timeless elegance</p>
        </div>

        <?php if ($error): ?>
            <p class="text-red-600 text-center mb-5">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="signup.php" class="space-y-6">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs tracking-widest uppercase mb-2 text-gray-500">First Name</label>
                    <input
                            name="first_name"
                            type="text"
                            required
                            class="w-full px-5 py-4 border border-gray-300 rounded-2xl focus:border-black focus:outline-none text-lg"
                            placeholder="Sarah"
                    >
                </div>

                <div>
                    <label class="block text-xs tracking-widest uppercase mb-2 text-gray-500">Last Name</label>
                    <input
                            name="last_name"
                            type="text"
                            required
                            class="w-full px-5 py-4 border border-gray-300 rounded-2xl focus:border-black focus:outline-none text-lg"
                            placeholder="Al-Masri"
                    >
                </div>
            </div>

            <div>
                <label class="block text-xs tracking-widest uppercase mb-2 text-gray-500">Email Address</label>
                <input
                        name="email"
                        type="email"
                        required
                        class="w-full px-5 py-4 border border-gray-300 rounded-2xl focus:border-black focus:outline-none text-lg"
                        placeholder="your@email.com"
                >
            </div>

            <div>
                <label class="block text-xs tracking-widest uppercase mb-2 text-gray-500">Password</label>

                <div class="relative">
                    <input
                            name="password"
                            id="password"
                            type="password"
                            required
                            class="w-full px-5 py-4 border border-gray-300 rounded-2xl focus:border-black focus:outline-none text-lg"
                            placeholder="••••••••"
                    >

                    <button type="button" onclick="togglePassword()" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-black">
                        <i id="eyeIcon" data-lucide="eye" style="width:20px;height:20px"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full bg-black text-white py-5 rounded-2xl text-sm tracking-widest font-medium hover:bg-gray-900 transition-all">
                CREATE ACCOUNT
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Already have an account?
            <a href="signin.php" class="text-black font-medium hover:underline">Log in</a>
        </p>
    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById("password");
        const icon = document.getElementById("eyeIcon");

        if (input.type === "password") {
            input.type = "text";
            icon.setAttribute("data-lucide", "eye-off");
        } else {
            input.type = "password";
            icon.setAttribute("data-lucide", "eye");
        }

        lucide.createIcons();
    }

    lucide.createIcons();
</script>

</body>
</html>