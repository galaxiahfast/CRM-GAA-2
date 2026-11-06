<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo para confirmar que el buzón es real. Se manda con el mismo correo SMTP de la empresa que ya usan los
 * reportes de respaldos y la recuperación de contraseña, y el enlace vence a los 60 minutos.
 */
class VerifyAccountEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirma tu correo | DataMID')
            ->mailer('backup_reports')
            ->from(
                (string) config('backup-reports.email.from_address'),
                (string) config('backup-reports.email.from_name'),
            )
            ->view('mail.verify-email', [
                'recipientName' => $notifiable->name,
                'verificationUrl' => $this->verificationUrl($notifiable),
                'expiresInMinutes' => (int) config('auth.verification.expire', 60),
            ]);
    }
}
