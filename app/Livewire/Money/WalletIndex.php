<?php

namespace App\Livewire\Money;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class WalletIndex extends Component
{
    public \App\Models\User $user;

    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wallet> */
    public \Illuminate\Database\Eloquent\Collection $wallets;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->wallets = (new \Illuminate\Database\Eloquent\Collection($this->user->wallets->all()));
    }

    #[\Livewire\Attributes\On('wallets-updated')]
    public function refreshList(): void
    {
        $this->wallets = (new \Illuminate\Database\Eloquent\Collection($this->user->wallets->all()));
    }

    public function updateWallet(string|int $walletId): void
    {
        /** @var \App\Models\Wallet|null $wallet */
        $wallet = $this->wallets->find($walletId);
        if ($wallet) {
            // $wallet->update(['name' => $name]);
            Toaster::success('Compte bancaire mis à jour avec succès.');
        } else {
            Toaster::error('Compte bancaire introuvable.');
        }
    }

    public function delete(string|int $walletId): void
    {
        /** @var \App\Models\Wallet|null $wallet */
        $wallet = $this->user->wallets()->find($walletId);

        if ($wallet) {
            $wallet->delete();
            $this->wallets = (new \Illuminate\Database\Eloquent\Collection($this->user->wallets->all()));
            Flux::modals()->close('delete-wallet-'.$wallet->id);
            Toaster::success('Portefeuille supprimé avec succès.');
        } else {
            Toaster::error('Portefeuille introuvable.');
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.money.wallet-index');
    }
}
