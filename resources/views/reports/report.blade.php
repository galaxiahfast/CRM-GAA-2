<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $data->title }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .generated { color: #6b7280; font-size: 10px; margin-bottom: 12px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta td { padding: 2px 6px; }
        .meta td.label { color: #6b7280; width: 220px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 2px; }
        table.section { width: 100%; border-collapse: collapse; }
        table.section th, table.section td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
        table.section th { background: #f3f4f6; }
        table.section td.num, table.section th.num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
        .empty { color: #9ca3af; font-style: italic; }
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
        <h2>{{ $section->title }}</h2>
        @if ($section->dayGroups !== null)
            @if (empty($section->dayGroups))
                <p class="empty">Sin datos.</p>
            @else
                @foreach ($section->dayGroups as $group)
                    <h3 style="font-size: 12px; margin: 12px 0 4px;">{{ $group['date'] }}</h3>
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
                                        @php $column = $section->columns[$i] ?? ''; @endphp
                                        <td class="{{ in_array($column, ['Inicio', 'Fin', 'Tiempo efectivo'], true) ? 'num' : '' }}">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
