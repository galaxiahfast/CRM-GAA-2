<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Conversación con el asistente</title>
    <style>
        @page { margin: 38px; }
        body { color: #18181b; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.55; }
        h1 { margin: 0; font-size: 20px; }
        .meta { margin-top: 6px; color: #71717a; }
        .divider { margin: 20px 0; border-top: 1px solid #d4d4d8; }
        .message { margin-bottom: 14px; padding: 14px; border: 1px solid #d4d4d8; border-radius: 8px; page-break-inside: avoid; }
        .user { margin-left: 80px; background: #f4f4f5; }
        .assistant { margin-right: 80px; background: #ffffff; }
        .author { margin-bottom: 6px; font-weight: bold; }
        .empty { padding: 30px; color: #71717a; text-align: center; }
    </style>
</head>
<body>
    <h1>Conversación con el asistente</h1>
    <div class="meta">Generado por {{ $generatedBy }} · {{ $generatedAt }}</div>
    <div class="divider"></div>

    <div class="message assistant">
        <div class="author">Asistente DataMID</div>
        ¡Hola! Soy el asistente de ayuda. Elige una de las preguntas disponibles y te explicaré cómo realizar esa acción.
    </div>

    @forelse ($conversation as $exchange)
        <div class="message user">
            <div class="author">{{ $generatedBy }}</div>
            {{ $exchange['question'] }}
        </div>
        <div class="message assistant">
            <div class="author">Asistente DataMID</div>
            {{ $exchange['answer'] }}
        </div>
    @empty
        <div class="empty">Todavía no se han realizado preguntas en esta conversación.</div>
    @endforelse
</body>
</html>
