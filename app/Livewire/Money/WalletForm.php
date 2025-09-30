<?php

namespace App\Livewire\Money;

use App\Models\Wallet;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class WalletForm extends Component
{
    public \App\Models\User $user;

    public bool $edition = false;

    public string|int $walletId = '';

    public ?Wallet $wallet = null; // filled when editing

    /** @var array{name: string, unit: string, balance: string|float|int} */
    public array $walletForm = [
        'name' => '',
        'unit' => 'EUR',
        'balance' => 0,
    ];

    public bool $mobile = false;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->populateForm();
    }

    public function populateForm(): void
    {
        if ($this->wallet) {
            $this->edition = true;
            $this->walletId = (string) $this->wallet->id;
            $this->walletForm = [
                'name' => $this->wallet->name,
                'unit' => $this->wallet->unit,
                'balance' => (string) $this->wallet->balance,
            ];
        } else {
            $this->edition = false;
            $this->walletId = '';
            $this->walletForm = [
                'name' => '',
                'unit' => 'EUR',
                'balance' => 0,
            ];
        }
    }

    public function save(): void
    {
        $this->validate([
            'walletForm.name' => 'required|string|max:255',
            'walletForm.unit' => 'required|string|max:16',
            'walletForm.balance' => 'required|numeric',
        ]);

        if ($this->wallet) {
            $this->wallet->update([
                'name' => $this->walletForm['name'],
                'unit' => $this->walletForm['unit'],
                'balance' => (string) $this->walletForm['balance'],
            ]);
            Toaster::success(__('Wallet updated.'));
        } else {
            $wallet = new Wallet([
                'user_id' => $this->user->id,
                'name' => $this->walletForm['name'],
                'unit' => $this->walletForm['unit'],
                'balance' => (string) $this->walletForm['balance'],
            ]);
            $wallet->save();
            Toaster::success(__('Wallet created.'));
        }

        $this->dispatch('wallets-updated');

        if ($this->wallet) {
            Flux::modals()->close('edit-wallet-'.$this->wallet->id.($this->mobile ? '-m' : ''));
        } else {
            Flux::modals()->close('create-wallet'.($this->mobile ? '-m' : ''));
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.money.wallet-form');
    }
}
