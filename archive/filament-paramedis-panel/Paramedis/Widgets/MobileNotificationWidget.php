<?php

namespace App\Filament\Paramedis\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class MobileNotificationWidget extends Widget
{
    protected static string $view = 'filament.paramedis.widgets.mobile-notification-widget';
    protected static ?int $sort = 4;
    
    public function getViewData(): array
    {
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $this->getNotificationType($notification->type),
                    'icon' => $this->getNotificationIcon($notification->type),
                    'color' => $this->getNotificationColor($notification->type),
                    'title' => $notification->data['title'] ?? 'Notifikasi',
                    'message' => $notification->data['message'] ?? '',
                    'time' => $notification->created_at->diffForHumans(),
                    'read' => !is_null($notification->read_at),
                ];
            });
            
        return [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ];
    }
    
    protected function getNotificationType($type): string
    {
        return match($type) {
            'App\\Notifications\\JaspelApproved' => 'Jaspel',
            'App\\Notifications\\ScheduleChanged' => 'Jadwal',
            'App\\Notifications\\AttendanceReminder' => 'Presensi',
            default => 'Info',
        };
    }
    
    protected function getNotificationIcon($type): string
    {
        return match($type) {
            'App\\Notifications\\JaspelApproved' => 'heroicon-o-currency-dollar',
            'App\\Notifications\\ScheduleChanged' => 'heroicon-o-calendar',
            'App\\Notifications\\AttendanceReminder' => 'heroicon-o-clock',
            default => 'heroicon-o-bell',
        };
    }
    
    protected function getNotificationColor($type): string
    {
        return match($type) {
            'App\\Notifications\\JaspelApproved' => 'success',
            'App\\Notifications\\ScheduleChanged' => 'warning',
            'App\\Notifications\\AttendanceReminder' => 'danger',
            default => 'primary',
        };
    }
    
    public function markAsRead($notificationId): void
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }
    
    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }
}