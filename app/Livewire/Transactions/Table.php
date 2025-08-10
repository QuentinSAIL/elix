<?php

namespace App\Livewire\Transactions;

use App\Models\BankAccount;
use Livewire\Component;

class Table extends Component
{
    public BankAccount $account;

    public function mount(BankAccount $account)
    {
        $this->account = $account;
    }

    public function getTransactionsProperty()
    {
        return $this->account->transactionsGroupedByDate();
    }

    public function render()
    {
        return view('livewire.transactions.table', [
            'transactions' => $this->getTransactionsProperty(),
        ]);
    }
}
