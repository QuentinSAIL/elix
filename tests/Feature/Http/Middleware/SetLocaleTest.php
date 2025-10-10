<?php

use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->middleware = new SetLocale;
    $this->user = User::factory()->create();
});

test('set locale middleware sets locale from query parameter', function () {
    $request = Request::create('/test', 'GET', ['lang' => 'en']);

    $response = $this->middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(Session::get('locale'))->toBe('en');
    expect(App::getLocale())->toBe('en');
});

test('set locale middleware ignores unsupported locale from query', function () {
    $request = Request::create('/test', 'GET', ['lang' => 'invalid']);

    $response = $this->middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(App::getLocale())->toBe(config('app.locale'));
});

test('set locale middleware sets locale from session', function () {
    Session::put('locale', 'fr');

    $request = Request::create('/test');

    $response = $this->middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(App::getLocale())->toBe('fr');
});

test('set locale middleware sets locale from user preference', function () {
    $this->actingAs($this->user);

    UserPreference::factory()->create([
        'user_id' => $this->user->id,
        'locale' => 'es',
    ]);

    $request = Request::create('/test');

    $response = $this->middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(App::getLocale())->toBe('es');
});

test('set locale middleware prioritizes user preference over session', function () {
    $this->actingAs($this->user);

    Session::put('locale', 'fr');

    UserPreference::factory()->create([
        'user_id' => $this->user->id,
        'locale' => 'es',
    ]);

    $request = Request::create('/test');

    $response = $this->middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(App::getLocale())->toBe('es');
});

test('set locale middleware ignores unsupported user preference locale', function () {
    $this->actingAs($this->user);

    UserPreference::factory()->create([
        'user_id' => $this->user->id,
        'locale' => 'invalid',
    ]);

    $request = Request::create('/test');

    $response = $this->middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(App::getLocale())->toBe(config('app.locale'));
});

test('set locale middleware falls back to default locale', function () {
    $request = Request::create('/test');

    $response = $this->middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(App::getLocale())->toBe(config('app.locale'));
});

test('set locale middleware handles unauthenticated user', function () {
    $request = Request::create('/test');

    $response = $this->middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(App::getLocale())->toBe(config('app.locale'));
});
