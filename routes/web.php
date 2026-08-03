<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemandController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectScheduleColumnController;
use App\Http\Controllers\ProjectScheduleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\TimeEntryExportController;
use App\Http\Controllers\TimerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/timer', [TimerController::class, 'store'])->name('timer.start');
    Route::patch('/timer/{time_entry}/stop', [TimerController::class, 'stop'])->name('timer.stop');
    Route::patch('/timer/{time_entry}/cancel', [TimerController::class, 'cancel'])->name('timer.cancel');

    Route::get('/registros/exportar', TimeEntryExportController::class)->name('time-entries.export');
    Route::resource('/registros', TimeEntryController::class)->parameters(['registros' => 'time_entry'])->names('time-entries');
    Route::patch('/registros/{time_entry}/aprovar', [ApprovalController::class, 'approve'])->name('time-entries.approve');
    Route::patch('/registros/{time_entry}/rejeitar', [ApprovalController::class, 'reject'])->name('time-entries.reject');
    Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/demandas', [DemandController::class, 'index'])->name('demands.index');
    Route::get('/demandas/compartilhar', [DemandController::class, 'share'])->name('demands.share');
    Route::post('/demandas', [DemandController::class, 'store'])->name('demands.store');
    Route::put('/demandas/{demand}', [DemandController::class, 'update'])->name('demands.update');
    Route::patch('/demandas/{demand}/status', [DemandController::class, 'updateStatus'])->name('demands.status');
    Route::delete('/demandas/{demand}', [DemandController::class, 'destroy'])->name('demands.destroy');
    Route::get('/cronograma', [ProjectScheduleController::class, 'index'])->name('project-schedules.index');
    Route::post('/cronograma/{project}/importar', [ProjectScheduleController::class, 'import'])->name('project-schedules.import');
    Route::put('/cronograma/{project}', [ProjectScheduleController::class, 'update'])->name('project-schedules.update');
    Route::post('/cronograma/{project}/colunas', [ProjectScheduleColumnController::class, 'store'])->name('project-schedules.columns.store');
    Route::patch('/cronograma/{project}/colunas/{scheduleColumn}/mover', [ProjectScheduleColumnController::class, 'move'])->name('project-schedules.columns.move');
    Route::delete('/cronograma/{project}/colunas/{scheduleColumn}', [ProjectScheduleColumnController::class, 'destroy'])->name('project-schedules.columns.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:admin')->group(function () {
        Route::resource('/clientes', ClientController::class)->except('show')->parameters(['clientes' => 'client'])->names('clients');
        Route::resource('/projetos', ProjectController::class)->except('show')->parameters(['projetos' => 'project'])->names('projects');
        Route::resource('/atividades', ActivityController::class)->except('show')->parameters(['atividades' => 'activity'])->names('activities');
        Route::resource('/usuarios', UserController::class)->except('show')->parameters(['usuarios' => 'user'])->names('users');
        Route::get('/configuracoes', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/configuracoes', [SettingsController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/auth.php';
