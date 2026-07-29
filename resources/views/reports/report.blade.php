<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $data->title }}</title>
    <style>
        * { font-family: Helvetica, Arial, sans-serif; }
        body { font-size: 11px; color: #1f2937; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .generated { color: #6b7280; font-size: 10px; margin-bottom: 12px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta td { padding: 2px 6px; }
        .meta td.label { color: #6b7280; width: 220px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 2px; }
        h2.report-start { page-break-before: always; }
        table.section { width: 100%; border-collapse: collapse; }
        table.section th, table.section td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
        table.section th { background: #f3f4f6; }
        table.section td.num, table.section th.num { text-align: right; font-family: Courier, monospace; }
        .empty { color: #9ca3af; font-style: italic; }
        .day-total-box { text-align: right; font-size: 11px; font-weight: bold; padding: 6px; color: #111827; }
        .day-total-value { font-family: Courier, monospace; background: #f3f4f6; padding: 2px 6px; border: 1px solid #e5e7eb; margin-left: 4px; }
    </style>
</head>
<body>
    <h1>{{ $data->title }}</h1>
    <div class="generated">Generado: {{ $data->generatedAt()->format('d/m/Y H:i:s') }}</div>

    @if (! empty($data->meta))
        <table class="meta">
            @foreach ($data->meta as $label => $value)
                <tr>
                    <td class="label">{{ $label }}</td>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @foreach ($data->sections as $section)
        <h2 @class(['report-start' => str_starts_with($section->title, 'Reporte individual:') && ! $loop->first])>{{ $section->title }}</h2>
        @if ($section->dayGroups !== null)
            @if (empty($section->dayGroups))
                <p class="empty">Sin datos.</p>
            @else
                @foreach ($section->dayGroups as $group)
                    <h3 style="font-size: 12px; margin: 12px 0 4px;">{{ $group['date'] }}</h3>
                    
                    @php $totalSecondsThisDay = 0; @endphp
                    
                    <table class="section">
                        <thead>
                            <tr>
                                @foreach ($section->columns as $i => $column)
                                    <th class="{{ in_array($column, ['Inicio', 'Fin', 'Tiempo efectivo'], true) ? 'num' : '' }}">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['rows'] as $row)
                                <tr>
                                    @foreach (array_values($row) as $i => $cell)
                                        @php 
                                            $column = $section->columns[$i] ?? ''; 
                                            
                                            // Si la columna actual es la de "Tiempo efectivo", calculamos sus segundos para sumarlos
                                            if ($column === 'Tiempo efectivo' && !empty($cell)) {
                                                $parts = explode(':', $cell);
                                                if (count($parts) === 3) {
                                                    $totalSecondsThisDay += ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
                                                }
                                            }
                                        @endphp
                                        <td class="{{ in_array($column, ['Inicio', 'Fin', 'Tiempo efectivo'], true) ? 'num' : '' }}">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @php
                        $hours = intdiv($totalSecondsThisDay, 3600);
                        $minutes = intdiv($totalSecondsThisDay % 3600, 60);
                        $seconds = $totalSecondsThisDay % 60;
                        $formattedDayTotal = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    @endphp
                    <div class="day-total-box">
                        <span>Total del día:</span><span class="day-total-value">{{ $formattedDayTotal }}</span>
                    </div>

                @endforeach
            @endif
        @elseif (empty($section->rows))
            <p class="empty">Sin datos.</p>
        @else
            <table class="section">
                <thead>
                    <tr>
                        @foreach ($section->columns as $i => $column)
                            <th class="{{ $i === 0 ? '' : 'num' }}">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section->rows as $row)
                        <tr>
                            @foreach (array_values($row) as $i => $cell)
                                <td class="{{ $i === 0 ? '' : 'num' }}">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>
