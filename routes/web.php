<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FocusController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('entries', EntryController::class);

    // Rutas de tareas — manuales primero (antes del resource)
    Route::get('tasks/kanban',            [TaskController::class, 'kanban'])->name('tasks.kanban');
    Route::patch('tasks/{task}/status',   [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::patch('tasks/{task}/toggle',   [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::post('tasks/reorder',          [TaskController::class, 'reorder'])->name('tasks.reorder');
    Route::patch('tasks/bulk-update',     [TaskController::class, 'bulkUpdate'])->name('tasks.bulk-update');
    Route::delete('tasks/bulk-delete',    [TaskController::class, 'bulkDelete'])->name('tasks.bulk-delete');
    Route::post('tasks/{task}/subtasks',  [TaskController::class, 'storeSubtask'])->name('tasks.subtasks.store');
    Route::resource('tasks', TaskController::class);

    Route::get('/search', SearchController::class)->name('search');
    Route::get('/focus',                         [FocusController::class, 'index'])->name('focus.index');
    Route::get('/focus/history',                 [FocusController::class, 'history'])->name('focus.history');
    Route::post('/focus/start',                  [FocusController::class, 'start'])->name('focus.start');
    Route::patch('/focus/{session}/complete',    [FocusController::class, 'complete'])->name('focus.complete');
    Route::patch('/focus/{session}/cancel',      [FocusController::class, 'cancel'])->name('focus.cancel');

    Route::get('/reports/daily',                 [ReportController::class, 'daily'])->name('reports.daily');
    Route::get('/reports/weekly',                [ReportController::class, 'weekly'])->name('reports.weekly');

    Route::get('/calendar',                      [CalendarController::class, 'index'])->name('calendar.index');
    Route::patch('/calendar/task-due-date/{task}', [CalendarController::class, 'updateDueDate'])->name('calendar.update-due-date');

    Route::get('/planning/week',                 [PlanningController::class, 'week'])->name('planning.week');
    Route::patch('/planning/task-due-date',      [PlanningController::class, 'updateDueDate'])->name('planning.update-due-date');

    Route::resource('templates', TemplateController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('projects', ProjectController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::resource('tags',     TagController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/files',          [FileController::class, 'index'])->name('files.index');
    Route::post('/files',         [FileController::class, 'store'])->name('files.store');
    Route::delete('/files/{attachment}', [FileController::class, 'destroy'])->name('files.destroy');

    Route::get('/api/ai/summary',         [AiController::class, 'dailySummary'])->name('ai.summary');
    Route::post('/api/ai/extract-tasks',  [AiController::class, 'extractTasks'])->name('ai.extract-tasks');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Exports (CSV only - PDF disabled due to version compatibility)
    Route::get('/export/tasks/csv',         [ExportController::class, 'tasksCSV'])->name('export.tasks.csv');
    Route::get('/export/entries/csv',       [ExportController::class, 'entriesCSV'])->name('export.entries.csv');
    Route::get('/export/report/daily/csv',  [ExportController::class, 'reportDailyCSV'])->name('export.report.daily.csv');
    Route::get('/export/report/weekly/csv', [ExportController::class, 'reportWeeklyCSV'])->name('export.report.weekly.csv');

    // Task Comments
    Route::get('/api/tasks/{task}/comments',        [TaskCommentController::class, 'getTaskComments'])->name('task-comments.index');
    Route::post('/api/tasks/{task}/comments',       [TaskCommentController::class, 'store'])->name('task-comments.store');
    Route::patch('/api/comments/{comment}',         [TaskCommentController::class, 'update'])->name('task-comments.update');
    Route::delete('/api/comments/{comment}',        [TaskCommentController::class, 'destroy'])->name('task-comments.destroy');
});

require __DIR__.'/auth.php';
