@extends('admin.layout')
@section('title', $item->exists ? (($item->isTranslatable('name') ? $item->tr('name', 'en') : ($item->isTranslatable('title') ? $item->tr('title', 'en') : ($item->name ?? null))) ?: 'Edit '.strtolower($def['singular'])) : 'Add '.strtolower($def['singular']))
@section('crumb')
  <a href="{{ route('admin.resources.index', $def['key']) }}">{{ $def['label'] }}</a>
@endsection

@section('actions')
  @if ($item->exists && empty($def['edit_only']))
    <form method="post" action="{{ route('admin.resources.destroy', [$def['key'], $item->id]) }}" data-confirm="Delete this {{ strtolower($def['singular']) }}? This cannot be undone.">
      @csrf @method('DELETE')
      <button class="btn btn--danger-ghost">@include('admin.partials.icon', ['name' => 'trash']) Delete</button>
    </form>
  @endif
@endsection

@section('content')
@php
  $hasTr = collect($def['fields'])->contains(fn ($f) => ! empty($f['tr']));
  // Split fields into sections (a field with 'section' starts a new one); keep 'half' fields in pairs
  $sections = [];
  foreach ($def['fields'] as $f) {
      if (! empty($f['section']) || ! $sections) {
          $sections[] = ['title' => $f['section'] ?? null, 'fields' => []];
      }
      $sections[count($sections) - 1]['fields'][] = $f;
  }
  $named = count(array_filter($sections, fn ($s) => $s['title'])) > 1;
@endphp
<form class="edit-form {{ $named ? 'edit-form--sections' : '' }}" method="post" enctype="multipart/form-data"
      action="{{ $item->exists ? route('admin.resources.update', [$def['key'], $item->id]) : route('admin.resources.store', $def['key']) }}">
  @csrf
  @if ($item->exists) @method('PUT') @endif

  @if ($hasTr) @include('admin.partials.lang-tabs') @endif

  @if ($named)
    <nav class="form-toc" aria-label="Form sections">
      @foreach ($sections as $i => $s)<a href="#sec-{{ $i }}">{{ $s['title'] }}</a>@endforeach
    </nav>
  @endif

  @foreach ($sections as $i => $s)
    <section class="form-card" id="sec-{{ $i }}">
      @if ($s['title'])<h2 class="form-card__title">{{ $s['title'] }}</h2>@endif
      @php $buffer = []; @endphp
      @foreach ($s['fields'] as $f)
        @if (! empty($f['half']))
          @php $buffer[] = $f; @endphp
          @if (count($buffer) === 2 || $loop->last || empty($s['fields'][$loop->index + 1]['half']))
            <div class="field-row">
              @foreach ($buffer as $bf)
                @include('admin.partials.field', ['f' => $bf, 'value' => $item->getAttribute($bf['name']), 'prefix' => ''])
              @endforeach
            </div>
            @php $buffer = []; @endphp
          @endif
        @else
          @include('admin.partials.field', ['f' => $f, 'value' => $item->getAttribute($f['name']), 'prefix' => ''])
        @endif
      @endforeach
    </section>
  @endforeach

  <div class="form-bar">
    <a class="btn" href="{{ route('admin.resources.index', $def['key']) }}">Cancel</a>
    <button class="btn btn--primary" type="submit">@include('admin.partials.icon', ['name' => 'check']) {{ $item->exists ? 'Save changes' : 'Create '.strtolower($def['singular']) }}</button>
  </div>
</form>
@endsection
