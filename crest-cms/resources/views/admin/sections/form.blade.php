@extends('admin.layout')
@section('title', $def['label'])
@section('crumb')
  <a href="{{ route('admin.sections.index') }}">Homepage Sections</a>
@endsection

@section('content')
@if (! empty($def['note']))
  <p class="muted intro">{!! preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', fn ($m) => '<a href="'.url(html_entity_decode($m[2])).'">'.$m[1].'</a>', e($def['note'])) !!}</p>
@endif
<form class="edit-form" method="post" enctype="multipart/form-data" action="{{ route('admin.sections.update', $def['key']) }}">
  @csrf @method('PUT')
  @include('admin.partials.lang-tabs')
  @if ($def['fields'])
    <div class="form-card">
      <h2 class="form-card__title">{{ $def['fields_title'] ?? 'Text & photos' }}</h2>
      @foreach ($def['fields'] as $f)
        @include('admin.partials.field', ['f' => $f, 'value' => $content[$f['name']] ?? ($f['default'] ?? null), 'prefix' => 'content'])
      @endforeach
    </div>
  @endif

  @foreach ($settingGroups as $gKey => $g)
    <div class="form-card">
      <h2 class="form-card__title">{{ $gKey === 'company' ? 'Contact details' : $g['label'] }}</h2>
      <p class="help">The same as in <a href="{{ route('admin.settings.edit', $gKey) }}">Settings → {{ $g['label'] }}</a>: a change here changes it everywhere on the website.</p>
      @foreach ($g['fields'] as $f)
        @include('admin.partials.field', ['f' => $f, 'value' => $settingValues[$f['name']] ?? null, 'prefix' => 'settings'])
      @endforeach
    </div>
  @endforeach

  @foreach ($buttons as $heading => $group)
    <div class="form-card" @if ($loop->first) id="buttons" @endif>
      <h2 class="form-card__title">{{ $heading }}</h2>
      @foreach ($group as $uiKey => $b)
        @php $id = 'b_'.str_replace('.', '_', $uiKey); $changed = $b['value'] !== '' && $b['value'] !== $b['default']; @endphp
        <div class="field field--text {{ $errors->has("strings.$uiKey") ? 'has-error' : '' }}">
          <label class="field__label" for="{{ $id }}">{{ $b['label'] }}</label>
          @php $long = mb_strlen($b['default']) > 90; @endphp
          @foreach ($b['englishOnly'] ? ['en' => config('cms.locales.en')] : config('cms.locales') as $code => $l)
            @php $en = $code === 'en'; $v = $en ? old("strings.$uiKey.en", $b['value'] ?: $b['default']) : ($b['values'][$code] ?? ''); @endphp
            <div class="{{ $b['englishOnly'] ? '' : 'tr-pane' }}" data-locale="{{ $code }}" @if (! $en) hidden @endif>
              @if (! $en)
                @include('admin.partials.tr-view', ['name' => "strings[$uiKey][$code]", 'value' => $v, 'hasEnglish' => true, 'code' => $code, 'rtl' => ! empty($l['rtl'])])
              @elseif ($long)
                <textarea id="{{ $id }}" name="strings[{{ $uiKey }}][en]" rows="3" maxlength="1000" lang="en" dir="auto" class="{{ $changed ? 'is-changed' : '' }}">{{ $v }}</textarea>
              @else
                <input type="text" id="{{ $id }}" name="strings[{{ $uiKey }}][en]" value="{{ $v }}" maxlength="1000" lang="en" dir="auto" class="{{ $changed ? 'is-changed' : '' }}" />
              @endif
            </div>
          @endforeach
          <p class="help">Original: “{{ $b['default'] }}”@if (str_contains($b['default'], '{')) · keep the text in {curly brackets}, it is filled in automatically @endif · empty = original @if ($b['englishOnly'] && count(\App\Support\Locales::enabled()) > 1) · <strong>English on every language</strong> (the chat assistant is English only) @endif</p>
        </div>
      @endforeach
    </div>
  @endforeach

  <div class="form-bar">
    <a class="btn" href="{{ route('admin.sections.index') }}">Cancel</a>
    <button class="btn btn--primary" type="submit">@include('admin.partials.icon', ['name' => 'check']) Save</button>
  </div>
</form>
@endsection
