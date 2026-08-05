<?php

namespace Tests\Feature;

use App\Livewire\NotificationCenter;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SystemEventNotification;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_and_failed_logins_are_saved_in_the_users_mailbox(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertSame('Intento de acceso fallido', $user->fresh()->notifications()->first()->data['title']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertSame(2, $user->fresh()->notifications()->count());
        $this->assertContains(
            'Inicio de sesión exitoso',
            $user->fresh()->notifications->pluck('data.title')->all(),
        );
    }

    public function test_component_only_reads_and_updates_the_authenticated_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = new SystemEventNotification([
            'severity' => 'info',
            'title' => 'Aviso propio',
            'message' => 'Detalle',
        ]);

        $user->notify($notification);
        $otherUser->notify(new SystemEventNotification([
            'severity' => 'warning',
            'title' => 'Aviso ajeno',
            'message' => 'No debe mostrarse',
        ]));

        $ownNotification = $user->notifications()->first();
        $otherNotification = $otherUser->notifications()->first();

        Livewire::actingAs($user)
            ->test(NotificationCenter::class)
            ->assertSee('Aviso propio')
            ->assertDontSee('Aviso ajeno')
            ->call('markAsRead', $otherNotification->id)
            ->call('markAsRead', $ownNotification->id);

        $this->assertNull($otherNotification->fresh()->read_at);
        $this->assertNotNull($ownNotification->fresh()->read_at);
    }

    public function test_unhandled_web_error_gets_a_safe_page_and_notifies_the_administrator(): void
    {
        $adminRole = Role::query()->create([
            'role' => 'Administrador',
            'description' => 'Administración',
            'permission_profile' => Role::PROFILE_ADMINISTRATOR,
        ]);
        $administrator = User::factory()->create(['role_id' => $adminRole->id]);

        Route::middleware('web')->get('/__notification-error-test', function () {
            throw new RuntimeException('Falla controlada de prueba');
        });

        $this->actingAs($administrator)
            ->get('/__notification-error-test')
            ->assertStatus(500)
            ->assertSee('No pudimos completar la operación')
            ->assertDontSee('Falla controlada de prueba');

        $stored = $administrator->fresh()->notifications()->latest()->first();

        $this->assertNotNull($stored);
        $this->assertSame('Error del sistema', $stored->data['title']);
    }

    public function test_notification_storage_failure_does_not_escape_the_service(): void
    {
        $user = User::factory()->create();

        $this->app['db']->connection()->getSchemaBuilder()->drop('notifications');

        app(SystemNotificationService::class)->sendToUser($user, [
            'title' => 'No debe bloquear',
            'message' => 'La tabla no está disponible.',
        ]);

        $this->assertTrue(true);
    }

    public function test_scheduled_task_failure_notifies_administrators(): void
    {
        $adminRole = Role::query()->create([
            'role' => 'Administrador',
            'description' => 'Administración',
            'permission_profile' => Role::PROFILE_ADMINISTRATOR,
        ]);
        $administrator = User::factory()->create(['role_id' => $adminRole->id]);
        $task = app(Schedule::class)->call(static fn () => null)->name('proceso operativo de prueba');

        Event::dispatch(new ScheduledTaskFailed($task, new RuntimeException('Falla de tarea')));

        $stored = $administrator->fresh()->notifications()->first();

        $this->assertNotNull($stored);
        $this->assertSame('Error del sistema', $stored->data['title']);
        $this->assertSame('scheduler', $stored->data['context']['source']);
    }

    public function test_filters_notifications_by_read_status_and_event_type(): void
    {
        $user = User::factory()->create();

        $user->notify(new SystemEventNotification([
            'category' => 'auth',
            'severity' => 'success',
            'title' => 'Acceso reciente',
            'message' => 'Inicio correcto',
        ]));
        $user->notify(new SystemEventNotification([
            'category' => 'system',
            'severity' => 'error',
            'title' => 'Falla operativa',
            'message' => 'Proceso detenido',
        ]));

        $user->notifications()->where('data->severity', 'success')->first()->markAsRead();

        Livewire::actingAs($user)
            ->test(NotificationCenter::class)
            ->call('setFilter', 'unread')
            ->assertSee('Falla operativa')
            ->assertDontSee('Acceso reciente')
            ->call('setFilter', 'read')
            ->assertSee('Acceso reciente')
            ->assertDontSee('Falla operativa')
            ->call('setFilter', 'system')
            ->assertSee('Falla operativa')
            ->call('setFilter', 'auth')
            ->assertSee('Acceso reciente');
    }

    public function test_individual_and_bulk_deletion_cannot_delete_another_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        foreach (['Primera', 'Segunda'] as $title) {
            $user->notify(new SystemEventNotification([
                'category' => 'operation',
                'severity' => 'info',
                'title' => $title,
                'message' => 'Aviso propio',
            ]));
        }

        $otherUser->notify(new SystemEventNotification([
            'category' => 'operation',
            'severity' => 'warning',
            'title' => 'Aviso protegido',
            'message' => 'Pertenece a otra persona',
        ]));

        $ownIds = $user->notifications()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $otherId = (string) $otherUser->notifications()->first()->id;

        Livewire::actingAs($user)
            ->test(NotificationCenter::class)
            ->call('deleteNotification', $otherId)
            ->set('selected', [$ownIds[0], $otherId])
            ->call('deleteSelected');

        $this->assertDatabaseMissing('notifications', ['id' => $ownIds[0]]);
        $this->assertDatabaseHas('notifications', ['id' => $ownIds[1], 'notifiable_id' => $user->id]);
        $this->assertDatabaseHas('notifications', ['id' => $otherId, 'notifiable_id' => $otherUser->id]);
    }

    public function test_select_all_only_selects_the_visible_filtered_notifications(): void
    {
        $user = User::factory()->create();

        foreach (['Uno', 'Dos'] as $title) {
            $user->notify(new SystemEventNotification([
                'category' => 'operation',
                'severity' => 'info',
                'title' => $title,
                'message' => 'Pendiente',
            ]));
        }

        $readNotification = $user->notifications()->first();
        $readNotification->markAsRead();

        Livewire::actingAs($user)
            ->test(NotificationCenter::class)
            ->call('setFilter', 'unread')
            ->call('toggleSelectAll')
            ->assertSet('selected', [(string) $user->unreadNotifications()->first()->id])
            ->call('deleteSelected');

        $this->assertDatabaseHas('notifications', ['id' => $readNotification->id]);
        $this->assertSame(1, $user->fresh()->notifications()->count());
    }
}
