<?php
namespace App\Services;

class EventReservationEmail
{
    public static function subject(array $reservation): string
    {
        return 'Reserva confirmada — ' . trim((string)($reservation['event_name'] ?? 'Evento'));
    }

    public static function render(array $reservation): string
    {
        $escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $name = $escape($reservation['guest_name'] ?? 'Convidado');
        $event = $escape($reservation['event_name'] ?? 'Evento');
        $token = $escape($reservation['token'] ?? '');
        $date = self::formatDate((string)($reservation['starts_at'] ?? ''));
        $qrUrl = trim((string)($reservation['qr_code_url'] ?? ''));
        $qr = $qrUrl !== '' ? '<img src="' . $escape($qrUrl) . '" width="240" alt="QR-Code do bilhete" style="display:block;width:100%;max-width:240px;height:auto;margin:20px auto 0;border:0">' : '';

        return '<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $escape(self::subject($reservation)) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#eef2f7;color:#101828;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%">'
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0">A tua reserva para ' . $event . ' está confirmada.</div>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#eef2f7"><tr><td align="center" style="padding:32px 12px">'
            . '<table role="presentation" width="620" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:620px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 16px 40px rgba(15,23,42,.10)">'
            . '<tr><td style="padding:34px 36px;background:#111827;color:#ffffff"><div style="font-size:25px;line-height:1;letter-spacing:6px;font-weight:700">CHORARDERIR</div><div style="margin-top:26px;font-size:13px;line-height:1.4;letter-spacing:2px;text-transform:uppercase;color:#a5b4fc;font-weight:700">Reserva confirmada</div><h1 style="margin:8px 0 0;font-size:30px;line-height:1.18">' . $event . '</h1></td></tr>'
            . '<tr><td style="padding:34px 36px"><p style="margin:0 0 18px;font-size:20px;line-height:1.5">Olá <strong>' . $name . '</strong>,</p><p style="margin:0 0 26px;font-size:17px;line-height:1.65;color:#344054">A tua reserva foi confirmada. Guarda este email e apresenta o QR-Code à entrada do evento.</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin-bottom:24px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px"><tr><td style="padding:22px"><div style="font-size:12px;line-height:1.4;letter-spacing:1.5px;text-transform:uppercase;color:#667085;font-weight:700">Data e hora</div><div style="margin-top:6px;font-size:20px;line-height:1.4;font-weight:700;color:#101828">' . $escape($date) . '</div></td></tr></table>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f8fafc;border:1px solid #dbe3ee;border-radius:18px"><tr><td align="center" style="padding:26px 22px"><div style="font-size:13px;line-height:1.4;letter-spacing:1.5px;text-transform:uppercase;color:#4338ca;font-weight:700">Bilhete digital</div>' . $qr . '<div style="margin-top:18px;font-size:12px;line-height:1.5;color:#667085">Token</div><div style="font-size:14px;line-height:1.5;color:#344054;word-break:break-all;font-family:Courier New,monospace">' . $token . '</div></td></tr></table>'
            . '<p style="margin:24px 0 0;font-size:13px;line-height:1.6;text-align:center;color:#667085">Cada QR-Code só pode ser validado uma vez.</p></td></tr>'
            . '<tr><td style="padding:22px 36px;background:#f8fafc;border-top:1px solid #e4e7ec;font-size:12px;line-height:1.6;text-align:center;color:#667085">Este email foi enviado automaticamente. Se não reconheces esta reserva, responde a esta mensagem.</td></tr></table></td></tr></table></body></html>';
    }

    public static function send(array $reservation, ?callable $transport = null): bool
    {
        $recipient = trim((string)($reservation['guest_email'] ?? ''));
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) return false;
        $subject = self::subject($reservation);
        $body = self::render($reservation);
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: CHORARDERIR <noreply@chorarderir.com>\r\n";
        return $transport ? (bool)$transport($recipient, $subject, $body, $headers) : mail($recipient, $subject, $body, $headers);
    }

    private static function formatDate(string $value): string
    {
        try {
            $date = new \DateTimeImmutable($value);
            $months = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
            return $date->format('j') . ' de ' . $months[(int)$date->format('n')] . ' de ' . $date->format('Y') . ', às ' . $date->format('H:i');
        } catch (\Throwable) {
            return $value;
        }
    }
}
