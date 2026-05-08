<?php
global $conn;
include "db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require dirname(__DIR__) . "/vendor/autoload.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? "");

    if ($email === "") {
        $error = "Please enter your email";
    } else {

        $check = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows === 0) {
            $error = "Email not found";
        } else {

            $code = strval(rand(100000, 999999));
            $expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            $old = $conn->prepare("UPDATE password_resets SET used=1 WHERE email=?");
            $old->bind_param("s", $email);
            $old->execute();

            $stmt = $conn->prepare("
                INSERT INTO password_resets (email, code, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("sss", $email, $code, $expires_at);
            $stmt->execute();

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = "smtp.gmail.com";
                $mail->SMTPAuth = true;

                $mail->Username = "noorfayek321@gmail.com";
                $mail->Password = "veek mrfq jazh krkt";

                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom("noorfayek321@gmail.com", "Demoiselle");
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = "Demoiselle Password Reset Code";
                $mail->Body = "
                    <div style='font-family:Arial;padding:25px'>
                        <h2>Demoiselle</h2>
                        <p>Your reset code is:</p>
                        <h1 style='letter-spacing:6px'>$code</h1>
                        <p>This code expires in 10 minutes.</p>
                    </div>
                ";

                $mail->send();

                header("Location: verify_code.php?email=" . urlencode($email));
                exit;

            } catch (Exception $e) {
                $error = "Email could not be sent";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
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
            box-shadow:0 10px 40px rgba(0,0,0,0.08);
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
        .success{color:green}
        .back{
            display:block;
            text-align:center;
            margin-top:25px;
            text-decoration:none;
            color:black;
            font-size:12px;
            letter-spacing:2px;
            text-transform:uppercase;
        }
    </style>
</head>

<body>

<div class="box">
    <div class="logo">DEMOISELLE</div>

    <h1>Forgot Password</h1>

    <p class="sub">Enter your email to receive a reset code</p>

    <?php if($error !== ""): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <button type="submit">Send Code</button>
    </form>

    <a href="signin.php" class="back">Back To Sign In</a>
</div>

</body>
</html>