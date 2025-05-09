<?php

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Support\Str;
use Masmerise\Toaster\Toaster;
use App\Models\BankTransactions;
use App\Models\MoneyCategoryMatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
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
        return Cache::remember("gocardless_account_transactions_{$accountId}", 3600 * 20, function () use ($accountId) {
            $res = Http::withToken($this->accessToken())
                ->get("{$this->baseUrl}/accounts/{$accountId}/transactions/")
                ->json();

            return $res;
        });
    }

    public function getAccountBalances(string $accountId)
    {
        return Cache::remember("gocardless_account_balances_{$accountId}", 3600 * 20, function () use ($accountId) {
            $res = Http::withToken($this->accessToken())
                ->get("{$this->baseUrl}/accounts/{$accountId}/balances/")
                ->json();

            return $res;
        });
    }

    public function getAccountDetails($accountId)
    {
        return Cache::remember("gocardless_account_details_{$accountId}", 3600 * 20, function () use ($accountId) {
            $res = Http::withToken($this->accessToken())
                ->get("{$this->baseUrl}/accounts/{$accountId}/details/")
                ->json();

            return $res;
        });
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

            $balance = $balances['balances'][0]['balanceAmount']['amount'] ?? 0;
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

    public function getBanks($country = 'fr')
    {
        return Cache::remember('gocardless_banks', 3600*12, function () use ($country) {
            $res = Http::withToken($this->accessToken())
                ->get("{$this->baseUrl}/institutions/?country={$country}")
                ->json();

            return $res;
        });
    }

    public function getAccountsFromRef($ref)
    {
        $response = Http::withToken($this->accessToken())
            ->get("{$this->baseUrl}/requisitions/?limit=100&offset=0")
            ->json();

        $results = $response['results'];
        foreach ($results as $result) {
            if ($result['reference'] === $ref) {
                return $result['accounts'];
            }
        }
        return;
    }

    public function deleteRequisitionFromRef($ref)
    {
        $response = Http::withToken($this->accessToken())
            ->delete("{$this->baseUrl}/requisitions/{$ref}/")
            ->json();
        return $response;
    }

    public function addNewBankAccount($institutionId, $maxHistoricalDays, $accessValidForDays)
    {
        $response = Http::withToken($this->accessToken())->post("{$this->baseUrl}/agreements/enduser/", [
            'institution_id' => $institutionId,
            'max_historical_days' => $maxHistoricalDays,
            'access_valid_for_days' => $accessValidForDays,
            'access_scope' => ['balances', 'details', 'transactions'],
        ]);

        if ($response['created']) {
            $this->requisition($institutionId, $response['id'], now()->addDays($response['access_valid_for_days']), $response['max_historical_days']);
        } else {
            exit('Error creating user agreement: ' . json_encode($response));
        }
    }

    public function requisition($institutionId, $agreementId, $accesEndDate, $maxHistoricalDays, $country = 'fr')
    {
        $reference = (string) Str::uuid();
        $response = Http::withToken($this->accessToken())->post("{$this->baseUrl}/requisitions/", [
            'redirect' => config('services.url.web') . 'money/accounts',
            'institution_id' => $institutionId,
            'reference' => $reference,
            'agreement' => $agreementId,
            'user_language' => $country,
        ]);

        if ($response['created']) {
            $bankAccount = Auth::user()
                ->bankAccounts()
                ->updateOrCreate(
                    [
                        'gocardless_account_id' => null,
                    ],
                    [
                        'name' => $institutionId,
                        'end_valid_access' => $accesEndDate,
                        'institution_id' => $institutionId,
                        'agreement_id' => $agreementId,
                        'reference' => $reference,
                        'transaction_total_days' => $maxHistoricalDays,
                    ],
                );

            return redirect($response['link']);
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

        if (count($parts) > 1) {
            $last = array_pop($parts);
            return implode(', ', $parts) . ' et ' . $last;
        }

        return $parts[0];
    }
}
