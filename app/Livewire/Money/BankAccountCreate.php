<?php

namespace App\Livewire\Money;

use Livewire\Component;
use App\Services\GoCardlessDataService;

class BankAccountCreate extends Component
{
    public $banks;
    public $selectedBank;
    public $searchTerm = '';
    public $maxAccessValidForDays;
    public $transactionTotalDays;
    protected $goCardlessDataService;

    public function mount()
    {
        $this->goCardlessDataService = new GoCardlessDataService();
        $this->banks = $this->goCardlessDataService->getBanks();
    }

    public function updateSelectedBank($value)
    {
        $this->selectedBank = $value;
        $this->searchTerm = collect($this->banks)->firstWhere('id', $this->selectedBank)['name'];
        $this->maxAccessValidForDays = collect($this->banks)->firstWhere('id', $this->selectedBank)['max_access_valid_for_days'];
        $this->transactionTotalDays = collect($this->banks)->firstWhere('id', $this->selectedBank)['transaction_total_days'];
    }

    public function getFilteredBanksProperty()
    {
        return collect($this->banks)->filter(fn($b) => stripos($b['name'], $this->searchTerm) !== false)->values()->toArray();
    }

    public function addNewBankAccount()
    {
        $goCardlessDataService = new GoCardlessDataService();
        $goCardlessDataService->addNewBankAccount($this->selectedBank, $this->transactionTotalDays, $this->maxAccessValidForDays);
    }

    public function addNewAccount() {}

    public function render()
    {
        return view('livewire.money.bank-account-create');
    }
}
