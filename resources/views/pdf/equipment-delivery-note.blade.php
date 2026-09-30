<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Hoja de entrega {{ $report->folio }}</title>
    <style>
        @page { size: letter; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #230944; font-family: DejaVu Sans, sans-serif; font-size: 8pt; }
        .page { position: relative; width: 612pt; height: 792pt; overflow: hidden; background: #fff; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .header-decoration { position: absolute; top: 0; left: 0; width: 133pt; }
        .brand-logo { position: absolute; top: 17pt; right: 54pt; width: 105pt; max-height: 34pt; object-fit: contain; }
        .footer-decoration { position: absolute; bottom: 0; left: 0; width: 612pt; height: auto; }
        .document-title { position: absolute; top: 80pt; right: 54pt; margin: 0; font-size: 15pt; font-weight: 700; }
        .title-rule { position: absolute; top: 105pt; right: 54pt; width: 368pt; border-top: 1.2pt solid #3e3a8f; }
        .folio { position: absolute; top: 121pt; left: 190pt; font-size: 9pt; font-weight: 700; }
        .copy-badge { position: absolute; top: 117pt; right: 54pt; min-width: 86pt; padding: 5pt 10pt; border-radius: 9pt; color: #fff; background: #34277c; font-size: 7pt; font-weight: 700; text-align: center; }
        .content { position: absolute; top: 180pt; left: 54pt; width: 504pt; }
        table { width: 100%; border-collapse: separate; border-spacing: 6pt 5pt; margin: -5pt -6pt 0; }
        td { vertical-align: top; }
        .field { min-height: 42pt; padding: 6pt 8pt; border: .45pt solid #828181; border-radius: 4pt; background: #fff; }
        .field.tall { min-height: 50pt; }
        .field.condition { height: 90pt; }
        .field.observations { height: 70pt; }
        .label { margin-bottom: 5pt; color: #3e3a8f; font-size: 6.5pt; font-weight: 700; text-transform: uppercase; }
        .value { color: #230944; font-size: 8.2pt; line-height: 1.28; overflow-wrap: anywhere; }
        .photo-field { height: 90pt; padding: 6pt 7pt; border: .45pt solid #828181; border-radius: 4pt; }
        .photo-field .label { margin-bottom: 3pt; }
        .photo { display: block; width: 100%; height: 65pt; object-fit: contain; }
        .no-photo { padding-top: 25pt; color: #828181; font-size: 7.5pt; font-style: italic; text-align: center; }
        .confirmation { margin-top: 8pt; color: #828181; font-size: 7pt; line-height: 1.35; }
        .signatures { position: absolute; top: 676pt; left: 64pt; width: 484pt; }
        .signature { display: inline-block; width: 202pt; border-top: .8pt solid #262360; padding-top: 8pt; color: #230944; font-size: 7pt; font-weight: 700; text-align: center; }
        .signature.right { float: right; }
        .created-by { position: absolute; top: 710pt; left: 64pt; width: 484pt; color: #828181; font-size: 6.5pt; text-align: center; }
    </style>
</head>
<body>
    @foreach ($copies as $copy)
        <section class="page">
            @if ($headerDecoration)<img class="header-decoration" src="{{ $headerDecoration }}" alt="">@endif
            @if ($logo)<img class="brand-logo" src="{{ $logo }}" alt="DataMID">@endif
            @if ($footerDecoration)<img class="footer-decoration" src="{{ $footerDecoration }}" alt="">@endif

            <h1 class="document-title">HOJA DE ENTREGA Y RECEPCIÓN</h1>
            <div class="title-rule"></div>
            <div class="folio">Folio: {{ $report->folio }}</div>
            <div class="copy-badge">{{ $copy === 'datamid' ? 'COPIA DATAMID' : 'COPIA CLIENTE' }}</div>

            <div class="content">
                <table>
                    <tr>
                        <td colspan="2" style="width: 67%"><div class="field"><div class="label">Cliente</div><div class="value">{{ $report->customer_name }}</div></div></td>
                        <td style="width: 33%"><div class="field"><div class="label">Contacto</div><div class="value">{{ $report->customer_contact ?: '—' }}</div></div></td>
                    </tr>
                    <tr>
                        <td style="width: 33.33%"><div class="field"><div class="label">Movimiento</div><div class="value">{{ $report->movementLabel() }}</div></div></td>
                        <td style="width: 33.33%"><div class="field"><div class="label">Tipo de equipo</div><div class="value">{{ $report->equipment_type }}</div></div></td>
                        <td style="width: 33.33%"><div class="field"><div class="label">Fecha</div><div class="value">{{ $report->created_at->timezone(config('support.timezone'))->format('d/m/Y H:i') }}</div></div></td>
                    </tr>
                    <tr>
                        <td><div class="field"><div class="label">Marca</div><div class="value">{{ $report->brand ?: '—' }}</div></div></td>
                        <td><div class="field"><div class="label">Modelo</div><div class="value">{{ $report->model }}</div></div></td>
                        <td><div class="field"><div class="label">Número de serie</div><div class="value">{{ $report->serial_number ?: 'SIN SERIE' }}</div></div></td>
                    </tr>
                    <tr>
                        <td colspan="3"><div class="field tall"><div class="label">Accesorios recibidos / entregados</div><div class="value">{{ $report->accessories ?: '—' }}</div></div></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="width: 63%"><div class="field condition"><div class="label">Estado físico</div><div class="value">{{ $report->conditionLabel() }}</div></div></td>
                        <td style="width: 37%"><div class="photo-field"><div class="label">Fotografía</div>@if ($photo)<img class="photo" src="{{ $photo }}" alt="Fotografía del equipo">@else<div class="no-photo">Sin fotografía</div>@endif</div></td>
                    </tr>
                    <tr>
                        <td colspan="3"><div class="field observations"><div class="label">Observaciones</div><div class="value">{{ $report->observations ?: '—' }}</div></div></td>
                    </tr>
                </table>
                <p class="confirmation">Las partes confirman que los datos y el estado descritos corresponden al equipo al momento de esta operación.</p>
            </div>

            <div class="signatures"><span class="signature">FIRMA DATAMID</span><span class="signature right">FIRMA DEL CLIENTE</span></div>
            <div class="created-by">Registro generado por {{ trim(($report->creator?->name ?? '').' '.($report->creator?->last_name ?? '')) ?: 'Administración DataMID' }}</div>
        </section>
    @endforeach
</body>
</html>
