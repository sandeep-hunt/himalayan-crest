<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UiString;
use Illuminate\Http\Request;

/**
 * Buttons & Labels: every button name and small text on the website, in one list.
 * English is edited; the other languages are filled by Google Translate when saving
 * (see admin.js) and shown read-only. Chat assistant texts are English only.
 */
class UiStringController extends Controller
{
    public function edit()
    {
        $locales = config('cms.locales');
        $groups = config('cms_ui');
        $values = UiString::query()->pluck('value', 'key')->all();

        $total = 0;
        $done = 0;
        foreach ($groups as $strings) {
            foreach ($strings as $key => $default) {
                $total++;
                if (($values[$key]['en'] ?? '') !== '' && $values[$key]['en'] !== $default) {
                    $done++;
                }
            }
        }

        return view('admin.ui.form', compact('locales', 'groups', 'values', 'total', 'done'));
    }

    public function update(Request $request)
    {
        $request->validate(['strings' => 'array', 'strings.*' => 'array', 'strings.*.*' => 'nullable|string|max:1000']);
        UiString::saveAll((array) $request->input('strings', []));

        return redirect()->route('admin.ui.edit')->with('success', 'Buttons & labels saved'.(count(config('cms.locales')) > 1 ? ' in every language' : '').'. Refresh the website to see them.');
    }
}
