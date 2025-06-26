<?php

namespace App\Livewire\Money;

use App\Services\GoCardlessDataService;
use Livewire\Component;

class BankAccountCreate extends Component
{
    public $banks;

    public $selectedBank;

    public $searchTerm = '';

    public $maxAccessValidForDays;

    public $transactionTotalDays;

    public $logo;

    public function mount(GoCardlessDataService $goCardlessDataService)
    {
        $this->banks = $goCardlessDataService->getBanks();
    }

    public function updateSelectedBank($value)
    {
        $this->selectedBank = $value;
        $this->searchTerm = collect($this->banks)->firstWhere('id', $this->selectedBank)['name'];
        $this->maxAccessValidForDays = collect($this->banks)->firstWhere('id', $this->selectedBank)['max_access_valid_for_days'];
        $this->transactionTotalDays = collect($this->banks)->firstWhere('id', $this->selectedBank)['transaction_total_days'];
        $this->logo = collect($this->banks)->firstWhere('id', $this->selectedBank)['logo'];
    }

    public function getFilteredBanksProperty()
    {
        return collect($this->banks)->filter(fn ($b) => stripos($b['name'], $this->searchTerm) !== false)->values()->toArray();
    }

    public function addNewBankAccount(GoCardlessDataService $goCardlessDataService)
    {
        $goCardlessDataService->addNewBankAccount($this->selectedBank, $this->transactionTotalDays, $this->maxAccessValidForDays, $this->logo);
    }

    public function addNewAccount() {}

    public function render()
    {
        return view('livewire.money.bank-account-create');
    }
}
