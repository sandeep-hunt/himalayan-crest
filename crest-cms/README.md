# Crest Himalaya CMS

Laravel 12 content management system and REST API for the Crest Himalaya website.
The website is the existing Vite site in `../Archive`. It reads its content from this CMS's API.

```
Visitor ─► Website (Vite, ../Archive) ──GET /api/v1/site?lang=xx──► Laravel CMS ─► MySQL
Crest team ─► /admin (Blade admin panel, one fixed login) ─► MySQL
```

## What the client can edit (Admin panel, `/admin`)

| Area | Where in the admin |
|---|---|
| Navbar buttons: "Plan Your Trip" text, show / hide WhatsApp and Plan buttons, WhatsApp pre-filled message | Homepage Sections → Navbar |
| Every section's buttons and labels (e.g. "View All Treks", "Browse Treks", currency) | Homepage Sections → that section ("Buttons & labels in this section") |
| Footer labels, Plan Your Trip form texts, trek pop-up labels | Homepage Sections → Footer / Plan Your Trip form / Trek details |
| Hero heading, supporting text, value badges, 5 slideshow photos and captions | Homepage Sections → Hero |
| Journey categories (the 4 cards) | Journey Categories |
| Featured treks and all trek details: name, description, duration, difficulty, max altitude, best season, price, region, hero image, gallery, highlights, itinerary, included / not included | Treks |
| Fixed departure dates and seats | Fixed Departures |
| "Why Crest Himalaya" text, founder quote, 4 reasons | Homepage Sections → Why Crest Himalaya |
| Team / founder: name, designation, experience, regions, biography, profile photo, display on/off | Team Members |
| Journey support (4 columns) | Homepage Sections → Journey Support |
| Traveler stories / reviews: name, country, trek, text, rating, photo, publish on/off | Traveler Stories |
| Travel guide ("Before You Trek") with full guide pages | Travel Guides |
| Articles / blog with full article pages | Articles / Blog |
| FAQs: add, edit, delete, publish on/off | FAQs |
| Videos & reels | Videos & Reels |
| Header menus, dropdown links (with columns and buttons), footer link columns | Menu (header & footer) |
| All buttons and labels in one list, incl. the chat assistant | Buttons & Labels |
| Brand name, company, email, phones, WhatsApp, address, social links, footer, association badges, payment badges | Settings |
| Browser title and Google description | Settings → SEO |
| Admin name and password | Your account (bottom-left) |
| Website languages (only when multi-language is on, see below) | Languages, Interface Text |
| Trip facts used by the chat assistant (deposit, pickup, packing list…) | Settings → Trip Info |

Every list has search, filters, a publish/unpublish toggle, create, edit and delete (the Menu is edit-only: no add, delete or hide). Every save updates the public
API immediately (the API cache is cleared automatically).

### Languages
Multi-language is **on** (`CMS_MULTILINGUAL=true`). Admin → **Languages** adds, edits, shows/hides and deletes
languages (about 100 are suggested; any ISO code works). **English is the main language and cannot be deleted.**

Translation is automatic with the **Google Cloud Translation API (v2)**:
- `.env`: `GOOGLE_TRANSLATE_API_KEY=...`
- Only changed English fields (or fields without a translation yet) are translated on save; the rest is kept.
- Numbers, dates, weekdays, month names, `{placeholders}`, links and emails are never translated (native digits are converted back to 0-9).
- **Switching a language on** (or adding it with "Translate now and show") translates all English content into it
  step by step with a progress popup (`POST /admin/languages/{code}/translate`), then shows it. Switching off only hides it.
- **Saving any edit page** translates the changed English fields into every **shown** language (`POST /admin/translate`,
  progress popup), then saves. Hidden languages are skipped (re-translated when switched on). If a translation
  fails nothing is saved and the popup offers *Retry*.
- Only English is editable; the other language tabs are read-only.
- Translations are cached in `translation_memory`, so unchanged text is never sent to Google twice.
- The website reads each language from the database (`/api/v1/site?lang=fr`); Google is never called per visitor.
- The chat assistant is English only (chat texts, treks and FAQs in English on every language); `trek.currency` is not translated.
- Deleting a language deletes its translations.

### Chat assistant (website)
The assistant in the bottom-right corner runs in the visitor's browser, with **no AI service and no API key**.
It answers buttons (find a trek, dates, prices, packing, FAQs, book) and typed questions from the website content
loaded from the CMS (treks, departures, FAQs, trip info, contact details), so editing that content in the admin
updates the assistant too. "Talk to Team" hands the visitor over to WhatsApp or email.

## Admin login

There is **one fixed admin account**. There is no registration, no "forgot password" and no user management.

| | |
|---|---|
| Address | `/admin` (e.g. http://localhost:8000/admin) |
| Email | `admin@cresthimalaya.com` |
| Password | `ChangeMe@2026` |

Only this email can sign in (5 wrong attempts → 5-minute lock). The login comes from `ADMIN_EMAIL` / `ADMIN_PASSWORD`
in `.env`. **To change it** (do this before going live): edit those two lines in `.env`, then run

```bash
php artisan admin:sync
```

## Local development

Requirements: PHP 8.2+ (extensions: pdo_mysql, mbstring, openssl, curl, fileinfo, gd, intl, zip), Composer,
MySQL 8 / MariaDB 10.6+, Node 20.19+ / 22.

```bash
cd crest-cms
composer install
cp .env.example .env          # then edit the DB_ lines (below)
php artisan key:generate
php artisan migrate --seed    # creates tables, the admin account and all current website content
php artisan serve             # http://localhost:8000/admin

cd ../Archive
npm install
npm run dev                   # http://localhost:5173  (reads VITE_CMS_URL from Archive/.env)
```

### MySQL settings (`crest-cms/.env`)
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crest_himalaya
DB_USERNAME=your_user
DB_PASSWORD=your_password
```
Create the database first: `CREATE DATABASE crest_himalaya CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`

## Deploying on Hostinger

1. **Database:** hPanel → Databases → create a MySQL database + user. Note the name, user and password.
2. **CMS (subdomain recommended, e.g. `cms.cresthimalaya.com`):**
   - Upload the `crest-cms` folder (without `vendor/` and `node_modules/`) to e.g. `~/crest-cms`.
   - Point the subdomain's document root to `~/crest-cms/public` (hPanel → Domains → Subdomains).
     If the plan cannot change the document root, upload `crest-cms` outside `public_html`, copy the contents of
     `crest-cms/public` into the subdomain folder, and in that folder's `index.php` change the two
     `__DIR__.'/../'` paths to point at `~/crest-cms/`.
   - SSH into the account: `cd ~/crest-cms && composer install --no-dev --optimize-autoloader`
   - `cp .env.example .env`, then set:
     ```
     APP_ENV=production
     APP_DEBUG=false
     APP_URL=https://cms.cresthimalaya.com
     FRONTEND_URL=https://cresthimalaya.com,https://www.cresthimalaya.com
     DB_CONNECTION=mysql  (+ DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD from step 1)
     ADMIN_PASSWORD=<a strong password>
     ```
   - `php artisan key:generate && php artisan migrate --force --seed`
   - `php artisan config:cache && php artisan route:cache && php artisan view:cache`
   - Make sure `storage/`, `bootstrap/cache/` and `public/uploads/` are writable (755 on Hostinger).
   - Set PHP to 8.2 or newer for the subdomain (hPanel → Advanced → PHP Configuration).
3. **Website:** in `Archive/.env` set `VITE_CMS_URL=https://cms.cresthimalaya.com`, run `npm run build`, and upload
   the **contents** of `Archive/dist/` to `public_html`.
4. Open `https://cms.cresthimalaya.com/admin` and sign in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

After changing `.env` on the server, run `php artisan config:cache` again (and `php artisan admin:sync` if you
changed the admin login).

## Public API (read-only, JSON)

All endpoints accept `?lang=<code>` (one of the languages shown on the website; anything else → English).
Rate limit 120 requests / minute.

| Endpoint | Returns |
|---|---|
| `GET /api/v1/site` | everything the homepage needs in one request (used by the website) |
| `GET /api/v1/treks`, `/treks/{slug}` | treks (`/treks/{slug}` includes its departures) |
| `GET /api/v1/departures` | upcoming fixed departures |
| `GET /api/v1/categories`, `/team`, `/reviews`, `/faqs`, `/videos` | lists |
| `GET /api/v1/guides`, `/guides/{slug}` | travel guides (single includes the full text) |
| `GET /api/v1/posts`, `/posts/{slug}` | articles (single includes the full text) |
| `GET /api/v1/settings`, `/sections`, `/ui`, `/languages` | settings, homepage section copy, interface text, website languages |

Responses are cached and refreshed automatically whenever content is saved in the admin.

## Code map

| Path | What |
|---|---|
| `config/cms.php` | **All editable content is defined here**: built-in languages, list modules (fields, columns), homepage sections, settings |
| `config/cms_ui.php` | Interface text keys with their English defaults |
| `config/language_catalog.php` | Language suggestions for Admin → Languages |
| `app/Http/Controllers/Admin/ResourceController.php` | Generic list / create / edit / delete / publish for every module in `cms.resources` |
| `app/Http/Controllers/Admin/*` | Login, dashboard, sections, settings, languages, interface text |
| `app/Http/Middleware/EnsureFixedAdmin.php` | Only the fixed admin account may use the admin panel |
| `app/Support/Fields.php` | Field types → validation rules, stored values, translations |
| `app/Support/Locales.php` | Website languages (stored in the `settings` table, key `languages`) |
| `app/Support/SiteContent.php` | Builds the API JSON for a language (with English fallback) |
| `app/Http/Controllers/Api/ContentController.php` | Public read-only API |
| `routes/console.php` | `php artisan admin:sync` (set the fixed admin login from `.env`) |
| `database/migrations/` | MySQL schema |
| `database/seeders/ContentSeeder.php` | The current website content moved into the CMS |
| `resources/views/admin/*`, `public/cms-assets/*` | Admin panel (Blade, plain CSS/JS, no build step) |
| `public/uploads/` | Uploaded images (`uploads/seed/` = original website photos) |

Adding a field to an existing module = one migration column + one line in `config/cms.php` (+ use it in the website).

## Not included (per proposal §12–13)
Online booking/payments, CRM, WhatsApp Business API, email automation, admin roles / multiple admin users,
AI chatbot service, analytics, translation services, hosting/domain costs.
