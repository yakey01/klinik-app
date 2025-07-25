<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\JadwalJaga;
use App\Filament\Resources\JadwalJagaResource;
use Illuminate\Database\Eloquent\Model;

class CalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = JadwalJaga::class;

    public function fetchEvents(array $fetchInfo): array
    {
        return JadwalJaga::query()
            ->with(['pegawai', 'shiftTemplate'])
            ->whereBetween('tanggal_jaga', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(
                fn (JadwalJaga $event) => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start,
                    'end' => $event->end,
                    'backgroundColor' => $event->color,
                    'borderColor' => $event->color,
                    'url' => JadwalJagaResource::getUrl(name: 'edit', parameters: ['record' => $event]),
                    'shouldOpenUrlInNewTab' => false
                ]
            )
            ->all();
    }

    public function getViewData(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay'
            ],
            'height' => 'auto',
            'locale' => 'id',
            'firstDay' => 1, // Monday
            'displayEventTime' => true,
            'eventDisplay' => 'block',
            'dayMaxEvents' => 3,
            'moreLinkClick' => 'popover',
            'navLinks' => true,
            'selectable' => true,
            'selectMirror' => true,
        ];
    }

    protected function headerActions(): array
    {
        return [
            \Filament\Actions\Action::make('create')
                ->label('Tambah Jadwal')
                ->icon('heroicon-o-plus')
                ->url(JadwalJagaResource::getUrl('create'))
                ->button(),
        ];
    }

    public function eventDidMount(): string
    {
        return <<<JS
        function({ event, el }) {
            el.setAttribute("x-tooltip", `\${event.title} - \${event.extendedProps.description || ''}`);
        }
        JS;
    }
}