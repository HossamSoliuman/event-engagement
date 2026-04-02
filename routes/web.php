<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\FotoModerationController;
use App\Http\Controllers\Admin\LotteryAdminController;
use App\Http\Controllers\Admin\VotingAdminController;
use App\Http\Controllers\Admin\MembershipAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\VidiwallController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Guest\EventPageController;
use App\Http\Controllers\Guest\FotoBombController;
use App\Http\Controllers\Guest\VoteController;
use App\Http\Controllers\Guest\LotteryController;
use App\Http\Controllers\Guest\MembershipController;
use Illuminate\Support\Facades\Route;

// ── Guest ─────────────────────────────────────────────────────────────────────
Route::get('/e/{slug}',              [EventPageController::class, 'index'])->name('event.landing');
Route::post('/e/{slug}/session',     [EventPageController::class, 'startSession'])->name('event.session.start');
Route::post('/e/{slug}/foto/upload', [FotoBombController::class, 'upload'])->name('fotobomb.upload');
Route::post('/e/{slug}/vote',        [VoteController::class, 'store'])->name('vote.store');
Route::post('/e/{slug}/lottery',     [LotteryController::class, 'enter'])->name('lottery.enter');
Route::post('/e/{slug}/membership',  [MembershipController::class, 'signup'])->name('membership.signup');

// Vidiwall
Route::get('/screen/{slug}',      [VidiwallController::class, 'show'])->name('vidiwall.show');
Route::get('/screen/{slug}/feed', [VidiwallController::class, 'feed'])->name('vidiwall.feed');

Route::get('login',   [AdminAuthController::class, 'showLogin'])->name('login');

// ── Admin Auth ────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',   [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login',  [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Events
        Route::resource('events', EventController::class);
        Route::post('events/{event}/generate-qr',   [EventController::class, 'generateQr'])->name('events.generate-qr');
        Route::post('events/{event}/toggle-module', [EventController::class, 'toggleModule'])->name('events.toggle-module');
        Route::post('events/{event}/duplicate',     [EventController::class, 'duplicate'])->name('events.duplicate');

        // Foto moderation
        Route::get('events/{event}/fotos',                 [FotoModerationController::class, 'index'])->name('fotos.index');
        Route::post('fotos/{foto}/approve',                [FotoModerationController::class, 'approve'])->name('fotos.approve');
        Route::post('fotos/{foto}/reject',                 [FotoModerationController::class, 'reject'])->name('fotos.reject');
        Route::post('fotos/{foto}/push-to-screen',         [FotoModerationController::class, 'pushToScreen'])->name('fotos.push-to-screen');
        Route::post('fotos/{foto}/remove-from-screen',     [FotoModerationController::class, 'removeFromScreen'])->name('fotos.remove-from-screen');
        Route::delete('fotos/{foto}',                      [FotoModerationController::class, 'destroy'])->name('fotos.destroy');
        Route::get('events/{event}/fotos/export',          [FotoModerationController::class, 'export'])->name('fotos.export');

        // Lottery
        Route::get('events/{event}/lottery',               [LotteryAdminController::class, 'index'])->name('lottery.index');
        Route::post('events/{event}/lottery/draw',         [LotteryAdminController::class, 'draw'])->name('lottery.draw');
        Route::post('events/{event}/lottery/reset',        [LotteryAdminController::class, 'reset'])->name('lottery.reset');
        Route::delete('lottery/{entry}',                   [LotteryAdminController::class, 'destroy'])->name('lottery.destroy');
        Route::get('events/{event}/lottery/export',        [LotteryAdminController::class, 'export'])->name('lottery.export');

        // Voting
        Route::get('events/{event}/voting',                [VotingAdminController::class, 'index'])->name('voting.index');
        Route::post('events/{event}/voting/close',         [VotingAdminController::class, 'close'])->name('voting.close');
        Route::post('events/{event}/voting/reopen',        [VotingAdminController::class, 'reopen'])->name('voting.reopen');
        Route::post('events/{event}/voting/reset',         [VotingAdminController::class, 'reset'])->name('voting.reset');
        Route::get('events/{event}/voting/export',         [VotingAdminController::class, 'export'])->name('voting.export');

        // Membership
        Route::get('events/{event}/membership',            [MembershipAdminController::class, 'index'])->name('membership.index');
        Route::delete('membership/{member}',               [MembershipAdminController::class, 'destroy'])->name('membership.destroy');
        Route::get('events/{event}/membership/export',     [MembershipAdminController::class, 'export'])->name('membership.export');

        // Users
        Route::resource('users', UserController::class)->middleware('superadmin');

        // Settings
        Route::get('settings',  [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

// ── Mobile Admin API ──────────────────────────────────────────────────────────
Route::prefix('api/v1')->middleware('auth:sanctum')->group(function () {
    Route::get('dashboard',                     [\App\Http\Controllers\Api\MobileApiController::class, 'dashboard']);
    Route::get('events/{event}/fotos/pending',  [\App\Http\Controllers\Api\MobileApiController::class, 'pendingFotos']);
    Route::post('fotos/{foto}/approve',         [\App\Http\Controllers\Api\MobileApiController::class, 'approveFoto']);
    Route::post('fotos/{foto}/reject',          [\App\Http\Controllers\Api\MobileApiController::class, 'rejectFoto']);
    Route::post('fotos/{foto}/push-to-screen',  [\App\Http\Controllers\Api\MobileApiController::class, 'pushToScreen']);
    Route::get('events/{event}/stats',          [\App\Http\Controllers\Api\MobileApiController::class, 'eventStats']);
    Route::post('events/{event}/toggle-module', [\App\Http\Controllers\Api\MobileApiController::class, 'toggleModule']);
});
Route::post('api/v1/login',  [\App\Http\Controllers\Api\MobileApiController::class, 'login']);
Route::post('api/v1/logout', [\App\Http\Controllers\Api\MobileApiController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/', fn() => redirect()->route('admin.dashboard'));
