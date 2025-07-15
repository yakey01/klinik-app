<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Carbon\Carbon;
use Exception;

class AdvancedSearchService
{
    protected array $supportedOperators = [
        'equals' => '=',
        'not_equals' => '!=',
        'contains' => 'LIKE',
        'not_contains' => 'NOT LIKE',
        'starts_with' => 'LIKE',
        'ends_with' => 'LIKE',
        'greater_than' => '>',
        'greater_than_or_equal' => '>=',
        'less_than' => '<',
        'less_than_or_equal' => '<=',
        'between' => 'BETWEEN',
        'not_between' => 'NOT BETWEEN',
        'in' => 'IN',
        'not_in' => 'NOT IN',
        'is_null' => 'IS NULL',
        'is_not_null' => 'IS NOT NULL',
        'date_equals' => 'DATE_EQUALS',
        'date_before' => 'DATE_BEFORE',
        'date_after' => 'DATE_AFTER',
        'date_between' => 'DATE_BETWEEN',
    ];

    protected array $supportedSortOrders = ['asc', 'desc'];

    /**
     * Perform advanced search with filters
     */
    public function search(string $modelClass, array $searchParams): array
    {
        try {
            $query = $modelClass::query();
            
            // Apply filters
            if (!empty($searchParams['filters'])) {
                $query = $this->applyFilters($query, $searchParams['filters']);
            }
            
            // Apply search terms
            if (!empty($searchParams['search'])) {
                $query = $this->applySearchTerms($query, $searchParams['search'], $modelClass);
            }
            
            // Apply sorting
            if (!empty($searchParams['sort'])) {
                $query = $this->applySorting($query, $searchParams['sort']);
            }
            
            // Apply pagination
            $perPage = $searchParams['per_page'] ?? 25;
            $page = $searchParams['page'] ?? 1;
            
            $results = $query->paginate($perPage, ['*'], 'page', $page);
            
            // Log search operation
            $this->logSearchOperation($modelClass, $searchParams, $results->total());
            
            return [
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                    'has_more_pages' => $results->hasMorePages(),
                ],
                'applied_filters' => $searchParams['filters'] ?? [],
                'search_terms' => $searchParams['search'] ?? '',
                'sort' => $searchParams['sort'] ?? [],
            ];
            
        } catch (Exception $e) {
            Log::error('Advanced search failed', [
                'model' => $modelClass,
                'params' => $searchParams,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter) {
            if (!isset($filter['field']) || !isset($filter['operator'])) {
                continue;
            }
            
            $field = $filter['field'];
            $operator = $filter['operator'];
            $value = $filter['value'] ?? null;
            
            if (!isset($this->supportedOperators[$operator])) {
                continue;
            }
            
            $query = $this->applyFilter($query, $field, $operator, $value);
        }
        
        return $query;
    }

    /**
     * Apply single filter to query
     */
    protected function applyFilter(Builder $query, string $field, string $operator, $value): Builder
    {
        switch ($operator) {
            case 'equals':
                return $query->where($field, '=', $value);
                
            case 'not_equals':
                return $query->where($field, '!=', $value);
                
            case 'contains':
                return $query->where($field, 'LIKE', "%{$value}%");
                
            case 'not_contains':
                return $query->where($field, 'NOT LIKE', "%{$value}%");
                
            case 'starts_with':
                return $query->where($field, 'LIKE', "{$value}%");
                
            case 'ends_with':
                return $query->where($field, 'LIKE', "%{$value}");
                
            case 'greater_than':
                return $query->where($field, '>', $value);
                
            case 'greater_than_or_equal':
                return $query->where($field, '>=', $value);
                
            case 'less_than':
                return $query->where($field, '<', $value);
                
            case 'less_than_or_equal':
                return $query->where($field, '<=', $value);
                
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    return $query->whereBetween($field, $value);
                }
                break;
                
            case 'not_between':
                if (is_array($value) && count($value) === 2) {
                    return $query->whereNotBetween($field, $value);
                }
                break;
                
            case 'in':
                if (is_array($value)) {
                    return $query->whereIn($field, $value);
                }
                break;
                
            case 'not_in':
                if (is_array($value)) {
                    return $query->whereNotIn($field, $value);
                }
                break;
                
            case 'is_null':
                return $query->whereNull($field);
                
            case 'is_not_null':
                return $query->whereNotNull($field);
                
            case 'date_equals':
                return $query->whereDate($field, '=', $value);
                
            case 'date_before':
                return $query->whereDate($field, '<', $value);
                
            case 'date_after':
                return $query->whereDate($field, '>', $value);
                
            case 'date_between':
                if (is_array($value) && count($value) === 2) {
                    return $query->whereBetween(DB::raw("DATE($field)"), $value);
                }
                break;
        }
        
        return $query;
    }

    /**
     * Apply search terms to query
     */
    protected function applySearchTerms(Builder $query, string $searchTerm, string $modelClass): Builder
    {
        $model = new $modelClass;
        
        // Get searchable fields
        $searchableFields = method_exists($model, 'getSearchableFields') 
            ? $model->getSearchableFields() 
            : $model->getFillable();
        
        if (empty($searchableFields)) {
            return $query;
        }
        
        return $query->where(function ($query) use ($searchTerm, $searchableFields) {
            foreach ($searchableFields as $field) {
                $query->orWhere($field, 'LIKE', "%{$searchTerm}%");
            }
        });
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting(Builder $query, array $sortParams): Builder
    {
        foreach ($sortParams as $sort) {
            if (!isset($sort['field'])) {
                continue;
            }
            
            $field = $sort['field'];
            $direction = $sort['direction'] ?? 'asc';
            
            if (!in_array($direction, $this->supportedSortOrders)) {
                $direction = 'asc';
            }
            
            $query->orderBy($field, $direction);
        }
        
        return $query;
    }

    /**
     * Get searchable fields for model
     */
    public function getSearchableFields(string $modelClass): array
    {
        try {
            $model = new $modelClass;
            
            // Get searchable fields from model
            if (method_exists($model, 'getSearchableFields')) {
                $searchableFields = $model->getSearchableFields();
            } else {
                $searchableFields = $model->getFillable();
            }
            
            // Get field types for better filtering
            $fieldTypes = [];
            if (method_exists($model, 'getFieldTypes')) {
                $fieldTypes = $model->getFieldTypes();
            }
            
            return [
                'searchable_fields' => $searchableFields,
                'field_types' => $fieldTypes,
                'supported_operators' => $this->supportedOperators,
                'supported_sort_orders' => $this->supportedSortOrders,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get searchable fields', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Build advanced search query
     */
    public function buildSearchQuery(string $modelClass, array $conditions): Builder
    {
        $query = $modelClass::query();
        
        foreach ($conditions as $condition) {
            if ($condition['type'] === 'where') {
                $query = $this->applyFilter(
                    $query, 
                    $condition['field'], 
                    $condition['operator'], 
                    $condition['value']
                );
            } elseif ($condition['type'] === 'orWhere') {
                $query->orWhere(function ($subQuery) use ($condition) {
                    $this->applyFilter(
                        $subQuery, 
                        $condition['field'], 
                        $condition['operator'], 
                        $condition['value']
                    );
                });
            } elseif ($condition['type'] === 'whereHas') {
                $query->whereHas($condition['relation'], function ($subQuery) use ($condition) {
                    $this->applyFilter(
                        $subQuery, 
                        $condition['field'], 
                        $condition['operator'], 
                        $condition['value']
                    );
                });
            }
        }
        
        return $query;
    }

    /**
     * Save search for later use
     */
    public function saveSearch(string $modelClass, array $searchParams, string $name): array
    {
        try {
            $savedSearch = DB::table('saved_searches')->insert([
                'user_id' => auth()->id(),
                'model_class' => $modelClass,
                'name' => $name,
                'search_params' => json_encode($searchParams),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return [
                'success' => true,
                'message' => 'Search saved successfully',
                'name' => $name,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to save search', [
                'model' => $modelClass,
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get saved searches for user
     */
    public function getSavedSearches(string $modelClass = null): array
    {
        try {
            $query = DB::table('saved_searches')
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc');
            
            if ($modelClass) {
                $query->where('model_class', $modelClass);
            }
            
            $savedSearches = $query->get()->map(function ($search) {
                return [
                    'id' => $search->id,
                    'name' => $search->name,
                    'model_class' => $search->model_class,
                    'search_params' => json_decode($search->search_params, true),
                    'created_at' => $search->created_at,
                ];
            });
            
            return [
                'success' => true,
                'data' => $savedSearches,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get saved searches', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get search suggestions
     */
    public function getSearchSuggestions(string $modelClass, string $field, string $term): array
    {
        try {
            $suggestions = $modelClass::select($field)
                ->where($field, 'LIKE', "%{$term}%")
                ->distinct()
                ->limit(10)
                ->pluck($field)
                ->toArray();
            
            return [
                'success' => true,
                'field' => $field,
                'term' => $term,
                'suggestions' => $suggestions,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get search suggestions', [
                'model' => $modelClass,
                'field' => $field,
                'term' => $term,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get search statistics
     */
    public function getSearchStats(string $modelClass = null, int $days = 30): array
    {
        try {
            $query = AuditLog::where('action', 'search')
                ->where('created_at', '>=', now()->subDays($days));
            
            if ($modelClass) {
                $query->where('model_type', $modelClass);
            }
            
            $logs = $query->get();
            
            $stats = [
                'total_searches' => $logs->count(),
                'unique_users' => $logs->unique('user_id')->count(),
                'popular_search_terms' => [],
                'popular_filters' => [],
                'search_frequency' => [],
            ];
            
            // Analyze search patterns
            foreach ($logs as $log) {
                $changes = json_decode($log->changes, true);
                
                // Popular search terms
                if (isset($changes['search_terms']) && !empty($changes['search_terms'])) {
                    $term = $changes['search_terms'];
                    $stats['popular_search_terms'][$term] = ($stats['popular_search_terms'][$term] ?? 0) + 1;
                }
                
                // Popular filters
                if (isset($changes['applied_filters'])) {
                    foreach ($changes['applied_filters'] as $filter) {
                        $key = $filter['field'] . ':' . $filter['operator'];
                        $stats['popular_filters'][$key] = ($stats['popular_filters'][$key] ?? 0) + 1;
                    }
                }
                
                // Search frequency by date
                $date = $log->created_at->format('Y-m-d');
                $stats['search_frequency'][$date] = ($stats['search_frequency'][$date] ?? 0) + 1;
            }
            
            // Sort by popularity
            arsort($stats['popular_search_terms']);
            arsort($stats['popular_filters']);
            ksort($stats['search_frequency']);
            
            return [
                'success' => true,
                'data' => $stats,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get search statistics', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Log search operation
     */
    protected function logSearchOperation(string $modelClass, array $searchParams, int $resultCount): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'search',
                'model_type' => $modelClass,
                'model_id' => null,
                'changes' => json_encode([
                    'model' => class_basename($modelClass),
                    'search_terms' => $searchParams['search'] ?? '',
                    'applied_filters' => $searchParams['filters'] ?? [],
                    'sort' => $searchParams['sort'] ?? [],
                    'result_count' => $resultCount,
                    'per_page' => $searchParams['per_page'] ?? 25,
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'risk_level' => 'low',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log search operation', [
                'error' => $e->getMessage(),
                'model' => $modelClass
            ]);
        }
    }
}