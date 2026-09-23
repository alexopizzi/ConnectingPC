<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Email transazionali in testo semplice via SMTP (D-017). In locale: Mailpit (http://localhost:8025).
 * Invio sincrono: nessuna coda, nessun processo in background.
 */
final class Mailer
{
    public function __construct(private readonly Logger $logger)
    {
    }

    public function send(string $to, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = (string) Env::get('MAIL_HOST', '127.0.0.1');
            $mail->Port = (int) Env::get('MAIL_PORT', '25');
            $user = Env::get('MAIL_USER');
            if ($user !== null) {
                $mail->SMTPAuth = true;
                $mail->Username = $user;
                $mail->Password = (string) Env::get('MAIL_PASS', '');
            }
            $encryption = Env::get('MAIL_ENCRYPTION');
            $mail->SMTPSecure = match ($encryption) {
                'ssl', 'smtps' => PHPMailer::ENCRYPTION_SMTPS,
                'tls', 'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
                default => '',
            };
            $mail->SMTPAutoTLS = $encryption !== null;
            $mail->Timeout = 15;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom((string) Env::get('MAIL_FROM_ADDRESS', 'noreply@localhost'), (string) Env::get('MAIL_FROM_NAME', 'ConnectingPC'));
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);
            $mail->send();

            return true;
        } catch (MailerException $e) {
            // Nessun indirizzo nel log: basta il tipo di errore e l'identificativo della richiesta.
            $this->logger->error('Invio email non riuscito', ['error' => $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage()]);

            return false;
        }
    }
}
