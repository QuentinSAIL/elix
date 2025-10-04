<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * @covers \App\Http\Controllers\Auth\VerifyEmailController
 */
class VerifyEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    #[test]
    public function it_redirects_if_email_is_already_verified()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $request = EmailVerificationRequest::create('/email/verify/1/hash', ['id' => $user->id]);
        $request->setUserResolver(fn () => $user);

        $controller = new VerifyEmailController();
        $response = $controller($request);

        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    #[test]
    public function it_verifies_email_and_redirects()
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $request = EmailVerificationRequest::create('/email/verify/1/hash', ['id' => $user->id]);
        $request->setUserResolver(fn () => $user);

        $controller = new VerifyEmailController();
        $response = $controller($request);

        Event::assertDispatched(Verified::class);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }
}
