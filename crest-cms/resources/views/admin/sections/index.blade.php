@extends('admin.layout')
@section('title', 'Homepage Sections')
@section('crumb', 'Homepage')

@section('content')
<p class="muted intro">Every part of the website from the navbar to the footer, in the order it appears on the page, and every pop-up window that opens from it. Open one to edit its text, photos and the names of its buttons. Lists such as treks, team, stories and FAQs have their own pages in the side menu; the menu links are in <a href="{{ route('admin.resources.index', 'menu') }}">Menu (header &amp; footer)</a>.</p>
@php
  $isPopup = fn ($key) => $key === 'plan_form' || str_ends_with($key, '_popup');
  $parts = [
      ['On the page (top to bottom)', collect($sections)->reject(fn ($s, $k) => $isPopup($k))],
      ['Pop-ups (windows that open on the website)', collect($sections)->filter(fn ($s, $k) => $isPopup($k))],
  ];
@endphp
@foreach ($parts as [$heading, $list])
  <h2 class="section-list__title" @if (! $loop->first) id="pop-ups" @endif>{{ $heading }}</h2>
  <div class="section-list">
    @foreach ($list as $key => $s)
      @php $n = \App\Http\Controllers\Admin\SectionController::buttonKeys($s); $set = count($s['settings'] ?? []); @endphp
      <a class="section-card" href="{{ route('admin.sections.edit', $key) }}">
        <span class="section-card__n">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="section-card__body">
          <strong>{{ $s['label'] }}</strong>
          <small>{{ collect($s['fields'] ?? [])->pluck('label')->take(4)->push($set ? $set.' '.($set === 1 ? 'setting' : 'settings') : null)->push($n ? count($n).' '.(count($n) === 1 ? 'button or label' : 'buttons & labels') : null)->filter()->implode(' · ') }}</small>
        </span>
        @if (! empty($s['fields']))
          <span class="section-card__meta">{{ isset($updated[$key]) ? 'Edited '.\Illuminate\Support\Carbon::parse($updated[$key])->diffForHumans() : 'Default' }}</span>
        @endif
      </a>
    @endforeach
  </div>
@endforeach
@endsection
