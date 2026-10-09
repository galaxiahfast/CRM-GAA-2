<?php

namespace App\Livewire\Backups;

use App\Models\BackupUpload;
use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

class BackupStorageManager extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'merida';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('manage-system-backups');
        if (! in_array($this->activeTab, ['merida', 'tulum', 'history'], true)) {
            $this->activeTab = 'merida';
        }
    }

    public function selectTab(string $tab): void
    {
        abort_unless(in_array($tab, ['merida', 'tulum', 'history'], true), 404);
        $this->activeTab = $tab;
    }

    public function render()
    {
        Gate::authorize('manage-system-backups');

        $customers = Customer::query()
            ->when(filled($this->search), fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%'))
            ->orderBy('name')
            ->get(['id', 'name', 'last_name', 'maternal_last_name']);

        $site = in_array($this->activeTab, ['merida', 'tulum'], true) ? $this->activeTab : 'merida';
        $completed = BackupUpload::query()
            ->where('site', $site)
            ->where('status', BackupUpload::STATUS_COMPLETED)
            ->whereIn('customer_id', $customers->pluck('id'))
            ->latest('completed_at')
            ->get();
        $filesByCustomer = $completed->groupBy('customer_id');

        $history = BackupUpload::query()
            ->with(['customer:id,name', 'user:id,name,last_name'])
            ->when(filled($this->search), function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($nested) use ($term): void {
                    $nested->where('original_name', 'like', $term)
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', $term))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term)->orWhere('last_name', 'like', $term));
                });
            })
            ->latest()
            ->limit(150)
            ->get();

        $statusCounts = BackupUpload::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.backups.backup-storage-manager', [
            'customers' => $customers,
            'filesByCustomer' => $filesByCustomer,
            'history' => $history,
            'statusCounts' => $statusCounts,
            'sites' => config('backup-storage.sites'),
            'site' => $site,
            'uploadEndpoint' => route('activity-backups.uploads.initialize'),
        ])->layout('layouts.app');
    }
}
