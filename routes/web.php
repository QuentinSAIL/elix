<?php

use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Appearance;
use Illuminate\Support\Facades\Route;
use App\Services\GoCardlessDataService;

use App\Livewire\Note\Index as NoteIndex;

use App\Livewire\Routine\Index as RoutineIndex;

use App\Livewire\Money\BankAccountIndex as AccountIndex;
use App\Livewire\Money\BankTransactionIndex as TransactionIndex;

Route::middleware(['auth'])->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');

    Route::get('routines', RoutineIndex::class)->name('routines.index');
    Route::get('notes', NoteIndex::class)->name('notes.index');
    Route::group(['prefix' => 'money'], function () {
        Route::get('dashboard', TransactionIndex::class)->name('money.dashboard');
        Route::get('accounts', AccountIndex::class)->name('money.accounts');
        Route::get('transactions', TransactionIndex::class)->name('money.transactions');
        Route::get('categories', TransactionIndex::class)->name('money.categories');
    });
    Route::redirect('settings', 'settings/profile');
    Route::group(['prefix' => 'settings'], function () {
        Route::get('profile', Profile::class)->name('settings.profile');
        Route::get('password', Password::class)->name('settings.password');
        Route::get('appearance', Appearance::class)->name('settings.appearance');
    });
    Route::get('/bank-accounts/callback', [GoCardLessDataService::class, '@handleCallback'])->name('bank-accounts.callback');
});


require __DIR__ . '/auth.php';
