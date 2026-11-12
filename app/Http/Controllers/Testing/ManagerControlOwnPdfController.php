<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Services\ManagerControl\HoursSummary;
use App\Services\ManagerControl\LegacyEmployeeResolver;
use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ManagerControlRepository;
use App\Services\ManagerControl\TotalHoursPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** PDF de «Prueba becarios»: el colaborador sale del nombre del usuario autenticado, nunca de la petición. */
class ManagerControlOwnPdfController extends Controller
{
    public function __invoke(Request $request, ManagerControlRepository $repository, LegacyEmployeeResolver $resolver, TotalHoursPdf $pdf)
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            // «managercontrol» = formato original de ManagerControl (Registro de horas por día).
            'formato' => ['nullable', 'in:crm,managercontrol'],
        ]);
        $user = $request->user();

        try {
            $match = $resolver->forUser($user);
            abort_if($match['employee'] === null, 404, 'No tienes un colaborador de ManagerControl asignado.');
            $employee = $match['employee'];
            $summary = HoursSummary::fromRows($repository->workedHours($employee['id'], $data['from'], $data['to']));
            $content = ($data['formato'] ?? 'crm') === 'managercontrol'
                ? $pdf->renderOriginal($employee, $repository->employeeDepartment($employee['id']), $data['from'], $data['to'], $summary)
                : $pdf->render($employee, $data['from'], $data['to'], $summary);
        } catch (ManagerControlException $e) {
            Log::warning('ManagerControl PDF propio (solo lectura): '.$e->getMessage());
            abort(503, $e->getMessage());
        }

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="total-horas-laboradas_'.$data['from'].'_'.$data['to'].'.pdf"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
