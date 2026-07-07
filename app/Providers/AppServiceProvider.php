<?php

namespace App\Providers;

use App\Models\Asset;
use App\Policies\AssetPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $broker = config('auth.defaults.passwords');
            $expiresIn = (int) config("auth.passwords.{$broker}.expire", 60);
            $appName = config('app.name', 'Zinus Dream');
            $supportEmail = config('mail.from.address', 'it.support@zinus.com');

            return (new MailMessage)
                ->subject('Reset Password Portal IT Zinus')
                ->view([
                    'html' => 'emails.auth.password-reset',
                    'text' => 'emails.auth.password-reset-text',
                ], [
                    'appName' => $appName,
                    'displayName' => trim((string) ($notifiable->name ?? '')) ?: 'Rekan Zinus',
                    'email' => $notifiable->getEmailForPasswordReset(),
                    'expiresIn' => $expiresIn,
                    'logoUrl' => asset('images/logo-email.png'),
                    'resetUrl' => $resetUrl,
                    'supportEmail' => $supportEmail,
                ]);
        });

        Gate::policy(Asset::class, AssetPolicy::class);
    }
}
