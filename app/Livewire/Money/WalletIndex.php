<?php

namespace App\Livewire\Money;

use App\Services\PriceService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class WalletIndex extends Component
{
    public \App\Models\User $user;

    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wallet> */
    public \Illuminate\Database\Eloquent\Collection $wallets;

    public string $userCurrency = 'EUR';

    public function mount(): void
    {
        $this->user = Auth::user();

        // Get user's preferred currency
        $userPreference = $this->user->preference()->first();
        $this->userCurrency = $userPreference->currency ?? 'EUR';

        $this->loadWallets();
    }

    #[\Livewire\Attributes\On('wallets-updated')]
    public function refreshList(): void
    {
        $this->loadWallets();
    }

    private function loadWallets(): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wallet> $wallets */
        $wallets = $this->user->wallets()
            ->withCount('positions')
            ->orderBy('created_at', 'desc')
            ->get();
        $this->wallets = $wallets;
    }

    public function delete(string|int $walletId): void
    {
        /** @var \App\Models\Wallet|null $wallet */
        $wallet = $this->user->wallets()->find($walletId);

        if (! $wallet) {
            Toaster::error(__('Wallet not found.'));

            return;
        }

        // Check if wallet has positions
        $positionsCount = $wallet->positions()->count();

        try {
            $wallet->delete();
            $this->loadWallets();
            Flux::modals()->close('delete-wallet-'.$wallet->id);

            if ($positionsCount > 0) {
                Toaster::success(__('Wallet and :count positions deleted successfully.', ['count' => $positionsCount]));
            } else {
                Toaster::success(__('Wallet deleted successfully.'));
            }
        } catch (\Exception $e) {
            Toaster::error(__('Failed to delete wallet. Please try again.'));
        }
    }

    /**
     * Get wallet balance in user's preferred currency
     */
    public function getWalletBalanceInCurrency(\App\Models\Wallet $wallet): float
    {
        if ($wallet->mode === 'single') {
            // For single mode, convert the balance from wallet currency to user currency
            $walletBalance = (float) $wallet->getCurrentBalance();

            // If wallet currency is the same as user currency, no conversion needed
            if ($wallet->unit === $this->userCurrency) {
                return $walletBalance;
            }

            // Convert from wallet currency to user currency
            $priceService = app(PriceService::class);
            $exchangeRate = $priceService->getExchangeRate($wallet->unit, $this->userCurrency);

            if ($exchangeRate !== null) {
                return $walletBalance * $exchangeRate;
            }

            // Fallback: return the original balance if conversion fails
            return $walletBalance;
        }

        // For multi mode, calculate from positions using the same logic as getCurrentMarketValue
        $totalValue = 0;
        $positions = $wallet->positions;

        foreach ($positions as $position) {
            $totalValue += $position->getCurrentMarketValue($this->userCurrency);
        }

        return $totalValue;
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

    /**
     * Get total portfolio value across all wallets
     */
    public function getTotalPortfolioValue(): float
    {
        $totalValue = 0;

        foreach ($this->wallets as $wallet) {
            $totalValue += $this->getWalletBalanceInCurrency($wallet);
        }

        return $totalValue;
    }

    /**
     * Get top positions by value for a wallet
     */
    public function getTopPositionsByValue(\App\Models\Wallet $wallet, int $limit = 3): \Illuminate\Database\Eloquent\Collection
    {
        if ($wallet->mode !== 'multi') {
            return collect();
        }

        return $wallet->positions()
            ->with('wallet')
            ->get()
            ->sortByDesc(function($position) {
                return $position->getCurrentMarketValue($this->userCurrency);
            })
            ->take($limit);
    }

    /**
     * Check if there are wallets with different currencies than user's preferred currency
     */
    public function hasMultipleCurrencies(): bool
    {
        foreach ($this->wallets as $wallet) {
            if ($wallet->unit !== $this->userCurrency) {
                return true;
            }
        }

        return false;
    }
}
