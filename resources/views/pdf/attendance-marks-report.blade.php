<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    {{-- Marcas de tiempo del Reloj Checador: mismo formato que los reportes del Cronómetro y de Manager Control
         (carta, franja y logo de DataMID, sección numerada «01 / …» y pie con número de página). La letra es
         monoespaciada, así fechas, horas e importes quedan alineados de una fila a otra. --}}
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
        h1, h2, p { margin: 0; font-size: 8pt; line-height: 1.65; }
        h1, h2 { font-weight: bold; }
        .meta { width: 100%; margin-bottom: 13pt; border-collapse: collapse; }
        .meta td { padding: 0; vertical-align: top; }
        .meta td:last-child { text-align: right; }
        .responsible { margin-top: 11pt; }
        .divider { margin: 13pt 0; border-top: .55pt dashed #777; }
        .section-title { margin-bottom: 11pt; }
        .empty { margin: 7pt 0; }
        .marks { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .marks th { padding: 5pt 6pt; text-align: left; background: #f3f3f3; font-weight: normal; }
        .marks td { padding: 6pt; vertical-align: top; border-bottom: .45pt dotted #aaa; word-wrap: break-word; }
        .marks .date { width: 12%; }
        .marks .punches { width: 25%; }
        .marks .net { width: 16%; }
        .marks .decimal { width: 15%; }
        .marks .base { width: 15%; }
        .marks .total { width: 17%; }
        .marks th { white-space: nowrap; }
        .marks .num { text-align: right; }
        .marks tr.review td { background: #fdecec; }
        .marks tr.sum td { border-top: .55pt solid #777; border-bottom: 0; font-weight: bold; }
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
                    <h1>Marcas de tiempo del Reloj checador</h1>
                    <p class="responsible">Colaborador: <strong>{{ $report->meta['Colaborador'] ?? '—' }}</strong></p>
                    <p>ID checador: {{ $report->meta['ID Checador'] ?? '—' }}</p>
                    <p>Periodo: {{ $report->meta['Periodo'] ?? '—' }}</p>
                    <p>Tiempo neto en el periodo: {{ $report->meta['Tiempo neto'] ?? '00h 00m 00s' }}</p>
                    <p>Total del periodo: {{ $report->meta['Total acumulado'] ?? '$0.00' }}</p>
                    <div class="divider"></div>
                @endif

                <h2 class="section-title">01 / {{ $section->title }}{{ $page['intro'] ? '' : ' (continuación)' }}</h2>
                @if ($page['rows'] === [] && $page['intro'])
                    <p class="empty">Sin marcas registradas en el periodo.</p>
                @endif
                @if ($page['rows'] !== [] || $page['total'] !== null)
                    <table class="marks">
                        <thead><tr>
                            <th class="date">{{ $section->columns[0] }}</th>
                            <th class="punches">{{ $section->columns[1] }}</th>
                            <th class="net">{{ $section->columns[2] }}</th>
                            <th class="decimal num">{{ $section->columns[3] }}</th>
                            <th class="base num">{{ $section->columns[4] }}</th>
                            <th class="total num">{{ $section->columns[5] }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($page['rows'] as $row)
                                <tr @class(['review' => $row['review']])>
                                    <td>{{ $row['cells'][0] }}</td>
                                    <td>{{ $row['cells'][1] }}</td>
                                    <td>{{ $row['cells'][2] }}</td>
                                    <td class="num">{{ $row['cells'][3] }}</td>
                                    <td class="num">{{ $row['cells'][4] }}</td>
                                    <td class="num">{{ $row['cells'][5] }}</td>
                                </tr>
                            @endforeach
                            @if ($page['total'] !== null)
                                <tr class="sum">
                                    <td colspan="2">{{ $page['total'][0] }}</td>
                                    <td>{{ $page['total'][2] }}</td>
                                    <td class="num">{{ $page['total'][3] }}</td>
                                    <td class="num">{{ $page['total'][4] }}</td>
                                    <td class="num">{{ $page['total'][5] }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="page-footer-line"><span>Reloj checador / Marcas de tiempo</span><span class="page-number">Página {{ $pageNumber + 1 }} / {{ count($pages) }}</span></div>
            @if ($footerDecorations[$pageNumber] ?? null)<div class="footer-crop"><img class="footer-decoration" src="{{ $footerDecorations[$pageNumber] }}" alt=""></div>@endif
        </section>
    @endforeach
</body>
</html>
