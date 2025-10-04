<?php

namespace Tests\Feature\Livewire\Settings;

use App\Livewire\Settings\CurrencySwitcher;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @covers \App\Livewire\Settings\CurrencySwitcher
 */
class CurrencySwitcherTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    #[test]
    public function currency_switcher_component_can_be_rendered()
    {
        Livewire::test(CurrencySwitcher::class)
            ->assertStatus(200);
    }

    #[test]
    public function it_mounts_with_default_currency_if_no_preference()
    {
        Livewire::test(CurrencySwitcher::class)
            ->assertSet('currency', 'EUR');
    }

    #[test]
    public function it_mounts_with_user_preference_currency()
    {
        UserPreference::factory()->create(['user_id' => $this->user->id, 'currency' => 'USD']);

        Livewire::test(CurrencySwitcher::class)
            ->assertSet('currency', 'USD');
    }

    #[test]
    public function it_switches_to_supported_currency_and_saves_preference()
    {
        Livewire::test(CurrencySwitcher::class)
            ->call('switchTo', 'GBP')
            ->assertSet('currency', 'GBP')
            ->assertDispatched('toast', function ($name, $data) {
                return $data['type'] === 'success' && str_contains($data['message'], 'Currency switched successfully to British Pound (£)');
            });

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'currency' => 'GBP',
        ]);
    }

    #[test]
    public function it_does_not_switch_to_unsupported_currency_and_shows_error()
    {
        Livewire::test(CurrencySwitcher::class)
            ->call('switchTo', 'XYZ')
            ->assertSet('currency', 'EUR') // Should remain default or previous
            ->assertDispatched('toast', function ($name, $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'Currency not supported.');
            });

        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $this->user->id,
            'currency' => 'XYZ',
        ]);
    }
}
