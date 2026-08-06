<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionContinuityTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_covers_the_complete_operational_day(): void
    {
        $this->assertSame(1200, config('session.lifetime'));
        $this->assertFalse((bool) config('session.expire_on_close'));
        $this->assertSame('03:00', config('time-control.workday_starts_at'));
        $this->assertSame('21:00', config('time-control.auto_close_at'));
    }

    public function test_keep_alive_requires_authentication(): void
    {
        $this->getJson(route('session.keep-alive'))->assertUnauthorized();
    }

    public function test_authenticated_user_can_keep_session_alive(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('session.keep-alive'))
            ->assertNoContent();
    }
}
