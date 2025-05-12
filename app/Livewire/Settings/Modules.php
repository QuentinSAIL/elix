<?php

namespace App\Livewire\Settings;

use App\Models\Module;
use Livewire\Component;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;

class Modules extends Component
{
    public $user;
    public $allModules;
    public $activeModules = [];

    public function mount()
    {
        $this->user = Auth::user();
        $this->allModules = Module::all();
        $this->activeModules = $this->user->modules->pluck('id')->map(fn($id) => (string) $id)->toArray();
    }

    public function updateModules()
    {
        $this->user->modules()->sync($this->activeModules);
        $this->activeModules = $this->user->modules->pluck('id')->map(fn($id) => (string) $id)->toArray();
        Toaster::success('Modules updated successfully.');
        return redirect()->route('settings.modules');
    }
}
