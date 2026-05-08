<?php
global $conn;
include "db.php";

$error = "";

$email = trim($_GET['email'] ?? ($_POST['email'] ?? ""));

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $code = preg_replace('/\D/', '', $_POST['code'] ?? '');

    $stmt = $conn->prepare("
        SELECT reset_id, email, code, used, expires_at
        FROM password_resets
        WHERE email = ?
        ORDER BY reset_id DESC
        LIMIT 1
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        $dbCode = trim((string)$row['code']);
        $isUsed = (int)$row['used'];

        if ($dbCode === $code && $isUsed === 0) {

            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_code'] = $code;

            header("Location: reset_password.php");
            exit;

        } else {
            $error = "Invalid or expired code";
        }

    } else {
        $error = "Invalid or expired code";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Code</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#f8f8f8;font-family:'Outfit',sans-serif;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:30px}
        .box{width:420px;background:white;padding:55px 45px;box-shadow:0 10px 40px rgba(0,0,0,.08);border-radius:12px}
        .logo{text-align:center;font-size:13px;letter-spacing:7px;margin-bottom:35px;font-weight:300}
        h1{font-family:'Cormorant Garamond',serif;font-size:45px;font-weight:300;text-align:center;margin-bottom:10px}
        .sub{text-align:center;color:#777;font-size:14px;margin-bottom:35px}
        .input-group{margin-bottom:22px}
        label{display:block;margin-bottom:8px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#777}
        input{width:100%;border:none;border-bottom:1px solid #ccc;padding:12px 0;outline:none;font-size:18px;background:transparent;text-align:center;letter-spacing:6px}
        input:focus{border-bottom:1px solid black}
        button{width:100%;margin-top:15px;background:black;color:white;border:none;padding:16px;letter-spacing:3px;text-transform:uppercase;cursor:pointer}
        .msg{margin-bottom:20px;font-size:14px;text-align:center}
        .error{color:#b00020}
        .back{display:block;text-align:center;margin-top:25px;text-decoration:none;color:black;font-size:12px;letter-spacing:2px;text-transform:uppercase}
    </style>
</head>

<body>

<div class="box">
    <div class="logo">DEMOISELLE</div>

    <h1>Verify Code</h1>

    <p class="sub">Enter the 6-digit code sent to your email</p>

    <?php if($error !== ""): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

        <div class="input-group">
            <label>Verification Code</label>
            <input type="text" name="code" maxlength="6" required>
        </div>

        <button type="submit">Verify Code</button>
    </form>

    <a href="forget.php" class="back">Back</a>
</div>

</body>
</html>