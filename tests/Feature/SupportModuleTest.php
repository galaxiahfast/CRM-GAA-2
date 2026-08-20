<?php

namespace Tests\Feature;

use App\Livewire\Support\QuestionsBot;
use App\Livewire\Support\TicketChat;
use App\Models\Role;
use App\Models\SupportChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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
            ->assertSee('Chat general de soporte')
            ->assertSeeText('Soporte')
            ->assertSeeText('Centro de ayuda')
            ->assertSeeText('Ticket')
            ->assertSeeText('Descargar PDF')
            ->assertSeeText('Imprimir')
            ->assertSeeText('Buscar mensajes')
            ->assertSee('min-w-[1200px]', false)
            ->assertSee('grid-cols-[360px_minmax(0,1fr)]', false)
            ->assertSee('p-[50px]', false)
            ->assertDontSee('lg:grid-cols-', false)
            ->assertDontSee('overflow-x-auto', false)
            ->assertSee('support-message-input', false);

        $this->actingAs($user)
            ->get(route('soporte.preguntas'))
            ->assertOk()
            ->assertSee('Asistente de preguntas')
            ->assertSeeText('Soporte')
            ->assertSeeText('Centro de ayuda')
            ->assertSeeText('Preguntas')
            ->assertSee('min-w-[1200px]', false)
            ->assertSee('p-[50px]', false)
            ->assertSee('xl:grid-cols-[340px_420px_minmax(0,1fr)]', false)
            ->assertSee('group flex w-full items-center gap-[15px]', false)
            ->assertDontSee('group flex w-full items-start gap-3', false);
    }

    public function test_authenticated_user_can_download_the_daily_conversation_as_pdf(): void
    {
        $user = User::factory()->create([
            'name' => 'Laura',
            'last_name' => 'Méndez',
            'email' => 'laura@example.test',
        ]);

        SupportChatMessage::create([
            'user_id' => $user->id,
            'message' => 'Conversación incluida en el documento PDF.',
        ]);

        Livewire::actingAs($user)
            ->test(TicketChat::class)
            ->call('downloadPdf')
            ->assertFileDownloaded('conversacion-soporte-'.now(config('support.timezone'))->format('Y-m-d').'.pdf');
    }

    public function test_authenticated_user_can_download_the_questions_conversation_as_pdf(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 14:30:15', config('support.timezone')));
        $user = User::factory()->create([
            'name' => 'Marina',
            'last_name' => 'Manrique',
        ]);

        Livewire::actingAs($user)
            ->test(QuestionsBot::class)
            ->call('ask', 'general', 'perfil')
            ->call('downloadPdf')
            ->assertFileDownloaded('conversacion-asistente-2026-08-20-143015.pdf');

        Carbon::setTestNow();
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

    public function test_user_can_send_an_image_and_add_an_emoji(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        RateLimiter::clear('support-chat:'.$user->id);
        $image = UploadedFile::fake()->image('captura.png', 640, 480);

        $component = Livewire::actingAs($user)
            ->test(TicketChat::class)
            ->call('appendEmoji', '😊')
            ->assertSet('message', '😊')
            ->set('image', $image)
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSee('captura.png');

        $storedMessage = SupportChatMessage::query()
            ->where('user_id', $user->id)
            ->where('message', '😊')
            ->firstOrFail();

        $this->assertNotNull($storedMessage->image_path);
        $this->assertSame('captura.png', $storedMessage->image_original_name);
        Storage::disk('public')->assertExists($storedMessage->image_path);
        $component->assertSee('/storage/'.$storedMessage->image_path, false);
    }

    public function test_user_can_send_an_allowed_file_and_one_of_the_available_stickers(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        RateLimiter::clear('support-chat:'.$user->id);
        $attachment = UploadedFile::fake()->create('informe.txt', 24, 'text/plain');

        $component = Livewire::actingAs($user)
            ->test(TicketChat::class)
            ->set('attachment', $attachment)
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSee('informe.txt')
            ->call('sendSticker', 'ternura')
            ->assertSee('img/support/stickers/ternura.jpg', false)
            ->call('sendSticker', 'sticker-no-permitido');

        $storedFile = SupportChatMessage::query()
            ->where('user_id', $user->id)
            ->whereNotNull('attachment_path')
            ->firstOrFail();

        Storage::disk('public')->assertExists($storedFile->attachment_path);
        $this->assertSame('informe.txt', $storedFile->attachment_original_name);
        $this->assertSame(1, SupportChatMessage::query()->where('sticker_key', 'ternura')->count());
        $this->assertSame(0, SupportChatMessage::query()->where('sticker_key', 'sticker-no-permitido')->count());
        $component->assertSee('/storage/'.$storedFile->attachment_path, false);
    }

    public function test_users_can_react_to_messages_and_change_or_remove_their_reaction(): void
    {
        $author = User::factory()->create();
        $reactingUser = User::factory()->create();
        $message = SupportChatMessage::create([
            'user_id' => $author->id,
            'message' => 'Mensaje para reaccionar.',
        ]);

        $component = Livewire::actingAs($reactingUser)
            ->test(TicketChat::class)
            ->call('toggleReaction', $message->id, 'like')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('support_chat_message_reactions', [
            'support_chat_message_id' => $message->id,
            'user_id' => $reactingUser->id,
            'reaction' => 'like',
        ]);

        $component->call('toggleReaction', $message->id, 'heart');

        $this->assertDatabaseMissing('support_chat_message_reactions', [
            'support_chat_message_id' => $message->id,
            'user_id' => $reactingUser->id,
            'reaction' => 'like',
        ]);
        $this->assertDatabaseHas('support_chat_message_reactions', [
            'support_chat_message_id' => $message->id,
            'user_id' => $reactingUser->id,
            'reaction' => 'heart',
        ]);

        $component->call('toggleReaction', $message->id, 'dislike');

        $this->assertDatabaseMissing('support_chat_message_reactions', [
            'support_chat_message_id' => $message->id,
            'user_id' => $reactingUser->id,
            'reaction' => 'heart',
        ]);
        $this->assertDatabaseHas('support_chat_message_reactions', [
            'support_chat_message_id' => $message->id,
            'user_id' => $reactingUser->id,
            'reaction' => 'dislike',
        ]);

        $component->call('toggleReaction', $message->id, 'dislike');

        $this->assertDatabaseMissing('support_chat_message_reactions', [
            'support_chat_message_id' => $message->id,
            'user_id' => $reactingUser->id,
        ]);
    }

    public function test_automated_account_posts_one_greeting_per_time_period_and_stays_online(): void
    {
        $user = User::factory()->create();
        User::factory()->create([
            'name' => 'Sofía',
            'last_name' => 'Soporte',
            'email' => 'sofia.soporte@sistema.local',
        ]);
        User::factory()->create([
            'name' => 'Dioni',
            'last_name' => 'Colaborador',
            'email' => 'administrador@datamid.com.mx',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 09:00:00', config('support.timezone')));

        $component = Livewire::actingAs($user)
            ->test(TicketChat::class)
            ->assertSee('Sofia Soporte (bot)')
            ->assertSee('sofia.soporte@sistema.local')
            ->assertSee('img/support/sofia-avatar.svg', false)
            ->assertSee('11 ago. 2026')
            ->assertSee('Buenos días');

        $this->assertTrue(collect($component->get('onlineUsers'))->contains('email', 'sofia.soporte@sistema.local'));
        $this->assertTrue(collect($component->get('onlineUsers'))->contains('email', 'administrador@datamid.com.mx'));
        $this->assertDatabaseHas('users', [
            'email' => 'sofia.soporte@sistema.local',
            'name' => 'Sofia',
            'last_name' => 'Soporte (bot)',
        ]);

        $component->set('message', '@sof');
        $this->assertTrue(collect($component->get('mentionSuggestions'))->contains('email', 'sofia.soporte@sistema.local'));

        $this->assertDatabaseHas('support_chat_messages', [
            'message' => 'Buenos días',
            'automatic_key' => 'daily-greeting:2026-08-11:morning',
        ]);

        $component->call('refreshMessages')->call('refreshMessages');
        $this->assertSame(1, SupportChatMessage::query()->where('automatic_key', 'daily-greeting:2026-08-11:morning')->count());

        Carbon::setTestNow(Carbon::parse('2026-08-11 15:00:00', config('support.timezone')));
        $component->call('refreshMessages')->assertSee('Buenas tardes');

        Carbon::setTestNow(Carbon::parse('2026-08-11 21:00:00', config('support.timezone')));
        $component->call('refreshMessages')->assertSee('Buenas noches');

        $this->assertSame(3, SupportChatMessage::query()->whereNotNull('automatic_key')->count());

        Carbon::setTestNow();
    }

    public function test_sofia_only_answers_when_a_user_addresses_her_and_uses_local_rules(): void
    {
        $user = User::factory()->create(['name' => 'Marina']);
        RateLimiter::clear('support-chat:'.$user->id);

        $component = Livewire::actingAs($user)->test(TicketChat::class);

        $component
            ->set('message', 'Hola Carlos, ¿cómo estás?')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertSame(0, SupportChatMessage::query()->where('automatic_key', 'like', 'bot-reply:%')->count());

        $component
            ->set('message', 'Sofía, olvidé mi contraseña')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSee('Para cambiar tu contraseña');

        $question = SupportChatMessage::query()
            ->where('user_id', $user->id)
            ->where('message', 'Sofía, olvidé mi contraseña')
            ->firstOrFail();

        $this->assertDatabaseHas('support_chat_messages', [
            'automatic_key' => 'bot-reply:'.$question->id,
            'message' => 'Para cambiar tu contraseña, abre Mi cuenta, entra a Mi perfil y busca la sección Cambiar contraseña. Si olvidaste la actual, utiliza “¿Olvidaste tu contraseña?” en el inicio de sesión.',
        ]);
    }

    public function test_sofia_mentions_and_notifies_the_help_recipient_when_someone_reports_a_problem(): void
    {
        $sender = User::factory()->create(['name' => 'Laura']);
        $helpRecipient = User::factory()->create([
            'name' => 'Dioni',
            'last_name' => 'Colaborador',
            'email' => 'administrador@datamid.com.mx',
        ]);
        RateLimiter::clear('support-chat:'.$sender->id);

        Livewire::actingAs($sender)
            ->test(TicketChat::class)
            ->set('message', 'Sofía, necesito ayuda porque aparece un error')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSee('@dioni-colaborador-'.$helpRecipient->id);

        $notification = $helpRecipient->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('Te mencionaron en Soporte', $notification->data['title']);
        $this->assertStringContainsString('Sofia Soporte (bot) te mencionó', $notification->data['message']);
        $this->assertSame(route('soporte.ticket'), $notification->data['action_url']);

        $helpRecipient->update([
            'name' => 'Nuevo nombre',
            'last_name' => 'Actualizado',
            'email' => 'correo-nuevo@example.test',
        ]);

        Livewire::actingAs($sender)
            ->test(TicketChat::class)
            ->set('message', 'Sofía, el sistema no funciona y necesito soporte')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSee('@nuevo-nombre-actualizado-'.$helpRecipient->id);

        $this->assertTrue($helpRecipient->fresh()->is_support_help_recipient);
        $this->assertSame(2, $helpRecipient->fresh()->notifications()->count());

        $helpRecipient->delete();

        Livewire::actingAs($sender)
            ->test(TicketChat::class)
            ->set('message', 'Sofía, necesito ayuda con otra falla')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSee('Cuéntame brevemente qué apartado presenta el problema');

        $this->assertDatabaseMissing('users', ['id' => $helpRecipient->id]);
        $this->assertSame(3, SupportChatMessage::query()->where('automatic_key', 'like', 'bot-reply:%')->count());
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

    public function test_automatic_greeting_uses_the_application_storage_timezone(): void
    {
        config([
            'app.timezone' => 'America/Mexico_City',
            'support.timezone' => 'America/Mexico_City',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-11 13:22:00', 'America/Mexico_City'));
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(TicketChat::class);
        $greeting = collect($component->get('messages'))->firstWhere(
            'automatic_key',
            'daily-greeting:2026-08-11:afternoon'
        );

        $this->assertSame('13:22', $greeting['time']);
        $this->assertSame('Buenas tardes', $greeting['message']);

        Carbon::setTestNow();
    }

    public function test_existing_greeting_with_utc_shift_is_repaired_on_the_server(): void
    {
        config([
            'app.timezone' => 'America/Mexico_City',
            'support.timezone' => 'America/Mexico_City',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-11 13:30:00', 'America/Mexico_City'));
        $user = User::factory()->create();
        $sofia = User::factory()->create([
            'name' => 'Sofía',
            'last_name' => 'Soporte',
            'email' => 'sofia.soporte@sistema.local',
        ]);

        DB::table('support_chat_messages')->insert([
            'user_id' => $sofia->id,
            'message' => 'Buenas tardes',
            'automatic_key' => 'daily-greeting:2026-08-11:afternoon',
            'created_at' => '2026-08-11 19:22:00',
            'updated_at' => '2026-08-11 19:22:00',
        ]);

        $component = Livewire::actingAs($user)->test(TicketChat::class);
        $greeting = collect($component->get('messages'))->firstWhere(
            'automatic_key',
            'daily-greeting:2026-08-11:afternoon'
        );

        $this->assertSame('13:22', $greeting['time']);
        $this->assertDatabaseHas('support_chat_messages', [
            'automatic_key' => 'daily-greeting:2026-08-11:afternoon',
            'created_at' => '2026-08-11 13:22:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_users_can_delete_their_own_messages_but_not_messages_from_others(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownMessage = SupportChatMessage::create([
            'user_id' => $author->id,
            'message' => 'Mensaje que eliminaré.',
        ]);
        $otherMessage = SupportChatMessage::create([
            'user_id' => $otherUser->id,
            'message' => 'Mensaje de otra persona.',
        ]);

        Livewire::actingAs($author)
            ->test(TicketChat::class)
            ->call('deleteMessage', $ownMessage->id)
            ->assertSee('Mensaje eliminado')
            ->assertDontSee('Mensaje que eliminaré.');

        $this->assertSoftDeleted('support_chat_messages', ['id' => $ownMessage->id]);

        Livewire::actingAs($author)
            ->test(TicketChat::class)
            ->call('deleteMessage', $otherMessage->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted('support_chat_messages', ['id' => $otherMessage->id]);
    }

    public function test_administrators_can_delete_messages_from_any_user(): void
    {
        $administratorRole = Role::create([
            'role' => 'Administrador',
            'permission_profile' => Role::PROFILE_ADMINISTRATOR,
        ]);
        $administrator = User::factory()->create(['role_id' => $administratorRole->id]);
        $author = User::factory()->create();
        $message = SupportChatMessage::create([
            'user_id' => $author->id,
            'message' => 'Mensaje moderado por administración.',
        ]);

        Livewire::actingAs($administrator)
            ->test(TicketChat::class)
            ->call('deleteMessage', $message->id)
            ->assertSee('Mensaje eliminado')
            ->assertDontSee('Mensaje moderado por administración.');

        $this->assertSoftDeleted('support_chat_messages', ['id' => $message->id]);
    }

    public function test_profile_photos_are_rendered_in_ticket_chat(): void
    {
        $currentUser = User::factory()->create([
            'name' => 'Dioni',
            'email' => 'dioni-photo@example.test',
            'profile_photo_path' => 'profile-photos/dioni.png',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Laura',
            'email' => 'laura-photo@example.test',
            'profile_photo_path' => 'profile-photos/laura.png',
        ]);

        SupportChatMessage::create([
            'user_id' => $otherUser->id,
            'message' => 'Mensaje con fotografía de perfil.',
        ]);

        Livewire::actingAs($currentUser)
            ->test(TicketChat::class)
            ->assertSee('storage/profile-photos/dioni.png', false)
            ->assertSee('storage/profile-photos/laura.png', false)
            ->set('message', '@lau')
            ->assertSee('storage/profile-photos/laura.png', false);
    }

    public function test_users_can_search_and_select_a_mention_that_notifies_the_recipient(): void
    {
        $sender = User::factory()->create([
            'name' => 'Carlos',
            'last_name' => 'Gómez',
            'email' => 'carlos@example.test',
        ]);
        $recipient = User::factory()->create([
            'name' => 'Laura',
            'last_name' => 'Méndez',
            'email' => 'laura@example.test',
        ]);
        $unrelated = User::factory()->create();
        RateLimiter::clear('support-chat:'.$sender->id);

        $component = Livewire::actingAs($sender)
            ->test(TicketChat::class)
            ->set('message', 'Hola @lau')
            ->assertSee('Laura Méndez')
            ->assertSee('laura@example.test')
            ->call('selectMention', $recipient->id)
            ->assertSet('message', 'Hola @laura-mendez-'.$recipient->id.' ')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $component
            ->assertSee('@laura-mendez-'.$recipient->id)
            ->assertSee('<span class="font-semibold text-blue-600">@laura-mendez-'.$recipient->id.'</span>', false);

        $notification = $recipient->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('support', $notification->data['category']);
        $this->assertSame('Te mencionaron en Soporte', $notification->data['title']);
        $this->assertStringContainsString('Carlos Gómez te mencionó', $notification->data['message']);
        $this->assertSame(route('soporte.ticket'), $notification->data['action_url']);
        $this->assertSame(0, $unrelated->fresh()->notifications()->count());
    }

    public function test_everyone_mention_notifies_every_user_except_the_sender(): void
    {
        $sender = User::factory()->create([
            'name' => 'Coordinador',
            'email' => 'coordinador@example.test',
        ]);
        $firstRecipient = User::factory()->create();
        $secondRecipient = User::factory()->create();
        RateLimiter::clear('support-chat:'.$sender->id);

        Livewire::actingAs($sender)
            ->test(TicketChat::class)
            ->set('message', 'Aviso para @to')
            ->assertSee('Todos')
            ->assertSee('Notificar a todos los colaboradores')
            ->call('selectMention', 0)
            ->assertSet('message', 'Aviso para @todos ')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertSame(0, $sender->fresh()->notifications()->count());
        $this->assertSame('Mensaje para todos en Soporte', $firstRecipient->fresh()->notifications()->first()->data['title']);
        $this->assertSame('Mensaje para todos en Soporte', $secondRecipient->fresh()->notifications()->first()->data['title']);
    }

    public function test_sidebar_only_lists_users_with_recent_session_activity(): void
    {
        $currentUser = User::factory()->create([
            'name' => 'Usuario',
            'last_name' => 'Actual',
            'email' => 'actual@example.test',
        ]);
        $onlineUser = User::factory()->create([
            'name' => 'Persona',
            'last_name' => 'En Línea',
            'email' => 'online@example.test',
        ]);
        $offlineUser = User::factory()->create([
            'name' => 'Persona',
            'last_name' => 'Ausente',
            'email' => 'offline@example.test',
        ]);

        DB::table('sessions')->insert([
            [
                'id' => 'online-session',
                'user_id' => $onlineUser->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => '',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'offline-session',
                'user_id' => $offlineUser->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => '',
                'last_activity' => now()->subMinutes(3)->timestamp,
            ],
        ]);

        Livewire::actingAs($currentUser)
            ->test(TicketChat::class)
            ->assertSee('Personas en línea')
            ->assertSee('actual@example.test')
            ->assertSee('online@example.test')
            ->assertDontSee('offline@example.test');
    }

    public function test_chat_keeps_only_messages_from_the_current_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('support.timezone')));

        $user = User::factory()->create();
        $recentMessage = SupportChatMessage::create([
            'user_id' => $user->id,
            'message' => 'Mensaje del día anterior.',
        ]);
        $recentMessage->forceFill([
            'created_at' => Carbon::now(config('support.timezone'))->subDay()->utc(),
            'updated_at' => Carbon::now(config('support.timezone'))->subDay()->utc(),
        ])->save();

        $olderMessage = SupportChatMessage::create([
            'user_id' => $user->id,
            'message' => 'Mensaje de hace ocho días.',
        ]);
        $olderMessage->forceFill([
            'created_at' => Carbon::now(config('support.timezone'))->subDays(8)->utc(),
            'updated_at' => Carbon::now(config('support.timezone'))->subDays(8)->utc(),
        ])->save();

        SupportChatMessage::create([
            'user_id' => $user->id,
            'message' => 'Mensaje de hoy.',
        ]);

        Livewire::actingAs($user)
            ->test(TicketChat::class)
            ->assertDontSee('Mensaje del día anterior.')
            ->assertDontSee('Mensaje de hace ocho días.')
            ->assertSee('Mensaje de hoy.');

        $this->assertDatabaseMissing('support_chat_messages', ['id' => $recentMessage->id]);
        $this->assertDatabaseMissing('support_chat_messages', ['id' => $olderMessage->id]);

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
