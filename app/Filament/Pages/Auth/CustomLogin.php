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
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
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
            ->helperText('Anda dapat login menggunakan email atau username');
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

}