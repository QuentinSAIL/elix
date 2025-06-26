<?php

namespace App\Livewire\Settings;

use App\Http\Livewire\Traits\Notifies;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteUserForm extends Component
{
    use Notifies;
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        try {
            $this->validate([
                'password' => ['required', 'string', 'current_password'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->notifyError($e->getMessage());

            return;
        }

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}
