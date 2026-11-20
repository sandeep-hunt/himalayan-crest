@extends('admin.auth.layout')
@section('title', 'Sign in')

@section('content')
<form method="post" action="{{ route('login.attempt') }}" class="auth-form">
  @csrf
  <label class="field__label" for="email">Email</label>
  <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />

  <label class="field__label" for="password">Password</label>
  <div class="pw">
    <input id="password" type="password" name="password" required autocomplete="current-password" />
    <button type="button" class="pw__toggle" data-reveal="password" aria-pressed="false">Show</button>
  </div>

  <label class="check check--inline"><input type="checkbox" name="remember" value="1" /> <span>Keep me signed in</span></label>
  <button class="btn btn--primary btn--block" type="submit">Sign in</button>
</form>
@endsection
