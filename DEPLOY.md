# Updating andnowhq.com

The &Now website lives on Hostinger, on the same hosting plan as ledflowapp.com.
This folder (`andnow-v2`) is the master copy. The live site is a copy of it -
edit here, then push the update out using one of the two ways below.

## The quick way - ask Claude

Open Claude Code in this folder and say:

> deploy andnow - follow DEPLOY.md

Claude packs the site, uploads it, switches it live and checks the result.
The steps it follows are in "What Claude does" at the bottom.

## The manual way - hPanel, no Claude

1. **Pack the site.** In Terminal, in this folder, run:

       ./deploy.sh

   This creates `andnow-site.zip` - only the files the live site needs. Design
   notes, test files, screenshots and the `.md` files stay on the laptop. It
   also checks every PHP file (the code that builds the page) for typos; if
   one is broken, it stops and says which, and nothing should be uploaded.

2. **Upload it.** hPanel → Websites → andnowhq.com → File Manager → open
   `public_html` → Upload → pick `andnow-site.zip`.

3. **Unpack it.** Right-click the zip → Extract → into `public_html` itself
   (not a new subfolder) → say yes to overwriting files.

4. **Delete the zip** from `public_html` afterwards.

5. **Check it** - see "After every update" below.

Note: this way only adds and overwrites. If you **deleted or renamed** a file
locally, the old one stays on the server - delete it there by hand, or use the
Claude way, which replaces everything.

## After every update

- Open https://andnowhq.com in a private/incognito window (avoids seeing an old
  saved copy). Check the thing you changed is there.
- Switch to Bahasa (`?lang=id`) and glance at it too.
- If the old version keeps showing: hPanel → Websites → andnowhq.com →
  Performance → Clear cache, then reload.

## Things to know

- **Enquiries from the contact form are safe across updates.** They are saved
  to `andnow-data/inquiries.jsonl`, one folder *above* `public_html`
  (`domains/andnowhq.com/andnow-data/`). An update wipes and replaces
  `public_html` only, so it never touches them. Download that file from File
  Manager to read or back them up. Do not move the folder back into the site
  folder - the next update would delete every enquiry.
- **`config.php` holds the site settings** (email, WhatsApp number, booking
  link). It is uploaded with every update, so change settings here in this
  folder, not on the server - a change made only on the server is overwritten
  next time.
- **Settings and code are not reachable from the web.** `.htaccess` (the web
  server's rule file) blocks `config.php`, `lib/`, `data/` and any `.md` file.
- **New pages or images need nothing extra** as long as they sit in `assets/`
  or `lib/`, or are added to the file list inside `deploy.sh`. A brand-new file
  at the top level of this folder (next to `index.php`) must be added to that
  list, or it will not be uploaded.

## What Claude does

For Claude, using the Hostinger tools (`hostinger` plugin). Account details:
username `u714944329`, domain `andnowhq.com`, hosting order `1008725659`.

1. Run `./deploy.sh`. Stop if it fails.
2. `hosting_files_generate-upload-url` with `{username, domain}` → `url`,
   `auth_key`, `rest_auth_key` (fresh each time, they expire - never save them).
3. Upload `andnow-site.zip` by TUS with curl, as the
   `hostinger:hosting-deploy-static-site` skill shows: `POST` then `PATCH` to
   `{url}/andnow-site.zip?override=true`. Done when the `Upload-Offset` header
   equals the file size.
4. `hosting_websites_deploy-static-site-archive` with
   `{username, domain, archive_path: "andnow-site.zip"}`. This wipes
   `public_html` and unpacks the zip. Enquiries sit outside it and survive.
5. Verify, do not trust a `200` alone:
   - `https://andnowhq.com/` contains `Better processes. Better digitalisation.`
     (or whatever text was just changed)
   - `https://andnowhq.com/config.php` → 403, `/lib/strings.php` → 404
   - If the page is stale, `hosting_cache_clear` with `{domain}` and check once more.
6. Report what went live and the check results.
