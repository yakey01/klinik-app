<?php

namespace SolutionForest\FilamentTree;

use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Database\Schema\Blueprint;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\AlpineComponent;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use SolutionForest\FilamentTree\Macros\BlueprintMarcos;

class FilamentTreeServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-tree';
    
    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasAssets()
            ->hasTranslations()
            ->hasCommands([
                Commands\MakeTreePageCommand::class,
                Commands\MakeTreeWidgetCommand::class,
            ]);
    }

    public function boot()
    {
        parent::boot();

        $this->registerBlueprintMacros();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-tree-min', __DIR__ . '/../resources/dist/filament-tree.css'),
        ], 'solution-forest/filament-tree');
        
        FilamentAsset::register([
            AlpineComponent::make('filament-tree-component', __DIR__ . '/../resources/dist/components/filament-tree-component.js'),
        ], 'solution-forest/filament-tree');
    }

    protected function registerBlueprintMacros()
    {
        Blueprint::mixin(new BlueprintMarcos);
    }
}
