<?php

namespace App\Filament\Bendahara\Widgets;

use Filament\Widgets\Widget;

class LanguageSwitcherWidget extends Widget
{
    protected static string $view = 'filament.bendahara.widgets.language-switcher-widget';
    
    protected int | string | array $columnSpan = 1;
    
    public function switchLanguage(string $locale): void
    {
        session(['locale' => $locale]);
        app()->setLocale($locale);
        
        // Redirect to refresh the page with new language
        $this->redirect(request()->header('Referer'));
    }
    
    public function getCurrentLocale(): string
    {
        return app()->getLocale();
    }
    
    public function getAvailableLocales(): array
    {
        return [
            'id' => [
                'name' => 'Indonesia',
                'flag' => '🇮🇩',
                'code' => 'id',
            ],
            'en' => [
                'name' => 'English',
                'flag' => '🇺🇸',
                'code' => 'en',
            ],
        ];
    }
}