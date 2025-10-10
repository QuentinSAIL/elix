<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $preference = UserPreference::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $preference->user);
        $this->assertEquals($user->id, $preference->user->id);
    }

    #[Test]
    public function test_fillable_attributes()
    {
        $data = [
            'user_id' => User::factory()->create()->id,
            'locale' => 'en_US',
            'timezone' => 'UTC',
            'theme_mode' => 'dark',
        ];

        $preference = new UserPreference($data);

        $this->assertEquals($data['user_id'], $preference->user_id);
        $this->assertEquals($data['locale'], $preference->locale);
        $this->assertEquals($data['timezone'], $preference->timezone);
        $this->assertEquals($data['theme_mode'], $preference->theme_mode);
    }
}
