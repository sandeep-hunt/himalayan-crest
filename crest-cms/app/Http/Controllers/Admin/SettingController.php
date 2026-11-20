<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Fields;
use App\Support\Settings;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private function group(string $group): array
    {
        $def = config("cms.settings.$group");
        abort_unless($def, 404);

        return $def + ['key' => $group];
    }

    public function edit(string $group = 'company')
    {
        $def = $this->group($group);
        $groups = config('cms.settings');
        $values = Settings::all();

        return view('admin.settings.form', compact('def', 'groups', 'values'));
    }

    public function update(Request $request, string $group)
    {
        $def = $this->group($group);
        $request->validate(Fields::rules($def['fields'], 'settings'), [], Fields::attributeNames($def['fields'], 'settings'));
        $values = Fields::values($def['fields'], $request, 'settings', Settings::all(), 'settings');

        foreach ($values as $key => $value) {
            Settings::set($key, $value);
        }

        return redirect()->route('admin.settings.edit', $group)->with('success', $def['label'].' saved.');
    }
}
