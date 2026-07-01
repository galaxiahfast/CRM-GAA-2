<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TimeControl\AttendanceExportService;
use App\Services\TimeControl\AttendanceService;
use App\Services\TimeControl\AttendanceSettingsService;
use App\Services\TimeControl\Exceptions\ActiveEntryException;
use App\Services\TimeControl\Exceptions\NoOrganizationalProfileException;
use App\Services\TimeControl\TimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimeEntryController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private AttendanceSettingsService $settingsService,
        private AttendanceExportService $exportService,
    ) {}

    /**
     * Inicia un cronómetro vía API.
     */
    public function start(Request $request, TimerService $timer): JsonResponse
    {
        if (Gate::denies('operate-time-tracking')) {
            abort(403, 'El rol Administrador no puede registrar tiempos.');
        }

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'sub_service_id' => ['required', 'exists:sub_services,id'],
        ]);

        try {
            $entry = $timer->start($request->user(), $data['customer_id'], $data['sub_service_id']);
        } catch (ActiveEntryException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (NoOrganizationalProfileException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Actividad iniciada.',
            'time_entry_id' => $entry->id,
        ], 201);
    }

    /**
     * Consulta y procesa el cálculo de horas de un colaborador (Checador).
     */
    public function consultarAsistencia(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'string'],
            'inicio' => ['required', 'date'],
            'fin' => ['required', 'date'],
            'pago' => ['nullable', 'numeric', 'min:0'],
            'bono' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employeeId = $this->resolveEmployeeId($request, $data['employee_id']);

        try {
            $resultado = $this->buildPayrollResult(
                $employeeId,
                $data['inicio'],
                $data['fin'],
                isset($data['pago']) ? (float) $data['pago'] : null,
                isset($data['bono']) ? (float) $data['bono'] : null,
            );

            return response()->json($resultado, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el reporte de asistencia.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exporta el reporte del checador en TXT, CSV o PDF.
     */
    public function exportarAsistencia(Request $request, string $format): StreamedResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'string'],
            'inicio' => ['required', 'date'],
            'fin' => ['required', 'date'],
        ]);

        $employeeId = $this->resolveEmployeeId($request, $data['employee_id']);
        $user = User::where('employee_id', $employeeId)->first();

        $resultado = $this->buildPayrollResult($employeeId, $data['inicio'], $data['fin']);

        $meta = [
            'Colaborador' => $user ? trim($user->name.' '.$user->last_name) : 'ID '.$employeeId,
            'ID Checador' => $employeeId,
            'Periodo' => $data['inicio'].' — '.$data['fin'],
            'Pago por hora general' => '$'.number_format($resultado['settings']['hourly_rate'], 2),
            'Bono general (días correctos)' => '$'.number_format($resultado['settings']['bonus_amount'], 2),
            'Total acumulado' => $resultado['total_general'],
        ];

        return $this->exportService->download($format, $resultado, $meta);
    }

    /**
     * Guarda tarifas generales del colaborador (pisa ajustes individuales).
     */
    public function guardarTarifasGenerales(Request $request): JsonResponse
    {
        abort_unless($request->user()->role_id === 1, 403);

        $data = $request->validate([
            'employee_id' => ['required', 'string'],
            'pago' => ['required', 'numeric', 'min:0'],
            'bono' => ['required', 'numeric', 'min:0'],
        ]);

        $this->settingsService->saveGeneral(
            $data['employee_id'],
            (float) $data['pago'],
            (float) $data['bono'],
        );

        return response()->json(['message' => 'Tarifas generales actualizadas.']);
    }

    /**
     * Guarda ajuste individual por día (prioridad sobre valores generales).
     */
    public function guardarAjusteDia(Request $request): JsonResponse
    {
        abort_unless($request->user()->role_id === 1, 403);

        $data = $request->validate([
            'employee_id' => ['required', 'string'],
            'fecha' => ['required', 'date'],
            'pago' => ['required', 'numeric', 'min:0'],
            'bono' => ['required', 'numeric', 'min:0'],
        ]);

        $this->settingsService->saveDayOverride(
            $data['employee_id'],
            $data['fecha'],
            (float) $data['pago'],
            (float) $data['bono'],
        );

        return response()->json(['message' => 'Ajuste del día guardado.']);
    }

    private function resolveEmployeeId(Request $request, string $requestedId): string
    {
        $usuarioLogueado = $request->user();

        if ((int) $usuarioLogueado->role_id !== 1) {
            $employeeId = $usuarioLogueado->employee_id;

            if (empty($employeeId)) {
                abort(403, 'Tu usuario no tiene un ID de checador asignado. Contacta al administrador.');
            }

            return $employeeId;
        }

        return $requestedId;
    }

    /** @return array<string, mixed> */
    private function buildPayrollResult(
        string $employeeId,
        string $from,
        string $to,
        ?float $overrideHourly = null,
        ?float $overrideBonus = null,
    ): array {
        $user = User::where('employee_id', $employeeId)->first();
        $profile = $user?->activeOrganizationalProfile;

        $profileHourly = $profile ? (float) $profile->hourly_rate : null;
        $profileBonus = $profile ? (float) $profile->food_allowance : null;

        $settings = $this->settingsService->getSettings($employeeId, $profileHourly, $profileBonus);

        if ($overrideHourly !== null) {
            $settings['hourly_rate'] = $overrideHourly;
        }
        if ($overrideBonus !== null) {
            $settings['bonus_amount'] = $overrideBonus;
        }

        $records = $this->attendanceService->fetchRecords($employeeId, $from, $to);

        return $this->attendanceService->processPayroll($records, $settings);
    }
}
