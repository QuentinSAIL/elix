<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\UpdateWalletsFromTransactions;
use App\Models\BankTransactions;
use App\Models\MoneyCategory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateWalletsFromTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_successfully(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
        ]);

        $this->artisan(UpdateWalletsFromTransactions::class)
            ->assertExitCode(0);
    }

    public function test_command_with_recalculate_option(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
        ]);

        $this->artisan(UpdateWalletsFromTransactions::class, ['--recalculate' => true])
            ->assertExitCode(0);
    }

    public function test_command_processes_transactions(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
        ]);

        $category = MoneyCategory::factory()->create([
            'user_id' => $user->id,
        ]);

        $wallet->update(['category_linked_id' => $category->id]);

        BankTransactions::factory()->create([
            'money_category_id' => $category->id,
        ]);

        $this->artisan(UpdateWalletsFromTransactions::class)
            ->assertExitCode(0);
    }

    public function test_command_handles_multi_mode_wallets(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'multi',
        ]);

        $this->artisan(UpdateWalletsFromTransactions::class, ['--recalculate' => true])
            ->assertExitCode(0);
    }

    public function test_command_handles_no_wallets(): void
    {
        $this->artisan(UpdateWalletsFromTransactions::class)
            ->assertExitCode(0);
    }

    public function test_command_handles_no_transactions(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'mode' => 'single',
        ]);

        $this->artisan(UpdateWalletsFromTransactions::class)
            ->assertExitCode(0);
    }
}
