<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoggingService
{
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'api_token',
        'remember_token',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'telegram_token',
        'bot_token',
    ];

    protected array $logLevels = [
        'emergency' => 800,
        'alert' => 700,
        'critical' => 600,
        'error' => 500,
        'warning' => 400,
        'notice' => 300,
        'info' => 200,
        'debug' => 100,
    ];

    public function logActivity(
        string $action,
        Model $model = null,
        array $properties = [],
        string $description = null,
        User $user = null
    ): AuditLog {
        $user = $user ?? Auth::user();
        
        $logData = [
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'user_role' => $user?->roles?->first()?->name,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $description ?? $this->generateDescription($action, $model),
            'properties' => $this->sanitizeProperties($properties),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'session_id' => session()->getId(),
            'created_at' => now(),
        ];

        // Add model-specific data
        if ($model) {
            $logData['model_data'] = $this->getModelData($model);
        }

        // Create audit log
        $auditLog = AuditLog::create($logData);

        // Also log to Laravel log for debugging
        Log::info("Activity logged: {$action}", [
            'user' => $user?->name,
            'model' => $model ? get_class($model) . ':' . $model->id : 'none',
            'ip' => Request::ip(),
        ]);

        return $auditLog;
    }

    public function logError(
        string $message,
        \Exception $exception = null,
        array $context = [],
        string $level = 'error'
    ): void {
        $logData = [
            'level' => $level,
            'message' => $message,
            'context' => $this->sanitizeProperties($context),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'session_id' => session()->getId(),
            'timestamp' => now(),
        ];

        if ($exception) {
            $logData['exception'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        // Log to Laravel log
        Log::log($level, $message, $logData);

        // Store critical errors in database
        if ($this->logLevels[$level] >= $this->logLevels['error']) {
            $this->storeErrorLog($logData);
        }
    }

    public function logSecurity(
        string $action,
        User $user = null,
        string $description = null,
        array $context = []
    ): void {
        $user = $user ?? Auth::user();
        
        $logData = [
            'action' => $action,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'description' => $description ?? "Security event: {$action}",
            'context' => $this->sanitizeProperties($context),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'session_id' => session()->getId(),
            'severity' => $this->getSecuritySeverity($action),
            'timestamp' => now(),
        ];

        // Log to Laravel log with security channel
        Log::channel('security')->warning("Security event: {$action}", $logData);

        // Store in database
        $this->storeSecurityLog($logData);
    }

    public function logPerformance(
        string $operation,
        float $duration,
        array $metrics = [],
        string $level = 'info'
    ): void {
        $logData = [
            'operation' => $operation,
            'duration' => $duration,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'metrics' => $metrics,
            'user_id' => Auth::id(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => now(),
        ];

        Log::channel('performance')->log($level, "Performance: {$operation}", $logData);

        // Store slow operations in database
        if ($duration > 5.0) { // More than 5 seconds
            $this->storePerformanceLog($logData);
        }
    }

    public function logDatabaseQuery(
        string $query,
        array $bindings = [],
        float $duration = 0,
        string $connection = 'default'
    ): void {
        if (!config('logging.log_queries', false)) {
            return;
        }

        $logData = [
            'query' => $query,
            'bindings' => $bindings,
            'duration' => $duration,
            'connection' => $connection,
            'user_id' => Auth::id(),
            'url' => Request::fullUrl(),
            'timestamp' => now(),
        ];

        Log::channel('database')->info("Query executed", $logData);

        // Log slow queries
        if ($duration > 1.0) { // More than 1 second
            Log::channel('slow_queries')->warning("Slow query detected", $logData);
        }
    }

    public function logApiRequest(
        string $method,
        string $endpoint,
        array $request = [],
        array $response = [],
        int $statusCode = 200,
        float $duration = 0
    ): void {
        $logData = [
            'method' => $method,
            'endpoint' => $endpoint,
            'request' => $this->sanitizeProperties($request),
            'response' => $this->sanitizeProperties($response),
            'status_code' => $statusCode,
            'duration' => $duration,
            'user_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'timestamp' => now(),
        ];

        $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');
        
        Log::channel('api')->log($level, "API request: {$method} {$endpoint}", $logData);
    }

    protected function generateDescription(string $action, Model $model = null): string
    {
        $descriptions = [
            'created' => $model ? "Membuat {$this->getModelName($model)} baru" : 'Membuat data baru',
            'updated' => $model ? "Memperbarui {$this->getModelName($model)}" : 'Memperbarui data',
            'deleted' => $model ? "Menghapus {$this->getModelName($model)}" : 'Menghapus data',
            'viewed' => $model ? "Melihat {$this->getModelName($model)}" : 'Melihat data',
            'exported' => $model ? "Ekspor {$this->getModelName($model)}" : 'Ekspor data',
            'imported' => $model ? "Impor {$this->getModelName($model)}" : 'Impor data',
            'login' => 'Login ke sistem',
            'logout' => 'Logout dari sistem',
            'failed_login' => 'Gagal login',
            'password_reset' => 'Reset password',
            'bulk_update' => 'Update massal',
            'bulk_delete' => 'Hapus massal',
            'validation_approved' => 'Validasi disetujui',
            'validation_rejected' => 'Validasi ditolak',
            'validation_submitted' => 'Validasi diajukan',
        ];

        return $descriptions[$action] ?? "Aksi: {$action}";
    }

    protected function getModelName(Model $model): string
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
        ];

        return $modelNames[get_class($model)] ?? class_basename($model);
    }

    protected function getModelData(Model $model): array
    {
        $data = $model->toArray();
        
        // Remove sensitive fields
        foreach ($this->sensitiveFields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    protected function sanitizeProperties(array $properties): array
    {
        foreach ($properties as $key => $value) {
            if (in_array($key, $this->sensitiveFields)) {
                $properties[$key] = '[HIDDEN]';
            } elseif (is_array($value)) {
                $properties[$key] = $this->sanitizeProperties($value);
            }
        }

        return $properties;
    }

    protected function getSecuritySeverity(string $action): string
    {
        $severities = [
            'failed_login' => 'medium',
            'multiple_failed_login' => 'high',
            'unauthorized_access' => 'high',
            'permission_denied' => 'medium',
            'suspicious_activity' => 'high',
            'sql_injection_attempt' => 'critical',
            'xss_attempt' => 'critical',
            'password_changed' => 'medium',
            'role_changed' => 'high',
            'account_locked' => 'high',
            'account_unlocked' => 'medium',
            'two_factor_enabled' => 'low',
            'two_factor_disabled' => 'medium',
        ];

        return $severities[$action] ?? 'low';
    }

    protected function storeErrorLog(array $logData): void
    {
        try {
            \DB::table('error_logs')->insert([
                'level' => $logData['level'],
                'message' => $logData['message'],
                'context' => json_encode($logData['context']),
                'exception' => isset($logData['exception']) ? json_encode($logData['exception']) : null,
                'user_id' => $logData['user_id'],
                'ip_address' => $logData['ip_address'],
                'user_agent' => $logData['user_agent'],
                'url' => $logData['url'],
                'method' => $logData['method'],
                'session_id' => $logData['session_id'],
                'created_at' => $logData['timestamp'],
            ]);
        } catch (\Exception $e) {
            // Fallback to file log if database fails
            Log::error('Failed to store error log in database', [
                'original_error' => $logData,
                'storage_error' => $e->getMessage(),
            ]);
        }
    }

    protected function storeSecurityLog(array $logData): void
    {
        try {
            \DB::table('security_logs')->insert([
                'action' => $logData['action'],
                'user_id' => $logData['user_id'],
                'user_name' => $logData['user_name'],
                'user_email' => $logData['user_email'],
                'description' => $logData['description'],
                'context' => json_encode($logData['context']),
                'ip_address' => $logData['ip_address'],
                'user_agent' => $logData['user_agent'],
                'url' => $logData['url'],
                'method' => $logData['method'],
                'session_id' => $logData['session_id'],
                'severity' => $logData['severity'],
                'created_at' => $logData['timestamp'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store security log in database', [
                'original_log' => $logData,
                'storage_error' => $e->getMessage(),
            ]);
        }
    }

    protected function storePerformanceLog(array $logData): void
    {
        try {
            \DB::table('performance_logs')->insert([
                'operation' => $logData['operation'],
                'duration' => $logData['duration'],
                'memory_usage' => $logData['memory_usage'],
                'memory_peak' => $logData['memory_peak'],
                'metrics' => json_encode($logData['metrics']),
                'user_id' => $logData['user_id'],
                'url' => $logData['url'],
                'method' => $logData['method'],
                'created_at' => $logData['timestamp'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store performance log in database', [
                'original_log' => $logData,
                'storage_error' => $e->getMessage(),
            ]);
        }
    }

    public function getRecentLogs(string $type = 'activity', int $limit = 100): array
    {
        switch ($type) {
            case 'activity':
                return AuditLog::with('user')
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->toArray();
            
            case 'error':
                return \DB::table('error_logs')
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->toArray();
            
            case 'security':
                return \DB::table('security_logs')
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->toArray();
            
            case 'performance':
                return \DB::table('performance_logs')
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->toArray();
            
            default:
                return [];
        }
    }

    public function cleanupOldLogs(int $days = 30): array
    {
        $cutoffDate = Carbon::now()->subDays($days);
        $deleted = [
            'activity' => 0,
            'error' => 0,
            'security' => 0,
            'performance' => 0,
        ];

        try {
            // Clean activity logs
            $deleted['activity'] = AuditLog::where('created_at', '<', $cutoffDate)->delete();

            // Clean error logs
            $deleted['error'] = \DB::table('error_logs')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            // Clean security logs (keep longer)
            $securityCutoff = Carbon::now()->subDays($days * 2);
            $deleted['security'] = \DB::table('security_logs')
                ->where('created_at', '<', $securityCutoff)
                ->delete();

            // Clean performance logs
            $deleted['performance'] = \DB::table('performance_logs')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            Log::info('Log cleanup completed', $deleted);
        } catch (\Exception $e) {
            Log::error('Log cleanup failed', [
                'error' => $e->getMessage(),
                'deleted' => $deleted,
            ]);
        }

        return $deleted;
    }
}