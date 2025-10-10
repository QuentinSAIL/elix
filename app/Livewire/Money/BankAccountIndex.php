<?php

namespace App\Livewire\Money;

use App\Services\GoCardlessDataService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class BankAccountIndex extends Component
{
    public \App\Models\User $user;

    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\BankAccount> */
    public \Illuminate\Database\Eloquent\Collection $accounts;

    protected \App\Services\GoCardlessDataService $goCardlessDataService;

    public ?string $ref = null;

    public ?string $error = null;

    /** @var array<string> */
    protected array $queryString = ['ref', 'error'];

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->accounts = (new \Illuminate\Database\Eloquent\Collection($this->user->bankAccounts->all()));
        $this->updateGoCardlessAccount();
    }

    public function updateAccountName(string|int $accountId, string $name): void
    {
        /** @var \App\Models\BankAccount|null $account */
        $account = $this->accounts->find($accountId);
        if ($account) {
            $account->update(['name' => $name]);
            Toaster::success('Compte bancaire mis à jour avec succès.');
        } else {
            Toaster::error('Compte bancaire introuvable.');
        }
    }

    public function delete(string|int $accountId): void
    {
        /** @var \App\Models\BankAccount|null $account */
        $account = $this->user->bankAccounts()->find($accountId);

        if ($account) {
            if ($account->gocardless_account_id) {
                $goCardlessDataService = new GoCardlessDataService;
                $goCardlessDataService->deleteRequisitionFromRef($account->reference);
            }
            $account->delete();
            $this->accounts = (new \Illuminate\Database\Eloquent\Collection($this->user->bankAccounts->all()));
            Flux::modals()->close('delete-account-'.$account->id);
            Toaster::success('Compte bancaire supprimé avec succès.');
        } else {
            Toaster::error('Compte bancaire introuvable.');
        }
    }

    public function updateGoCardlessAccount(): void
    {
        if ($this->ref && ! $this->error) {
            $goCardlessDataService = new GoCardlessDataService;

            $accountId = $goCardlessDataService->getAccountsFromRef($this->ref);

            if (isset($accountId[0])) {
                $accountId = $accountId[0];
            }

            /** @var \App\Models\BankAccount|null $bankAccount */
            $bankAccount = $this->user->bankAccounts()->firstWhere('gocardless_account_id', $accountId);

            $accountDetails = $goCardlessDataService->getAccountDetails($accountId);
            if (isset($accountDetails['status_code']) && $accountDetails['status_code'] !== 200) {
                Toaster::error('Error fetching account details from GoCardless.');

                return;
            }
            /** @var \App\Models\BankAccount $bankAccount */
            $bankAccount = $this->user
                ->bankAccounts()
                ->whereNull('gocardless_account_id')
                ->orWhere('gocardless_account_id', $accountId)
                ->firstOrFail();

            $bankAccount->gocardless_account_id = $accountId;
            $bankAccount->iban = $accountDetails['account']['iban'];
            $bankAccount->currency = $accountDetails['account']['currency'];
            $bankAccount->owner_name = $accountDetails['account']['name'] ?? $accountDetails['account']['ownerName'];
            $bankAccount->cash_account_type = $accountDetails['account']['cashAccountType'];
            // 'logo' =>
            $bankAccount->save();

            // return $bankAccount;
        }
    }

    public function needsRenewal(\App\Models\BankAccount $account, int $weeksThreshold = 8): bool
    {
        if (! $account->end_valid_access) {
            return false;
        }

        $endDate = \Carbon\Carbon::parse($account->end_valid_access);
        $weeksRemaining = now()->diffInWeeks($endDate, false);

        return $weeksRemaining <= $weeksThreshold && $weeksRemaining >= 0;
    }

    public function renewAuthorization(string|int $accountId): void
    {
        /** @var \App\Models\BankAccount|null $account */
        $account = $this->accounts->find($accountId);

        if (! $account) {
            Toaster::error('Compte bancaire introuvable.');

            return;
        }

        if (! $account->gocardless_account_id || ! $account->institution_id || ! $account->agreement_id) {
            Toaster::error('Impossible de renouveler l\'autorisation pour ce compte.');

            return;
        }

        try {
            $goCardlessDataService = new GoCardlessDataService;

            // Récupérer les informations de la banque pour obtenir le max_access_valid_for_days
            $banks = $goCardlessDataService->getBanks();
            $bank = collect($banks)->firstWhere('id', $account->institution_id);

            if (! $bank) {
                Toaster::error('Impossible de récupérer les informations de la banque.');

                return;
            }

            $maxAccessValidForDays = $bank['max_access_valid_for_days'] ?? 90; // Fallback à 90 jours si non trouvé

            // Créer un nouvel accord avec la même institution
            $response = $goCardlessDataService->addNewBankAccount(
                $account->institution_id,
                $account->transaction_total_days,
                $maxAccessValidForDays,
                $account->logo
            );

            Toaster::success('Redirection vers votre banque pour renouveler l\'autorisation.');
        } catch (\Exception $e) {
            Toaster::error('Erreur lors du renouvellement de l\'autorisation: '.$e->getMessage());
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.money.bank-account-index');
    }
}
