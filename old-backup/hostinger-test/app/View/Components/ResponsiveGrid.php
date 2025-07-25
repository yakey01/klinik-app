<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class ResponsiveGrid extends Component
{
    public int $cols;
    public ?int $smCols;
    public ?int $mdCols;
    public ?int $lgCols;
    public ?int $xlCols;
    public string $gap;
    public string $align;
    public bool $autoFit;
    public ?string $minWidth;

    /**
     * Create a new component instance.
     */
    public function __construct(
        int $cols = 1,
        ?int $smCols = null,
        ?int $mdCols = null,
        ?int $lgCols = null,
        ?int $xlCols = null,
        string $gap = '6',
        string $align = 'stretch',
        bool $autoFit = false,
        ?string $minWidth = null
    ) {
        $this->cols = $cols;
        $this->smCols = $smCols;
        $this->mdCols = $mdCols;
        $this->lgCols = $lgCols;
        $this->xlCols = $xlCols;
        $this->gap = $gap;
        $this->align = $align;
        $this->autoFit = $autoFit;
        $this->minWidth = $minWidth;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.responsive-grid');
    }

    /**
     * Get the grid classes
     */
    public function getGridClasses(): string
    {
        $classes = ['grid'];

        if ($this->autoFit && $this->minWidth) {
            $classes[] = 'grid-cols-[repeat(auto-fit,minmax(' . $this->minWidth . ',1fr))]';
        } else {
            // Base columns
            $classes[] = 'grid-cols-' . $this->cols;
            
            // Responsive columns
            if ($this->smCols) {
                $classes[] = 'sm:grid-cols-' . $this->smCols;
            }
            if ($this->mdCols) {
                $classes[] = 'md:grid-cols-' . $this->mdCols;
            }
            if ($this->lgCols) {
                $classes[] = 'lg:grid-cols-' . $this->lgCols;
            }
            if ($this->xlCols) {
                $classes[] = 'xl:grid-cols-' . $this->xlCols;
            }
        }

        // Gap
        $classes[] = 'gap-' . $this->gap;

        // Alignment
        $classes[] = match($this->align) {
            'start' => 'items-start',
            'center' => 'items-center',
            'end' => 'items-end',
            'baseline' => 'items-baseline',
            default => 'items-stretch',
        };

        return implode(' ', $classes);
    }
}