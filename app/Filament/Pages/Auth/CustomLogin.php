<?php

namespace App\Filament\Pages\Auth;

use App\Models\Dokter;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\HtmlString;

class CustomLogin extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent()
                    ->label('Email / Username')
                    ->placeholder('Masukkan email atau username Anda')
                    ->helperText('Anda dapat login menggunakan email atau username'),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                $this->getAvailableAccountsComponent(),
            ])
            ->statePath('data');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email / Username')
            ->placeholder('Masukkan email atau username Anda')
            ->autofocus()
            ->required()
            ->autocomplete('username')
            ->extraInputAttributes(['tabindex' => 1])
            ->helperText('Anda dapat login menggunakan email atau nama pengguna Anda');
    }

    public function getTitle(): string
    {
        return 'Masuk ke Sistem';
    }

    public function getHeading(): string
    {
        return 'Masuk ke Sistem Dokterku';
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
    }

    protected function getAvailableAccountsComponent(): Component
    {
        // Get dokters with usernames
        $doktersWithUsernames = Dokter::whereNotNull('username')
            ->whereNotNull('password')
            ->get(['nama_lengkap', 'username']);

        $html = '<div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">';
        $html .= '<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Akun yang Tersedia:</h3>';
        $html .= '<div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">';
        
        // System accounts
        $html .= '<div><strong>Admin:</strong> admin@dokterku.com / admin123</div>';
        $html .= '<div><strong>Petugas:</strong> petugas@dokterku.com / petugas123</div>';
        $html .= '<div><strong>Manajer:</strong> manajer@dokterku.com / manajer123</div>';
        $html .= '<div><strong>Bendahara:</strong> bendahara@dokterku.com / bendahara123</div>';
        $html .= '<div><strong>Dokter:</strong> dokter@dokterku.com / dokter123</div>';
        
        // Dokter usernames if any exist
        foreach ($doktersWithUsernames as $dokter) {
            $html .= '<div><strong>Dokter:</strong> ' . $dokter->username . ' / (password dibuat admin)</div>';
        }
        
        $html .= '</div>';
        
        if ($doktersWithUsernames->isEmpty()) {
            $html .= '<div class="mt-2 text-xs text-blue-600 dark:text-blue-400">';
            $html .= '<em>💡 Tip: Admin dapat membuat username dokter di menu Manajemen Dokter → Buat Akun</em>';
            $html .= '</div>';
        }
        
        $html .= '</div>';

        return Placeholder::make('available_accounts')
            ->content(new HtmlString($html))
            ->hiddenLabel();
    }
}