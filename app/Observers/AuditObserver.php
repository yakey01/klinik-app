<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    /**
     * Handle the created event
     */
    public function created(Model $model): void
    {
        if ($this->shouldAudit($model)) {
            AuditLog::log(
                AuditLog::ACTION_CREATED,
                $model,
                [],
                $this->getModelAttributes($model)
            );
        }
    }

    /**
     * Handle the updated event
     */
    public function updated(Model $model): void
    {
        if ($this->shouldAudit($model)) {
            AuditLog::log(
                AuditLog::ACTION_UPDATED,
                $model,
                $this->getModelAttributes($model, $model->getOriginal()),
                $this->getModelAttributes($model, $model->getDirty())
            );
        }
    }

    /**
     * Handle the deleted event
     */
    public function deleted(Model $model): void
    {
        if ($this->shouldAudit($model)) {
            AuditLog::log(
                AuditLog::ACTION_DELETED,
                $model,
                $this->getModelAttributes($model),
                []
            );
        }
    }

    /**
     * Check if model should be audited
     */
    protected function shouldAudit(Model $model): bool
    {
        // Don't audit the audit log itself to prevent infinite loops
        if ($model instanceof AuditLog) {
            return false;
        }

        // Don't audit if we're in a command/console context (seeders, migrations, etc.)
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return false;
        }

        // Only audit if there's an authenticated user
        if (!auth()->check()) {
            return false;
        }

        // List of models that should be audited
        $auditableModels = [
            \App\Models\User::class,
            \App\Models\SystemSetting::class,
            \App\Models\FeatureFlag::class,
            \App\Models\Pasien::class,
            \App\Models\Tindakan::class,
            \App\Models\Pendapatan::class,
            \App\Models\Pengeluaran::class,
            \App\Models\Role::class,
            \App\Models\Pegawai::class,
            \App\Models\Dokter::class,
            \App\Models\TelegramSetting::class,
        ];

        return in_array(get_class($model), $auditableModels);
    }

    /**
     * Get model attributes for auditing
     */
    protected function getModelAttributes(Model $model, array $attributes = null): array
    {
        $attributes = $attributes ?? $model->toArray();
        
        // Remove sensitive fields
        $sensitiveFields = ['password', 'remember_token', 'api_token'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = '[HIDDEN]';
            }
        }

        return $attributes;
    }
}
