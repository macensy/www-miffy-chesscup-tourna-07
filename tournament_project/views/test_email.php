<?php
/**
 * EMAIL TEST SCRIPT
 * Put this file inside your /views/ folder and open it in the browser.
 * DELETE THIS FILE after testing — don't leave it on a live server.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require __DIR__ . '/../config/config.php';

echo "<h2>Email Config Loaded</h2>";
echo "<pre>";
echo "Host:       " . $config['host'] . "\n";
echo "Username:   " . $config['username'] . "\n";
echo "Password:   " . (empty($config['password']) ? '❌ EMPTY!' : '✅ set (' . strlen($config['password']) . ' chars)') . "\n";
echo "Port:       " . $config['port'] . "\n";
echo "Encryption: " . $config['encryption'] . "\n";
echo "From Email: " . $config['from_email'] . "\n";
echo "</pre>";

echo "<h2>Attempting to Send Test Email...</h2>";

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Show full SMTP conversation
    $mail->Debugoutput = 'echo'; // Print debug output directly

    $mail->isSMTP();
    $mail->Host       = $config['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['username'];
    $mail->Password   = $config['password'];
    $mail->SMTPSecure = $config['encryption'];
    $mail->Port       = $config['port'];

    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]
    ];

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress('peyttabungar@gmail.com', 'Admin');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email - Miffy Chess Cup';
    $mail->Body    = '<h3>It works!</h3><p>Your email setup is working correctly.</p>';
    $mail->AltBody = 'It works! Your email setup is working correctly.';

    $mail->send();
    echo "<br><h2 style='color:green'>✅ Email sent successfully! Check peyttabungar@gmail.com</h2>";

} catch (Exception $e) {
    echo "<br><h2 style='color:red'>❌ Email failed!</h2>";
    echo "<p><strong>Error:</strong> " . $mail->ErrorInfo . "</p>";
    echo "<p>Read the SMTP debug output above — it will tell you exactly what went wrong.</p>";
}
?>