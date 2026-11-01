<?php
// Enquiry form: validation, storage, WhatsApp hand-off, optional notification email.

const AREAS = ['order', 'stock', 'invoice', 'books', 'delivery', 'approval', 'ops'];

/**
 * Clean and validate a raw POST array.
 * Returns ['data' => cleaned fields, 'errors' => [field => error key]].
 */
function validate_inquiry(array $in): array
{
    $str = fn($k, $max) => mb_substr(trim((string)($in[$k] ?? '')), 0, $max + 1);

    $data = [
        'name' => $str('name', 100),
        'business' => $str('business', 120),
        'email' => $str('email', 200),
        'phone' => $str('phone', 40),
        'message' => $str('message', 3000),
        'area' => in_array($in['area'] ?? '', AREAS, true) ? $in['area'] : '',
    ];
    $errors = [];

    if ($data['name'] === '') $errors['name'] = 'err.required';
    elseif (mb_strlen($data['name']) > 100) $errors['name'] = 'err.long';

    if (mb_strlen($data['business']) > 120) $errors['business'] = 'err.long';

    // Email is optional now that replies go through WhatsApp.
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'err.email';

    if (mb_strlen($data['phone']) > 40) $errors['phone'] = 'err.long';

    if ($data['area'] === '') $errors['area'] = 'err.required';

    if ($data['message'] === '') $errors['message'] = 'err.required';
    elseif (mb_strlen($data['message']) < 10) $errors['message'] = 'err.short';
    elseif (mb_strlen($data['message']) > 3000) $errors['message'] = 'err.long';

    return ['data' => $data, 'errors' => $errors];
}

/** Append one enquiry as a JSON line. Returns false if the write failed. */
function store_inquiry(string $dir, array $data, string $lang): bool
{
    if (!is_dir($dir) && !mkdir($dir, 0750, true)) return false;
    $row = $data + ['lang' => $lang, 'at' => date('c'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? ''];
    $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    return file_put_contents($dir . '/inquiries.jsonl', $line, FILE_APPEND | LOCK_EX) !== false;
}

/** Best-effort notification. Storage is the source of truth, so a mail failure is not an error. */
function notify_inquiry(string $to, array $data): void
{
    if ($to === '') return;
    // Email already passed FILTER_VALIDATE_EMAIL, so it cannot carry header line breaks.
    $headers = ($data['email'] !== '' ? "Reply-To: {$data['email']}\r\n" : '') . 'Content-Type: text/plain; charset=UTF-8';
    $body = "Name: {$data['name']}\nBusiness: {$data['business']}\nEmail: {$data['email']}\n"
        . "Phone: {$data['phone']}\nArea: {$data['area']}\n\n{$data['message']}\n";
    @mail($to, '=?UTF-8?B?' . base64_encode('New &Now enquiry: ' . $data['name']) . '?=', $body, $headers);
}

/** The fill-in sentence as the visitor wrote it, in their language, ready to send on WhatsApp. */
function inquiry_message(array $data, array $T): string
{
    $from = $data['business'] !== '' ? " {$T['ask.s2']} {$data['business']}" : '';
    return "{$T['ask.s1']} {$data['name']}{$from}. {$T['ask.s3']} {$T['ask.area.' . $data['area']]} {$T['ask.s4']} {$data['message']}";
}

function wa_link(string $number, string $text): string
{
    return 'https://wa.me/' . preg_replace('/\D/', '', $number) . '?text=' . rawurlencode($text);
}
