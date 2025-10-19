<?php

namespace Tests\Feature\Console\Commands;

use App\Models\ApiKey;
use App\Models\ApiService;
use App\Models\BankAccount;
use App\Models\User;
use App\Services\GoCardlessDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_command_handles_user_not_found()
    {
        $user = User::factory()->create();
        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'gocardless_account_id' => 'test-account-1',
        ]);

        // Simuler un compte sans utilisateur en supprimant l'utilisateur
        $user->delete();

        $this->artisan('bank-accounts:update-data')
            ->expectsOutput('🔄 Mise à jour des données des comptes bancaires...')
            ->expectsOutput('📊 1 compte(s) bancaire(s) trouvé(s).')
            ->expectsOutput('❌ Utilisateur non trouvé pour le compte ' . $bankAccount->name)
            ->assertExitCode(0);
    }

    public function test_command_handles_api_error()
    {
        $user = User::factory()->create();
        $apiService = ApiService::factory()->create(['name' => 'GoCardless']);
        $apiKey = ApiKey::factory()->create([
            'user_id' => $user->id,
            'api_service_id' => $apiService->id,
        ]);

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'gocardless_account_id' => 'test-account-1',
        ]);

        // Mock HTTP pour simuler une erreur API
        Http::fake([
            'bankaccountdata.gocardless.com/api/v2/accounts/test-account-1/details/' => Http::response([
                'status_code' => 500,
                'detail' => 'Internal server error'
            ], 500),
        ]);

        $this->artisan('bank-accounts:update-data')
            ->expectsOutput('🔄 Mise à jour des données des comptes bancaires...')
            ->expectsOutput('📊 1 compte(s) bancaire(s) trouvé(s).')
            ->expectsOutput('🔄 Traitement du compte: ' . $bankAccount->name . ' (ID: test-account-1)')
            ->expectsOutput('❌ Erreur lors de la récupération des détails du compte ' . $bankAccount->name . ': {"status_code":500,"detail":"Internal server error"}')
            ->assertExitCode(0);
    }

    public function test_command_successfully_updates_account_data()
    {
        $user = User::factory()->create();
        $apiService = ApiService::factory()->create(['name' => 'GoCardless']);
        $apiKey = ApiKey::factory()->create([
            'user_id' => $user->id,
            'api_service_id' => $apiService->id,
        ]);

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'gocardless_account_id' => 'test-account-1',
            'agreement_id' => 'test-agreement-1',
        ]);

        // Mock HTTP pour simuler des réponses réussies
        Http::fake([
            'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
                'access' => 'test-access-token'
            ]),
            'bankaccountdata.gocardless.com/api/v2/accounts/test-account-1/details/' => Http::response([
                'account' => [
                    'iban' => 'FR1420041010050500013M02606',
                    'currency' => 'EUR',
                    'name' => 'John Doe',
                    'cashAccountType' => 'CACC'
                ]
            ]),
            'bankaccountdata.gocardless.com/api/v2/agreements/enduser/test-agreement-1/' => Http::response([
                'access_valid_for_days' => 90
            ]),
        ]);

        $this->artisan('bank-accounts:update-data')
            ->expectsOutput('🔄 Mise à jour des données des comptes bancaires...')
            ->expectsOutput('📊 1 compte(s) bancaire(s) trouvé(s).')
            ->expectsOutput('🔄 Traitement du compte: ' . $bankAccount->name . ' (ID: test-account-1)')
            ->expectsOutput('📋 Données récupérées depuis GoCardless:')
            ->expectsOutput('   - IBAN: FR1420041010050500013M02606')
            ->expectsOutput('   - Devise: EUR')
            ->expectsOutput('   - Titulaire: John Doe')
            ->expectsOutput('   - Type: CACC')
            ->expectsOutput('📅 Détails de l\'accord:')
            ->expectsOutput('   - Validité: 90 jours')
            ->expectsOutput('✅ Compte ' . $bankAccount->name . ' mis à jour: iban, currency, owner_name, cash_account_type, end_valid_access')
            ->assertExitCode(0);

        // Vérifier que les données ont été mises à jour
        $bankAccount->refresh();
        $this->assertEquals('FR1420041010050500013M02606', $bankAccount->iban);
        $this->assertEquals('EUR', $bankAccount->currency);
        $this->assertEquals('John Doe', $bankAccount->owner_name);
        $this->assertEquals('CACC', $bankAccount->cash_account_type);
        $this->assertNotNull($bankAccount->end_valid_access);
    }

}
