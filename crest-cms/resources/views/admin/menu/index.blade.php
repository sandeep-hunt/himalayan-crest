@extends('admin.layout')
@section('title', $def['label'])
@section('crumb', $def['group'] ?? 'Homepage')

@section('actions')
  <form class="menu-search" method="get"><label class="search">@include('admin.partials.icon', ['name' => 'search'])<input type="search" name="q" placeholder="Find a link…" /></label></form>
@endsection

@section('content')
@php
  use App\Models\MenuItem;
  $styles = [
      'list' => 'Simple dropdown list', 'mega' => 'Large dropdown with columns',
      'peaks' => 'Peak climbs list (filled automatically) + your links',
      'departures' => 'Upcoming departures (filled automatically) + your links',
      'guides' => 'Travel guides (filled automatically) + your links',
  ];
  $does = function (MenuItem $l) {
      $t = $l->target;
      return match ($l->action) {
          'section' => 'Scrolls to section “'.$t.'”',
          'filter' => 'Shows treks: '.$t,
          'trek' => 'Opens trek “'.$t.'”',
          'plan' => 'Opens the Plan Your Trip form',
          'chat' => 'Opens the chat on “'.$t.'”',
          'guides' => 'Opens the travel guides',
          'articles' => 'Opens the articles',
          'url' => 'Opens '.$t,
          default => '—',
      };
  };
@endphp

<p class="muted intro">This is how the menus look on the website. <strong>Header menus</strong> appear in the top bar from left to right; <strong>footer columns</strong> appear at the bottom of the page. Each card holds the links inside that menu. Click a name or the pencil to change its text, what it opens or its order.</p>

@foreach (['header' => ['Header menu (top bar)', 'Shown left to right in the navbar. Each one opens a dropdown.', 'Add header menu'], 'footer' => ['Footer columns', 'Shown left to right in the footer. The Contact column comes from the contact details.', 'Add footer column']] as $loc => [$heading, $explain, $addLabel])
  @php $list = $loc === 'header' ? $header : $footer; @endphp
  <section class="menu-area">
    <header class="menu-area__head">
      <div>
        <h2>{{ $heading }}</h2>
        <p class="muted">{{ $explain }}</p>
      </div>
    </header>

    @if ($loc === 'header')
      <ol class="menu-preview" aria-label="Order in the navbar">
        @foreach ($list as $m)<li><a href="#menu-{{ $m->id }}">{{ $m->tr('title', 'en') }}</a></li>@endforeach
      </ol>
    @endif

    <div class="menu-cards">
    @forelse ($list as $m)
      @php
        $links = $m->children->where('is_button', false);
        $buttons = $m->children->where('is_button', true);
        $groups = $m->style === 'mega' ? $links->groupBy(fn ($l) => $l->tr('column', 'en') ?: 'Without a column heading') : collect(['' => $links]);
      @endphp
      <article class="menu-card" id="menu-{{ $m->id }}">
        <header class="menu-card__head">
          <span class="menu-card__n">{{ $loop->iteration }}</span>
          <div class="menu-card__title">
            <a class="strong" href="{{ route('admin.resources.edit', ['menu', $m->id]) }}">{{ $m->tr('title', 'en') }}</a>
            <small>{{ $loc === 'header' ? ($styles[$m->style ?? 'list'] ?? 'Dropdown') : 'Footer column' }} · {{ $m->children->count() }} {{ $m->children->count() === 1 ? 'item' : 'items' }}</small>
          </div>
          <a class="icon-btn" href="{{ route('admin.resources.edit', ['menu', $m->id]) }}" title="Edit menu">@include('admin.partials.icon', ['name' => 'edit'])<span class="sr-only">Edit menu</span></a>
        </header>

        @if (in_array($m->style, ['peaks', 'departures', 'guides'], true))
          <p class="menu-card__auto">Part of this dropdown is filled automatically from {{ ['peaks' => 'the peak-climbing treks', 'departures' => 'the upcoming Fixed Departures', 'guides' => 'the Travel Guides'][$m->style] }}. The links below are added after it.</p>
        @endif

        <div class="menu-card__body">
          @foreach ($groups as $col => $items)
            <div class="menu-group">
              @if ($m->style === 'mega')<h3 class="menu-group__title">Column: {{ $col }}</h3>@endif
              <h4 class="menu-kind"><span class="kind kind--link">Links</span> normal text links</h4>
              @include('admin.menu.rows', ['items' => $items, 'empty' => 'No links yet.'])
            </div>
          @endforeach
          @if ($m->style === 'mega' || $buttons->count())
            <div class="menu-group menu-group--buttons">
              <h4 class="menu-kind"><span class="kind kind--button">Buttons</span> shown as a button at the bottom of the dropdown</h4>
              @include('admin.menu.rows', ['items' => $buttons, 'empty' => 'No buttons in this menu.'])
            </div>
          @endif
        </div>

      </article>
    @empty
      <p class="empty">No {{ $loc === 'header' ? 'header menus' : 'footer columns' }} yet.</p>
    @endforelse
    </div>
  </section>
@endforeach
@endsection
