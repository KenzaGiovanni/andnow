<?php
session_start();
$strings = require __DIR__ . '/lib/strings.php';
$config = require __DIR__ . '/config.php';
require __DIR__ . '/lib/contact.php';

// Language: explicit ?lang= wins, otherwise the browser's preference, otherwise English.
$lang = $_GET['lang'] ?? (str_starts_with(strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''), 'id') ? 'id' : 'en');
$lang = $lang === 'id' ? 'id' : 'en';
$T = $strings[$lang];

$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$old = $flash['old'] ?? [];
$errors = $flash['errors'] ?? [];
$sent = isset($_GET['sent']);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function t(string $key): string { global $T; return e($T[$key] ?? $key); }
// Attribute pair for a translatable leaf element.
function i(string $key): string { return 'data-i18n="' . e($key) . '"'; }

function cells(string $text, int $n): string
{
    $chars = mb_str_split(str_pad(mb_strtoupper(mb_substr($text, 0, $n)), $n));
    return implode('', array_map(fn($c) => '<span class="cell"><span class="ch">' . e($c) . '</span></span>', $chars));
}

function headline(string $key, string $class = ''): string {
    return '<h2 class="h2 ' . $class . '"><span ' . i("$key.a") . '>' . t("$key.a") . '</span> <span class="accent-text scroll-highlight" ' . i("$key.b") . '>' . t("$key.b") . '</span></h2>';
}
// Paper-craft icons in assets/img/services/, generated with Higgsfield to match the hero art.
$marks = ['process' => 'flow', 'system' => 'phone', 'web' => 'browser', 'products' => 'list'];
// Each service card opens the project that shows it in practice.
$cardLinks = ['process' => '#project-sunday', 'system' => '#project-dimas', 'web' => '#project-aco', 'products' => '#products'];
$booking = $config['booking_url'] ?? '';
// Fixed live address, so search engines and link previews always point at andnowhq.com.
$base = rtrim($config['site_url'], '/');
$email = $config['contact_email'];
$waBase = wa_link($config['whatsapp_number'], $T['wa.hello']);
// Changes whenever a stylesheet or script changes, so browsers drop their cached copy.
$v = max(array_map('filemtime', array_merge(glob(__DIR__ . '/assets/css/*.css'), glob(__DIR__ . '/assets/js/*.js'))));
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title data-i18n="meta.title"><?= t('meta.title') ?></title>
<meta name="description" content="<?= t('meta.desc') ?>">
<meta name="robots" content="<?= $sent ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1' ?>">
<link rel="canonical" href="<?= e($base) ?>/?lang=<?= $lang ?>">
<link rel="alternate" hreflang="en" href="<?= e($base) ?>/?lang=en">
<link rel="alternate" hreflang="id" href="<?= e($base) ?>/?lang=id">
<link rel="alternate" hreflang="x-default" href="<?= e($base) ?>/">
<meta name="theme-color" content="#192749">
<link rel="icon" type="image/png" href="assets/brand/favicon.png">
<link rel="apple-touch-icon" href="assets/brand/apple-touch-icon.png">
<link rel="preload" href="assets/fonts/geist-sans-latin-600-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="assets/fonts/geist-sans-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" as="image" href="assets/img/hero-parts/ribbon-tangled.webp">
<link rel="stylesheet" href="assets/css/site.css?v=<?= $v ?>">
<link rel="stylesheet" href="assets/css/editorial.css?v=<?= $v ?>">
<link rel="stylesheet" href="assets/css/abstracts.css?v=<?= $v ?>">
<link rel="stylesheet" href="assets/css/focus.css?v=<?= $v ?>">
<meta property="og:title" content="<?= t('meta.title') ?>">
<meta property="og:description" content="<?= t('meta.desc') ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= e($base) ?>/?lang=<?= $lang ?>">
<meta property="og:site_name" content="&amp;Now">
<meta property="og:locale" content="<?= $lang === 'id' ? 'id_ID' : 'en_US' ?>">
<meta property="og:locale:alternate" content="<?= $lang === 'id' ? 'en_US' : 'id_ID' ?>">
<meta property="og:image" content="<?= e($base) ?>/assets/brand/og-image.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= t('meta.title') ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= t('meta.title') ?>">
<meta name="twitter:description" content="<?= t('meta.desc') ?>">
<meta name="twitter:image" content="<?= e($base) ?>/assets/brand/og-image.jpg">
<?php
// Structured data: tells Google and AI assistants who &Now is, what it offers and the answers to common questions.
// Only facts already on the page. Add address, phone and social profiles here once they exist.
$plain = fn (string $k) => html_entity_decode($T[$k], ENT_QUOTES, 'UTF-8');
$org = [
    '@type' => 'ProfessionalService',
    '@id' => "$base/#org",
    'name' => '&Now',
    'url' => "$base/",
    'logo' => "$base/assets/brand/icon-512.png",
    'image' => "$base/assets/brand/og-image.jpg",
    'description' => $plain('meta.desc'),
    'email' => $email,
    'areaServed' => ['@type' => 'Country', 'name' => 'Indonesia'],
    'knowsLanguage' => ['en', 'id'],
    'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => $plain('nav.services'), 'itemListElement' => array_map(
        fn ($s) => ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $plain("svc.$s.name"), 'description' => $plain("svc.$s.desc")]],
        array_keys($marks)
    )],
];
if (strpos($config['whatsapp_number'], 'X') === false) $org['telephone'] = '+' . $config['whatsapp_number'];
$faq = ['@type' => 'FAQPage', '@id' => "$base/#faq", 'inLanguage' => $lang, 'mainEntity' => array_map(
    fn ($q) => ['@type' => 'Question', 'name' => $plain("faq.q$q"), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $plain("faq.a$q")]],
    range(1, 5)
)];
$site = ['@type' => 'WebSite', '@id' => "$base/#website", 'url' => "$base/", 'name' => '&Now', 'inLanguage' => ['en', 'id'], 'publisher' => ['@id' => "$base/#org"]];
?>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => [$org, $site, $faq]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
</head>
<body>
<a class="skip" href="#main" <?= i('skip') ?>><?= t('skip') ?></a>
<div class="header-stage"><header class="nav wrap" id="top">
  <a class="nav-logo" href="#top" aria-label="&amp;Now"><img src="assets/brand/logo.webp" alt="&amp;Now" width="900" height="375"></a>
  <nav class="nav-links" aria-label="<?= t('nav.label') ?>" data-i18n-aria="nav.label">
    <a href="#work" <?= i('nav.work') ?>><?= t('nav.work') ?></a>
    <a href="#services" <?= i('nav.services') ?>><?= t('nav.services') ?></a>
    <a href="#process" <?= i('nav.process') ?>><?= t('nav.process') ?></a>
  </nav>
  <div class="nav-end">
    <div class="lang" role="group" aria-label="<?= t('lang.label') ?>" data-i18n-aria="lang.label">
      <a href="?lang=en" hreflang="en" data-lang="en" aria-current="<?= $lang === 'en' ? 'true' : 'false' ?>">EN</a>
      <span aria-hidden="true">/</span>
      <a href="?lang=id" hreflang="id" data-lang="id" aria-current="<?= $lang === 'id' ? 'true' : 'false' ?>">ID</a>
    </div>
    <a class="nav-contact talk-cta" href="#contact"><span <?= i('cta.talk') ?>><?= t('cta.talk') ?></span> <span aria-hidden="true">↗</span></a>
  </div>
</header></div>
<main id="main">
<div class="hero-stage" data-hero-fix><div class="hero-pin"><div class="hero-scene" aria-hidden="true"><img class="part part--circle" src="assets/img/hero-parts/circle.webp" alt="" loading="eager" decoding="async" style="--w:44;--mx:61;--my:34;--mr:-8;--tx:58;--ty:30;--tr:0" data-delay="0.0"><img class="part part--ribbon-tangled" src="assets/img/hero-parts/ribbon-tangled.webp" alt="" loading="eager" decoding="async" style="--w:72;--mx:52;--my:50;--mr:-12;--tx:52;--ty:50;--tr:8" data-delay="0.1"><img class="part part--ribbon-smooth" src="assets/img/hero-parts/ribbon-smooth.webp" alt="" loading="eager" decoding="async" style="--w:118;--mx:55;--my:58;--mr:-20;--tx:55;--ty:58;--tr:-20" data-delay="0.1"><img class="part part--graph-b" src="assets/img/hero-parts/graph-b.webp" alt="" loading="eager" decoding="async" style="--w:13;--mx:24;--my:60;--mr:-16;--tx:55;--ty:60;--tr:5" data-delay="0.18"><img class="part part--graph-a" src="assets/img/hero-parts/graph-a.webp" alt="" loading="eager" decoding="async" style="--w:15;--mx:63;--my:40;--mr:20;--tx:53;--ty:58;--tr:10" data-delay="0.26"><img class="part part--receipt-b" src="assets/img/hero-parts/receipt-b.webp" alt="" loading="eager" decoding="async" style="--w:11;--mx:40;--my:74;--mr:30;--tx:54;--ty:58;--tr:7" data-delay="0.32"><img class="part part--receipt-c" src="assets/img/hero-parts/receipt-c.webp" alt="" loading="eager" decoding="async" style="--w:11;--mx:79;--my:62;--mr:-24;--tx:56;--ty:55;--tr:13" data-delay="0.38"><img class="part part--receipt-a" src="assets/img/hero-parts/receipt-a.webp" alt="" loading="eager" decoding="async" style="--w:7;--mx:31;--my:28;--mr:-36;--tx:52;--ty:56;--tr:11" data-delay="0.44"><img class="part part--calculator" src="assets/img/hero-parts/calculator.webp" alt="" loading="eager" decoding="async" style="--w:17;--mx:21;--my:87;--mr:-26;--tx:29;--ty:83;--tr:0" data-delay="0.5"><img class="part part--boxes" src="assets/img/hero-parts/boxes.webp" alt="" loading="eager" decoding="async" style="--w:26;--mx:93;--my:85;--mr:16;--tx:88;--ty:81;--tr:0" data-delay="0.56"></div><section class="hero wrap">
  <div class="hero-copy">
    <h1><span <?= i('hero.title.a') ?>><?= t('hero.title.a') ?></span> <span class="hero-line" <?= i('hero.title.b') ?>><?= t('hero.title.b') ?></span></h1>
    <p class="hero-sub" <?= i('hero.sub') ?>><?= t('hero.sub') ?></p>
    <div class="hero-ctas">
      <a class="btn btn-ink talk-cta" href="#contact"><span <?= i('cta.talk') ?>><?= t('cta.talk') ?></span><span aria-hidden="true">↗</span></a>
      <a class="text-link" href="#work"><span <?= i('cta.work') ?>><?= t('cta.work') ?></span><span aria-hidden="true">↓</span></a>
    </div>
    <p class="hero-note" <?= i('hero.note') ?>><?= t('hero.note') ?></p>
  </div>
</section></div></div>
<section class="intro" id="intro" aria-label="<?= t('intro.label') ?>" data-i18n-aria="intro.label">
  <div class="wrap">
    <p class="eyebrow" <?= i('intro.eyebrow') ?>><?= t('intro.eyebrow') ?></p>
    <p class="intro-text" data-word-reveal>
      <span <?= i('intro.a') ?>><?= t('intro.a') ?></span>
      <span class="inline-process inline-icon-stack" aria-hidden="true"><span class="process-circle"><img src="assets/img/abstract/understand.webp" alt="" width="900" height="900" loading="lazy"></span><span class="process-circle"><img src="assets/img/abstract/improve.webp" alt="" width="900" height="900" loading="lazy"></span><span class="process-circle"><img src="assets/img/abstract/digitise.webp" alt="" width="900" height="900" loading="lazy"></span></span>
      <span <?= i('intro.b') ?>><?= t('intro.b') ?></span>
      <span class="inline-process inline-ribbon" aria-hidden="true"><img src="assets/img/abstract/process-path.webp" alt="" width="800" height="400" loading="lazy"></span>
      <span <?= i('intro.c') ?>><?= t('intro.c') ?></span>
      <span class="inline-process inline-system" aria-hidden="true"><img src="assets/img/abstract/digitise.webp" alt="" width="900" height="900" loading="lazy"></span>
      <span <?= i('intro.d') ?>><?= t('intro.d') ?></span>
      <span class="intro-finish scroll-highlight" <?= i('intro.e') ?>><?= t('intro.e') ?></span>
    </p>
    <a class="intro-next" href="#services" aria-label="<?= t('nav.services') ?>" data-i18n-aria="nav.services"><span aria-hidden="true">↓</span></a>
  </div>
</section>

<section class="offerings wrap" id="services" aria-label="<?= t('nav.services') ?>" data-i18n-aria="nav.services">
  <p class="eyebrow" <?= i('services.eyebrow') ?>><?= t('services.eyebrow') ?></p>
  <div class="offering-grid">
    <?php foreach ($marks as $s => $kind): ?>
    <a class="offering" href="<?= $cardLinks[$s] ?>">
      <img class="offering-icon" src="assets/img/services/<?= $kind ?>.webp" alt="" width="256" height="256" loading="lazy">
      <h3 <?= i("svc.$s.name") ?>><?= t("svc.$s.name") ?></h3>
      <p <?= i("svc.$s.desc") ?>><?= t("svc.$s.desc") ?></p>
      <span class="offering-arrow" aria-hidden="true">↗</span>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="ai-perspective" aria-labelledby="ai-title">
  <div class="wrap ai-layout">
    <div class="ai-route" data-ai-route>
      <svg class="ai-route-svg" viewBox="0 0 600 440" preserveAspectRatio="none" aria-hidden="true"><path class="ai-route-line" pathLength="1" d="M60 380 C 150 200, 230 170, 300 230 S 440 330, 540 80"/></svg>
      <ol class="ai-route-stops">
        <?php foreach ([1 => [10, 86.4, ''], 2 => [50, 52.3, ''], 3 => [90, 18.2, ' is-last']] as $n => [$x, $y, $cls]): ?>
        <li class="ai-route-stop<?= $cls ?>" style="--x:<?= $x ?>%;--y:<?= $y ?>%;--n:<?= $n ?>"><span class="ai-route-dot" aria-hidden="true"></span><div class="ai-route-pill"><h3 <?= i("ai.step$n.title") ?>><?= t("ai.step$n.title") ?></h3><p <?= i("ai.step$n.desc") ?>><?= t("ai.step$n.desc") ?></p></div></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <div class="ai-copy"><p class="eyebrow" <?= i('ai.eyebrow') ?>><?= t('ai.eyebrow') ?></p>
      <h2 id="ai-title"><span <?= i('ai.title.a') ?>><?= t('ai.title.a') ?></span> <span <?= i('ai.title.b') ?>><?= t('ai.title.b') ?></span></h2>
      <p class="ai-body" <?= i('ai.body') ?>><?= t('ai.body') ?></p>
    </div>
  </div>
</section>

<section class="section editorial-work work-focus" id="work">
  <div class="wrap">
    <div class="section-head"><div><p class="eyebrow" <?= i('work.eyebrow') ?>><?= t('work.eyebrow') ?></p><?= headline('work.title') ?></div><p class="section-aside" <?= i('work.aside') ?>><?= t('work.aside') ?></p></div>
    <div class="project-switcher" data-project-tabs role="tablist" aria-label="<?= t('nav.work') ?>" data-i18n-aria="nav.work" hidden>
      <?php foreach (['aco', 'dimas', 'sunday'] as $n => $c): ?>
      <button type="button" role="tab" id="project-tab-<?= $c ?>" aria-controls="project-<?= $c ?>" aria-selected="<?= $n === 0 ? 'true' : 'false' ?>" tabindex="<?= $n === 0 ? '0' : '-1' ?>" data-project-tab="<?= $c ?>"><span class="project-tab-number" aria-hidden="true">0<?= $n+1 ?></span><span <?= i("case.$c.tab") ?>><?= t("case.$c.tab") ?></span><span aria-hidden="true">↗</span></button>
      <?php endforeach; ?>
    </div>
    <div class="project-playback" data-project-playback hidden><button type="button" data-project-pause aria-pressed="false" aria-label="<?= t("work.pause") ?>" data-i18n-aria="work.pause"><span class="playback-icon" aria-hidden="true"></span></button></div>
    <div class="project-stage">
    <?php foreach (['aco' => ['work-acosystem', '01'], 'dimas' => ['work-dimas-pod', '02'], 'sunday' => ['work-sundaytable', '03']] as $c => [$img, $num]): ?>
      <article class="project-panel project-<?= $c ?>" id="project-<?= $c ?>" data-project="<?= $c ?>">
        <div class="project-visual"><img src="assets/img/<?= $img ?>.webp" alt="<?= t("case.$c.alt") ?>" data-i18n-alt="case.<?= $c ?>.alt" width="1200" height="750" loading="lazy"></div>
        <div class="project-copy"><p class="eyebrow" <?= i("case.$c.type") ?>><?= t("case.$c.type") ?></p><h3 <?= i("case.$c.client") ?>><?= t("case.$c.client") ?></h3><dl><div><dt <?= i('work.problem') ?>><?= t('work.problem') ?></dt><dd <?= i("case.$c.problem") ?>><?= t("case.$c.problem") ?></dd></div><div><dt <?= i('work.built') ?>><?= t('work.built') ?></dt><dd <?= i("case.$c.built") ?>><?= t("case.$c.built") ?></dd></div><div><dt <?= i('work.now') ?>><?= t('work.now') ?></dt><dd <?= i("case.$c.now") ?>><?= t("case.$c.now") ?></dd></div></dl><?php if (isset($T["case.$c.also"])): ?><p class="project-also" <?= i("case.$c.also") ?>><?= t("case.$c.also") ?></p><?php endif; ?><a class="text-link conversation-link" href="#contact"><span <?= i('work.contact') ?>><?= t('work.contact') ?></span><span aria-hidden="true">↗</span></a></div>
      </article>
    <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section products editorial-products" id="products"><div class="wrap">
  <div class="section-head"><div><p class="eyebrow" <?= i('products.eyebrow') ?>><?= t('products.eyebrow') ?></p><?= headline('products.title') ?></div><p class="section-aside" <?= i('products.sub') ?>><?= t('products.sub') ?></p></div>
  <div class="prod-grid">
  <?php foreach (['ledflow' => ['LedFlow', 'ledflowapp.com'], 'westra' => ['Westra', 'getwestra.com'], 'sumaru' => ['Sumaru', null]] as $p => [$name, $site]): ?>
    <article class="prod"><div class="product-index"><span><?= $name ?></span><span class="product-imprint" aria-hidden="true"><img class="brand-inline" src="assets/brand/icon-512.png" alt="" width="512" height="512" loading="lazy"><span>/ <?= strtoupper(substr($p, 0, 2)) ?></span></span></div><div class="prod-image"><img src="assets/img/<?= $p === 'ledflow' ? 'product-ledflow-dashboard' : 'product-' . $p ?>.webp" alt="<?= t("prod.$p.alt") ?>" data-i18n-alt="prod.<?= $p ?>.alt" width="1600" height="1000" loading="lazy"></div><div class="prod-text"><h3><?= $name ?></h3><p <?= i("prod.$p.desc") ?>><?= t("prod.$p.desc") ?></p>
    <?php if ($site): ?><a class="prod-link" href="https://<?= $site ?>" target="_blank" rel="noopener"><?= $site ?><span aria-hidden="true">↗</span></a><?php else: ?><span class="prod-soon" <?= i('prod.soon') ?>><?= t('prod.soon') ?></span><?php endif; ?>
    </div></article>
  <?php endforeach; ?>
  </div>
</div></section>

<section class="section process editorial-process" id="process"><div class="wrap">
  <div class="section-head"><div><p class="eyebrow" <?= i('process.eyebrow') ?>><?= t('process.eyebrow') ?></p><?= headline('process.title') ?></div><p class="section-aside" <?= i('process.sub') ?>><?= t('process.sub') ?></p></div>
  <div class="steps"><?php foreach (['talk', 'map', 'build', 'run'] as $n => $s): ?><article class="step"><div class="step-top"><span>0<?= $n + 1 ?></span><span aria-hidden="true"><?= $n === 3 ? '↗' : '→' ?></span></div><h3 <?= i("step.$s.name") ?>><?= t("step.$s.name") ?></h3><p <?= i("step.$s.desc") ?>><?= t("step.$s.desc") ?></p><div class="step-output"><span <?= i('process.out') ?>><?= t('process.out') ?></span><strong <?= i("step.$s.out") ?>><?= t("step.$s.out") ?></strong></div></article><?php endforeach; ?></div>
</div></section>

<section class="section faq editorial-faq faq-focus" id="questions"><div class="wrap faq-grid"><div><p class="eyebrow" <?= i('faq.eyebrow') ?>><?= t('faq.eyebrow') ?></p><?= headline('faq.title') ?><a class="text-link conversation-link" href="#contact"><span <?= i('faq.more') ?>><?= t('faq.more') ?></span><span aria-hidden="true">↗</span></a></div><div class="faq-list"><?php for ($q = 1; $q <= 5; $q++): ?><details class="faq-item" name="project-questions" <?= $q === 1 ? 'open' : '' ?>><summary><span class="faq-number" aria-hidden="true">0<?= $q ?></span><span <?= i("faq.q$q") ?>><?= t("faq.q$q") ?></span><span class="faq-toggle" aria-hidden="true">+</span></summary><p <?= i("faq.a$q") ?>><?= t("faq.a$q") ?></p></details><?php endfor; ?></div></div></section>

<section class="ask" id="contact" aria-label="<?= t('ask.label') ?>" data-i18n-aria="ask.label"><div class="wrap">
  <p class="eyebrow ask-eyebrow" <?= i('ask.eyebrow') ?>><?= t('ask.eyebrow') ?></p>
  <form class="ask-form <?= $sent ? 'is-received' : '' ?>" action="contact.php" method="post" novalidate data-form>
    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
    <input type="hidden" name="lang" value="<?= $lang ?>" data-lang-field>
    <input type="hidden" name="t" value="<?= time() ?>">
    <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <p class="ask-sentence">
      <span <?= i('ask.s1') ?>><?= t('ask.s1') ?></span>
      <input class="blank" name="name" type="text" autocomplete="name" required maxlength="100" value="<?= e((string)($old['name'] ?? '')) ?>" placeholder="<?= t('ask.name.ph') ?>" data-i18n-ph="ask.name.ph" aria-label="<?= t('form.name') ?>" data-i18n-aria="form.name" <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
      <span <?= i('ask.s2') ?>><?= t('ask.s2') ?></span>
      <input class="blank" name="business" type="text" autocomplete="organization" maxlength="120" value="<?= e((string)($old['business'] ?? '')) ?>" placeholder="<?= t('ask.business.ph') ?>" data-i18n-ph="ask.business.ph" aria-label="<?= t('form.business') ?>" data-i18n-aria="form.business">.
      <span <?= i('ask.s3') ?>><?= t('ask.s3') ?></span>
      <select class="blank blank-select" name="area" aria-label="<?= t('ask.area.label') ?>" data-i18n-aria="ask.area.label">
        <?php foreach (AREAS as $a): ?><option value="<?= $a ?>" <?= ($old['area'] ?? 'ops') === $a ? 'selected' : '' ?> <?= i("ask.area.$a") ?>><?= t("ask.area.$a") ?></option><?php endforeach; ?>
      </select>
      <span <?= i('ask.s4') ?>><?= t('ask.s4') ?></span>
      <textarea class="blank blank-wide" name="message" rows="1" required maxlength="3000" placeholder="<?= t('ask.message.ph') ?>" data-i18n-ph="ask.message.ph" aria-label="<?= t('form.message') ?>" data-i18n-aria="form.message" <?= isset($errors['message']) ? 'aria-invalid="true"' : '' ?>><?= e((string)($old['message'] ?? '')) ?></textarea>
    </p>
    <div class="ask-foot">
      <button class="btn btn-ink ask-send" type="submit" data-submit><span <?= i('cta.wa') ?>><?= t('cta.wa') ?></span><span aria-hidden="true">↗</span></button>
      <p class="form-status" role="status" aria-live="polite" <?= $errors ? i(reset($errors)) : (isset($flash['error']) ? i($flash['error']) : '') ?>><?= $errors ? t(reset($errors)) : (isset($flash['error']) ? t($flash['error']) : '') ?></p>
      <p class="ask-note" <?= i('ask.note') ?>><?= t('ask.note') ?></p>
      <p class="ask-note ask-privacy" <?= i('ask.privacy') ?>><?= t('ask.privacy') ?></p>
      <?php if ($booking): ?><p class="ask-book"><a class="text-link" href="<?= e($booking) ?>" target="_blank" rel="noopener"><span <?= i('cta.book') ?>><?= t('cta.book') ?></span><span aria-hidden="true">↗</span></a></p><?php endif; ?>
      <?php if ($email): ?><p class="ask-email"><span <?= i('form.emailAlt') ?>><?= t('form.emailAlt') ?></span> <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif; ?>
    </div>
    <div class="received" <?= $sent ? '' : 'hidden' ?> data-received>
      <div class="board board-mini" aria-hidden="true">
        <div class="board-row"><span class="seg" data-received-board><?= cells($T['form.received.board'], 9) ?></span></div>
      </div>
      <p class="received-title" <?= i('form.received.title') ?>><?= t('form.received.title') ?></p>
      <p><span <?= i('form.received.body') ?>><?= t('form.received.body') ?></span> <a href="<?= e($waBase) ?>" data-wa-open <?= i('ask.open') ?>><?= t('ask.open') ?></a></p>
    </div>
  </form>
</div></section>
</main>
<div class="footer-stage closing-footer"><footer class="footer wrap"><div class="footer-top"><a href="#top" aria-label="&amp;Now"><img src="assets/brand/logo.webp" alt="&amp;Now" width="900" height="375"></a><div class="footer-about"><p <?= i('footer.tag') ?>><?= t('footer.tag') ?></p><nav aria-label="<?= t('footer.nav') ?>" data-i18n-aria="footer.nav"><a href="#work" <?= i('nav.work') ?>><?= t('nav.work') ?></a><a href="#services" <?= i('nav.services') ?>><?= t('nav.services') ?></a><a href="#questions" <?= i('nav.questions') ?>><?= t('nav.questions') ?></a></nav></div><a class="text-link" href="#top"><span <?= i('footer.top') ?>><?= t('footer.top') ?></span><span aria-hidden="true">↑</span></a></div><div class="footer-bottom"><span>&copy; <?= date('Y') ?> &amp;Now</span><span <?= i('footer.location') ?>><?= t('footer.location') ?></span></div></footer></div>
<?php if (strpos($config['whatsapp_number'], 'X') === false): // shown once config.php has the real number ?><a class="wa-float" href="<?= e($waBase) ?>" target="_blank" rel="noopener" data-wa-open aria-label="<?= t('wa.float') ?>" data-i18n-aria="wa.float"><svg viewBox="0 0 448 512" aria-hidden="true"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg><span <?= i('wa.label') ?>><?= t('wa.label') ?></span></a><?php endif; ?>
<script type="application/json" id="i18n"><?= json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="assets/js/site.js?v=<?= $v ?>" defer></script>
</body></html>
