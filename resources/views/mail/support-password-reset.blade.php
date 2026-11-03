<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:30px 12px;background:#f4f4f5;color:#18181b;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6">
    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;margin:0 auto;border:1px solid #d4d4d8;border-radius:12px;background:#fff">
        <tr><td style="padding:28px 32px 20px;border-bottom:3px solid #1a3a6b">
            <div style="color:#1a3a6b;font-size:23px;letter-spacing:.02em">DataMID</div>
            <div style="color:#52525b;font-size:12px">Informática, tecnología y equipos · Soporte técnico</div>
        </td></tr>
        <tr><td style="padding:30px 32px">
            <h1 style="margin:0 0 22px;font-size:21px;font-weight:normal;color:#18181b">Restablecer contraseña</h1>
            @if ($isPreview)
                <p style="margin:0 0 18px;padding:12px;border:1px solid #d4d4d8;border-radius:8px">Correo de prueba: no contiene un enlace de recuperación activo.</p>
            @endif
            <p style="margin:0 0 18px">Hola{{ filled($recipientName) ? ', '.$recipientName : '' }}:</p>
            <p style="margin:0 0 22px">Recibimos una solicitud para restablecer la contraseña de tu cuenta. Utiliza el siguiente botón para crear una nueva.</p>
            @unless ($isPreview)
                <p style="margin:0 0 22px"><a href="{{ $resetUrl }}" style="display:inline-block;border-radius:8px;background:#1a3a6b;padding:12px 20px;color:#fff;text-decoration:none">Crear nueva contraseña</a></p>
                <p style="margin:0 0 22px;color:#52525b;font-size:13px">El enlace vence en {{ $expiresInMinutes }} minutos. Si no solicitaste este cambio, puedes ignorar este correo.</p>
                <p style="margin:0;color:#52525b;font-size:12px;overflow-wrap:anywhere">Si el botón no funciona, copia este enlace en tu navegador:<br><a href="{{ $resetUrl }}" style="color:#1a3a6b">{{ $resetUrl }}</a></p>
            @endunless
        </td></tr>
        <tr><td style="padding:18px 32px;border-top:1px solid #d4d4d8;color:#52525b;font-size:12px">Soporte DataMID<br>{{ config('backup-reports.email.from_address') }}</td></tr>
    </table>
</body>
</html>
