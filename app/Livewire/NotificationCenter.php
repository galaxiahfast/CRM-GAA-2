<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

class NotificationCenter extends Component
{
    public string $filter = 'all';

    public bool $notificationsLoaded = false;

    public bool $selectionMode = false;

    /** @var array<int, string> */
    public array $selected = [];

    private const FILTERS = ['all', 'unread', 'read', 'system', 'auth'];

    public function loadNotifications(): void
    {
        $this->notificationsLoaded = true;
    }

    public function markAsRead(string $notificationId): void
    {
        $this->loadNotifications();
        $notification = $this->ownedNotification($notificationId);

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        $this->loadNotifications();
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, self::FILTERS, true)) {
            return;
        }

        $this->loadNotifications();
        $this->filter = $filter;
        $this->selected = [];
    }

    public function toggleSelectionMode(): void
    {
        $this->loadNotifications();
        $this->selectionMode = ! $this->selectionMode;
        $this->selected = [];
    }

    public function toggleSelectAll(): void
    {
        $this->loadNotifications();
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
        $this->loadNotifications();
        $this->ownedNotification($notificationId)?->delete();
        $this->selected = array_values(array_diff($this->selected, [$notificationId]));
    }

    public function deleteSelected(): void
    {
        $this->loadNotifications();

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
        $notifications = $this->notificationsLoaded
            ? ($this->filteredNotificationsQuery()?->limit(20)->get() ?? collect())
            : collect();
        $visibleIds = $notifications->pluck('id')->all();
        $selectedVisible = array_intersect($visibleIds, $this->selected);
        $counts = auth()->user()?->notifications()
            // The Notifications relationship is ordered by newest first. MySQL
            // rejects that ORDER BY when this query only contains aggregates.
            ->reorder()
            ->toBase()
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread_count')
            ->first();

        return view('livewire.notification-center', [
            'notifications' => $notifications,
            'unreadCount' => (int) ($counts->unread_count ?? 0),
            'totalCount' => (int) ($counts->total_count ?? 0),
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
