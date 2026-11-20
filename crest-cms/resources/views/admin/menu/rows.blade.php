@if ($items->isEmpty())
  <p class="menu-rows__empty">{{ $empty }}</p>
@else
<ul class="menu-rows">
  @foreach ($items as $l)
    <li class="menu-row {{ $l->is_button ? 'menu-row--button' : '' }}">
      <span class="menu-row__order" title="Order">{{ $l->sort_order }}</span>
      <div class="menu-row__main">
        <a class="menu-row__title" href="{{ route('admin.resources.edit', ['menu', $l->id]) }}">@if ($l->is_button)<span class="fake-btn">{{ $l->tr('title', 'en') }}</span>@else{{ $l->tr('title', 'en') }}@endif</a>
        @if ($l->tr('subtitle', 'en'))<small class="menu-row__sub">{{ $l->tr('subtitle', 'en') }}</small>@endif
      </div>
      <span class="menu-row__does">{{ $does($l) }}</span>
      <a class="icon-btn" href="{{ route('admin.resources.edit', ['menu', $l->id]) }}" title="Edit">@include('admin.partials.icon', ['name' => 'edit'])<span class="sr-only">Edit</span></a>
    </li>
  @endforeach
</ul>
@endif
