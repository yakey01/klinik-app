<?php

namespace App\Observers;

use App\Models\WorkLocation;
use App\Events\WorkLocationUpdated;
use Illuminate\Support\Facades\Log;

class WorkLocationObserver
{
    /**
     * Handle the WorkLocation "updated" event.
     */
    public function updated(WorkLocation $workLocation): void
    {
        // Get the changed fields
        $changedFields = [];
        $dirty = $workLocation->getDirty();
        $original = $workLocation->getOriginal();

        foreach ($dirty as $field => $newValue) {
            $changedFields[$field] = [
                'old' => $original[$field] ?? null,
                'new' => $newValue,
            ];
        }

        // Log the update for debugging
        Log::info('WorkLocation updated via observer', [
            'id' => $workLocation->id,
            'name' => $workLocation->name,
            'changed_fields' => array_keys($changedFields),
            'changes' => $changedFields
        ]);

        // Fire the event for real-time updates
        event(new WorkLocationUpdated($workLocation, $changedFields));
    }

    /**
     * Handle the WorkLocation "created" event.
     */
    public function created(WorkLocation $workLocation): void
    {
        Log::info('New WorkLocation created', [
            'id' => $workLocation->id,
            'name' => $workLocation->name,
            'address' => $workLocation->address
        ]);

        // Fire event for new location
        event(new WorkLocationUpdated($workLocation, ['created' => true]));
    }

    /**
     * Handle the WorkLocation "deleted" event.
     */
    public function deleted(WorkLocation $workLocation): void
    {
        Log::info('WorkLocation deleted', [
            'id' => $workLocation->id,
            'name' => $workLocation->name
        ]);

        // Clear caches for deleted location
        event(new WorkLocationUpdated($workLocation, ['deleted' => true]));
    }
}