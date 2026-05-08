<?php
global $conn;
include "db.php";

$error = "";

$email = trim($_SESSION['reset_email'] ?? "");
$code  = trim($_SESSION['reset_code'] ?? "");

if ($email === "" || $code === "") {
    die("Invalid or expired reset link");
}

$check = $conn->prepare("
    SELECT *
    FROM password_resets
    WHERE email = ?
      AND code = ?
      AND used = 0
    ORDER BY reset_id DESC
    LIMIT 1
");

$check->bind_param("ss", $email, $code);
$check->execute();
$checkRes = $check->get_result();

if ($checkRes->num_rows === 0) {
    die("Invalid or expired reset link");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = trim($_POST['password'] ?? "");
    $confirm = trim($_POST['confirm'] ?? "");

    if ($password === "" || $confirm === "") {
        $error = "Please fill all fields";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } else {

        $update = $conn->prepare("
    UPDATE users
    SET password = ?
    WHERE email = ?
");

        $update->bind_param("ss", $password, $email);

        if ($update->execute()) {

            $used = $conn->prepare("
                UPDATE password_resets
                SET used = 1
                WHERE email = ?
                  AND code = ?
            ");

            $used->bind_param("ss", $email, $code);
            $used->execute();

            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_code']);

            header("Location: signin.php?reset=success");
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
    <meta charset="UTF-8">
    <title>Reset Password</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{
            background:#f8f8f8;
            font-family:'Outfit',sans-serif;
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            padding:30px;
        }
        .box{
            width:420px;
            background:white;
            padding:55px 45px;
            box-shadow:0 10px 40px rgba(0,0,0,.08);
            border-radius:12px;
        }
        .logo{
            text-align:center;
            font-size:13px;
            letter-spacing:7px;
            margin-bottom:35px;
            font-weight:300;
        }
        h1{
            font-family:'Cormorant Garamond',serif;
            font-size:45px;
            font-weight:300;
            text-align:center;
            margin-bottom:10px;
        }
        .sub{
            text-align:center;
            color:#777;
            font-size:14px;
            margin-bottom:35px;
        }
        .input-group{margin-bottom:22px}
        label{
            display:block;
            margin-bottom:8px;
            font-size:11px;
            letter-spacing:2px;
            text-transform:uppercase;
            color:#777;
        }
        input{
            width:100%;
            border:none;
            border-bottom:1px solid #ccc;
            padding:12px 0;
            outline:none;
            font-size:15px;
            background:transparent;
        }
        input:focus{border-bottom:1px solid black}
        button{
            width:100%;
            margin-top:15px;
            background:black;
            color:white;
            border:none;
            padding:16px;
            letter-spacing:3px;
            text-transform:uppercase;
            cursor:pointer;
        }
        .msg{
            margin-bottom:20px;
            font-size:14px;
            text-align:center;
        }
        .error{color:#b00020}
    </style>
</head>

<body>

<div class="box">
    <div class="logo">DEMOISELLE</div>

    <h1>New Password</h1>

    <p class="sub">Create your new password</p>

    <?php if($error !== ""): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">

        <div class="input-group">
            <label>New Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="input-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm" required>
        </div>

        <button type="submit">Update Password</button>
    </form>
</div>

</body>
</html>