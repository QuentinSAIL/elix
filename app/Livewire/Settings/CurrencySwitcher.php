<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class CurrencySwitcher extends Component
{
    public $user;
    public $userPreference;
    public $currency;
    public $supportedCurrencies;

    public function mount()
    {
        $this->supportedCurrencies = [
            'EUR' => 'Euro (€)',
            'USD' => 'US Dollar ($)',
            'GBP' => 'British Pound (£)',
            'CHF' => 'Swiss Franc (CHF)',
            'CAD' => 'Canadian Dollar (C$)',
            'AUD' => 'Australian Dollar (A$)',
            'JPY' => 'Japanese Yen (¥)',
            'CNY' => 'Chinese Yuan (¥)',
        ];

        $this->user = Auth::user();

        $candidate = null;
        if ($this->user) {
            /** @var \App\Models\UserPreference|null $persistedPref */
            $persistedPref = $this->user->preference()->first();
            $candidate = $persistedPref?->currency;
            $this->userPreference = $this->user->preference()->firstOrNew(['user_id' => $this->user->id]);
        }

        if (!$candidate) {
            $candidate = 'EUR'; // Default currency
        }

        if (!array_key_exists($candidate, $this->supportedCurrencies)) {
            $candidate = 'EUR';
        }

        $this->currency = $candidate;
    }

    public function switchTo(string $currency)
    {
        if (!array_key_exists($currency, $this->supportedCurrencies)) {
            Toaster::error(__('Currency not supported.'));
            return;
        }

        $this->currency = $currency;
        
        if ($this->user) {
            $pref = $this->user->preference()->firstOrNew(['user_id' => $this->user->id]);
            $pref->currency = $currency;
            $pref->save();
            $this->userPreference = $pref;
        }

        Toaster::success(__('Currency switched successfully to ') . $this->supportedCurrencies[$currency]);
    }

    public function render()
    {
        return view('livewire.settings.currency-switcher');
    }
}
