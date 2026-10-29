<?php
// Centralized SMTP Configuration for PHPMailer

function getMailer() {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    
    // Using your configured credentials from register.php
    $mail->Username   = 'pdp2512@gmail.com'; 
    $mail->Password   = 'ajoo qbyn ieyc mgrb';         
    
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    $mail->setFrom('pdp2512@gmail.com', 'ONYXPLAY');
    $mail->addReplyTo('pdp2512@gmail.com', 'ONYXPLAY Support');
    
    return $mail;
}
?>
