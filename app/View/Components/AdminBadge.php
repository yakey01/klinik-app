<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminBadge extends Component
{
    public string $variant;
    public string $size;
    public bool $pill;
    public bool $removable;
    public ?string $icon;
    public ?string $removeAction;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $variant = 'default',
        string $size = 'default',
        bool $pill = false,
        bool $removable = false,
        ?string $icon = null,
        ?string $removeAction = null
    ) {
        $this->variant = $variant;
        $this->size = $size;
        $this->pill = $pill;
        $this->removable = $removable;
        $this->icon = $icon;
        $this->removeAction = $removeAction;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.admin-badge');
    }

    /**
     * Get the variant classes
     */
    public function getVariantClasses(): array
    {
        return match($this->variant) {
            'primary' => [
                'bg' => 'bg-blue-100 dark:bg-blue-900/20',
                'text' => 'text-blue-800 dark:text-blue-200',
                'border' => 'border-blue-200 dark:border-blue-800',
            ],
            'success' => [
                'bg' => 'bg-green-100 dark:bg-green-900/20',
                'text' => 'text-green-800 dark:text-green-200',
                'border' => 'border-green-200 dark:border-green-800',
            ],
            'warning' => [
                'bg' => 'bg-yellow-100 dark:bg-yellow-900/20',
                'text' => 'text-yellow-800 dark:text-yellow-200',
                'border' => 'border-yellow-200 dark:border-yellow-800',
            ],
            'error' => [
                'bg' => 'bg-red-100 dark:bg-red-900/20',
                'text' => 'text-red-800 dark:text-red-200',
                'border' => 'border-red-200 dark:border-red-800',
            ],
            'info' => [
                'bg' => 'bg-cyan-100 dark:bg-cyan-900/20',
                'text' => 'text-cyan-800 dark:text-cyan-200',
                'border' => 'border-cyan-200 dark:border-cyan-800',
            ],
            'purple' => [
                'bg' => 'bg-purple-100 dark:bg-purple-900/20',
                'text' => 'text-purple-800 dark:text-purple-200',
                'border' => 'border-purple-200 dark:border-purple-800',
            ],
            'indigo' => [
                'bg' => 'bg-indigo-100 dark:bg-indigo-900/20',
                'text' => 'text-indigo-800 dark:text-indigo-200',
                'border' => 'border-indigo-200 dark:border-indigo-800',
            ],
            'outline' => [
                'bg' => 'bg-transparent',
                'text' => 'text-gray-700 dark:text-gray-300',
                'border' => 'border-gray-300 dark:border-gray-600',
            ],
            default => [
                'bg' => 'bg-gray-100 dark:bg-gray-900/20',
                'text' => 'text-gray-800 dark:text-gray-200',
                'border' => 'border-gray-200 dark:border-gray-800',
            ],
        };
    }

    /**
     * Get the size classes
     */
    public function getSizeClasses(): array
    {
        return match($this->size) {
            'sm' => [
                'padding' => 'px-2 py-0.5',
                'text' => 'text-xs',
                'icon' => 'h-3 w-3',
            ],
            'lg' => [
                'padding' => 'px-4 py-1.5',
                'text' => 'text-sm',
                'icon' => 'h-4 w-4',
            ],
            default => [
                'padding' => 'px-3 py-1',
                'text' => 'text-xs',
                'icon' => 'h-3 w-3',
            ],
        };
    }

    /**
     * Get the shape classes
     */
    public function getShapeClasses(): string
    {
        return $this->pill ? 'rounded-full' : 'rounded';
    }
}