<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Conversación de soporte</title>
    <style>
        @page { margin: 28px 34px 40px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #34445a; background: #ffffff; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.45; }
        .document-header { padding: 16px 18px; background: #1A3A6B; color: #ffffff; }
        .header-table, .summary-table, .message-table, .message-header { width: 100%; border-collapse: collapse; }
        .brand { margin-bottom: 3px; color: #c9d7e9; font-size: 7px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { margin: 0; font-size: 14px; line-height: 1.25; }
        .date-cell { width: 195px; color: #e2eaf4; font-size: 8.5px; line-height: 1.5; text-align: right; vertical-align: middle; }
        .date-label { display: block; color: #aebfd5; font-size: 6.5px; font-weight: bold; letter-spacing: .7px; text-transform: uppercase; }
        .summary { margin: 12px 0 14px; padding: 9px 12px; border: 1px solid #dbe4ef; background: #f6f8fb; }
        .summary-cell { padding: 0 12px; border-right: 1px solid #dbe4ef; vertical-align: top; }
        .summary-cell:first-child { padding-left: 0; }
        .summary-cell:last-child { padding-right: 0; border-right: 0; }
        .summary-label { display: block; margin-bottom: 2px; color: #8492a6; font-size: 6.5px; font-weight: bold; letter-spacing: .55px; text-transform: uppercase; }
        .summary-value { color: #34445a; font-size: 8.5px; font-weight: bold; }
        .day-divider { margin: 5px 0 13px; text-align: center; }
        .day-divider-line { border-top: 1px solid #dfe6ef; }
        .day-divider-label { display: inline-block; position: relative; top: -7px; padding: 0 10px; color: #738399; background: #ffffff; font-size: 7px; font-weight: bold; letter-spacing: .45px; text-transform: uppercase; }
        .message-table { margin-bottom: 10px; page-break-inside: avoid; }
        .avatar-cell { width: 42px; vertical-align: top; }
        .avatar-cell.right { padding-left: 8px; text-align: right; }
        .avatar-cell.left { padding-right: 8px; }
        .avatar, .avatar-fallback { width: 34px; height: 34px; border: 1px solid #d6e0ec; border-radius: 50%; }
        .avatar { object-fit: cover; }
        .avatar-fallback { color: #ffffff; background: #1A3A6B; font-size: 10px; font-weight: bold; line-height: 34px; text-align: center; }
        .bubble-cell { width: 76%; vertical-align: top; }
        .message-spacer { width: 24%; }
        .bubble { padding: 10px 12px; border: 1px solid #dbe3ed; background: #ffffff; }
        .mine .bubble { border-color: #b9c9e2; background: #eef4fc; }
        .message-header { padding-bottom: 7px; border-bottom: 1px solid #e5eaf1; }
        .author-cell { vertical-align: top; }
        .author { color: #26364a; font-size: 9px; font-weight: bold; }
        .email { margin-top: 1px; color: #7c8a9e; font-size: 7px; }
        .time { width: 42px; color: #8492a6; font-size: 7px; text-align: right; vertical-align: top; }
        .content { margin-top: 8px; color: #42526a; font-size: 9px; line-height: 1.55; white-space: pre-wrap; overflow-wrap: break-word; }
        .content.deleted { color: #8492a6; font-style: italic; }
        .empty { padding: 32px 20px; border: 1px dashed #cbd5e1; color: #728197; text-align: center; }
        .footer { position: fixed; right: 0; bottom: -25px; left: 0; color: #8a98aa; font-size: 7px; text-align: center; }
    </style>
</head>
<body>
    <header class="document-header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand">González Alonzo y Asociados S.C.P.</div>
                    <h1>Conversación general de soporte</h1>
                </td>
                <td class="date-cell">
                    <span class="date-label">Chat del día</span>
                    {{ ucfirst($dateLabel) }}
                </td>
            </tr>
        </table>
    </header>

    <section class="summary">
        <table class="summary-table">
            <tr>
                <td class="summary-cell">
                    <span class="summary-label">Exportado por</span>
                    <span class="summary-value">{{ $generatedBy ?: 'Usuario del sistema' }}</span>
                </td>
                <td class="summary-cell">
                    <span class="summary-label">Fecha de descarga</span>
                    <span class="summary-value">{{ $generatedAt }}</span>
                </td>
                <td class="summary-cell" style="width: 75px;">
                    <span class="summary-label">Mensajes</span>
                    <span class="summary-value">{{ count($messages) }}</span>
                </td>
            </tr>
        </table>
    </section>

    <div class="day-divider">
        <div class="day-divider-line"></div>
        <span class="day-divider-label">{{ ucfirst($dateLabel) }}</span>
    </div>

    @forelse ($messages as $message)
        @php
            $nameParts = array_values(array_filter(preg_split('/\s+/', trim($message['name']))));
            $initials = collect($nameParts)->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
        @endphp
        <table class="message-table {{ $message['is_mine'] ? 'mine' : '' }}">
            <tr>
                @if ($message['is_mine'])
                    <td class="message-spacer"></td>
                @else
                    <td class="avatar-cell left">
                        @if ($message['pdf_photo_data'] ?? null)
                            <img class="avatar" src="{{ $message['pdf_photo_data'] }}" alt="Foto de {{ $message['name'] }}">
                        @else
                            <div class="avatar-fallback">{{ $initials }}</div>
                        @endif
                    </td>
                @endif

                <td class="bubble-cell">
                    <div class="bubble">
                        <table class="message-header">
                            <tr>
                                <td class="author-cell">
                                    <div class="author">{{ $message['name'] }}</div>
                                    <div class="email">{{ $message['email'] }}</div>
                                </td>
                                <td class="time">{{ $message['time'] }}</td>
                            </tr>
                        </table>
                        @if ($message['is_deleted'] ?? false)
                            <div class="content deleted">Mensaje eliminado</div>
                        @else
                            <div class="content">{{ $message['message'] }}</div>
                        @endif
                    </div>
                </td>

                @if ($message['is_mine'])
                    <td class="avatar-cell right">
                        @if ($message['pdf_photo_data'] ?? null)
                            <img class="avatar" src="{{ $message['pdf_photo_data'] }}" alt="Foto de {{ $message['name'] }}">
                        @else
                            <div class="avatar-fallback">{{ $initials }}</div>
                        @endif
                    </td>
                @else
                    <td class="message-spacer"></td>
                @endif
            </tr>
        </table>
    @empty
        <div class="empty">No se registraron mensajes en la conversación de este día.</div>
    @endforelse

    <footer class="footer">Conversación exportada desde el Portal interno DataMID · {{ $generatedAt }}</footer>
</body>
</html>
