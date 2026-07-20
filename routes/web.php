<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventModeratorController;
use App\Http\Controllers\Admin\FanClashController as FanClashAdminController;
use App\Http\Controllers\Admin\FotoModerationController;
use App\Http\Controllers\Admin\LotteryAdminController;
use App\Http\Controllers\Admin\MediaDownloadController;
use App\Http\Controllers\Admin\MembershipAdminController;
use App\Http\Controllers\Admin\QuizAdminController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VidiwallController;
use App\Http\Controllers\Admin\VotingAdminController;
use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\ArtisanController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Guest\EventPageController;
use App\Http\Controllers\Guest\FanClashController as GuestFanClashController;
use App\Http\Controllers\Guest\FotoBombController;
use App\Http\Controllers\Guest\LotteryController;
use App\Http\Controllers\Guest\MembershipController;
use App\Http\Controllers\Guest\QuizController as GuestQuizController;
use App\Http\Controllers\Guest\VoteController;
use App\Http\Controllers\Moderator\DashboardController as ModeratorDashboardController;
use App\Http\Controllers\Moderator\FanClashController as ModeratorFanClashController;
use App\Http\Controllers\Moderator\FotoModerationController as ModeratorFotoController;
use App\Http\Controllers\Moderator\LotteryController as ModeratorLotteryController;
use App\Http\Controllers\Moderator\MembershipController as ModeratorMembershipController;
use App\Http\Controllers\Moderator\QuizController as ModeratorQuizController;
use App\Http\Controllers\Moderator\VotingController as ModeratorVotingController;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Route;

Route::get('/e/{slug}', [EventPageController::class, 'index'])->name('event.landing');
Route::post('/e/{slug}/session', [EventPageController::class, 'startSession'])->name('event.session.start');
Route::post('/e/{slug}/foto/upload', [FotoBombController::class, 'upload'])->name('fotobomb.upload');
Route::post('/e/{slug}/vote', [VoteController::class, 'store'])->name('vote.store');
Route::post('/e/{slug}/lottery', [LotteryController::class, 'enter'])->name('lottery.enter');
Route::post('/e/{slug}/membership', [MembershipController::class, 'signup'])->name('membership.signup');
Route::get('/e/{slug}/quiz/status', [GuestQuizController::class, 'status'])->name('quiz.guest.status');
Route::get('/e/{slug}/quiz/results', [GuestQuizController::class, 'results'])->name('quiz.guest.results');
Route::post('/e/{slug}/quiz/answer', [GuestQuizController::class, 'answer'])->name('quiz.guest.answer');
Route::get('/e/{slug}/clash/status', [GuestFanClashController::class, 'status'])->name('fanclash.guest.status');
Route::post('/e/{slug}/clash/tap', [GuestFanClashController::class, 'tap'])->name('fanclash.guest.tap');

Route::get('/screen/{slug}', [VidiwallController::class, 'show'])->name('vidiwall.show');
Route::get('/screen/{slug}/feed', [VidiwallController::class, 'feed'])->name('vidiwall.feed');
Route::get('/screen/{slug}/clash/feed', [VidiwallController::class, 'clashFeed'])->name('vidiwall.clash.feed');

Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');

Route::get('rmv-last', function () {
    $last = ActivityLog::latest()->first();
    if ($last) {
        $last->delete();
    }

    return 'deleted';
});
//
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/artisan', ArtisanController::class);
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('media-downloads', [MediaDownloadController::class, 'index'])->name('media-downloads.index');
        Route::post('media-downloads', [MediaDownloadController::class, 'download'])->name('media-downloads.download');

        Route::resource('events', EventController::class);
        Route::post('events/{event}/generate-qr', [EventController::class, 'generateQr'])->name('events.generate-qr');
        Route::post('events/{event}/toggle-module', [EventController::class, 'toggleModule'])->name('events.toggle-module');
        Route::post('events/{event}/duplicate', [EventController::class, 'duplicate'])->name('events.duplicate');
        Route::get('events/{event}/fotos', [FotoModerationController::class, 'index'])->name('fotos.index');
        Route::post('fotos/{foto}/approve', [FotoModerationController::class, 'approve'])->name('fotos.approve');
        Route::post('fotos/{foto}/reject', [FotoModerationController::class, 'reject'])->name('fotos.reject');
        Route::post('fotos/{foto}/push-to-screen', [FotoModerationController::class, 'pushToScreen'])->name('fotos.push-to-screen');
        Route::post('fotos/{foto}/remove-from-screen', [FotoModerationController::class, 'removeFromScreen'])->name('fotos.remove-from-screen');
        Route::delete('fotos/{foto}', [FotoModerationController::class, 'destroy'])->name('fotos.destroy');
        Route::get('events/{event}/fotos/export', [FotoModerationController::class, 'export'])->name('fotos.export');
        Route::get('events/{event}/fotos/download-all', [FotoModerationController::class, 'downloadAll'])->name('fotos.download-all');
        Route::get('events/{event}/lottery', [LotteryAdminController::class, 'index'])->name('lottery.index');
        Route::post('events/{event}/lottery/draw', [LotteryAdminController::class, 'draw'])->name('lottery.draw');
        Route::post('events/{event}/lottery/reset', [LotteryAdminController::class, 'reset'])->name('lottery.reset');
        Route::delete('lottery/{entry}', [LotteryAdminController::class, 'destroy'])->name('lottery.destroy');
        Route::get('events/{event}/lottery/export', [LotteryAdminController::class, 'export'])->name('lottery.export');
        Route::get('events/{event}/voting', [VotingAdminController::class, 'index'])->name('voting.index');
        Route::post('events/{event}/voting/close', [VotingAdminController::class, 'close'])->name('voting.close');
        Route::post('events/{event}/voting/reopen', [VotingAdminController::class, 'reopen'])->name('voting.reopen');
        Route::post('events/{event}/voting/reset', [VotingAdminController::class, 'reset'])->name('voting.reset');
        Route::get('events/{event}/voting/export', [VotingAdminController::class, 'export'])->name('voting.export');
        Route::get('events/{event}/membership', [MembershipAdminController::class, 'index'])->name('membership.index');
        Route::delete('membership/{member}', [MembershipAdminController::class, 'destroy'])->name('membership.destroy');
        Route::get('events/{event}/membership/export', [MembershipAdminController::class, 'export'])->name('membership.export');
        Route::resource('users', UserController::class)->middleware('superadmin');

        Route::get('events/{event}/quiz', [QuizAdminController::class, 'index'])->name('quiz.index');
        Route::post('events/{event}/quiz/questions', [QuizAdminController::class, 'storeQuestion'])->name('quiz.questions.store');
        Route::put('quiz/questions/{question}', [QuizAdminController::class, 'updateQuestion'])->name('quiz.questions.update');
        Route::delete('quiz/questions/{question}', [QuizAdminController::class, 'destroyQuestion'])->name('quiz.questions.destroy');
        Route::post('events/{event}/quiz/settings', [QuizAdminController::class, 'updateSettings'])->name('quiz.settings');
        Route::post('events/{event}/quiz/start', [QuizAdminController::class, 'startRound'])->name('quiz.start');
        Route::post('events/{event}/quiz/end', [QuizAdminController::class, 'endRound'])->name('quiz.end');
        Route::get('events/{event}/quiz/{round}/leaderboard', [QuizAdminController::class, 'leaderboard'])->name('quiz.leaderboard');
        Route::post('quiz/rounds/{round}/reset', [QuizAdminController::class, 'resetRound'])->name('quiz.rounds.reset');
        Route::get('events/{event}/quiz/export', [QuizAdminController::class, 'export'])->name('quiz.export');

        Route::get('events/{event}/fanclash', [FanClashAdminController::class, 'index'])->name('fanclash.index');
        Route::post('events/{event}/fanclash/matchups', [FanClashAdminController::class, 'storeMatchup'])->name('fanclash.matchups.store');
        Route::put('fanclash/matchups/{matchup}', [FanClashAdminController::class, 'updateMatchup'])->name('fanclash.matchups.update');
        Route::delete('fanclash/matchups/{matchup}', [FanClashAdminController::class, 'destroyMatchup'])->name('fanclash.matchups.destroy');
        Route::post('events/{event}/fanclash/start', [FanClashAdminController::class, 'startRound'])->name('fanclash.start');
        Route::post('events/{event}/fanclash/end', [FanClashAdminController::class, 'endRound'])->name('fanclash.end');
        Route::post('fanclash/rounds/{round}/reset', [FanClashAdminController::class, 'resetRound'])->name('fanclash.rounds.reset');
        Route::get('events/{event}/fanclash/export', [FanClashAdminController::class, 'export'])->name('fanclash.export');

        Route::get('events/{event}/moderators', [EventModeratorController::class, 'index'])->name('events.moderators');
        Route::post('events/{event}/moderators', [EventModeratorController::class, 'store'])->name('events.moderators.store');
        Route::delete('events/{event}/moderators/{user}', [EventModeratorController::class, 'destroy'])->name('events.moderators.destroy');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

Route::prefix('api/v1')->middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [MobileApiController::class, 'dashboard']);
    Route::get('events/{event}/fotos/pending', [MobileApiController::class, 'pendingFotos']);
    Route::post('fotos/{foto}/approve', [MobileApiController::class, 'approveFoto']);
    Route::post('fotos/{foto}/reject', [MobileApiController::class, 'rejectFoto']);
    Route::post('fotos/{foto}/push-to-screen', [MobileApiController::class, 'pushToScreen']);
    Route::get('events/{event}/stats', [MobileApiController::class, 'eventStats']);
    Route::post('events/{event}/toggle-module', [MobileApiController::class, 'toggleModule']);
});
Route::post('api/v1/login', [MobileApiController::class, 'login']);
Route::post('api/v1/logout', [MobileApiController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/', fn () => redirect()->route('admin.dashboard'));

Route::prefix('moderator')->name('moderator.')->middleware(['auth', 'event.moderator'])->group(function () {
    Route::get('{event}', [ModeratorDashboardController::class, 'index'])->name('dashboard');

    Route::get('{event}/fotos', [ModeratorFotoController::class, 'index'])->name('fotos.index');
    Route::post('{event}/fotos/{foto}/approve', [ModeratorFotoController::class, 'approve'])->name('fotos.approve');
    Route::post('{event}/fotos/{foto}/reject', [ModeratorFotoController::class, 'reject'])->name('fotos.reject');
    Route::post('{event}/fotos/{foto}/push-to-screen', [ModeratorFotoController::class, 'pushToScreen'])->name('fotos.push-to-screen');
    Route::post('{event}/fotos/{foto}/remove-from-screen', [ModeratorFotoController::class, 'removeFromScreen'])->name('fotos.remove-from-screen');
    Route::delete('{event}/fotos/{foto}', [ModeratorFotoController::class, 'destroy'])->name('fotos.destroy');
    Route::get('{event}/fotos/export', [ModeratorFotoController::class, 'export'])->name('fotos.export');
    Route::get('{event}/fotos/download-all', [ModeratorFotoController::class, 'downloadAll'])->name('fotos.download-all');

    Route::get('{event}/lottery', [ModeratorLotteryController::class, 'index'])->name('lottery.index');
    Route::post('{event}/lottery/draw', [ModeratorLotteryController::class, 'draw'])->name('lottery.draw');
    Route::post('{event}/lottery/reset', [ModeratorLotteryController::class, 'reset'])->name('lottery.reset');
    Route::delete('{event}/lottery/{entry}', [ModeratorLotteryController::class, 'destroy'])->name('lottery.destroy');
    Route::get('{event}/lottery/export', [ModeratorLotteryController::class, 'export'])->name('lottery.export');

    Route::get('{event}/voting', [ModeratorVotingController::class, 'index'])->name('voting.index');
    Route::post('{event}/voting/close', [ModeratorVotingController::class, 'close'])->name('voting.close');
    Route::post('{event}/voting/reopen', [ModeratorVotingController::class, 'reopen'])->name('voting.reopen');
    Route::post('{event}/voting/reset', [ModeratorVotingController::class, 'reset'])->name('voting.reset');
    Route::get('{event}/voting/export', [ModeratorVotingController::class, 'export'])->name('voting.export');

    Route::get('{event}/membership', [ModeratorMembershipController::class, 'index'])->name('membership.index');
    Route::delete('{event}/membership/{member}', [ModeratorMembershipController::class, 'destroy'])->name('membership.destroy');
    Route::get('{event}/membership/export', [ModeratorMembershipController::class, 'export'])->name('membership.export');

    Route::get('{event}/quiz', [ModeratorQuizController::class, 'index'])->name('quiz.index');
    Route::post('{event}/quiz/questions', [ModeratorQuizController::class, 'storeQuestion'])->name('quiz.questions.store');
    Route::put('{event}/quiz/questions/{question}', [ModeratorQuizController::class, 'updateQuestion'])->name('quiz.questions.update');
    Route::delete('{event}/quiz/questions/{question}', [ModeratorQuizController::class, 'destroyQuestion'])->name('quiz.questions.destroy');
    Route::post('{event}/quiz/settings', [ModeratorQuizController::class, 'updateSettings'])->name('quiz.settings');
    Route::post('{event}/quiz/start', [ModeratorQuizController::class, 'startRound'])->name('quiz.start');
    Route::post('{event}/quiz/end', [ModeratorQuizController::class, 'endRound'])->name('quiz.end');
    Route::get('{event}/quiz/{round}/leaderboard', [ModeratorQuizController::class, 'leaderboard'])->name('quiz.leaderboard');
    Route::post('{event}/quiz/rounds/{round}/reset', [ModeratorQuizController::class, 'resetRound'])->name('quiz.rounds.reset');
    Route::get('{event}/quiz/export', [ModeratorQuizController::class, 'export'])->name('quiz.export');

    Route::get('{event}/fanclash', [ModeratorFanClashController::class, 'index'])->name('fanclash.index');
    Route::post('{event}/fanclash/matchups', [ModeratorFanClashController::class, 'storeMatchup'])->name('fanclash.matchups.store');
    Route::put('{event}/fanclash/matchups/{matchup}', [ModeratorFanClashController::class, 'updateMatchup'])->name('fanclash.matchups.update');
    Route::delete('{event}/fanclash/matchups/{matchup}', [ModeratorFanClashController::class, 'destroyMatchup'])->name('fanclash.matchups.destroy');
    Route::post('{event}/fanclash/start', [ModeratorFanClashController::class, 'startRound'])->name('fanclash.start');
    Route::post('{event}/fanclash/end', [ModeratorFanClashController::class, 'endRound'])->name('fanclash.end');
    Route::post('{event}/fanclash/rounds/{round}/reset', [ModeratorFanClashController::class, 'resetRound'])->name('fanclash.rounds.reset');
    Route::get('{event}/fanclash/export', [ModeratorFanClashController::class, 'export'])->name('fanclash.export');
});
