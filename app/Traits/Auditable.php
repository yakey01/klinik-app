<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            AuditLog::log('created', $model, [], $model->getAttributes());
        });

        static::updated(function ($model) {
            AuditLog::log('updated', $model, $model->getOriginal(), $model->getAttributes());
        });

        static::deleted(function ($model) {
            AuditLog::log('deleted', $model, $model->getAttributes(), []);
        });
    }

    public function auditLogs()
    {
        return AuditLog::where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->orderBy('created_at', 'desc');
    }
}