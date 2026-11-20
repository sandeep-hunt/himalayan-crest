<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsModel;
use App\Support\Fields;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Generic create / list / edit / delete / publish screens for every resource
 * defined in config('cms.resources').
 */
class ResourceController extends Controller
{
    private function def(string $resource): array
    {
        $def = config("cms.resources.$resource");
        abort_unless($def, 404);

        return $def + ['key' => $resource];
    }

    /** Resources marked 'edit_only' (the Menu) can be edited, but not added, deleted or switched on/off. */
    private function denyIfEditOnly(array $def): void
    {
        abort_if(! empty($def['edit_only']), 403, 'Items here can be edited, but not added, deleted or hidden.');
    }

    private function find(array $def, int $id): CmsModel
    {
        return $def['model']::query()->findOrFail($id);
    }

    public function index(Request $request, string $resource)
    {
        $def = $this->def($resource);
        // The menu has its own page: header menus and footer columns as cards, with their links inside
        if ($resource === 'menu' && ! $request->filled('q') && ! $request->filled('parent_id')) {
            $menus = \App\Models\MenuItem::whereNull('parent_id')->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
                ->orderBy('sort_order')->orderBy('id')->get()->groupBy('location');

            return view('admin.menu.index', ['def' => $def, 'header' => $menus['header'] ?? collect(), 'footer' => $menus['footer'] ?? collect()]);
        }
        $query = $def['model']::query();

        if ($search = trim((string) $request->query('q'))) {
            $term = '%'.mb_strtolower(str_replace(['%', '_'], ['\%', '\_'], $search)).'%';
            $query->where(function ($q) use ($def, $term) {
                foreach ($def['search'] ?? [] as $col) {
                    // JSON columns are searched as text (any language). Casting + LOWER makes it
                    // case-insensitive on MySQL too, where JSON compares with a binary collation.
                    $q->orWhereRaw('LOWER(CAST('.$q->getGrammar()->wrap($col).' AS CHAR)) LIKE ?', [$term]);
                }
            });
        }
        // Dropdown filters from config: select fields, trek, and on/off fields
        foreach ($this->filters($def) as $key => $filter) {
            $value = $request->query($key);
            if ($value === null || $value === '' || ! array_key_exists($value, $filter['options'])) {
                continue;
            }
            $query->where($key, $filter['bool'] ? $value === '1' : $value);
        }

        if (isset($def['order'])) {
            $query->orderBy($def['order'][0], $def['order'][1]);
        } else {
            $query->ordered();
        }
        if ($resource === 'departures') {
            $query->with('trek');
        }

        $items = $query->paginate($def['per_page'] ?? 25)->withQueryString();
        $filters = $this->filters($def);
        $toggles = $this->toggles($def);

        return view('admin.resources.index', compact('def', 'items', 'filters', 'toggles'));
    }

    /** Filter dropdowns: key => [label, options, bool]. Defaults to a Published filter. */
    private function filters(array $def): array
    {
        $out = [];
        foreach ($def['filters'] ?? ['is_published'] as $key) {
            $field = collect($def['fields'])->firstWhere('name', $key);
            if (isset($def['toggles'][$key]) || in_array($key, ['is_published', 'is_featured'], true)) {
                [$on, $off] = $def['toggles'][$key] ?? ['Published', 'Hidden'];
                $out[$key] = ['label' => ($def['columns'][$key] ?? null) ?: (['is_featured' => 'Featured', 'is_published' => 'Status'][$key] ?? $field['label'] ?? $on), 'options' => ['1' => $on, '0' => $off], 'bool' => true];
            } elseif (($field['type'] ?? null) === 'trek') {
                $out[$key] = ['label' => 'Trek', 'options' => \App\Models\Trek::orderBy('sort_order')->get()->mapWithKeys(fn ($t) => [(string) $t->id => $t->tr('name', 'en')])->all(), 'bool' => false];
            } elseif (($field['type'] ?? null) === 'menuparent') {
                $out[$key] = ['label' => 'Menu', 'options' => \App\Models\MenuItem::whereNull('parent_id')->orderByRaw("CASE location WHEN 'header' THEN 0 ELSE 1 END")->orderBy('sort_order')->get()
                    ->mapWithKeys(fn ($m) => [(string) $m->id => $m->tr('title', 'en').($m->location === 'footer' ? ' (footer)' : '')])->all(), 'bool' => false];
            } elseif (isset($field['options'])) {
                $out[$key] = ['label' => $field['label'], 'options' => array_map('strval', $field['options']), 'bool' => false];
            }
        }

        return $out;
    }

    /** On/off fields that can be switched from the list. */
    private function toggles(array $def): array
    {
        return $def['toggles'] ?? ['is_published' => ['Published', 'Hidden']];
    }

    public function create(string $resource)
    {
        $def = $this->def($resource);
        $this->denyIfEditOnly($def);
        $item = new $def['model'](['is_published' => true]);
        if ($resource === 'menu') {
            // "+ Add link" inside a menu card, or "+ Add footer column"
            $parent = \App\Models\MenuItem::whereNull('parent_id')->find(request()->integer('parent_id'));
            if ($parent) {
                $item->parent_id = $parent->id;
                $item->sort_order = (int) $parent->children()->max('sort_order') + 1;
                $item->action = 'section';
            } elseif (in_array(request('location'), ['header', 'footer'], true)) {
                $item->location = request('location');
                $item->style = 'list';
                $item->sort_order = (int) \App\Models\MenuItem::whereNull('parent_id')->where('location', request('location'))->max('sort_order') + 1;
            }
        }

        return view('admin.resources.form', compact('def', 'item'));
    }

    public function store(Request $request, string $resource)
    {
        $def = $this->def($resource);
        $this->denyIfEditOnly($def);
        $item = new $def['model'];
        $this->save($request, $def, $item);
        if ($resource === 'menu') {
            return $this->backToMenu($item, ($item->parent_id ? 'Link' : 'Menu').' “'.$item->tr('title', 'en').'” added.');
        }

        return redirect()->route('admin.resources.edit', [$resource, $item->id])
            ->with('success', $def['singular'].' created.');
    }

    public function edit(string $resource, int $id)
    {
        $def = $this->def($resource);
        $item = $this->find($def, $id);

        return view('admin.resources.form', compact('def', 'item'));
    }

    public function update(Request $request, string $resource, int $id)
    {
        $def = $this->def($resource);
        $item = $this->find($def, $id);
        $this->save($request, $def, $item);
        if ($resource === 'menu') {
            return $this->backToMenu($item, '“'.$item->tr('title', 'en').'” saved. Refresh the website to see it.');
        }

        return redirect()->route('admin.resources.edit', [$resource, $item->id])
            ->with('success', $def['singular'].' saved.');
    }

    /** Back to the menu page, scrolled to the menu card the item belongs to. */
    private function backToMenu(CmsModel $item, string $message)
    {
        return redirect()->to(route('admin.resources.index', 'menu').'#menu-'.($item->parent_id ?: $item->id))->with('success', $message);
    }

    public function destroy(string $resource, int $id)
    {
        $def = $this->def($resource);
        $this->denyIfEditOnly($def);
        $item = $this->find($def, $id);
        foreach ($def['fields'] as $f) {
            if ($f['type'] === 'image') {
                Media::delete($item->{$f['name']});
            } elseif ($f['type'] === 'gallery') {
                foreach ((array) $item->{$f['name']} as $path) {
                    Media::delete($path);
                }
            }
        }
        $item->delete();
        if ($resource === 'menu') {
            return $this->backToMenu($item, '“'.$item->tr('title', 'en').'” deleted.');
        }

        return redirect()->route('admin.resources.index', $resource)
            ->with('success', $def['singular'].' deleted.');
    }

    public function toggle(Request $request, string $resource, int $id)
    {
        $def = $this->def($resource);
        $this->denyIfEditOnly($def);
        $item = $this->find($def, $id);
        $field = $request->input('field', 'is_published');
        $toggles = $this->toggles($def);
        abort_unless(isset($toggles[$field]), 422);
        $item->{$field} = ! $item->{$field};
        $item->save();

        $name = $item->isTranslatable('name') ? $item->tr('name', 'en') : ($item->isTranslatable('title') ? $item->tr('title', 'en') : $def['singular']);

        return back()->with('success', ($name ?: $def['singular']).': '.($item->{$field} ? $toggles[$field][0] : $toggles[$field][1]).'.');
    }

    private function save(Request $request, array $def, CmsModel $item): void
    {
        $rules = Fields::rules($def['fields']);
        foreach ($def['fields'] as $f) {
            if ($f['name'] === 'slug') {
                $rules['slug'] .= '|'.Rule::unique($item->getTable(), 'slug')->ignore($item->id);
            }
        }
        $request->validate($rules, [], Fields::attributeNames($def['fields']));

        $old = [];
        foreach ($def['fields'] as $f) {
            $old[$f['name']] = $item->getAttribute($f['name']);
        }
        $values = Fields::values($def['fields'], $request, '', $old, $def['key']);
        if (isset($values['slug'])) {
            $values['slug'] = strtolower($values['slug']);
        }
        $item->fill($values)->save();
    }
}
