<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminButton extends Component
{
    public string $variant;
    public string $size;
    public bool $disabled;
    public bool $loading;
    public ?string $icon;
    public ?string $iconPosition;
    public ?string $href;
    public ?string $target;
    public string $tag;
    public bool $fullWidth;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $variant = 'primary',
        string $size = 'default',
        bool $disabled = false,
        bool $loading = false,
        ?string $icon = null,
        ?string $iconPosition = 'left',
        ?string $href = null,
        ?string $target = null,
        bool $fullWidth = false
    ) {
        $this->variant = $variant;
        $this->size = $size;
        $this->disabled = $disabled;
        $this->loading = $loading;
        $this->icon = $icon;
        $this->iconPosition = $iconPosition;
        $this->href = $href;
        $this->target = $target;
        $this->tag = $href ? 'a' : 'button';
        $this->fullWidth = $fullWidth;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.admin-button');
    }

    /**
     * Get the variant classes
     */
    public function getVariantClasses(): array
    {
        return match($this->variant) {
            'primary' => [
                'base' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 text-white border-transparent',
                'disabled' => 'bg-blue-300 text-blue-100 cursor-not-allowed',
            ],
            'secondary' => [
                'base' => 'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500 text-white border-transparent',
                'disabled' => 'bg-gray-300 text-gray-100 cursor-not-allowed',
            ],
            'success' => [
                'base' => 'bg-green-600 hover:bg-green-700 focus:ring-green-500 text-white border-transparent',
                'disabled' => 'bg-green-300 text-green-100 cursor-not-allowed',
            ],
            'warning' => [
                'base' => 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500 text-white border-transparent',
                'disabled' => 'bg-yellow-300 text-yellow-100 cursor-not-allowed',
            ],
            'danger' => [
                'base' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500 text-white border-transparent',
                'disabled' => 'bg-red-300 text-red-100 cursor-not-allowed',
            ],
            'outline' => [
                'base' => 'bg-transparent hover:bg-gray-50 focus:ring-gray-500 text-gray-700 border-gray-300 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-800',
                'disabled' => 'bg-transparent text-gray-300 border-gray-200 cursor-not-allowed dark:text-gray-600 dark:border-gray-700',
            ],
            'ghost' => [
                'base' => 'bg-transparent hover:bg-gray-100 focus:ring-gray-500 text-gray-700 border-transparent dark:text-gray-300 dark:hover:bg-gray-800',
                'disabled' => 'bg-transparent text-gray-300 cursor-not-allowed dark:text-gray-600',
            ],
            default => [
                'base' => 'bg-white hover:bg-gray-50 focus:ring-gray-500 text-gray-700 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700',
                'disabled' => 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500 dark:border-gray-600',
            ],
        };
    }

    /**
     * Get the size classes
     */
    public function getSizeClasses(): array
    {
        return match($this->size) {
            'xs' => [
                'padding' => 'px-2 py-1',
                'text' => 'text-xs',
                'icon' => 'h-3 w-3',
            ],
            'sm' => [
                'padding' => 'px-3 py-1.5',
                'text' => 'text-sm',
                'icon' => 'h-4 w-4',
            ],
            'lg' => [
                'padding' => 'px-4 py-2.5',
                'text' => 'text-base',
                'icon' => 'h-5 w-5',
            ],
            'xl' => [
                'padding' => 'px-6 py-3',
                'text' => 'text-lg',
                'icon' => 'h-6 w-6',
            ],
            default => [
                'padding' => 'px-4 py-2',
                'text' => 'text-sm',
                'icon' => 'h-4 w-4',
            ],
        };
    }

    /**
     * Get the base classes
     */
    public function getBaseClasses(): string
    {
        $classes = [
            'inline-flex',
            'items-center',
            'justify-center',
            'border',
            'font-medium',
            'rounded-md',
            'focus:outline-none',
            'focus:ring-2',
            'focus:ring-offset-2',
            'transition-colors',
            'duration-150',
        ];

        if ($this->fullWidth) {
            $classes[] = 'w-full';
        }

        return implode(' ', $classes);
    }

    /**
     * Get the current state classes
     */
    public function getStateClasses(): string
    {
        $variants = $this->getVariantClasses();
        
        if ($this->disabled || $this->loading) {
            return $variants['disabled'];
        }

        return $variants['base'];
    }
}