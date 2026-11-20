@extends('admin.layout')
@section('title', 'Buttons & Labels')
@section('crumb', 'Website')

@section('content')
@php $multi = count(\App\Support\Locales::enabled()) > 1; @endphp
<p class="muted intro">Every button name, label and small text on the website: navigation, trek cards, section buttons, footer, the Plan Your Trip form and the chat assistant. Change the English text and save. Leave a box empty to go back to the original wording.@if ($multi) The other languages are translated automatically when you save; the chat assistant stays in English.@endif</p>

<div class="toolbar">
  <span class="toolbar__count">{{ $done }} of {{ $total }} changed from the original</span>
  <label class="search">@include('admin.partials.icon', ['name' => 'search'])<input type="search" placeholder="Find a text…" data-filter-rows="#ui-table" /></label>
</div>

<nav class="form-toc" aria-label="Groups">
  @foreach ($groups as $group => $strings)<a href="#grp-{{ \Illuminate\Support\Str::slug($group) }}">{{ $group }} <small>({{ count($strings) }})</small></a>@endforeach
</nav>

<form method="post" action="{{ route('admin.ui.update') }}" class="edit-form">
  @csrf @method('PUT')
  @include('admin.partials.lang-tabs')
  <div class="table-wrap">
  <table class="table table--ui" id="ui-table">
    <thead><tr><th>Original wording</th><th>Text on the website</th></tr></thead>
    @foreach ($groups as $group => $strings)
      <tbody>
        <tr class="table__group" id="grp-{{ \Illuminate\Support\Str::slug($group) }}"><th colspan="2">{{ $group }}@if ($multi && str_starts_with(array_key_first($strings), 'chat.')) <small class="muted">· English on every language</small>@endif</th></tr>
        @foreach ($strings as $key => $default)
          @php $cur = $values[$key]['en'] ?? ''; $englishOnly = \App\Support\ContentTranslator::englishOnly($key); @endphp
          <tr data-row>
            <td data-label="Original"><span class="ui-default">{{ $default }}</span><code class="ui-key">{{ $key }}</code></td>
            <td data-label="On the website">
              @foreach ($englishOnly ? ['en' => $locales['en']] : $locales as $code => $l)
                @php $en = $code === 'en'; @endphp
                <div class="{{ $englishOnly ? '' : 'tr-pane' }}" data-locale="{{ $code }}" @unless ($en) hidden @endunless>
                  @if ($en)
                    <input type="text" name="strings[{{ $key }}][en]" value="{{ old("strings.$key.en", $cur ?: $default) }}" dir="auto" lang="en" aria-label="{{ $default }}" class="{{ $cur !== '' && $cur !== $default ? 'is-changed' : '' }}" />
                  @else
                    @include('admin.partials.tr-view', ['name' => "strings[$key][$code]", 'value' => $values[$key][$code] ?? '', 'hasEnglish' => true, 'code' => $code, 'rtl' => ! empty($l['rtl'])])
                  @endif
                </div>
              @endforeach
            </td>
          </tr>
        @endforeach
      </tbody>
    @endforeach
  </table>
  </div>
  <p class="help">Text in curly brackets such as <code>{n}</code>, <code>{trek}</code>, <code>{date}</code> or <code>{email}</code> is filled in automatically. Keep it in your text.</p>
  <div class="form-bar">
    <a class="btn" href="{{ route('admin.ui.edit') }}">Cancel</a>
    <button class="btn btn--primary" type="submit">@include('admin.partials.icon', ['name' => 'check']) Save buttons &amp; labels</button>
  </div>
</form>
@endsection
