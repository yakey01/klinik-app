<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Field;

class CanvasOSMMap extends Field
{
    protected string $view = 'filament.forms.components.canvas-osm-map';
    
    protected array $defaultLocation = ['lat' => -7.89946200, 'lng' => 111.96239900];
    protected int $zoom = 15;
    protected int $height = 400;
    
    public function defaultLocation(float $lat, float $lng): static
    {
        $this->defaultLocation = ['lat' => $lat, 'lng' => $lng];
        return $this;
    }
    
    public function getDefaultLocation(): array
    {
        return $this->defaultLocation;
    }
    
    public function zoom(int $zoom): static
    {
        $this->zoom = $zoom;
        return $this;
    }
    
    public function getZoom(): int
    {
        return $this->zoom;
    }
    
    public function height(int $height): static
    {
        $this->height = $height;
        return $this;
    }
    
    public function getHeight(): int
    {
        return $this->height;
    }
}