@extends('admin.layout')
@section('title', 'Your account')

@section('content')
<div class="account-grid">
  <form class="form-card" method="post" action="{{ route('admin.profile.update') }}">
    @csrf @method('PUT')
    <h2 class="form-card__title">Your details</h2>
    @php $pe = $errors->profile; @endphp
    <div class="field {{ $pe->has('name') ? 'has-error' : '' }}">
      <label class="field__label" for="name">Name</label>
      <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="100" />
      <p class="help">Shown at the bottom of the menu.</p>
      @if ($pe->has('name'))<p class="field__err">{{ $pe->first('name') }}</p>@endif
    </div>
    <div class="field">
      <label class="field__label" for="email">Login email</label>
      <input id="email" type="email" value="{{ $user->email }}" disabled />
      <p class="help">The login email is fixed. A developer can change it in the server's .env file (ADMIN_EMAIL), then run <code>php artisan admin:sync</code>.</p>
    </div>
    <div class="form-card__actions"><button class="btn btn--primary" type="submit">Save name</button></div>
  </form>

  <form class="form-card" method="post" action="{{ route('admin.profile.password') }}">
    @csrf @method('PUT')
    <h2 class="form-card__title">Change password</h2>
    @php $pw = $errors->password; @endphp
    <div class="field {{ $pw->has('current_password') ? 'has-error' : '' }}">
      <label class="field__label" for="current_password">Current password</label>
      <input id="current_password" type="password" name="current_password" required autocomplete="current-password" />
      @if ($pw->has('current_password'))<p class="field__err">{{ $pw->first('current_password') }}</p>@endif
    </div>
    <div class="field {{ $pw->has('password') ? 'has-error' : '' }}">
      <label class="field__label" for="password">New password</label>
      <input id="password" type="password" name="password" required autocomplete="new-password" minlength="10" />
      <p class="help">At least 10 characters, with letters and numbers.</p>
      @if ($pw->has('password'))<p class="field__err">{{ $pw->first('password') }}</p>@endif
    </div>
    <div class="field">
      <label class="field__label" for="password_confirmation">Repeat new password</label>
      <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
    </div>
    <p class="help">Changing the password signs out any other device that is logged in.</p>
    <div class="form-card__actions"><button class="btn btn--primary" type="submit">Change password</button></div>
  </form>
</div>
@endsection
