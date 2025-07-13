<?php

namespace App\Services;

use App\Models\PermohonanCuti;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LeaveNotificationService
{
    /**
     * Send notification when leave is approved
     */
    public static function notifyApproval(PermohonanCuti $leave): void
    {
        $pegawai = $leave->pegawai;
        $approver = $leave->approver;
        
        $message = "✅ CUTI DISETUJUI\n\n";
        $message .= "Hai {$pegawai->name},\n";
        $message .= "Permohonan cuti Anda telah DISETUJUI:\n\n";
        $message .= "📅 Periode: {$leave->tanggal_mulai->format('d/m/Y')} - {$leave->tanggal_selesai->format('d/m/Y')}\n";
        $message .= "⏰ Durasi: {$leave->durasicuti} hari\n";
        $message .= "📋 Jenis: {$leave->jenis_cuti}\n";
        $message .= "👤 Disetujui oleh: {$approver->name}\n";
        
        if ($leave->catatan_approval) {
            $message .= "💬 Catatan: {$leave->catatan_approval}\n";
        }
        
        $message .= "\n🏥 SAHABAT MENUJU SEHAT\n";
        $message .= "Sistem Dokterku";
        
        // Log notification (can be extended to send via Telegram/Email)
        Log::info("Leave Approval Notification", [
            'pegawai_id' => $pegawai->id,
            'leave_id' => $leave->id,
            'message' => $message
        ]);
        
        // TODO: Send via Telegram Bot API
        // TODO: Send via Email
        
        self::sendToTelegram($pegawai, $message);
    }
    
    /**
     * Send notification when leave is rejected
     */
    public static function notifyRejection(PermohonanCuti $leave): void
    {
        $pegawai = $leave->pegawai;
        $approver = $leave->approver;
        
        $message = "❌ CUTI DITOLAK\n\n";
        $message .= "Hai {$pegawai->name},\n";
        $message .= "Permohonan cuti Anda telah DITOLAK:\n\n";
        $message .= "📅 Periode: {$leave->tanggal_mulai->format('d/m/Y')} - {$leave->tanggal_selesai->format('d/m/Y')}\n";
        $message .= "⏰ Durasi: {$leave->durasicuti} hari\n";
        $message .= "📋 Jenis: {$leave->jenis_cuti}\n";
        $message .= "👤 Ditolak oleh: {$approver->name}\n";
        $message .= "💬 Alasan: {$leave->catatan_approval}\n";
        
        $message .= "\n🏥 SAHABAT MENUJU SEHAT\n";
        $message .= "Sistem Dokterku";
        
        // Log notification
        Log::info("Leave Rejection Notification", [
            'pegawai_id' => $pegawai->id,
            'leave_id' => $leave->id,
            'message' => $message
        ]);
        
        // TODO: Send via Telegram Bot API
        // TODO: Send via Email
        
        self::sendToTelegram($pegawai, $message);
    }
    
    /**
     * Send notification for new leave request
     */
    public static function notifyNewRequest(PermohonanCuti $leave): void
    {
        $pegawai = $leave->pegawai;
        
        // Notify managers about new leave request
        $managers = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['admin', 'manajer']);
        })->get();
        
        $message = "📋 PERMOHONAN CUTI BARU\n\n";
        $message .= "Permohon: {$pegawai->name}\n";
        $message .= "📅 Periode: {$leave->tanggal_mulai->format('d/m/Y')} - {$leave->tanggal_selesai->format('d/m/Y')}\n";
        $message .= "⏰ Durasi: {$leave->durasicuti} hari\n";
        $message .= "📋 Jenis: {$leave->jenis_cuti}\n";
        $message .= "💭 Alasan: {$leave->keterangan}\n";
        
        $message .= "\n⚠️ Menunggu persetujuan\n";
        $message .= "🏥 SAHABAT MENUJU SEHAT";
        
        foreach ($managers as $manager) {
            Log::info("New Leave Request Notification", [
                'manager_id' => $manager->id,
                'leave_id' => $leave->id,
                'message' => $message
            ]);
            
            self::sendToTelegram($manager, $message);
        }
    }
    
    /**
     * Send message via Telegram (placeholder for actual implementation)
     */
    private static function sendToTelegram(User $user, string $message): void
    {
        // TODO: Implement actual Telegram Bot API integration
        // This would require:
        // 1. Telegram Bot Token from BotFather
        // 2. User's Telegram Chat ID stored in database
        // 3. HTTP client to send message to Telegram API
        
        Log::info("Telegram Notification (Mock)", [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'message' => $message
        ]);
        
        // Example implementation:
        /*
        if ($user->telegram_chat_id) {
            $telegramBotToken = config('services.telegram.bot_token');
            $chatId = $user->telegram_chat_id;
            
            Http::post("https://api.telegram.org/bot{$telegramBotToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
        }
        */
    }
    
    /**
     * Send email notification (placeholder for actual implementation)
     */
    private static function sendEmail(User $user, string $subject, string $message): void
    {
        // TODO: Implement email notification
        // This could use Laravel's Mail facade with a custom Mailable class
        
        Log::info("Email Notification (Mock)", [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'subject' => $subject,
            'message' => $message
        ]);
    }
}