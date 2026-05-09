<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($toEmail, $toName, $subject, $body) {
    // Load config directly inside the function to avoid global $config being null
    // when config.php was already require_once'd elsewhere (its return value would be lost)
    $config = require __DIR__ . '/../config/config.php';

    $mail = new PHPMailer(true);

    try {
        // Uncomment the line below temporarily if emails still don't arrive,
        // then check the Network tab in DevTools for the raw SMTP error.
        // $mail->SMTPDebug = 2;

        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port       = $config['port'];

        // SSL bypass — safe for localhost/XAMPP only, remove on live server
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;

    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}