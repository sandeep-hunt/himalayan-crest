<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\UiString;
use App\Support\ContentTranslator;
use App\Support\Fields;
use App\Support\Settings;
use Illuminate\Http\Request;

/** Homepage section copy (hero, why, support, ...) plus the buttons & labels shown in each section. */
class SectionController extends Controller
{
    private function def(string $key): array
    {
        $def = config("cms.sections.$key");
        abort_unless($def, 404);

        return $def + ['key' => $key, 'fields' => [], 'buttons' => [], 'settings' => []];
    }

    /**
     * Settings shown on this section page (e.g. phone, email), grouped by their Settings tab:
     * [group key => ['label' => ..., 'fields' => [...]]]. They are the same values as in Settings.
     */
    private function settingGroups(array $def): array
    {
        $out = [];
        foreach ($def['settings'] as $name) {
            foreach (config('cms.settings') as $gKey => $group) {
                $field = collect($group['fields'])->firstWhere('name', $name);
                if ($field) {
                    $out[$gKey] ??= ['label' => $group['label'], 'fields' => []];
                    $out[$gKey]['fields'][] = $field;
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * Buttons & labels of a section, as cards: [heading => [ui key => label]].
     * In config a 'buttons' entry is either "key => label" (shown in the main card) or
     * "Heading => [keys or key => label]" (its own card). Keys without a label get one from the key.
     */
    public static function buttonGroups(array $def): array
    {
        $main = 'Buttons & labels in this section';
        $out = [];
        foreach ($def['buttons'] ?? [] as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $kk => $label) {
                    is_int($kk) ? $out[$k][$label] = self::autoLabel($label) : $out[$k][$kk] = $label;
                }
            } else {
                $out[$main][$k] = $v;
            }
        }

        return $out;
    }

    /** All ui keys of a section: [key => label]. */
    public static function buttonKeys(array $def): array
    {
        return array_merge([], ...array_values(self::buttonGroups($def)));
    }

    /** "chat.btn_find_me" -> "Button: find me" */
    private static function autoLabel(string $key): string
    {
        $name = str_contains($key, '.') ? substr($key, strpos($key, '.') + 1) : $key;
        $kinds = ['btn' => 'Button', 'q' => 'Question', 'opt' => 'Answer button', 'lbl' => 'Label', 'w' => 'Word', 'rec' => 'Recommendation text'];
        $first = strtok($name, '_');
        if (isset($kinds[$first]) && $first !== $name) {
            return $kinds[$first].': '.str_replace('_', ' ', substr($name, strlen($first) + 1));
        }
        $name = preg_replace('/_aria$/', ' (screen readers)', $name);

        return 'Text: '.str_replace('_', ' ', $name);
    }

    public function index()
    {
        $sections = config('cms.sections');
        $updated = Section::query()->pluck('updated_at', 'key');

        return view('admin.sections.index', compact('sections', 'updated'));
    }

    public function edit(string $key)
    {
        $def = $this->def($key);
        $content = (array) (Section::where('key', $key)->value('content') ?? []);
        if (is_string($content)) {
            $content = json_decode($content, true) ?: [];
        }
        // Buttons & labels of this section: current English text (empty = the default shows)
        $defaults = UiString::defaults();
        $groups = self::buttonGroups($def);
        $stored = UiString::query()->whereIn('key', array_keys(self::buttonKeys($def)))->pluck('value', 'key')->all();
        $buttons = [];
        foreach ($groups as $heading => $keys) {
            foreach ($keys as $uiKey => $label) {
                $buttons[$heading][$uiKey] = [
                    'label' => $label, 'default' => $defaults[$uiKey] ?? '', 'value' => $stored[$uiKey]['en'] ?? '',
                    'values' => (array) ($stored[$uiKey] ?? []), 'englishOnly' => ContentTranslator::englishOnly($uiKey),
                ];
            }
        }

        $settingGroups = $this->settingGroups($def);
        $settingValues = $settingGroups ? Settings::all() : [];

        return view('admin.sections.form', compact('def', 'content', 'buttons', 'settingGroups', 'settingValues'));
    }

    public function update(Request $request, string $key)
    {
        $def = $this->def($key);
        $settingFields = array_merge([], ...array_column($this->settingGroups($def), 'fields'));
        $rules = Fields::rules($def['fields'], 'content') + Fields::rules($settingFields, 'settings') + ['strings' => 'array', 'strings.*' => 'array', 'strings.*.*' => 'nullable|string|max:1000'];
        $request->validate($rules, [], Fields::attributeNames($def['fields'], 'content') + Fields::attributeNames($settingFields, 'settings'));

        if ($def['fields']) {
            $section = Section::firstOrNew(['key' => $key]);
            $section->content = Fields::values($def['fields'], $request, 'content', (array) ($section->content ?? []), 'sections');
            $section->save();
        }
        if ($settingFields) {
            foreach (Fields::values($settingFields, $request, 'settings', Settings::all(), 'settings') as $name => $value) {
                Settings::set($name, $value);
            }
        }
        if ($keys = self::buttonKeys($def)) {
            UiString::saveAll(array_intersect_key((array) $request->input('strings', []), $keys));
        }

        return redirect()->route('admin.sections.edit', $key)->with('success', $def['label'].' saved. Refresh the website to see it.');
    }
}
