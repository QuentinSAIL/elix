<?php

namespace App\Livewire\Money;

use App\Models\MoneyCategoryMatch;
use App\Services\GoCardlessDataService;
use App\Services\TransactionCacheService;
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

    public ?string $selectedAccountId = null;

    public bool $allAccounts = false;

    public int $perPage = 100;

    public int $onInitialLoad = 50;

    public int $increasedLoad = 50;

    public bool $noMoreToLoad = false;

    public bool $isAccountLoading = false;

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
    protected array $allowedSorts = ['transaction_date', 'amount', 'description', 'money_category_id', 'bank_account_id', 'created_at', 'id'];

    public function mount(): void
    {
        $this->user = Auth::user();
        $cacheService = app(TransactionCacheService::class);

        if (! isset($this->accounts)) {
            /** @var EloquentCollection<int, \App\Models\BankAccount> $accounts */
            $accounts = $this->user->bankAccounts()->withCount('transactions')->get();
            $this->accounts = $accounts;
        }

        // Utiliser le cache pour les catégories
        $this->categories = $cacheService->getCategories();
        $this->transactions = new EloquentCollection;

        $this->allAccounts = true;
        $this->selectedAccountId = null;
        $this->perPage = $this->onInitialLoad;

        // Précharger le cache et mettre à jour les comptes
        $cacheService->warmUpUserCache($this->user);
        $this->updateAccountCounts();

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
        Toaster::success(__('Category applied to all matching transactions (:count)', ['count' => $transactionEdited]));

        $this->getTransactionsProperty();
    }

    public function updateSelectedAccount(string|int $accountId): void
    {
        $this->allAccounts = $accountId === 'all';
        $this->selectedAccountId = $this->allAccounts ? null : (string) $accountId;
        $this->selectedAccount = $this->allAccounts ? null : $this->accounts->firstWhere('id', $this->selectedAccountId);

        $this->resetPagination();

        // Indique le chargement côté client (entangle Alpine)
        $this->isAccountLoading = true;
        $this->dispatch('account-changing');

        // Utiliser le cache pour accélérer le chargement
        $cacheService = app(TransactionCacheService::class);
        $cacheService->warmUpUserCache($this->user);

        $this->getTransactionsProperty();
        $this->isAccountLoading = false;
        $this->dispatch('account-changed');
    }

    public function loadMore(): void
    {
        if ($this->noMoreToLoad) {
            return;
        }

        $oldPerPage = $this->perPage;
        $this->perPage += $this->increasedLoad;

        // Récupérer seulement les nouvelles transactions
        $query = $this->getTransactionQuery();

        if ($query instanceof SupportCollection) {
            return; // Pas de pagination pour les collections
        }

        // Récupérer les nouvelles transactions avec eager loading
        $newRows = $query
            ->with([
                'category' => fn ($query) => $query->select('id', 'name'),
                'account' => fn ($query) => $query->select('id', 'name'),
            ])
            ->orderBy($this->sortField, $this->sortDirection)
            ->skip($oldPerPage)
            ->take($this->increasedLoad + 1)
            ->get();

        $this->noMoreToLoad = $newRows->count() <= $this->increasedLoad;

        // Ajouter les nouvelles transactions à la collection existante
        $newTransactions = $newRows->take($this->increasedLoad);
        $this->transactions = $this->transactions->merge($newTransactions);
    }

    /** Réinitialise la pagination lors de l'actualisation de la recherche */
    public function updatedSearch(): void
    {
        $this->isAccountLoading = true;
        $this->resetPagination();
        $this->getTransactionsProperty();
        $this->isAccountLoading = false;
    }

    /** Réinitialise la pagination lors du changement de filtre de catégorie */
    public function updatedCategoryFilter(): void
    {
        $this->isAccountLoading = true;
        $this->resetPagination();
        $this->getTransactionsProperty();
        $this->isAccountLoading = false;
    }

    /** Réinitialise la pagination lors du changement de filtre de date */
    public function updatedDateFilter(): void
    {
        $this->isAccountLoading = true;
        $this->resetPagination();
        $this->getTransactionsProperty();
        $this->isAccountLoading = false;
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
        $this->isAccountLoading = true;
        $this->resetPagination();
        $this->getTransactionsProperty();
        $this->isAccountLoading = false;
    }

    /**
     * Rafraîchit les transactions après une édition externe
     */
    #[On('transactions-edited')]
    public function refreshTransactions(): void
    {
        $this->mount();
    }

    /**
     * Met à jour une transaction spécifique après catégorisation
     */
    #[On('transaction-categorized')]
    public function updateTransaction(string $transactionId): void
    {
        // Recharger la transaction avec ses relations
        // @phpstan-ignore-next-line
        $updatedTransaction = \App\Models\BankTransactions::with([
            'category' => fn ($query) => $query->select('id', 'name'),
            'account' => fn ($query) => $query->select('id', 'name'),
        ])->find($transactionId);

        if ($updatedTransaction) {
            // Trouver et remplacer la transaction dans la collection
            $index = $this->transactions->search(function ($transaction) use ($transactionId) {
                return $transaction->id === (int) $transactionId;
            });

            if ($index !== false) {
                $this->transactions->put($index, $updatedTransaction);
            }
        }
    }

    /**
     * Retourne la requête de base pour les transactions
     *
     * @return HasMany<\App\Models\BankTransactions, \App\Models\BankAccount>
     *                                                                        | HasManyThrough<\App\Models\BankTransactions, \App\Models\BankAccount, \App\Models\User>
     *                                                                        | SupportCollection<array-key, mixed>
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
            if ($this->categoryFilter === 'uncategorized') {
                $query->whereNull('money_category_id');
            } else {
                $query->where('money_category_id', $this->categoryFilter);
            }
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

        // Forcer le type : si $query est déjà une Collection, on wrap en queryBuilder
        if ($query instanceof \Illuminate\Support\Collection) {
            $ids = $query->pluck('id')->all();

            $query = \App\Models\BankTransactions::query()->whereIn('id', $ids);
            // Ici on repasse sur une query SQL, donc tout est optimisé côté DB
        }

        // Tri sécurisé
        if (! in_array($this->sortField, $this->allowedSorts, true)) {
            $this->sortField = 'transaction_date';
            $this->sortDirection = 'desc';
        }

        // On récupère perPage + 1 en BDD
        $rows = $query
            ->with(['category:id,name', 'account:id,name'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->take($this->perPage + 1)
            ->get();

        $this->noMoreToLoad = $rows->count() <= $this->perPage;

        $this->transactions = $rows->take($this->perPage);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        // Réhydrate l'objet sélectionné à partir de l'ID (évite les incohérences de rehydratation)
        if (! $this->allAccounts && $this->selectedAccountId) {
            $this->selectedAccount = $this->accounts->firstWhere('id', $this->selectedAccountId);
        }

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

    /**
     * Met à jour les compteurs de transactions pour tous les comptes
     */
    protected function updateAccountCounts(): void
    {
        $cacheService = app(TransactionCacheService::class);
        $accountCounts = $cacheService->getUserAccountCounts($this->user);
        $totalCount = $cacheService->getUserTotalCount($this->user);

        // Mettre à jour les comptes avec les compteurs du cache
        foreach ($this->accounts as $account) {
            $account->setAttribute('transactions_count', $accountCounts[$account->id] ?? 0);
        }

        // Ajouter le total au user pour l'affichage "All accounts"
        $this->user->setAttribute('bank_transactions_count', $totalCount);
    }
}
