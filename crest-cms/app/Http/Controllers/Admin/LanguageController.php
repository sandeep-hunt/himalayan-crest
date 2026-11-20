<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UiString;
use App\Support\ContentCache;
use App\Support\ContentTranslator;
use App\Support\Locales;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin → Languages: add, edit, remove and show/hide website languages. */
class LanguageController extends Controller
{
    public function index()
    {
        $languages = Locales::all();
        $catalog = Locales::catalog();

        // interface-text progress per language
        $ui = UiString::query()->pluck('value', 'key')->all();
        $keys = array_filter(array_keys(UiString::defaults()), fn ($k) => ! ContentTranslator::englishOnly($k));
        $total = count($keys);
        $progress = [];
        foreach (array_keys($languages) as $code) {
            $progress[$code] = $code === 'en' ? 100 : ($total ? (int) floor(count(array_filter($keys, fn ($k) => ! empty($ui[$k][$code]))) * 100 / $total) : 0);
        }

        $steps = collect(ContentTranslator::steps())->map(fn ($s) => ['step' => $s, 'label' => ContentTranslator::label($s)])->all();

        return view('admin.languages.index', compact('languages', 'catalog', 'progress', 'steps'));
    }

    private function validated(Request $request, ?string $existing = null): array
    {
        $request->merge(['code' => strtolower(trim((string) $request->input('code')))]);
        $codes = array_keys(Locales::all());

        return $request->validate([
            'code' => array_filter([
                $existing ? null : 'required',
                $existing ? null : 'regex:'.Locales::CODE_PATTERN,
                $existing ? null : Rule::notIn($codes),
            ]),
            'name' => 'required|string|max:60',
            'native' => 'nullable|string|max:60',
            'rtl' => 'nullable|boolean',
            'enabled' => 'nullable|boolean',
        ], [
            'code.regex' => 'The code must be 2 or 3 lowercase letters, optionally with a region, e.g. "te", "fil" or "pt-br".',
            'code.not_in' => 'This language is already in the list.',
        ], ['name' => 'English name', 'native' => 'native name']);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $list = Locales::all();
        $list[$data['code']] = [
            'name' => $data['name'],
            'native' => ($data['native'] ?? '') ?: $data['name'],
            'rtl' => $request->boolean('rtl'),
            // hidden from the website until all English content has been translated into it
            'enabled' => false,
            'translated' => false,
        ];
        Locales::save($list);

        // "Show on the website": translate all English content into it now (progress popup), then show it.
        // Otherwise it is added hidden and translated when it is switched on.
        if (! $request->boolean('enabled')) {
            return redirect()->route('admin.languages.index')->with('success', "{$data['name']} was added (hidden). Switch it on to translate the website into {$data['name']} and show it.");
        }

        return redirect()->route('admin.languages.index')
            ->with('fill', ['code' => $data['code'], 'show' => true])
            ->with('success', "{$data['name']} was added.");
    }

    /** One step of filling a language with Google translations (called by the progress popup). */
    public function translateStep(Request $request, string $code)
    {
        $list = Locales::all();
        abort_unless(isset($list[$code]) && $code !== 'en', 404);
        $step = (string) $request->input('step');
        abort_unless(in_array($step, ContentTranslator::steps(), true), 422);
        @set_time_limit(300);
        try {
            ContentTranslator::run($step, $code);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['ok' => true]);
    }

    /** All steps done: mark the language as translated (and show it, if that was asked). */
    public function translateDone(Request $request, string $code)
    {
        $list = Locales::all();
        abort_unless(isset($list[$code]) && $code !== 'en', 404);
        $list[$code]['translated'] = true;
        if ($request->boolean('show')) {
            $list[$code]['enabled'] = true;
        }
        Locales::save($list);
        ContentCache::bump();
        session()->flash('success', $list[$code]['name'].' is translated'.($list[$code]['enabled'] ? ' and shown on the website.' : '. Switch it on to show it on the website.'));

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, string $code)
    {
        $list = Locales::all();
        abort_unless(isset($list[$code]), 404);
        if ($code === 'en') {
            return back()->withErrors(['code' => 'English is the main language and cannot be changed.']);
        }
        $data = $this->validated($request, $code);
        $list[$code] = [
            'name' => $data['name'],
            'native' => ($data['native'] ?? '') ?: $data['name'],
            'rtl' => $code === 'en' ? false : $request->boolean('rtl'),
            'enabled' => $list[$code]['enabled'],
        ];
        Locales::save($list);

        return back()->with('success', "{$data['name']} was updated.");
    }

    /** Show / hide on the website. */
    public function toggle(string $code)
    {
        $list = Locales::all();
        abort_unless(isset($list[$code]), 404);
        if ($code === 'en') {
            return back()->withErrors(['code' => 'English is the main language and is always shown.']);
        }
        if (! $list[$code]['enabled']) {
            // showing a language first re-translates all English content into it (Languages page,
            // progress popup -> translate steps -> translateDone with show=1)
            return back()->withErrors(['code' => 'Use the Hidden / Shown switch on the Languages page: it translates all content into '.$list[$code]['name'].' first.']);
        }
        $list[$code]['enabled'] = false;
        Locales::save($list);

        return back()->with('success', $list[$code]['name'].($list[$code]['enabled'] ? ' is now shown on the website.' : ' is now hidden from the website.'));
    }

    /** Remove a language. Its translations stay stored, so adding it back restores them. */
    public function destroy(string $code)
    {
        $list = Locales::all();
        abort_unless(isset($list[$code]), 404);
        if ($code === 'en') {
            return back()->withErrors(['code' => 'English is the main language and cannot be removed.']);
        }
        $name = $list[$code]['name'];
        unset($list[$code]);
        Locales::save($list);
        @set_time_limit(300);
        ContentTranslator::forget($code); // its translations are deleted too
        ContentCache::bump();

        return back()->with('success', "{$name} was deleted.");
    }
}
