# Crest Himalaya website

Scroll-driven site for Crest Himalaya Treks & Expeditions. Vite + vanilla JS, GSAP ScrollTrigger and Lenis smooth scroll. The hero is a 5-photo slideshow under the slogan.

Home page order (client feedback, Sep 2026): Hero, Find Your Next Adventure, Featured Treks, Craft Your Crest Experience, Why Choose Us (Why Crest / Team / Journey Support), Traveler Stories, Before You Trek, Blog, Videos & Reels, FAQ, Footer. Layouts for Find, Treks, Craft, Prepare and Blog follow the Figma screenshots in `../Assets/Figma Screenshotss/`.

## Content comes from the CMS
The site's content (treks, departures, team, stories, guides, articles, FAQs, homepage copy, menus, contact details,
buttons & labels) is managed in the **Crest Himalaya CMS** (`../crest-cms`, Laravel + MySQL) and loaded from
its API when the page opens. **Edit content in the admin panel, not in the code.** See `../crest-cms/README.md`.

Set the CMS address in `.env` (copy `.env.example`):

    VITE_CMS_URL=https://cms.cresthimalaya.com

If the CMS can't be reached (or `VITE_CMS_URL` is empty), the site shows the built-in content from `index.html` and
`src/data.js`, so it never breaks.

## Run it
    npm install
    npm run dev        # http://localhost:5173 (start the CMS too: cd ../crest-cms && php artisan serve)
    npm run build      # static site in dist/ (upload the *contents* of dist/)

## Where things live
- `src/cms.js`: loads content from the CMS API, interface translations (`t()`), language switching, article Markdown
- `src/render.js`: puts CMS content into the fixed sections (hero, categories, why, team, support, stories, footer, contacts)
- `index.html`: page layout and the built-in English copy. `data-s="section.field"` and `data-i18n="key"` mark text the CMS replaces
- `src/data.js`: built-in fallback content and the header `MENU` structure (labels are translated through CMS interface text)
- `src/main.js`: scroll animations, filters, trek drawer, article / guide / team panels, booking form
- `src/menu.js`: header menus and footer link columns from CMS → Menu (peak climbs and departures also list CMS treks)
- `src/chat.js`: trek assistant. Runs in the browser with no AI service or API key: buttons and typed questions are
  answered from the CMS content (treks, departures, FAQs, trip info). "Talk to Team" hands off to WhatsApp
- `src/style.css`: brand colours, fonts and layout (CMS additions and right-to-left rules for Arabic at the end)
- `public/img/`: built-in photos (Wikimedia Commons, credited in the footer "Photo credits" dialog via `src/credits.json`)

## Languages
English only for now. When multi-language is switched on in the CMS (`CMS_MULTILINGUAL=true`), a language switcher
appears in the header (and phone menu) with the languages enabled in the CMS. The choice is kept
in the browser and in the `?lang=` URL parameter. Arabic switches the page to right-to-left.

## Where enquiries go
The Plan Your Trip form opens the visitor's email app with a pre-filled message to the email set in the CMS (Settings).
The visitor presses send. Nothing is stored on a server. WhatsApp buttons use the WhatsApp number from the CMS.

## Before launch
- Confirm trek prices, durations and departure dates in the CMS.
- Replace the three **sample reviews** (Traveler Stories, named "Sample guest") with real guest reviews.
- Confirm social links (CMS → Settings → Social Links).
- Replace Wikimedia photos with your own trip photos when you have them, and update `src/credits.json`.
- Set absolute `og:image` / `og:url` once the domain is live.
