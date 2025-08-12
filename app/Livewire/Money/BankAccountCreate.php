<?php

namespace App\Livewire\Money;

use App\Services\GoCardlessDataService;
use Livewire\Component;

class BankAccountCreate extends Component
{
    /** @var array<array<string, mixed>> */
    public array $banks;

    public string $selectedBank;

    public ?string $searchTerm = '';

    public int $maxAccessValidForDays;

    public int $transactionTotalDays;

    public ?string $logo;

    protected GoCardlessDataService $goCardlessDataService;

    public function mount(): void
    {
        $this->goCardlessDataService = app(GoCardlessDataService::class);
        $this->banks = $this->goCardlessDataService->getBanks();
    }

    public function updateSelectedBank(string $value): void
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

    /**
     * @return array<array<string, mixed>>
     */
    public function getFilteredBanksProperty(): array
    {
        if (! $this->banks) {
            return [];
        }

        return collect($this->banks)->filter(fn ($b) => isset($b['name']) && stripos($b['name'], $this->searchTerm) !== false)->values()->toArray();
    }

    public function addNewBankAccount(): void
    {
        if (! $this->selectedBank) {
            return;
        }

        $service = app(GoCardlessDataService::class);
        $service->addNewBankAccount($this->selectedBank, $this->transactionTotalDays, $this->maxAccessValidForDays, $this->logo);
    }

    public function addNewAccount(): void {}

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.money.bank-account-create');
    }
}
