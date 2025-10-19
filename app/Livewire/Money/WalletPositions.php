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
        $this->userCurrency = $userPreference->currency ?? 'EUR';

        $this->refreshList();
    }

    public function refreshList(): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\WalletPosition> $positions */
        $positions = $this->wallet->positions()
            ->get();

        $this->positions = $positions->sortByDesc(function (\App\Models\WalletPosition $position) {
            return $position->getCurrentMarketValue();
        });
    }

    public function edit(string $positionId): void
    {
        /** @var \App\Models\WalletPosition|null $pos */
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
            'positionForm.price' => 'required|numeric|gt:0',
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
                /** @var \App\Models\WalletPosition $position */
                $position = $this->wallet->positions()->create([
                    'name' => trim($this->positionForm['name']),
                    'ticker' => $this->positionForm['ticker'] ? strtoupper(trim($this->positionForm['ticker'])) : null,
                    'unit' => strtoupper(trim($this->positionForm['unit'])),
                    'quantity' => (string) $this->positionForm['quantity'],
                    'price' => (string) $this->positionForm['price'],
                ]);
                Toaster::success(__('Position created successfully.'));

                // Si la position a un ticker, vérifier/créer dans price_assets et mettre à jour le prix
                if ($position->ticker) {
                    $this->handleTickerPriceUpdate($position);
                }
            }

            $this->resetForm();
            $this->refreshList();
        } catch (\Exception $e) {
            Toaster::error(__('Failed to save position. Please try again.'));
        }
    }

    /**
     * Handle ticker price update for new positions
     * Only updates price_assets table, never wallet_positions
     */
    private function handleTickerPriceUpdate(\App\Models\WalletPosition $position): void
    {
        try {
            $priceService = app(\App\Services\PriceService::class);

            // Vérifier si l'actif existe déjà dans price_assets
            $priceAsset = \App\Models\PriceAsset::where('ticker', $position->ticker)->first();

            if ($priceAsset && $priceAsset->isPriceRecent()) {
                // Utiliser le prix récent de la base de données
                Toaster::info(__('Using recent market data from database.'));
            } else {
                // Créer l'entrée dans price_assets si elle n'existe pas
                if (! $priceAsset) {
                    $assetType = $this->getAssetTypeFromUnitType($position->unit);
                    $priceAsset = \App\Models\PriceAsset::findOrCreate($position->ticker, $assetType);
                }

                // Essayer de récupérer un prix frais depuis l'API et le sauvegarder dans price_assets
                $currentPrice = $priceService->getPrice($position->ticker, $this->wallet->unit, $position->unit);

                if ($currentPrice !== null) {
                    $priceAsset->updatePrice($currentPrice, $this->wallet->unit);
                    Toaster::info(__('Price updated from market data.'));
                } else {
                    // Garder le prix manuel si l'API échoue
                    Toaster::warning(__('Could not fetch current price. Using manual price.'));
                }
            }
        } catch (\Exception $e) {
            // En cas d'erreur, garder le prix manuel
            Toaster::warning(__('Could not update price. Using manual price.'));
        }
    }

    /**
     * Convert unitType to asset type for the price_assets table
     */
    private function getAssetTypeFromUnitType(?string $unitType): string
    {
        return match ($unitType) {
            'CRYPTO', 'TOKEN' => 'CRYPTO',
            'STOCK' => 'STOCK',
            'COMMODITY' => 'COMMODITY',
            'ETF' => 'ETF',
            'BOND' => 'BOND',
            default => 'OTHER',
        };
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
     * Update prices for all positions with tickers
     * Only updates price_assets table, never wallet_positions
     */
    public function updatePrices(): void
    {
        $updated = 0;
        $failed = 0;

        // Group positions by ticker to synchronize prices
        $positionsByTicker = [];
        foreach ($this->positions as $position) {
            if ($position->ticker) {
                $ticker = strtoupper($position->ticker);
                $positionsByTicker[$ticker][] = $position;
            }
        }

        // Update prices for each ticker group in price_assets table
        foreach ($positionsByTicker as $ticker => $positions) {
            try {
                $priceService = app(\App\Services\PriceService::class);
                $currentPrice = $priceService->getPrice($ticker, $this->wallet->unit, $positions[0]->unit);

                if ($currentPrice !== null) {
                    // Update price in price_assets table
                    $priceAsset = \App\Models\PriceAsset::findOrCreate($ticker, 'OTHER');
                    $priceAsset->updatePrice($currentPrice, $this->wallet->unit);
                    $updated += count($positions);
                } else {
                    $failed += count($positions);
                }
            } catch (\Exception $e) {
                $failed += count($positions);
            }
        }

        $this->refreshList();

        if ($updated > 0) {
            Toaster::success(__('Prices updated successfully for :count positions', ['count' => $updated]));
        }

        if ($failed > 0) {
            Toaster::warning(__('Failed to update prices for :count positions', ['count' => $failed]));
        }
    }

    /**
     * Update price for a specific position and synchronize with other positions having the same ticker
     * Only updates price_assets table, never wallet_positions
     */
    public function updatePositionPrice(string $positionId): void
    {
        /** @var \App\Models\WalletPosition|null $position */
        $position = $this->wallet->positions()->find($positionId);
        if (! $position || ! $position->ticker) {
            Toaster::error(__('Position not found or no ticker available.'));

            return;
        }

        try {
            $priceService = app(\App\Services\PriceService::class);
            $currentPrice = $priceService->getPrice($position->ticker, $this->wallet->unit, $position->unit);

            if ($currentPrice !== null) {
                // Update price in price_assets table
                $priceAsset = \App\Models\PriceAsset::findOrCreate($position->ticker, 'OTHER');
                $priceAsset->updatePrice($currentPrice, $this->wallet->unit);

                // Count positions with the same ticker
                $ticker = strtoupper($position->ticker);
                $updatedCount = 0;
                foreach ($this->positions as $pos) {
                    if ($pos->ticker && strtoupper($pos->ticker) === $ticker) {
                        $updatedCount++;
                    }
                }

                Toaster::success(__('Price updated successfully for :count positions with ticker :ticker', [
                    'count' => $updatedCount,
                    'ticker' => $position->ticker,
                ]));
                $this->refreshList();
            } else {
                Toaster::warning(__('Failed to update price for :name', ['name' => $position->name]));
            }
        } catch (\Exception $e) {
            Toaster::error(__('Error updating price for :name', ['name' => $position->name]));
        }
    }

    /**
     * Get current price for a position in user's preferred currency
     * ONLY uses price_assets table if ticker exists, never wallet_positions
     */
    public function getCurrentPrice(WalletPosition $position): ?float
    {
        if (! $position->ticker) {
            return null;
        }

        // ONLY get price from price_assets table, never wallet_positions
        $priceAsset = \App\Models\PriceAsset::where('ticker', $position->ticker)->first();

        if ($priceAsset && $priceAsset->price !== null) {
            // Convert to user's currency if needed
            if ($priceAsset->currency !== $this->userCurrency) {
                $priceService = app(PriceService::class);
                $convertedPrice = $priceService->convertCurrency(
                    (float) $priceAsset->price,
                    $priceAsset->currency,
                    $this->userCurrency
                );

                return $convertedPrice;
            }

            return (float) $priceAsset->price;
        }

        // If no price in price_assets, return null (never use wallet_positions price)
        return null;
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
