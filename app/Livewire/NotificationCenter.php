<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

class NotificationCenter extends Component
{
    public string $filter = 'all';

    public bool $selectionMode = false;

    /** @var array<int, string> */
    public array $selected = [];

    private const FILTERS = ['all', 'unread', 'read', 'system', 'auth'];

    public function markAsRead(string $notificationId): void
    {
        $notification = $this->ownedNotification($notificationId);

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, self::FILTERS, true)) {
            return;
        }

        $this->filter = $filter;
        $this->selected = [];
    }

    public function toggleSelectionMode(): void
    {
        $this->selectionMode = ! $this->selectionMode;
        $this->selected = [];
    }

    public function toggleSelectAll(): void
    {
        $visibleIds = $this->filteredNotificationsQuery()
            ?->limit(20)
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all() ?? [];

        if ($visibleIds === []) {
            $this->selected = [];

            return;
        }

        $selectedVisible = array_intersect($visibleIds, $this->selected);

        if (count($selectedVisible) === count($visibleIds)) {
            $this->selected = array_values(array_diff($this->selected, $visibleIds));

            return;
        }

        $this->selected = array_values(array_unique([...$this->selected, ...$visibleIds]));
    }

    public function deleteNotification(string $notificationId): void
    {
        $this->ownedNotification($notificationId)?->delete();
        $this->selected = array_values(array_diff($this->selected, [$notificationId]));
    }

    public function deleteSelected(): void
    {
        if ($this->selected === []) {
            return;
        }

        auth()->user()?->notifications()
            ->whereIn('id', array_values(array_unique($this->selected)))
            ->delete();

        $this->selected = [];
    }

    public function render(): View
    {
        $query = $this->filteredNotificationsQuery();
        $notifications = $query?->limit(20)->get() ?? collect();
        $visibleIds = $notifications->modelKeys();
        $selectedVisible = array_intersect($visibleIds, $this->selected);

        return view('livewire.notification-center', [
            'notifications' => $notifications,
            'unreadCount' => auth()->user()?->unreadNotifications()->count() ?? 0,
            'totalCount' => auth()->user()?->notifications()->count() ?? 0,
            'allVisibleSelected' => $visibleIds !== [] && count($selectedVisible) === count($visibleIds),
        ]);
    }

    private function ownedNotification(string $notificationId): ?DatabaseNotification
    {
        return auth()->user()?->notifications()->whereKey($notificationId)->first();
    }

    private function filteredNotificationsQuery(): ?MorphMany
    {
        $query = auth()->user()?->notifications()->latest();

        if (! $query) {
            return null;
        }

        return match ($this->filter) {
            'unread' => $query->whereNull('read_at'),
            'read' => $query->whereNotNull('read_at'),
            'system' => $query->where('data->severity', 'error'),
            'auth' => $query->where(function ($query) {
                $query->where('data->category', 'auth')
                    ->orWhere('data->category', 'security');
            }),
            default => $query,
        };
    }
}
