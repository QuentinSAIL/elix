<?php

namespace App\Livewire\Money;

use App\Models\Wallet;
use App\Models\WalletPosition;
use App\Services\PriceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class WalletPositions extends Component
{
    public \App\Models\User $user;

    public Wallet $wallet;

    /** @var \Illuminate\Database\Eloquent\Collection<int, WalletPosition> */
    public \Illuminate\Database\Eloquent\Collection $positions;

    /** @var array{name: string, ticker: string|null, unit: string, quantity: string|float|int, price: string|float|int} */
    public array $positionForm = [
        'name' => '',
        'ticker' => '',
        'unit' => 'SHARE',
        'quantity' => 0,
        'price' => 0,
    ];

    public ?WalletPosition $editing = null;

    public string $userCurrency = 'EUR';

    public function mount(Wallet $wallet): void
    {
        $this->user = Auth::user();
        $this->wallet = $wallet;

        // Get user's preferred currency
        $userPreference = $this->user->preference()->first();
        $this->userCurrency = $userPreference?->currency ?? 'EUR';

        $this->refreshList();
    }

    public function refreshList(): void
    {
        $this->positions = $this->wallet->positions()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function edit(string $positionId): void
    {
        $pos = $this->wallet->positions()->find($positionId);
        if (! $pos) {
            Toaster::error(__('Position not found.'));

            return;
        }
        $this->editing = $pos;
        $this->positionForm = [
            'name' => $pos->name,
            'ticker' => (string) $pos->ticker,
            'unit' => $pos->unit,
            'quantity' => (string) $pos->quantity,
            'price' => (string) $pos->price,
        ];
    }

    public function resetForm(): void
    {
        $this->editing = null;
        $this->positionForm = [
            'name' => '',
            'ticker' => '',
            'unit' => 'SHARE',
            'quantity' => 0,
            'price' => 0,
        ];
    }

    public function save(): void
    {
        $this->validate([
            'positionForm.name' => 'required|string|max:255',
            'positionForm.ticker' => 'nullable|string|max:32',
            'positionForm.unit' => 'required|string|max:16',
            'positionForm.quantity' => 'required|numeric|min:0',
            'positionForm.price' => 'required|numeric|min:0',
        ]);

        try {
            if ($this->editing) {
                $this->editing->update([
                    'name' => trim($this->positionForm['name']),
                    'ticker' => $this->positionForm['ticker'] ? strtoupper(trim($this->positionForm['ticker'])) : null,
                    'unit' => strtoupper(trim($this->positionForm['unit'])),
                    'quantity' => (string) $this->positionForm['quantity'],
                    'price' => (string) $this->positionForm['price'],
                ]);
                Toaster::success(__('Position updated successfully.'));
            } else {
                $this->wallet->positions()->create([
                    'name' => trim($this->positionForm['name']),
                    'ticker' => $this->positionForm['ticker'] ? strtoupper(trim($this->positionForm['ticker'])) : null,
                    'unit' => strtoupper(trim($this->positionForm['unit'])),
                    'quantity' => (string) $this->positionForm['quantity'],
                    'price' => (string) $this->positionForm['price'],
                ]);
                Toaster::success(__('Position created successfully.'));
            }

            $this->resetForm();
            $this->refreshList();
        } catch (\Exception $e) {
            Toaster::error(__('Failed to save position. Please try again.'));
        }
    }

    public function delete(string $positionId): void
    {
        $pos = $this->wallet->positions()->find($positionId);
        if (! $pos) {
            Toaster::error(__('Position not found.'));

            return;
        }

        try {
            $pos->delete();
            Toaster::success(__('Position deleted successfully.'));
            $this->refreshList();
        } catch (\Exception $e) {
            Toaster::error(__('Failed to delete position. Please try again.'));
        }
    }

    /**
     * Get current price for a position in user's preferred currency
     */
    public function getCurrentPrice(WalletPosition $position): ?float
    {
        if (! $position->ticker) {
            return null;
        }

        return app(PriceService::class)->getPriceInCurrency($position->ticker, $this->userCurrency, 'USD');
    }

    /**
     * Get current value for a position in user's preferred currency
     */
    public function getCurrentValue(WalletPosition $position): ?float
    {
        $currentPrice = $this->getCurrentPrice($position);

        if ($currentPrice === null) {
            return null;
        }

        return (float) $position->quantity * $currentPrice;
    }

    /**
     * Get total portfolio value in user's preferred currency
     */
    public function getTotalValue(): float
    {
        $positions = $this->positions->map(function ($position) {
            return [
                'ticker' => $position->ticker,
                'quantity' => $position->quantity,
                'price' => $position->price,
            ];
        })->toArray();

        return app(PriceService::class)->calculatePositionsValueInCurrency($positions, $this->userCurrency);
    }

    /**
     * Get currency symbol for display
     */
    public function getCurrencySymbol(): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CHF' => 'CHF',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
            'CNY' => '¥',
        ];

        return $symbols[$this->userCurrency] ?? $this->userCurrency;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.money.wallet-positions');
    }
}
