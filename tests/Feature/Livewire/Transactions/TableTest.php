<?php

use App\Livewire\Transactions\Table;
use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('transactions table component can be rendered', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();

    Livewire::test(Table::class, ['account' => $bankAccount])
        ->assertStatus(200);
});

test('can get transactions property', function () {
    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create();

    $component = Livewire::test(Table::class, ['account' => $bankAccount]);

    $transactions = $component->instance()->getTransactionsProperty();
    $this->assertNotNull($transactions);
});
