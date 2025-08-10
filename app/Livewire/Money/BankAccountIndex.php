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
            Toaster::success(__('Bank account updated successfully.'));
        } else {
            Toaster::error(__('Bank account not found.'));
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
            Toaster::success(__('Bank account deleted successfully.'));
        } else {
            Toaster::error(__('Bank account not found.'));
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
                Toaster::error(__('Error fetching account details from GoCardless.'));

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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.money.bank-account-index');
    }
}
