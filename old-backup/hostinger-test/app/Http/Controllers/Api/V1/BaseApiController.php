<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BaseApiController extends Controller
{
    /**
     * Success response method
     */
    protected function successResponse($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Error response method
     */
    protected function errorResponse(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Paginated response method
     */
    protected function paginatedResponse($paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get authenticated user
     */
    protected function getAuthUser()
    {
        return Auth::user();
    }

    /**
     * Check if user has required role
     */
    protected function checkUserRole(string $role): bool
    {
        $user = $this->getAuthUser();
        return $user && $user->hasRole($role);
    }

    /**
     * Validate API request permissions
     */
    protected function validateApiPermissions(array $allowedRoles = ['petugas']): JsonResponse|bool
    {
        $user = $this->getAuthUser();
        
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        if (!empty($allowedRoles)) {
            $hasPermission = false;
            foreach ($allowedRoles as $role) {
                if ($user->hasRole($role)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                return $this->errorResponse('Forbidden: Insufficient permissions', 403);
            }
        }

        return true;
    }

    /**
     * Log API activity
     */
    protected function logApiActivity(string $action, array $data = [], string $level = 'info'): void
    {
        $user = $this->getAuthUser();
        
        $logData = [
            'user_id' => $user?->id,
            'user_role' => $user?->role?->name,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
        ];

        Log::log($level, 'API Activity: ' . $action, $logData);
    }

    /**
     * Handle API exceptions
     */
    protected function handleApiException(\Exception $e, string $context = 'API Error'): JsonResponse
    {
        Log::error($context . ': ' . $e->getMessage(), [
            'exception' => $e,
            'user_id' => $this->getAuthUser()?->id,
            'request_url' => request()->fullUrl(),
            'request_method' => request()->method(),
            'request_data' => request()->all(),
        ]);

        if (app()->isProduction()) {
            return $this->errorResponse('Internal server error', 500);
        }

        return $this->errorResponse(
            'Error: ' . $e->getMessage(),
            500,
            [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]
        );
    }

    /**
     * Apply filters from request
     */
    protected function applyFilters($query, Request $request, array $allowedFilters = []): mixed
    {
        foreach ($allowedFilters as $filter => $column) {
            $value = $request->get($filter);
            if ($value !== null && $value !== '') {
                if (is_array($column)) {
                    // Custom filter logic
                    if (isset($column['type'])) {
                        switch ($column['type']) {
                            case 'date_range':
                                if ($filter === 'date_from') {
                                    $query->where($column['column'], '>=', $value);
                                } elseif ($filter === 'date_to') {
                                    $query->where($column['column'], '<=', $value);
                                }
                                break;
                            case 'like':
                                $query->where($column['column'], 'like', '%' . $value . '%');
                                break;
                            case 'in':
                                if (is_array($value)) {
                                    $query->whereIn($column['column'], $value);
                                }
                                break;
                            case 'relation':
                                $query->whereHas($column['relation'], function ($q) use ($column, $value) {
                                    $q->where($column['column'], 'like', '%' . $value . '%');
                                });
                                break;
                        }
                    }
                } else {
                    // Simple where clause
                    $query->where($column, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Apply sorting from request
     */
    protected function applySorting($query, Request $request, array $allowedSorts = [], string $defaultSort = 'created_at', string $defaultDirection = 'desc'): mixed
    {
        $sort = $request->get('sort', $defaultSort);
        $direction = $request->get('direction', $defaultDirection);

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy($defaultSort, $defaultDirection);
        }

        return $query;
    }

    /**
     * Validate pagination parameters
     */
    protected function getPaginationParams(Request $request): array
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100); // Between 1 and 100

        return [
            'per_page' => $perPage,
            'page' => max((int) $request->get('page', 1), 1),
        ];
    }

    /**
     * Transform model for API response
     */
    protected function transformModel($model, array $fields = []): array
    {
        if (empty($fields)) {
            return $model->toArray();
        }

        $result = [];
        foreach ($fields as $field) {
            if (str_contains($field, '.')) {
                // Handle nested relationships
                $parts = explode('.', $field);
                $value = $model;
                foreach ($parts as $part) {
                    $value = $value?->{$part};
                    if ($value === null) break;
                }
                $result[$field] = $value;
            } else {
                $result[$field] = $model->{$field} ?? null;
            }
        }

        return $result;
    }

    /**
     * Get request metadata
     */
    protected function getRequestMetadata(): array
    {
        return [
            'request_id' => request()->header('X-Request-ID', uniqid()),
            'api_version' => 'v1',
            'client_version' => request()->header('X-Client-Version'),
            'platform' => request()->header('X-Platform', 'web'),
            'device_id' => request()->header('X-Device-ID'),
        ];
    }

    /**
     * Add metadata to response
     */
    protected function addMetadata(array $response): array
    {
        $response['metadata'] = $this->getRequestMetadata();
        return $response;
    }
}