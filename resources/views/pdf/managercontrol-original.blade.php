<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    {{-- Réplica del reporte «Total de Horas Laboradas» de ManagerControl (Crystal Reports): mismas posiciones, fuentes y tamaños. --}}
    <style>
        @page { size: letter; margin: 15.85pt 42.9pt 40pt 82.3pt; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: Helvetica, Arial, sans-serif; font-size: 8.1pt; line-height: 1.2; }
        .head { position: relative; height: 104pt; }
        .head > * { position: absolute; white-space: nowrap; }
        .logo { left: 0; top: 0; width: 66pt; height: 54pt; }
        .firm { left: 110.1pt; top: 1.2pt; font-size: 12.65pt; font-style: italic; }
        .title { left: 166.2pt; top: 19pt; font-size: 10.75pt; }
        .label { font-size: 8.75pt; font-weight: bold; }
        .range { left: 174pt; top: 43pt; }
        .range b { font-size: 8.75pt; }
        .range span { margin-right: 26pt; }
        .meta { left: 0; font-size: 8.1pt; }
        .meta b { font-size: 8.75pt; }
        .meta .v { position: absolute; left: 72pt; top: -0.6pt; }
        table { width: 486.8pt; border-collapse: collapse; }
        th { padding: 0 0 6pt; text-align: left; font-size: 8.75pt; font-weight: bold; vertical-align: bottom; }
        td { padding: 2.5pt 0; vertical-align: top; word-wrap: break-word; }
        td.c-client, th.c-client { padding-right: 8pt; }
        td.c-activity, th.c-activity { padding-right: 8pt; }
        .c-hours { text-align: right; }
        .desc { display: block; margin-top: 1.5pt; }
        .empty { padding-top: 6pt; }
        .closing { position: relative; height: 40pt; margin-top: 30pt; page-break-inside: avoid; }
        .closing > * { position: absolute; white-space: nowrap; }
        .sign-line { top: 11pt; width: 120pt; border-top: 1pt solid #000; }
        .sign-label { top: 17pt; width: 120pt; text-align: center; font-size: 8.75pt; font-weight: bold; }
        .total { left: 348pt; top: 0; font-size: 8.75pt; font-weight: bold; }
        .total-value { right: 0; top: 0; font-size: 8.75pt; font-weight: bold; }
        .page-number { position: fixed; right: -29.2pt; bottom: -20pt; font-size: 8.95pt; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="page-number"></div>

    <div class="head">
        @if ($logo)<img class="logo" src="{{ $logo }}" alt="González, Alonzo y Asociados">@endif
        <div class="firm">GONZÁLEZ, ALONZO Y ASOCIADOS S.C.P</div>
        <div class="title">Total de Horas Laboradas</div>
        <div class="range"><span><b>Del:</b>&nbsp; {{ $from }}</span><b>Al:</b>&nbsp; {{ $to }}</div>
        <div class="meta" style="top: 67.3pt"><b>Fecha:</b><span class="v">{{ $generatedAt }}</span></div>
        <div class="meta" style="top: 79.4pt"><b>Persona:</b><span class="v">{{ $employee['name'] }}</span></div>
        <div class="meta" style="top: 91.4pt"><b>Departamento:</b><span class="v">{{ $department ?? '' }}</span></div>
    </div>

    <table>
        <thead>
            {{-- Mismas columnas que el original: Fecha en 82.3pt, Cliente en 130.3pt, Actividades en 328.3pt y Horas alineadas a 569.1pt --}}
            <tr><th class="c-date" style="width: 9.86%">Fecha</th><th class="c-client" style="width: 40.67%">Cliente</th><th class="c-activity" style="width: 41.43%">Actividades</th><th class="c-hours" style="width: 8.04%">Horas</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="c-date">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                    <td class="c-client">{{ $row['customer'] }}</td>
                    <td class="c-activity">{{ $row['activity'] }}@if ($row['description'] !== '')<span class="desc">{{ $row['description'] }}</span>@endif</td>
                    <td class="c-hours">{{ number_format($row['seconds'] / 3600, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty"></td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="closing">
        <div class="total">Total de Horas Laboradas:</div>
        <div class="total-value">{{ number_format($totalSeconds / 3600, 2) }}</div>
        <div class="sign-line" style="left: 0"></div>
        <div class="sign-label" style="left: 0">Supervisó</div>
        <div class="sign-line" style="left: 162pt"></div>
        <div class="sign-label" style="left: 162pt">Revisó</div>
    </div>
</body>
</html>
