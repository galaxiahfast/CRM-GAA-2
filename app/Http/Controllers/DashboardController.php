<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $viewer = $request->user();
        $isAdmin = $viewer->isAdmin();
        [$start, $end] = $this->dateRangeFromRequest($request);

        $search = trim((string) $request->query('search', ''));
        $users = $isAdmin ? $this->searchUsers($search) : collect();
        $selectedUser = $isAdmin
            ? $this->selectedUser($request, $users)
            : $viewer;
        $chart = $selectedUser
            ? $this->workedTimeByDay($selectedUser, $start, $end)
            : $this->emptyChart($start, $end);

        return view('time-dashboard.index', [
            'isAdmin' => $isAdmin,
            'search' => $search,
            'users' => $users,
            'selectedUser' => $selectedUser,
            'start' => $start,
            'end' => $end,
            'labels' => $chart['labels'],
            'hours' => $chart['hours'],
            'averageHours' => $chart['averageHours'],
            'totalSeconds' => $chart['totalSeconds'],
            'clientLabels' => $chart['clientLabels'],
            'clientData' => $chart['clientData'],
            'clientIds' => $chart['clientIds'],
            'clientTotalSeconds' => $chart['clientTotalSeconds'],
            'activityLabels' => $chart['activityLabels'],
            'activityData' => $chart['activityData'],
            'activityIds' => $chart['activityIds'],
            'activityTotalSeconds' => $chart['activityTotalSeconds'],
            'topClientActivity' => $chart['topClientActivity'],
        ]);
    }

    /**
     * Obtiene los datos de una actividad específica para el gráfico
     */
    public function getActivityData(Request $request): JsonResponse
    {
        $user = $request->user();
        $activityId = $request->integer('activity_id');
        $start = Carbon::parse($request->query('fecha_inicio'));
        $end = Carbon::parse($request->query('fecha_fin'));

        Log::info('=== getActivityData ===', [
            'user_id' => $user->id,
            'activity_id' => $activityId,
            'start' => $start->toDateString(),
            'end' => $end->toDateString()
        ]);

        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('subService', function ($query) use ($activityId) {
                $query->where('id', $activityId);
            })
            ->with('intervals')
            ->get();

        Log::info('Entries found:', ['count' => $entries->count()]);

        $days = $this->daysBetween($start, $end);
        $secondsByDate = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(function ($dayEntries) {
                $total = (int) $dayEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds());
                Log::info('Day total:', [
                    'date' => $dayEntries->first()->entry_date,
                    'total_seconds' => $total,
                    'formatted' => gmdate('H:i:s', $total),
                    'entries' => $dayEntries->count()
                ]);
                return $total;
            });

        $hours = [];
        foreach ($days as $day) {
            $seconds = (int) ($secondsByDate[$day->toDateString()] ?? 0);
            $hours[] = round($seconds / 3600, 4);
        }

        return response()->json([
            'labels' => array_map(fn (Carbon $day) => $day->format('d/m/Y'), $days),
            'hours' => $hours,
            'totalSeconds' => array_sum($hours) * 3600,
        ]);
    }

    /**
     * Obtiene los datos de un cliente específico para el gráfico
     */
    public function getClientData(Request $request): JsonResponse
    {
        $user = $request->user();
        $clientId = $request->integer('client_id');
        $start = Carbon::parse($request->query('fecha_inicio'));
        $end = Carbon::parse($request->query('fecha_fin'));

        Log::info('=== getClientData ===', [
            'user_id' => $user->id,
            'client_id' => $clientId,
            'start' => $start->toDateString(),
            'end' => $end->toDateString()
        ]);

        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->where('customer_id', $clientId)
            ->with('intervals')
            ->get();

        Log::info('Entries found:', ['count' => $entries->count()]);

        $days = $this->daysBetween($start, $end);
        $secondsByDate = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(function ($dayEntries) {
                $total = (int) $dayEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds());
                Log::info('Day total:', [
                    'date' => $dayEntries->first()->entry_date,
                    'total_seconds' => $total,
                    'formatted' => gmdate('H:i:s', $total),
                    'entries' => $dayEntries->count()
                ]);
                return $total;
            });

        $hours = [];
        foreach ($days as $day) {
            $seconds = (int) ($secondsByDate[$day->toDateString()] ?? 0);
            $hours[] = round($seconds / 3600, 4);
        }

        return response()->json([
            'labels' => array_map(fn (Carbon $day) => $day->format('d/m/Y'), $days),
            'hours' => $hours,
            'totalSeconds' => array_sum($hours) * 3600,
        ]);
    }

    /**
     * Obtiene la combinación cliente-actividad con más horas trabajadas
     */
    private function getTopClientActivity(User $user, Carbon $start, Carbon $end): ?array
    {
        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->with(['intervals', 'customer', 'subService'])
            ->get();

        if ($entries->isEmpty()) {
            return null;
        }

        $grouped = $entries->groupBy(function ($entry) {
            return $entry->customer_id . '|' . ($entry->sub_service_id ?? 0);
        });

        $combinations = $grouped->map(function ($group) {
            $first = $group->first();
            $seconds = (int) $group->sum(fn(TimeEntry $entry) => $entry->calculateEffectiveSeconds());
            
            return [
                'client_id' => $first->customer_id,
                'client_name' => $first->customer->name ?? 'Sin cliente',
                'activity_id' => $first->sub_service_id,
                'activity_name' => $first->subService->sub_service ?? 'Sin actividad',
                'seconds' => $seconds,
            ];
        });

        $top = $combinations->sortByDesc('seconds')->first();

        Log::info('Top Client Activity:', [
            'client_id' => $top['client_id'] ?? null,
            'activity_id' => $top['activity_id'] ?? null,
            'seconds' => $top['seconds'] ?? 0,
            'formatted' => isset($top['seconds']) ? gmdate('H:i:s', $top['seconds']) : '0'
        ]);

        return $top;
    }

    /**
     * Obtiene los datos de un cliente + actividad específica para el gráfico
     */
    public function getClientActivityData(Request $request): JsonResponse
    {
        $user = $request->user();
        $clientId = $request->integer('client_id');
        $activityId = $request->integer('activity_id');
        $start = Carbon::parse($request->query('fecha_inicio'));
        $end = Carbon::parse($request->query('fecha_fin'));

        Log::info('=== getClientActivityData ===', [
            'user_id' => $user->id,
            'client_id' => $clientId,
            'activity_id' => $activityId,
            'start' => $start->toDateString(),
            'end' => $end->toDateString()
        ]);

        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->where('customer_id', $clientId)
            ->whereHas('subService', fn($q) => $q->where('id', $activityId))
            ->with('intervals')
            ->get();

        Log::info('Entries found:', ['count' => $entries->count()]);

        // ✅ LOG DE CADA INTERVALO CORREGIDO
        foreach ($entries as $entry) {
            $seconds = $entry->calculateEffectiveSeconds();
            Log::info('Entry detail:', [
                'entry_id' => $entry->id,
                'date' => $entry->entry_date,
                'total_seconds' => $seconds,
                'formatted' => gmdate('H:i:s', $seconds),
                'intervals_count' => $entry->intervals->count()
            ]);
            
            foreach ($entry->intervals as $interval) {
                // ✅ Usar abs() para obtener valor positivo
                $diff = $interval->started_at && $interval->ended_at 
                    ? abs($interval->ended_at->diffInSeconds($interval->started_at))
                    : 0;
                Log::info('Interval:', [
                    'started_at' => $interval->started_at,
                    'ended_at' => $interval->ended_at,
                    'diff_seconds' => $diff,
                    'formatted' => gmdate('H:i:s', $diff)
                ]);
            }
        }

        $days = $this->daysBetween($start, $end);
        $secondsByDate = $entries
            ->groupBy(fn(TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(function ($dayEntries) {
                $total = (int) $dayEntries->sum(fn(TimeEntry $entry) => $entry->calculateEffectiveSeconds());
                Log::info('Day total:', [
                    'date' => $dayEntries->first()->entry_date,
                    'total_seconds' => $total,
                    'formatted' => gmdate('H:i:s', $total),
                    'entries' => $dayEntries->count()
                ]);
                return $total;
            });

        $hours = [];
        foreach ($days as $day) {
            $seconds = (int) ($secondsByDate[$day->toDateString()] ?? 0);
            $hours[] = round($seconds / 3600, 4);
        }

        return response()->json([
            'labels' => array_map(fn(Carbon $day) => $day->format('d/m/Y'), $days),
            'hours' => $hours,
            'totalSeconds' => array_sum($hours) * 3600,
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function dateRangeFromRequest(Request $request): array
    {
        $timezone = $this->moduleTimezone();
        $defaultStart = Carbon::now($timezone)->startOfWeek();
        $defaultEnd = Carbon::now($timezone)->endOfWeek();

        $start = $this->parseDateInput((string) $request->query('fecha_inicio'), $defaultStart);
        $end = $this->parseDateInput((string) $request->query('fecha_fin'), $defaultEnd);

        if ($end->lessThan($start)) {
            return [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start->copy()->startOfDay(), $end->copy()->endOfDay()];
    }

    private function parseDateInput(string $value, Carbon $fallback): Carbon
    {
        if ($value === '') {
            return $fallback->copy();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value, $this->moduleTimezone());
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }

    private function selectedUser(Request $request, $users): ?User
    {
        $id = $request->integer('user_id');

        if ($id > 0) {
            return User::with('role')->find($id);
        }

        $search = trim((string) $request->query('search', ''));

        if ($search !== '' && ctype_digit($search)) {
            return User::with('role')->find((int) $search);
        }

        if ($search !== '' && $users->count() === 1) {
            return $users->first();
        }

        return null;
    }

    private function searchUsers(string $search)
    {
        return User::with('role')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('id', $search)
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /** @return array{labels: array<int, string>, hours: array<int, float>, averageHours: array<int, float>, totalSeconds: int, clientLabels: array<int, string>, clientData: array<int, float>, clientIds: array<int, int>, clientTotalSeconds: int, activityLabels: array<int, string>, activityData: array<int, float>, activityTotalSeconds: int, topClientActivity: ?array} */
    private function workedTimeByDay(User $user, Carbon $start, Carbon $end): array
    {
        Log::info('=== workedTimeByDay ===', [
            'user_id' => $user->id,
            'start' => $start->toDateString(),
            'end' => $end->toDateString()
        ]);

        $days = $this->daysBetween($start, $end);
        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->with(['intervals', 'customer', 'subService'])
            ->get();

        Log::info('Total entries found:', ['count' => $entries->count()]);

        // ✅ LOG DE CADA ENTRADA Y SUS INTERVALOS CORREGIDO
        foreach ($entries as $entry) {
            $seconds = $entry->calculateEffectiveSeconds();
            Log::info('Entry detail:', [
                'entry_id' => $entry->id,
                'date' => $entry->entry_date,
                'customer' => $entry->customer->name ?? 'Sin cliente',
                'activity' => $entry->subService->sub_service ?? 'Sin actividad',
                'total_seconds' => $seconds,
                'formatted' => gmdate('H:i:s', $seconds),
                'intervals_count' => $entry->intervals->count()
            ]);
            
            foreach ($entry->intervals as $interval) {
                // ✅ Usar abs() para obtener valor positivo
                $diff = $interval->started_at && $interval->ended_at 
                    ? abs($interval->ended_at->diffInSeconds($interval->started_at))
                    : 0;
                Log::info('Interval:', [
                    'started_at' => $interval->started_at,
                    'ended_at' => $interval->ended_at,
                    'diff_seconds' => $diff,
                    'formatted' => gmdate('H:i:s', $diff)
                ]);
            }
        }

        $secondsByDate = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(fn ($dayEntries) => (int) $dayEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds()));

        $clientGroups = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->customer_id)
            ->map(function ($clientEntries) {
                $total = (int) $clientEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds());
                Log::info('Client group:', [
                    'client_id' => $clientEntries->first()->customer_id,
                    'client_name' => $clientEntries->first()->customer->name ?? 'Sin cliente',
                    'total_seconds' => $total,
                    'formatted' => gmdate('H:i:s', $total)
                ]);
                return [
                    'id' => $clientEntries->first()->customer_id,
                    'name' => $clientEntries->first()->customer->name ?? 'Sin cliente',
                    'seconds' => $total,
                ];
            })
            ->sortByDesc('seconds');

        $clientLabels = $clientGroups->pluck('name')->values()->toArray();
        $clientData = $clientGroups->map(fn($c) => round($c['seconds'] / 3600, 4))->values()->toArray();
        $clientIds = $clientGroups->pluck('id')->values()->toArray();
        $clientTotalSeconds = $clientGroups->sum('seconds');

        // ✅ AHORA GUARDAMOS LOS IDs REALES DE LAS ACTIVIDADES
        $secondsByActivity = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->subService->sub_service ?? 'Sin actividad')
            ->map(function ($activityEntries) {
                $first = $activityEntries->first();
                return [
                    'name' => $first->subService->sub_service ?? 'Sin actividad',
                    'id' => $first->sub_service_id ?? 0,
                    'seconds' => (int) $activityEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds()),
                ];
            })
            ->sortByDesc('seconds');

        // ✅ Extraer los arrays con los IDs reales
        $activityLabels = $secondsByActivity->pluck('name')->values()->toArray();
        $activityData = $secondsByActivity->map(fn($a) => round($a['seconds'] / 3600, 4))->values()->toArray();
        $activityIds = $secondsByActivity->pluck('id')->values()->toArray(); // ✅ IDs reales

        $totalSeconds = 0;
        $hours = [];

        foreach ($days as $day) {
            $seconds = (int) ($secondsByDate[$day->toDateString()] ?? 0);
            $totalSeconds += $seconds;
            $hours[] = round($seconds / 3600, 4);
        }

        $average = count($days) > 0 ? round(($totalSeconds / 3600) / count($days), 4) : 0;

        $maxActivities = 8;
        $topActivities = $secondsByActivity->take($maxActivities);
        $othersActivitySum = $secondsByActivity->skip($maxActivities)->sum('seconds');

        $activityLabels = $topActivities->pluck('name')->values()->toArray();
        $activityData = $topActivities->map(fn($a) => round($a['seconds'] / 3600, 4))->values()->toArray();
        $activityIds = $topActivities->pluck('id')->values()->toArray();

        if ($othersActivitySum > 0) {
            $activityLabels[] = 'Otras';
            $activityData[] = round($othersActivitySum / 3600, 4);
            $activityIds[] = 0; // ID 0 para "Otras"
        }

        $topClientActivity = $this->getTopClientActivity($user, $start, $end);

        return [
            'labels' => array_map(fn (Carbon $day) => $day->format('d/m/Y'), $days),
            'hours' => $hours,
            'averageHours' => array_fill(0, count($days), $average),
            'totalSeconds' => $totalSeconds,
            'clientLabels' => $clientLabels,
            'clientData' => $clientData,
            'clientIds' => $clientIds,
            'clientTotalSeconds' => $clientTotalSeconds,
            'activityLabels' => $activityLabels,
            'activityData' => $activityData,
            'activityIds' => $activityIds, // ✅ NUEVO: IDs reales de actividades
            'activityTotalSeconds' => $secondsByActivity->sum('seconds'),
            'topClientActivity' => $topClientActivity,
        ];
    }

    /** @return array{labels: array<int, string>, hours: array<int, float>, averageHours: array<int, float>, totalSeconds: int, clientLabels: array<int, string>, clientData: array<int, float>, clientIds: array<int, int>, clientTotalSeconds: int, activityLabels: array<int, string>, activityData: array<int, float>, activityTotalSeconds: int, topClientActivity: ?array} */
    private function emptyChart(Carbon $start, Carbon $end): array
    {
        $days = $this->daysBetween($start, $end);

        return [
            'labels' => array_map(fn (Carbon $day) => $day->format('d/m/Y'), $days),
            'hours' => array_fill(0, count($days), 0),
            'averageHours' => array_fill(0, count($days), 0),
            'totalSeconds' => 0,
            'clientLabels' => [],
            'clientData' => [],
            'clientIds' => [],
            'clientTotalSeconds' => 0,
            'activityLabels' => [],
            'activityData' => [],
            'activityTotalSeconds' => 0,
            'topClientActivity' => null,
        ];
    }

    /** @return array<int, Carbon> */
    private function daysBetween(Carbon $start, Carbon $end): array
    {
        $days = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        return $days;
    }

    private function moduleTimezone(): string
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');

        return $timezone === 'UTC' ? 'America/Mexico_City' : $timezone;
    }
}