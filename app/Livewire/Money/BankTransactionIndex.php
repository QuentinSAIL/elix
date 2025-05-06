<?php

namespace App\Livewire\Money;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class BankTransactionIndex extends Component
{
    public $user;
    public $accounts;
    public $selectedAccount;
    public $onInitialLoad = 40;
    public $increasedLoad = 20;
    public $perPage;

    public function mount()
    {
        $this->user = Auth::user();
        $this->accounts = $this->user->bankAccounts;
    }

    public function updateSelectedAccount($accountId)
    {
        $this->selectedAccount = $this->accounts->find($accountId);
        $this->perPage = $this->onInitialLoad;
    }

    public function loadMore()
    {
        $this->perPage += $this->increasedLoad;
    }

    public function getTransactionsProperty(): Collection
    {
        if (!$this->selectedAccount) {
            return collect();
        }

        return $this->selectedAccount->transactions()->latest()->take($this->perPage)->get();
    }

    public function render()
    {
        return view('livewire.money.bank-transaction-index', [
            'transactions' => $this->transactions,
        ]);
    }
}
