<?php

use App\Models\ApiKey;
use App\Models\ApiService;
use App\Models\User;
use App\Services\GoCardlessDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can format duration correctly', function () {
    $service = new GoCardlessDataService;

    $this->assertEquals('1 hour et 30 minutes', $service->formatDuration(5400));
    $this->assertEquals('2 hours et 15 minutes', $service->formatDuration(8100));
    $this->assertEquals('45 minutes', $service->formatDuration(2700));
    $this->assertEquals('1 hour', $service->formatDuration(3600));
});

test('can get access token with cache', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
    ]);

    $apiService = ApiService::factory()->create(['name' => 'GoCardless']);
    $apiKey = ApiKey::factory()->for($this->user)->for($apiService)->create([
        'secret_id' => 'test-secret-id',
        'secret_key' => 'test-secret-key',
    ]);

    $service = new GoCardlessDataService;
    $token = $service->accessToken();

    $this->assertEquals('test-access-token', $token);
    $this->assertTrue(Cache::has('gocardless_access_token'));
});

test('can get access token without cache', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
    ]);

    $apiService = ApiService::factory()->create(['name' => 'GoCardless']);
    $apiKey = ApiKey::factory()->for($this->user)->for($apiService)->create([
        'secret_id' => 'test-secret-id',
        'secret_key' => 'test-secret-key',
    ]);

    $service = new GoCardlessDataService;
    $token = $service->accessToken(false);

    $this->assertEquals('test-access-token', $token);
});

test('can get account transactions', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/accounts/test-account/transactions/' => Http::response([
            'transactions' => [],
        ], 200),
    ]);

    $apiService = ApiService::factory()->create(['name' => 'GoCardless']);
    $apiKey = ApiKey::factory()->for($this->user)->for($apiService)->create([
        'secret_id' => 'test-secret-id',
        'secret_key' => 'test-secret-key',
    ]);

    $service = new GoCardlessDataService;
    $transactions = $service->getAccountTransactions('test-account');

    $this->assertIsArray($transactions);
    $this->assertArrayHasKey('transactions', $transactions);
});

test('can get account balances', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/accounts/test-account/balances/' => Http::response([
            'balances' => [],
        ], 200),
    ]);

    $apiService = ApiService::factory()->create(['name' => 'GoCardless']);
    $apiKey = ApiKey::factory()->for($this->user)->for($apiService)->create([
        'secret_id' => 'test-secret-id',
        'secret_key' => 'test-secret-key',
    ]);

    $service = new GoCardlessDataService;
    $balances = $service->getAccountBalances('test-account');

    $this->assertIsArray($balances);
    $this->assertArrayHasKey('balances', $balances);
});
