<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\Post;
use App\Models\Trek;
use App\Support\ContentCache;
use App\Support\SiteContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public, read-only REST API used by the website.
 * Every endpoint accepts ?lang=xx (one of the enabled languages; defaults to English).
 */
class ContentController extends Controller
{
    private function content(Request $request): SiteContent
    {
        return new SiteContent(SiteContent::resolveLocale($request->query('lang')));
    }

    private function cached(Request $request, string $key, callable $build): JsonResponse
    {
        $content = $this->content($request);
        $data = ContentCache::remember($key.':'.$content->locale, fn () => $build($content));

        return response()->json(['data' => $data])
            ->header('Cache-Control', 'no-cache, must-revalidate'); // always fresh after an admin save (the server cache clears itself on every save)
    }

    /** Everything the homepage needs in a single request. */
    public function site(Request $request): JsonResponse
    {
        return $this->cached($request, 'site', fn (SiteContent $c) => $c->bundle());
    }

    public function languages(Request $request): JsonResponse
    {
        return $this->cached($request, 'languages', fn (SiteContent $c) => $c->languages());
    }

    public function settings(Request $request): JsonResponse
    {
        return $this->cached($request, 'settings', fn (SiteContent $c) => $c->settings());
    }

    public function sections(Request $request): JsonResponse
    {
        return $this->cached($request, 'sections', fn (SiteContent $c) => $c->sections());
    }

    public function ui(Request $request): JsonResponse
    {
        return $this->cached($request, 'ui', fn (SiteContent $c) => $c->ui());
    }

    public function treks(Request $request): JsonResponse
    {
        return $this->cached($request, 'treks', fn (SiteContent $c) => $c->treks());
    }

    public function trek(Request $request, string $slug): JsonResponse
    {
        $trek = Trek::published()->where('slug', $slug)->firstOrFail();
        $c = $this->content($request);
        $data = $c->trek($trek);
        $data['departures'] = array_values(array_filter($c->departures(), fn ($d) => $d['trek'] === $slug));

        return response()->json(['data' => $data]);
    }

    public function departures(Request $request): JsonResponse
    {
        return $this->cached($request, 'departures', fn (SiteContent $c) => $c->departures());
    }

    public function categories(Request $request): JsonResponse
    {
        return $this->cached($request, 'categories', fn (SiteContent $c) => $c->categories());
    }

    public function team(Request $request): JsonResponse
    {
        return $this->cached($request, 'team', fn (SiteContent $c) => $c->team());
    }

    public function reviews(Request $request): JsonResponse
    {
        return $this->cached($request, 'reviews', fn (SiteContent $c) => $c->reviews());
    }

    public function faqs(Request $request): JsonResponse
    {
        return $this->cached($request, 'faqs', fn (SiteContent $c) => $c->faqs());
    }

    public function videos(Request $request): JsonResponse
    {
        return $this->cached($request, 'videos', fn (SiteContent $c) => $c->videos());
    }

    public function guides(Request $request): JsonResponse
    {
        return $this->cached($request, 'guides', fn (SiteContent $c) => $c->guides(false));
    }

    public function guide(Request $request, string $slug): JsonResponse
    {
        $guide = Guide::published()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $this->content($request)->guide($guide)]);
    }

    public function posts(Request $request): JsonResponse
    {
        return $this->cached($request, 'posts', fn (SiteContent $c) => $c->posts(false));
    }

    public function post(Request $request, string $slug): JsonResponse
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $this->content($request)->post($post)]);
    }
}
