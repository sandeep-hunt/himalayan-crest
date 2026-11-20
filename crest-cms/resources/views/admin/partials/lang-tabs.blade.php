{{-- Language tabs: English + the languages SHOWN on the website (hidden languages are not shown in the admin). --}}
@php $shown = \App\Support\Locales::enabled(); @endphp
@if (config('cms.multilingual') && count($shown) > 1)
<div class="lang-tabs" role="tablist" aria-label="Content language" data-lang-tabs>
  <span class="lang-tabs__label">Language:</span>
  @foreach (config('cms.locales') as $code => $l)
    @continue(! in_array($code, $shown, true))
    <button type="button" role="tab" data-locale="{{ $code }}" aria-selected="{{ $code === 'en' ? 'true' : 'false' }}" title="{{ $l['name'] }}">
      <span class="lang-tabs__code">{{ strtoupper($code) }}</span><span class="lang-tabs__name">{{ $l['native'] }}</span><i class="lang-tabs__dot" aria-hidden="true"></i>
    </button>
  @endforeach
  <p class="lang-tabs__hint">You edit <strong>English</strong> only. When you press Save, the content you changed is translated with Google Translate into the other languages and saved too.</p>
  <p class="lang-tabs__view" data-view-note hidden>Viewing <b></b> (view only): the automatic translation of the English text. To change it, go back to <button type="button" class="link-btn" data-locale-go="en">English</button>, edit and press Save.</p>
</div>
@endif
