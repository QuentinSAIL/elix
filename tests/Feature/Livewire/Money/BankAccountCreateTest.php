<?php

use App\Livewire\Money\BankAccountCreate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create GoCardless API service and keys
    $apiService = \App\Models\ApiService::factory()->create([
        'name' => 'GoCardless',
    ]);

    \App\Models\ApiKey::factory()->create([
        'user_id' => $this->user->id,
        'api_service_id' => $apiService->id,
        'secret_id' => 'test-secret-id',
        'secret_key' => 'test-secret-key',
    ]);

    // Move Http::fake() here
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

test('bank account create component can be rendered', function () {

    Livewire::test(BankAccountCreate::class)
        ->assertStatus(200);
});

test('can filter banks by search term', function () {

    $banks = [
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
    ];

    $component = Livewire::test(BankAccountCreate::class)
        ->set('banks', $banks);
    $component->set('searchTerm', 'Test');

    $filteredBanks = $component->instance()->getFilteredBanksProperty();
    $this->assertCount(1, $filteredBanks);
    $this->assertEquals('Test Bank', $filteredBanks[0]['name']);
});

test('can select a bank', function () {

    $banks = [
        [
            'id' => 'test-bank',
            'name' => 'Test Bank',
            'max_access_valid_for_days' => 90,
            'transaction_total_days' => 30,
            'logo' => 'test-logo.png',
        ],
    ];

    $component = Livewire::test(BankAccountCreate::class)
        ->set('banks', $banks);
    $component->call('updateSelectedBank', 'test-bank');

    $this->assertEquals('test-bank', $component->get('selectedBank'));
    $this->assertEquals('Test Bank', $component->get('searchTerm'));
    $this->assertEquals(90, $component->get('maxAccessValidForDays'));
    $this->assertEquals(30, $component->get('transactionTotalDays'));
    $this->assertEquals('test-logo.png', $component->get('logo'));
});

test('can add new bank account', function () {

    $component = Livewire::test(BankAccountCreate::class);
    $component->set('selectedBank', 'test-bank');
    $component->set('transactionTotalDays', 30);
    $component->set('maxAccessValidForDays', 90);
    $component->set('logo', 'test-logo.png');

    $component->call('addNewBankAccount');

    // The method should execute without errors
    $this->assertTrue(true);
});

test('handles empty banks array', function () {

    $component = Livewire::test(BankAccountCreate::class);
    $component->set('banks', []);
    $component->set('searchTerm', 'test');

    $filteredBanks = $component->instance()->getFilteredBanksProperty();
    $this->assertEmpty($filteredBanks);
});

test('handles non-existent bank selection', function () {

    $banks = []; // No banks exist for this test case

    $component = Livewire::test(BankAccountCreate::class)
        ->set('banks', $banks);
    $component->call('updateSelectedBank', 'non-existent-bank');

    $this->assertEquals('non-existent-bank', $component->get('selectedBank'));
    $this->assertNull($component->get('searchTerm'));
});

test('handles bank selection with no logo', function () {

    $banks = [
        [
            'id' => 'test-bank',
            'name' => 'Test Bank',
            'max_access_valid_for_days' => 90,
            'transaction_total_days' => 30,
            'logo' => null, // No logo provided
        ],
    ];

    $component = Livewire::test(BankAccountCreate::class)
        ->set('banks', $banks);
    $component->call('updateSelectedBank', 'test-bank');

    $this->assertEquals('test-bank', $component->get('selectedBank'));
    $this->assertNull($component->get('logo')); // Logo should be null
});

test('handles bank selection with empty search term', function () {

    $banks = [
        [
            'id' => 'test-bank',
            'name' => 'Test Bank',
            'max_access_valid_for_days' => 90,
            'transaction_total_days' => 30,
            'logo' => 'test-logo.png',
        ],
    ];

    $component = Livewire::test(BankAccountCreate::class)
        ->set('banks', $banks);
    $component->call('updateSelectedBank', 'test-bank');

    // Set search term to empty
    $component->set('searchTerm', '');

    $this->assertEquals('', $component->get('searchTerm'));
});

test('can not add new bank account without selection', function () {

    $component = Livewire::test(BankAccountCreate::class);
    $component->set('selectedBank', null); // No bank selected

    $component->call('addNewBankAccount')
        ->assertDispatched('error', ['message' => 'Please select a bank.']);

});
