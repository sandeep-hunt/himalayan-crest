<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Departure;
use App\Models\Faq;
use App\Models\Guide;
use App\Models\Post;
use App\Models\Review;
use App\Models\Section;
use App\Models\TeamMember;
use App\Models\Trek;
use App\Models\UiString;
use App\Models\Video;

/**
 * Builds the JSON the website consumes, already resolved to one language
 * (missing translations fall back to English) with absolute image URLs.
 */
class SiteContent
{
    public function __construct(public string $locale) {}

    public static function resolveLocale(?string $requested): string
    {
        $requested = strtolower(substr((string) $requested, 0, 5));

        return in_array($requested, Settings::enabledLocales(), true) ? $requested : config('cms.fallback_locale', 'en');
    }

    public function bundle(): array
    {
        return [
            'locale' => $this->locale,
            'dir' => ! empty(config("cms.locales.{$this->locale}.rtl")) ? 'rtl' : 'ltr',
            'languages' => $this->languages(),
            'settings' => $this->settings(),
            'sections' => $this->sections(),
            'ui' => $this->ui(),
            'categories' => $this->categories(),
            'treks' => $this->treks(),
            'departures' => $this->departures(),
            'team' => $this->team(),
            'reviews' => $this->reviews(),
            'guides' => $this->guides(false),
            'posts' => $this->posts(false),
            'faqs' => $this->faqs(),
            'videos' => $this->videos(),
            'menu' => $this->menu(),
            // The chat assistant is English only (client decision): English treks + FAQs for it
            'chat' => $this->locale === 'en' ? null : ['treks' => ($en = new self('en'))->treks(), 'faqs' => $en->faqs()],
        ];
    }

    public function languages(): array
    {
        return array_map(fn ($code) => [
            'code' => $code,
            'name' => config("cms.locales.$code.name"),
            'native' => config("cms.locales.$code.native"),
            'rtl' => ! empty(config("cms.locales.$code.rtl")),
        ], Settings::enabledLocales());
    }

    public function settings(): array
    {
        $s = fn ($k, $d = null) => Settings::get($k, $d);
        $packing = array_map(function ($line) {
            $parts = explode(':', $line, 2);

            return count($parts) === 2 ? [trim($parts[0]), trim($parts[1])] : ['', trim($line)];
        }, (array) $s('packing', []));

        return [
            'company_name' => $s('company_name', 'Crest Himalaya'),
            'email' => $s('email'),
            'phone' => $s('phone'),
            'phone2' => $s('phone2'),
            'whatsapp' => $s('whatsapp'),
            'address' => Settings::tr('address', $this->locale),
            'office_hours' => Settings::tr('office_hours', $this->locale),
            'social' => [
                'instagram' => $s('instagram'),
                'facebook' => $s('facebook'),
                'youtube' => $s('youtube'),
                'tiktok' => $s('tiktok'),
                'tripadvisor' => $s('tripadvisor'),
                'google_reviews' => $s('google_reviews'),
            ],
            'brand_name' => $s('brand_name'),
            'brand_sub' => Settings::tr('brand_sub', $this->locale),
            'seo_title' => Settings::tr('seo_title', $this->locale),
            'seo_description' => Settings::tr('seo_description', $this->locale),
            'footer_tagline' => Settings::tr('footer_tagline', $this->locale),
            'copyright' => Settings::tr('copyright', $this->locale),
            'payment_methods' => array_values((array) $s('payment_methods', [])),
            // "Short name | Full name | https://link" per line
            'associations' => array_values(array_map(function ($line) {
                $p = array_map('trim', explode('|', (string) $line));

                return ['short' => $p[0] ?? '', 'name' => $p[1] ?? '', 'url' => $p[2] ?? ''];
            }, (array) $s('associations', []))),
            'default_inclusions' => (array) Settings::tr('default_inclusions', $this->locale, []),
            // "Photo | Photographer | Licence | https://link" per line
            'photo_credits' => array_values(array_filter(array_map(function ($line) {
                $p = array_map('trim', explode('|', (string) $line));
                $url = $p[3] ?? '';

                return ['title' => $p[0] ?? '', 'artist' => $p[1] ?? '', 'license' => $p[2] ?? '', 'source' => preg_match('#^https?://#i', $url) ? $url : ''];
            }, (array) $s('photo_credits', [])), fn ($c) => $c['title'] !== '')),
            // Facts used by the website's chat assistant (answers in English).
            'chat' => [
                'whatsapp' => $s('whatsapp'),
                'phone' => $s('phone'),
                'phone2' => $s('phone2'),
                'email' => $s('email'),
                'hours' => Settings::tr('office_hours', 'en'),
                'deposit' => $s('deposit'),
                'payments' => $s('payments'),
                'offers' => array_values((array) $s('offers', [])),
                'meeting' => $s('meeting'),
                'briefing' => $s('briefing'),
                'reporting' => $s('reporting'),
                'hotel' => $s('hotel'),
                'emergency' => array_values((array) $s('emergency', [])),
                'packing' => $packing,
                'fitness' => $s('fitness'),
            ],
        ];
    }

    /** The website's original photo credits as setting lines (database/data/photo-credits.json). */
    public static function defaultPhotoCredits(): array
    {
        $file = database_path('data/photo-credits.json');
        $list = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];

        return array_map(fn ($c) => implode(' | ', [
            preg_replace('/\.jpe?g$/i', '', str_replace('|', '/', (string) ($c['title'] ?? ''))),
            str_replace('|', '/', (string) ($c['artist'] ?? '')),
            (string) ($c['license'] ?? ''),
            (string) ($c['source'] ?? ''),
        ]), $list);
    }

    public function sections(): array
    {
        $stored = Section::query()->pluck('content', 'key')->all();
        $out = [];
        foreach (config('cms.sections') as $key => $def) {
            $out[$key] = Fields::resolveAll($def['fields'] ?? [], (array) ($stored[$key] ?? []), $this->locale);
        }

        return $out;
    }

    public function ui(): array
    {
        $db = UiString::query()->pluck('value', 'key')->all();
        $out = [];
        foreach (config('cms_ui') as $group) {
            foreach ($group as $key => $default) {
                $v = $db[$key] ?? [];
                // chat assistant texts are always English
                $loc = ContentTranslator::englishOnly($key) ? 'en' : $this->locale;
                $out[$key] = ($v[$loc] ?? '') !== '' ? $v[$loc] : (($v['en'] ?? '') !== '' ? $v['en'] : $default);
            }
        }

        return $out;
    }

    /** Header menus and footer link columns, each with its published links. */
    public function menu(): array
    {
        $l = $this->locale;
        $out = ['header' => [], 'footer' => []];
        $menus = \App\Models\MenuItem::published()->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_published', true)])
            ->orderBy('sort_order')->orderBy('id')->get();
        foreach ($menus as $m) {
            $out[$m->location === 'footer' ? 'footer' : 'header'][] = [
                'title' => $m->tr('title', $l),
                'style' => $m->style ?: 'list',
                'links' => $m->children->map(fn ($c) => array_filter([
                    'title' => $c->tr('title', $l),
                    'subtitle' => $c->tr('subtitle', $l),
                    'column' => $c->tr('column', $l),
                    'action' => $c->action,
                    'target' => $c->target,
                    'button' => $c->is_button ?: null,
                ], fn ($v) => $v !== null && $v !== ''))->values()->all(),
            ];
        }

        return $out;
    }

    public function categories(): array
    {
        return Category::published()->ordered()->get()->map(fn (Category $c) => [
            'title' => $c->tr('title', $this->locale),
            'description' => $c->tr('description', $this->locale),
            'link_label' => $c->tr('link_label', $this->locale),
            'action' => $c->action,
            'icon' => $c->icon,
            'img' => Media::url($c->image),
        ])->all();
    }

    public function trek(Trek $t): array
    {
        $l = $this->locale;
        $inclusions = $t->tr('inclusions', $l) ?: (array) Settings::tr('default_inclusions', $l, []);

        return [
            'id' => $t->slug,
            'name' => $t->tr('name', $l),
            'cat' => $t->category,
            'badge' => $t->tr('badge', $l) ?? '',
            'area' => $t->tr('area', $l) ?? '',
            'level' => $t->tr('difficulty', $l) ?? '',
            'days' => $t->days,
            'alt' => $t->altitudeLabel(),
            'alt_m' => $t->max_altitude,
            'grade' => $t->tr('grade', $l) ?? $t->tr('difficulty', $l) ?? '',
            'price' => $t->price,
            'img' => Media::url($t->image),
            'gallery' => array_values(array_filter(array_map([Media::class, 'url'], (array) $t->gallery))),
            // English region is kept for the region filters in the menu
            'region' => $t->tr('region', $l) ?? '',
            'region_en' => $t->tr('region', 'en') ?? '',
            'area_en' => $t->tr('area', 'en') ?? '',
            'start' => $t->tr('start_point', $l) ?? '',
            'season' => $t->tr('season', $l) ?? '',
            'months' => array_map('intval', (array) $t->months),
            'tags' => array_values((array) $t->tags),
            'summary' => $t->tr('summary', $l) ?? '',
            'highlights' => array_values((array) ($t->tr('highlights', $l) ?? [])),
            'itinerary' => array_values((array) ($t->tr('itinerary', $l) ?? [])),
            'inclusions' => array_values($inclusions),
            'exclusions' => array_values((array) ($t->tr('exclusions', $l) ?? [])),
            'featured' => $t->is_featured,
        ];
    }

    public function treks(): array
    {
        return Trek::published()->ordered()->get()->map(fn (Trek $t) => $this->trek($t))->all();
    }

    public function departures(): array
    {
        return Departure::published()
            ->whereDate('date', '>=', now()->toDateString())
            ->whereHas('trek', fn ($q) => $q->where('is_published', true))
            ->with('trek:id,slug')
            ->orderBy('date')
            ->get()
            ->map(fn (Departure $d) => ['trek' => $d->trek->slug, 'date' => $d->date->format('Y-m-d'), 'seats' => $d->seats])
            ->all();
    }

    public function team(): array
    {
        return TeamMember::published()->ordered()->get()->map(fn (TeamMember $m) => [
            'name' => $m->name,
            'designation' => $m->tr('designation', $this->locale),
            'experience' => $m->tr('experience', $this->locale),
            'regions' => $m->tr('regions', $this->locale),
            'bio' => $m->tr('bio', $this->locale),
            'photo' => Media::url($m->photo),
            'background' => Media::url($m->background),
        ])->all();
    }

    public function reviews(): array
    {
        return Review::published()->ordered()->get()->map(fn (Review $r) => [
            'name' => $r->traveller_name,
            'country' => $r->tr('country', $this->locale),
            'trek' => $r->tr('trek_label', $this->locale),
            'text' => $r->tr('text', $this->locale),
            'rating' => $r->rating,
            'photo' => Media::url($r->photo),
        ])->all();
    }

    public function guide(Guide $g, bool $withContent = true): array
    {
        return array_filter([
            'slug' => $g->slug,
            'title' => $g->tr('title', $this->locale),
            'summary' => $g->tr('summary', $this->locale),
            'icon' => $g->icon,
            'img' => Media::url($g->image),
            'has_content' => (bool) $g->tr('content', 'en'),
            'content' => $withContent ? $g->tr('content', $this->locale) : null,
        ], fn ($v) => $v !== null);
    }

    public function guides(bool $withContent = true): array
    {
        return Guide::published()->ordered()->get()->map(fn (Guide $g) => $this->guide($g, $withContent))->all();
    }

    public function post(Post $p, bool $withContent = true): array
    {
        return array_filter([
            'slug' => $p->slug,
            'title' => $p->tr('title', $this->locale),
            'cat' => $p->tr('category', $this->locale),
            'date' => $p->published_at?->format('Y-m-d'),
            'excerpt' => $p->tr('excerpt', $this->locale),
            'img' => Media::url($p->image),
            'has_content' => (bool) $p->tr('content', 'en'),
            'content' => $withContent ? $p->tr('content', $this->locale) : null,
        ], fn ($v) => $v !== null);
    }

    public function posts(bool $withContent = true): array
    {
        return Post::published()->ordered()->get()->map(fn (Post $p) => $this->post($p, $withContent))->all();
    }

    public function faqs(): array
    {
        return Faq::published()->ordered()->get()->map(fn (Faq $f) => [
            'q' => $f->tr('question', $this->locale),
            'a' => $f->tr('answer', $this->locale),
        ])->all();
    }

    public function videos(): array
    {
        return Video::published()->ordered()->get()->map(fn (Video $v) => [
            'title' => $v->tr('title', $this->locale),
            'type' => $v->type,
            'url' => $v->url,
            'img' => Media::url($v->image),
        ])->all();
    }
}
