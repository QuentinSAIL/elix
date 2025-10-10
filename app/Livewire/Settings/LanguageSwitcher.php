<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class LanguageSwitcher extends Component
{
    public $user;

    public $userPreference;

    public $locale;

    public $supportedLocales;

    public function mount()
    {
        $this->supportedLocales = config('app.supported_locales');

        if (! is_array($this->supportedLocales) || empty($this->supportedLocales)) {
            $this->supportedLocales = ['en' => 'English'];
        }

        $this->user = auth()->user();

        $candidate = null;
        if ($this->user) {
            /** @var \App\Models\UserPreference|null $persistedPref */
            $persistedPref = $this->user->preference()->first();
            $candidate = $persistedPref?->locale;
            $this->userPreference = $this->user->preference()->firstOrNew(['user_id' => $this->user->id]);
        }
        if (! $candidate) {
            $candidate = Session::get('locale', App::getLocale());
        }

        if (! array_key_exists((string) $candidate, $this->supportedLocales)) {
            $candidate = array_key_first($this->supportedLocales);
        }

        $this->locale = $candidate;
    }

    public function switchTo(string $lang)
    {
        if (! array_key_exists($lang, $this->supportedLocales)) {
            Toaster::error(__('Language not supported.'));

            return;
        }

        App::setLocale($lang);
        $this->locale = $lang;
        if ($this->user) {
            $pref = $this->user->preference()->firstOrNew(['user_id' => $this->user->id]);
            $pref->locale = $lang;
            $pref->save();
            $this->userPreference = $pref;
        }
        Session::put('locale', $lang);

        Toaster::success(__('Language switched successfully to ').$this->supportedLocales[$lang]);

        return $this->redirect(route('settings.preference'));
    }
}
