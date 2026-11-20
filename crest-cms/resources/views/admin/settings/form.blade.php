@extends('admin.layout')
@section('title', 'Settings')
@section('crumb', 'Website')

@section('content')
<nav class="tabs" aria-label="Settings sections">
  @foreach ($groups as $gk => $g)
    <a href="{{ route('admin.settings.edit', $gk) }}" class="{{ $gk === $def['key'] ? 'is-active' : '' }}">{{ $g['label'] }}</a>
  @endforeach
</nav>

@php $hasTr = collect($def['fields'])->contains(fn ($f) => ! empty($f['tr'])); @endphp

<form class="edit-form" method="post" action="{{ route('admin.settings.update', $def['key']) }}">
  @csrf @method('PUT')
  @if ($hasTr) @include('admin.partials.lang-tabs') @endif
  <div class="form-card">
    @foreach ($def['fields'] as $f)
      @include('admin.partials.field', ['f' => $f, 'value' => $values[$f['name']] ?? null, 'prefix' => 'settings'])
    @endforeach
  </div>
  <div class="form-bar">
    <a class="btn" href="{{ route('admin.settings.edit', $def['key'] ?? request()->route('group')) }}">Cancel</a>
    <button class="btn btn--primary" type="submit">@include('admin.partials.icon', ['name' => 'check']) Save {{ strtolower($def['label']) }}</button>
  </div>
</form>
@endsection
