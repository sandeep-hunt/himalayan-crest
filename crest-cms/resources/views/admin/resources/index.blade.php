@extends('admin.layout')
@section('title', $def['label'])
@section('crumb', $def['group'] ?? 'Content')

@section('actions')
  @if (empty($def['edit_only']))
  <a class="btn btn--primary" href="{{ route('admin.resources.create', $def['key']) }}">@include('admin.partials.icon', ['name' => 'plus']) Add {{ strtolower($def['singular']) }}</a>
  @endif
@endsection

@section('content')
@php
  use App\Support\Media;
  use Illuminate\Support\Str;
  $imageCols = ['image', 'photo'];
  $cols = $def['columns'];
  $titleCol = collect(array_keys($cols))->first(fn ($c) => ! in_array($c, $imageCols, true));
  $fieldsByName = collect($def['fields'])->keyBy('name');
  $formats = $def['formats'] ?? [];
  $sub = $def['sub'] ?? [];
  $val = fn ($item, $col) => $item->isTranslatable($col) ? $item->tr($col, 'en') : $item->{$col};
  $fmt = function ($col, $v) use ($formats) {
      if ($v === null || $v === '') return '—';
      if (is_numeric($v) && in_array($col, ['price', 'max_altitude'], true)) $v = number_format((int) $v);
      return isset($formats[$col]) ? str_replace('{v}', $v, $formats[$col]) : $v;
  };
  $active = collect($filters)->keys()->filter(fn ($k) => request()->filled($k))->count() + (request()->filled('q') ? 1 : 0);
@endphp

<form class="toolbar" method="get">
  @if (! empty($def['search']))
    <label class="search">@include('admin.partials.icon', ['name' => 'search'])<input type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ strtolower($def['label']) }}…" /></label>
  @endif
  @foreach ($filters as $key => $filter)
    <label class="filter">
      <span class="filter__label">{{ $filter['label'] }}</span>
      <select name="{{ $key }}" onchange="this.form.submit()">
        <option value="">All</option>
        @foreach ($filter['options'] as $ov => $ol)
          <option value="{{ $ov }}" @selected((string) request($key) === (string) $ov)>{{ $ol }}</option>
        @endforeach
      </select>
    </label>
  @endforeach
  @if ($active)<a href="{{ route('admin.resources.index', $def['key']) }}" class="link">Clear filters</a>@endif
  <span class="toolbar__count">{{ $items->total() }} {{ $items->total() === 1 ? strtolower($def['singular']) : strtolower($def['label']) }}</span>
</form>

<div class="table-wrap">
<table class="table table--list">
  <thead>
    <tr>
      @foreach ($cols as $col => $label)
        <th class="{{ in_array($col, $imageCols, true) ? 'col-img' : '' }} {{ in_array($col, ['price', 'days', 'max_altitude', 'seats', 'sort_order'], true) ? 'num' : '' }}">{{ $label }}</th>
      @endforeach
      <th class="col-actions"><span class="sr-only">Actions</span></th>
    </tr>
  </thead>
  <tbody>
  @forelse ($items as $item)
    <tr class="{{ isset($item->is_published) && ! $item->is_published ? 'is-hidden-row' : '' }}">
      @foreach ($cols as $col => $label)
        @php $v = $col === "_toggles" ? null : $val($item, $col); $opts = $fieldsByName[$col]['options'] ?? null; @endphp
        <td class="{{ in_array($col, $imageCols, true) ? 'col-img' : '' }} {{ in_array($col, ['price', 'days', 'max_altitude', 'seats', 'sort_order'], true) ? 'num' : '' }} {{ $col === 'price' ? 'col-price' : '' }}" data-label="{{ $label }}">
          @if (in_array($col, $imageCols, true))
            <a href="{{ route('admin.resources.edit', [$def['key'], $item->id]) }}" tabindex="-1">
              @if ($v)<img class="thumb" src="{{ Media::url($v) }}" alt="" loading="lazy" />@else<span class="thumb thumb--empty"></span>@endif
            </a>
          @elseif ($col === "_toggles")
            <div class="toggles">
              @foreach ($toggles as $tf => $tl)
                <form method="post" action="{{ route('admin.resources.toggle', [$def['key'], $item->id]) }}">
                  @csrf @method('PATCH')
                  <input type="hidden" name="field" value="{{ $tf }}" />
                  <button class="toggle {{ $item->{$tf} ? 'is-on' : '' }}" title="Click to switch">
                    <i aria-hidden="true"></i>{{ $item->{$tf} ? $tl[0] : $tl[1] }}
                  </button>
                </form>
              @endforeach
            </div>
          @elseif (isset($toggles[$col]))
            <form method="post" action="{{ route('admin.resources.toggle', [$def['key'], $item->id]) }}">
              @csrf @method('PATCH')
              <input type="hidden" name="field" value="{{ $col }}" />
              <button class="toggle {{ $v ? 'is-on' : '' }}" title="Click to switch to “{{ $v ? $toggles[$col][1] : $toggles[$col][0] }}”">
                <i aria-hidden="true"></i>{{ $v ? $toggles[$col][0] : $toggles[$col][1] }}
              </button>
            </form>
          @elseif ($col === $titleCol)
            <a class="strong" href="{{ route('admin.resources.edit', [$def['key'], $item->id]) }}">{{ Str::limit((string) $v, 80) ?: '(untitled)' }}</a>
            @php $subs = collect($sub)->mapWithKeys(fn ($s) => [$s => $val($item, $s)])->filter(); @endphp
            @if ($subs->count())
              <span class="cell-sub">
                @foreach ($subs as $s => $text)
                  <span class="{{ $s === 'badge' ? 'mini-badge' : '' }}">{{ $text }}</span>
                @endforeach
              </span>
            @endif
          @elseif ($v instanceof \DateTimeInterface)
            <span class="date">{{ $v->format('j M Y') }}</span>
            @if ($v->isPast())<span class="pill pill--off">Past</span>@endif
          @elseif ($opts && isset($opts[$v]))
            <span class="tag tag--{{ Str::slug((string) $v) }}">{{ $opts[$v] }}</span>
          @elseif (is_bool($v))
            {!! $v ? '<span class="pill pill--ok">Yes</span>' : '<span class="muted">—</span>' !!}
          @else
            {{ Str::limit(is_array($v) ? implode(', ', $v) : (string) $fmt($col, $v), 60) }}
          @endif
        </td>
      @endforeach
      <td class="col-actions">
        <a class="icon-btn" href="{{ route('admin.resources.edit', [$def['key'], $item->id]) }}" title="Edit">@include('admin.partials.icon', ['name' => 'edit'])<span class="sr-only">Edit</span></a>
        @if (empty($def['edit_only']))
        <form method="post" action="{{ route('admin.resources.destroy', [$def['key'], $item->id]) }}" data-confirm="Delete this {{ strtolower($def['singular']) }}? This cannot be undone.">
          @csrf @method('DELETE')
          <button class="icon-btn icon-btn--danger" title="Delete">@include('admin.partials.icon', ['name' => 'trash'])<span class="sr-only">Delete</span></button>
        </form>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="{{ count($cols) + 1 }}" class="empty">
      @if ($active) Nothing matches these filters. <a href="{{ route('admin.resources.index', $def['key']) }}">Clear filters</a>.
      @else Nothing here yet.@if (empty($def['edit_only'])) <a href="{{ route('admin.resources.create', $def['key']) }}">Add the first {{ strtolower($def['singular']) }}</a>.@endif @endif
    </td></tr>
  @endforelse
  </tbody>
</table>
</div>
@if ($def['key'] === 'treks')
  <p class="help list-help">Treks marked <strong>Featured</strong> appear first in “Featured Himalayan Treks” on the homepage, in <strong>Order</strong>. “View All Treks” shows every published trek.</p>
@endif
{{ $items->links('admin.partials.pagination') }}
@endsection
