{{--
  Renders one CMS field.
  $f       field definition (config/cms.php)
  $value   current stored value
  $prefix  input name prefix in dot notation ('' for resources, 'content' for sections, 'settings' for settings)
--}}
@php
  use App\Support\Fields;
  use App\Support\Media;
  $key = ltrim(($prefix ?? '').'.'.$f['name'], '.');
  $name = Fields::htmlName($key);
  $id = 'f_'.str_replace(['.', '[', ']'], '_', $key);
  $tr = ! empty($f['tr']);
  $req = ! empty($f['required']);
  $locales = config('cms.locales');
  $err = $errors->first($key) ?: ($tr ? $errors->first("$key.en") : null) ?: $errors->first($key.'_file') ?: $errors->first($key.'_files.*');
  $type = $f['type'];
@endphp

@if ($type === 'group')
  <fieldset class="group">
    <legend>{{ $f['label'] }}</legend>
    <div class="group__items">
      @for ($i = 0; $i < ($f['count'] ?? 1); $i++)
        <div class="group__item">
          <p class="group__n">{{ $i + 1 }}</p>
          @foreach ($f['fields'] as $sub)
            @include('admin.partials.field', ['f' => $sub, 'value' => ($value[$i] ?? [])[$sub['name']] ?? null, 'prefix' => "$key.$i"])
          @endforeach
        </div>
      @endfor
    </div>
  </fieldset>
@else
<div class="field field--{{ $type }} {{ $err ? 'has-error' : '' }}" @if (! empty($f['only'])) data-only="{{ $f['only'] }}" @endif>
  <label class="field__label" for="{{ $id }}{{ $tr ? '_en' : '' }}">
    {{ $f['label'] }}@if ($req)<span class="req" title="Required">*</span>@endif
    @if ($tr && count(\App\Support\Locales::enabled()) > 1)<span class="tr-badge" title="Edited in English; the other languages are translated automatically when you save">Translated automatically</span>@endif
  </label>

  @switch($type)
    @case('text')
    @case('textarea')
    @case('markdown')
    @case('lines')
      @php $tag = $type === 'text' ? 'input' : 'textarea'; $rows = $type === 'markdown' ? 14 : ($type === 'lines' ? 6 : 4); @endphp
      @if ($tr)
        @foreach ($locales as $code => $l)
          @php
            $v = old("$key.$code", Fields::display($f, $value, $code));
            $en = Fields::display($f, $value, 'en');
          @endphp
          <div class="tr-pane" data-locale="{{ $code }}" @if ($code !== 'en') hidden @endif>
            @if ($code !== 'en')
              {{-- other languages: view only --}}
              @include('admin.partials.tr-view', ['name' => "{$name}[{$code}]", 'value' => $v, 'hasEnglish' => (string) $en !== '', 'code' => $code, 'rtl' => ! empty($l['rtl'])])
            @elseif ($tag === 'input')
              <input type="text" id="{{ $id }}_{{ $code }}" name="{{ $name }}[{{ $code }}]" value="{{ $v }}" dir="{{ ! empty($l['rtl']) ? 'rtl' : 'auto' }}" lang="{{ $code }}" />
            @else
              <textarea id="{{ $id }}_{{ $code }}" name="{{ $name }}[{{ $code }}]" rows="{{ $rows }}" dir="{{ ! empty($l['rtl']) ? 'rtl' : 'auto' }}" lang="{{ $code }}" class="{{ $type === 'markdown' ? 'mono' : '' }}">{{ $v }}</textarea>
            @endif
          </div>
        @endforeach
      @else
        @php $v = old($key, Fields::display($f, $value)); @endphp
        @if ($tag === 'input')
          <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $v }}" @if ($req) required @endif />
        @else
          <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="{{ $type === 'markdown' ? 'mono' : '' }}">{{ $v }}</textarea>
        @endif
      @endif
      @if ($type === 'markdown')
        <p class="help">Formatting: blank line between paragraphs · <code>## Heading</code> · <code>- list item</code> · <code>**bold**</code> · <code>[link text](https://...)</code></p>
      @endif
      @break

    @case('number')
      <input type="number" id="{{ $id }}" name="{{ $name }}" value="{{ old($key, $value) }}" @if ($req) required @endif class="short" />
      @break

    @case('date')
      @php $dv = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value; @endphp
      <input type="date" id="{{ $id }}" name="{{ $name }}" value="{{ old($key, $dv) }}" @if ($req) required @endif class="short" />
      @break

    @case('select')
      <select id="{{ $id }}" name="{{ $name }}" @if ($req) required @endif>
        @unless ($req)<option value="">—</option>@endunless
        @foreach ($f['options'] as $ov => $ol)
          <option value="{{ $ov }}" @selected((string) old($key, $value) === (string) $ov)>{{ $ol }}</option>
        @endforeach
      </select>
      @break

    @case('menuparent')
      @php $current = old($key, $value); $editingId = request()->route('id'); @endphp
      <select id="{{ $id }}" name="{{ $name }}" data-menu-parent>
        <option value="">This is a menu (header menu or footer column)</option>
        @foreach (\App\Models\MenuItem::whereNull('parent_id')->orderByRaw("CASE location WHEN 'header' THEN 0 ELSE 1 END")->orderBy('sort_order')->get() as $m)
          @continue((string) $m->id === (string) $editingId)
          <option value="{{ $m->id }}" @selected((string) $current === (string) $m->id)>Link inside: {{ $m->tr('title', 'en') }} ({{ $m->location === 'footer' ? 'footer' : 'header' }})</option>
        @endforeach
      </select>
      @break

    @case('trek')
      <select id="{{ $id }}" name="{{ $name }}" @if ($req) required @endif>
        <option value="">Choose a trek…</option>
        @foreach (\App\Models\Trek::orderBy('sort_order')->orderBy('id')->get() as $t)
          <option value="{{ $t->id }}" @selected((string) old($key, $value) === (string) $t->id)>{{ $t->tr('name', 'en') }}{{ $t->is_published ? '' : ' (unpublished)' }}</option>
        @endforeach
      </select>
      @break

    @case('checkbox')
      <label class="switch">
        <input type="hidden" name="{{ $name }}" value="0" />
        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked(old($key, $value)) />
        <span class="switch__ui" aria-hidden="true"></span>
        <span class="switch__text">Yes</span>
      </label>
      @break

    @case('multicheck')
      @php $sel = array_map('strval', (array) old($key, $value ?? [])); @endphp
      <div class="checks">
        @foreach ($f['options'] as $ov => $ol)
          <label class="check"><input type="checkbox" name="{{ $name }}[]" value="{{ $ov }}" @checked(in_array((string) $ov, $sel, true)) /> <span>{{ $ol }}</span></label>
        @endforeach
      </div>
      @break

    @case('locales')
      @php $sel = (array) old($key, $value ?? array_keys($locales)); @endphp
      <div class="checks checks--locales">
        @foreach ($locales as $code => $l)
          <label class="check">
            <input type="checkbox" name="{{ $name }}[]" value="{{ $code }}" @checked($code === 'en' || in_array($code, $sel, true)) @disabled($code === 'en') />
            <span>{{ $l['native'] }} <small>{{ $l['name'] }}{{ ! empty($l['rtl']) ? ' · right-to-left' : '' }}</small></span>
          </label>
        @endforeach
        <input type="hidden" name="{{ $name }}[]" value="en" />
      </div>
      @break

    @case('secret')
      @php $has = is_string($value) && $value !== ''; @endphp
      <input type="password" id="{{ $id }}" name="{{ $name }}" value="" autocomplete="new-password" placeholder="{{ $has ? 'A key is saved. Type a new one to replace it.' : 'Paste the key here' }}" />
      @if ($has)
        <label class="check check--inline"><input type="checkbox" name="{{ Fields::htmlName($key.'_clear') }}" value="1" /> <span>Remove the saved key</span></label>
      @endif
      @break

    @case('image')
      @php $url = Media::url($value); @endphp
      <div class="img-field">
        <div class="img-field__preview" data-preview-for="{{ $id }}">
          @if ($url)<img src="{{ $url }}" alt="" />@else<span>No image</span>@endif
        </div>
        <div class="img-field__ctrl">
          <input type="file" id="{{ $id }}" name="{{ Fields::htmlName($key.'_file') }}" accept="image/jpeg,image/png,image/webp,image/gif" data-preview />
          @if ($url)
            <label class="check check--inline"><input type="checkbox" name="{{ Fields::htmlName($key.'_remove') }}" value="1" /> <span>Remove image</span></label>
          @endif
          <p class="help">JPG, PNG, WebP or GIF, up to 6 MB. Landscape photos at least 1600 px wide look best.</p>
        </div>
      </div>
      @break

    @case('gallery')
      <div class="gallery">
        @foreach ((array) $value as $path)
          <label class="gallery__item" title="Untick to remove">
            <img src="{{ Media::url($path) }}" alt="" />
            <input type="checkbox" name="{{ Fields::htmlName($key.'_keep') }}[]" value="{{ $path }}" checked />
            <span>Keep</span>
          </label>
        @endforeach
      </div>
      <input type="file" id="{{ $id }}" name="{{ Fields::htmlName($key.'_files') }}[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple />
      <p class="help">Add one or more photos. Untick a photo to remove it when you save.</p>
      @break
  @endswitch

  @if (! empty($f['help']))<p class="help">{{ $f['help'] }}</p>@endif
  @if ($err)<p class="field__err">{{ $err }}</p>@endif
</div>
@endif
