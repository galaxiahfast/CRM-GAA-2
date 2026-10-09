<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: "Courier New", Courier, monospace; font-size: 7.5pt; line-height: 1.65; }
        .page { position: relative; width: 612pt; height: 792pt; overflow: hidden; background: #fff; page-break-after: always; }
        .page.last { page-break-after: avoid; }
        .header-decoration { position: absolute; top: 0; left: 0; width: 133pt; }
        .brand-logo { position: absolute; top: 27pt; right: 42pt; width: 105pt; max-height: 40pt; object-fit: contain; }
        .page-content { position: absolute; top: 100pt; left: 54pt; width: 504pt; height: 594pt; }
        .content { width: 504pt; }
        .page-footer-line { position: absolute; bottom: 52pt; left: 50pt; width: 512pt; }
        .page-footer-line .page-number { float: right; }
        .footer-crop { position: absolute; bottom: 0; left: 0; width: 612pt; height: 48pt; overflow: hidden; }
        .footer-decoration { position: absolute; top: -133pt; left: 0; width: 612pt; }
        .fallback-header-shape { position: absolute; top: 0; left: 0; width: 128pt; height: 42pt; background: #463b92; }
        .fallback-header-angle { position: absolute; top: 0; left: 92pt; width: 0; height: 0; border-top: 42pt solid #30246f; border-right: 28pt solid transparent; }
        .fallback-logo { position: absolute; top: 24pt; right: 42pt; color: #241450; font-family: Arial, sans-serif; font-size: 17pt; font-weight: bold; line-height: 1; }
        .fallback-logo small { display: block; margin-top: 3pt; font-size: 4.5pt; font-weight: bold; text-align: center; }
        .fallback-footer { position: absolute; bottom: 0; left: 0; width: 612pt; height: 32pt; background: #3d318a; color: #fff; font-family: Arial, sans-serif; font-size: 9pt; padding: 10pt 49pt 0; }
        .fallback-footer-angle { position: absolute; right: 43pt; bottom: 0; width: 0; height: 0; border-bottom: 32pt solid #2f236f; border-left: 27pt solid transparent; }
        h1, h2, h3, p { margin: 0; }
        h1, h2, h3, .section-title { font-size: 10.5pt; line-height: 1.45; font-weight: bold; }
        .meta { width: 100%; margin-bottom: 13pt; }
        .meta td { vertical-align: top; }.meta td:last-child { text-align: right; }
        .responsible { margin-top: 11pt; }.divider { margin: 13pt 0; border-top: .55pt dashed #777; }.section-title { margin-bottom: 11pt; }
        .metrics { width: 100%; margin-bottom: 13pt; table-layout: fixed; }.metrics td { padding-right: 8pt; vertical-align: top; }.metric { display: block; font-size: 10.5pt; font-weight: bold; }
        .chart { width: 100%; border-collapse: separate; border-spacing: 0 5pt; margin-bottom: 8pt; }.chart .name { width: 22%; }.chart .bar-cell { width: 48%; padding-right: 10pt; }
        .bar { height: 8pt; white-space: nowrap; font-size: 0; }.ok-bar, .bad-bar, .pending-bar { display: inline-block; height: 8pt; }.ok-bar { background: #2d6075; }.bad-bar { background: #b44735; }.pending-bar { background: #d09316; }
        .legend { margin-bottom: 7pt; }.legend span { margin-right: 17pt; }.swatch { display: inline-block; width: 7pt; height: 7pt; margin-right: 4pt; vertical-align: middle; background: #2d6075; }.swatch.bad { background: #b44735; }.swatch.pending { background: #d09316; }
        .server { margin-bottom: 14pt; page-break-inside: avoid; }.server-head { width: 100%; margin-bottom: 7pt; }.server-head td:last-child { text-align: right; }
        table.detail { width: 100%; border-collapse: collapse; table-layout: fixed; }.detail th { padding: 5pt 6pt; text-align: left; font-weight: normal; background: #f3f3f3; }.detail td { padding: 6pt; vertical-align: top; border-bottom: .45pt dotted #aaa; word-wrap: break-word; }
        .detail .profile { width: 24%; font-weight: bold; }.detail .date { width: 33%; }.detail .done { width: 16%; }.detail .result { width: 27%; }
        .error { color: #b44735; }.incident { padding: 4pt 6pt 7pt 18pt !important; color: #b44735; }.incident div { margin-top: 2pt; }
        .note { margin-top: 8pt; }.signature { margin-top: 14pt; font-weight: bold; }
    </style>
</head>
<body>
@php
    $includedServers = collect($servers)->map(function ($server) {
        $server['profiles'] = collect($server['profiles'])->where('available', true)->values()->all();
        return $server;
    })->filter(fn ($server) => $server['profiles'] !== [])->values();
    $estimatedHeight = function ($server) {
        $height = 48;
        foreach ($server['profiles'] as $profile) {
            $height += 25;
            $files = count($profile['failedFiles'] ?? []);
            $errors = $files > 0 ? 0 : count($profile['errors'] ?? []);
            $issues = count($profile['issues'] ?? []);
            if ($files + $errors + $issues > 0) $height += 18 + (($files + $errors + $issues) * 12);
        }
        return $height + 14;
    };
    $firstPageServers = [];
    $remainingServers = $includedServers->all();
    if ($remainingServers !== [] && $estimatedHeight($remainingServers[0]) <= 190) $firstPageServers[] = array_shift($remainingServers);
    $detailPages = [];
    $currentPage = [];
    $currentHeight = 0;
    foreach ($remainingServers as $server) {
        $height = $estimatedHeight($server);
        if ($currentPage !== [] && $currentHeight + $height > 500) {
            $detailPages[] = $currentPage;
            $currentPage = [];
            $currentHeight = 0;
        }
        $currentPage[] = $server;
        $currentHeight += $height;
    }
    if ($currentPage !== []) $detailPages[] = $currentPage;
    $noteOnFirstPage = $includedServers->isEmpty();
    $totalPages = 1 + count($detailPages);
    $pageNumber = 1;
@endphp

<section class="page {{ $detailPages === [] ? 'last' : '' }}">
    @if ($headerDecorations[0] ?? null)<img class="header-decoration" src="{{ $headerDecorations[0] }}" alt="">@endif
    @if ($logos[0] ?? null)<img class="brand-logo" src="{{ $logos[0] }}" alt="DataMID">@endif
    <div class="page-content"><div class="content">
        <table class="meta"><tr><td>{{ ucfirst($generatedAt->locale('es')->translatedFormat('l d \d\e F \d\e Y')) }} a las {{ $generatedAt->format('h:i:s a') }}</td><td>{{ $filename }}</td></tr></table>
        <h1>Informe técnico de respaldos</h1>
        <p class="responsible">Responsable: <strong>{{ $responsible }}</strong></p>
        <div class="divider"></div>
        <div class="section-title">01 / Resumen de ejecuciones</div>
        <table class="metrics"><tr><td><span class="metric">{{ $metrics['servers'] }}</span>Servidores con datos</td><td><span class="metric">{{ $metrics['profiles'] }}</span>Perfiles revisados</td><td><span class="metric">{{ $metrics['errors'] }}</span>Perfiles con error</td><td><span class="metric">{{ $metrics['companies'] }}</span>Empresas afectadas</td></tr></table>
        @php $maxProfiles = max(1, collect($servers)->max(fn ($server) => count($server['profiles']))); @endphp
        <table class="chart">
            @foreach ($servers as $server)
                @php
                    $correct = collect($server['profiles'])->filter(fn ($profile) => $profile['available'] && !$profile['failed'] && $profile['errors'] === [] && $profile['issues'] === [])->count();
                    $bad = collect($server['profiles'])->filter(fn ($profile) => $profile['available'] && ($profile['failed'] || $profile['errors'] !== [] || $profile['issues'] !== []))->count();
                    $pending = collect($server['profiles'])->where('available', false)->count();
                @endphp
                <tr><td class="name">{{ mb_strtoupper(preg_replace('/^COMPAQI \(|\)$/iu', '', $server['name'])) }}</td><td class="bar-cell"><div class="bar"><span class="ok-bar" style="width:{{ $correct / $maxProfiles * 100 }}%"></span><span class="bad-bar" style="width:{{ $bad / $maxProfiles * 100 }}%"></span><span class="pending-bar" style="width:{{ $pending / $maxProfiles * 100 }}%"></span></div></td><td>{{ $correct }} correctos / {{ $bad }} error / {{ $pending }} sin datos</td></tr>
            @endforeach
        </table>
        <div class="legend"><span><i class="swatch"></i>Correcto</span><span><i class="swatch bad"></i>Con error</span><span><i class="swatch pending"></i>Sin datos / pendiente</span></div>
        <p>Cobertura: {{ $metrics['profiles'] }} de {{ $metrics['totalProfiles'] }} perfiles con datos. Los pendientes o inaccesibles no se contabilizan como errores de respaldo.</p>
        <div class="divider"></div>
        <div class="section-title">02 / Detalle por servidor</div>
        @foreach ($firstPageServers as $server)
            @include('pdf.partials.server-backup-table', ['server' => $server])
        @endforeach
        @if ($noteOnFirstPage) @include('pdf.partials.server-backup-note') @endif
    </div></div>
    <div class="page-footer-line"><span>Control de respaldos / Informe técnico</span><span class="page-number">Página {{ $pageNumber++ }} / {{ $totalPages }}</span></div>
    <div class="footer-crop">@if ($footerDecorations[0] ?? null)<img class="footer-decoration" src="{{ $footerDecorations[0] }}" alt="">@endif</div>
</section>

@foreach ($detailPages as $serversOnPage)
    <section class="page {{ $loop->last ? 'last' : '' }}">
        @if ($pageNumber % 2 === 0)
            <div class="fallback-header-shape"></div><div class="fallback-header-angle"></div>
            <div class="fallback-logo">DataMID<small>Informática, tecnología y equipos</small></div>
            <div class="fallback-footer">Cel. 999 324 2664&nbsp;&nbsp;|&nbsp;&nbsp;ventas@datamid.com.mx&nbsp;&nbsp;|&nbsp;&nbsp;www.datamid.com.mx</div><div class="fallback-footer-angle"></div>
        @else
            @if ($headerDecorations[0] ?? null)<img class="header-decoration" src="{{ $headerDecorations[0] }}" alt="">@endif
            @if ($logos[0] ?? null)<img class="brand-logo" src="{{ $logos[0] }}" alt="DataMID">@endif
        @endif
        <div class="page-content"><div class="content">
            @foreach ($serversOnPage as $server)
                @include('pdf.partials.server-backup-table', ['server' => $server])
            @endforeach
            @if ($loop->last) @include('pdf.partials.server-backup-note') @endif
        </div></div>
        <div class="page-footer-line"><span>Control de respaldos / Informe técnico</span><span class="page-number">Página {{ $pageNumber++ }} / {{ $totalPages }}</span></div>
        @if ($pageNumber % 2 === 0)<div class="footer-crop">@if ($footerDecorations[0] ?? null)<img class="footer-decoration" src="{{ $footerDecorations[0] }}" alt="">@endif</div>@endif
    </section>
@endforeach
</body>
</html>
