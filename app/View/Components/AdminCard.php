<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminCard extends Component
{
    public string $title;
    public ?string $subtitle;
    public ?string $icon;
    public ?string $iconColor;
    public ?string $action;
    public ?string $actionLabel;
    public bool $loading;
    public string $size;
    public ?string $footer;
    public bool $collapsible;
    public bool $defaultCollapsed;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $title,
        ?string $subtitle = null,
        ?string $icon = null,
        ?string $iconColor = 'primary',
        ?string $action = null,
        ?string $actionLabel = null,
        bool $loading = false,
        string $size = 'default',
        ?string $footer = null,
        bool $collapsible = false,
        bool $defaultCollapsed = false
    ) {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->icon = $icon;
        $this->iconColor = $iconColor;
        $this->action = $action;
        $this->actionLabel = $actionLabel;
        $this->loading = $loading;
        $this->size = $size;
        $this->footer = $footer;
        $this->collapsible = $collapsible;
        $this->defaultCollapsed = $defaultCollapsed;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.admin-card');
    }

    /**
     * Get the size classes for the card
     */
    public function getSizeClasses(): string
    {
        return match($this->size) {
            'sm' => 'p-4',
            'lg' => 'p-8',
            'xl' => 'p-10',
            default => 'p-6',
        };
    }

    /**
     * Get the icon color classes
     */
    public function getIconColorClasses(): string
    {
        return match($this->iconColor) {
            'primary' => 'text-blue-600 bg-blue-100 dark:bg-blue-900',
            'success' => 'text-green-600 bg-green-100 dark:bg-green-900',
            'warning' => 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900',
            'danger' => 'text-red-600 bg-red-100 dark:bg-red-900',
            'info' => 'text-cyan-600 bg-cyan-100 dark:bg-cyan-900',
            'gray' => 'text-gray-600 bg-gray-100 dark:bg-gray-900',
            default => 'text-blue-600 bg-blue-100 dark:bg-blue-900',
        };
    }
}