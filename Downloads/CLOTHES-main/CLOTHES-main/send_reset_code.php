<?php
global $conn;
include "db.php";

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = trim($_POST['email'] ?? '');

if ($email == "") {
    die("Email is required");
}

$check = $conn->prepare("
SELECT user_id
FROM users
WHERE email=?
");

$check->bind_param("s", $email);
$check->execute();

$result = $check->get_result();

if ($result->num_rows == 0) {
    die("Email not found");
}

$code = rand(100000,999999);

$deleteOld = $conn->prepare("
DELETE FROM password_resets
WHERE email=?
");

$deleteOld->bind_param("s", $email);
$deleteOld->execute();

$insert = $conn->prepare("
INSERT INTO password_resets(email, reset_code)
VALUES(?,?)
");

$insert->bind_param("ss", $email, $code);
$insert->execute();

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();

    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    $mail->Username = "noorfayek321@gmail.com";
    $mail->Password = "veek mrfq jazh krkt";

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;

    $mail->setFrom("noorfayek321@gmail.com", "Demoiselle");

    $mail->addAddress($email);

    $mail->isHTML(true);

    $mail->Subject = "Password Reset Code";

    $mail->Body = "
        <div style='font-family:Arial;padding:20px'>
            <h2>Demoiselle Password Reset</h2>

            <p>Your verification code is:</p>

            <h1 style='letter-spacing:5px'>$code</h1>

            <p>Enter this code to reset your password.</p>
        </div>
    ";

    $mail->send();

    $_SESSION['reset_email'] = $email;

    header("Location: verify_code.php");
    exit;

} catch (Exception $e) {

    echo "Mailer Error: " . $mail->ErrorInfo;
}
?>