<?php

use App\Livewire\Money\BankAccountIndex;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
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
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create();

    Livewire::test(BankAccountIndex::class)
        ->assertStatus(200)
        ->assertSee($bankAccount->name);
});

test('can update account name', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

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
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('updateAccountName', '00000000-0000-0000-0000-000000000000', 'New Name');

    Toaster::assertDispatched('Compte bancaire introuvable.');
});

test('can delete account', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create();

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('delete', $bankAccount->id);

    Toaster::assertDispatched('Compte bancaire supprimé avec succès.');

    $this->assertDatabaseMissing('bank_accounts', ['id' => $bankAccount->id]);
});

test('shows error when deleting non-existent account', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('delete', '00000000-0000-0000-0000-000000000000');

    Toaster::assertDispatched('Compte bancaire introuvable.');
});

test('can delete account with gocardless account id', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
        'bankaccountdata.gocardless.com/api/v2/requisitions/*' => Http::response([
            'status' => 'success',
        ], 200),
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
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
        'bankaccountdata.gocardless.com/api/v2/requisitions/?limit=100&offset=0' => Http::response([
            'results' => [
                [
                    'reference' => 'test-ref',
                    'accounts' => ['test-account-id'],
                ],
            ],
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/accounts/test-account-id/details/' => Http::response([
            'status_code' => 200,
            'account' => [
                'iban' => 'FR123456789',
                'currency' => 'EUR',
                'name' => 'Test Account',
                'cashAccountType' => 'CURRENT',
            ],
        ], 200),
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
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
        'bankaccountdata.gocardless.com/api/v2/requisitions/?limit=100&offset=0' => Http::response([
            'results' => [
                [
                    'reference' => 'test-ref',
                    'accounts' => ['test-account-id'],
                ],
            ],
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/accounts/test-account-id/details/' => Http::response([
            'status_code' => 500,
            'detail' => 'Error',
        ], 500),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => null,
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class, ['ref' => 'test-ref'])
        ->call('updateGoCardlessAccount');

    Toaster::assertDispatched('Error fetching account details from GoCardless.');
});

test('can check if account needs renewal', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => 'test-account-id',
        'reference' => 'test-reference',
    ]);

    $component = Livewire::test(BankAccountIndex::class);
    $needsRenewal = $component->instance()->needsRenewal($bankAccount, 8);

    $this->assertIsBool($needsRenewal);
});

test('needs renewal returns false when no end valid access', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'end_valid_access' => null,
    ]);

    $component = Livewire::test(BankAccountIndex::class);
    $needsRenewal = $component->instance()->needsRenewal($bankAccount, 8);

    $this->assertFalse($needsRenewal);
});

test('needs renewal returns true when within threshold', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'end_valid_access' => now()->addWeeks(4), // 4 weeks from now
    ]);

    $component = Livewire::test(BankAccountIndex::class);
    $needsRenewal = $component->instance()->needsRenewal($bankAccount, 8);

    $this->assertTrue($needsRenewal);
});

test('needs renewal returns false when beyond threshold', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'end_valid_access' => now()->addWeeks(12), // 12 weeks from now
    ]);

    $component = Livewire::test(BankAccountIndex::class);
    $needsRenewal = $component->instance()->needsRenewal($bankAccount, 8);

    $this->assertFalse($needsRenewal);
});

test('needs renewal returns false when already expired', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'end_valid_access' => now()->subWeeks(2), // 2 weeks ago
    ]);

    $component = Livewire::test(BankAccountIndex::class);
    $needsRenewal = $component->instance()->needsRenewal($bankAccount, 8);

    $this->assertFalse($needsRenewal);
});

test('can renew authorization successfully', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([
            [
                'id' => 'test-institution-id',
                'name' => 'Test Bank',
                'logo' => 'test-logo.png',
                'max_access_valid_for_days' => 90,
            ],
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/agreements/enduser/' => Http::response([
            'created' => true,
            'id' => 'new-agreement-id',
            'access_valid_for_days' => 90,
            'max_historical_days' => 90,
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/requisitions/' => Http::response([
            'created' => true,
            'link' => 'https://example.com/redirect',
        ], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => 'test-account-id',
        'institution_id' => 'test-institution-id',
        'agreement_id' => 'old-agreement-id',
        'transaction_total_days' => 90,
        'logo' => 'test-logo.png',
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('renewAuthorization', $bankAccount->id);

    Toaster::assertDispatched('Redirection vers votre banque pour renouveler l\'autorisation.');
});

test('renew authorization fails when account not found', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('renewAuthorization', '00000000-0000-0000-0000-000000000000');

    Toaster::assertDispatched('Compte bancaire introuvable.');
});

test('renew authorization fails when missing required fields', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => null, // Missing required field
        'institution_id' => null,
        'agreement_id' => null,
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('renewAuthorization', $bankAccount->id);

    Toaster::assertDispatched('Impossible de renouveler l\'autorisation pour ce compte.');
});

test('renew authorization handles bank not found', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => 'test-account-id',
        'institution_id' => 'non-existent-institution',
        'agreement_id' => 'test-agreement-id',
        'transaction_total_days' => 90,
        'logo' => 'test-logo.png',
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('renewAuthorization', $bankAccount->id);

    Toaster::assertDispatched('Impossible de récupérer les informations de la banque.');
});

test('renew authorization uses fallback max access days', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/institutions/?country=fr' => Http::response([
            [
                'id' => 'test-institution-id',
                'name' => 'Test Bank',
                'logo' => 'test-logo.png',
                // No max_access_valid_for_days field
            ],
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/agreements/enduser/' => Http::response([
            'created' => true,
            'id' => 'new-agreement-id',
            'access_valid_for_days' => 90,
            'max_historical_days' => 90,
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/requisitions/' => Http::response([
            'created' => true,
            'link' => 'https://example.com/redirect',
        ], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => 'test-account-id',
        'institution_id' => 'test-institution-id',
        'agreement_id' => 'test-agreement-id',
        'transaction_total_days' => 90,
        'logo' => 'test-logo.png',
    ]);

    Toaster::fake();

    Livewire::test(BankAccountIndex::class)
        ->call('renewAuthorization', $bankAccount->id);

    Toaster::assertDispatched('Redirection vers votre banque pour renouveler l\'autorisation.');
});
