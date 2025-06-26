<?php

namespace App\Livewire\Settings;

use App\Http\Livewire\Traits\Notifies;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class Modules extends Component
{
    use Notifies;
    public $user;

    public $allModules;

    public $activeModules = [];

    public function mount()
    {
        $this->user = Auth::user();
        $this->allModules = Module::all();
        $this->activeModules = $this->user->modules->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    public function updateModules()
    {
        $this->user->modules()->sync($this->activeModules);
        $this->activeModules = $this->user->modules->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $this->notifySuccess('Modules updated successfully.');

        return redirect()->route('settings.modules');
    }
}
