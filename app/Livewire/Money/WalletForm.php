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

    /** @var array{name: string, unit: string, mode: string, balance: string|float|int} */
    public array $walletForm = [
        'name' => '',
        'unit' => 'EUR',
        'mode' => 'single',
        'balance' => 0,
    ];

    public bool $mobile = false;

    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\WalletPosition> */
    public \Illuminate\Database\Eloquent\Collection $positions;

    public ?\App\Models\WalletPosition $editingPosition = null;

    /** @var array{name: string, ticker: string|null, unit: string, quantity: string|float|int, price: string|float|int} */
    public array $positionForm = [
        'name' => '',
        'ticker' => '',
        'unit' => 'SHARE',
        'quantity' => 0,
        'price' => 0,
    ];

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->populateForm();
        $this->positions = new \Illuminate\Database\Eloquent\Collection();
        if ($this->wallet) {
            $this->positions = $this->wallet->positions()->get();
        }
    }

    public function populateForm(): void
    {
        if ($this->wallet) {
            $this->edition = true;
            $this->walletId = (string) $this->wallet->id;
            $this->walletForm = [
                'name' => $this->wallet->name,
                'unit' => $this->wallet->unit,
                'mode' => $this->wallet->mode ?? 'single',
                'balance' => rtrim(rtrim(number_format((float) $this->wallet->balance, 8, '.', ''), '0'), '.'),
            ];
        } else {
            $this->edition = false;
            $this->walletId = '';
            $this->walletForm = [
                'name' => '',
                'unit' => 'EUR',
                'mode' => 'single',
                'balance' => '',
            ];
        }
    }

    public function save(): void
    {
        $rules = [
            'walletForm.name' => 'required|string|max:255',
            'walletForm.unit' => 'required|string|max:16',
            'walletForm.mode' => 'required|in:single,multi',
        ];

        // Balance is only required for single mode
        if ($this->walletForm['mode'] === 'single') {
            $rules['walletForm.balance'] = 'required|numeric|min:0';
        }

        $this->validate($rules);

        try {
            if ($this->wallet) {
                $updateData = [
                    'name' => trim($this->walletForm['name']),
                    'unit' => strtoupper(trim($this->walletForm['unit'])),
                    'mode' => $this->walletForm['mode'],
                ];

                // Only update balance for single mode
                if ($this->walletForm['mode'] === 'single') {
                    $updateData['balance'] = (string) $this->walletForm['balance'];
                } else {
                    // For multi mode, set balance to 0 (will be calculated from positions)
                    $updateData['balance'] = '0';
                }

                $this->wallet->update($updateData);
                Toaster::success(__('Wallet updated successfully.'));
                $this->positions = $this->wallet->positions()->get();
            } else {
                $walletData = [
                    'user_id' => $this->user->id,
                    'name' => trim($this->walletForm['name']),
                    'unit' => strtoupper(trim($this->walletForm['unit'])),
                    'mode' => $this->walletForm['mode'],
                ];

                // Only set balance for single mode
                if ($this->walletForm['mode'] === 'single') {
                    $walletData['balance'] = (string) $this->walletForm['balance'];
                } else {
                    // For multi mode, set balance to 0 (will be calculated from positions)
                    $walletData['balance'] = '0';
                }

                $wallet = new Wallet($walletData);
                $wallet->save();
                Toaster::success(__('Wallet created successfully.'));
            }

            $this->dispatch('wallets-updated');

            if ($this->wallet) {
                Flux::modals()->close('edit-wallet-'.$this->wallet->id.($this->mobile ? '-m' : ''));
            } else {
                Flux::modals()->close('create-wallet'.($this->mobile ? '-m' : ''));
            }
        } catch (\Exception $e) {
            Toaster::error(__('Failed to save wallet. Please try again.'));
        }
    }

    public function savePosition(): void
    {
        if (!$this->wallet) {
            Toaster::error(__('Save wallet before adding positions.'));
            return;
        }

        $this->validate([
            'positionForm.name' => 'required|string|max:255',
            'positionForm.ticker' => 'nullable|string|max:32',
            'positionForm.unit' => 'required|string|max:16',
            'positionForm.quantity' => 'required|numeric|min:0',
            'positionForm.price' => 'required|numeric|min:0',
        ]);

        try {
            if ($this->editingPosition) {
                $this->editingPosition->update([
                    'name' => trim($this->positionForm['name']),
                    'ticker' => $this->positionForm['ticker'] ? strtoupper(trim($this->positionForm['ticker'])) : null,
                    'unit' => strtoupper(trim($this->positionForm['unit'])),
                    'quantity' => (string) $this->positionForm['quantity'],
                    'price' => (string) $this->positionForm['price'],
                ]);
                Toaster::success(__('Position updated successfully.'));
            } else {
                $position = $this->wallet->positions()->create([
                    'name' => trim($this->positionForm['name']),
                    'ticker' => $this->positionForm['ticker'] ? strtoupper(trim($this->positionForm['ticker'])) : null,
                    'unit' => strtoupper(trim($this->positionForm['unit'])),
                    'quantity' => (string) $this->positionForm['quantity'],
                    'price' => (string) $this->positionForm['price'],
                ]);
                Toaster::success(__('Position added successfully.'));

                // Update price for the new position if it has a ticker
                if ($position->ticker) {
                    $position->updateCurrentPrice();
                }
            }

            $this->resetPositionForm();
            $this->positions = $this->wallet->positions()->get();

            // Update price for the position if it has a ticker
            if ($this->editingPosition && $this->editingPosition->ticker) {
                $this->editingPosition->updateCurrentPrice();
            }
        } catch (\Exception $e) {
            Toaster::error(__('Failed to save position. Please try again.'));
        }
    }

    public function editPosition(string $positionId): void
    {
        $position = $this->wallet->positions()->find($positionId);
        if (!$position) {
            Toaster::error(__('Position not found.'));
            return;
        }

        $this->editingPosition = $position;
        $this->positionForm = [
            'name' => $position->name,
            'ticker' => (string) $position->ticker,
            'unit' => $position->unit,
            'quantity' => (string) $position->quantity,
            'price' => (string) $position->price,
        ];
    }

    public function cancelEditPosition(): void
    {
        $this->editingPosition = null;
        $this->resetPositionForm();
    }

    public function deletePosition(string $positionId): void
    {
        $position = $this->wallet->positions()->find($positionId);
        if (!$position) {
            Toaster::error(__('Position not found.'));
            return;
        }

        try {
            $position->delete();
            $this->positions = $this->wallet->positions()->get();
            Toaster::success(__('Position deleted successfully.'));
        } catch (\Exception $e) {
            Toaster::error(__('Failed to delete position. Please try again.'));
        }
    }

    private function resetPositionForm(): void
    {
        $this->positionForm = [
            'name' => '',
            'ticker' => '',
            'unit' => 'SHARE',
            'quantity' => 0,
            'price' => 0,
        ];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.money.wallet-form');
    }
}
