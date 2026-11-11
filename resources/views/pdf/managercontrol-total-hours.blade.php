<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 40pt 42pt; }
        body { margin: 0; color: #000; font-family: "DejaVu Sans", sans-serif; font-size: 9pt; }
        .top { width: 100%; border-collapse: collapse; margin-bottom: 14pt; }
        .top td { vertical-align: top; padding: 0; }
        .logo { width: 120pt; }
        h1 { margin: 0 0 4pt; font-size: 14pt; font-weight: bold; }
        .period { font-size: 10pt; }
        .person { margin: 0 0 14pt; padding: 8pt 0; border-top: .6pt solid #000; border-bottom: .6pt solid #000; }
        .person td { padding: 1pt 12pt 1pt 0; }
        table.hours { width: 100%; border-collapse: collapse; }
        table.hours th { padding: 5pt 6pt; text-align: left; background: #f1f1f1; border-bottom: .6pt solid #000; }
        table.hours td { padding: 4pt 6pt; vertical-align: top; border-bottom: .4pt dotted #999; }
        .num { text-align: right !important; white-space: nowrap; }
        .day td { font-weight: bold; background: #fafafa; }
        .subtotal td { text-align: right; border-bottom: .6pt solid #000; padding-bottom: 6pt; }
        .total { margin-top: 14pt; text-align: right; font-size: 11pt; font-weight: bold; }
        .empty { margin: 12pt 0; font-style: italic; }
        .foot { margin-top: 18pt; font-size: 7.5pt; color: #555; }
    </style>
</head>
<body>
    <table class="top"><tr>
        <td>
            <h1>Total de Horas Laboradas</h1>
            <div class="period">Del {{ $from }} Al {{ $to }}</div>
        </td>
        <td style="text-align:right">@if ($logo)<img class="logo" src="{{ $logo }}" alt="DataMID">@else DataMID @endif</td>
    </tr></table>

    <table class="person">
        <tr><td>Colaborador:</td><td>{{ $employee['name'] }}</td></tr>
        <tr><td>Clave:</td><td>{{ $employee['id'] }}</td></tr>
    </table>

    @if ($summary->rows === [])
        <p class="empty">Sin horas laboradas en el periodo.</p>
    @else
        <table class="hours">
            <thead><tr><th style="width:13%">Fecha</th><th style="width:27%">Cliente</th><th style="width:30%">Actividad</th><th>Descripción</th><th class="num" style="width:11%">Horas</th></tr></thead>
            <tbody>
                @foreach ($summary->byDay as $date => $dayRows)
                    @foreach ($dayRows as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</td>
                            <td>{{ $row['customer'] }}</td>
                            <td>{{ $row['activity'] }}</td>
                            <td>{{ $row['description'] }}</td>
                            <td class="num">{{ \App\Services\ManagerControl\HoursSummary::format($row['seconds']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal"><td colspan="4">Total del día {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</td><td class="num">{{ \App\Services\ManagerControl\HoursSummary::format(array_sum(array_column($dayRows, 'seconds'))) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="total">Total acumulado: {{ \App\Services\ManagerControl\HoursSummary::format($summary->totalSeconds) }}</div>
    <div class="foot">Informe de consulta de solo lectura de ManagerControl · Generado el {{ $generatedAt->format('d/m/Y H:i') }}</div>
</body>
</html>
