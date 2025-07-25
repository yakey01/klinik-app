<?php

namespace App\Traits;

use App\Services\LoggingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            $model->logModelActivity('created');
        });

        static::updated(function (Model $model) {
            $model->logModelActivity('updated');
        });

        static::deleted(function (Model $model) {
            $model->logModelActivity('deleted');
        });
    }

    public function logModelActivity(string $action, array $properties = []): void
    {
        $loggingService = new LoggingService();
        
        $activityProperties = $properties;
        
        // Add changed attributes for update action
        if ($action === 'updated' && $this->isDirty()) {
            $activityProperties['old'] = $this->getOriginal();
            $activityProperties['new'] = $this->getChanges();
        }
        
        // Add model attributes for create action
        if ($action === 'created') {
            $activityProperties['attributes'] = $this->getAttributes();
        }
        
        $loggingService->logActivity(
            $action,
            $this,
            $activityProperties,
            $this->getActivityDescription($action)
        );
    }

    protected function getActivityDescription(string $action): string
    {
        $modelName = $this->getModelDisplayName();
        
        $descriptions = [
            'created' => "Membuat {$modelName} baru",
            'updated' => "Memperbarui {$modelName}",
            'deleted' => "Menghapus {$modelName}",
        ];
        
        return $descriptions[$action] ?? "Aksi {$action} pada {$modelName}";
    }

    protected function getModelDisplayName(): string
    {
        $modelNames = [
            'App\Models\Pasien' => 'Pasien',
            'App\Models\Dokter' => 'Dokter',
            'App\Models\Tindakan' => 'Tindakan',
            'App\Models\Pendapatan' => 'Pendapatan',
            'App\Models\Pengeluaran' => 'Pengeluaran',
            'App\Models\PendapatanHarian' => 'Pendapatan Harian',
            'App\Models\PengeluaranHarian' => 'Pengeluaran Harian',
            'App\Models\JumlahPasienHarian' => 'Jumlah Pasien Harian',
            'App\Models\User' => 'User',
            'App\Models\Role' => 'Role',
            'App\Models\Pegawai' => 'Pegawai',
            'App\Models\JenisTindakan' => 'Jenis Tindakan',
        ];
        
        return $modelNames[get_class($this)] ?? class_basename($this);
    }

    /**
     * Get the attributes that should be logged
     */
    public function getLoggedAttributes(): array
    {
        if (property_exists($this, 'loggedAttributes')) {
            return $this->loggedAttributes;
        }
        
        return $this->getFillable();
    }

    /**
     * Get the attributes that should be hidden from logs
     */
    public function getHiddenLogAttributes(): array
    {
        if (property_exists($this, 'hiddenLogAttributes')) {
            return $this->hiddenLogAttributes;
        }
        
        return $this->getHidden();
    }

    /**
     * Determine if the model should log this action
     */
    public function shouldLogAction(string $action): bool
    {
        if (property_exists($this, 'loggedActions')) {
            return in_array($action, $this->loggedActions);
        }
        
        return true; // Log all actions by default
    }
}