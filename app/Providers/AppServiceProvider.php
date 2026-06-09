<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $appUrl = $this->publicAppUrl();

        URL::forceRootUrl($appUrl);

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }

        VerifyEmail::createUrlUsing(function ($notifiable): string {
            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Welcome to BlipBlap - verify your email')
                ->view('emails.verify-email', [
                    'user' => $notifiable,
                    'verifyUrl' => $url,
                ]);
        });
    }

    private function publicAppUrl(): string
    {
        $url = rtrim((string) env('APP_URL', ''), '/');
        $host = parse_url($url, PHP_URL_HOST);

        if ($url === '' || in_array($host, [null, '', '127.0.0.1', 'localhost'], true)) {
            return 'https://blipblap.com';
        }

        return $url;
    }
}
