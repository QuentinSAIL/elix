<?php

namespace App\Livewire\Money;

use App\Models\MoneyCategory;
use App\Models\MoneyCategoryMatch;
use App\Services\GoCardlessDataService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class BankTransactionIndex extends Component
{
    public \App\Models\User $user;

    /** @var EloquentCollection<int, \App\Models\BankAccount> */
    public EloquentCollection $accounts;

    public ?\App\Models\BankAccount $selectedAccount = null;

    public bool $allAccounts = false;

    public int $perPage = 100;

    public int $onInitialLoad = 50;

    public int $increasedLoad = 10;

    public bool $noMoreToLoad = false;

    public string $search = '';

    public string $sortField = 'transaction_date';

    public string $sortDirection = 'desc';

    public string|int|null $categoryFilter = '';

    public string $dateFilter = 'all';

    /** @var EloquentCollection<int, \App\Models\BankTransactions> */
    public EloquentCollection $transactions;

    /** @var EloquentCollection<int, \App\Models\MoneyCategory> */
    public EloquentCollection $categories;

    /** Champs autorisés pour le tri (sécurité + perf/index) */
    protected array $allowedSorts = [
        'transaction_date',
        'amount',
        'description',
        'money_category_id',
        'created_at',
        'id',
    ];

    public function mount(): void
    {
        $this->user = Auth::user();

        if (! isset($this->accounts)) {
            $this->accounts = $this->user->bankAccounts;
        }

        $this->categories = MoneyCategory::orderBy('name')->get();
        $this->transactions = new EloquentCollection;

        $this->allAccounts = true;
        $this->perPage = $this->onInitialLoad;

        $this->getTransactionsProperty();
    }

    /**
     * Récupère et met à jour les transactions depuis GoCardless
     */
    public function getTransactions(): void
    {
        $gocardless = app(GoCardlessDataService::class);

        foreach ($this->accounts as $account) {
            $responses = $account->updateFromGocardless($gocardless);

            if ($responses) {
                foreach ($responses as $response) {
                    if (isset($response['status']) && $response['status'] === 'error') {
                        Toaster::error($response['message']);
                    } else {
                        Toaster::success($response['message']);
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
        $this->allAccounts = ($accountId === 'all');
        $this->selectedAccount = $this->allAccounts ? null : $this->accounts->find($accountId);

        $this->resetPagination();
        $this->getTransactionsProperty();
    }

    public function loadMore(): void
    {
        $this->perPage += $this->increasedLoad;

        // On recharge la liste ; le flag noMoreToLoad sera calibré en fonction
        // du "perPage + 1" dans getTransactionsProperty()
        $this->getTransactionsProperty();
    }

    /** Réinitialise la pagination lors de l'actualisation de la recherche */
    public function updatingSearch(): void
    {
        $this->resetPagination();
        $this->getTransactionsProperty();
    }

    /** Réinitialise la pagination lors du changement de filtre de catégorie */
    public function updatingCategoryFilter(): void
    {
        $this->resetPagination();
        $this->getTransactionsProperty();
    }

    /** Réinitialise la pagination lors du changement de filtre de date */
    public function updatingDateFilter(): void
    {
        $this->resetPagination();
        $this->getTransactionsProperty();
    }

    /**
     * Gère le tri des colonnes (avec whitelisting)
     */
    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->allowedSorts, true)) {
            // Sécurité : si colonne non autorisée, on ignore
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPagination();
        $this->getTransactionsProperty();
    }

    /**
     * Rafraîchit les transactions après une édition externe
     */
    #[On('transactions-edited')]
    public function refreshTransactions(): void
    {
        $this->resetPagination();
        $this->getTransactionsProperty();
    }

    /**
     * Retourne la requête de base pour les transactions
     *
     * @return HasMany<\App\Models\BankTransactions, \App\Models\BankAccount>
     *         | HasManyThrough<\App\Models\BankTransactions, \App\Models\BankAccount, \App\Models\User>
     *         | SupportCollection<array-key, mixed>
     */
    protected function getTransactionQuery(): HasMany|HasManyThrough|SupportCollection
    {
        if (! $this->selectedAccount && ! $this->allAccounts) {
            return new SupportCollection;
        }

        if ($this->allAccounts) {
            /** @var HasManyThrough<\App\Models\BankTransactions, \App\Models\BankAccount, \App\Models\User> $query */
            $query = $this->user->bankTransactions();
        } else {
            /** @var HasMany<\App\Models\BankTransactions, \App\Models\BankAccount> $query */
            $query = $this->selectedAccount->transactions();
        }

        // Recherche full-text simple (case-insensitive) optimisée
        $search = trim(Str::lower($this->search));
        if ($search !== '') {
            // Garde la forme actuelle (compatible tous SGBD) tout en normalisant une fois
            $query->whereRaw('LOWER(description) LIKE ?', ['%'.$search.'%']);
        }

        // Filtre de catégorie : attention aux valeurs "0"/0
        if ($this->categoryFilter !== '' && $this->categoryFilter !== null) {
            $query->where('money_category_id', $this->categoryFilter);
        }

        // Filtre de date : passer sur des bornes (meilleure utilisation des index)
        $this->applyDateWindow($query);

        return $query;
    }

    /**
     * Applique un intervalle de dates index-friendly au lieu de whereMonth/whereYear
     *
     * @param  HasMany|\Illuminate\Database\Eloquent\Relations\HasManyThrough  $query
     */
    protected function applyDateWindow($query): void
    {
        $now = now();

        switch ($this->dateFilter) {
            case 'current_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $query->whereBetween('transaction_date', [$start, $end]);
                break;

            case 'last_month':
                $start = $now->copy()->subMonthNoOverflow()->startOfMonth();
                $end = $now->copy()->subMonthNoOverflow()->endOfMonth();
                $query->whereBetween('transaction_date', [$start, $end]);
                break;

            case 'current_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                $query->whereBetween('transaction_date', [$start, $end]);
                break;

            // 'all' => pas de filtre
        }
    }

    /**
     * Recharge $this->transactions en mode "perPage + 1" pour détecter s'il reste des éléments
     * et règle $this->noMoreToLoad sans faire de COUNT(*)
     */
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
            // Si on est sur une collection, on n'a pas la notion de "reste"
            $this->noMoreToLoad = true;

            return;
        }

        // Tri sécurisé (si jamais sortField a été forcé entre-temps)
        if (! in_array($this->sortField, $this->allowedSorts, true)) {
            $this->sortField = 'transaction_date';
            $this->sortDirection = 'desc';
        }

        // On récupère perPage + 1 en BDD pour savoir s'il reste des résultats
        $rows = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->take($this->perPage + 1)
            ->get();

        $this->noMoreToLoad = $rows->count() <= $this->perPage;

        // On tronque à perPage pour l'affichage
        $this->transactions = new EloquentCollection($rows->take($this->perPage)->values());
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        // On s'assure d'avoir les données à jour (cohérent avec le flux Livewire actuel)
        $this->getTransactionsProperty();

        return view('livewire.money.bank-transaction-index');
    }

    /**
     * Remet la pagination dans son état initial (DRY)
     */
    protected function resetPagination(): void
    {
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
    }
}
