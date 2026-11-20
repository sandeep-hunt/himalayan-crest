<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Section;
use App\Models\UiString;
use App\Support\Fields;
use App\Support\Settings;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $counts = [];
        foreach (config('cms.resources') as $key => $def) {
            $counts[$key] = [
                'label' => $def['label'],
                'icon' => $def['icon'],
                'total' => $def['model']::count(),
                'published' => $def['model']::where('is_published', true)->count(),
            ];
        }

        $upcoming = Departure::with('trek')->where('is_published', true)
            ->whereDate('date', '>=', now()->toDateString())->orderBy('date')->limit(5)->get();

        return view('admin.dashboard', [
            'counts' => $counts,
            'upcoming' => $upcoming,
            'translations' => $this->translationStatus(),
            'enabledLocales' => Settings::enabledLocales(),
        ]);
    }

    /** % of translatable content filled in, per language (English is the source). */
    private function translationStatus(): array
    {
        // Collect [fields, values] pairs across resources, sections and interface text once
        $sets = [];
        foreach (config('cms.resources') as $def) {
            $trFields = array_values(array_filter($def['fields'], fn ($f) => ! empty($f['tr'])));
            if (! $trFields) {
                continue;
            }
            foreach ($def['model']::query()->get(array_merge(['id'], array_column($trFields, 'name'))) as $row) {
                $sets[] = [$trFields, $row->only(array_column($trFields, 'name'))];
            }
        }
        $stored = Section::query()->pluck('content', 'key')->all();
        foreach (config('cms.sections') as $key => $def) {
            $sets[] = [$def['fields'] ?? [], (array) ($stored[$key] ?? [])];
        }
        $ui = UiString::query()->pluck('value', 'key')->all();
        $uiTotal = array_sum(array_map('count', config('cms_ui')));

        $out = [];
        foreach (config('cms.locales') as $code => $l) {
            if ($code === 'en') {
                continue;
            }
            $total = $uiTotal;
            $done = count(array_filter($ui, fn ($v) => ! empty($v[$code])));
            foreach ($sets as [$fields, $values]) {
                [$t, $d] = Fields::completeness($fields, $values, $code);
                $total += $t;
                $done += $d;
            }
            $out[$code] = ['name' => $l['name'], 'native' => $l['native'], 'pct' => $total ? (int) floor($done * 100 / $total) : 0];
        }

        return $out;
    }
}
