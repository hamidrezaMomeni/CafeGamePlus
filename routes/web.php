<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConsoleController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\BoardGameController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CafeItemController;
use App\Http\Controllers\ConsoleSessionController;
use App\Http\Controllers\TableSessionController;
use App\Http\Controllers\BoardGameSessionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\PricingPlanController;
use App\Http\Controllers\AccountingStatsController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('consoles', ConsoleController::class)
        ->except(['show'])
        ->middleware('permission:consoles.manage');
    Route::resource('tables', TableController::class)
        ->except(['show'])
        ->middleware('permission:tables.manage');
    Route::resource('board-games', BoardGameController::class)
        ->except(['show'])
        ->middleware('permission:board_games.manage');
    Route::resource('customers', CustomerController::class)->middleware('permission:customers.manage');
    Route::resource('cafe-items', CafeItemController::class)
        ->except(['show'])
        ->middleware('permission:cafe_items.manage');

    Route::resource('console-sessions', ConsoleSessionController::class)
        ->only(['index', 'create', 'store'])
        ->middleware('permission:console_sessions.manage');
    Route::post('console-sessions/{consoleSession}/end', [ConsoleSessionController::class, 'end'])
        ->name('console-sessions.end')
        ->middleware('permission:console_sessions.manage');

    Route::resource('table-sessions', TableSessionController::class)
        ->only(['index', 'create', 'store'])
        ->middleware('permission:table_sessions.manage');
    Route::post('table-sessions/{tableSession}/end', [TableSessionController::class, 'end'])
        ->name('table-sessions.end')
        ->middleware('permission:table_sessions.manage');

    Route::resource('board-game-sessions', BoardGameSessionController::class)
        ->only(['index', 'create', 'store'])
        ->middleware('permission:board_game_sessions.manage');
    Route::post('board-game-sessions/{boardGameSession}/end', [BoardGameSessionController::class, 'end'])
        ->name('board-game-sessions.end')
        ->middleware('permission:board_game_sessions.manage');

    Route::resource('orders', OrderController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middleware('permission:orders.manage');

    Route::resource('invoices', InvoiceController::class)
        ->only(['index', 'show'])
        ->middleware('permission:invoices.manage');
    Route::get('invoices/{invoice}/items', [InvoiceController::class, 'items'])
        ->name('invoices.items')
        ->middleware('permission:invoices.manage');
    Route::post('invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])
        ->name('invoices.mark-as-paid')
        ->middleware('permission:invoices.manage');
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])
        ->name('invoices.destroy')
        ->middleware('permission:invoices.delete');

    Route::resource('users', UserController::class)
        ->except(['show'])
        ->middleware('permission:users.manage');

    Route::resource('pricing-plans', PricingPlanController::class)
        ->except(['show'])
        ->middleware('permission:pricing_plans.manage');

    Route::get('accounting-stats', [AccountingStatsController::class, 'index'])
        ->name('accounting-stats.index')
        ->middleware('permission:invoices.manage');

    Route::post('system/tick', [SystemController::class, 'tick'])
        ->name('system.tick');
});
