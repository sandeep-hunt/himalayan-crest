<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  @if (config('cms.multilingual'))<meta name="cms-translate-url" content="{{ route('admin.translate') }}" />@endif
  <meta name="robots" content="noindex, nofollow" />
  <title>@yield('title', 'Dashboard') · Crest Himalaya CMS</title>
  @include('admin.partials.favicon')
  <link rel="stylesheet" href="{{ asset('cms-assets/admin.css') }}?v=19" />
</head>
<body>
@php
  $resources = config('cms.resources');
  $nav = [
    'Homepage' => [
      ['label' => 'Homepage Sections', 'icon' => 'home', 'url' => route('admin.sections.index'), 'active' => request()->routeIs('admin.sections.*')],
    ],
    'Content' => [],
    'Website' => [
      ...(config('cms.multilingual') ? [['label' => 'Languages', 'icon' => 'globe', 'url' => route('admin.languages.index'), 'active' => request()->routeIs('admin.languages.*')]] : []),
      ['label' => 'Buttons & Labels', 'icon' => 'edit', 'url' => route('admin.ui.edit'), 'active' => request()->routeIs('admin.ui.*')],
      ['label' => 'Settings', 'icon' => 'settings', 'url' => route('admin.settings.edit'), 'active' => request()->routeIs('admin.settings.*')],
    ],
  ];
  foreach ($resources as $key => $r) {
    $nav[$r['group'] ?? 'Content'][] = ['label' => $r['label'], 'icon' => $r['icon'], 'url' => route('admin.resources.index', $key), 'active' => request()->route('resource') === $key];
  }
@endphp
<div class="shell">
  <aside class="side" id="side">
    <a class="side__brand" href="{{ route('admin.dashboard') }}">
      <svg viewBox="0 0 100 110" aria-hidden="true"><path d="M50 5 91 13v49q0 8-6 13L50 104 15 75q-6-5-6-13V13z" fill="none" stroke="currentColor" stroke-width="7" stroke-linejoin="round"/><path d="M17 74 34 50l4 8 12-26 10 18 6-8 18 32-8-6-4 6-6-12-4 8-6-12-4 14-4-14-4 12-6-8-8 10-4-6z" fill="#DCD3C4"/></svg>
      <span><strong>CREST HIMALAYA</strong><small>Content Manager</small></span>
    </a>
    <nav class="side__nav">
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">@include('admin.partials.icon', ['name' => 'dashboard']) Dashboard</a>
      @foreach ($nav as $group => $links)
        <p class="side__group">{{ $group }}</p>
        @foreach ($links as $l)
          <a href="{{ $l['url'] }}" class="{{ $l['active'] ? 'is-active' : '' }}">@include('admin.partials.icon', ['name' => $l['icon']]) {{ $l['label'] }}</a>
        @endforeach
      @endforeach
    </nav>
    <div class="side__foot">
      <a href="{{ route('admin.profile.edit') }}" class="{{ request()->routeIs('admin.profile.*') ? 'is-active' : '' }}" title="Your account: name and password">@include('admin.partials.icon', ['name' => 'user']) {{ auth()->user()->name }}</a>
      <form method="post" action="{{ route('admin.logout') }}">@csrf<button type="submit">@include('admin.partials.icon', ['name' => 'logout']) Log out</button></form>
    </div>
  </aside>

  <div class="main">
    <header class="top">
      <button class="top__burger" type="button" data-toggle-side aria-label="Menu"><span></span><span></span></button>
      <div class="top__title">
        @hasSection('crumb')<p class="crumb">@yield('crumb')</p>@endif
        <h1>@yield('title', 'Dashboard')</h1>
      </div>
      <div class="top__actions">@yield('actions')</div>
    </header>

    @if (session('success'))
      <div class="flash flash--ok" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
      <div class="flash flash--err" role="alert">
        <strong>Please fix {{ $errors->count() === 1 ? 'this problem' : 'these problems' }}:</strong>
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif

    <main class="content">
      @yield('content')
    </main>
  </div>
</div>
<script src="{{ asset('cms-assets/admin.js') }}?v=34" defer></script>
</body>
</html>
