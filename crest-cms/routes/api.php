<?php

use App\Http\Controllers\Api\ContentController;
use Illuminate\Support\Facades\Route;

/*
| Public website API (read-only). All GET endpoints accept ?lang=en|es|fr|de|zh|ja|ru|pl|ar|pt|it
*/
Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
    Route::get('site', [ContentController::class, 'site']);
    Route::get('languages', [ContentController::class, 'languages']);
    Route::get('settings', [ContentController::class, 'settings']);
    Route::get('sections', [ContentController::class, 'sections']);
    Route::get('ui', [ContentController::class, 'ui']);
    Route::get('categories', [ContentController::class, 'categories']);
    Route::get('treks', [ContentController::class, 'treks']);
    Route::get('treks/{slug}', [ContentController::class, 'trek']);
    Route::get('departures', [ContentController::class, 'departures']);
    Route::get('team', [ContentController::class, 'team']);
    Route::get('reviews', [ContentController::class, 'reviews']);
    Route::get('faqs', [ContentController::class, 'faqs']);
    Route::get('videos', [ContentController::class, 'videos']);
    Route::get('guides', [ContentController::class, 'guides']);
    Route::get('guides/{slug}', [ContentController::class, 'guide']);
    Route::get('posts', [ContentController::class, 'posts']);
    Route::get('posts/{slug}', [ContentController::class, 'post']);
});
