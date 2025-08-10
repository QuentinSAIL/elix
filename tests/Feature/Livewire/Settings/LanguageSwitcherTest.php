<?php

use App\Livewire\Settings\LanguageSwitcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
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
    $supportedLocales = config('app.supported_locales');
    $testLang = array_key_first($supportedLocales);

    Livewire::test(LanguageSwitcher::class)
        ->call('switchTo', $testLang)
        ->assertSet('locale', $testLang);

    $this->assertEquals($testLang, App::getLocale());
    $this->assertEquals($testLang, Session::get('locale'));
});

test('cannot switch to unsupported language', function () {
    Livewire::test(LanguageSwitcher::class)
        ->call('switchTo', 'unsupported');
});

test('handles invalid locale in session', function () {
    Session::put('locale', 'invalid');

    Livewire::test(LanguageSwitcher::class)
        ->assertSet('locale', array_key_first(config('app.supported_locales')));
});
