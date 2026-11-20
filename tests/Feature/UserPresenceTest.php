<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_bots_are_always_online_and_everyone_else_only_while_using_the_web(): void
    {
        $bot = User::factory()->create(['email' => 'lucia.soporte@sistema.local', 'last_seen_at' => null]);
        $admin = User::factory()->create(['email' => 'administrador@datamid.com.mx', 'last_seen_at' => null]);
        $active = User::factory()->create(['last_seen_at' => now()->subMinute()]);
        $gone = User::factory()->create(['last_seen_at' => now()->subMinutes(User::ONLINE_MINUTES + 1)]);

        $online = User::query()->online()->pluck('id')->all();

        $this->assertContains($bot->id, $online);
        $this->assertContains($active->id, $online);
        $this->assertNotContains($admin->id, $online);
        $this->assertNotContains($gone->id, $online);
        $this->assertTrue($bot->isOnline());
        $this->assertFalse($admin->isOnline());
    }

    public function test_visiting_a_page_marks_the_person_online_in_that_section(): void
    {
        $user = User::factory()->create(['last_seen_at' => null, 'last_seen_section' => null]);

        $this->actingAs($user)->get(route('inicio'))->assertOk();
        $user->refresh();
        $this->assertTrue($user->isOnline());
        $this->assertSame('Inicio', $user->last_seen_section);

        $this->actingAs($user)->get(route('soporte.preguntas'))->assertOk();
        $this->assertSame('Preguntas', $user->fresh()->last_seen_section);
    }

    public function test_logging_out_takes_the_person_offline_right_away(): void
    {
        $user = User::factory()->create(['last_seen_at' => now()]);
        $this->assertTrue($user->isOnline());

        $this->actingAs($user)->post(route('logout'));

        $this->assertNull($user->fresh()->last_seen_at);
        $this->assertFalse($user->fresh()->isOnline());
    }
}
