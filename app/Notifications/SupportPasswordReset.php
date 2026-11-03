<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class SupportPasswordReset extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Restablecer contraseña | Soporte DataMID')
            ->mailer('backup_reports')
            ->from(
                (string) config('backup-reports.email.from_address'),
                (string) config('backup-reports.email.from_name'),
            )
            ->view('mail.support-password-reset', [
                'recipientName' => $notifiable->name,
                'resetUrl' => $this->resetUrl($notifiable),
                'expiresInMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                'isPreview' => false,
            ]);
    }
}
