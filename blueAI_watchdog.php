<?php
function failed ($msg) {
    global $mail, $settings;
   
    echo "ERROR DETECTED: {$msg}\n";

    $settings['gmailID'] .= '@gmail.com';

    $mail->IsSMTP();
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = TRUE;
    $mail->SMTPSecure = "tls";
    $mail->Port     = 587;  
    $mail->Username = $settings['gmailID'];
    $mail->Password = $settings['gmailPW'];
    $mail->Host     = "smtp.gmail.com";
    $mail->Mailer   = "smtp";
    $mail->SetFrom($settings['gmailID'], 'BlueAI');
    $mail->AddReplyTo($settings['gmailID'], 'BlueAI');
    $mail->AddAddress($settings['watchdogAlertEmail']);
    $mail->Subject = 'BlueAI';
    $mail->WordWrap = 80;
    $mail->MsgHTML($msg);
    $mail->IsHTML(true);
    
    echo $mail->Send() ? "Mail sent\n" : "Problem sending mail\n";
    exit;
}

chdir(__DIR__);

if (!file_exists('/etc/blueAI_settings.json')) {
    echo "/etc/blueAI_settings.json not found\n";
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$settings = json_decode(file_get_contents('/etc/blueAI_settings.json'), true);
$imgFile  = new \CURLFile('test.jpg');
$mail     = new PHPMailer();
$ch       = curl_init();
$err      = '';

curl_setopt($ch, CURLOPT_URL, $settings['deepstack']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $imgFile]);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_FAILONERROR, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);

$result   = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Curl Response: $httpcode $result\n";

if (200 != $httpcode)
    failed('Unable To Connect To Deepstack');

if ('' == $result)
    failed('No Reponse');

curl_close($ch);

$mtx = json_decode($result, true);

echo 'JSON Decode: ' . print_r($mtx, true);

if (!isset($mtx['success']) || !is_bool($mtx['success']))
    failed('Invalid API response');

if (!$mtx['success'])
    failed('AI API: ' . $mtx['error']);

echo "successful check\n";
