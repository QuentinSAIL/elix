<?php

namespace App\Livewire\Money;

use App\Models\MoneyCategory;
use App\Models\MoneyCategoryMatch;
use App\Services\GoCardlessDataService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;


class BankTransactionIndex extends Component
{
    public \App\Models\User $user;

    /** @var EloquentCollection<int, \App\Models\BankAccount> */
    public EloquentCollection $accounts;

    public ?\App\Models\BankAccount $selectedAccount = null;

    public bool $allAccounts = false;

    public int $perPage = 100;

    public int $onInitialLoad = 100;

    public int $increasedLoad = 50;

    public bool $noMoreToLoad = false;

    public string $search = '';

    public string $sortField = 'transaction_date';

    public string $sortDirection = 'desc';

    public string|int|null $categoryFilter = '';

    public string $dateFilter = 'all';

    /** @var EloquentCollection<array-key, \App\Models\BankTransactions> */
    public EloquentCollection $transactions;

    /** @var EloquentCollection<int, \App\Models\MoneyCategory> */
    public EloquentCollection $categories;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->accounts = $this->user->bankAccounts;
        $this->categories = MoneyCategory::orderBy('name')->get();
        $this->transactions = new EloquentCollection();

        $this->allAccounts = true;
        $this->perPage = $this->onInitialLoad;

        $this->getTransactionsProperty();
    }

    /**
     * Récupère et met à jour les transactions depuis GoCardless
     */
    public function getTransactions(): void
    {
        $gocardless = new GoCardlessDataService;

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
    public function searchAndApplyCategory(string $keyword): void
    {
        $transactionEdited = MoneyCategoryMatch::searchAndApplyMatchCategory($keyword);
        Toaster::success("Catégorie appliquée à toutes les transactions correspondantes ($transactionEdited)");

        $this->getTransactionsProperty();
    }

    public function updateSelectedAccount(string|int $accountId): void
    {
        $this->allAccounts = $accountId === 'all';
        $this->selectedAccount = $this->accounts->find($accountId);
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
    }

    public function loadMore(): void
    {
        $this->perPage += $this->increasedLoad;

        if ($this->perPage > $this->getTransactionQuery()->count()) {
            $this->noMoreToLoad = true;
        }
    }

    /**
     * Réinitialise la pagination lors de l'actualisation de la recherche
     */
    public function updatingSearch(): void
    {
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
    }

    /**
     * Réinitialise la pagination lors du changement de filtre de catégorie
     */
    public function updatingCategoryFilter(): void
    {
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
    }

    /**
     * Réinitialise la pagination lors du changement de filtre de date
     */
    public function updatingDateFilter(): void
    {
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
    }

    /**
     * Gère le tri des colonnes
     */
    public function sortBy(string $field): void
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
    public function refreshTransactions(): void
    {
        $this->getTransactionsProperty();
    }

    /**
     * Retourne la requête de base pour les transactions
     */
    /**
     * @return HasMany<\App\Models\BankTransactions, \App\Models\BankAccount>|HasManyThrough<\App\Models\BankTransactions, \App\Models\BankAccount, \App\Models\User>|SupportCollection<array-key, mixed>
     */
    protected function getTransactionQuery(): HasMany|HasManyThrough|SupportCollection
    {
        if (! $this->selectedAccount && ! $this->allAccounts) {
            return new SupportCollection();
        }

        if ($this->allAccounts) {
            /** @var HasManyThrough<\App\Models\BankTransactions, \App\Models\BankAccount, \App\Models\User> $query */
            $query = $this->user->bankTransactions();
        } else {
            /** @var HasMany<\App\Models\BankTransactions, \App\Models\BankAccount> $query */
            $query = $this->selectedAccount->transactions();
        }

        if (Str::length($this->search) > 0) {
            $query->whereRaw('LOWER(description) LIKE ?', ['%'.strtolower($this->search).'%']);
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

    public function getTransactionsProperty(): void
    {
        $query = $this->getTransactionQuery();

        if ($query instanceof SupportCollection) {
            $this->transactions = new EloquentCollection(
                collect($query->all())->map(function ($item) {
                    return $item instanceof \App\Models\BankTransactions
                        ? $item
                        : new \App\Models\BankTransactions((array) $item);
                })
            );

            return;
        }

        $this->transactions = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->take($this->perPage)
            ->get()
            ->values();

        }

    public function render(): \Illuminate\Contracts\View\View
    {
        $this->getTransactionsProperty();

        return view('livewire.money.bank-transaction-index');
    }
}
