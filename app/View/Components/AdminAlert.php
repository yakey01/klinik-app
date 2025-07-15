<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminAlert extends Component
{
    public string $type;
    public ?string $title;
    public bool $dismissible;
    public ?string $action;
    public ?string $actionLabel;
    public ?string $icon;
    public string $size;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $type = 'info',
        ?string $title = null,
        bool $dismissible = false,
        ?string $action = null,
        ?string $actionLabel = null,
        ?string $icon = null,
        string $size = 'default'
    ) {
        $this->type = $type;
        $this->title = $title;
        $this->dismissible = $dismissible;
        $this->action = $action;
        $this->actionLabel = $actionLabel;
        $this->icon = $icon;
        $this->size = $size;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.admin-alert');
    }

    /**
     * Get the alert type classes
     */
    public function getTypeClasses(): array
    {
        return match($this->type) {
            'success' => [
                'bg' => 'bg-green-50 dark:bg-green-900/20',
                'border' => 'border-green-200 dark:border-green-800',
                'text' => 'text-green-800 dark:text-green-200',
                'icon' => 'text-green-400',
                'button' => 'text-green-500 hover:text-green-600 focus:ring-green-600',
            ],
            'warning' => [
                'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
                'border' => 'border-yellow-200 dark:border-yellow-800',
                'text' => 'text-yellow-800 dark:text-yellow-200',
                'icon' => 'text-yellow-400',
                'button' => 'text-yellow-500 hover:text-yellow-600 focus:ring-yellow-600',
            ],
            'error' => [
                'bg' => 'bg-red-50 dark:bg-red-900/20',
                'border' => 'border-red-200 dark:border-red-800',
                'text' => 'text-red-800 dark:text-red-200',
                'icon' => 'text-red-400',
                'button' => 'text-red-500 hover:text-red-600 focus:ring-red-600',
            ],
            default => [
                'bg' => 'bg-blue-50 dark:bg-blue-900/20',
                'border' => 'border-blue-200 dark:border-blue-800',
                'text' => 'text-blue-800 dark:text-blue-200',
                'icon' => 'text-blue-400',
                'button' => 'text-blue-500 hover:text-blue-600 focus:ring-blue-600',
            ],
        };
    }

    /**
     * Get the default icon for the alert type
     */
    public function getDefaultIcon(): string
    {
        return match($this->type) {
            'success' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'error' => 'heroicon-o-x-circle',
            default => 'heroicon-o-information-circle',
        };
    }

    /**
     * Get the size classes
     */
    public function getSizeClasses(): string
    {
        return match($this->size) {
            'sm' => 'p-3',
            'lg' => 'p-6',
            default => 'p-4',
        };
    }

    /**
     * Get the icon size classes
     */
    public function getIconSizeClasses(): string
    {
        return match($this->size) {
            'sm' => 'h-4 w-4',
            'lg' => 'h-6 w-6',
            default => 'h-5 w-5',
        };
    }
}