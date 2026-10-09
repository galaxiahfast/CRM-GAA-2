<?php

namespace App\Livewire\Backups;

use App\Models\BackupUpload;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

class BackupStorageManager extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'backups';

    #[Url(as: 'site')]
    public string $selectedSite = 'merida';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('manage-system-backups');
        $sites = array_keys(config('backup-storage.sites', []));
        abort_if($sites === [], 500, 'No hay sedes configuradas para respaldos.');

        // Mantiene compatibles los enlaces anteriores que usaban ?tab=merida o ?tab=tulum.
        if (in_array($this->activeTab, $sites, true)) {
            $this->selectedSite = $this->activeTab;
            $this->activeTab = 'backups';
        }
        if (! in_array($this->activeTab, ['backups', 'history'], true)) {
            $this->activeTab = 'backups';
        }
        if (! in_array($this->selectedSite, $sites, true)) {
            $this->selectedSite = $sites[0];
        }
    }

    public function selectTab(string $tab): void
    {
        abort_unless(in_array($tab, ['backups', 'history'], true), 404);
        $this->activeTab = $tab;
    }

    public function selectSite(string $site): void
    {
        abort_unless(array_key_exists($site, config('backup-storage.sites', [])), 404);
        $this->selectedSite = $site;
        $this->activeTab = 'backups';
    }

    public function render()
    {
        Gate::authorize('manage-system-backups');

        $site = $this->selectedSite;
        $completed = BackupUpload::query()
            ->where('site', $site)
            ->where('status', BackupUpload::STATUS_COMPLETED)
            ->latest('completed_at')
            ->get();

        $term = mb_strtolower(trim($this->search));
        $backupTree = $completed
            ->flatMap(function (BackupUpload $upload): array {
                return collect($upload->manifest ?? [])->map(function (array $file, int $index) use ($upload): array {
                    return $file + [
                        'upload_id' => $upload->id,
                        'file_index' => $index,
                        'completed_at' => $upload->completed_at,
                        'download_url' => route('activity-backups.files.download', [$upload, $index]),
                    ];
                })->all();
            })
            ->when($term !== '', fn ($files) => $files->filter(fn (array $file) => str_contains(mb_strtolower(
                ($file['customer'] ?? '').' '.($file['name'] ?? '').' '.($file['category'] ?? '')
            ), $term)))
            ->groupBy('customer')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn ($files) => $files->groupBy('category'));

        $history = BackupUpload::query()
            ->with('user:id,name,last_name')
            ->latest()
            ->limit(300)
            ->get()
            ->when($term !== '', fn ($records) => $records->filter(function (BackupUpload $upload) use ($term): bool {
                $manifestText = collect($upload->manifest ?? [])->pluck('customer')->implode(' ');
                $text = $upload->original_name.' '.$upload->site.' '.$upload->user?->name.' '.$upload->user?->last_name.' '.$manifestText;

                return str_contains(mb_strtolower($text), $term);
            }))
            ->take(150);

        $statusCounts = BackupUpload::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.backups.backup-storage-manager', [
            'backupTree' => $backupTree,
            'history' => $history,
            'statusCounts' => $statusCounts,
            'sites' => config('backup-storage.sites'),
            'site' => $site,
            'uploadEndpoint' => route('activity-backups.uploads.initialize'),
        ])->layout('layouts.app');
    }
}
