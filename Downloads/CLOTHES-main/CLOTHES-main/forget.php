<?php
include 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);

    if ($email == "" || $password == "" || $confirm == "") {

        $error = "Please fill all fields";

    } elseif ($password != $confirm) {

        $error = "Passwords do not match";

    } else {

        $check = $conn->prepare("SELECT user_id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows == 0) {

            $error = "Email not found";

        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password=? WHERE email=?");
            $update->bind_param("ss", $hashed, $email);

            if ($update->execute()) {
                $success = "Password updated successfully";
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
    <meta charset="UTF-8">
    <title>Forgot Password</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

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

        .input-group{
            margin-bottom:22px;
        }

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

        input:focus{
            border-bottom:1px solid black;
        }

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
            transition:.3s;
        }

        button:hover{
            background:#222;
        }

        .msg{
            margin-bottom:20px;
            font-size:14px;
            text-align:center;
        }

        .error{
            color:#b00020;
        }

        .success{
            color:green;
        }

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

        .back:hover{
            opacity:.7;
        }

    </style>
</head>

<body>

<div class="box">

    <div class="logo">DEMOISELLE</div>

    <h1>Forgot Password</h1>

    <p class="sub">
        Enter your email and create a new password
    </p>

    <?php if($error != ""): ?>
        <div class="msg error"><?= $error ?></div>
    <?php endif; ?>

    <?php if($success != ""): ?>
        <div class="msg success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="input-group">
            <label>New Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="input-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm" required>
        </div>

        <button type="submit">
            Reset Password
        </button>

    </form>

    <a href="signin.php" class="back">
        Back To Sign In
    </a>

</div>

</body>
</html>