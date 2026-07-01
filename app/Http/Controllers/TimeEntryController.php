<?php

namespace App\Http\Controllers;

use App\Services\TimeControl\Exceptions\ActiveEntryException;
use App\Services\TimeControl\Exceptions\NoOrganizationalProfileException;
use App\Services\TimeControl\TimerService;
use App\Services\TimeControl\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class TimeEntryController extends Controller
{
    protected $attendanceService;

    /**
     * Inyectamos el servicio de asistencia en el constructor.
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

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
     * Consulta y procesa el cálculo de horas de un colaborador (Modo Espejo Checador).
     */
    public function consultarAsistencia(Request $request): JsonResponse
    {
        // Validación de parámetros obligatorios de consulta
        $data = $request->validate([
            'employee_id' => ['required', 'string'],
            'pago'        => ['required', 'numeric', 'min:0'],
            'bono'        => ['required', 'numeric', 'min:0'],
            'inicio'      => ['required', 'date'],
            'fin'         => ['required', 'date'],
        ]);

        // 🔒 CAPA DE SEGURIDAD AUTOMÁTICA
        $usuarioLogueado = $request->user();

        // Si NO es Administrador (role_id != 1), sobreescribimos el ID con el Suyo propio obligatoriamente
        if ($usuarioLogueado->role_id != 1) {
            $data['employee_id'] = $usuarioLogueado->employee_id;
            
            // Si el usuario ni siquiera tiene configurado un employee_id en su cuenta, lanzamos error
            if (empty($data['employee_id'])) {
                return response()->json([
                    'message' => 'Tu usuario no tiene un ID de checador asignado. Contacta al administrador.'
                ], 403);
            }
        }

        try {
            // Se obtienen los logs crudos filtrados con el ID verificado de forma segura
            $registros = DB::table('control_de_horas')
                ->where('employeeID', $data['employee_id'])
                ->whereBetween('authDate', [$data['inicio'], $data['fin']])
                ->orderBy('authDateTime', 'asc')
                ->get();

            $registrosNormalizados = $registros->map(function ($reg) {
                return (object) [
                    'auth_datetime' => $reg->authDateTime,
                    'employee_id'   => $reg->employeeID,
                    'person_name'   => $reg->personName,
                    'direction'     => $reg->direction ?? null,
                    'auth_date'     => $reg->authDate,
                ];
            });

            $resultado = $this->attendanceService->processPayroll(
                $registrosNormalizados,
                (float) $data['pago'],
                (float) $data['bono']
            );

            return response()->json($resultado, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el reporte de asistencia.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}