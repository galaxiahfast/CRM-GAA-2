<?php

namespace App\Http\Controllers;

use App\Services\TimeControl\Exceptions\ActiveEntryException;
use App\Services\TimeControl\Exceptions\NoOrganizationalProfileException;
use App\Services\TimeControl\TimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TimeEntryController extends Controller
{
    /**
     * Inicia un cronómetro vía API.
     *
     * - Administrador -> 403 (regla 8.8 / escenario 12.4).
     * - Cronómetro ya activo -> 422 (regla 8.2 / escenario 12.3).
     */
    public function start(Request $request, TimerService $timer): JsonResponse
    {
        // Bloqueo de rol administrativo mediante Gate.
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
}
