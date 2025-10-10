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
        $this->userCurrency = $userPreference?->currency ?? 'EUR';

        $this->loadWallets();
    }

    #[\Livewire\Attributes\On('wallets-updated')]
    public function refreshList(): void
    {
        $this->loadWallets();
    }

    private function loadWallets(): void
    {
        $this->wallets = $this->user->wallets()
            ->withCount('positions')
            ->orderBy('created_at', 'desc')
            ->get();
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
            // For single mode, assume the balance is already in user's currency
            return (float) $wallet->getCurrentBalance();
        }

        // For multi mode, calculate from positions
        $positions = $wallet->positions->map(function ($position) {
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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.money.wallet-index');
    }
}
