<?php

namespace App\Livewire\Money;

use App\Http\Livewire\Traits\Notifies;
use App\Services\GoCardlessDataService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BankAccountIndex extends Component
{
    use Notifies;

    public $user;

    public $accounts;

    public $ref = null;

    public $error = null;

    protected $queryString = ['ref', 'error'];

    public function mount(GoCardlessDataService $goCardlessDataService)
    {
        $this->user = Auth::user();
        $this->accounts = $this->user->bankAccounts;
        $this->updateGoCardlessAccount($goCardlessDataService);
    }

    public function updateAccountName($accountId, $name)
    {
        $account = $this->accounts->find($accountId);
        if ($account) {
            $account->update(['name' => $name]);
            $this->notifySuccess(__('Bank account updated successfully.'));
        } else {
            $this->notifyError(__('Bank account not found.'));
        }
    }

    public function delete($accountId, GoCardlessDataService $goCardlessDataService)
    {
        $account = $this->user->bankAccounts()->find($accountId);

        if ($account) {
            if ($account->gocardless_account_id) {
                $goCardlessDataService->deleteRequisitionFromRef($account->reference);
            }
            $account->delete();
            $this->accounts = $this->user->bankAccounts;
            Flux::modals()->close('delete-account-'.$account->id);
            $this->notifySuccess(__('Bank account deleted successfully.'));
        } else {
            $this->notifyError(__('Bank account not found.'));
        }
    }

    public function updateGoCardlessAccount(GoCardlessDataService $goCardlessDataService)
    {
        if ($this->ref && ! $this->error) {

            $accountId = $goCardlessDataService->getAccountsFromRef($this->ref);

            if (isset($accountId[0])) {
                $accountId = $accountId[0];
            }

            $bankAccount = $this->user->bankAccounts()->firstWhere('gocardless_account_id', $accountId);

            $accountDetails = $goCardlessDataService->getAccountDetails($accountId);
            if (isset($accountDetails['status_code']) && $accountDetails['status_code'] !== 200) {
                $this->notifyError(__('Error fetching account details from GoCardless.'));

                return;
            }
            $bankAccount = $this->user
                ->bankAccounts()
                ->whereNull('gocardless_account_id')
                ->orWhere('gocardless_account_id', $accountId)
                ->firstOrFail()->update([
                    'gocardless_account_id' => $accountId,
                    'iban' => $accountDetails['account']['iban'],
                    'currency' => $accountDetails['account']['currency'],
                    'owner_name' => $accountDetails['account']['name'] ?? $accountDetails['account']['ownerName'],
                    'cash_account_type' => $accountDetails['account']['cashAccountType'],
                    // 'logo' =>
                ]);

            return $bankAccount;
        }
    }

    public function render()
    {
        return view('livewire.money.bank-account-index');
    }
}
