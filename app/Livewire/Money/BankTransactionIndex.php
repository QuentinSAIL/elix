<?php

namespace App\Livewire\Money;

use App\Http\Livewire\Traits\Notifies;
use App\Models\MoneyCategory;
use App\Models\MoneyCategoryMatch;
use App\Services\GoCardlessDataService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class BankTransactionIndex extends Component
{
    use Notifies;

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

    public function mount(TransactionService $transactionService)
    {
        $this->user = Auth::user();
        $this->accounts = $this->user->bankAccounts;
        $this->categories = MoneyCategory::orderBy('name')->get();

        $this->allAccounts = true;
        $this->perPage = $this->onInitialLoad;

        $this->getTransactionsProperty($transactionService);
    }

    /**
     * Récupère et met à jour les transactions depuis GoCardless
     */
    public function getTransactions(GoCardlessDataService $gocardless)
    {
        foreach ($this->accounts as $account) {
            $responses = $account->updateFromGocardless($gocardless);

            if ($responses) {
                foreach ($responses as $response) {
                    if (isset($response['status']) && $response['status'] === 'error') {
                        $this->notifyError($response['message'])->duration(30000);
                    } else {
                        $this->notifySuccess($response['message'])->duration(30000);
                    }
                }
            }
        }

        $this->getTransactionsProperty();
    }

    #[On('update-category-match')]
    public function searchAndApplyCategory($keyword, TransactionService $transactionService)
    {
        $transactionEdited = MoneyCategoryMatch::searchAndApplyMatchCategory($keyword);
        $this->notifySuccess("Catégorie appliquée à toutes les transactions correspondantes ($transactionEdited)");

        $this->getTransactionsProperty($transactionService);
    }

    public function updateSelectedAccount($accountId)
    {
        $this->allAccounts = $accountId === 'all';
        $this->selectedAccount = $this->accounts->find($accountId);
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
    }

    public function loadMore(TransactionService $transactionService)
    {
        $this->perPage += $this->increasedLoad;

        $this->getTransactionsProperty($transactionService);

        if ($this->perPage > $this->transactions->count()) {
            $this->noMoreToLoad = true;
        }
    }

    /**
     * Réinitialise la pagination lors de l'actualisation de la recherche
     */
    public function updatingSearch(TransactionService $transactionService)
    {
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
        $this->getTransactionsProperty($transactionService);
    }

    /**
     * Réinitialise la pagination lors du changement de filtre de catégorie
     */
    public function updatingCategoryFilter(TransactionService $transactionService)
    {
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
        $this->getTransactionsProperty($transactionService);
    }

    /**
     * Réinitialise la pagination lors du changement de filtre de date
     */
    public function updatingDateFilter(TransactionService $transactionService)
    {
        $this->perPage = $this->onInitialLoad;
        $this->noMoreToLoad = false;
        $this->getTransactionsProperty($transactionService);
    }

    /**
     * Gère le tri des colonnes
     */
    public function sortBy($field, TransactionService $transactionService)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        // Actualiser les transactions après le changement de tri
        $this->getTransactionsProperty($transactionService);
    }

    /**
     * Rafraîchit les transactions après une édition externe
     */
    #[On('transactions-edited')]
    public function refreshTransactions(TransactionService $transactionService)
    {
        $this->getTransactionsProperty($transactionService);
    }

    /**
     * Retourne la requête de base pour les transactions
     */
    public function getTransactionsProperty(TransactionService $transactionService)
    {
        $this->transactions = $transactionService->getTransactions(
            $this->selectedAccount,
            $this->allAccounts,
            $this->search,
            $this->categoryFilter,
            $this->dateFilter,
            $this->sortField,
            $this->sortDirection,
            $this->perPage
        );
    }

    public function render(TransactionService $transactionService)
    {
        $this->getTransactionsProperty($transactionService);

        return view('livewire.money.bank-transaction-index');
    }
}
