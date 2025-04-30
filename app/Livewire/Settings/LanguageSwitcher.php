<?php
namespace App\Livewire\Settings;

use Livewire\Component;
use Masmerise\Toaster\Toast;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageSwitcher extends Component
{
    public string $locale;

    public function mount()
    {
        $this->locale = App::getLocale();
    }

    public function switchTo(string $lang)
    {
        if (in_array($lang, array_keys(config('app.supported_locales')))) {
            Session::put('locale', $lang);
            App::setLocale($lang);
            $this->locale = $lang;
            Toaster::info(__('Language switched successfully. to ' . $lang));
        }
    }
}
