<?php

namespace App\Services;

use App\Models\BankAccount;
use Masmerise\Toaster\Toaster;
use App\Models\BankTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

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
        return Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/accounts/{$accountId}/transactions/")
            ->json();
    }

    public function getAccountBalances(string $accountId)
    {
        return Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/accounts/{$accountId}/balances/")
            ->json();
    }

    public function updateAccountBalance(string $accountId)
    {
        try {
            $balances = $this->getAccountBalances($accountId);

            if (isset($balances['status_code']) && $balances['status_code'] !== 200) {
                if ($balances['status_code'] === 429) {
                    // on récupère le nombre de secondes dans le message d'erreur
                    preg_match('/(\d+)\sseconds/', $balances['detail'], $matches);
                    $seconds = isset($matches[1]) ? (int) $matches[1] : 0;

                    $readable = $this->formatDuration($seconds);

                    throw new \Exception('Rate limit exceeded while fetching balances. ' . "Please wait {$readable}.");
                } else {
                    throw new \Exception('Error fetching balances: ' . json_encode($balances));
                }
            }

            $balance = $balances['balances']['current']['amount'] ?? 0;
        } catch (\Exception $e) {
            Log::error('Error fetching account balances: ' . $e->getMessage() . ' for account ID: ' . $accountId);
            throw $e;
        }

        $account = BankAccount::where('gocardless_account_id', $accountId)->first();

        if ($account) {
            $account->balance = $balance;
            $account->save();
        }
    }

    public function updateAccountTransactions(string $accountId)
    {
        $account = BankAccount::where('gocardless_account_id', $accountId)->firstOrFail();

        try {
            $transactions = $this->getAccountTransactions($accountId);

            if (isset($transactions['status_code']) && $transactions['status_code'] !== 200) {
                if ($transactions['status_code'] === 429) {
                    // on récupère le nombre de secondes dans le message d'erreur
                    preg_match('/(\d+)\sseconds/', $transactions['detail'], $matches);
                    $seconds = isset($matches[1]) ? (int) $matches[1] : 0;

                    $readable = $this->formatDuration($seconds);

                    throw new \Exception('Rate limit exceeded while fetching transactions. ' . "Please wait {$readable}.");
                } else {
                    throw new \Exception('Error fetching transactions: ' . json_encode($transactions));
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching account transactions: ' . $e->getMessage() . ' for account ID: ' . $accountId);
            throw $e;
        }

        if (isset($transactions['transactions']['booked']) && count($transactions['transactions']['booked']) > 0) {
            $transactions = $transactions['transactions']['booked'];
        } else {
            return;
        }

        foreach ($transactions as $transaction) {
            try {
                BankTransactions::where('gocardless_transaction_id', $transaction['internalTransactionId'])->firstOrCreate([
                    'bank_account_id' => $account->id,
                    'gocardless_transaction_id' => (string) $transaction['internalTransactionId'],
                    'amount' => $transaction['transactionAmount']['amount'],
                    'description' => isset($transaction['remittanceInformationUnstructuredArray'][0]) ? $transaction['remittanceInformationUnstructuredArray'][0] : $transaction['creditorName'] ?? $transaction['debtorName'],
                    'transaction_date' => $transaction['bookingDate'],
                ]);
            } catch (\Exception $e) {
                Log::error('Error creating transaction: ' . $e->getMessage() . ' for account: ' . $account . ' on transaction: ' . json_encode($transaction));
            }
        }
    }

    public function formatDuration(int $totalSeconds): string
    {
        $units = [
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
            'second' => 1,
        ];

        $parts = [];
        foreach ($units as $name => $divisor) {
            $quotient = intdiv($totalSeconds, $divisor);
            if ($quotient > 0) {
                $parts[] = $quotient . ' ' . $name . ($quotient > 1 ? 's' : '');
                $totalSeconds -= $quotient * $divisor;
            }
        }

        if (empty($parts)) {
            return '0 seconds';
        }

        // On sépare par des virgules, et ajoute "et" avant la dernière unité
        if (count($parts) > 1) {
            $last = array_pop($parts);
            return implode(', ', $parts) . ' et ' . $last;
        }

        return $parts[0];
    }
}
