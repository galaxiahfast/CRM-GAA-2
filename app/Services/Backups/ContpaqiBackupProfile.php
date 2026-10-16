<?php

namespace App\Services\Backups;

use Illuminate\Support\Str;

class ContpaqiBackupProfile
{
    public function profiles(bool $includeDisabled = false): array
    {
        return collect((array) config('backup-storage.backup_profiles', []))
            ->map(fn (array $profile, string $key): array => $profile + ['key' => $key])
            ->when(! $includeDisabled, fn ($profiles) => $profiles->where('enabled', true))
            ->values()
            ->all();
    }

    public function defaultKey(): string
    {
        return (string) (collect($this->profiles())->first()['key'] ?? 'merida-contabilidad');
    }

    public function profile(?string $key): array
    {
        return collect($this->profiles(true))->firstWhere('key', $key)
            ?? collect($this->profiles())->first()
            ?? ['key' => '', 'label' => 'Sin configuración', 'classification' => 'all'];
    }

    public function dashboard(array $dashboard, ?string $key): array
    {
        $profile = $this->profile($key);
        $records = collect($dashboard['records'] ?? [])->filter(fn (array $record): bool => $this->matches($record, $profile))->values();
        $todayRecords = collect($dashboard['today_records'] ?? [])->filter(fn (array $record): bool => $this->matches($record, $profile))->values();
        $allRecords = collect($dashboard['all_records'] ?? $records);

        if (isset($dashboard['all_records'])) {
            $allRecords = $allRecords->filter(fn (array $record): bool => $this->matches($record, $profile))->values();
        }

        $companyKeys = $allRecords->pluck('company_key')->filter()->unique();
        $companies = collect($dashboard['companies'] ?? [])->whereIn('key', $companyKeys)->values();
        $dates = $allRecords->groupBy('date')->map(function ($items, string $date): array {
            return [
                'date' => $date,
                'total' => $items->count(),
                'complete' => $items->where('complete', true)->count(),
                'partial' => $items->where('complete', false)->count(),
                'size' => (int) $items->sum('total_size'),
            ];
        })->sortKeysDesc()->values();
        $lastByCompany = $allRecords->filter(fn (array $record): bool => filled($record['company_key'] ?? null))
            ->sortByDesc('occurred_at')->groupBy('company_key')->map->first()->all();

        return array_merge($dashboard, [
            'profile' => $profile,
            'records' => $records->all(),
            'today_records' => $todayRecords->all(),
            'companies' => $companies->all(),
            'dates' => $dates->all(),
            'last_by_company' => $lastByCompany,
        ]);
    }

    public function matches(array $record, array $profile): bool
    {
        $classification = (string) ($profile['classification'] ?? 'all');
        if ($classification === 'all') {
            return true;
        }

        $folder = (string) ($record['company_folder'] ?? '');
        $text = Str::ascii(implode(' ', [
            $record['company_raw'] ?? '',
            $record['company'] ?? '',
            $record['stem'] ?? '',
        ]));
        $isPayroll = preg_match('/^nt/i', $folder) === 1
            || preg_match('/^ct\d+_/i', $folder) === 1
            || preg_match('/(?:^|[\s_-])(?:Q\d+|S\s*#?\s*\d+|SEM(?:ANA|ANAL)?\w*\s*#?\s*\d*|QUINCEN\w*|MENSUAL\w*|PTU)(?:[\s_-]|$)/i', $text) === 1;

        return $classification === 'payroll' ? $isPayroll : ! $isPayroll;
    }
}
