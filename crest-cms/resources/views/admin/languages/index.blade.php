@extends('admin.layout')
@section('title', 'Languages')
@section('crumb', 'Website')

@section('content')
<p class="muted intro">Add any language your visitors speak. When a language is switched to <strong>Shown</strong>, all the English content of the website (texts, treks, menus, buttons…) is translated into it with Google Translate first. After that, every time you save English anywhere in the admin, the changed content is translated into every <strong>shown</strong> language. Hidden languages are not translated; they are brought up to date when they are switched on again. English is the main language and cannot be removed. The chat assistant always stays in English.</p>

{{-- Add a language --}}
<section class="form-card lang-add">
  <h2 class="form-card__title">Add a language</h2>
  <form method="post" action="{{ route('admin.languages.store') }}" id="lang-add-form" autocomplete="off">
    @csrf
    <div class="field">
      <label class="field__label" for="lang-search">Language</label>
      <input id="lang-search" type="text" list="lang-catalog" placeholder="Start typing, e.g. Telugu, Thai, Swahili…" value="{{ old('name') }}" />
      <datalist id="lang-catalog">
        @foreach ($catalog as $c)
          <option value="{{ $c['name'] }}" label="{{ $c['native'] }} · {{ $c['code'] }}"></option>
        @endforeach
      </datalist>
      <p class="help">Pick from the list to fill in everything automatically, or type any language and complete the details below.</p>
    </div>
    <div class="field-row field-row--4">
      <div class="field">
        <label class="field__label" for="lang-name">English name <span class="req">*</span></label>
        <input id="lang-name" type="text" name="name" value="{{ old('name') }}" required maxlength="60" />
      </div>
      <div class="field">
        <label class="field__label" for="lang-native">Native name</label>
        <input id="lang-native" type="text" name="native" value="{{ old('native') }}" maxlength="60" dir="auto" placeholder="As shown in the switcher" />
      </div>
      <div class="field">
        <label class="field__label" for="lang-code">Code <span class="req">*</span></label>
        <input id="lang-code" type="text" name="code" value="{{ old('code') }}" required maxlength="10" pattern="[a-zA-Z]{2,3}(-[a-zA-Z0-9]{2,4})?" placeholder="e.g. te" />
        <p class="help">2-3 letters (ISO code), e.g. te, th, pt-br.</p>
      </div>
      <div class="field">
        <span class="field__label">Options</span>
        <label class="check check--inline"><input type="hidden" name="rtl" value="0" /><input type="checkbox" id="lang-rtl" name="rtl" value="1" @checked(old('rtl')) /> <span>Right-to-left script</span></label>
        <label class="check check--inline"><input type="hidden" name="enabled" value="0" /><input type="checkbox" name="enabled" value="1" @checked(old('enabled', true)) /> <span>Translate now and show on the website</span></label>
      </div>
    </div>
    <div class="form-card__actions"><button class="btn btn--primary" type="submit">@include('admin.partials.icon', ['name' => 'plus']) Add &amp; translate</button></div>
  </form>
</section>

{{-- Current languages --}}
<div class="table-wrap lang-table">
<table class="table table--list">
  <thead><tr><th>Language</th><th>Code</th><th>Translated</th><th>On website</th><th class="col-actions"><span class="sr-only">Actions</span></th></tr></thead>
  <tbody>
  @foreach ($languages as $code => $l)
    @php $en = $code === 'en'; @endphp
    <tr class="{{ $l['enabled'] ? '' : 'is-hidden-row' }}">
      <td data-label="Language"><span class="strong" lang="{{ $code }}" dir="{{ $l['rtl'] ? 'rtl' : 'ltr' }}">{{ $l['native'] }}</span><span class="cell-sub"><span>{{ $l['name'] }}</span>@if ($en)<span class="mini-badge">Main language · cannot be removed</span>@elseif (! $l['enabled'])<span class="lang-pending">Hidden · translated when switched on</span>@endif</span></td>
      <td data-label="Code"><code>{{ $code }}</code></td>
      <td data-label="Translated">
        @if ($en)<span class="muted">Source (you edit English)</span>
        @else
          <span class="progress-link" title="Buttons & labels translated">
            <span class="bar__track"><i style="width: {{ $l['translated'] ? $progress[$code] : min($progress[$code], 99) }}%"></i></span> {{ $l['translated'] ? $progress[$code] : min($progress[$code], 99) }}%
          </span>
        @endif
      </td>
      <td data-label="On website">
        @if ($en)
          <span class="toggle is-on is-locked" title="Always shown"><i aria-hidden="true"></i>Shown</span>
        @else
          @if ($l['enabled'])
            <form method="post" action="{{ route('admin.languages.toggle', $code) }}">
              @csrf @method('PATCH')
              <button class="toggle is-on" title="Click to hide it from the website"><i aria-hidden="true"></i>Shown</button>
            </form>
          @else
            {{-- showing = translate all English content into it first, then show --}}
            <button type="button" class="toggle" data-fill-lang="{{ $code }}" data-fill-name="{{ $l['name'] }}" title="Click to translate the website into {{ $l['name'] }} and show it"><i aria-hidden="true"></i>Hidden</button>
          @endif
        @endif
      </td>
      <td class="col-actions col-actions--users">
        <div class="users-actions">
          @unless ($en)
            <form method="post" action="{{ route('admin.languages.destroy', $code) }}" data-confirm="Delete {{ $l['name'] }}? It disappears from the website and the admin.">
              @csrf @method('DELETE')
              <button class="icon-btn icon-btn--danger" title="Remove {{ $l['name'] }}">@include('admin.partials.icon', ['name' => 'trash'])<span class="sr-only">Remove</span></button>
            </form>
          @endunless
        </div>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
</div>
<p class="help list-help">The website language switcher shows the languages marked <strong>Shown</strong>, in this order. Switching a language on translates the whole website into it first (keep the tab open until it finishes); switching it off just hides it. Every edit page shows a tab per language so you can check the translations; only English can be changed there. The Google Translate key is set in the <code>.env</code> file (<code>GOOGLE_TRANSLATE_API_KEY</code>).</p>

<script>
  // Catalogue for auto-fill (code, names, direction)
  const LANG_CATALOG = @json($catalog);
  const $id = (id) => document.getElementById(id);
  $id("lang-search").addEventListener("input", (e) => {
    const q = e.target.value.trim().toLowerCase();
    const hit = LANG_CATALOG.find((c) => c.name.toLowerCase() === q || c.native.toLowerCase() === q || c.code === q);
    if (hit) {
      $id("lang-name").value = hit.name; $id("lang-native").value = hit.native;
      $id("lang-code").value = hit.code; $id("lang-rtl").checked = hit.rtl;
    } else if (q && !$id("lang-code").dataset.touched) {
      $id("lang-name").value = e.target.value.trim();
    }
  });
  $id("lang-code").addEventListener("input", (e) => { e.target.dataset.touched = "1"; });

  // Fill a language: translate all English content step by step, with a progress bar
  const STEPS = @json($steps);
  const LANG_URL = @json(url('admin/languages'));
  async function fillLanguage(code, name, show) {
    const { progressPopup, postJson, runJobs } = window.cmsTranslate;
    const ui = progressPopup(`Translating the website into ${name}…`);
    const jobs = STEPS.map((s, i) => ({ label: `${s.label} (${i + 1} of ${STEPS.length})`, run: () => postJson(`${LANG_URL}/${encodeURIComponent(code)}/translate`, { step: s.step }) }));
    const ok = await runJobs(ui, jobs);
    if (!ok) { ui.close(); location.reload(); return; }
    ui.step("Finishing…");
    try { await postJson(`${LANG_URL}/${encodeURIComponent(code)}/translated`, { show }); } catch {}
    location.reload();
  }
  document.addEventListener("click", (e) => {
    const b = e.target.closest("[data-fill-lang]");
    if (b) fillLanguage(b.dataset.fillLang, b.dataset.fillName, true);
  });
  @if (session('fill'))
    window.addEventListener("load", () => fillLanguage(@json(session('fill')['code']), @json($languages[session('fill')['code']]['name'] ?? session('fill')['code']), @json((bool) session('fill')['show'])));
  @endif
</script>
@endsection
