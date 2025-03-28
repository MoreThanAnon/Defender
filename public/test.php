<?php
$to = "martinballirelandmain@gmail.com";
$subject = "Test Email from Postfix";
$message = "This is a test email sent via Postfix SMTP.";
$headers = "From: no-reply@yourdomain.com" . "\r\n" .
           "Reply-To: no-reply@yourdomain.com" . "\r\n" .
           "X-Mailer: PHP/" . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo "Email successfully sent!";
} else {
    echo "Failed to send email.";
}
?>