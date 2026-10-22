<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de soporte técnico {{ $serial }}</title>
    <style>
        @page { size: letter; margin: 90pt 54pt 54pt; }
        * {
            box-sizing: border-box;
            color: #000000;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9pt;
            font-weight: 400 !important;
            line-height: 2;
        }
        body { margin: 0; padding: 0; }
        .header-decoration { position: fixed; top: -90pt; left: -54pt; width: 108pt; }
        .brand-logo { position: fixed; top: -62pt; right: 0; width: 120pt; max-height: 42pt; object-fit: contain; }
        .footer-decoration { position: fixed; right: -54pt; bottom: -54pt; left: -54pt; width: 612pt; }
        .meta { width: 100%; margin: 0 0 18pt; border-collapse: collapse; table-layout: fixed; }
        .meta td { padding: 0; vertical-align: top; }
        .meta td:first-child { width: 37%; }
        .meta td:last-child { width: 63%; text-align: right; }
        .document-title { margin: 0 0 18pt; text-align: center; }
        .addressee { margin: 0 0 18pt; text-align: justify; }
        .body-copy { margin: 0 0 18pt; text-align: justify; }
        .section-title { margin: 0; }
        .support-list { margin: 0 0 18pt; padding: 0 0 0 24pt; list-style: decimal; }
        .version-sublist { margin: 0; padding: 0 0 0 24pt; list-style: none; }
        .support-list li, .version-sublist li { margin: 0; text-align: justify; }
        .signature { width: 330pt; margin: 0 auto; text-align: center; page-break-inside: avoid; }
        .signature-space { position: relative; width: 200pt; height: 63pt; margin: 0 auto; overflow: hidden; }
        .signature-image { position: absolute; top: -32pt; left: -22pt; width: 240pt; height: auto; }
        .signature-name { margin-top: 0; }
    </style>
</head>
<body>
    @if ($headerDecoration)<img class="header-decoration" src="{{ $headerDecoration }}" alt="">@endif
    @if ($logo)<img class="brand-logo" src="{{ $logo }}" alt="DataMID">@endif
    @if ($footerDecoration)<img class="footer-decoration" src="{{ $footerDecoration }}" alt="">@endif

    @php
        $versions = collect(data_get($details, 'versions_applied', []))->filter()->values();
    @endphp

    <main>
        <table class="meta" aria-label="Folio y fecha"><tr><td>Folio: {{ $serial }}</td><td>{{ $issuedAtLong }}</td></tr></table>
        <p class="document-title">{{ $title }}</p>
        <p class="addressee">{{ $addressee }}</p>
        <p class="body-copy">Por medio de la presente nos permitimos informarle del soporte solicitado.</p>

        @if (count($supports))
            <p class="section-title">Servicios realizados</p>
            <ol class="support-list">
                @foreach ($supports as $support)
                    @php($quantity = (int) ($support['quantity'] ?? 1))
                    <li>
                        {{ $support['title'] }} ({{ $quantity }} {{ $quantity === 1 ? 'vez' : 'veces' }})
                        @if (str_ends_with((string) ($support['key'] ?? ''), '_server_update') && $versions->isNotEmpty())
                            <ul class="version-sublist">
                                @foreach ($versions as $version)<li>{{ $loop->parent->iteration }}.{{ $loop->iteration }} {{ $version }}</li>@endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
        <p class="body-copy">En espera de que el presente sea de su interés y responda a sus expectativas, quedamos a sus órdenes para cualquier comentario adicional y esperamos poder servirles ahora o en un futuro.</p>
        <div class="signature">
            <div class="signature-space">@if ($signatureImage)<img class="signature-image" src="{{ $signatureImage }}" alt="Firma">@endif</div>
            <div class="signature-name">{{ $responsible }} · Soporte técnico</div>
        </div>
    </main>

</body>
</html>
