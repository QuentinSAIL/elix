<?php

namespace App\Livewire\Money;

use Flux\Flux;
use Livewire\Component;
use Illuminate\Support\Str;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;
use App\Services\GoCardlessDataService;

class BankAccountIndex extends Component
{
    public $user;
    public $accounts;

    public function mount()
    {
        $this->user = Auth::user();
        $this->accounts = $this->user->bankAccounts;
    }

    public function addNewAccount(GoCardlessDataService $gocardless)
    {
        //
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
            // $account->delete();
            Flux::modals()->close('delete-account-' . $account->id);
            Toaster::success(__('Bank account deleted successfully.'));
        } else {
            Toaster::error(__('Bank account not found.'));
        }
    }

    public function render()
    {
        return view('livewire.money.bank-account-index');
    }
}
