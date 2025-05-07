<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

use App\Livewire\Routine\Index as RoutineIndex;
use App\Livewire\Note\Index as NoteIndex;
use App\Livewire\Money\Index as MoneyIndex;
use App\Livewire\Money\BankTransactionIndex as BankTransactionIndex;

use App\Services\GoCardlessDataService;
Route::get('/gocardless/callback', GoCardLessDataService::class . '@handleCallback');

Route::middleware(['auth'])->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');

    Route::get('routines', RoutineIndex::class)->name('routines.index');
    Route::get('notes', NoteIndex::class)->name('notes.index');
    Route::group(['prefix' => 'money'], function () {
        Route::get('/', MoneyIndex::class)->name('money.index');
        Route::get('transactions', BankTransactionIndex::class)->name('money.transactions');
    });
    Route::redirect('settings', 'settings/profile');
    Route::group(['prefix' => 'settings'], function () {
        Route::get('profile', Profile::class)->name('settings.profile');
        Route::get('password', Password::class)->name('settings.password');
        Route::get('appearance', Appearance::class)->name('settings.appearance');
    });

    // Route::get('/bank-data', [App\Http\Controllers\BankDataController::class, 'showAccounts']);
});

require __DIR__ . '/auth.php';
