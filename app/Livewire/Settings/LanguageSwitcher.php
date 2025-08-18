<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class LanguageSwitcher extends Component
{
    public string $locale;

    public array $supportedLocales;

    public function mount()
    {
        // $this->locale = App::getLocale();
        $this->locale = Session::get('locale', App::getLocale());
        if (!isset($this->supportedLocales)) {
            $this->supportedLocales = config('app.supported_locales');
        }
        if (! in_array($this->locale, array_keys($this->supportedLocales))) {
            $this->locale = array_key_first($this->supportedLocales);
        }
    }

    public function switchTo(string $lang)
    {
        if (array_key_exists($lang, $this->supportedLocales)) {
            App::setLocale($lang);
            Session::put('locale', $lang);
            $this->locale = $lang;
            $this->dispatch('show-toast', type: 'info', message: __('Language switched successfully to '.$lang));
        } else {
            $this->dispatch('show-toast', type: 'error', message: 'Language not supported.');
        }
    }
}
