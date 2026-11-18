<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} {{ $serial }}</title>
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
        .pdf-page { position: relative; box-sizing: content-box; width: 504pt; height: 646pt; margin: 0; padding: 90pt 54pt 54pt; overflow: hidden; }
        .page-decoration { position: absolute; z-index: -1; top: 0; left: 0; width: 612pt; height: 792pt; }
        .meta, .document-heading, .data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .meta { margin: 0 0 18pt; }
        .meta td, .document-heading td { padding: 0; vertical-align: top; }
        .meta td:first-child { width: 37%; }
        .meta td:last-child { width: 63%; text-align: right; }
        .document-heading { margin: 0 0 18pt; }
        .document-heading td { width: 50%; }
        .document-heading td:last-child { text-align: right; }
        .body-copy { margin: 0 0 18pt; text-align: justify; }
        .section-title { margin: 0; }
        .data { margin: 0 0 18pt; }
        .data td { padding: 0; vertical-align: top; text-align: left; }
        .data td.label { width: 40%; padding-left: 24pt; }
        .data td.value { width: 60%; }
        .description { margin: 0 0 18pt; padding-left: 24pt; text-align: justify; }
    </style>
</head>
<body>
    <section class="pdf-page">
        @if ($pageDecoration)<img class="page-decoration" src="{{ $pageDecoration }}" alt="DataMID">@endif

        <main>
            <table class="meta" aria-label="Folio y fecha"><tr><td>Folio: {{ $serial }}</td><td>{{ $issuedAtLong }}</td></tr></table>
            <table class="document-heading" aria-label="Título y colaborador"><tr><td>{{ $title }}</td><td>Colaborador: {{ $fullName }}</td></tr></table>

            <p class="body-copy">Por medio de la presente se comparte la ficha de perfil de {{ $fullName }}, con la información personal, organizacional y de actividad registrada en el CRM. Documento solicitado por {{ $requestedBy }}.</p>

            @foreach ($sections as $sectionTitle => $rows)
                <p class="section-title">{{ $loop->iteration }}. {{ $sectionTitle }}</p>
                <table class="data">
                    @foreach ($rows as $label => $value)
                        <tr><td class="label">{{ $label }}:</td><td class="value">{{ $value }}</td></tr>
                    @endforeach
                </table>
            @endforeach

            @if ($description)
                <p class="section-title">{{ count($sections) + 1 }}. Acerca de {{ \Illuminate\Support\Str::before($fullName, ' ') }}</p>
                <p class="description">{{ $description }}</p>
            @endif
        </main>
    </section>
</body>
</html>
