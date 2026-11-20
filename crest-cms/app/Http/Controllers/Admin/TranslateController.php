<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Locales;
use App\Support\Translator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Translates the English texts of an edit page into one language. Called by the progress popup
 * that runs when "Save" is pressed; the form is only saved when every language succeeded.
 */
class TranslateController extends Controller
{
    public function __invoke(Request $request)
    {
        $codes = array_values(array_diff(array_keys(Locales::all()), ['en']));
        $data = $request->validate([
            'target' => ['required', Rule::in($codes)],
            'texts' => 'present|array|max:500',
            'texts.*' => 'nullable|string|max:60000',
        ]);
        @set_time_limit(300);
        try {
            $out = Translator::translate(array_map(fn ($t) => (string) $t, $data['texts']), $data['target']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['translations' => array_values($out)]);
    }
}
