<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Guía de uso {{ $serial }}</title>
    <style>
        @page { size: letter; margin: 0; }
        * {
            box-sizing: border-box;
            color: #000000;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9pt;
            font-weight: 400 !important;
            line-height: 2;
        }
        html, body { margin: 0; padding: 0; }
        .pdf-page { position: relative; box-sizing: content-box; width: 504pt; height: 646pt; margin: 0; padding: 90pt 54pt 54pt; overflow: hidden; page-break-after: always; }
        .pdf-page:last-child { page-break-after: auto; }
        .page-decoration { position: absolute; top: 0; left: 0; width: 612pt; height: 792pt; }
        .meta { width: 100%; margin: 0 0 18pt; border-collapse: collapse; table-layout: fixed; }
        .meta td { padding: 0; vertical-align: top; }
        .meta td:first-child { width: 37%; }
        .meta td:last-child { width: 63%; text-align: right; }
        .document-heading { width: 100%; margin: 0 0 18pt; border-collapse: collapse; table-layout: fixed; }
        .document-heading td { width: 50%; padding: 0; vertical-align: top; }
        .document-heading td:first-child { text-align: left; }
        .document-heading td:last-child { text-align: right; }
        .addressee { margin: 0 0 18pt; text-align: justify; }
        .body-copy { margin: 0 0 18pt; text-align: justify; }
        .section-title { margin: 0; }
        .questions { margin: 0 0 18pt; }
        .question-block { margin: 0; }
        .question-heading, .detail-row { position: relative; margin: 0; padding: 0 0 0 24pt; text-align: justify; page-break-inside: avoid; }
        .question-number, .detail-marker { position: absolute; top: 0; left: 0; width: 18pt; text-align: right; white-space: nowrap; }
        .closing-block { page-break-inside: avoid; }
        .signature { width: 330pt; margin: 0 auto; text-align: center; page-break-inside: avoid; }
        .signature-space { position: relative; width: 200pt; height: 63pt; margin: 0 auto; overflow: hidden; }
        .signature-image { position: absolute; top: -32pt; left: -22pt; width: 240pt; height: auto; }
        .signature-name { margin-top: 0; }
    </style>
</head>
<body>
    @foreach ($items as $itemNumber => $item)
        <section class="pdf-page">
            @if ($pageDecoration)<img class="page-decoration" src="{{ $pageDecoration }}#page-{{ $itemNumber }}" alt="DataMID">@endif

            <main>
                <table class="meta" aria-label="Folio y fecha"><tr><td>Folio: {{ $serial }}</td><td>{{ $issuedAtLong }}</td></tr></table>
                <table class="document-heading" aria-label="Título y apartado de la guía"><tr><td>{{ $title }}</td><td>Apartado: {{ $label }}</td></tr></table>

                @if ($loop->first)
                    <p class="addressee">{{ $addressee }}</p>
                    <p class="body-copy">Por medio de la presente nos permitimos compartirle la guía de uso del apartado {{ $label }}. {{ $summary }}</p>
                    <p class="body-copy">Ruta de acceso: {{ $menu }}</p>
                @endif

                <p class="section-title">Pregunta {{ $itemNumber + 1 }} de {{ count($items) }}</p>
                <div class="questions">
                    <div class="question-block">
                        <div class="question-heading"><span class="question-number">{{ $itemNumber + 1 }}.</span>{{ $item['question'] }}</div>
                        <div class="detail-row"><span class="detail-marker">&mdash;</span>{{ $item['answer'] }}</div>
                        @foreach ($item['steps'] as $stepNumber => $step)<div class="detail-row"><span class="detail-marker">{{ $stepNumber + 1 }}.</span>{{ $step }}</div>@endforeach
                    </div>
                </div>

                @if ($loop->last)
                    <div class="closing-block">
                        <p class="body-copy">En espera de que la presente guía sea de su utilidad, quedamos a sus órdenes para cualquier duda adicional y esperamos poder servirles ahora o en un futuro.</p>
                        <div class="signature">
                            <div class="signature-space">@if ($signatureImage)<img class="signature-image" src="{{ $signatureImage }}" alt="Firma">@endif</div>
                            <div class="signature-name">{{ $responsible }} · Soporte técnico</div>
                        </div>
                    </div>
                @endif
            </main>
        </section>
    @endforeach

</body>
</html>
