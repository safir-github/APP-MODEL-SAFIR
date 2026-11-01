<?php
// Envoi d'e-mails via la fonction mail() de l'hébergeur (o2switch).
// Simple et gratuit ; la délivrabilité dépend du domaine (SPF/DKIM, voir docs/EMAIL.md).
// Pour un volume élevé ou une meilleure délivrabilité, remplacer le corps de send_mail()
// par un appel HTTP à un service transactionnel (clé dans config.local.php).

function send_mail(string $to, string $subject, string $text): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=utf-8',
        'From: ' . MAIL_FROM,
    ];
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $sent = @mail($to, $encodedSubject, $text, implode("\r\n", $headers));
    if (!$sent) {
        error_log('[mail] envoi impossible vers ' . $to);
    }
    return $sent;
}
