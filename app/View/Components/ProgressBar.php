<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class ProgressBar extends Component
{
    public float $value;
    public float $max;
    public string $color;
    public string $size;
    public bool $animated;
    public bool $striped;
    public bool $showLabel;
    public ?string $label;
    public string $labelPosition;

    /**
     * Create a new component instance.
     */
    public function __construct(
        float $value = 0,
        float $max = 100,
        string $color = 'blue',
        string $size = 'default',
        bool $animated = false,
        bool $striped = false,
        bool $showLabel = false,
        ?string $label = null,
        string $labelPosition = 'right'
    ) {
        $this->value = $value;
        $this->max = $max;
        $this->color = $color;
        $this->size = $size;
        $this->animated = $animated;
        $this->striped = $striped;
        $this->showLabel = $showLabel;
        $this->label = $label;
        $this->labelPosition = $labelPosition;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.progress-bar');
    }

    /**
     * Get the progress percentage
     */
    public function getPercentage(): float
    {
        return min(100, max(0, ($this->value / $this->max) * 100));
    }

    /**
     * Get the color classes
     */
    public function getColorClasses(): array
    {
        return match($this->color) {
            'blue' => [
                'bg' => 'bg-blue-600',
                'track' => 'bg-blue-100 dark:bg-blue-900/20',
            ],
            'green' => [
                'bg' => 'bg-green-600',
                'track' => 'bg-green-100 dark:bg-green-900/20',
            ],
            'yellow' => [
                'bg' => 'bg-yellow-600',
                'track' => 'bg-yellow-100 dark:bg-yellow-900/20',
            ],
            'red' => [
                'bg' => 'bg-red-600',
                'track' => 'bg-red-100 dark:bg-red-900/20',
            ],
            'purple' => [
                'bg' => 'bg-purple-600',
                'track' => 'bg-purple-100 dark:bg-purple-900/20',
            ],
            'indigo' => [
                'bg' => 'bg-indigo-600',
                'track' => 'bg-indigo-100 dark:bg-indigo-900/20',
            ],
            default => [
                'bg' => 'bg-gray-600',
                'track' => 'bg-gray-100 dark:bg-gray-900/20',
            ],
        };
    }

    /**
     * Get the size classes
     */
    public function getSizeClasses(): string
    {
        return match($this->size) {
            'sm' => 'h-2',
            'lg' => 'h-4',
            'xl' => 'h-6',
            default => 'h-3',
        };
    }

    /**
     * Get the formatted label
     */
    public function getFormattedLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }

        return number_format($this->getPercentage(), 1) . '%';
    }

    /**
     * Get the striped classes
     */
    public function getStripedClasses(): string
    {
        if (!$this->striped) {
            return '';
        }

        $classes = 'bg-gradient-to-r from-transparent via-white/20 to-transparent bg-[length:20px_20px]';
        
        if ($this->animated) {
            $classes .= ' animate-pulse';
        }

        return $classes;
    }
}