<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action as PageAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AdminProfileSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static ?string $navigationGroup = '👥 USER MANAGEMENT';
    
    protected static ?string $navigationLabel = 'Profil Admin';
    
    protected static ?string $title = 'Pengaturan Profil Admin';
    
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.admin-profile-settings';

    public ?array $emailData = [];
    public ?array $passwordData = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function mount(): void
    {
        $this->emailData = [
            'current_email' => auth()->user()->email,
            'new_email' => '',
            'password_confirmation' => '',
        ];

        $this->passwordData = [
            'current_password' => '',
            'new_password' => '',
            'new_password_confirmation' => '',
        ];
    }

    protected function getForms(): array
    {
        return [
            'emailForm' => $this->makeForm()
                ->schema([
                    Section::make('Ganti Email Admin')
                        ->description('Ubah alamat email yang digunakan untuk login admin')
                        ->icon('heroicon-o-envelope')
                        ->schema([
                            TextInput::make('current_email')
                                ->label('Email Saat Ini')
                                ->email()
                                ->disabled()
                                ->dehydrated(false),
                            
                            TextInput::make('new_email')
                                ->label('Email Baru')
                                ->email()
                                ->required()
                                ->different('current_email')
                                ->unique('users', 'email', ignoreRecord: true)
                                ->rules(['email:rfc,dns']),
                            
                            TextInput::make('password_confirmation')
                                ->label('Konfirmasi Password')
                                ->password()
                                ->required()
                                ->rules(['current_password']),
                        ])
                ])
                ->statePath('emailData')
                ->model(auth()->user()),
            
            'passwordForm' => $this->makeForm()
                ->schema([
                    Section::make('Ganti Password Admin')
                        ->description('Ubah password untuk keamanan akun admin')
                        ->icon('heroicon-o-lock-closed')
                        ->schema([
                            TextInput::make('current_password')
                                ->label('Password Saat Ini')
                                ->password()
                                ->required()
                                ->rules(['current_password']),
                            
                            TextInput::make('new_password')
                                ->label('Password Baru')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->rules(['min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/']),
                            
                            TextInput::make('new_password_confirmation')
                                ->label('Konfirmasi Password Baru')
                                ->password()
                                ->required()
                                ->same('new_password'),
                        ])
                ])
                ->statePath('passwordData'),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getActions(): array
    {
        return [
            PageAction::make('updateEmail')
                ->label('Update Email')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->action('updateEmail'),
            
            PageAction::make('updatePassword')
                ->label('Update Password')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->action('updatePassword'),
        ];
    }

    public function updateEmail(): void
    {
        try {
            $form = $this->getForms()['emailForm'];
            $data = $form->getState();
            
            // Validate that new email is different
            if ($data['new_email'] === auth()->user()->email) {
                throw ValidationException::withMessages([
                    'emailData.new_email' => 'Email baru harus berbeda dengan email saat ini.'
                ]);
            }

            // Check if email already exists
            $existingUser = \App\Models\User::where('email', $data['new_email'])->first();
            if ($existingUser && $existingUser->id !== auth()->id()) {
                throw ValidationException::withMessages([
                    'emailData.new_email' => 'Email ini sudah digunakan oleh pengguna lain.'
                ]);
            }

            // Verify current password
            if (!Hash::check($data['password_confirmation'], auth()->user()->password)) {
                throw ValidationException::withMessages([
                    'emailData.password_confirmation' => 'Password saat ini tidak benar.'
                ]);
            }

            // Update email
            $user = auth()->user();
            $oldEmail = $user->email;
            $user->email = $data['new_email'];
            $user->save();

            // Log the email change
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'email_changed',
                'description' => "Email changed from {$oldEmail} to {$data['new_email']}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Send notification email to both old and new email
            try {
                // Notification to old email
                Mail::raw(
                    "Halo,\n\nEmail akun admin Anda telah berhasil diubah ke: {$data['new_email']}\n\nWaktu: " . now()->format('d/m/Y H:i:s') . "\nIP Address: " . request()->ip() . "\n\nJika Anda tidak melakukan perubahan ini, silakan hubungi administrator sistem segera.\n\nTerima kasih,\nTim " . config('app.name'),
                    function ($message) use ($oldEmail) {
                        $message->to($oldEmail)
                               ->subject('[' . config('app.name') . '] Email Akun Diubah');
                    }
                );

                // Notification to new email
                Mail::raw(
                    "Selamat!\n\nEmail akun admin Anda telah berhasil diubah ke alamat ini.\n\nWaktu: " . now()->format('d/m/Y H:i:s') . "\nIP Address: " . request()->ip() . "\n\nAnda sekarang dapat menggunakan email ini untuk login ke sistem admin.\n\nTerima kasih,\nTim " . config('app.name'),
                    function ($message) use ($data) {
                        $message->to($data['new_email'])
                               ->subject('[' . config('app.name') . '] Email Berhasil Diubah');
                    }
                );
            } catch (\Exception $e) {
                // Email sending failed but email change was successful
                \Log::warning('Email notification failed after email change', [
                    'user_id' => $user->id,
                    'old_email' => $oldEmail,
                    'new_email' => $data['new_email'],
                    'error' => $e->getMessage()
                ]);
            }

            // Reset form
            $this->emailData = [
                'current_email' => $data['new_email'],
                'new_email' => '',
                'password_confirmation' => '',
            ];

            Notification::make()
                ->title('Email Berhasil Diubah!')
                ->body("Email admin telah diubah ke: {$data['new_email']}")
                ->success()
                ->persistent()
                ->send();

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Mengubah Email')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function updatePassword(): void
    {
        try {
            $form = $this->getForms()['passwordForm'];
            $data = $form->getState();

            // Verify current password
            if (!Hash::check($data['current_password'], auth()->user()->password)) {
                throw ValidationException::withMessages([
                    'passwordData.current_password' => 'Password saat ini tidak benar.'
                ]);
            }

            // Check if new password is different from current
            if (Hash::check($data['new_password'], auth()->user()->password)) {
                throw ValidationException::withMessages([
                    'passwordData.new_password' => 'Password baru harus berbeda dengan password saat ini.'
                ]);
            }

            // Update password
            $user = auth()->user();
            $user->password = Hash::make($data['new_password']);
            $user->save();

            // Log the password change
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'password_changed',
                'description' => 'Admin password changed successfully',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Send notification email
            try {
                Mail::raw(
                    "Halo,\n\nPassword akun admin Anda telah berhasil diubah.\n\nWaktu: " . now()->format('d/m/Y H:i:s') . "\nIP Address: " . request()->ip() . "\n\nJika Anda tidak melakukan perubahan ini, silakan hubungi administrator sistem segera.\n\nTerima kasih,\nTim " . config('app.name'),
                    function ($message) use ($user) {
                        $message->to($user->email)
                               ->subject('[' . config('app.name') . '] Password Akun Diubah');
                    }
                );
            } catch (\Exception $e) {
                \Log::warning('Email notification failed after password change', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Reset form
            $this->passwordData = [
                'current_password' => '',
                'new_password' => '',
                'new_password_confirmation' => '',
            ];

            Notification::make()
                ->title('Password Berhasil Diubah!')
                ->body('Password admin telah diperbarui dengan sukses.')
                ->success()
                ->persistent()
                ->send();

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Mengubah Password')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}