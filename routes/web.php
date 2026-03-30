<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\FotoModerationController;
use App\Http\Controllers\Admin\VidiwallController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Guest\EventPageController;
use App\Http\Controllers\Guest\FotoBombController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| GUEST / PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// QR Code landing — guests scan and land here
Route::get('/e/{slug}', [EventPageController::class, 'index'])->name('event.landing');
Route::post('/e/{slug}/session', [EventPageController::class, 'startSession'])->name('event.session.start');

// FotoBomb
Route::post('/e/{slug}/foto/upload', [FotoBombController::class, 'upload'])->name('fotobomb.upload');

// Voting
Route::post('/e/{slug}/vote', [App\Http\Controllers\Guest\VoteController::class, 'store'])->name('vote.store');

// Lottery
Route::post('/e/{slug}/lottery', [App\Http\Controllers\Guest\LotteryController::class, 'enter'])->name('lottery.enter');

// Membership
Route::post('/e/{slug}/membership', [App\Http\Controllers\Guest\MembershipController::class, 'signup'])->name('membership.signup');

// Vidiwall / screen overlay (public, no auth — TV displays this)
Route::get('/screen/{slug}', [VidiwallController::class, 'show'])->name('vidiwall.show');
Route::get('/screen/{slug}/feed', [VidiwallController::class, 'feed'])->name('vidiwall.feed');

/*
|--------------------------------------------------------------------------
| ADMIN AUTH
|--------------------------------------------------------------------------
*/
Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Protected admin routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Event management
        Route::resource('events', EventController::class);
        Route::post('events/{event}/generate-qr', [EventController::class, 'generateQr'])->name('events.generate-qr');
        Route::post('events/{event}/toggle-module', [EventController::class, 'toggleModule'])->name('events.toggle-module');

        // Foto moderation
        Route::get('events/{event}/fotos', [FotoModerationController::class, 'index'])->name('fotos.index');
        Route::post('fotos/{foto}/approve', [FotoModerationController::class, 'approve'])->name('fotos.approve');
        Route::post('fotos/{foto}/reject', [FotoModerationController::class, 'reject'])->name('fotos.reject');
        Route::post('fotos/{foto}/push-to-screen', [FotoModerationController::class, 'pushToScreen'])->name('fotos.push-to-screen');
        Route::post('fotos/{foto}/remove-from-screen', [FotoModerationController::class, 'removeFromScreen'])->name('fotos.remove-from-screen');
        Route::delete('fotos/{foto}', [FotoModerationController::class, 'destroy'])->name('fotos.destroy');
    });
});

// Root → redirect to admin
Route::get('/', fn() => redirect()->route('admin.dashboard'));
