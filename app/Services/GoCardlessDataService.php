<?php

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Support\Str;
use Masmerise\Toaster\Toaster;
use App\Models\BankTransactions;
use App\Models\MoneyCategoryMatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GoCardlessDataService
{
    protected $baseUrl = 'https://bankaccountdata.gocardless.com/api/v2';

    public function accessToken()
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
        $res = Http::withToken($this->accessToken())
            ->get("{$this->baseUrl}/accounts/{$accountId}/transactions/")
            ->json();
        return $res;
    }

    public function getAccountBalances(string $accountId)
    {
        $res = Http::withToken($this->accessToken())
            ->get("{$this->baseUrl}/accounts/{$accountId}/balances/")
            ->json();

        Storage::put('balances_' . Str::uuid() . "_{$accountId}.json", json_encode($res, JSON_PRETTY_PRINT));

        return $res;
    }

    public function updateAccountBalance(string $accountId)
    {
        try {
            $balances = $this->getAccountBalances($accountId);
            $account = BankAccount::where('gocardless_account_id', $accountId)->firstOrFail();

            if (isset($balances['status_code']) && $balances['status_code'] !== 200) {
                if ($balances['status_code'] === 429) {
                    // récupère le nombre de secondes dans le message d'erreur
                    preg_match('/(\d+)\sseconds/', $balances['detail'], $matches);
                    $seconds = isset($matches[1]) ? (int) $matches[1] : 0;

                    $readable = $this->formatDuration($seconds);

                    return ['status' => 'error', 'message' => __('Rate limit exceeded while fetching balances for account "' . $account->name . '". ' . "Please wait {$readable}.")];
                } else {
                    return ['status' => 'error', 'message' => __('Error fetching balances for account "' . $account->name . '": ' . json_encode($balances))];
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

        return ['status' => 'success', 'message' => __('Account balance for "' . $account->name . '" updated successfully.')];
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

                    return ['status' => 'error', 'message' => __('Rate limit exceeded while fetching transactions for account "' . $account->name . '". ' . "Please wait {$readable}.")];
                } else {
                    return ['status' => 'error', 'message' => __('Error fetching transactions for account "' . $account->name . '": ' . json_encode($transactions))];
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
                $transaction = BankTransactions::where('gocardless_transaction_id', $transaction['internalTransactionId'])->firstOrCreate([
                    'bank_account_id' => $account->id,
                    'gocardless_transaction_id' => (string) $transaction['internalTransactionId'],
                    'amount' => $transaction['transactionAmount']['amount'],
                    'description' => isset($transaction['remittanceInformationUnstructuredArray'][0]) ? $transaction['remittanceInformationUnstructuredArray'][0] : $transaction['creditorName'] ?? $transaction['debtorName'],
                    'transaction_date' => $transaction['bookingDate'],
                ]);
                MoneyCategoryMatch::checkAndApplyCategory($transaction);
            } catch (\Exception $e) {
                Log::error('Error creating transaction: ' . $e->getMessage() . ' for account: ' . $account . ' on transaction: ' . json_encode($transaction));
            }
        }
        return ['status' => 'success', 'message' => __('Account transactions for "' . $account->name . '" updated successfully.')];
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

        if (count($parts) > 1) {
            $last = array_pop($parts);
            return implode(', ', $parts) . ' et ' . $last;
        }

        return $parts[0];
    }
}
