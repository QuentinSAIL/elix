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
    public $logo;
    protected $goCardlessDataService;

    public function mount()
    {
        $this->goCardlessDataService = app(GoCardlessDataService::class);
        $this->banks = $this->goCardlessDataService->getBanks();
    }

    public function updateSelectedBank($value)
    {
        $this->selectedBank = $value;
        $bank = collect($this->banks)->firstWhere('id', $this->selectedBank);
        if ($bank) {
            $this->searchTerm = $bank['name'];
            $this->maxAccessValidForDays = $bank['max_access_valid_for_days'];
            $this->transactionTotalDays = $bank['transaction_total_days'];
            $this->logo = $bank['logo'];
        } else {
            $this->searchTerm = null;
        }
    }

    public function getFilteredBanksProperty()
    {
        if (!$this->banks || !is_array($this->banks)) {
            return [];
        }
        return collect($this->banks)->filter(fn($b) => is_array($b) && isset($b['name']) && stripos($b['name'], $this->searchTerm) !== false)->values()->toArray();
    }

    public function addNewBankAccount()
    {
        if (!$this->selectedBank) {
            return;
        }

        $service = app(GoCardlessDataService::class);
        $service->addNewBankAccount($this->selectedBank, $this->transactionTotalDays, $this->maxAccessValidForDays, $this->logo);
    }

    public function addNewAccount() {}

    public function render()
    {
        return view('livewire.money.bank-account-create');
    }
}
