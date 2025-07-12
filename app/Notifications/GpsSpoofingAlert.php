<?php

namespace App\Notifications;

use App\Models\GpsSpoofingDetection;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class GpsSpoofingAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected GpsSpoofingDetection $detection;
    protected User $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(GpsSpoofingDetection $detection)
    {
        $this->detection = $detection;
        $this->user = $detection->user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $riskLevelEmoji = match($this->detection->risk_level) {
            'low' => '🟢',
            'medium' => '🟡',
            'high' => '🔴',
            'critical' => '🚨',
            default => '⚪',
        };

        $actionRequired = $this->detection->risk_level === 'critical' ? 'TINDAKAN SEGERA DIPERLUKAN' : 'Perlu Ditinjau';

        return (new MailMessage)
            ->subject("🚨 GPS Spoofing Terdeteksi - {$this->detection->risk_level_label}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Sistem telah mendeteksi aktivitas GPS spoofing yang mencurigakan.")
            ->line('')
            ->line("**Detail Deteksi:**")
            ->line("👤 **User:** {$this->user->name} ({$this->user->email})")
            ->line("{$riskLevelEmoji} **Risk Level:** {$this->detection->risk_level_label}")
            ->line("📊 **Risk Score:** {$this->detection->risk_score}%")
            ->line("⏰ **Waktu:** {$this->detection->attempted_at->format('d M Y H:i:s')}")
            ->line("📍 **Lokasi:** {$this->detection->latitude}, {$this->detection->longitude}")
            ->line("📋 **Jenis:** " . ucfirst($this->detection->attendance_type ?? 'Unknown'))
            ->line('')
            ->line("**Metode Spoofing Terdeteksi:**")
            ->lines($this->getDetectedMethodsList())
            ->line('')
            ->when($this->detection->spoofing_indicators, function($mail) {
                return $mail->line("**Indikator Spoofing:**")
                          ->lines($this->detection->spoofing_indicators);
            })
            ->line('')
            ->line("**{$actionRequired}**")
            ->action('🔍 Lihat Detail Deteksi', url("/admin/gps-spoofing-detections/{$this->detection->id}"))
            ->action('🗺️ Lihat di Peta', $this->detection->google_maps_url)
            ->line('')
            ->line('Silakan review deteksi ini dan ambil tindakan yang sesuai.')
            ->line('Terima kasih!')
            ->salutation('Sistem Keamanan Dokterku');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'gps_spoofing_alert',
            'title' => "🚨 GPS Spoofing Terdeteksi - {$this->detection->risk_level_label}",
            'message' => "User {$this->user->name} terdeteksi menggunakan GPS palsu",
            'detection_id' => $this->detection->id,
            'user_id' => $this->user->id,
            'risk_level' => $this->detection->risk_level,
            'risk_score' => $this->detection->risk_score,
            'detected_methods' => $this->detection->detected_methods,
            'attempted_at' => $this->detection->attempted_at,
            'coordinates' => [
                'latitude' => $this->detection->latitude,
                'longitude' => $this->detection->longitude,
            ],
            'action_url' => url("/admin/gps-spoofing-detections/{$this->detection->id}"),
            'maps_url' => $this->detection->google_maps_url,
        ];
    }

    /**
     * Get list of detected methods for email
     */
    private function getDetectedMethodsList(): array
    {
        $methods = [];
        
        if ($this->detection->mock_location_detected) {
            $methods[] = "📍 Mock Location aktif";
        }
        
        if ($this->detection->fake_gps_app_detected) {
            $methods[] = "📱 Fake GPS app terdeteksi";
        }
        
        if ($this->detection->developer_mode_detected) {
            $methods[] = "⚙️ Developer mode aktif";
        }
        
        if ($this->detection->impossible_travel_detected) {
            $methods[] = "🚀 Pergerakan tidak mungkin";
        }
        
        if ($this->detection->coordinate_anomaly_detected) {
            $methods[] = "📊 Anomali koordinat";
        }
        
        if ($this->detection->device_integrity_failed) {
            $methods[] = "🛡️ Integritas device gagal";
        }

        return empty($methods) ? ["➖ Tidak ada metode spesifik terdeteksi"] : $methods;
    }

    /**
     * Send Filament notification to admin
     */
    public static function sendFilamentNotification(GpsSpoofingDetection $detection): void
    {
        $title = match($detection->risk_level) {
            'critical' => '🚨 GPS Spoofing KRITIS Terdeteksi!',
            'high' => '🔴 GPS Spoofing Tinggi Terdeteksi!',
            'medium' => '🟡 GPS Spoofing Sedang Terdeteksi',
            'low' => '🟢 GPS Spoofing Rendah Terdeteksi',
            default => '⚪ GPS Spoofing Terdeteksi',
        };

        $color = match($detection->risk_level) {
            'critical' => 'danger',
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'info',
            default => 'gray',
        };

        FilamentNotification::make()
            ->title($title)
            ->body("User: {$detection->user->name} | Score: {$detection->risk_score}% | Metode: " . implode(', ', $detection->detected_methods))
            ->icon('heroicon-o-shield-exclamation')
            ->color($color)
            ->duration(10000)
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Lihat Detail')
                    ->url(url("/admin/gps-spoofing-detections/{$detection->id}"))
                    ->button(),
                \Filament\Notifications\Actions\Action::make('map')
                    ->label('Lihat Peta')
                    ->url($detection->google_maps_url)
                    ->openUrlInNewTab()
                    ->button(),
            ])
            ->sendToDatabase(\App\Models\User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->get());
    }
}
