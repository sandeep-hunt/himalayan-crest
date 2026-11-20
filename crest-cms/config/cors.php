<?php

// The public API is called from the website (a different domain/port than the CMS).
// Production: only the website address(es) in FRONTEND_URL (comma separated) may call the API.
// Local development (APP_ENV=local): any http://localhost:<port> / 127.0.0.1:<port> is also allowed,
// because Vite moves to 5174, 5175... when 5173 is busy.
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('FRONTEND_URL', 'http://localhost:5173'))))),
    'allowed_origins_patterns' => env('APP_ENV', 'production') === 'local'
        ? ['#^https?://(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$#']
        : [],
    'allowed_headers' => ['Content-Type', 'Accept', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,
];
