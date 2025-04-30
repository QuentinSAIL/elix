<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

use App\Livewire\Routine\Index as RoutineIndex;
use App\Livewire\Note\Index as NoteIndex;

Route::middleware(['auth'])->group(function () {
    Route::get('routines', RoutineIndex::class)->name('routines.index');
    Route::get('notes', NoteIndex::class)->name('notes.index');
    Route::view('/', 'dashboard')->name('dashboard');

    Route::redirect('settings', 'settings/profile');
    Route::group(['prefix' => 'settings'], function () {
        Route::get('profile', Profile::class)->name('settings.profile');
        Route::get('password', Password::class)->name('settings.password');
        Route::get('appearance', Appearance::class)->name('settings.appearance');
    });
});

require __DIR__ . '/auth.php';
