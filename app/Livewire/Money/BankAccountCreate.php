<?php

namespace App\Livewire\Money;

use App\Services\GoCardlessDataService;
use Livewire\Component;

class BankAccountCreate extends Component
{
    /** @var array<array<string, mixed>> */
    public array $banks = [];

    public ?string $selectedBank = null;

    public ?string $searchTerm = '';

    public int $maxAccessValidForDays = 0;

    public int $transactionTotalDays = 0;

    public ?string $logo = null;

    protected GoCardlessDataService $goCardlessDataService;

    public function mount(): void
    {
        $this->goCardlessDataService = app(GoCardlessDataService::class);
        $this->banks = $this->goCardlessDataService->getBanks() ?? [];
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

    protected function normalize(string $s): string
    {
        $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
        $s = preg_replace('/\p{Mn}+/u', '', $s); // enlève accents

        return mb_strtolower($s);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getFilteredBanksProperty(): array
    {
        $banks = $this->banks ?? [];
        $needle = $this->normalize((string) $this->searchTerm);

        if ($needle === '') {
            return $banks;
        }

        return collect($banks)
            ->filter(fn ($b) => isset($b['name']) && str_contains(
                $this->normalize((string) $b['name']), $needle
            ))
            ->values()
            ->all();
    }

    public function addNewBankAccount(): void
    {
        if (! isset($this->selectedBank)) {
            $this->dispatch('error', ['message' => 'Please select a bank.']);

            return;
        }

        $service = app(GoCardlessDataService::class);
        $service->addNewBankAccount($this->selectedBank, $this->transactionTotalDays, $this->maxAccessValidForDays, $this->logo);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.money.bank-account-create');
    }
}
