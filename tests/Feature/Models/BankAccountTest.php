<?php

use App\Models\User;
use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Services\GoCardlessDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('bank account belongs to user', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();

    $this->assertInstanceOf(User::class, $bankAccount->user);
    $this->assertEquals($this->user->id, $bankAccount->user->id);
});

test('bank account has many transactions', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create();

    $this->assertInstanceOf(BankTransactions::class, $bankAccount->transactions->first());
    $this->assertEquals($transaction->id, $bankAccount->transactions->first()->id);
});

test('can group transactions by date', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction1 = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'transaction_date' => now(),
        'amount' => 100,
    ]);
    $transaction2 = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'transaction_date' => now(),
        'amount' => 200,
    ]);

    $groupedTransactions = $bankAccount->transactionsGroupedByDate();

    $this->assertCount(1, $groupedTransactions);
    $this->assertEquals(300, $groupedTransactions->first()['total']);
    $this->assertCount(2, $groupedTransactions->first()['transactions']);
});

test('update from gocardless returns null when no gocardless account id', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create([
        'gocardless_account_id' => null,
    ]);

    $gocardlessService = app(GoCardlessDataService::class);
    $result = $bankAccount->updateFromGocardless($gocardlessService);

    $this->assertNull($result);
});
