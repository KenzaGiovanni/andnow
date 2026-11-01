<?php
// POST endpoint for the enquiry form. Answers JSON to fetch() and redirects plain form posts.

session_start();
require __DIR__ . '/lib/contact.php';
$config = require __DIR__ . '/config.php';
$strings = require __DIR__ . '/lib/strings.php';

$lang = ($_POST['lang'] ?? '') === 'id' ? 'id' : 'en';
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function respond(bool $wantsJson, string $lang, int $code, array $payload): never
{
    if ($wantsJson) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    } else {
        if (!$payload['ok']) $_SESSION['flash'] = $payload + ['old' => $_POST];
        // Without JavaScript a successful send goes straight to WhatsApp.
        header('Location: ' . ($payload['wa'] ?? 'index.php?lang=' . $lang . ($payload['ok'] ? '&sent=1' : '') . '#contact'), true, 303);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#contact', true, 303);
    exit;
}

if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
    respond($wantsJson, $lang, 400, ['ok' => false, 'error' => 'err.generic']);
}

// Honeypot field, or a form sent faster than a person could type: pretend it worked.
$renderedAt = (int)($_POST['t'] ?? 0);
if (($_POST['website'] ?? '') !== '' || time() - $renderedAt < 3) {
    respond($wantsJson, $lang, 200, ['ok' => true]);
}

if (time() - ($_SESSION['last_sent'] ?? 0) < 30) {
    respond($wantsJson, $lang, 429, ['ok' => false, 'error' => 'err.rate']);
}

['data' => $data, 'errors' => $errors] = validate_inquiry($_POST);
if ($errors) {
    respond($wantsJson, $lang, 422, ['ok' => false, 'errors' => $errors]);
}

if (!store_inquiry($config['data_dir'], $data, $lang)) {
    error_log('andnow: could not write enquiry to ' . $config['data_dir']);
    respond($wantsJson, $lang, 500, ['ok' => false, 'error' => 'err.generic']);
}

notify_inquiry($config['notify_email'], $data);
$_SESSION['last_sent'] = time();
respond($wantsJson, $lang, 200, ['ok' => true, 'wa' => wa_link($config['whatsapp_number'], inquiry_message($data, $strings[$lang]))]);
