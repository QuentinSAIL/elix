<?php

use App\Livewire\Money\BankTransactionIndex;
use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\MoneyCategoryMatch;
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
});

test('bank transaction index component can be rendered', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create();

    Livewire::test(BankTransactionIndex::class)
        ->assertStatus(200)
        ->assertSee($transaction->description);
});

test('can update selected account', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();

    Livewire::test(BankTransactionIndex::class)
        ->call('updateSelectedAccount', $bankAccount->id)
        ->assertSet('selectedAccount.id', $bankAccount->id)
        ->assertSet('allAccounts', false);
});

test('can select all accounts', function () {
    Livewire::test(BankTransactionIndex::class)
        ->call('updateSelectedAccount', 'all')
        ->assertSet('allAccounts', true)
        ->assertSet('selectedAccount', null);
});

test('can load more transactions', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    BankTransactions::factory()->count(150)->for($bankAccount, 'account')->create();

    $component = Livewire::test(BankTransactionIndex::class);
    $initialCount = count($component->get('transactions'));

    $component->call('loadMore');

    $this->assertGreaterThan($initialCount, count($component->get('transactions')));
});

test('can search transactions', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'description' => 'Test Transaction',
    ]);

    Livewire::test(BankTransactionIndex::class)
        ->set('search', 'Test')
        ->assertSee('Test Transaction');
});

test('can filter by category', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $category = MoneyCategory::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'money_category_id' => $category->id,
    ]);

    Livewire::test(BankTransactionIndex::class)
        ->set('categoryFilter', $category->id)
        ->assertSee($transaction->description);
});

test('can sort transactions', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction1 = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'transaction_date' => now()->subDay(),
        'amount' => 100,
    ]);
    $transaction2 = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'transaction_date' => now(),
        'amount' => 200,
    ]);

    Livewire::test(BankTransactionIndex::class)
        ->call('sortBy', 'amount')
        ->assertSet('sortField', 'amount');
});

test('can search and apply category', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $category = MoneyCategory::factory()->for($this->user)->create();
    $match = MoneyCategoryMatch::factory()->create([
        'money_category_id' => $category->id,
        'user_id' => $this->user->id,
        'keyword' => 'test',
    ]);
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'description' => 'This is a test transaction',
    ]);

    Livewire::test(BankTransactionIndex::class)
        ->call('searchAndApplyCategory', 'test');

    $this->assertDatabaseHas('bank_transactions', [
        'id' => $transaction->id,
        'money_category_id' => $category->id,
    ]);
});

test('can refresh transactions', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create();

    Livewire::test(BankTransactionIndex::class)
        ->call('refreshTransactions')
        ->assertSee($transaction->description);
});

test('can get transactions from gocardless', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
        'bankaccountdata.gocardless.com/api/v2/accounts/*/transactions/' => Http::response([
            'transactions' => [
                'booked' => [
                    [
                        'internalTransactionId' => 'test-transaction-1',
                        'bookingDate' => '2025-01-01',
                        'transactionAmount' => [
                            'amount' => '100.00',
                            'currency' => 'EUR',
                        ],
                        'remittanceInformationUnstructuredArray' => ['Test Transaction 1'],
                    ],
                    [
                        'internalTransactionId' => 'test-transaction-2',
                        'bookingDate' => '2025-01-02',
                        'transactionAmount' => [
                            'amount' => '50.00',
                            'currency' => 'EUR',
                        ],
                        'remittanceInformationUnstructuredArray' => ['Test Transaction 2'],
                    ],
                ],
            ],
        ], 200),
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => 'test-account-id',
    ]);

    Livewire::test(BankTransactionIndex::class)
        ->call('getTransactions');

    $this->assertDatabaseHas('bank_transactions', [
        'gocardless_transaction_id' => 'test-transaction-1',
        'bank_account_id' => $bankAccount->id,
    ]);
    $this->assertDatabaseHas('bank_transactions', [
        'gocardless_transaction_id' => 'test-transaction-2',
        'bank_account_id' => $bankAccount->id,
    ]);
});

test('stop loading more transactions when no more to load', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    BankTransactions::factory()->count(100)->for($bankAccount, 'account')->create();

    $component = Livewire::test(BankTransactionIndex::class);
    $component->call('loadMore');

    $this->assertTrue($component->get('noMoreToLoad'));

    // Simulate no more transactions
    $component->set('noMoreToLoad', true);
    $component->call('loadMore');

    $this->assertTrue($component->get('noMoreToLoad'));
});

test('can filter transactions by current month', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transactionCurrentMonth = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'transaction_date' => now()->startOfMonth(),
        'description' => 'Current Month Transaction',
    ]);
    $transactionLastMonth = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'transaction_date' => now()->subMonth()->startOfMonth(),
        'description' => 'Last Month Transaction',
    ]);

    Livewire::test(BankTransactionIndex::class)
        ->set('dateFilter', 'current_month')
        ->assertSee('Current Month Transaction')
        ->assertDontSee('Last Month Transaction');
});

test('can filter transactions by last month', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transactionCurrentMonth = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'transaction_date' => now()->startOfMonth(),
        'description' => 'Current Month Transaction',
    ]);
    $transactionLastMonth = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'transaction_date' => now()->subMonth()->startOfMonth(),
        'description' => 'Last Month Transaction',
    ]);

    Livewire::test(BankTransactionIndex::class)
        ->set('dateFilter', 'last_month')
        ->assertSee('Last Month Transaction')
        ->assertDontSee('Current Month Transaction');
});

test('can filter transactions by current year', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transactionCurrentYear = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'transaction_date' => now()->startOfYear(),
        'description' => 'Current Year Transaction',
    ]);
    $transactionLastYear = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'transaction_date' => now()->subYear()->startOfYear(),
        'description' => 'Last Year Transaction',
    ]);

    Livewire::test(BankTransactionIndex::class)
        ->set('dateFilter', 'current_year')
        ->assertSee('Current Year Transaction')
        ->assertDontSee('Last Year Transaction');
});
