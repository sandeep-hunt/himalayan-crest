<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TranslateController;
use App\Http\Controllers\Admin\UiStringController;
use App\Http\Middleware\EnsureFixedAdmin;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->group(function () {
    // ---- Sign in (one fixed admin account; no registration or password reset) ----
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'show'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
    });

    // ---- Admin panel ----
    Route::middleware(['auth', EnsureFixedAdmin::class])->name('admin.')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('sections', [SectionController::class, 'index'])->name('sections.index');
        Route::get('sections/{key}', [SectionController::class, 'edit'])->name('sections.edit');
        Route::put('sections/{key}', [SectionController::class, 'update'])->name('sections.update');

        Route::redirect('settings/languages', '/admin/languages'); // old address (languages have their own page now)
        Route::get('settings/{group?}', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings/{group}', [SettingController::class, 'update'])->name('settings.update');

        // Languages: add, edit, remove, show/hide on the website
        Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
        Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
        Route::put('languages/{code}', [LanguageController::class, 'update'])->name('languages.update');
        Route::patch('languages/{code}/toggle', [LanguageController::class, 'toggle'])->name('languages.toggle');
        Route::delete('languages/{code}', [LanguageController::class, 'destroy'])->name('languages.destroy');
        Route::post('languages/{code}/translate', [LanguageController::class, 'translateStep'])->name('languages.translate');
        Route::post('languages/{code}/translated', [LanguageController::class, 'translateDone'])->name('languages.translated');

        // Google Translate for the "Save" button of every edit page (English -> each language)
        Route::post('translate', TranslateController::class)->name('translate');

        // Your account: name + password of the fixed admin login
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');

        Route::get('interface-text', [UiStringController::class, 'edit'])->name('ui.edit');
        Route::put('interface-text', [UiStringController::class, 'update'])->name('ui.update');

        // Generic content modules: treks, departures, categories, team, reviews, guides, posts, faqs, videos
        Route::prefix('content/{resource}')->name('resources.')->group(function () {
            Route::get('/', [ResourceController::class, 'index'])->name('index');
            Route::get('create', [ResourceController::class, 'create'])->name('create');
            Route::post('/', [ResourceController::class, 'store'])->name('store');
            Route::get('{id}/edit', [ResourceController::class, 'edit'])->name('edit')->whereNumber('id');
            Route::put('{id}', [ResourceController::class, 'update'])->name('update')->whereNumber('id');
            Route::delete('{id}', [ResourceController::class, 'destroy'])->name('destroy')->whereNumber('id');
            Route::patch('{id}/toggle', [ResourceController::class, 'toggle'])->name('toggle')->whereNumber('id');
        });
    });
});
