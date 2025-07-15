<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\CustomAuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    // TEMPORARILY DISABLED ALL FILAMENT PANELS TO FORCE UNIFIED LOGIN
    // App\Providers\Filament\AdminPanelProvider::class,
    // App\Providers\Filament\BendaharaPanelProvider::class,
    // App\Providers\Filament\DokterPanelProvider::class,
    // App\Providers\Filament\ManajerPanelProvider::class,
    // App\Providers\Filament\ParamedisPanelProvider::class,
    // App\Providers\Filament\PetugasPanelProvider::class,
];
