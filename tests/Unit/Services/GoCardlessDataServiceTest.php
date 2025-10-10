<?php

namespace Tests\Unit\Services;

use App\Models\ApiKey;
use App\Models\ApiService;
use App\Models\BankAccount;
use App\Models\MoneyCategoryMatch;
use App\Models\User;
use App\Services\GoCardlessDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @covers \App\Services\GoCardlessDataService
 */
class GoCardlessDataServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GoCardlessDataService $service;

    protected User $user;

    protected ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GoCardlessDataService;
        $this->user = User::factory()->create();
        Auth::login($this->user);

        $apiService = ApiService::factory()->create(['name' => 'GoCardless']);
        $this->apiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'api_service_id' => $apiService->id,
            'secret_id' => 'test_secret_id',
            'secret_key' => 'test_secret_key',
        ]);

        Cache::clear();
        Log::swap($this->createMock(\Psr\Log\LoggerInterface::class));
    }

    #[test]
    public function access_token_returns_cached_token_if_available()
    {
        Cache::put('gocardless_access_token', 'cached_token', 3000);

        $token = $this->service->accessToken();

        $this->assertEquals('cached_token', $token);
        Http::assertNothingSent();
    }

    #[test]
    public function access_token_fetches_and_caches_token_if_not_available()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'new_token'], 200),
        ]);

        $token = $this->service->accessToken();

        $this->assertEquals('new_token', $token);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://bankaccountdata.gocardless.com/api/v2/token/new/' &&
                   $request['secret_id'] === 'test_secret_id';
        });
        $this->assertTrue(Cache::has('gocardless_access_token'));
    }

    #[test]
    public function access_token_fetches_token_directly_if_with_cache_is_false()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'direct_token'], 200),
        ]);

        $token = $this->service->accessToken(false);

        $this->assertEquals('direct_token', $token);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://bankaccountdata.gocardless.com/api/v2/token/new/';
        });
        $this->assertFalse(Cache::has('gocardless_access_token'));
    }

    #[test]
    public function access_token_throws_exception_if_api_keys_not_found()
    {
        $this->apiKey->delete(); // Remove API key

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GoCardless API keys not found');

        $this->service->accessToken();
    }

    #[test]
    public function get_account_transactions_returns_cached_transactions()
    {
        Cache::put('gocardless_account_transactions_acc123', ['transactions' => ['booked' => ['tx1']]], 3600 * 20);

        $transactions = $this->service->getAccountTransactions('acc123');

        $this->assertEquals(['transactions' => ['booked' => ['tx1']]], $transactions);
        Http::assertNothingSent();
    }

    #[test]
    public function get_account_transactions_fetches_and_caches_transactions()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/accounts/acc123/transactions/' => Http::response(['transactions' => ['booked' => ['tx2']]], 200),
        ]);

        $transactions = $this->service->getAccountTransactions('acc123');

        $this->assertEquals(['transactions' => ['booked' => ['tx2']]], $transactions);
        $this->assertTrue(Cache::has('gocardless_account_transactions_acc123'));
    }

    #[test]
    public function get_account_balances_returns_cached_balances()
    {
        Cache::put('gocardless_account_balances_acc123', ['balances' => [['balanceAmount' => ['amount' => 100.0]]]], 3600 * 20);

        $balances = $this->service->getAccountBalances('acc123');

        $this->assertEquals(['balances' => [['balanceAmount' => ['amount' => 100.0]]]], $balances);
        Http::assertNothingSent();
    }

    #[test]
    public function get_account_balances_fetches_and_caches_balances()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/accounts/acc123/balances/' => Http::response(['balances' => [['balanceAmount' => ['amount' => 200.0]]]], 200),
        ]);

        $balances = $this->service->getAccountBalances('acc123');

        $this->assertEquals(['balances' => [['balanceAmount' => ['amount' => 200.0]]]], $balances);
        $this->assertTrue(Cache::has('gocardless_account_balances_acc123'));
    }

    #[test]
    public function get_account_details_returns_cached_details()
    {
        Cache::put('gocardless_account_details_acc123', ['details' => 'some_details'], 3600 * 20);

        $details = $this->service->getAccountDetails('acc123');

        $this->assertEquals(['details' => 'some_details'], $details);
        Http::assertNothingSent();
    }

    #[test]
    public function get_account_details_fetches_and_caches_details()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/accounts/acc123/details/' => Http::response(['details' => 'new_details'], 200),
        ]);

        $details = $this->service->getAccountDetails('acc123');

        $this->assertEquals(['details' => 'new_details'], $details);
        $this->assertTrue(Cache::has('gocardless_account_details_acc123'));
    }

    #[test]
    public function update_account_balance_updates_balance_on_success()
    {
        $bankAccount = BankAccount::factory()->create(['user_id' => $this->user->id, 'gocardless_account_id' => 'acc123', 'balance' => 0]);

        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/accounts/acc123/balances/' => Http::response(['balances' => [['balanceAmount' => ['amount' => 123.45]]]], 200),
        ]);

        $result = $this->service->updateAccountBalance('acc123');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(123.45, $bankAccount->fresh()->balance);
    }

    #[test]
    public function update_account_balance_handles_rate_limiting()
    {
        $bankAccount = BankAccount::factory()->create(['user_id' => $this->user->id, 'gocardless_account_id' => 'acc123']);

        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/accounts/acc123/balances/' => Http::response(['status_code' => 429, 'detail' => 'Rate limit exceeded. Try again in 60 seconds.'], 429),
        ]);

        $result = $this->service->updateAccountBalance('acc123');

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Rate limit exceeded', $result['message']);
        $this->assertStringContainsString('Please wait 1 minute.', $result['message']);
    }

    #[test]
    public function update_account_balance_handles_other_api_errors()
    {
        $bankAccount = BankAccount::factory()->create(['user_id' => $this->user->id, 'gocardless_account_id' => 'acc123']);

        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/accounts/acc123/balances/' => Http::response(['status_code' => 500, 'detail' => 'Internal Server Error'], 500),
        ]);

        $result = $this->service->updateAccountBalance('acc123');

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Error fetching balances', $result['message']);
    }

    #[test]
    public function update_account_transactions_creates_new_transactions()
    {
        $bankAccount = BankAccount::factory()->create(['user_id' => $this->user->id, 'gocardless_account_id' => 'acc123']);

        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/accounts/acc123/transactions/' => Http::response([
                'transactions' => [
                    'booked' => [
                        [
                            'internalTransactionId' => 'tx_new',
                            'transactionAmount' => ['amount' => -50.0, 'currency' => 'EUR'],
                            'remittanceInformationUnstructuredArray' => ['Groceries'],
                            'bookingDate' => '2024-01-01',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->updateAccountTransactions('acc123');

        $this->assertEquals('success', $result['status']);
        $this->assertDatabaseHas('bank_transactions', [
            'gocardless_transaction_id' => 'tx_new',
            'amount' => -50.0,
            'description' => 'Groceries',
        ]);
    }

    #[test]
    public function update_account_transactions_applies_money_category_match()
    {
        $bankAccount = BankAccount::factory()->create(['user_id' => $this->user->id, 'gocardless_account_id' => 'acc123']);
        $category = MoneyCategoryMatch::factory()->create(['user_id' => $this->user->id, 'match_string' => 'Groceries'])->category;

        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/accounts/acc123/transactions/' => Http::response([
                'transactions' => [
                    'booked' => [
                        [
                            'internalTransactionId' => 'tx_match',
                            'transactionAmount' => ['amount' => -50.0, 'currency' => 'EUR'],
                            'remittanceInformationUnstructuredArray' => ['Groceries'],
                            'bookingDate' => '2024-01-01',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->updateAccountTransactions('acc123');

        $this->assertEquals('success', $result['status']);
        $this->assertDatabaseHas('bank_transactions', [
            'gocardless_transaction_id' => 'tx_match',
            'money_category_id' => $category->id,
        ]);
    }

    #[test]
    public function update_account_transactions_handles_rate_limiting()
    {
        $bankAccount = BankAccount::factory()->create(['user_id' => $this->user->id, 'gocardless_account_id' => 'acc123']);

        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/accounts/acc123/transactions/' => Http::response(['status_code' => 429, 'detail' => 'Rate limit exceeded. Try again in 120 seconds.'], 429),
        ]);

        $result = $this->service->updateAccountTransactions('acc123');

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Rate limit exceeded', $result['message']);
        $this->assertStringContainsString('Please wait 2 minutes.', $result['message']);
    }

    #[test]
    public function update_account_transactions_handles_no_new_transactions()
    {
        $bankAccount = BankAccount::factory()->create(['user_id' => $this->user->id, 'gocardless_account_id' => 'acc123']);

        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/accounts/acc123/transactions/' => Http::response(['transactions' => ['booked' => []]], 200),
        ]);

        $result = $this->service->updateAccountTransactions('acc123');

        $this->assertEquals('success', $result['status']);
        $this->assertStringContainsString('No new transactions', $result['message']);
    }

    #[test]
    public function get_banks_returns_cached_banks()
    {
        Cache::put('gocardless_banks', ['bank1', 'bank2'], 1);

        $banks = $this->service->getBanks();

        $this->assertEquals(['bank1', 'bank2'], $banks);
        Http::assertNothingSent();
    }

    #[test]
    public function get_banks_fetches_and_caches_banks()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/institutions/?country=fr' => Http::response(['bank3', 'bank4'], 200),
        ]);

        $banks = $this->service->getBanks('fr');

        $this->assertEquals(['bank3', 'bank4'], $banks);
        $this->assertTrue(Cache::has('gocardless_banks'));
    }

    #[test]
    public function get_accounts_from_ref_returns_accounts_for_matching_ref()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/requisitions/?limit=100&offset=0' => Http::response([
                'results' => [
                    ['id' => 'req1', 'reference' => 'ref1', 'accounts' => ['accA', 'accB']],
                    ['id' => 'req2', 'reference' => 'ref2', 'accounts' => ['accC']],
                ],
            ], 200),
        ]);

        $accounts = $this->service->getAccountsFromRef('ref1');

        $this->assertEquals(['accA', 'accB'], $accounts);
    }

    #[test]
    public function get_accounts_from_ref_returns_null_for_no_matching_ref()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/requisitions/?limit=100&offset=0' => Http::response([
                'results' => [
                    ['id' => 'req1', 'reference' => 'ref1', 'accounts' => ['accA']],
                ],
            ], 200),
        ]);

        $accounts = $this->service->getAccountsFromRef('non_existent_ref');

        $this->assertNull($accounts);
    }

    #[test]
    public function delete_requisition_from_ref_makes_api_call()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/requisitions/req1/' => Http::response([], 204),
        ]);

        $this->service->deleteRequisitionFromRef('req1');

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE' &&
                   $request->url() === 'https://bankaccountdata.gocardless.com/api/v2/requisitions/req1/';
        });
    }

    #[test]
    public function add_new_bank_account_creates_agreement_and_requisition()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/agreements/enduser/' => Http::response([
                'created' => true,
                'id' => 'agreement_id',
                'access_valid_for_days' => 90,
                'max_historical_days' => 365,
            ], 201),
            '*/requisitions/' => Http::response([
                'created' => true,
                'link' => 'http://redirect.url',
            ], 201),
        ]);

        $this->service->addNewBankAccount('inst_id', 365, 90, 'logo_url');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bankaccountdata.gocardless.com/api/v2/agreements/enduser/' &&
                   $request['institution_id'] === 'inst_id';
        });
        Http::assertSent(function ($request) {
            return $request->url() === 'https://bankaccountdata.gocardless.com/api/v2/requisitions/' &&
                   $request['institution_id'] === 'inst_id' &&
                   $request['agreement'] === 'agreement_id';
        });
        $this->assertDatabaseHas('bank_accounts', [
            'user_id' => $this->user->id,
            'institution_id' => 'inst_id',
            'agreement_id' => 'agreement_id',
        ]);
    }

    #[test]
    public function add_new_bank_account_throws_exception_on_agreement_error()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/agreements/enduser/' => Http::response([
                'created' => false,
                'detail' => 'Agreement error',
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error creating user agreement: {"created":false,"detail":"Agreement error"}');

        $this->service->addNewBankAccount('inst_id', 365, 90, 'logo_url');
    }

    #[test]
    public function requisition_creates_bank_account_and_redirects()
    {
        Http::fake([
            '*/token/new/' => Http::response(['access' => 'test_token'], 200),
            '*/requisitions/' => Http::response([
                'created' => true,
                'link' => 'http://redirect.url',
            ], 201),
        ]);

        $result = $this->service->requisition('inst_id', 'agreement_id', now()->addDays(90), 365, 'logo_url');

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('http://redirect.url', $result->getTargetUrl());
        $this->assertDatabaseHas('bank_accounts', [
            'user_id' => $this->user->id,
            'institution_id' => 'inst_id',
            'agreement_id' => 'agreement_id',
        ]);
    }

    #[test]
    public function handle_callback_redirects_with_ref_and_error()
    {
        $request = request();
        $request->query->set('ref', 'test_ref');
        $request->query->set('error', 'test_error');

        $response = $this->service->handleCallback();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(route('money.accounts', ['ref' => 'test_ref', 'error' => 'test_error']), $response->getTargetUrl());
    }

    #[test]
    public function format_duration_formats_seconds_correctly()
    {
        $this->assertEquals('1 second', $this->service->formatDuration(1));
        $this->assertEquals('10 seconds', $this->service->formatDuration(10));
    }

    #[test]
    public function format_duration_formats_minutes_correctly()
    {
        $this->assertEquals('1 minute', $this->service->formatDuration(60));
        $this->assertEquals('2 minutes', $this->service->formatDuration(120));
    }

    #[test]
    public function format_duration_formats_hours_correctly()
    {
        $this->assertEquals('1 hour', $this->service->formatDuration(3600));
        $this->assertEquals('2 hours', $this->service->formatDuration(7200));
    }

    #[test]
    public function format_duration_formats_days_correctly()
    {
        $this->assertEquals('1 day', $this->service->formatDuration(86400));
        $this->assertEquals('2 days', $this->service->formatDuration(172800));
    }

    #[test]
    public function format_duration_formats_multiple_units_correctly()
    {
        $this->assertEquals('1 day, 2 hours, 3 minutes et 4 seconds', $this->service->formatDuration(93784));
        $this->assertEquals('1 hour et 30 minutes', $this->service->formatDuration(5400));
    }

    #[test]
    public function format_duration_handles_zero_seconds()
    {
        $this->assertEquals('0 seconds', $this->service->formatDuration(0));
    }
}
