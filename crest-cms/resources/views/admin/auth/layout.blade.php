<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, nofollow" />
  <title>@yield('title') · Crest Himalaya CMS</title>
  @include('admin.partials.favicon')
  <link rel="stylesheet" href="{{ asset('cms-assets/admin.css') }}?v=13" />
</head>
<body class="login-page">
  <main class="login">
    <div class="login__brand">
      <svg viewBox="0 0 100 110" aria-hidden="true"><path d="M50 5 91 13v49q0 8-6 13L50 104 15 75q-6-5-6-13V13z" fill="none" stroke="currentColor" stroke-width="7" stroke-linejoin="round"/><path d="M17 74 34 50l4 8 12-26 10 18 6-8 18 32-8-6-4 6-6-12-4 8-6-12-4 14-4-14-4 12-6-8-8 10-4-6z" fill="#DCD3C4"/></svg>
      <p><strong>CREST HIMALAYA</strong><span>Content Manager</span></p>
    </div>
    <div class="login__card">
      <h1>@yield('title')</h1>
      @hasSection('lede')<p class="login__lede">@yield('lede')</p>@endif
      @if (session('status'))<p class="auth-status" role="status">{{ session('status') }}</p>@endif
      @if ($errors->any())
        <div class="auth-errors" role="alert">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
      @endif
      @yield('content')
    </div>
    @hasSection('below')<p class="login__below">@yield('below')</p>@endif
  </main>
  <script>
    // show / hide password buttons
    document.addEventListener("click", (e) => {
      const b = e.target.closest("[data-reveal]");
      if (!b) return;
      const input = document.getElementById(b.dataset.reveal);
      const show = input.type === "password";
      input.type = show ? "text" : "password";
      b.textContent = show ? "Hide" : "Show";
      b.setAttribute("aria-pressed", String(show));
    });
  </script>
</body>
</html>
