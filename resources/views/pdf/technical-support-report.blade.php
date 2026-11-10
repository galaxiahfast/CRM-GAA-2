<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de soporte técnico {{ $serial }}</title>
    <style>
        @page { size: {{ data_get($details, 'report_kind') === 'hardware_inventory' ? '11in 8.5in' : 'letter' }}; margin: 90pt 54pt 54pt; }
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
        body.inventory-mode .footer-decoration { width: 792pt; }
        .meta { width: 100%; margin: 0 0 18pt; border-collapse: collapse; table-layout: fixed; }
        .meta td { padding: 0; vertical-align: top; }
        .meta td:first-child { width: 37%; }
        .meta td:last-child { width: 63%; text-align: right; }
        .document-heading { width: 100%; margin: 0 0 18pt; border-collapse: collapse; table-layout: fixed; }
        .document-heading td { padding: 0; vertical-align: top; }
        .document-heading td:first-child { width: 40%; }
        .document-heading td:last-child { width: 60%; }
        .document-heading td:first-child { text-align: left; }
        .document-heading td:last-child { text-align: right; }
        .addressee { margin: 0 0 18pt; text-align: justify; }
        .body-copy { margin: 0 0 18pt; text-align: justify; }
        .section-title { margin: 0; }
        .support-list { margin: 0 0 18pt; padding: 0 0 0 24pt; list-style: decimal; }
        .detail-sublist { width: calc(100% - 24pt); margin: 0 0 0 24pt; border-collapse: collapse; }
        .support-list li { margin: 0; text-align: justify; }
        .support-list > li { page-break-inside: avoid; }
        .detail-marker-cell { width: 24pt; padding: 0 6pt 0 0; vertical-align: top; line-height: 2; text-align: right; white-space: nowrap; }
        .detail-text { padding: 0; line-height: 2; text-align: justify; }
        .spec-table { width: 100%; margin: 0 0 18pt; border-collapse: collapse; table-layout: auto; }
        .spec-table td { padding: 4pt 7pt; vertical-align: top; }
        .spec-table .spec-marker { padding-right: 4pt; text-align: right; white-space: nowrap; }
        body.inventory-mode * { line-height: 1.45; }
        body.inventory-mode .meta, body.inventory-mode .document-heading,
        body.inventory-mode .addressee, body.inventory-mode .body-copy,
        body.inventory-mode .spec-table { margin-bottom: 11pt; }
        body.inventory-mode .spec-table td { padding: 2pt 7pt; }
        body.inventory-mode .signature-space { height: 55pt; }
        .closing-block { page-break-inside: avoid; }
        .signature { width: 330pt; margin: 0 auto; text-align: center; page-break-inside: avoid; }
        .signature-space { position: relative; width: 200pt; height: 63pt; margin: 0 auto; overflow: hidden; }
        .signature-image { position: absolute; top: -32pt; left: -22pt; width: 240pt; height: auto; }
        .signature-name { margin-top: 0; }
    </style>
</head>
<body @if (data_get($details, 'report_kind') === 'hardware_inventory') class="inventory-mode" @endif>
    @if ($headerDecoration)<img class="header-decoration" src="{{ $headerDecoration }}" alt="">@endif
    @if ($logo)<img class="brand-logo" src="{{ $logo }}" alt="DataMID">@endif
    @if ($footerDecoration)<img class="footer-decoration" src="{{ $footerDecoration }}" alt="">@endif

    @php
        $versions = collect(data_get($details, 'versions_applied', []))->filter()->values();
    @endphp

    <main>
        <table class="meta" aria-label="Folio y fecha"><tr><td>Folio: {{ $serial }}</td><td>{{ $issuedAtLong }}</td></tr></table>
        <table class="document-heading" aria-label="Título y destinatario del servicio"><tr><td>{{ $title }}</td><td>Departamento o cliente: {{ $serviceRecipient }}</td></tr></table>
        @if (data_get($details, 'contact_email'))
            <p class="body-copy">Correo de contacto: {{ data_get($details, 'contact_email') }}</p>
        @endif
        <p class="addressee">{{ $addressee }}</p>
        @if (data_get($details, 'report_kind') === 'hardware_inventory')
            <p class="body-copy">Se registran las características actuales del equipo para que el área correspondiente prepare una cotización. Este documento es únicamente informativo; no contiene precios ni una propuesta de compra.</p>
            <p class="section-title">Características registradas</p>
            <table class="spec-table" aria-label="Características del equipo">
                <colgroup><col style="width: 3%"><col style="width: 27%"><col style="width: 70%"></colgroup>
                @foreach (data_get($details, 'hardware_specs', []) as $spec)
                    <tr><td class="spec-marker">&mdash;</td><td class="spec-label">{{ $spec['label'] }}</td><td>{{ $spec['value'] }}</td></tr>
                @endforeach
            </table>
        @else
        <p class="body-copy">Por medio de la presente nos permitimos informarle del soporte solicitado.</p>

        @if (count($supports))
            <p class="section-title">Servicios realizados</p>
            <ol class="support-list">
                @foreach ($supports as $support)
                    @php($quantity = (int) ($support['quantity'] ?? 1))
                    @php($targets = collect($support['targets'] ?? [])->filter()->values())
                    <li>
                        {{ $support['title'] }} ({{ $quantity }} {{ $quantity === 1 ? 'vez' : 'veces' }})
                        @if (str_ends_with((string) ($support['key'] ?? ''), '_server_update') && $versions->isNotEmpty())
                            <table class="detail-sublist" role="presentation">
                                @foreach ($versions as $version)<tr><td class="detail-marker-cell">&mdash;</td><td class="detail-text">{{ $version }}</td></tr>@endforeach
                            </table>
                        @endif
                        @if ($targets->isNotEmpty())
                            <table class="detail-sublist" role="presentation">
                                @foreach ($targets as $target)<tr><td class="detail-marker-cell">&mdash;</td><td class="detail-text">{{ $target }}</td></tr>@endforeach
                            </table>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
        @if (data_get($details, 'summary'))
            <p class="body-copy">Observaciones: {{ data_get($details, 'summary') }}</p>
        @endif
        @endif
        <div class="closing-block">
            @if (data_get($details, 'report_kind') !== 'hardware_inventory' && ! data_get($details, 'omit_closing_paragraph'))
                <p class="body-copy">En espera de que el presente sea de su interés y responda a sus expectativas, quedamos a sus órdenes para cualquier comentario adicional y esperamos poder servirles ahora o en un futuro.</p>
            @endif
            <div class="signature">
                <div class="signature-space">@if ($signatureImage)<img class="signature-image" src="{{ $signatureImage }}" alt="Firma">@endif</div>
                <div class="signature-name">{{ $responsible }} · Soporte técnico</div>
            </div>
        </div>
    </main>

</body>
</html>
