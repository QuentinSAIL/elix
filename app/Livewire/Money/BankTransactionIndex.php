<?php

namespace App\Livewire\Money;

use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Services\GoCardlessDataService;
use Illuminate\Support\Facades\Storage;

class BankTransactionIndex extends Component
{
    public $user;
    public $accounts;
    public $selectedAccount;

    public $transactions;

    public $onInitialLoad = 30;
    public $increasedLoad = 10;
    public $perPage;
    public $search = '';

    public $sortField = 'transaction_date';
    public $sortDirection = 'desc';

    public function mount()
    {
        $this->user = Auth::user();
        $this->accounts = $this->user->bankAccounts;
        $this->perPage = $this->onInitialLoad;
        $this->getTransactionsProperty();
    }

    public function getTransactions()
    {
        $gocardless = new GoCardlessDataService();

        foreach ($this->accounts as $account) {
            $responses = $account->updateFromGocardless($gocardless);
            foreach ($responses as $response) {
                if (isset($response['status']) && $response['status'] === 'error') {
                    Toaster::error($response['message'])->duration(30000);
                } else {
                    Toaster::success($response['message'])->duration(30000);
                }
            }
        }
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

    public function updatingSearch()
    {
        $this->perPage = $this->onInitialLoad;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[On('transactions-edited')]
    public function refreshTransactions()
    {
        $this->getTransactionsProperty();
    }

    public function getTransactionsProperty()
    {
        if (!$this->selectedAccount) {
            return collect();
        }

        $this->transactions = $this->selectedAccount
            ->transactions()
            ->when(Str::length($this->search), function ($query) {
                $query->whereRaw('LOWER(description) LIKE ?', ['%' . strtolower($this->search) . '%']);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->latest('id')
            ->take($this->perPage)
            ->get();
    }

    public function render()
    {
        $this->getTransactionsProperty();
        return view('livewire.money.bank-transaction-index');
    }
}
