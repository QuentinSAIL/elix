<?php

namespace App\Livewire\Money;

use Livewire\Component;
use App\Models\BankAccount;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Models\MoneyCategory;
use Masmerise\Toaster\Toaster;
use App\Models\MoneyCategoryMatch;
use Illuminate\Support\Facades\Auth;
use App\Services\GoCardlessDataService;

class BankTransactionIndex extends Component
{
    public $user;
    public $accounts;
    public $selectedAccount = null;
    public $allAccounts = false;

    public $perPage = 100;
    public $onInitialLoad = 100;
    public $increasedLoad = 50;
    public $noMoreToLoad = false;

    public $search = '';
    public $sortField = 'transaction_date';
    public $sortDirection = 'desc';
    public $categoryFilter = '';
    public $dateFilter = 'all';

    public $transactions = [];

    public $categories = [];

    public function mount()
    {
        $this->user = Auth::user();
        $this->accounts = $this->user->bankAccounts;
        $this->categories = MoneyCategory::orderBy('name')->get();

        $this->allAccounts = true;
        $this->perPage = $this->onInitialLoad;

        $this->getTransactionsProperty();
    }

    /**
     * Récupère et met à jour les transactions depuis GoCardless
     */
    public function getTransactions()
    {
        $gocardless = new GoCardlessDataService();

        foreach ($this->accounts as $account) {
            $responses = $account->updateFromGocardless($gocardless);

            if ($responses) {
                foreach ($responses as $response) {
                    if (isset($response['status']) && $response['status'] === 'error') {
                        Toaster::error($response['message'])->duration(30000);
                    } else {
                        Toaster::success($response['message'])->duration(30000);
                    }
                }
            }
        }

        $this->getTransactionsProperty();
    }

    #[On('update-category-match')]
    public function searchAndApplyCategory($keyword)
    {
        $transactionEdited = MoneyCategoryMatch::searchAndApplyMatchCategory($keyword);
        Toaster::success("Catégorie appliquée à toutes les transactions correspondantes ($transactionEdited)");

        $this->getTransactionsProperty();
    }

    public function updateSelectedAccount($accountId)
    {
        $this->allAccounts = $accountId === 'all';
        $this->selectedAccount = $this->accounts->find($accountId);
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
    }

    public function loadMore()
    {
        $this->perPage += $this->increasedLoad;

        if ($this->perPage > $this->getTransactionQuery()->count()) {
            $this->noMoreToLoad = true;
        }
    }

    /**
     * Réinitialise la pagination lors de l'actualisation de la recherche
     */
    public function updatingSearch()
    {
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
    }

    /**
     * Réinitialise la pagination lors du changement de filtre de catégorie
     */
    public function updatingCategoryFilter()
    {
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
    }

    /**
     * Réinitialise la pagination lors du changement de filtre de date
     */
    public function updatingDateFilter()
    {
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
    }

    /**
     * Gère le tri des colonnes
     */
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        // Actualiser les transactions après le changement de tri
        $this->getTransactionsProperty();
    }

    /**
     * Rafraîchit les transactions après une édition externe
     */
    #[On('transactions-edited')]
    public function refreshTransactions()
    {
        $this->getTransactionsProperty();
    }

    /**
     * Retourne la requête de base pour les transactions
     */
    protected function getTransactionQuery()
    {
        if (!$this->selectedAccount && !$this->allAccounts) {
            return collect();
        }

        if ($this->allAccounts) {
            $query = $this->user->bankTransactions();
        } else {
            $query = $this->selectedAccount->transactions();
        }

        if (Str::length($this->search) > 0) {
            $query->whereRaw('LOWER(description) LIKE ?', ['%' . strtolower($this->search) . '%']);
        }

        if ($this->categoryFilter) {
            $query->where('money_category_id', $this->categoryFilter);
        }

        switch ($this->dateFilter) {
            case 'current_month':
                $query->whereMonth('transaction_date', now()->month)
                      ->whereYear('transaction_date', now()->year);
                break;
            case 'last_month':
                $query->whereMonth('transaction_date', now()->subMonth()->month)
                      ->whereYear('transaction_date', now()->subMonth()->year);
                break;
            case 'current_year':
                $query->whereYear('transaction_date', now()->year);
                break;
        }

        return $query;
    }

    public function getTransactionsProperty()
    {
        $query = $this->getTransactionQuery();

        if ($query instanceof \Illuminate\Database\Eloquent\Collection) {
            $this->transactions = collect();
            return;
        }

        $this->transactions = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->take($this->perPage)
            ->get();
    }

    public function render()
    {
        $this->getTransactionsProperty();
        return view('livewire.money.bank-transaction-index');
    }
}
