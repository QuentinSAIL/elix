<?php

namespace App\Livewire\Money;

use Flux\Flux;
use Livewire\Component;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;
use App\Services\GoCardlessDataService;

class BankAccountIndex extends Component
{
    public $user;
    public $accounts;

    public $goCardlessDataService;

    public $ref = null;
    public $error = null;

    protected $queryString = ['ref', 'error'];

    public function mount()
    {
        $this->user = Auth::user();
        $this->accounts = $this->user->bankAccounts;
        $this->updateGoCardlessAccount();
    }

    public function updateAccountName($accountId, $name)
    {
        $account = $this->accounts->find($accountId);
        if ($account) {
            $account->update(['name' => $name]);
            Toaster::success(__('Bank account updated successfully.'));
        } else {
            Toaster::error(__('Bank account not found.'));
        }
    }

    public function delete($accountId)
    {
        $account = $this->user->bankAccounts()->find($accountId);

        if ($account) {
            if ($account->gocardless_account_id) {
                $goCardlessDataService = new GoCardlessDataService();
                $goCardlessDataService->deleteRequisitionFromRef($account->reference);
            }
            $account->delete();
            $this->accounts = $this->user->bankAccounts;
            Flux::modals()->close('delete-account-' . $account->id);
            Toaster::success(__('Bank account deleted successfully.'));
        } else {
            Toaster::error(__('Bank account not found.'));
        }
    }

    public function updateGoCardlessAccount()
    {
        if ($this->ref && !$this->error) {
            $goCardlessDataService = new GoCardlessDataService();

            $accountId = $goCardlessDataService->getAccountsFromRef($this->ref);

            if (isset($accountId[0])) {
                $accountId = $accountId[0];
            }

            $bankAccount = $this->user->bankAccounts()->firstWhere('gocardless_account_id', $accountId);

            $accountDetails = $goCardlessDataService->getAccountDetails($accountId);
            if (isset($accountDetails['status_code']) && $accountDetails['status_code'] !== 200) {
                Toaster::error(__('Error fetching account details from GoCardless.'));
                return;
            }
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

            return $bankAccount;
        }
    }

    public function render()
    {
        return view('livewire.money.bank-account-index');
    }
}
