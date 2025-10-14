<?php

use App\Livewire\Settings\LanguageSwitcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Masmerise\Toaster\Toaster;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

test('language switcher component can be rendered', function () {
    Livewire::test(LanguageSwitcher::class)
        ->assertStatus(200);
});

test('can mount with default locale', function () {
    Livewire::test(LanguageSwitcher::class)
        ->assertSet('locale', App::getLocale())
        ->assertSet('supportedLocales', config('app.supported_locales'));
});

test('can mount with session locale', function () {
    Session::put('locale', 'fr');

    Livewire::test(LanguageSwitcher::class)
        ->assertSet('locale', 'fr');
});

test('can switch to supported language', function () {
    Toaster::fake();
    $supportedLocales = config('app.supported_locales');
    $testLang = array_key_first($supportedLocales);

    $component = Livewire::test(LanguageSwitcher::class);
    $component->call('switchTo', $testLang);

    $this->assertEquals($testLang, $this->user->preference()->first()->locale);
    $this->assertEquals($testLang, App::getLocale());
    $this->assertEquals($testLang, Session::get('locale'));

    Toaster::assertDispatched('Language switched successfully to '.$supportedLocales[$testLang]);
});

test('cannot switch to unsupported language', function () {
    Toaster::fake();
    $originalLocale = App::getLocale();
    $supportedLocales = ['en' => 'English', 'fr' => 'Français'];

    $component = Livewire::test(LanguageSwitcher::class, ['supportedLocales' => $supportedLocales]);
    $component->call('switchTo', 'unsupported');
    $component->assertSet('locale', $originalLocale);

    $this->assertEquals($originalLocale, App::getLocale());
    Toaster::assertDispatched(__('Language not supported.'));
});

test('handles invalid locale in session', function () {
    Session::put('locale', 'invalid');

    Livewire::test(LanguageSwitcher::class)
        ->assertSet('locale', array_key_first(config('app.supported_locales')));
});
