@extends('admin.layout')
@section('title', 'Dashboard')

@section('content')
<section class="cards">
  @foreach ($counts as $key => $c)
    <a class="stat" href="{{ route('admin.resources.index', $key) }}">
      <span class="stat__icon">@include('admin.partials.icon', ['name' => $c['icon']])</span>
      <span class="stat__n">{{ $c['total'] }}</span>
      <span class="stat__label">{{ $c['label'] }}</span>
      <span class="stat__sub">{{ $c['published'] }} published</span>
    </a>
  @endforeach
</section>

<div class="dash-grid">
  <section class="panel">
    <header class="panel__head"><h2>Quick actions</h2></header>
    <div class="quick">
      <a class="btn" href="{{ route('admin.resources.create', 'treks') }}">@include('admin.partials.icon', ['name' => 'plus']) Add a trek</a>
      <a class="btn" href="{{ route('admin.resources.create', 'departures') }}">@include('admin.partials.icon', ['name' => 'plus']) Add a departure</a>
      <a class="btn" href="{{ route('admin.resources.create', 'posts') }}">@include('admin.partials.icon', ['name' => 'plus']) Write an article</a>
      <a class="btn" href="{{ route('admin.resources.create', 'reviews') }}">@include('admin.partials.icon', ['name' => 'plus']) Add a traveler story</a>
      <a class="btn" href="{{ route('admin.resources.create', 'faqs') }}">@include('admin.partials.icon', ['name' => 'plus']) Add an FAQ</a>
      <a class="btn" href="{{ route('admin.sections.edit', 'hero') }}">@include('admin.partials.icon', ['name' => 'edit']) Edit the hero</a>
      <a class="btn" href="{{ route('admin.settings.edit', 'company') }}">@include('admin.partials.icon', ['name' => 'edit']) Contact details</a>
    </div>
  </section>

  <section class="panel">
    <header class="panel__head"><h2>Website assistant</h2><a href="{{ route('admin.settings.edit', 'trip') }}">Trip info →</a></header>
    <p class="muted small">The chat assistant on the website answers from the published content in this CMS: treks, prices, departures, FAQs, packing list, contact details and trip info. Edit any of them and the assistant uses the new information straight away. For personal questions it hands visitors over to WhatsApp or email.</p>
  </section>

  <section class="panel">
    <header class="panel__head"><h2>Upcoming departures</h2><a href="{{ route('admin.resources.index', 'departures') }}">All →</a></header>
    @forelse ($upcoming as $d)
      <div class="row-line">
        <span>{{ $d->date->format('j M Y') }}</span>
        <strong>{{ $d->trek_name }}</strong>
        <span class="pill {{ $d->seats <= 3 ? 'pill--warn' : '' }}">{{ $d->seats }} seats</span>
      </div>
    @empty
      <p class="muted">No upcoming departures.</p>
    @endforelse
  </section>

  @unless (config('cms.multilingual'))
  <section class="panel">
    <header class="panel__head"><h2>Where to edit what</h2></header>
    <ul class="where-list">
      <li><span>Navbar menus &amp; every sub-item, footer link columns</span><a href="{{ route('admin.resources.index', 'menu') }}">Menu</a></li>
      <li><span>Logo text, contact details, WhatsApp, social links</span><a href="{{ route('admin.settings.edit', 'company') }}">Settings</a></li>
      <li><span>Navbar buttons (Plan Your Trip, WhatsApp)</span><a href="{{ route('admin.sections.edit', 'navbar') }}">Sections → Navbar</a></li>
      <li><span>Each section from hero to footer: texts, photos and that section's buttons</span><a href="{{ route('admin.sections.index') }}">Homepage Sections</a></li>
      <li><span>Pop-ups: Plan form, trek details, articles, team, photo credits, chat assistant</span><a href="{{ route('admin.sections.index') }}#pop-ups">Sections → Pop-ups</a></li>
      <li><span>All button names and labels in one list, incl. the chat assistant</span><a href="{{ route('admin.ui.edit') }}">Buttons &amp; Labels</a></li>
      <li><span>Footer tagline, copyright, payment &amp; association badges</span><a href="{{ route('admin.settings.edit', 'footer') }}">Settings → Footer</a></li>
      <li><span>Browser tab title &amp; Google description</span><a href="{{ route('admin.settings.edit', 'seo') }}">Settings → SEO</a></li>
    </ul>
  </section>
  @endunless

  @if (config('cms.multilingual'))
  <section class="panel">
    <header class="panel__head"><h2>Translations</h2><a href="{{ route('admin.ui.edit') }}">Interface text →</a></header>
    <p class="muted small">How much of the website text has been translated. Missing text shows in English.</p>
    <div class="bars">
      @foreach ($translations as $code => $t)
        <div class="bar {{ in_array($code, $enabledLocales, true) ? '' : 'is-off' }}">
          <span class="bar__label">{{ $t['native'] }} <small>{{ $t['name'] }}</small></span>
          <span class="bar__track"><i style="width: {{ $t['pct'] }}%"></i></span>
          <span class="bar__pct">{{ $t['pct'] }}%</span>
        </div>
      @endforeach
    </div>
    <p class="muted small">Faded languages are hidden on the website (<a href="{{ route('admin.languages.index') }}">change</a>).</p>
  </section>
  @endif

  <section class="panel panel--wide">
    <header class="panel__head"><h2>Website API</h2></header>
    <p class="muted small">The website reads its content from these endpoints. All content is in English (multi-language is on hold).</p>
    <ul class="api-list">
      <li><code>GET {{ url('/api/v1/site') }}</code> <span>everything for the homepage, in one request</span></li>
      <li><code>GET {{ url('/api/v1/treks') }}</code> · <code>/treks/{slug}</code> · <code>/departures</code></li>
      <li><code>GET {{ url('/api/v1/posts') }}</code> · <code>/posts/{slug}</code> · <code>/guides</code> · <code>/guides/{slug}</code></li>
      <li><code>GET {{ url('/api/v1/faqs') }}</code> · <code>/team</code> · <code>/reviews</code> · <code>/videos</code> · <code>/categories</code></li>
      <li><code>GET {{ url('/api/v1/settings') }}</code> · <code>/sections</code> · <code>/ui</code> · <code>/languages</code></li>
    </ul>
  </section>
</div>
@endsection
