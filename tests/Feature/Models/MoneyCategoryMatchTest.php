<?php

use App\Models\BankAccount;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\MoneyCategoryMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('money category match belongs to category', function () {
    $category = MoneyCategory::factory()->for($this->user)->create();
    $match = MoneyCategoryMatch::factory()->create([
        'money_category_id' => $category->id,
        'user_id' => $this->user->id,
    ]);

    $this->assertInstanceOf(MoneyCategory::class, $match->category);
    $this->assertEquals($category->id, $match->category->id);
});

test('money category match belongs to user', function () {
    $category = MoneyCategory::factory()->for($this->user)->create();
    $match = MoneyCategoryMatch::factory()->create([
        'money_category_id' => $category->id,
        'user_id' => $this->user->id,
    ]);

    $this->assertInstanceOf(User::class, $match->user);
    $this->assertEquals($this->user->id, $match->user->id);
});

test('can check and apply category to transaction', function () {
    $category = MoneyCategory::factory()->for($this->user)->create();
    $match = MoneyCategoryMatch::factory()->create([
        'money_category_id' => $category->id,
        'user_id' => $this->user->id,
        'keyword' => 'test',
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'description' => 'This is a test transaction',
    ]);

    MoneyCategoryMatch::checkAndApplyCategory($transaction);

    $transaction->refresh();
    $this->assertEquals($category->id, $transaction->money_category_id);
});

test('can search and apply all match categories', function () {
    $category = MoneyCategory::factory()->for($this->user)->create();
    $match = MoneyCategoryMatch::factory()->create([
        'money_category_id' => $category->id,
        'user_id' => $this->user->id,
        'keyword' => 'test',
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'description' => 'This is a test transaction',
    ]);

    $count = MoneyCategoryMatch::searchAndApplyAllMatchCategory();

    $this->assertEquals(1, $count);
    $transaction->refresh();
    $this->assertEquals($category->id, $transaction->money_category_id);
});

test('can search and apply match category for specific keyword', function () {
    $category = MoneyCategory::factory()->for($this->user)->create();
    $match = MoneyCategoryMatch::factory()->create([
        'money_category_id' => $category->id,
        'user_id' => $this->user->id,
        'keyword' => 'test',
    ]);

    $bankAccount = BankAccount::factory()->for($this->user)->create();
    $transaction = BankTransactions::factory()->for($bankAccount, 'account')->create([
        'description' => 'This is a test transaction',
    ]);

    $count = MoneyCategoryMatch::searchAndApplyMatchCategory('test', true);

    $this->assertEquals(1, $count);
    $transaction->refresh();
    $this->assertEquals($category->id, $transaction->money_category_id);
});
