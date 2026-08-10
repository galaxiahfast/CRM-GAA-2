<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $viewer = $request->user();
        $isAdmin = $viewer->isAdmin();
        [$start, $end] = $this->dateRangeFromRequest($request);

        $search = trim((string) $request->query('search', ''));

        // ✅ Cambio 1: pasar false para NO limitar a 8 usuarios
        $users = $isAdmin ? $this->searchUsers($search, false) : collect();
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

        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->where('sub_service_id', $activityId)
            ->with('intervals')
            ->get();

        $days = $this->daysBetween($start, $end);
        $secondsByDate = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(fn ($dayEntries) => (int) $dayEntries->sum(
                fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds()
            ));

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

        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->where('customer_id', $clientId)
            ->with('intervals')
            ->get();

        $days = $this->daysBetween($start, $end);
        $secondsByDate = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(fn ($dayEntries) => (int) $dayEntries->sum(
                fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds()
            ));

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
    private function getTopClientActivity(Collection $entries, array $secondsByEntry): ?array
    {
        if ($entries->isEmpty()) {
            return null;
        }

        $grouped = $entries->groupBy(function ($entry) {
            return $entry->customer_id.'|'.($entry->sub_service_id ?? 0);
        });

        $combinations = $grouped->map(function ($group) use ($secondsByEntry) {
            $first = $group->first();
            $seconds = (int) $group->sum(fn (TimeEntry $entry) => $secondsByEntry[$entry->id] ?? 0);

            return [
                'client_id' => $first->customer_id,
                'client_name' => $first->customer->name ?? 'Sin cliente',
                'activity_id' => $first->sub_service_id,
                'activity_name' => $first->subService->sub_service ?? 'Sin actividad',
                'seconds' => $seconds,
            ];
        });

        return $combinations->sortByDesc('seconds')->first();
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

        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->where('customer_id', $clientId)
            ->where('sub_service_id', $activityId)
            ->with('intervals')
            ->get();

        $days = $this->daysBetween($start, $end);
        $secondsByDate = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(fn ($dayEntries) => (int) $dayEntries->sum(
                fn (TimeEntry $entry) => $entry->calculateEffectiveSeconds()
            ));

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

    public function generatePdf(Request $request)
    {
        $images = $request->input('images', []);

        $images = array_filter($images, function ($img) {
            return isset($img['src']) &&
                str_starts_with($img['src'], 'data:image/') &&
                strlen($img['src']) > 100;
        });

        $images = array_values($images);

        if (empty($images)) {
            return response()->json(['error' => 'No se capturaron imágenes válidas'], 400);
        }
        $start = Carbon::parse($request->input('fecha_inicio'));
        $end = Carbon::parse($request->input('fecha_fin'));
        $selectedUserId = $request->input('user_id');
        $selectedUser = $selectedUserId ? User::find($selectedUserId) : null;

        $labels = $request->input('labels', []);
        $hours = $request->input('hours', []);
        $clientLabels = $request->input('clientLabels', []);
        $clientData = $request->input('clientData', []);
        $activityLabels = $request->input('activityLabels', []);
        $activityData = $request->input('activityData', []);
        $totalSeconds = $request->input('totalSeconds', 0);

        $pdf = Pdf::loadView('time-dashboard.pdf', compact(
            'images', 'selectedUser', 'start', 'end',
            'labels', 'hours', 'clientLabels', 'clientData',
            'activityLabels', 'activityData', 'totalSeconds'
        ));

        return $pdf->download('dashboard_tiempo.pdf');
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

    // ✅ Cambio 2: Método searchUsers modificado con parámetro $limit
    private function searchUsers(string $search, $limit = true)
    {
        $query = User::with('role')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('id', $search)
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        // ✅ Si $limit es true, aplicar límite de 8 (para la búsqueda)
        // ✅ Si $limit es false, traer todos los usuarios
        if ($limit) {
            $query->limit(8);
        }

        return $query->get();
    }

    /** @return array{labels: array<int, string>, hours: array<int, float>, averageHours: array<int, float>, totalSeconds: int, clientLabels: array<int, string>, clientData: array<int, float>, clientIds: array<int, int>, clientTotalSeconds: int, activityLabels: array<int, string>, activityData: array<int, float>, activityTotalSeconds: int, topClientActivity: ?array} */
    private function workedTimeByDay(User $user, Carbon $start, Carbon $end): array
    {
        $days = $this->daysBetween($start, $end);
        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->with(['intervals', 'customer', 'subService'])
            ->get();

        // El mismo tiempo efectivo alimenta cuatro agrupaciones. Calcularlo
        // una sola vez evita recorrer todos los intervalos repetidamente.
        $secondsByEntry = $entries
            ->mapWithKeys(fn (TimeEntry $entry): array => [
                $entry->id => $entry->calculateEffectiveSeconds(),
            ])
            ->all();

        $secondsByDate = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->entry_date->format('Y-m-d'))
            ->map(fn ($dayEntries) => (int) $dayEntries->sum(
                fn (TimeEntry $entry) => $secondsByEntry[$entry->id] ?? 0
            ));

        $clientGroups = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->customer_id)
            ->map(function ($clientEntries) use ($secondsByEntry) {
                $total = (int) $clientEntries->sum(
                    fn (TimeEntry $entry) => $secondsByEntry[$entry->id] ?? 0
                );

                return [
                    'id' => $clientEntries->first()->customer_id,
                    'name' => $clientEntries->first()->customer->name ?? 'Sin cliente',
                    'seconds' => $total,
                ];
            })
            ->sortByDesc('seconds');

        $clientLabels = $clientGroups->pluck('name')->values()->toArray();
        $clientData = $clientGroups->map(fn ($c) => round($c['seconds'] / 3600, 4))->values()->toArray();
        $clientIds = $clientGroups->pluck('id')->values()->toArray();
        $clientTotalSeconds = $clientGroups->sum('seconds');

        $secondsByActivity = $entries
            ->groupBy(fn (TimeEntry $entry) => $entry->subService->sub_service ?? 'Sin actividad')
            ->map(function ($activityEntries) use ($secondsByEntry) {
                $first = $activityEntries->first();

                return [
                    'name' => $first->subService->sub_service ?? 'Sin actividad',
                    'id' => $first->sub_service_id ?? 0,
                    'seconds' => (int) $activityEntries->sum(
                        fn (TimeEntry $entry) => $secondsByEntry[$entry->id] ?? 0
                    ),
                ];
            })
            ->sortByDesc('seconds');

        $activityLabels = $secondsByActivity->pluck('name')->values()->toArray();
        $activityData = $secondsByActivity->map(fn ($a) => round($a['seconds'] / 3600, 4))->values()->toArray();
        $activityIds = $secondsByActivity->pluck('id')->values()->toArray();

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
        $activityData = $topActivities->map(fn ($a) => round($a['seconds'] / 3600, 4))->values()->toArray();
        $activityIds = $topActivities->pluck('id')->values()->toArray();

        if ($othersActivitySum > 0) {
            $activityLabels[] = 'Otras';
            $activityData[] = round($othersActivitySum / 3600, 4);
            $activityIds[] = 0;
        }

        $topClientActivity = $this->getTopClientActivity($entries, $secondsByEntry);

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
            'activityIds' => $activityIds,
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
            'activityIds' => [],
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
