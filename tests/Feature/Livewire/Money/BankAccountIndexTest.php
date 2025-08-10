<?php

use App\Models\User;
use App\Models\BankAccount;
use App\Services\GoCardlessDataService;
use Livewire\Livewire;
use App\Livewire\Money\BankAccountIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Masmerise\Toaster\Toaster;

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
});

test('bank account index component can be rendered', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();

    Livewire::test(BankAccountIndex::class)
        ->assertStatus(200)
        ->assertSee($bankAccount->name);
});

test('can update account name', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'name' => 'Old Name',
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('updateAccountName', $bankAccount->id, 'New Name');

    Toaster::assertDispatched('Compte bancaire mis à jour avec succès.');

    $bankAccount->refresh();
    $this->assertEquals('New Name', $bankAccount->name);
});

test('shows error when updating non-existent account', function () {
    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('updateAccountName', '00000000-0000-0000-0000-000000000000', 'New Name');

    Toaster::assertDispatched('Compte bancaire introuvable.');
});

test('can delete account', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('delete', $bankAccount->id);

    Toaster::assertDispatched('Compte bancaire supprimé avec succès.');

    $this->assertDatabaseMissing('bank_accounts', ['id' => $bankAccount->id]);
});

test('shows error when deleting non-existent account', function () {
    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('delete', '00000000-0000-0000-0000-000000000000');

    Toaster::assertDispatched('Compte bancaire introuvable.');
});

test('can delete account with gocardless account id', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token'
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/requisitions/*' => Http::response([
            'status' => 'success'
        ], 200)
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => 'test-account-id',
        'reference' => 'test-reference',
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('delete', $bankAccount->id);

    Toaster::assertDispatched('Compte bancaire supprimé avec succès.');

    $this->assertDatabaseMissing('bank_accounts', ['id' => $bankAccount->id]);
});

test('can update gocardless account', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token'
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/requisitions/?limit=100&offset=0' => Http::response([
            'results' => [
                [
                    'reference' => 'test-ref',
                    'accounts' => ['test-account-id']
                ]
            ]
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/accounts/test-account-id/details/' => Http::response([
            'status_code' => 200,
            'account' => [
                'iban' => 'FR123456789',
                'currency' => 'EUR',
                'name' => 'Test Account',
                'cashAccountType' => 'CURRENT',
            ]
        ], 200)
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => null,
    ]);

    Livewire::test(BankAccountIndex::class, ['ref' => 'test-ref'])
        ->call('updateGoCardlessAccount');

    $bankAccount->refresh();
    $this->assertEquals('test-account-id', $bankAccount->gocardless_account_id);
    $this->assertEquals('FR123456789', $bankAccount->iban);
    $this->assertEquals('EUR', $bankAccount->currency);
});

test('handles error when updating gocardless account', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token'
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/requisitions/?limit=100&offset=0' => Http::response([
            'results' => [
                [
                    'reference' => 'test-ref',
                    'accounts' => ['test-account-id']
                ]
            ]
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/accounts/test-account-id/details/' => Http::response([
            'status_code' => 500,
            'detail' => 'Error'
        ], 500)
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => null,
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class, ['ref' => 'test-ref'])
        ->call('updateGoCardlessAccount');

    Toaster::assertDispatched('Error fetching account details from GoCardless.');
});
