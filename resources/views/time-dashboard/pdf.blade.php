<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard de Tiempo</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 24px; color: #1A3A6B; margin: 0; }
        .header p { font-size: 14px; color: #555; margin: 5px 0; }
        .section { margin-bottom: 30px; page-break-inside: avoid; }
        .section h2 { font-size: 18px; border-bottom: 2px solid #1A3A6B; padding-bottom: 8px; margin-bottom: 15px; color: #1A3A6B; }
        .chart-container { text-align: center; margin: 15px 0; }
        .chart-container img { max-width: 100%; height: auto; }
        .meta { display: flex; justify-content: space-between; background: #f5f5f5; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; }
        .footer { text-align: center; color: #999; font-size: 11px; margin-top: 40px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Control de Horas</h1>
        <p>Panel de Control - Tiempo trabajado</p>
        <p><strong>Usuario:</strong> {{ $selectedUser ? $selectedUser->name . ' ' . $selectedUser->last_name : 'N/A' }}</p>
        <p><strong>Periodo:</strong> {{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if (!empty($images))
        @foreach ($images as $index => $imageData)
            <div class="section">
                <h2>{{ $index + 1 }}. {{ $imageData['title'] ?? 'Gráfica' }}</h2>
                <div class="chart-container">
                    <img src="{{ $imageData['src'] }}" alt="Gráfica {{ $index+1 }}" style="width:100%; max-width:750px;">
                </div>
            </div>
        @endforeach
    @else
        <p style="color: red;">No se recibieron gráficas.</p>
    @endif

    <div class="footer">
        Este reporte es generado automáticamente por el sistema.
    </div>
</body>
</html>