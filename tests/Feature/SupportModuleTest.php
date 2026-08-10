<?php

namespace Tests\Feature;

use App\Livewire\Support\QuestionsBot;
use App\Livewire\Support\TicketChat;
use App\Models\SupportChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class SupportModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_pages_require_an_authenticated_user(): void
    {
        $this->get(route('soporte.ticket'))->assertRedirect(route('login'));
        $this->get(route('soporte.preguntas'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_open_both_support_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('soporte.ticket'))
            ->assertOk()
            ->assertSee('Chat general de soporte');

        $this->actingAs($user)
            ->get(route('soporte.preguntas'))
            ->assertOk()
            ->assertSee('Asistente de preguntas');
    }

    public function test_chat_stores_the_message_with_its_authenticated_author(): void
    {
        $user = User::factory()->create([
            'name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.test',
        ]);
        RateLimiter::clear('support-chat:'.$user->id);

        Livewire::actingAs($user)
            ->test(TicketChat::class)
            ->set('message', 'Necesito ayuda con un reporte.')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSet('message', '')
            ->assertSee('Ana Pérez')
            ->assertSee('ana@example.test')
            ->assertSee('Necesito ayuda con un reporte.');

        $this->assertDatabaseHas('support_chat_messages', [
            'user_id' => $user->id,
            'message' => 'Necesito ayuda con un reporte.',
        ]);
    }

    public function test_all_users_can_read_messages_sent_today(): void
    {
        $author = User::factory()->create([
            'name' => 'Carlos',
            'email' => 'carlos@example.test',
        ]);
        $reader = User::factory()->create();

        SupportChatMessage::create([
            'user_id' => $author->id,
            'message' => 'Mensaje compartido para todos.',
        ]);

        Livewire::actingAs($reader)
            ->test(TicketChat::class)
            ->assertSee('Carlos')
            ->assertSee('carlos@example.test')
            ->assertSee('Mensaje compartido para todos.');
    }

    public function test_opening_the_chat_removes_messages_from_previous_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('support.timezone')));

        $user = User::factory()->create();
        $oldMessage = SupportChatMessage::create([
            'user_id' => $user->id,
            'message' => 'Mensaje del día anterior.',
        ]);
        $oldMessage->forceFill([
            'created_at' => Carbon::now(config('support.timezone'))->subDay()->utc(),
            'updated_at' => Carbon::now(config('support.timezone'))->subDay()->utc(),
        ])->save();

        SupportChatMessage::create([
            'user_id' => $user->id,
            'message' => 'Mensaje de hoy.',
        ]);

        Livewire::actingAs($user)
            ->test(TicketChat::class)
            ->assertDontSee('Mensaje del día anterior.')
            ->assertSee('Mensaje de hoy.');

        $this->assertDatabaseMissing('support_chat_messages', ['id' => $oldMessage->id]);

        Carbon::setTestNow();
    }

    public function test_questions_bot_returns_a_predefined_application_answer(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QuestionsBot::class)
            ->call('ask', 'horas', 'reloj_checador')
            ->assertSet('selectedQuestion', '¿Cómo consulto mis marcas del reloj checador?')
            ->assertSee('Revisar Horas')
            ->assertSee('opciones de exportación');
    }

    public function test_questions_bot_keeps_only_a_temporary_conversation_history(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QuestionsBot::class)
            ->call('ask', 'general', 'navegar')
            ->call('ask', 'horas', 'reloj_checador')
            ->assertCount('conversation', 2)
            ->assertSee('¿Cómo navego por la aplicación?')
            ->assertSee('¿Cómo consulto mis marcas del reloj checador?')
            ->call('resetConversation')
            ->assertSet('conversation', [])
            ->assertSet('selectedQuestion', null)
            ->assertSet('selectedAnswer', null);
    }
}
