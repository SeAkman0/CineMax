<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // HATA GÖRME MODU AÇIK
    $mail->SMTPDebug = 2; 
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    
    // BURAYI DOLDUR
    $mail->Username   = 'cmax131415@gmail.com'; 
    $mail->Password   = 'klhs biqc hjtl kuup'; 
    
    $mail->SMTPSecure = 'tls'; // veya 'ssl'
    $mail->Port       = 587;   // ssl için 465

    $mail->setFrom('test@localhost.com', 'Test Gönderici');
    $mail->addAddress('samierenakman@gmail.com'); // Kendine gönder

    $mail->Subject = 'Test Maili';
    $mail->Body    = 'Bu mail geldiyse sistem çalışıyor demektir.';

    $mail->send();
    echo '<h1 style="color:green">BAŞARILI: Mail gitti! Spam kutusuna bak.</h1>';

} catch (Exception $e) {
    echo '<h1 style="color:red">HATA VAR:</h1>';
    echo "Hata Detayı: " . $mail->ErrorInfo;
}
?>