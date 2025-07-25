<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class AdminPasswordReset extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = $this->resetUrl($notifiable);
        $count = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('🔐 Reset Password Admin - ' . config('app.name'))
            ->greeting('Halo Admin!')
            ->line('Anda menerima email ini karena kami menerima permintaan reset password untuk akun admin Anda.')
            ->action('Reset Password', $url)
            ->line("Link reset password ini akan kedaluarsa dalam {$count} menit.")
            ->line('Jika Anda tidak meminta reset password, abaikan email ini.')
            ->line('Demi keamanan, jangan bagikan link ini kepada siapapun.')
            ->salutation('Salam,')
            ->salutation('Tim ' . config('app.name'))
            ->with([
                'actionColor' => 'primary',
                'displayableActionUrl' => $url,
            ]);
    }

    protected function resetUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addMinutes(Config::get('auth.passwords.'.Config::get('auth.defaults.passwords').'.expire', 60)),
            [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]
        );
    }
}