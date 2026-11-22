<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SlaSettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\CannedReplyController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\EditorUploadController;
use App\Http\Controllers\GitWebhookController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectManagementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequesterController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TrelloController;
use App\Http\Controllers\TrelloWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/git/{provider}', GitWebhookController::class)->middleware('throttle:webhooks')->name('webhooks.git');

Route::get('/', [HomeController::class, 'index'])->name('home')->middleware('auth');

Route::middleware('guest')->group(static function (): void {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

// Available to guests too, so the sign-in page can use the same theme.
Route::post('/theme', [ThemeController::class, 'switch'])->name('theme.switch');

Route::get('/invitations/{user}/edit', [InvitationController::class, 'edit'])->middleware('signed')->name('invitations.edit');
Route::post('/invitations/{user}', [InvitationController::class, 'update'])->middleware('signed')->name('invitations.update');

// The challenge must be reachable before Auth::login() completes (the pending
// user id lives in the session only). Keeping it inside `auth` caused a
// login ↔ challenge redirect loop.
Route::get('/2fa/challenge', [TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');

Route::middleware('auth')->group(static function (): void {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');

    Route::get('/2fa/setup', [TwoFactorController::class, 'showSetup'])->name('2fa.setup');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::get('/2fa/recovery', [TwoFactorController::class, 'showRecovery'])->name('2fa.recovery');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');

    Route::get('/health', HealthController::class)->name('health');
    Route::get('/panduan', DocumentationController::class)->name('panduan');
    Route::get('/projects/{project:key}/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/projects/{project:key}/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/requests', [RequesterController::class, 'index'])->name('requester.requests');
    Route::get('/requests/{ticket}', [RequesterController::class, 'show'])->name('requester.requests.show');
    Route::get('/queue', [AgentController::class, 'queue'])->name('agent.queue');
    Route::get('/search', SearchController::class)->name('search');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project:key}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project:key}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project:key}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project:key}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project:key}/members', [ProjectController::class, 'addMember'])->name('projects.members.store');
    Route::delete('/projects/{project:key}/members/{member}', [ProjectController::class, 'removeMember'])->name('projects.members.destroy');
    Route::put('/projects/{project:key}/members/{member}', [ProjectController::class, 'setMemberRole'])->name('projects.members.role');

    Route::post('/projects/{project:key}/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/projects/{project:key}/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/projects/{project:key}/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('/projects/{project:key}/canned-replies', [CannedReplyController::class, 'store'])->name('canned.store');
    Route::put('/projects/{project:key}/canned-replies/{reply}', [CannedReplyController::class, 'update'])->name('canned.update');
    Route::delete('/projects/{project:key}/canned-replies/{reply}', [CannedReplyController::class, 'destroy'])->name('canned.destroy');

    Route::get('/projects/{project:key}/overview', [ProjectManagementController::class, 'overview'])->name('projects.overview');
    Route::get('/projects/{project:key}/epics', [ProjectManagementController::class, 'epics'])->name('projects.epics');
    Route::post('/projects/{project:key}/epics', [ProjectManagementController::class, 'storeEpic'])->name('projects.epics.store');
    Route::post('/projects/{project:key}/milestones', [ProjectManagementController::class, 'storeMilestone'])->name('projects.milestones.store');
    Route::get('/projects/{project:key}/timeline', [ProjectManagementController::class, 'timeline'])->name('projects.timeline');
    Route::get('/projects/{project:key}/workload', [ProjectManagementController::class, 'workload'])->name('projects.workload');

    Route::get('/projects/{project:key}/board', [DevController::class, 'board'])->name('dev.board');
    Route::get('/projects/{project:key}/backlog', [DevController::class, 'backlog'])->name('dev.backlog');
    Route::get('/projects/{project:key}/list', [DevController::class, 'list'])->name('dev.list');
    Route::get('/projects/{project:key}/sprints', [DevController::class, 'sprints'])->name('dev.sprints');
    Route::post('/projects/{project:key}/sprints', [DevController::class, 'storeSprint'])->name('dev.sprints.store');
    Route::post('/projects/{project:key}/sprints/{sprint}/start', [DevController::class, 'startSprint'])->name('dev.sprints.start');
    Route::post('/projects/{project:key}/sprints/{sprint}/complete', [DevController::class, 'completeSprint'])->name('dev.sprints.complete');
    Route::post('/projects/{project:key}/labels', [DevController::class, 'storeLabel'])->name('dev.labels.store');
    Route::delete('/projects/{project:key}/labels/{label}', [DevController::class, 'destroyLabel'])->name('dev.labels.destroy');
    Route::post('/filters', [DevController::class, 'saveFilter'])->name('dev.filters.store');
    Route::delete('/filters/{filter}', [DevController::class, 'destroyFilter'])->name('dev.filters.destroy');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/quick', [TicketController::class, 'quickCreate'])->name('tickets.quick');
    Route::get('/t/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/t/{ticket}/confirmation', [TicketController::class, 'confirmation'])->name('tickets.confirmation');
    Route::put('/t/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/t/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::post('/t/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
    Route::post('/t/{ticket}/dev', [ProjectManagementController::class, 'createDevTicket'])->name('tickets.dev');

    Route::post('/t/{ticket}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::put('/t/{ticket}/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/t/{ticket}/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::post('/t/{ticket}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::post('/editor/uploads', [EditorUploadController::class, 'store'])->name('editor.uploads.store');
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::prefix('admin')->name('admin.')->group(static function (): void {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/import', [UserController::class, 'showImport'])->name('users.import');
        Route::post('/users/import', [UserController::class, 'import'])->name('users.import.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::post('/users/{user}/reset-2fa', [UserController::class, 'resetTwoFactor'])->name('users.2fa.reset');
        Route::get('/audit-logs', AuditLogController::class)->name('audit-logs');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/settings/sla', [SlaSettingsController::class, 'index'])->name('settings.sla');
        Route::put('/settings/sla', [SlaSettingsController::class, 'update'])->name('settings.sla.update');
        Route::post('/settings/sla/holidays', [SlaSettingsController::class, 'addHoliday'])->name('settings.sla.holidays.store');
        Route::delete('/settings/sla/holidays/{holiday}', [SlaSettingsController::class, 'deleteHoliday'])->name('settings.sla.holidays.destroy');
    });
});

if (config('trello.enabled')) {
    Route::post('/webhooks/trello/{connection}', [TrelloWebhookController::class, 'handle'])->name('webhooks.trello');
    Route::match(['GET', 'HEAD'], '/webhooks/trello/{connection}', [TrelloWebhookController::class, 'verify'])->name('webhooks.trello.verify');

    Route::middleware('auth')->group(static function (): void {
        Route::get('/projects/{project:key}/integrations/trello', [TrelloController::class, 'settings'])->name('trello.settings');
        Route::get('/projects/{project:key}/integrations/trello/connect', [TrelloController::class, 'connectForm'])->name('trello.connect');
        Route::post('/projects/{project:key}/integrations/trello', [TrelloController::class, 'connect'])->name('trello.connect.store');
        Route::put('/projects/{project:key}/integrations/trello/{connection}', [TrelloController::class, 'update'])->name('trello.update');
        Route::post('/integrations/trello/{connection}/pause', [TrelloController::class, 'pause'])->name('trello.pause');
        Route::post('/integrations/trello/{connection}/resume', [TrelloController::class, 'resume'])->name('trello.resume');
        Route::delete('/integrations/trello/{connection}', [TrelloController::class, 'disconnect'])->name('trello.disconnect');
        Route::post('/integrations/trello/{connection}/import', [TrelloController::class, 'import'])->name('trello.import');
        Route::get('/integrations/trello/imports/{run}', [TrelloController::class, 'importReport'])->name('trello.import.report');
        Route::post('/integrations/trello/{connection}/link', [TrelloController::class, 'link'])->name('trello.link');
        Route::post('/t/{ticket}/trello/resync', [TrelloController::class, 'resync'])->name('trello.resync');
        Route::post('/t/{ticket}/trello/unlink', [TrelloController::class, 'unlink'])->name('trello.unlink');
        Route::post('/projects/{project:key}/trello-prompt', [TrelloController::class, 'prompt'])->name('trello.prompt');
        Route::post('/projects/{project:key}/trello-banner/dismiss', [TrelloController::class, 'dismissBanner'])->name('trello.banner.dismiss');
    });
}
