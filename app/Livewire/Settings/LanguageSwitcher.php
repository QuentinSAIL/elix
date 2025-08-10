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

    public array $supportedLocales;

    public function mount()
    {
        // $this->locale = App::getLocale();
        $this->locale = Session::get('locale', App::getLocale());
        $this->supportedLocales = config('app.supported_locales');
        if (!in_array($this->locale, array_keys($this->supportedLocales))) {
            $this->locale = array_key_first($this->supportedLocales);
        }

        dump($this->locale);
    }

    public function switchTo(string $lang)
    {
        if (array_key_exists($lang, $this->supportedLocales)) {
            App::setLocale($lang);
            Session::put('locale', $lang);
            $this->locale = $lang;
            Toaster::info(__('Language switched successfully to ' . $lang));
        } else {
            Toaster::error(__('Language not supported.'));
        }
    }
}
