<?php

use App\Models\ApiKey;
use App\Models\ApiService;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeAll(function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([
            'results' => [
                [
                    'id' => 'test-bank',
                    'name' => 'Test Bank',
                    'max_access_valid_for_days' => 90,
                    'transaction_total_days' => 30,
                    'logo' => 'test-logo.png',
                ],
                [
                    'id' => 'other-bank',
                    'name' => 'Other Bank',
                    'max_access_valid_for_days' => 90,
                    'transaction_total_days' => 30,
                    'logo' => 'other-logo.png',
                ],
            ],
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/agreements/enduser/' => Http::response([
            'created' => true,
            'id' => 'test-agreement',
            'access_valid_for_days' => 90,
            'max_historical_days' => 30,
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/requisitions/' => Http::response([
            'created' => true,
            'link' => 'https://test-link.com',
        ], 200),
    ]);
});

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('middleware allows access when user has valid gocardless keys', function () {
    $user = $this->user;
    $apiService = ApiService::factory()->create(['name' => 'GoCardless']);
    ApiKey::factory()->for($user)->for($apiService)->create();
    $moneyModule = Module::factory()->create([
        'name' => 'Money',
        'endpoint' => 'money',
    ]);
    $user->modules()->attach($moneyModule);

    $response = $this->actingAs($user)
        ->get('/money/dashboard');

    $response->assertStatus(200);
});

test('middleware redirects when user does not have gocardless keys', function () {
    $user = $this->user;
    $apiService = ApiService::factory()->create(['name' => 'GoCardless']);
    // No ApiKey created for GoCardless for this user
    $moneyModule = Module::factory()->create([
        'name' => 'Money',
        'endpoint' => 'money',
    ]);
    $user->modules()->attach($moneyModule);

    $response = $this->actingAs($user)
        ->get('/money');

    // The middleware should redirect to settings.api-keys
    $response->assertRedirect(route('settings.api-keys'));
});

test('middleware redirects when user is not authenticated', function () {
    // No actingAs
    // No ApiService or ApiKey or Module setup needed

    $response = $this->get('/money');

    // The middleware should redirect to login first
    $response->assertRedirect('/login');
});
