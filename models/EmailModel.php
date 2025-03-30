<?php
use PHPMailer\PHPMailer\PHPMailer;

class EmailModel {
    public static function setupMail(PHPMailer $mail) {
        // SMTP Server Settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = MAIL_SMTP_AUTH;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->Port       = MAIL_PORT;
        $mail->SMTPSecure = MAIL_ENCRYPTION;

        // Default Sender Details
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    }
}
?>
