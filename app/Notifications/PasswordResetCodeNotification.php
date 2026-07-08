<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresIn = 10,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Zinus Dream');
        $supportEmail = config('mail.from.address', 'it.support@zinus.com');

        return (new MailMessage)
            ->subject('Kode Reset Password Portal IT Zinus')
            ->view([
                'html' => 'emails.auth.password-reset',
                'text' => 'emails.auth.password-reset-text',
            ], [
                'appName' => $appName,
                'displayName' => trim((string) ($notifiable->name ?? '')) ?: 'Rekan Zinus',
                'email' => $notifiable->getEmailForPasswordReset(),
                'expiresIn' => $this->expiresIn,
                'logoUrl' => asset('images/logo-email.png'),
                'code' => $this->code,
                'supportEmail' => $supportEmail,
            ]);
    }
}
