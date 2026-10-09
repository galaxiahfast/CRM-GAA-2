<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historial de cambios del Reloj checador</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; font-family: "DejaVu Sans Mono", Courier, monospace; }
        body { margin: 0; color: #111827; font-size: 9px; line-height: 1.35; }
        .report-header { margin-bottom: 18px; border: 1px solid #9ca3af; padding: 10px 12px; }
        h1 { margin: 0 0 5px; font-size: 9px; font-weight: normal; }
        .generated { margin-bottom: 8px; color: #6b7280; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; }
        .meta .label { width: 180px; color: #6b7280; }
        .report { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .report th, .report td { border: 1px solid #9ca3af; padding: 8px; vertical-align: middle; font-size: 9px; font-weight: normal; }
        .report th { text-align: center; }
        .report td { text-align: left; }
        .report tr { page-break-inside: avoid; }
        .report .section-title { text-align: left; }
        .wrap { white-space: normal; overflow-wrap: break-word; }
        .nowrap { white-space: nowrap; }
        .person { text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>Historial de cambios del Reloj checador</h1>
        <div class="generated">Generado: {{ $generatedAt->format('d/m/Y H:i:s') }}</div>
        <table class="meta">
            <tr><td class="label">Registros incluidos</td><td>{{ count($changes) }}</td></tr>
            <tr><td class="label">Contenido</td><td>{{ $isExample ? 'Vista de ejemplo' : 'Cambios registrados' }}</td></tr>
        </table>
    </div>

    <table class="report">
        <colgroup>
            <col style="width: 11%"><col style="width: 16%"><col style="width: 8%"><col style="width: 18%">
            <col style="width: 20%"><col style="width: 17%"><col style="width: 10%">
        </colgroup>
        <thead>
            <tr><th class="section-title" colspan="7">Detalle del historial</th></tr>
            <tr>
                <th>Fecha del cambio</th><th>Colaborador</th><th>Jornada</th><th>Motivo</th>
                <th>Cambios de marcas</th><th>Cambios de pago</th><th>Administrador</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($changes as $change)
                @php
                    $marksBefore = array_values($change['marks_before'] ?? []);
                    $marksAfter = array_values($change['marks_after'] ?? []);
                    $mealBefore = isset($change['bonus_before']) ? '$'.number_format((float) $change['bonus_before'], 2) : 'Sin valor';
                @endphp
                <tr>
                    <td class="nowrap">{{ $change['changed_at'] ?? 'Fecha no disponible' }}</td>
                    <td class="wrap person">{{ $change['employee_name'] ?? 'Colaborador' }} · ID {{ $change['employee_id'] ?? '' }}</td>
                    <td class="nowrap">{{ $change['date'] ?? 'Día no disponible' }}</td>
                    <td class="wrap">{{ $change['comment'] ?? 'Sin comentario' }}</td>
                    <td class="wrap">
                        Antes: {{ $marksBefore === [] ? 'Sin marcas' : implode(' · ', $marksBefore) }}<br>
                        Después: {{ $marksAfter === [] ? 'Sin marcas' : implode(' · ', $marksAfter) }}
                    </td>
                    <td class="wrap">
                        Hora: ${{ number_format((float) ($change['hourly_rate_before'] ?? 0), 2) }} → ${{ number_format((float) ($change['hourly_rate_after'] ?? 0), 2) }}<br>
                        Comida: {{ $mealBefore }} → ${{ number_format((float) ($change['bonus_after'] ?? 0), 2) }}<br>
                        Bono: ${{ number_format((float) ($change['extra_bonus_before'] ?? 0), 2) }} → ${{ number_format((float) ($change['extra_bonus_after'] ?? 0), 2) }}
                    </td>
                    <td class="wrap">{{ $change['admin_name'] ?? 'Administrador' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
