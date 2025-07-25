<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class StatCard extends Component
{
    public string $title;
    public string $value;
    public ?string $change;
    public ?string $changeType;
    public ?string $icon;
    public string $color;
    public ?string $trend;
    public bool $loading;
    public ?string $subtitle;
    public ?string $period;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $title,
        string $value,
        ?string $change = null,
        ?string $changeType = null,
        ?string $icon = null,
        string $color = 'blue',
        ?string $trend = null,
        bool $loading = false,
        ?string $subtitle = null,
        ?string $period = null
    ) {
        $this->title = $title;
        $this->value = $value;
        $this->change = $change;
        $this->changeType = $changeType;
        $this->icon = $icon;
        $this->color = $color;
        $this->trend = $trend;
        $this->loading = $loading;
        $this->subtitle = $subtitle;
        $this->period = $period;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.stat-card');
    }

    /**
     * Get the color classes for the card
     */
    public function getColorClasses(): array
    {
        return match($this->color) {
            'blue' => [
                'bg' => 'bg-blue-50 dark:bg-blue-900/20',
                'icon' => 'text-blue-600 dark:text-blue-400',
                'border' => 'border-blue-200 dark:border-blue-800',
            ],
            'green' => [
                'bg' => 'bg-green-50 dark:bg-green-900/20',
                'icon' => 'text-green-600 dark:text-green-400',
                'border' => 'border-green-200 dark:border-green-800',
            ],
            'yellow' => [
                'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
                'icon' => 'text-yellow-600 dark:text-yellow-400',
                'border' => 'border-yellow-200 dark:border-yellow-800',
            ],
            'red' => [
                'bg' => 'bg-red-50 dark:bg-red-900/20',
                'icon' => 'text-red-600 dark:text-red-400',
                'border' => 'border-red-200 dark:border-red-800',
            ],
            'purple' => [
                'bg' => 'bg-purple-50 dark:bg-purple-900/20',
                'icon' => 'text-purple-600 dark:text-purple-400',
                'border' => 'border-purple-200 dark:border-purple-800',
            ],
            'indigo' => [
                'bg' => 'bg-indigo-50 dark:bg-indigo-900/20',
                'icon' => 'text-indigo-600 dark:text-indigo-400',
                'border' => 'border-indigo-200 dark:border-indigo-800',
            ],
            default => [
                'bg' => 'bg-gray-50 dark:bg-gray-900/20',
                'icon' => 'text-gray-600 dark:text-gray-400',
                'border' => 'border-gray-200 dark:border-gray-800',
            ],
        };
    }

    /**
     * Get the change color classes
     */
    public function getChangeColorClasses(): string
    {
        return match($this->changeType) {
            'increase' => 'text-green-600 dark:text-green-400',
            'decrease' => 'text-red-600 dark:text-red-400',
            'neutral' => 'text-gray-600 dark:text-gray-400',
            default => 'text-gray-600 dark:text-gray-400',
        };
    }

    /**
     * Get the trend icon
     */
    public function getTrendIcon(): string
    {
        return match($this->changeType) {
            'increase' => 'heroicon-o-arrow-trending-up',
            'decrease' => 'heroicon-o-arrow-trending-down',
            'neutral' => 'heroicon-o-minus',
            default => 'heroicon-o-minus',
        };
    }
}