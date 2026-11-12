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
        .section-title { margin-bottom: 11pt; }
        .day-heading { margin: 11pt 0 7pt; }
        .empty { margin: 7pt 0; }
        .detail { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .detail th { padding: 5pt 6pt; text-align: left; background: #f3f3f3; font-weight: normal; }
        .detail td { padding: 6pt; vertical-align: top; border-bottom: .45pt dotted #aaa; word-wrap: break-word; }
        .detail .num { text-align: right; white-space: nowrap; }
        .detail .c-customer { width: 26%; }
        .detail .c-activity { width: 24%; }
        .detail .c-desc { width: 38%; }
        .detail .c-hours { width: 12%; }
        .detail .s-name { width: 70%; }
        .detail .s-time { width: 18%; }
        .detail .s-share { width: 12%; }
        .total { margin-top: 11pt; font-weight: bold; text-align: right; }
    </style>
</head>
<body>
    @foreach ($pages as $pageNumber => $items)
        <section class="page {{ $loop->last ? 'last' : '' }}">
            @if ($headerDecorations[$pageNumber] ?? null)<img class="header-decoration" src="{{ $headerDecorations[$pageNumber] }}" alt="">@endif
            @if ($logos[$pageNumber] ?? null)<img class="brand-logo" src="{{ $logos[$pageNumber] }}" alt="DataMID">@else<div class="fallback-brand">DataMID</div>@endif

            <div class="page-content">
                @if ($pageNumber === 0)
                    <table class="meta"><tr>
                        <td>{{ $generatedAt->locale('es')->translatedFormat('d \d\e F \d\e Y') }} a las {{ $generatedAt->format('h:i:s a') }}</td>
                        <td>{{ $filename }}</td>
                    </tr></table>
                    <h1>Total de Horas Laboradas</h1>
                    <p class="responsible">Colaborador: <strong>{{ $employee['name'] }}</strong></p>
                    <p>Clave: {{ str_pad((string) $employee['id'], 5, '0', STR_PAD_LEFT) }}</p>
                    <p>Periodo: Del {{ $from }} Al {{ $to }}</p>
                    <div class="divider" style="margin-bottom: 0"></div>
                @endif

                @php($open = null)
                @foreach ($items as $item)
                    @php($kind = $item['type'])
                    @if ($open !== null && $open !== $kind)
                        @if ($open === 'srow' || $open === 'row')</tbody></table>@endif
                        @php($open = null)
                    @endif

                    @if ($kind === 'divider')
                        <div class="divider"></div>
                    @elseif ($kind === 'section')
                        <h2 class="section-title" style="margin-top: 13pt">{{ $item['title'] }}</h2>
                    @elseif ($kind === 'empty')
                        <p class="empty">{{ $item['text'] }}</p>
                    @elseif ($kind === 'day')
                        <h3 class="day-heading">{{ $item['date'] }} · Total del día: {{ $item['total'] }}{{ $item['continued'] ? ' (continuación)' : '' }}</h3>
                    @elseif ($kind === 'srow')
                        @if ($open === null)
                            <table class="detail"><thead><tr><th class="s-name">Concepto</th><th class="s-time">Tiempo</th><th class="s-share">%</th></tr></thead><tbody>
                            @php($open = 'srow')
                        @endif
                        <tr><td>{{ $item['cells'][0] }}</td><td>{{ $item['cells'][1] }}</td><td>{{ $item['cells'][2] }}</td></tr>
                    @elseif ($kind === 'row')
                        @if ($open === null)
                            <table class="detail"><thead><tr><th class="c-customer">Cliente</th><th class="c-activity">Actividad</th><th class="c-desc">Descripción</th><th class="c-hours">Horas</th></tr></thead><tbody>
                            @php($open = 'row')
                        @endif
                        <tr><td>{{ $item['cells'][0] }}</td><td>{{ $item['cells'][1] }}</td><td>{{ $item['cells'][2] }}</td><td class="num">{{ $item['cells'][3] }}</td></tr>
                    @elseif ($kind === 'total')
                        <p class="total">{{ $item['text'] }}</p>
                    @endif
                @endforeach
                @if ($open !== null)</tbody></table>@endif
            </div>

            <div class="page-footer-line"><span>Control de horas / ManagerControl</span><span class="page-number">Página {{ $pageNumber + 1 }} / {{ count($pages) }}</span></div>
            @if ($footerDecorations[$pageNumber] ?? null)<div class="footer-crop"><img class="footer-decoration" src="{{ $footerDecorations[$pageNumber] }}" alt=""></div>@endif
        </section>
    @endforeach
</body>
</html>
