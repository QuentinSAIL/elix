<?php

use App\Livewire\Settings\ApiKey;
use App\Livewire\Settings\Modules;
use App\Livewire\Settings\Profile;
use App\Http\Middleware\UserModule;
use App\Livewire\Settings\Password;
use App\Livewire\Money\CategoryIndex;
use App\Livewire\Note\Index as NoteIndex;
use App\Livewire\Settings\Appearance;
use Illuminate\Support\Facades\Route;
use App\Services\GoCardlessDataService;
use App\Livewire\Routine\Index as RoutineIndex;
use App\Http\Middleware\UserHasValidGoCardlessKeys;
use App\Livewire\Money\BankAccountIndex as AccountIndex;
use App\Livewire\Money\BankTransactionIndex as TransactionIndex;

Route::middleware(['auth'])->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');
    Route::middleware([UserModule::class])->group(function () {
        Route::get('routines', RoutineIndex::class)->name('routines.index');
        Route::get('notes', NoteIndex::class)->name('notes.index');
        Route::middleware([UserHasValidGoCardlessKeys::class])->group(function () {
            Route::group(['prefix' => 'money'], function () {
                Route::get('dashboard', TransactionIndex::class)->name('money.dashboard');
                Route::get('accounts', AccountIndex::class)->name('money.accounts');
                Route::get('transactions', TransactionIndex::class)->name('money.transactions');
                Route::get('categories', CategoryIndex::class)->name('money.categories');
            });
            Route::get('/bank-accounts/callback', [GoCardLessDataService::class, '@handleCallback'])->name('bank-accounts.callback');
        });
    });
    Route::redirect('settings', 'settings/profile')->name('settings');
    Route::group(['prefix' => 'settings'], function () {
        Route::get('profile', Profile::class)->name('settings.profile');
        Route::get('password', Password::class)->name('settings.password');
        Route::get('appearance', Appearance::class)->name('settings.appearance');
        Route::get('api-keys', ApiKey::class)->name('settings.api-keys');
        Route::get('modules', Modules::class)->name('settings.modules');
    });
});

require __DIR__ . '/auth.php';
