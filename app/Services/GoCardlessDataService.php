<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoCardlessDataService
{
    protected $baseUrl = 'https://bankaccountdata.gocardless.com/api/v2';
    private $accessToken;

    public function __construct()
    {
        $this->accessToken = Cache::remember('gocardless_access_token', 3000, function () {
            $response = Http::post("{$this->baseUrl}/token/new/", [
                'secret_id' => config('services.gocardless_data.secret_id'),
                'secret_key' => config('services.gocardless_data.secret_key'),
            ]);

            return $response->json('access');
        });
    }

    public function getAccessToken()
    {
        return Cache::remember('gocardless_access_token', 3000, function () {
            $response = Http::post("{$this->baseUrl}/token/new/", [
                'secret_id' => config('services.gocardless_data.secret_id'),
                'secret_key' => config('services.gocardless_data.secret_key'),
            ]);

            return $response->json('access');
        });
    }


    public function getAccountTransactions(string $accountId)
    {
        return Http::withToken($this->accessToken)->get("{$this->baseUrl}/accounts/{$accountId}/transactions/")->json();
    }

    public function getAccountBalances(string $accountId)
    {
        return Http::withToken($this->accessToken)->get("{$this->baseUrl}/accounts/{$accountId}/balances/")->json('balances.0.balanceAmount.amount');
    }

    public function updateAccountBalance(string $accountId)
    {
        $balance = $this->getAccountBalances($accountId);

        $account = BankAccount::where('gocardless_account_id', $accountId)->first();

        if ($account) {
            $account->balance = $balance;
            $account->save();
        }
    }

    public function updateAccountTransactions(string $accountId)
    {
        $account = BankAccount::where('gocardless_account_id', $accountId)->firstOrFail();

        $transactions = $this->getAccountTransactions($accountId);
        dd($transactions);
        if (isset($transactions['transactions']['booked']) && count($transactions['transactions']['booked']) > 0) {
            $transactions = $transactions['transactions']['booked'];
        } else {
            return;
        }

        foreach ($transactions as $transaction) {
            try {
                BankTransactions::where('gocardless_transaction_id', $transaction['internalTransactionId'])->firstOrCreate(
                    [
                        'bank_account_id' => $account->id,
                        'gocardless_transaction_id' => (string)$transaction['internalTransactionId'],
                        'amount' => $transaction['transactionAmount']['amount'],
                        'description' => isset($transaction['remittanceInformationUnstructuredArray'][0])
                            ? $transaction['remittanceInformationUnstructuredArray'][0]
                            : ($transaction['creditorName'] ?? $transaction['debtorName']),
                        'transaction_date' => $transaction['bookingDate'],
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Error creating transaction: ' . $e->getMessage() . ' for account: ' . $account . ' on transaction: ' . json_encode($transaction));
            }
        }
    }
}
