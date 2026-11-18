<?php

namespace Tests\Feature;

use App\Livewire\Chat\DirectChat;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class DirectChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_floating_chat_appears_for_signed_in_users_on_every_screen(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('inicio'))->assertOk()->assertSee('data-direct-chat', false)->assertSee('Mensajes');
    }

    public function test_it_starts_minimized_and_can_be_expanded_and_minimized_remembering_its_state(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(DirectChat::class)
            ->assertSet('expanded', false)
            ->call('toggle')
            ->assertSet('expanded', true)
            ->assertSee('Minimizar')
            ->call('minimize')
            ->assertSet('expanded', false);

        // El estado queda en la sesión: al cambiar de pantalla sigue como se dejó
        Livewire::test(DirectChat::class)->call('toggle');
        Livewire::test(DirectChat::class)->assertSet('expanded', true);
    }

    public function test_a_user_can_message_another_and_the_other_sees_it_unread_then_read(): void
    {
        RateLimiter::clear('direct-chat:1');
        $ana = User::factory()->create(['name' => 'Ana', 'last_name' => 'Pérez']);
        $luis = User::factory()->create(['name' => 'Luis', 'last_name' => 'Gómez']);

        $this->actingAs($ana);
        Livewire::test(DirectChat::class)
            ->call('openConversation', $luis->id)
            ->assertSet('activeUserId', $luis->id)
            ->assertSet('expanded', true)
            ->set('message', 'Hola Luis, ¿tienes un minuto?')
            ->call('send')
            ->assertSet('message', '')
            ->assertSee('Hola Luis, ¿tienes un minuto?');

        $this->assertDatabaseHas('direct_messages', ['sender_id' => $ana->id, 'recipient_id' => $luis->id, 'read_at' => null]);

        // Luis lo ve como mensaje sin leer en el chat minimizado y en la lista de conversaciones
        session()->forget('direct_chat');
        $this->actingAs($luis);
        Livewire::test(DirectChat::class)
            ->assertSee('Ana Pérez')
            ->assertSee('Hola Luis, ¿tienes un minuto?')
            ->assertSeeHtml('dc-badge');
        Livewire::test(DirectChat::class)
            ->call('toggle')
            ->assertSee('Ana Pérez')
            ->assertSee('Hola Luis, ¿tienes un minuto?')
            ->call('openConversation', $ana->id)
            ->assertSee('Hola Luis, ¿tienes un minuto?');

        $this->assertNotNull(DirectMessage::firstOrFail()->read_at);
    }

    public function test_empty_long_and_self_messages_are_not_sent(): void
    {
        $ana = User::factory()->create();
        $luis = User::factory()->create();
        $this->actingAs($ana);
        RateLimiter::clear('direct-chat:'.$ana->id);

        Livewire::test(DirectChat::class)
            ->call('openConversation', $luis->id)
            ->set('message', '   ')
            ->call('send')
            ->assertHasErrors('message')
            ->set('message', str_repeat('a', 1001))
            ->call('send')
            ->assertHasErrors('message')
            ->call('openConversation', $ana->id)
            ->assertSet('activeUserId', $luis->id);

        $this->assertSame(0, DirectMessage::count());
    }

    public function test_only_the_two_people_in_a_conversation_see_its_messages(): void
    {
        $ana = User::factory()->create();
        $luis = User::factory()->create(['name' => 'Luis']);
        $intruder = User::factory()->create(['name' => 'Intruso']);
        DirectMessage::create(['sender_id' => $ana->id, 'recipient_id' => $luis->id, 'body' => 'Mensaje privado entre dos']);

        $this->actingAs($intruder);
        Livewire::test(DirectChat::class)
            ->call('openConversation', $ana->id)
            ->assertDontSee('Mensaje privado entre dos');
    }

    public function test_people_can_be_searched_and_the_profile_button_opens_the_conversation(): void
    {
        $this->actingAs(User::factory()->create());
        $target = User::factory()->create(['name' => 'Buscada', 'last_name' => 'Persona']);

        Livewire::test(DirectChat::class)
            ->call('toggle')
            ->set('search', 'Buscada')
            ->assertSee('Buscada Persona')
            ->dispatch('open-direct-chat', userId: $target->id)
            ->assertSet('activeUserId', $target->id)
            ->assertSet('search', '');
    }

    public function test_it_shows_when_the_other_person_is_typing_in_the_conversation_and_in_the_notice(): void
    {
        $ana = User::factory()->create(['name' => 'Ana', 'last_name' => 'Pérez']);
        $luis = User::factory()->create(['name' => 'Luis', 'last_name' => 'Gómez']);
        DirectMessage::create(['sender_id' => $ana->id, 'recipient_id' => $luis->id, 'body' => 'Mensaje previo']);

        // Ana escribe a Luis
        $this->actingAs($ana);
        $anaChat = Livewire::test(DirectChat::class)->call('openConversation', $luis->id)->set('message', 'Estoy escribiendo...');

        // Luis, con el chat minimizado, ve el aviso de que Ana escribe; ya abierto, lo ve en la conversación
        session()->forget('direct_chat');
        $this->actingAs($luis);
        Livewire::test(DirectChat::class)->assertSee('escribiendo');
        Livewire::test(DirectChat::class)->call('openConversation', $ana->id)->assertSee('Ana Pérez está escribiendo');

        // Al borrar lo escrito deja de aparecer
        $this->actingAs($ana);
        $anaChat->set('message', '');
        $this->actingAs($luis);
        Livewire::test(DirectChat::class)->call('openConversation', $ana->id)->assertDontSee('está escribiendo');
    }

    public function test_the_list_never_cuts_rows_and_the_search_has_no_section_titles(): void
    {
        $this->actingAs(User::factory()->create());
        User::factory()->create(['name' => 'Persona', 'last_name' => 'Visible']);

        Livewire::test(DirectChat::class)
            ->call('toggle')
            ->set('search', 'Persona')
            ->assertSee('Persona Visible')
            ->assertDontSee('Resultados')
            ->assertSeeHtml('h-[400px]');
    }

    public function test_sending_a_message_notifies_once_per_conversation_and_the_notice_opens_the_chat(): void
    {
        $ana = User::factory()->create(['name' => 'Ana', 'last_name' => 'Pérez']);
        $luis = User::factory()->create(['name' => 'Luis']);
        $this->actingAs($ana);
        RateLimiter::clear('direct-chat:'.$ana->id);

        Livewire::test(DirectChat::class)->call('openConversation', $luis->id)
            ->set('message', 'Primer mensaje')->call('send')
            ->set('message', 'Segundo mensaje')->call('send');

        // Una sola notificación mientras Luis no lea, con el nombre de quien escribe y un enlace que abre el chat
        $notices = $luis->notifications()->get();
        $this->assertCount(1, $notices);
        $this->assertSame('Nuevo mensaje de Ana Pérez', $notices[0]->data['title']);
        $this->assertSame('Primer mensaje', $notices[0]->data['message']);
        $this->assertStringContainsString('chat='.$ana->id, $notices[0]->data['action_url']);

        // Al abrir el enlace de la notificación, el chat queda abierto en esa conversación
        session()->forget('direct_chat');
        $this->actingAs($luis);
        $this->get(route('inicio', ['chat' => $ana->id]))->assertOk()->assertSee('Ana Pérez');
    }

    public function test_minimizing_is_remembered_so_a_reload_does_not_bring_it_back_expanded(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(DirectChat::class)->call('setExpanded', true);
        Livewire::test(DirectChat::class)->assertSet('expanded', true);

        // El navegador minimiza al instante y avisa al servidor: al recargar sigue minimizado
        Livewire::test(DirectChat::class)->call('setExpanded', false);
        Livewire::test(DirectChat::class)->assertSet('expanded', false);
    }

    public function test_opening_a_conversation_tells_the_browser_to_expand_the_chat(): void
    {
        $this->actingAs(User::factory()->create());
        $target = User::factory()->create();

        Livewire::test(DirectChat::class)
            ->call('setExpanded', false)
            ->call('openConversation', $target->id)
            ->assertDispatched('direct-chat-opened')
            ->assertSet('expanded', true);
    }

    public function test_online_status_matches_the_home_screen(): void
    {
        $me = User::factory()->create();
        $recent = User::factory()->create(['name' => 'Reciente', 'last_seen_at' => now()->subMinute()]);
        $away = User::factory()->create(['name' => 'Ausente', 'last_seen_at' => now()->subMinutes(30)]);
        $always = User::factory()->create(['email' => 'siempre@example.test']);
        config(['support.always_online_emails' => ['siempre@example.test']]);
        $this->actingAs($me);

        Livewire::test(DirectChat::class)->call('openConversation', $recent->id)->assertSet('activeUserId', $recent->id)->assertSee('En línea');
        Livewire::test(DirectChat::class)->call('openConversation', $away->id)->assertSee('Desconectado');
        Livewire::test(DirectChat::class)->call('openConversation', $always->id)->assertSee('En línea');
    }
}
