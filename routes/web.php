<?php

use App\Http\Middleware\UserHasValidGoCardlessKeys;
use App\Http\Middleware\UserModule;
use App\Livewire\Money\BankAccountIndex as AccountIndex;
use App\Livewire\Money\BankTransactionIndex as TransactionIndex;
use App\Livewire\Money\WalletIndex;
use App\Livewire\Money\CategoryIndex;
use App\Livewire\Money\Dashboard;
use App\Livewire\Note\Index as NoteIndex;
use App\Livewire\Routine\Index as RoutineIndex;
use App\Livewire\Settings\ApiKey;
use App\Livewire\Settings\Modules;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Preference;
use App\Livewire\Settings\Profile;
use App\Services\GoCardlessDataService;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');
    Route::middleware([UserModule::class])->group(function () {
        Route::get('routines', RoutineIndex::class)->name('routines.index');
        Route::get('notes', NoteIndex::class)->name('notes.index');
        Route::middleware([UserHasValidGoCardlessKeys::class])->group(function () {
            Route::group(['prefix' => 'money'], function () {
                Route::get('/', function () {
                    return redirect()->route('money.dashboard');
                })->name('money.index');
                Route::get('dashboard', Dashboard::class)->name('money.dashboard');
                Route::get('accounts', AccountIndex::class)->name('money.accounts');
                Route::get('wallets', WalletIndex::class)->name('money.wallets');
                Route::get('transactions', TransactionIndex::class)->name('money.transactions');
                Route::get('categories', CategoryIndex::class)->name('money.categories');
            });
            Route::get('/bank-accounts/callback', [GoCardlessDataService::class, 'handleCallback'])->name('bank-accounts.callback');
        });
    });
    Route::redirect('settings', 'settings/profile')->name('settings');
    Route::group(['prefix' => 'settings'], function () {
        Route::get('profile', Profile::class)->name('settings.profile');
        Route::get('password', Password::class)->name('settings.password');
        Route::get('preference', Preference::class)->name('settings.preference');
        Route::get('api-keys', ApiKey::class)->name('settings.api-keys');
        Route::get('modules', Modules::class)->name('settings.modules');
    });
});

require __DIR__ . '/auth.php';
