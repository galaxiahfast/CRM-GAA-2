<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

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
            'activityTotalSeconds' => $chart['activityTotalSeconds'],
        ]);
    }

    /**
     * Obtiene los datos de una actividad específica para el gráfico
     */
    public function getActivityData(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $activityId = $request->integer('activity_id');
        $start = Carbon::parse($request->query('fecha_inicio'));
        $end = Carbon::parse($request->query('fecha_fin'));

        // Obtener las entradas de esa actividad
        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('subService', function ($query) use ($activityId) {
                $query->where('id', $activityId);
            })
            ->with('intervals')
            ->get();

        // Agrupar por día
        $days = $this->daysBetween($start, $end);
        $secondsByDate = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(fn ($dayEntries) => (int) $dayEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds()));

        $hours = [];
        foreach ($days as $day) {
            $seconds = (int) ($secondsByDate[$day->toDateString()] ?? 0);
            $hours[] = round($seconds / 3600, 2);
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
    public function getClientData(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $clientId = $request->integer('client_id');
        $start = Carbon::parse($request->query('fecha_inicio'));
        $end = Carbon::parse($request->query('fecha_fin'));

        // Obtener las entradas de ese cliente
        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->where('customer_id', $clientId)
            ->with('intervals')
            ->get();

        // Agrupar por día
        $days = $this->daysBetween($start, $end);
        $secondsByDate = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(fn ($dayEntries) => (int) $dayEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds()));

        $hours = [];
        foreach ($days as $day) {
            $seconds = (int) ($secondsByDate[$day->toDateString()] ?? 0);
            $hours[] = round($seconds / 3600, 2);
        }

        return response()->json([
            'labels' => array_map(fn (Carbon $day) => $day->format('d/m/Y'), $days),
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

    /** @return array{labels: array<int, string>, hours: array<int, float>, averageHours: array<int, float>, totalSeconds: int, clientLabels: array<int, string>, clientData: array<int, float>, clientIds: array<int, int>, clientTotalSeconds: int, activityLabels: array<int, string>, activityData: array<int, float>, activityTotalSeconds: int} */
    private function workedTimeByDay(User $user, Carbon $start, Carbon $end): array
    {
        $days = $this->daysBetween($start, $end);
        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->with(['intervals', 'customer', 'subService'])
            ->get();

        $secondsByDate = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(fn ($dayEntries) => (int) $dayEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds()));

        // ✅ Agrupar por cliente (guardando el ID)
        $clientGroups = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->customer_id)
            ->map(fn ($clientEntries) => [
                'id' => $clientEntries->first()->customer_id,
                'name' => $clientEntries->first()->customer->name ?? 'Sin cliente',
                'seconds' => (int) $clientEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds()),
            ])
            ->sortByDesc('seconds');

        // ✅ Crear arrays para clientes
        $clientLabels = $clientGroups->pluck('name')->values()->toArray();
        $clientData = $clientGroups->map(fn($c) => round($c['seconds'] / 3600, 2))->values()->toArray();
        $clientIds = $clientGroups->pluck('id')->values()->toArray();
        $clientTotalSeconds = $clientGroups->sum('seconds');

        // ✅ Agrupar por actividad
        $secondsByActivity = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->subService->sub_service ?? 'Sin actividad')
            ->map(fn ($activityEntries) => (int) $activityEntries->sum(fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds()))
            ->sortDesc();

        $totalSeconds = 0;
        $hours = [];

        foreach ($days as $day) {
            $seconds = (int) ($secondsByDate[$day->toDateString()] ?? 0);
            $totalSeconds += $seconds;
            $hours[] = round($seconds / 3600, 2);
        }

        $average = count($days) > 0 ? round(($totalSeconds / 3600) / count($days), 2) : 0;

        // ✅ Limitar actividades a 8 + "Otras"
        $maxActivities = 8;
        $topActivities = $secondsByActivity->take($maxActivities);
        $othersActivitySum = $secondsByActivity->skip($maxActivities)->sum();

        $activityLabels = $topActivities->keys()->toArray();
        $activityData = $topActivities->values()->map(fn($s) => round($s / 3600, 2))->toArray();

        if ($othersActivitySum > 0) {
            $activityLabels[] = 'Otras';
            $activityData[] = round($othersActivitySum / 3600, 2);
        }

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
            'activityTotalSeconds' => $secondsByActivity->sum(),
        ];
    }

    /** @return array{labels: array<int, string>, hours: array<int, float>, averageHours: array<int, float>, totalSeconds: int, clientLabels: array<int, string>, clientData: array<int, float>, clientIds: array<int, int>, clientTotalSeconds: int, activityLabels: array<int, string>, activityData: array<int, float>, activityTotalSeconds: int} */
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