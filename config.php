<?php
// Site settings. Edit before going live.

return [
    // Live address of the site. Used for search engines, link previews and the sitemap.
    'site_url' => 'https://andnowhq.com',

    // Shown on the page ("Prefer email?") and in search results. Empty = the line is hidden.
    'contact_email' => 'hello@andnowhq.com',

    // Used by the floating button and the contact form.
    'whatsapp_number' => '6285173336932',

    // Where a copy of each enquiry is emailed. Empty string = save only, no email.
    'notify_email' => 'hello@andnowhq.com',

    // TODO: booking page for the free 30-minute call (e.g. a Cal.com link). Empty = the link is hidden.
    'booking_url' => '',

    // Enquiries are appended here, one JSON object per line.
    // Outside public_html: a Hostinger deploy wipes public_html, so enquiries kept there would be lost.
    'data_dir' => dirname(__DIR__) . '/andnow-data',
];
