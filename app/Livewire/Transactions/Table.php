<?php

namespace App\Livewire\Transactions;

use App\Services\TransactionService;
use App\Models\BankAccount;
use Livewire\Component;

class Table extends Component
{
    public BankAccount $account;

    public function mount(BankAccount $account)
    {
        $this->account = $account;
    }

    public function getTransactionsProperty(TransactionService $transactionService)
    {
        return $transactionService->getGroupedTransactions($this->account);
    }

    public function render()
    {
        return view('livewire.transactions.table', [
            'transactions' => $this->transactions,
        ]);
    }
}
