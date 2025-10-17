<?php

namespace Tests\Feature\Console\Commands;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateBankAccountsDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_handles_no_bank_accounts()
    {
        $this->artisan('bank-accounts:update-data')
            ->expectsOutput('🔄 Mise à jour des données des comptes bancaires...')
            ->expectsOutput('Aucun compte bancaire avec un ID GoCardless trouvé.')
            ->assertExitCode(0);
    }

    public function test_command_with_specific_user_id()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $bankAccount1 = BankAccount::factory()->create([
            'user_id' => $user1->id,
            'gocardless_account_id' => 'test-account-1',
        ]);

        $bankAccount2 = BankAccount::factory()->create([
            'user_id' => $user2->id,
            'gocardless_account_id' => 'test-account-2',
        ]);

        $this->artisan('bank-accounts:update-data', ['--user-id' => $user1->id])
            ->expectsOutput('🔄 Mise à jour des données des comptes bancaires...')
            ->expectsOutput('📊 1 compte(s) bancaire(s) trouvé(s).')
            ->assertExitCode(0);
    }
}
