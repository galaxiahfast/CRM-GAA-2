<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: "DejaVu Sans Mono", Courier, monospace; font-size: 8pt; line-height: 1.65; }
        .page { position: relative; width: 612pt; height: 792pt; overflow: hidden; background: #fff; page-break-after: always; }
        .page.last { page-break-after: avoid; }
        .header-decoration { position: absolute; top: 0; left: 0; width: 133pt; }
        .brand-logo { position: absolute; top: 27pt; right: 42pt; width: 105pt; max-height: 40pt; }
        .fallback-brand { position: absolute; top: 27pt; right: 42pt; font-weight: bold; }
        .page-content { position: absolute; top: 100pt; left: 54pt; width: 504pt; height: 594pt; }
        .page-footer-line { position: absolute; bottom: 52pt; left: 54pt; width: 504pt; }
        .page-footer-line .page-number { float: right; }
        .footer-crop { position: absolute; bottom: 0; left: 0; width: 612pt; height: 48pt; overflow: hidden; }
        .footer-decoration { position: absolute; top: -133pt; left: 0; width: 612pt; height: 181pt; }
        h1, h2, h3, p { margin: 0; font-size: 8pt; line-height: 1.65; }
        h1, h2, h3 { font-weight: bold; }
        .meta { width: 100%; margin-bottom: 13pt; border-collapse: collapse; }
        .meta td { padding: 0; vertical-align: top; }
        .meta td:last-child { text-align: right; }
        .responsible { margin-top: 11pt; }
        .divider { margin: 13pt 0; border-top: .55pt dashed #777; }
        .report-section { margin-bottom: 15pt; }
        .section-title { margin-bottom: 11pt; }
        .day-heading { margin: 11pt 0 7pt; }
        .empty { margin: 7pt 0; }
        .detail { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .detail th { padding: 5pt 6pt; text-align: left; background: #f3f3f3; font-weight: normal; }
        .detail td { padding: 6pt; vertical-align: top; border-bottom: .45pt dotted #aaa; word-wrap: break-word; }
        .detail .time { width: 20%; }
        .detail .name { width: 35%; }
        .detail .duration { width: 13%; }
        .detail .context { width: 32%; }
        .detail .summary-name { width: 70%; }
        .detail .summary-time { width: 18%; }
        .detail .summary-share { width: 12%; }
        .detail .observation { padding-left: 18pt; }
    </style>
</head>
<body>
    @foreach ($pages as $pageNumber => $page)
        <section class="page {{ $loop->last ? 'last' : '' }}">
            @if ($headerDecorations[$pageNumber] ?? null)<img class="header-decoration" src="{{ $headerDecorations[$pageNumber] }}" alt="">@endif
            @if ($logos[$pageNumber] ?? null)<img class="brand-logo" src="{{ $logos[$pageNumber] }}" alt="DataMID">@else<div class="fallback-brand">DataMID</div>@endif

            <div class="page-content">
                @if ($page['intro'])
                    <table class="meta"><tr>
                        <td>{{ $report->generatedAt()->locale('es')->translatedFormat('d \d\e F \d\e Y') }} a las {{ $report->generatedAt()->format('h:i:s a') }}</td>
                        <td>{{ $report->filenameBase }}</td>
                    </tr></table>
                    <h1>Informe de productividad</h1>
                    <p class="responsible">Colaborador: <strong>{{ $report->meta['Colaborador'] ?? '—' }}</strong></p>
                    <p>Periodo: {{ $report->meta['Desde'] ?? '—' }} al {{ $report->meta['Hasta'] ?? '—' }}</p>
                    <p>Horas efectivas en el periodo: {{ $report->meta['Horas efectivas en el periodo'] ?? '00:00:00' }}</p>
                    <div class="divider"></div>
                @endif

                @foreach ($page['blocks'] as $block)
                    @if (! $loop->first)<div class="divider"></div>@endif
                    <div class="report-section">
                        <h2 class="section-title">{{ str_pad((string) $block['number'], 2, '0', STR_PAD_LEFT) }} / {{ $block['title'] }}{{ $block['continued'] ?? false ? ' (continuación)' : '' }}</h2>
                        @if ($block['kind'] === 'empty-detail')
                            <p class="empty">Sin registros en el periodo.</p>
                        @elseif ($block['kind'] === 'summary')
                            @if ($block['rows'] === [])
                                <p class="empty">Sin datos en el periodo.</p>
                            @else
                                <table class="detail">
                                    <thead><tr><th class="summary-name">{{ $block['columns'][0] ?? 'Concepto' }}</th><th class="summary-time">{{ $block['columns'][1] ?? 'Tiempo' }}</th><th class="summary-share">{{ $block['columns'][2] ?? '%' }}</th></tr></thead>
                                    <tbody>@foreach ($block['rows'] as $row)
                                        <tr><td>{{ $row[0] ?? '—' }}</td><td>{{ $row[1] ?? '—' }}</td><td>{{ $row[2] ?? '—' }}</td></tr>
                                    @endforeach</tbody>
                                </table>
                            @endif
                        @else
                            @php($col = $block['columns'])
                            <h3 class="day-heading">{{ $block['date'] }} · Total del día: {{ $block['total'] }}</h3>
                            <table class="detail">
                                <thead><tr><th class="time">Horario</th><th class="name">Actividad / cliente</th><th class="duration">Tiempo efectivo</th><th class="context">Puesto / área</th></tr></thead>
                                <tbody>
                                    @foreach ($block['rows'] as $row)
                                        <tr>
                                            <td>{{ $row[$col['Inicio']] ?? '—' }} - {{ $row[$col['Fin']] ?? '—' }}</td>
                                            <td>{{ $row[$col['Actividad']] ?? '—' }}<br>Cliente: {{ $row[$col['Cliente']] ?? '—' }}</td>
                                            <td>{{ $row[$col['Tiempo efectivo']] ?? '—' }}</td>
                                            <td>{{ $row[$col['Puesto profesional']] ?? '—' }}<br>Área: {{ $row[$col['Área física']] ?? '—' }}</td>
                                        </tr>
                                        @if (filled($row[$col['Observaciones']] ?? null) && ($row[$col['Observaciones']] ?? '') !== '—')
                                            <tr><td colspan="4" class="observation">Observaciones: {{ $row[$col['Observaciones']] }}</td></tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="page-footer-line"><span>Control de productividad / Informe técnico</span><span class="page-number">Página {{ $pageNumber + 1 }} / {{ count($pages) }}</span></div>
            @if ($footerDecorations[$pageNumber] ?? null)<div class="footer-crop"><img class="footer-decoration" src="{{ $footerDecorations[$pageNumber] }}" alt=""></div>@endif
        </section>
    @endforeach
</body>
</html>
