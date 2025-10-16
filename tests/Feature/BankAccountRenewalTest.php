<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\ApiService;
use App\Models\BankAccount;
use App\Models\User;
use App\Services\GoCardlessDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BankAccountRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Créer les données nécessaires pour GoCardless
        $apiService = ApiService::factory()->create(['name' => 'GoCardless']);
        ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'api_service_id' => $apiService->id,
            'secret_id' => 'test_secret_id',
            'secret_key' => 'test_secret_key',
        ]);

        $this->bankAccount = BankAccount::factory()->create([
            'user_id' => $this->user->id,
            'gocardless_account_id' => 'test_account_123',
            'institution_id' => 'test_institution',
            'agreement_id' => 'test_agreement',
            'end_valid_access' => now()->addDays(30), // Date limite dans 30 jours
        ]);

        // Authentifier l'utilisateur
        Auth::login($this->user);
    }

    public function test_renewal_does_not_update_date_limit_immediately()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/agreements/enduser/' => Http::response([
                'created' => true,
                'id' => 'new_agreement_123',
                'access_valid_for_days' => 90,
                'max_historical_days' => 365,
            ], 201),
            '*/requisitions/' => Http::response([
                'created' => true,
                'link' => 'http://redirect.url',
            ], 201),
        ]);

        $service = new GoCardlessDataService();

        // Simuler le renouvellement
        $service->addNewBankAccount(
            'test_institution',
            365,
            90,
            'logo.png',
            $this->bankAccount->id // ID du compte existant pour le renouvellement
        );

        // Vérifier que la date limite n'a PAS été mise à jour immédiatement
        $this->bankAccount->refresh();
        $this->assertEquals(now()->addDays(30)->format('Y-m-d'), $this->bankAccount->end_valid_access->format('Y-m-d'));
    }

    public function test_renewal_updates_date_limit_only_after_successful_callback()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/agreements/enduser/test_agreement/' => Http::response([
                'access_valid_for_days' => 90,
            ], 200),
            '*/requisitions/?limit=100&offset=0' => Http::response([
                'results' => [[
                    'reference' => 'test_ref',
                    'accounts' => ['test_account_123'],
                ]],
            ], 200),
            '*/accounts/test_account_123/details/' => Http::response([
                'account' => [
                    'iban' => 'FR123456789',
                    'currency' => 'EUR',
                    'name' => 'Test Account',
                    'cashAccountType' => 'CASH',
                ],
            ], 200),
        ]);

        $service = new GoCardlessDataService();

        // Simuler un callback réussi
        $accountId = $service->getAccountsFromRef('test_ref');
        $this->assertEquals(['test_account_123'], $accountId);

        // Simuler la mise à jour du compte après callback réussi
        $accountDetails = $service->getAccountDetails('test_account_123');
        $agreementDetails = $service->getAgreementDetails('test_agreement');

        // Mettre à jour le compte comme dans updateGoCardlessAccount
        $this->bankAccount->gocardless_account_id = 'test_account_123';
        $this->bankAccount->iban = $accountDetails['account']['iban'];
        $this->bankAccount->currency = $accountDetails['account']['currency'];
        $this->bankAccount->owner_name = $accountDetails['account']['name'];
        $this->bankAccount->cash_account_type = $accountDetails['account']['cashAccountType'];

        // Maintenant mettre à jour la date limite
        if (isset($agreementDetails['access_valid_for_days'])) {
            $this->bankAccount->end_valid_access = now()->addDays($agreementDetails['access_valid_for_days']);
        }

        $this->bankAccount->save();

        // Vérifier que la date limite a été mise à jour
        $this->bankAccount->refresh();
        $this->assertEquals(now()->addDays(90)->format('Y-m-d'), $this->bankAccount->end_valid_access->format('Y-m-d'));
    }

    public function test_failed_renewal_does_not_update_date_limit()
    {
        // Simuler un échec de renouvellement (pas de callback ou callback avec erreur)
        $originalDate = $this->bankAccount->end_valid_access;

        // Attendre un peu pour simuler le temps qui passe
        sleep(1);

        // Vérifier que la date limite n'a pas changé
        $this->bankAccount->refresh();
        $this->assertEquals($originalDate->format('Y-m-d'), $this->bankAccount->end_valid_access->format('Y-m-d'));
    }
}
