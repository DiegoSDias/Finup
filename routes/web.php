<?php

use App\Http\Controllers\CategoryGroupController;
use App\Http\Controllers\MonthsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\RecurringExpenseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Pluggy\PluggyController;
use App\Http\Controllers\Pluggy\PluggyAccountController;
use App\Http\Controllers\Pluggy\PluggyWebhookController;
use App\Http\Controllers\Pluggy\PluggyTransactionRuleController;
use App\Http\Controllers\Pluggy\PluggyTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group( function() {
    Route::get('/', [HomeController::class, 'index'])->name('dashboard');
    Route::get('months/{year}/{month}', [MonthsController::class, 'show'])->name('month.show');
    Route::post('months/{year}/{month}', [MonthsController::class, 'close'])->name('month.close');
    Route::get('expenses/{expense}/confirm-delete', [ExpensesController::class, 'confirmDelete'])->name('expenses.confirm-delete');
    Route::resource('expenses', ExpensesController::class);
    Route::get('recurring-expenses', [RecurringExpenseController::class, 'index'])->name('recurring-expenses.index');
    Route::get('recurring-expenses/create', [RecurringExpenseController::class, 'create'])->name('recurring-expenses.create');
    Route::post('recurring-expenses', [RecurringExpenseController::class, 'store'])->name('recurring-expenses.store');
    Route::get('recurring-expenses/{recurringExpense}/edit', [RecurringExpenseController::class, 'edit'])->name('recurring-expenses.edit');
    Route::put('recurring-expenses/{recurringExpense}', [RecurringExpenseController::class, 'update'])->name('recurring-expenses.update');
    Route::post('recurring-expenses/{recurringExpense}/end', [RecurringExpenseController::class, 'end'])->name('recurring-expenses.end');
    Route::delete('recurring-expenses/{recurringExpense}', [RecurringExpenseController::class, 'destroy'])->name('recurring-expenses.destroy');
    Route::resource('categories', CategoriesController::class);
    Route::resource('category-groups', CategoryGroupController::class);

    Route::post('/pluggy/connect-token', [PluggyController::class, 'connectToken'])->name('pluggy.connect-token');
    Route::post('/pluggy/items', [PluggyAccountController::class, 'store'])->name('pluggy.items.store');
    Route::post('/pluggy/transaction-rules', [PluggyTransactionRuleController::class, 'store'])->name('pluggy.transaction-rules.store');
    Route::patch('/pluggy/transaction-rules/{rule}', [PluggyTransactionRuleController::class, 'update'])->name('pluggy.transaction-rules.update');
    Route::delete('/pluggy/transaction-rules/{rule}', [PluggyTransactionRuleController::class, 'destroy'])->name('pluggy.transaction-rules.destroy');
    Route::get('/pluggy/transactions/{transaction}', [PluggyTransactionController::class, 'show'])->name('pluggy.transactions.show');
    Route::get('/pluggy/transactions/{transaction}/edit', [PluggyTransactionController::class, 'edit'])->name('pluggy.transactions.edit');
    Route::put('/pluggy/transactions/{transaction}', [PluggyTransactionController::class, 'update'])->name('pluggy.transactions.update');
    Route::delete('/pluggy/transactions/{transaction}', [PluggyTransactionController::class, 'destroy'])->name('pluggy.transactions.destroy');
    Route::get('/pluggy/accounts', [PluggyAccountController::class, 'index'])->name('pluggy.accounts.index');
    Route::get('/pluggy/connections', [PluggyAccountController::class, 'connections'])->name('pluggy.connections');
    Route::post('/pluggy/connections/sync-all', [PluggyAccountController::class, 'syncAll'])->name('pluggy.connections.sync-all');
    Route::view('/pluggy/sobre-os-dados', 'pluggy.data-notice')->name('pluggy.data-notice');
    Route::post('/pluggy/connections/{connection}/sync', [PluggyAccountController::class, 'sync'])->name('pluggy.connections.sync');
    Route::post('/pluggy/connections/{connection}/reactivate', [PluggyAccountController::class, 'reactivate'])->name('pluggy.connections.reactivate');
    Route::delete('/pluggy/connections/{connection}', [PluggyAccountController::class, 'destroy'])->name('pluggy.connections.destroy');
});

Route::post('/webhooks/pluggy', [PluggyWebhookController::class, 'handle'])->name('pluggy.webhook');

require __DIR__.'/auth.php';
