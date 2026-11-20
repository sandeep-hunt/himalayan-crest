<?php

/*
|--------------------------------------------------------------------------
| Crest Himalaya CMS configuration
|--------------------------------------------------------------------------
| Everything the admin panel can edit is described here:
|  - locales:   website languages (English is the master/fallback language)
|  - resources: list-type content (treks, team, reviews, ...) -> generic CRUD screens
|  - sections:  homepage section copy (hero, why, support, ...)
|  - settings:  company / contact / chatbot / language settings
|
| Field keys:  name, label, type, tr (translatable), rules, help, options, required
| Field types: text, textarea, markdown, number, select, checkbox, image, gallery,
|              lines (one item per line), date, multicheck, trek, group (sections only)
*/

$months = ['1' => 'Jan', '2' => 'Feb', '3' => 'Mar', '4' => 'Apr', '5' => 'May', '6' => 'Jun', '7' => 'Jul', '8' => 'Aug', '9' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'];

return [

    // Website languages. English is the main language; the others are added in Admin -> Languages
    // (stored in the `languages` setting) and translated automatically with Google Translate.
    'locales' => [
        'en' => ['name' => 'English', 'native' => 'English'],
    ],
    'fallback_locale' => 'en',

    // Multi-language website: Languages page in the admin, language tabs on every edit page and the
    // language switcher on the website. Only English is edited; the other languages are translated
    // automatically with Google Translate (see 'translate'). CMS_MULTILINGUAL=false = English only.
    'multilingual' => (bool) env('CMS_MULTILINGUAL', true),

    // Google Cloud Translation (v2). The key lives in .env (GOOGLE_TRANSLATE_API_KEY).
    'translate' => [
        'key' => env('GOOGLE_TRANSLATE_API_KEY'),
        'endpoint' => 'https://translation.googleapis.com/language/translate/v2',
        'timeout' => 30,
    ],

    // Allowed origins for the public API (comma separated in .env)
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    /*
    |----------------------------------------------------------------------
    | Resources (list content)
    |----------------------------------------------------------------------
    */
    'resources' => [

        'treks' => [
            'model' => App\Models\Trek::class,
            'label' => 'Treks',
            'singular' => 'Trek',
            'icon' => 'mountain',
            'group' => 'Content',
            // List view: 'sub' = small lines under the title, 'formats' = how values show,
            // 'toggles' = switch on/off with one click, 'filters' = dropdowns above the list.
            'columns' => ['image' => '', 'name' => 'Trek', 'category' => 'Category', 'days' => 'Duration', 'max_altitude' => 'Max altitude', 'difficulty' => 'Difficulty', 'price' => 'Price from', 'sort_order' => 'Order', '_toggles' => 'On website'],
            'sub' => ['area', 'badge'],
            'formats' => ['days' => '{v} days', 'max_altitude' => '{v} m', 'price' => 'USD {v}'],
            'toggles' => ['is_featured' => ['Featured', 'Not featured'], 'is_published' => ['Published', 'Hidden']],
            'filters' => ['category', 'is_featured', 'is_published'],
            'search' => ['name', 'slug', 'area', 'region'],
            'fields' => [
                ['section' => 'Basic information', 'name' => 'name', 'label' => 'Trek name', 'type' => 'text', 'tr' => true, 'required' => true],
                ['name' => 'slug', 'label' => 'URL key / ID', 'type' => 'text', 'required' => true, 'half' => true, 'help' => 'Short unique id used in links and by the chatbot, e.g. "ebc".', 'rules' => 'alpha_dash|max:60'],
                ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'required' => true, 'half' => true, 'options' => ['popular' => 'Popular trek', 'peak' => 'Peak climbing', 'cultural' => 'Cultural tour', 'short' => 'Short trek']],
                ['name' => 'badge', 'label' => 'Badge on the card', 'type' => 'text', 'tr' => true, 'help' => 'Optional, e.g. "Best seller", "Popular", "Adventure".'],
                ['name' => 'summary', 'label' => 'Description', 'type' => 'textarea', 'tr' => true, 'required' => true],

                ['section' => 'Trip facts & price', 'name' => 'price', 'label' => 'Price from (USD per person)', 'type' => 'number', 'required' => true, 'half' => true, 'rules' => 'integer|min:0'],
                ['name' => 'days', 'label' => 'Duration (days)', 'type' => 'number', 'required' => true, 'half' => true, 'rules' => 'integer|min:1|max:90'],
                ['name' => 'max_altitude', 'label' => 'Maximum altitude (m)', 'type' => 'number', 'required' => true, 'half' => true, 'rules' => 'integer|min:0|max:9000'],
                ['name' => 'difficulty', 'label' => 'Difficulty (short, on the card)', 'type' => 'text', 'tr' => true, 'required' => true, 'half' => true, 'help' => 'e.g. "Moderate".'],
                ['name' => 'grade', 'label' => 'Difficulty grade (details)', 'type' => 'text', 'tr' => true, 'half' => true, 'help' => 'e.g. "Moderate to challenging".'],
                ['name' => 'season', 'label' => 'Best season', 'type' => 'text', 'tr' => true, 'half' => true, 'help' => 'e.g. "Mar to May, Sep to Dec".'],
                ['name' => 'area', 'label' => 'Area label (on the card)', 'type' => 'text', 'tr' => true, 'half' => true, 'help' => 'e.g. "Everest region".'],
                ['name' => 'region', 'label' => 'Region', 'type' => 'text', 'tr' => true, 'half' => true, 'help' => 'e.g. "Khumbu". The English value is used by the "By Region" menu.'],
                ['name' => 'start_point', 'label' => 'Starts in', 'type' => 'text', 'tr' => true, 'half' => true, 'help' => 'e.g. "Lukla".'],
                ['name' => 'months', 'label' => 'Best months (used by the chatbot trek finder)', 'type' => 'multicheck', 'options' => $months],
                ['name' => 'tags', 'label' => 'Experience tags (menu filters)', 'type' => 'multicheck', 'options' => ['classic' => 'Classic', 'remote' => 'Remote', 'family' => 'Family']],

                ['section' => 'Photos', 'name' => 'image', 'label' => 'Hero / featured image', 'type' => 'image'],
                ['name' => 'gallery', 'label' => 'Gallery images', 'type' => 'gallery'],

                ['section' => 'Highlights & itinerary', 'name' => 'highlights', 'label' => 'Highlights', 'type' => 'lines', 'tr' => true, 'help' => 'One highlight per line.'],
                ['name' => 'itinerary', 'label' => 'Itinerary', 'type' => 'lines', 'tr' => true, 'help' => 'One day (or range of days) per line, e.g. "Day 1: Arrive Kathmandu".'],

                ['section' => 'What is included', 'name' => 'inclusions', 'label' => 'Included', 'type' => 'lines', 'tr' => true, 'half' => true, 'help' => 'One item per line. Empty = the default list from Settings → Trip Info.'],
                ['name' => 'exclusions', 'label' => 'Not included', 'type' => 'lines', 'tr' => true, 'half' => true, 'help' => 'One item per line.'],

                ['section' => 'Display on the website', 'name' => 'is_featured', 'label' => 'Show in "Featured Himalayan Treks"', 'type' => 'checkbox', 'half' => true],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox', 'half' => true],
                ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'rules' => 'integer', 'help' => 'Lower numbers show first. Featured treks always come before the others.'],
            ],
        ],

        'departures' => [
            'model' => App\Models\Departure::class,
            'label' => 'Fixed Departures',
            'singular' => 'Departure',
            'icon' => 'calendar',
            'group' => 'Content',
            'columns' => ['trek_name' => 'Trek', 'date' => 'Departure date', 'seats' => 'Seats left', '_toggles' => 'On website'],
            'formats' => ['seats' => '{v} seats'],
            'toggles' => ['is_published' => ['Published', 'Hidden']],
            'filters' => ['trek_id', 'is_published'],
            'order' => ['date', 'asc'],
            'fields' => [
                ['name' => 'trek_id', 'label' => 'Trek', 'type' => 'trek', 'required' => true],
                ['name' => 'date', 'label' => 'Departure date', 'type' => 'date', 'required' => true, 'half' => true],
                ['name' => 'seats', 'label' => 'Seats left', 'type' => 'number', 'required' => true, 'half' => true, 'rules' => 'integer|min:0|max:99'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
            ],
        ],

        'menu' => [
            'model' => App\Models\MenuItem::class,
            'label' => 'Menu (header & footer)',
            'per_page' => 500, // show every menu with its links on one page
            'singular' => 'Menu item',
            'icon' => 'menu',
            'group' => 'Homepage',
            // The client edits the existing menus only: no adding, deleting or hiding (client request)
            'edit_only' => true,
            'columns' => ['title' => 'Menu / link', 'place' => 'Where', 'opens' => 'What it does', 'sort_order' => 'Order'],
            'sub' => ['subtitle', 'column'],
            'toggles' => [],
            'filters' => ['parent_id'],
            'search' => ['title', 'target'],
            'fields' => [
                ['section' => 'Menu item', 'name' => 'parent_id', 'label' => 'Belongs to', 'type' => 'menuparent', 'help' => 'The menu this link is in. Choose another menu to move the link there.'],
                ['name' => 'title', 'label' => 'Text shown', 'type' => 'text', 'tr' => true, 'required' => true, 'help' => 'e.g. "Treks" for a menu, or "Everest" for a link.'],
                ['name' => 'subtitle', 'label' => 'Small text under it (optional)', 'type' => 'text', 'tr' => true, 'only' => 'link', 'help' => 'e.g. "6,476 m · 18 days" or "Temples, stupas and old Durbar squares".'],

                ['section' => 'Menu settings', 'name' => 'location', 'label' => 'Where the menu appears', 'type' => 'select', 'only' => 'top', 'options' => ['header' => 'Header (top navigation bar and phone menu)', 'footer' => 'Footer (a column of links)']],
                ['name' => 'style', 'label' => 'Dropdown style', 'type' => 'select', 'only' => 'top', 'options' => ['list' => 'Simple dropdown list', 'mega' => 'Large dropdown with columns (uses each link\'s "Column heading")', 'peaks' => 'Peak climbs from the Treks list (automatic) + the links below', 'departures' => 'Upcoming fixed departures (automatic)', 'guides' => 'Travel guides (automatic) + the links below'], 'help' => 'Header menus only.'],

                ['section' => 'Link settings', 'name' => 'action', 'label' => 'When clicked', 'type' => 'select', 'only' => 'link', 'options' => App\Models\MenuItem::ACTIONS],
                ['name' => 'target', 'label' => 'Details for "When clicked"', 'type' => 'text', 'only' => 'link', 'help' => 'Section: journeys, find, plan, why, crew, support, stories, prepare, blog, videos, faq, start · Show treks: all, trek, popular, peak, cultural, short, region:Everest (any region name), exp:classic / exp:short / exp:high / exp:remote / exp:family · Open a trek: the trek\'s URL key, e.g. ebc · Plan form: an optional message to pre-fill · Chat topic: find, availability, prices, packing, faqs, book, team, travel, safety, fitness, expeditions · Web address: https://…', 'rules' => 'max:300'],
                ['name' => 'column', 'label' => 'Column heading (large dropdowns only)', 'type' => 'text', 'tr' => true, 'only' => 'link', 'help' => 'Links with the same heading are grouped in one column, e.g. "By Region".'],
                ['name' => 'is_button', 'label' => 'Show as a button (large dropdowns only)', 'type' => 'checkbox', 'only' => 'link'],

                ['section' => 'Display', 'name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'rules' => 'integer', 'help' => 'Lower numbers show first (menus left to right, links top to bottom).'],
            ],
        ],

        'categories' => [
            'model' => App\Models\Category::class,
            'label' => 'Journey Categories',
            'singular' => 'Journey Category',
            'icon' => 'grid',
            'group' => 'Homepage',
            'columns' => ['image' => 'Image', 'title' => 'Title', 'action' => 'Opens', 'is_published' => 'Published'],
            'search' => ['title'],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'tr' => true, 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'tr' => true],
                ['name' => 'link_label', 'label' => 'Link text', 'type' => 'text', 'tr' => true, 'help' => 'e.g. "Explore treks →"'],
                ['name' => 'action', 'label' => 'Clicking the card', 'type' => 'select', 'required' => true, 'options' => ['trek' => 'Shows trekking (non-peak) treks', 'peak' => 'Shows peak climbs', 'cultural' => 'Shows cultural tours', 'popular' => 'Shows popular treks', 'short' => 'Shows short treks', 'plan' => 'Opens the Plan Your Trip form']],
                ['name' => 'icon', 'label' => 'Icon', 'type' => 'select', 'options' => ['up' => 'Arrow up (trekking)', 'peak' => 'Peak', 'circle' => 'Circles (culture)', 'star' => 'Star (custom)']],
                ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
                ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'rules' => 'integer'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
            ],
        ],

        'team' => [
            'model' => App\Models\TeamMember::class,
            'label' => 'Team Members',
            'singular' => 'Team Member',
            'icon' => 'users',
            'group' => 'Content',
            'columns' => ['photo' => 'Photo', 'name' => 'Name', 'designation' => 'Designation', 'is_published' => 'Displayed'],
            'search' => ['name'],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'tr' => true, 'required' => true],
                ['name' => 'experience', 'label' => 'Experience', 'type' => 'text', 'tr' => true, 'help' => 'e.g. "20+ years experience"'],
                ['name' => 'regions', 'label' => 'Regions / specialities', 'type' => 'text', 'tr' => true, 'help' => 'e.g. "Everest · Annapurna · Manaslu"'],
                ['name' => 'bio', 'label' => 'Short biography', 'type' => 'textarea', 'tr' => true],
                ['name' => 'photo', 'label' => 'Profile image', 'type' => 'image', 'help' => 'Square portrait. If empty, initials are shown.'],
                ['name' => 'background', 'label' => 'Background photo (desktop gallery)', 'type' => 'image'],
                ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'rules' => 'integer'],
                ['name' => 'is_published', 'label' => 'Display on website', 'type' => 'checkbox'],
            ],
        ],

        'reviews' => [
            'model' => App\Models\Review::class,
            'label' => 'Traveler Stories',
            'singular' => 'Traveler Story',
            'icon' => 'quote',
            'group' => 'Content',
            'columns' => ['photo' => 'Photo', 'traveller_name' => 'Traveller', 'trek_label' => 'Trek / package', 'is_published' => 'Published'],
            'search' => ['traveller_name'],
            'fields' => [
                ['name' => 'traveller_name', 'label' => 'Traveller name', 'type' => 'text', 'required' => true],
                ['name' => 'country', 'label' => 'Country', 'type' => 'text', 'tr' => true],
                ['name' => 'trek_label', 'label' => 'Trek / package', 'type' => 'text', 'tr' => true],
                ['name' => 'text', 'label' => 'Review text', 'type' => 'textarea', 'tr' => true, 'required' => true],
                ['name' => 'rating', 'label' => 'Rating (1-5)', 'type' => 'number', 'rules' => 'integer|min:1|max:5'],
                ['name' => 'photo', 'label' => 'Photo', 'type' => 'image'],
                ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'rules' => 'integer'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
            ],
        ],

        'guides' => [
            'model' => App\Models\Guide::class,
            'label' => 'Travel Guides',
            'singular' => 'Travel Guide',
            'icon' => 'compass',
            'group' => 'Content',
            'columns' => ['title' => 'Title', 'icon' => 'Icon', 'is_published' => 'Published'],
            'search' => ['title', 'slug'],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'tr' => true, 'required' => true],
                ['name' => 'slug', 'label' => 'URL key', 'type' => 'text', 'required' => true, 'rules' => 'alpha_dash|max:80'],
                ['name' => 'summary', 'label' => 'Short description (card)', 'type' => 'textarea', 'tr' => true],
                ['name' => 'content', 'label' => 'Content', 'type' => 'markdown', 'tr' => true],
                ['name' => 'icon', 'label' => 'Icon', 'type' => 'select', 'options' => ['plane' => 'Plane', 'sun' => 'Sun', 'pack' => 'Backpack', 'peak' => 'Peak', 'shield' => 'Shield', 'permit' => 'Document', 'pin' => 'Map pin', 'card' => 'Card']],
                ['name' => 'image', 'label' => 'Featured image', 'type' => 'image'],
                ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'rules' => 'integer'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
            ],
        ],

        'posts' => [
            'model' => App\Models\Post::class,
            'label' => 'Articles / Blog',
            'singular' => 'Article',
            'icon' => 'book',
            'group' => 'Content',
            'columns' => ['image' => 'Image', 'title' => 'Title', 'published_at' => 'Date', 'is_published' => 'Published'],
            'order' => ['published_at', 'desc'],
            'search' => ['title', 'slug'],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'tr' => true, 'required' => true],
                ['name' => 'slug', 'label' => 'URL key', 'type' => 'text', 'required' => true, 'rules' => 'alpha_dash|max:120'],
                ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'tr' => true, 'help' => 'e.g. "Guide", "Planning", "Gear".'],
                ['name' => 'published_at', 'label' => 'Publish date', 'type' => 'date', 'required' => true],
                ['name' => 'excerpt', 'label' => 'Excerpt (card)', 'type' => 'textarea', 'tr' => true],
                ['name' => 'content', 'label' => 'Content', 'type' => 'markdown', 'tr' => true],
                ['name' => 'image', 'label' => 'Featured image', 'type' => 'image'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
            ],
        ],

        'faqs' => [
            'model' => App\Models\Faq::class,
            'label' => 'FAQs',
            'singular' => 'FAQ',
            'icon' => 'help',
            'group' => 'Content',
            'columns' => ['question' => 'Question', 'is_published' => 'Published'],
            'search' => ['question'],
            'fields' => [
                ['name' => 'question', 'label' => 'Question', 'type' => 'text', 'tr' => true, 'required' => true],
                ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'tr' => true, 'required' => true],
                ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'rules' => 'integer'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
            ],
        ],

        'videos' => [
            'model' => App\Models\Video::class,
            'label' => 'Videos & Reels',
            'singular' => 'Video',
            'icon' => 'play',
            'group' => 'Homepage',
            'columns' => ['image' => 'Thumbnail', 'title' => 'Title', 'type' => 'Type', 'is_published' => 'Published'],
            'search' => ['title'],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'tr' => true, 'required' => true],
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => ['video' => 'Video (wide)', 'reel' => 'Reel / Short (tall)']],
                ['name' => 'url', 'label' => 'YouTube link', 'type' => 'text', 'required' => true, 'rules' => 'url|max:500'],
                ['name' => 'image', 'label' => 'Thumbnail', 'type' => 'image'],
                ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'rules' => 'integer'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
            ],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Homepage sections (fixed layout, editable copy)
    |----------------------------------------------------------------------
    */
    // 'buttons' => ui_strings keys (config/cms_ui.php) edited on the section page, with a label.
    'sections' => [
        'navbar' => ['label' => 'Navbar (top bar)', 'fields_title' => 'Buttons shown in the navbar', 'note' => 'The menus and their links (Treks, Peak Climbing, Tours, About…) are edited in [Menu (header & footer)](admin/content/menu). The logo text is in [Settings → Company](admin/settings/company).', 'fields' => [
            ['name' => 'show_whatsapp', 'label' => 'Show the round WhatsApp button', 'type' => 'checkbox', 'default' => true, 'help' => 'Opens WhatsApp with the number from Settings → Company.'],
            ['name' => 'show_plan', 'label' => 'Show the "Plan Your Trip" button', 'type' => 'checkbox', 'default' => true],
        ], 'buttons' => [
            'nav.plan' => 'Plan button text',
            'nav.wa_text' => 'WhatsApp button: message typed in for the visitor',
            'nav.whatsapp' => 'WhatsApp button: name when hovered / in the phone menu',
            'nav.home_aria' => 'Logo link (read by screen readers)',
            'nav.menu_aria' => 'Phone menu button (read by screen readers)',
        ]],
        'hero' => ['label' => 'Hero', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Hero heading', 'type' => 'lines', 'tr' => true, 'help' => 'Each line shows on its own line.'],
            ['name' => 'subtitle', 'label' => 'Supporting text (optional)', 'type' => 'textarea', 'tr' => true],
            ['name' => 'values', 'label' => 'Value badges (bottom of hero)', 'type' => 'lines', 'tr' => true, 'help' => 'Four lines, one per badge.'],
            ['name' => 'slides', 'label' => 'Slideshow photos', 'type' => 'group', 'count' => 5, 'fields' => [
                ['name' => 'image', 'label' => 'Photo', 'type' => 'image'],
                ['name' => 'caption', 'label' => 'Caption', 'type' => 'text', 'tr' => true],
            ]],
        ], 'buttons' => [
            'hero.pause' => 'Slideshow pause button (read by screen readers)',
            'hero.play' => 'Slideshow play button (read by screen readers)',
            'hero.dots_aria' => 'Slideshow dots (read by screen readers)',
            'hero.photo_aria' => 'One slideshow dot (read by screen readers)',
            'hero.values_aria' => 'Value badges (read by screen readers)',
        ]],
        'find' => ['label' => 'Find Your Journey', 'note' => 'The 4 cards, their texts and their "Explore →" buttons are edited in [Journey Categories](admin/content/categories).', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
        ]],
        'treks' => ['label' => 'Featured Treks', 'note' => 'The treks themselves are edited in [Treks](admin/content/treks) (switch on "Featured" to show one here).', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
        ], 'buttons' => [
            'trek.view_all' => 'Button: show all treks',
            'trek.view' => 'Trek card: link text',
            'trek.from' => 'Trek card: word before the price',
            'trek.currency' => 'Currency shown before prices (whole website)',
            'trek.days' => 'Trek card: word after the number of days',
            'filter.clear' => 'Filter: "clear" button',
            'filter.region' => 'Filter title for a region',
            'cat.all' => 'Filter title: all treks',
            'cat.trek' => 'Filter title: trekking',
            'cat.popular' => 'Filter title: popular treks',
            'cat.peak' => 'Filter title: peak climbing',
            'cat.cultural' => 'Filter title: cultural tours',
            'cat.short' => 'Filter title: short treks',
            'exp.classic' => 'Filter title: classic treks',
            'exp.short' => 'Filter title: short treks (by experience)',
            'exp.high' => 'Filter title: high altitude',
            'exp.remote' => 'Filter title: remote treks',
            'exp.family' => 'Filter title: family treks',
        ]],
        'craft' => ['label' => 'Craft Your Experience', 'note' => 'The button opens the "Plan Your Trip" form. The form\'s own texts are in [Plan Your Trip form](admin/sections/plan_form).', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title_em', 'label' => 'Heading (italic part)', 'type' => 'text', 'tr' => true],
            ['name' => 'lede', 'label' => 'Text', 'type' => 'textarea', 'tr' => true],
            ['name' => 'tags', 'label' => 'Tags', 'type' => 'lines', 'tr' => true],
            ['name' => 'button', 'label' => 'Button text', 'type' => 'text', 'tr' => true],
            ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
            ['name' => 'stat_value', 'label' => 'Stat value', 'type' => 'text'],
            ['name' => 'stat_label', 'label' => 'Stat label', 'type' => 'text', 'tr' => true],
        ]],
        'why' => ['label' => 'Why Crest Himalaya', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title_em', 'label' => 'Heading (italic part)', 'type' => 'text', 'tr' => true],
            ['name' => 'lede', 'label' => 'Text', 'type' => 'textarea', 'tr' => true],
            ['name' => 'image', 'label' => 'Quote photo', 'type' => 'image'],
            ['name' => 'quote', 'label' => 'Founder quote', 'type' => 'textarea', 'tr' => true],
            ['name' => 'quote_by', 'label' => 'Quote by', 'type' => 'text', 'tr' => true],
            ['name' => 'items', 'label' => 'Reasons', 'type' => 'group', 'count' => 4, 'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'tr' => true],
                ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'tr' => true],
            ]],
        ]],
        'team' => ['label' => 'Team (heading)', 'note' => 'The team members are edited in [Team Members](admin/content/team).', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
        ], 'buttons' => [
            'team.all' => 'Button: show the full team',
        ]],
        'support' => ['label' => 'Journey Support', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
            ['name' => 'lede', 'label' => 'Text', 'type' => 'textarea', 'tr' => true],
            ['name' => 'columns', 'label' => 'Support columns', 'type' => 'group', 'count' => 4, 'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'tr' => true],
                ['name' => 'items', 'label' => 'Items (one per line)', 'type' => 'lines', 'tr' => true],
            ]],
        ]],
        'stories' => ['label' => 'Traveler Stories (heading)', 'note' => 'The stories themselves are edited in [Traveler Stories](admin/content/reviews). The TripAdvisor and Google links are in [Settings → Social Links](admin/settings/social).', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'image', 'label' => 'Background photo', 'type' => 'image'],
        ], 'buttons' => [
            'stories.tripadvisor' => 'Button: TripAdvisor',
            'stories.google' => 'Button: Google Reviews',
            'stories.sample' => 'Tag on sample reviews',
            'stories.prev' => 'Arrow: previous story (read by screen readers)',
            'stories.next' => 'Arrow: next story (read by screen readers)',
            'stories.dot_aria' => 'Dots under the stories (read by screen readers)',
        ]],
        'prepare' => ['label' => 'Travel Guide (heading)', 'note' => 'The guides are edited in [Travel Guides](admin/content/guides).', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
        ], 'buttons' => [
            'prepare.all' => 'Button: show all guides',
            'prepare.read' => 'Guide card: link text',
        ]],
        'blog' => ['label' => 'Articles (heading)', 'note' => 'The articles are edited in [Articles / Blog](admin/content/posts).', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
        ], 'buttons' => [
            'blog.all' => 'Button: show all articles',
            'blog.more' => 'Article card: link text',
        ]],
        'videos' => ['label' => 'Videos (heading)', 'note' => 'The videos are edited in [Videos & Reels](admin/content/videos). The YouTube link is in [Settings → Social Links](admin/settings/social).', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
        ], 'buttons' => [
            'videos.youtube' => 'Button: YouTube channel',
            'videos.reels' => 'Label on reels',
        ]],
        'faq' => ['label' => 'FAQ (heading)', 'note' => 'The questions are edited in [FAQs](admin/content/faqs).', 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
            ['name' => 'lede', 'label' => 'Text', 'type' => 'textarea', 'tr' => true],
        ], 'buttons' => [
            'faq.whatsapp' => 'Button: ask on WhatsApp',
        ]],
        'closing' => ['label' => 'Closing call to action', 'settings' => ['phone', 'whatsapp', 'email', 'address'], 'fields' => [
            ['name' => 'eyebrow', 'label' => 'Small heading', 'type' => 'text', 'tr' => true],
            ['name' => 'title', 'label' => 'Heading', 'type' => 'text', 'tr' => true],
            ['name' => 'lede', 'label' => 'Text', 'type' => 'textarea', 'tr' => true],
            ['name' => 'image', 'label' => 'Background photo', 'type' => 'image'],
        ], 'buttons' => [
            'closing.plan' => 'Button: plan (opens the Plan Your Trip form)',
            'closing.browse' => 'Button: browse treks',
            'contact.whatsapp' => 'Contact label: WhatsApp',
            'contact.email' => 'Contact label: Email',
            'contact.city' => 'Contact label: address',
        ]],
        'footer' => ['label' => 'Footer', 'settings' => ['footer_tagline', 'phone', 'phone2', 'email', 'address', 'instagram', 'facebook', 'youtube', 'tiktok', 'payment_methods', 'associations', 'copyright'], 'note' => 'The footer link columns (Explore, Popular treks…) are edited in [Menu (header & footer)](admin/content/menu): open a column there to add, remove or reorder its links.', 'fields' => [], 'buttons' => [
            'footer.explore' => 'Column title: Explore (only if no footer menu is set)',
            'footer.popular' => 'Column title: popular treks',
            'footer.contact' => 'Column title: contact',
            'footer.follow' => 'Title above the social icons',
            'footer.accept' => 'Title above the payment badges',
            'footer.assoc' => 'Title above the association badges',
            'footer.credits' => 'Link: photo credits (opens the Photo credits pop-up)',
            'footer.pay_aria' => 'Payment badges (read by screen readers)',
            'footer.assoc_aria' => 'Association badges (read by screen readers)',
        ]],
        'plan_form' => ['label' => 'Plan Your Trip form (pop-up)', 'note' => 'Opens from every "Plan" button. Enquiries are sent to the email in [Settings → Company](admin/settings/company).', 'fields' => [], 'buttons' => [
            'plan.eyebrow' => 'Small heading',
            'plan.title' => 'Heading',
            'plan.lede' => 'Text under the heading',
            'plan.trek' => 'Label: trek',
            'plan.not_sure' => 'Trek choice: not sure yet',
            'plan.start' => 'Label: start date',
            'plan.travellers' => 'Label: travellers',
            'plan.style' => 'Label: trip style',
            'plan.style_private' => 'Trip style: private',
            'plan.style_group' => 'Trip style: group',
            'plan.style_tailor' => 'Trip style: tailor-made',
            'plan.comfort' => 'Label: comfort',
            'plan.comfort_classic' => 'Comfort: classic',
            'plan.comfort_comfort' => 'Comfort: comfort',
            'plan.comfort_luxury' => 'Comfort: luxury',
            'plan.extras' => 'Label: extras',
            'plan.extra_heli' => 'Extra: helicopter',
            'plan.extra_chitwan' => 'Extra: Chitwan',
            'plan.extra_pokhara' => 'Extra: Pokhara',
            'plan.extra_ktm' => 'Extra: Kathmandu tour',
            'plan.name' => 'Label: name',
            'plan.ph_name' => 'Example text in the name box',
            'plan.email' => 'Label: email',
            'plan.ph_email' => 'Example text in the email box',
            'plan.phone' => 'Label: phone',
            'plan.message' => 'Label: message',
            'plan.ph_message' => 'Example text in the message box',
            'plan.fewer' => 'Travellers: minus button (read by screen readers)',
            'plan.more' => 'Travellers: plus button (read by screen readers)',
            'plan.submit' => 'Send button',
            'plan.note' => 'Small note under the button',
            'plan.error' => 'Error message',
            'plan.success_title' => 'After sending: heading',
            'plan.success_text' => 'After sending: text',
            'plan.alt' => 'After sending: "prefer to chat?"',
            'plan.success_link' => 'After sending: WhatsApp link',
        ]],
        'trek_popup' => ['label' => 'Trek details (pop-up)', 'note' => 'Opens when a trek is clicked. The trek details themselves (photos, itinerary, price…) are edited in [Treks](admin/content/treks); the group dates in [Fixed Departures](admin/content/departures).', 'settings' => ['default_inclusions'], 'fields' => [], 'buttons' => [
            'trek.duration' => 'Label: duration',
            'trek.days_lower' => 'Word after the number of days',
            'trek.max_altitude' => 'Label: max altitude',
            'trek.difficulty' => 'Label: difficulty',
            'trek.price' => 'Label: price',
            'trek.best_months' => 'Label: best months',
            'trek.grade' => 'Label: grade',
            'trek.region' => 'Label: region',
            'trek.starts' => 'Label: starts in',
            'trek.season' => 'Label: best season',
            'trek.highlights' => 'Heading: highlights',
            'trek.itinerary' => 'Heading: itinerary',
            'trek.gallery' => 'Heading: gallery',
            'trek.included' => 'Heading: included',
            'trek.excluded' => 'Heading: not included',
            'trek.departures' => 'Heading: group departures',
            'trek.join' => 'Button: join a departure',
            'trek.next' => 'Text: next departure',
            'trek.private' => 'Text: runs privately',
            'trek.enquire' => 'Button: enquire / book',
            'trek.included_text' => 'Text under "Included" when a trek has no list and the default list is empty',
            'common.back' => 'Back button',
        ]],
        'docs_popup' => ['label' => 'Articles & Travel Guide (pop-up)', 'note' => 'Opens when an article or guide is clicked, or from "All Articles" / "Visit Travel Guide". The articles and guides themselves are edited in [Articles / Blog](admin/content/posts) and [Travel Guides](admin/content/guides).', 'fields' => [], 'buttons' => [
            'blog.all' => 'Articles: list heading (if the Articles section has no heading) and the back button',
            'prepare.all' => 'Guides: list heading (if the Travel Guide section has no heading) and the back button',
            'doc.error' => 'Text when a page cannot be loaded',
        ]],
        'team_popup' => ['label' => 'Full team (pop-up)', 'note' => 'Opens from "Meet Our Full Team". The people are edited in [Team Members](admin/content/team); the heading is the [Team section](admin/sections/team) heading.', 'fields' => [], 'buttons' => [
            'team.all' => 'Button text (and the heading if the Team section has none)',
        ]],
        'credits_popup' => ['label' => 'Photo credits (pop-up)', 'note' => 'Opens from "Photo credits" at the bottom of the footer.', 'settings' => ['photo_credits'], 'fields' => [], 'buttons' => [
            'footer.credits' => 'Heading (also the footer link)',
            'credits.text' => 'Text under the heading',
            'common.close' => 'Close button',
        ]],
        'chat_popup' => ['label' => 'Chat assistant (pop-up)', 'note' => 'The chat bubble in the corner. Its answers use the treks from [Treks](admin/content/treks), the dates from [Fixed Departures](admin/content/departures) and the questions from [FAQs](admin/content/faqs).', 'settings' => ['whatsapp', 'phone', 'phone2', 'email', 'office_hours', 'deposit', 'payments', 'offers', 'meeting', 'briefing', 'reporting', 'hotel', 'emergency', 'packing', 'fitness'], 'fields' => [], 'buttons' => [
            'Chat window & greeting' => ['chat.launcher', 'chat.title', 'chat.status', 'chat.menu_aria', 'chat.close_aria', 'chat.placeholder', 'chat.send', 'chat.greeting_title', 'chat.greeting', 'chat.hello', 'chat.thanks', 'chat.not_understood'],
            'Main menu buttons' => ['chat.q_menu', 'chat.q_more', 'chat.btn_find', 'chat.btn_expeditions', 'chat.btn_availability', 'chat.btn_prices', 'chat.btn_packing', 'chat.btn_faqs', 'chat.btn_book', 'chat.btn_team', 'chat.btn_travel', 'chat.btn_safety', 'chat.btn_fitness', 'chat.btn_menu'],
            'Find a Trek (questions & answers)' => ['chat.q_level', 'chat.opt_easy', 'chat.opt_moderate', 'chat.opt_challenging', 'chat.opt_not_sure', 'chat.q_days', 'chat.opt_short', 'chat.opt_mid', 'chat.opt_long', 'chat.opt_flexible', 'chat.q_season', 'chat.opt_spring', 'chat.opt_autumn', 'chat.opt_winter', 'chat.opt_monsoon', 'chat.q_budget', 'chat.opt_bud_low', 'chat.opt_bud_mid', 'chat.opt_bud_high', 'chat.rec_picks', 'chat.rec_pick', 'chat.rec_relaxed', 'chat.w_budget', 'chat.w_duration', 'chat.w_difficulty', 'chat.w_and', 'chat.rec_monsoon', 'chat.btn_trek_details', 'chat.btn_start_again', 'chat.btn_ask_planner', 'chat.btn_find_me'],
            'Trek cards & trek details' => ['chat.card_days', 'chat.card_from', 'chat.card_next', 'chat.opened', 'chat.lbl_max_alt', 'chat.lbl_grade', 'chat.lbl_region', 'chat.starts_in', 'chat.lbl_season', 'chat.lbl_highlights', 'chat.lbl_itinerary', 'chat.lbl_included', 'chat.next_dep', 'chat.btn_open_page', 'chat.btn_all_dates2', 'chat.btn_book_this', 'chat.btn_details'],
            'Dates & availability' => ['chat.btn_dates', 'chat.q_dates', 'chat.btn_all_dates', 'chat.dates_for', 'chat.dates_all', 'chat.seats_left', 'chat.dates_private', 'chat.no_dates', 'chat.this_trek', 'chat.btn_private', 'chat.btn_book_date'],
            'Prices' => ['chat.prices_intro', 'chat.price_for', 'chat.lbl_offers', 'chat.lbl_payment', 'chat.we_accept', 'chat.prices_note', 'chat.btn_budget', 'chat.btn_book_now', 'chat.btn_all_prices'],
            'Booking' => ['chat.book_intro', 'chat.book_step1', 'chat.book_step2', 'chat.book_which', 'chat.btn_book_short', 'chat.btn_not_sure_help', 'chat.great_choice', 'chat.book_has_dates', 'chat.book_private', 'chat.btn_join', 'chat.btn_open_form', 'chat.btn_wa_book'],
            'Expeditions' => ['chat.exp_intro', 'chat.exp_outro', 'chat.btn_ask_exp'],
            'Packing, fitness, FAQs, travel & safety' => ['chat.packing_intro', 'chat.packing_outro', 'chat.btn_packing_guide', 'chat.btn_fitness_tips', 'chat.q_faq', 'chat.btn_more_faqs', 'chat.btn_another', 'chat.btn_ask_team', 'chat.lbl_pickup', 'chat.lbl_briefing', 'chat.lbl_reporting', 'chat.lbl_hotel', 'chat.visa', 'chat.btn_travel_info', 'chat.btn_insurance', 'chat.btn_safety_info', 'chat.safety_urgent', 'chat.btn_altitude', 'chat.btn_talk_team'],
            'Talk to the team (WhatsApp / email / call)' => ['chat.team_intro', 'chat.btn_wa', 'chat.wa_message', 'chat.wa_asked', 'chat.btn_email', 'chat.email_subject', 'chat.lbl_call'],
        ]],
    ],

    /*
    |----------------------------------------------------------------------
    | Website settings (grouped into tabs)
    |----------------------------------------------------------------------
    */
    'settings' => [
        'company' => ['label' => 'Company & Contact', 'fields' => [
            ['name' => 'company_name', 'label' => 'Company name', 'type' => 'text'],
            ['name' => 'brand_name', 'label' => 'Logo text', 'type' => 'text', 'help' => 'Shown next to the logo in the navbar, the loading screen and the footer, e.g. "CREST HIMALAYA".'],
            ['name' => 'brand_sub', 'label' => 'Logo sub-text', 'type' => 'text', 'tr' => true, 'help' => 'Small line under the logo text, e.g. "TREKS & EXPEDITIONS".'],
            ['name' => 'email', 'label' => 'Email', 'type' => 'text', 'rules' => 'nullable|email'],
            ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
            ['name' => 'phone2', 'label' => 'Second phone', 'type' => 'text'],
            ['name' => 'whatsapp', 'label' => 'WhatsApp number', 'type' => 'text', 'help' => 'International format, digits only, e.g. 9779862221697.', 'rules' => 'nullable|regex:/^[0-9]{6,16}$/'],
            ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'tr' => true],
            ['name' => 'office_hours', 'label' => 'Reply hours', 'type' => 'text', 'tr' => true],
        ]],
        'social' => ['label' => 'Social Links', 'fields' => [
            ['name' => 'instagram', 'label' => 'Instagram URL', 'type' => 'text', 'rules' => 'nullable|url'],
            ['name' => 'facebook', 'label' => 'Facebook URL', 'type' => 'text', 'rules' => 'nullable|url'],
            ['name' => 'youtube', 'label' => 'YouTube URL', 'type' => 'text', 'rules' => 'nullable|url'],
            ['name' => 'tiktok', 'label' => 'TikTok URL', 'type' => 'text', 'rules' => 'nullable|url'],
            ['name' => 'tripadvisor', 'label' => 'TripAdvisor URL', 'type' => 'text', 'rules' => 'nullable|url'],
            ['name' => 'google_reviews', 'label' => 'Google Reviews URL', 'type' => 'text', 'rules' => 'nullable|url'],
        ]],
        'footer' => ['label' => 'Footer', 'fields' => [
            ['name' => 'footer_tagline', 'label' => 'Footer tagline', 'type' => 'text', 'tr' => true],
            ['name' => 'copyright', 'label' => 'Copyright line', 'type' => 'text', 'tr' => true],
            ['name' => 'payment_methods', 'label' => 'Payment methods (badges)', 'type' => 'lines', 'help' => 'One per line, e.g. VISA, MASTERCARD, PAYPAL.'],
            ['name' => 'photo_credits', 'label' => 'Photo credits list', 'type' => 'lines', 'help' => 'One photo per line: Photo name | Photographer | Licence | https://link  e.g. "Ama Dablam | Vyacheslav Argenberg | CC BY 4.0 | https://commons.wikimedia.org/..."'],
            ['name' => 'associations', 'label' => '"Registered & associated with" badges', 'type' => 'lines', 'help' => 'One per line: Short name | Full name | https://website  e.g. "TAAN | Trekking Agencies\' Association of Nepal | https://www.taan.org.np/"'],
        ]],
        'seo' => ['label' => 'SEO', 'fields' => [
            ['name' => 'seo_title', 'label' => 'Browser tab / Google title', 'type' => 'text', 'tr' => true, 'help' => 'About 50-60 characters, e.g. "Crest Himalaya Treks & Expeditions | Sherpa-led Treks in Nepal".'],
            ['name' => 'seo_description', 'label' => 'Google description', 'type' => 'textarea', 'tr' => true, 'help' => 'About 150-160 characters shown under the title in search results.'],
        ]],
        'trip' => ['label' => 'Trip Info', 'fields' => [
            ['name' => 'default_inclusions', 'label' => 'Default "Included" list for treks', 'type' => 'lines', 'tr' => true],
            ['name' => 'deposit', 'label' => 'Deposit / booking terms', 'type' => 'textarea'],
            ['name' => 'payments', 'label' => 'Accepted payments (sentence)', 'type' => 'text'],
            ['name' => 'offers', 'label' => 'Offers', 'type' => 'lines'],
            ['name' => 'meeting', 'label' => 'Airport pickup', 'type' => 'textarea'],
            ['name' => 'briefing', 'label' => 'Trip briefing', 'type' => 'textarea'],
            ['name' => 'reporting', 'label' => 'Reporting time', 'type' => 'textarea'],
            ['name' => 'hotel', 'label' => 'Accommodation', 'type' => 'textarea'],
            ['name' => 'emergency', 'label' => 'Safety & emergency', 'type' => 'lines'],
            ['name' => 'packing', 'label' => 'Packing list', 'type' => 'lines', 'help' => 'One group per line as "Group: items", e.g. "Footwear: boots, socks".'],
            ['name' => 'fitness', 'label' => 'Fitness advice', 'type' => 'textarea'],
        ]],
    ],
];
