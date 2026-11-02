<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class SupportPasswordReset extends ResetPassword
{
    public function toMail($notifiable)
    {
        return parent::toMail($notifiable)
            ->mailer('backup_reports')
            ->from(
                (string) config('backup-reports.email.from_address'),
                (string) config('backup-reports.email.from_name'),
            );
    }
}
