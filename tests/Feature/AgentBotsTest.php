<?php

namespace Tests\Feature;

use App\Livewire\Home\OnlineUsers;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class AgentBotsTest extends TestCase
{
    use RefreshDatabase;

    private function makeBots(): void
    {
        $admin = Role::query()->create(['role' => 'Administrador', 'permission_profile' => Role::PROFILE_ADMINISTRATOR]);

        foreach ((array) config('support.bots') as $bot) {
            User::factory()->create([
                'name' => $bot['name'],
                'last_name' => $bot['last_name'],
                'email' => $bot['email'],
                'role_id' => $admin->id,
                'profile_description' => $bot['description'],
            ]);
        }
    }

    public function test_there_are_three_agents_and_the_support_one_replaces_sofia(): void
    {
        $bots = collect((array) config('support.bots'));

        $this->assertCount(3, $bots);
        $this->assertTrue($bots->every(fn (array $bot): bool => str_ends_with($bot['last_name'], '(BOT)')));
        $this->assertSame(['SOPORTE DEL CRM (BOT)', 'DISEÑO DEL CRM (BOT)', 'DESARROLLO DEL CRM (BOT)'], $bots->pluck('last_name')->all());
        $this->assertSame(config('support.bots.soporte.email'), config('support.automated_user.email'));
        $this->assertSame('LUCÍA', config('support.automated_user.name'));
        foreach ($bots as $bot) {
            $this->assertContains($bot['email'], config('support.always_online_emails'));
            $this->assertFileExists(public_path($bot['photo']));
            [$width, $height] = getimagesize(public_path($bot['photo']));
            $this->assertSame($width, $height, 'La foto debe ser cuadrada');
            $this->assertGreaterThanOrEqual(1024, $width);
        }
    }

    public function test_agents_are_always_online_first_with_what_they_do(): void
    {
        Cache::flush();
        $this->makeBots();
        $viewer = User::factory()->create();

        $rows = collect(Livewire::actingAs($viewer)->test(OnlineUsers::class)->viewData('rows'));

        $this->assertSame(
            ['IAN DESARROLLO DEL CRM (BOT)', 'LUCÍA SOPORTE DEL CRM (BOT)', 'MATEO DISEÑO DEL CRM (BOT)'],
            $rows->take(3)->pluck('name')->all()
        );
        $this->assertSame(['Escribiendo código', 'Resolviendo dudas', 'Creando pantallas'], $rows->take(3)->pluck('activity')->all());
        $this->assertFalse($rows->contains('id', 'sofia'));
        $this->assertSame(route('soporte.preguntas'), $rows->firstWhere('name', 'LUCÍA SOPORTE DEL CRM (BOT)')['url']);
    }
}
