<?php

namespace App\Livewire\Money;

use Livewire\Component;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\GoCardlessDataService;

class BankTransactionIndex extends Component
{
    public $user;
    public $accounts;
    public $selectedAccount;

    public $onInitialLoad = 40;
    public $increasedLoad = 20;
    public $perPage;
    public $search = '';

    public $sortField = 'transaction_date';
    public $sortDirection = 'desc';

    public function mount()
    {
        $this->user     = Auth::user();
        $this->accounts = $this->user->bankAccounts;
        $this->selectedAccount = $this->accounts->first(); //TODO: remove this line
    }

    public function refreshTransaction()
    {
        $gocardless = new GoCardlessDataService();

        foreach ($this->accounts as $account) {
            try {
                $account->updateFromGocardless($gocardless);
                break;
            } catch (\Exception $e) {
                Toaster::error($e->getMessage());
                break;
            }
        }
    }

    public function updateSelectedAccount($accountId)
    {
        $this->selectedAccount = $this->accounts->find($accountId);
        $this->perPage         = $this->onInitialLoad;
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
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getTransactionsProperty(): Collection
    {
        if (! $this->selectedAccount) {
            return collect();
        }

        return $this->selectedAccount
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
        return view('livewire.money.bank-transaction-index', [
            'transactions' => $this->transactions,
        ]);
    }
}
