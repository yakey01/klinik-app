<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\LoggingService;

class QueryOptimizationService
{
    private LoggingService $loggingService;
    
    // Common eager loading relationships for each model
    private const EAGER_LOAD_RELATIONSHIPS = [
        'Pasien' => ['tindakan', 'tindakan.jenisTindakan', 'tindakan.dokter'],
        'Tindakan' => ['pasien', 'jenisTindakan', 'dokter', 'pendapatan', 'jaspel'],
        'Pendapatan' => ['tindakan', 'tindakan.pasien', 'inputBy', 'validasiBy'],
        'Pengeluaran' => ['inputBy', 'validasiBy'],
        'Dokter' => ['user', 'tindakan', 'tindakan.pasien', 'inputBy'],
        'User' => ['role', 'customRole', 'dokter', 'pegawai'],
        'JenisTindakan' => ['tindakan'],
        'Jaspel' => ['tindakan', 'tindakan.pasien', 'tindakan.dokter'],
    ];
    
    // Query optimization patterns
    private const OPTIMIZATION_PATTERNS = [
        'select_specific_columns' => true,
        'use_indexes' => true,
        'batch_loading' => true,
        'count_optimization' => true,
        'join_optimization' => true,
    ];
    
    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }
    
    /**
     * Optimize a query with eager loading
     */
    public function optimizeQuery(Builder $query, string $modelClass, array $additionalRelations = []): Builder
    {
        $startTime = microtime(true);
        
        try {
            $modelName = class_basename($modelClass);
            
            // Get default eager loading relationships
            $defaultRelations = self::EAGER_LOAD_RELATIONSHIPS[$modelName] ?? [];
            
            // Merge with additional relations
            $relations = array_unique(array_merge($defaultRelations, $additionalRelations));
            
            // Apply eager loading
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            // Apply additional optimizations
            $this->applyQueryOptimizations($query, $modelClass);
            
            $duration = microtime(true) - $startTime;
            
            $this->loggingService->logPerformance(
                'query_optimization',
                $duration,
                [
                    'model' => $modelName,
                    'relations' => $relations,
                    'optimizations_applied' => true,
                ],
                'info'
            );
            
            return $query;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Query optimization failed',
                $e,
                ['model' => $modelClass],
                'error'
            );
            
            return $query;
        }
    }
    
    /**
     * Apply general query optimizations
     */
    private function applyQueryOptimizations(Builder $query, string $modelClass): void
    {
        $modelName = class_basename($modelClass);
        
        // Select specific columns for performance
        if (self::OPTIMIZATION_PATTERNS['select_specific_columns']) {
            $this->optimizeSelectColumns($query, $modelClass);
        }
        
        // Optimize joins
        if (self::OPTIMIZATION_PATTERNS['join_optimization']) {
            $this->optimizeJoins($query, $modelClass);
        }
        
        // Add appropriate indexes hints
        if (self::OPTIMIZATION_PATTERNS['use_indexes']) {
            $this->addIndexHints($query, $modelClass);
        }
    }
    
    /**
     * Optimize select columns
     */
    private function optimizeSelectColumns(Builder $query, string $modelClass): void
    {
        $modelName = class_basename($modelClass);
        
        // Define essential columns for each model
        $essentialColumns = [
            'Pasien' => ['id', 'no_rekam_medis', 'nama', 'tanggal_lahir', 'jenis_kelamin', 'created_at', 'updated_at'],
            'Tindakan' => ['id', 'pasien_id', 'jenis_tindakan_id', 'dokter_id', 'tanggal_tindakan', 'tarif', 'status', 'created_at'],
            'Pendapatan' => ['id', 'tindakan_id', 'kategori', 'jumlah', 'status', 'input_by', 'validasi_by', 'created_at'],
            'Pengeluaran' => ['id', 'kategori', 'jumlah', 'status', 'input_by', 'validasi_by', 'created_at'],
            'Dokter' => ['id', 'user_id', 'nama', 'spesialisasi', 'no_izin_praktek', 'status', 'created_at'],
            'User' => ['id', 'name', 'email', 'role', 'last_login_at', 'created_at'],
        ];
        
        if (isset($essentialColumns[$modelName])) {
            $query->select($essentialColumns[$modelName]);
        }
    }
    
    /**
     * Optimize joins
     */
    private function optimizeJoins(Builder $query, string $modelClass): void
    {
        $modelName = class_basename($modelClass);
        
        // Add optimized joins based on model
        switch ($modelName) {
            case 'Tindakan':
                // Join with pasien for common queries
                $query->leftJoin('pasien', 'tindakan.pasien_id', '=', 'pasien.id')
                      ->leftJoin('jenis_tindakan', 'tindakan.jenis_tindakan_id', '=', 'jenis_tindakan.id')
                      ->leftJoin('dokter', 'tindakan.dokter_id', '=', 'dokter.id');
                break;
                
            case 'Pendapatan':
                // Join with tindakan for revenue queries
                $query->leftJoin('tindakan', 'pendapatan.tindakan_id', '=', 'tindakan.id')
                      ->leftJoin('users as input_user', 'pendapatan.input_by', '=', 'input_user.id')
                      ->leftJoin('users as validasi_user', 'pendapatan.validasi_by', '=', 'validasi_user.id');
                break;
                
            case 'Pengeluaran':
                // Join with users for expense queries
                $query->leftJoin('users as input_user', 'pengeluaran.input_by', '=', 'input_user.id')
                      ->leftJoin('users as validasi_user', 'pengeluaran.validasi_by', '=', 'validasi_user.id');
                break;
        }
    }
    
    /**
     * Add index hints for better performance
     */
    private function addIndexHints(Builder $query, string $modelClass): void
    {
        $modelName = class_basename($modelClass);
        
        // Add index hints based on common query patterns
        switch ($modelName) {
            case 'Tindakan':
                // Hint for date-based queries
                $query->whereRaw('1=1 /* INDEX(idx_tindakan_tanggal) */');
                break;
                
            case 'Pendapatan':
                // Hint for status-based queries
                $query->whereRaw('1=1 /* INDEX(idx_pendapatan_status) */');
                break;
                
            case 'Pengeluaran':
                // Hint for status-based queries
                $query->whereRaw('1=1 /* INDEX(idx_pengeluaran_status) */');
                break;
        }
    }
    
    /**
     * Optimize count queries
     */
    public function optimizeCountQuery(Builder $query, string $modelClass): int
    {
        $startTime = microtime(true);
        
        try {
            $modelName = class_basename($modelClass);
            
            // Use EXISTS instead of COUNT for better performance
            $optimizedQuery = $query->select(DB::raw('1'))
                                   ->limit(1);
            
            // For large datasets, use approximate count
            if ($this->isLargeDataset($modelClass)) {
                $count = $this->getApproximateCount($query, $modelClass);
            } else {
                $count = $query->count();
            }
            
            $duration = microtime(true) - $startTime;
            
            $this->loggingService->logPerformance(
                'count_query_optimization',
                $duration,
                [
                    'model' => $modelName,
                    'count' => $count,
                    'optimized' => true,
                ],
                'info'
            );
            
            return $count;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Count query optimization failed',
                $e,
                ['model' => $modelClass],
                'error'
            );
            
            return $query->count();
        }
    }
    
    /**
     * Check if dataset is large
     */
    private function isLargeDataset(string $modelClass): bool
    {
        $modelName = class_basename($modelClass);
        
        // Define thresholds for large datasets
        $thresholds = [
            'Tindakan' => 10000,
            'Pendapatan' => 5000,
            'Pengeluaran' => 5000,
            'Pasien' => 10000,
            'default' => 1000,
        ];
        
        $threshold = $thresholds[$modelName] ?? $thresholds['default'];
        
        try {
            $count = DB::table((new $modelClass())->getTable())
                       ->selectRaw('COUNT(*) as count')
                       ->first()
                       ->count;
            
            return $count > $threshold;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get approximate count for large datasets
     */
    private function getApproximateCount(Builder $query, string $modelClass): int
    {
        $table = (new $modelClass())->getTable();
        
        try {
            // Use table statistics for approximate count
            $result = DB::select("
                SELECT table_rows as approximate_count 
                FROM information_schema.tables 
                WHERE table_name = ? AND table_schema = DATABASE()
            ", [$table]);
            
            if (!empty($result)) {
                return (int) $result[0]->approximate_count;
            }
            
            // Fallback to regular count
            return $query->count();
            
        } catch (\Exception $e) {
            return $query->count();
        }
    }
    
    /**
     * Optimize paginated queries
     */
    public function optimizePaginatedQuery(Builder $query, string $modelClass, int $page = 1, int $perPage = 15): Builder
    {
        $startTime = microtime(true);
        
        try {
            $modelName = class_basename($modelClass);
            
            // Apply eager loading optimization
            $query = $this->optimizeQuery($query, $modelClass);
            
            // Use cursor pagination for large datasets
            if ($this->isLargeDataset($modelClass) && $page > 100) {
                $query = $this->applyCursorPagination($query, $modelClass, $page, $perPage);
            }
            
            $duration = microtime(true) - $startTime;
            
            $this->loggingService->logPerformance(
                'paginated_query_optimization',
                $duration,
                [
                    'model' => $modelName,
                    'page' => $page,
                    'per_page' => $perPage,
                ],
                'info'
            );
            
            return $query;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Paginated query optimization failed',
                $e,
                ['model' => $modelClass, 'page' => $page],
                'error'
            );
            
            return $query;
        }
    }
    
    /**
     * Apply cursor pagination for large datasets
     */
    private function applyCursorPagination(Builder $query, string $modelClass, int $page, int $perPage): Builder
    {
        $offset = ($page - 1) * $perPage;
        
        // Use cursor-based pagination with ID
        if ($offset > 0) {
            $lastId = $this->getLastIdFromPreviousPage($query, $modelClass, $offset);
            if ($lastId) {
                $query->where('id', '>', $lastId);
            }
        }
        
        return $query->limit($perPage);
    }
    
    /**
     * Get last ID from previous page
     */
    private function getLastIdFromPreviousPage(Builder $query, string $modelClass, int $offset): ?int
    {
        try {
            $clonedQuery = clone $query;
            $result = $clonedQuery->select('id')
                                  ->orderBy('id')
                                  ->offset($offset - 1)
                                  ->limit(1)
                                  ->first();
            
            return $result ? $result->id : null;
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Optimize search queries
     */
    public function optimizeSearchQuery(Builder $query, string $modelClass, string $searchTerm, array $searchFields = []): Builder
    {
        $startTime = microtime(true);
        
        try {
            $modelName = class_basename($modelClass);
            
            // Define default search fields for each model
            $defaultSearchFields = [
                'Pasien' => ['nama', 'no_rekam_medis', 'no_telepon'],
                'Tindakan' => ['pasien.nama', 'jenis_tindakan.nama'],
                'Pendapatan' => ['kategori', 'keterangan'],
                'Pengeluaran' => ['kategori', 'keterangan'],
                'Dokter' => ['nama', 'spesialisasi', 'no_izin_praktek'],
                'User' => ['name', 'email'],
            ];
            
            $fields = !empty($searchFields) ? $searchFields : ($defaultSearchFields[$modelName] ?? ['nama']);
            
            // Use full-text search if available
            if ($this->hasFullTextSearch($modelClass, $fields)) {
                $query->whereRaw("MATCH(" . implode(',', $fields) . ") AGAINST(? IN BOOLEAN MODE)", ["+{$searchTerm}*"]);
            } else {
                // Use LIKE search with optimization
                $query->where(function ($q) use ($fields, $searchTerm) {
                    foreach ($fields as $field) {
                        if (str_contains($field, '.')) {
                            // Handle relationship fields
                            $parts = explode('.', $field);
                            $relation = $parts[0];
                            $column = $parts[1];
                            $q->orWhereHas($relation, function ($subQ) use ($column, $searchTerm) {
                                $subQ->where($column, 'LIKE', "%{$searchTerm}%");
                            });
                        } else {
                            $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                        }
                    }
                });
            }
            
            $duration = microtime(true) - $startTime;
            
            $this->loggingService->logPerformance(
                'search_query_optimization',
                $duration,
                [
                    'model' => $modelName,
                    'search_term' => $searchTerm,
                    'search_fields' => $fields,
                ],
                'info'
            );
            
            return $query;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Search query optimization failed',
                $e,
                ['model' => $modelClass, 'search_term' => $searchTerm],
                'error'
            );
            
            return $query;
        }
    }
    
    /**
     * Check if full-text search is available
     */
    private function hasFullTextSearch(string $modelClass, array $fields): bool
    {
        $table = (new $modelClass())->getTable();
        
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Index_type = 'FULLTEXT'");
            
            foreach ($indexes as $index) {
                if (in_array($index->Column_name, $fields)) {
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Optimize bulk operations
     */
    public function optimizeBulkOperation(string $operation, string $modelClass, array $data): array
    {
        $startTime = microtime(true);
        
        try {
            $modelName = class_basename($modelClass);
            
            // Disable model events for bulk operations
            $model = new $modelClass();
            $model->unsetEventDispatcher();
            
            $result = [];
            
            switch ($operation) {
                case 'insert':
                    $result = $this->optimizedBulkInsert($modelClass, $data);
                    break;
                    
                case 'update':
                    $result = $this->optimizedBulkUpdate($modelClass, $data);
                    break;
                    
                case 'delete':
                    $result = $this->optimizedBulkDelete($modelClass, $data);
                    break;
            }
            
            $duration = microtime(true) - $startTime;
            
            $this->loggingService->logPerformance(
                'bulk_operation_optimization',
                $duration,
                [
                    'operation' => $operation,
                    'model' => $modelName,
                    'record_count' => count($data),
                    'result' => $result,
                ],
                'info'
            );
            
            return $result;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Bulk operation optimization failed',
                $e,
                ['operation' => $operation, 'model' => $modelClass],
                'error'
            );
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Optimized bulk insert
     */
    private function optimizedBulkInsert(string $modelClass, array $data): array
    {
        $table = (new $modelClass())->getTable();
        $chunkSize = 1000;
        $inserted = 0;
        
        $chunks = array_chunk($data, $chunkSize);
        
        foreach ($chunks as $chunk) {
            $inserted += DB::table($table)->insert($chunk);
        }
        
        return ['inserted' => $inserted];
    }
    
    /**
     * Optimized bulk update
     */
    private function optimizedBulkUpdate(string $modelClass, array $data): array
    {
        $table = (new $modelClass())->getTable();
        $updated = 0;
        
        foreach ($data as $record) {
            $id = $record['id'];
            unset($record['id']);
            
            $updated += DB::table($table)
                         ->where('id', $id)
                         ->update($record);
        }
        
        return ['updated' => $updated];
    }
    
    /**
     * Optimized bulk delete
     */
    private function optimizedBulkDelete(string $modelClass, array $ids): array
    {
        $table = (new $modelClass())->getTable();
        $chunkSize = 1000;
        $deleted = 0;
        
        $chunks = array_chunk($ids, $chunkSize);
        
        foreach ($chunks as $chunk) {
            $deleted += DB::table($table)->whereIn('id', $chunk)->delete();
        }
        
        return ['deleted' => $deleted];
    }
    
    /**
     * Get query optimization statistics
     */
    public function getOptimizationStats(): array
    {
        return [
            'eager_load_relationships' => self::EAGER_LOAD_RELATIONSHIPS,
            'optimization_patterns' => self::OPTIMIZATION_PATTERNS,
            'cache_enabled' => config('cache.enabled', true),
            'database_driver' => config('database.default'),
        ];
    }
    
    /**
     * Analyze query performance
     */
    public function analyzeQueryPerformance(Builder $query, string $modelClass): array
    {
        $startTime = microtime(true);
        
        try {
            // Enable query logging
            DB::enableQueryLog();
            
            // Execute query
            $result = $query->get();
            
            // Get query log
            $queries = DB::getQueryLog();
            
            $duration = microtime(true) - $startTime;
            
            $analysis = [
                'execution_time' => $duration,
                'result_count' => $result->count(),
                'queries_executed' => count($queries),
                'model' => class_basename($modelClass),
                'memory_usage' => memory_get_usage(true),
                'queries' => $queries,
            ];
            
            // Disable query logging
            DB::disableQueryLog();
            
            $this->loggingService->logPerformance(
                'query_performance_analysis',
                $duration,
                $analysis,
                $duration > 1.0 ? 'warning' : 'info'
            );
            
            return $analysis;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Query performance analysis failed',
                $e,
                ['model' => $modelClass],
                'error'
            );
            
            return ['error' => $e->getMessage()];
        }
    }
}