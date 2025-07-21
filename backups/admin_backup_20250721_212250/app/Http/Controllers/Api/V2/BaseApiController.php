<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="2.0.0",
 *         title="Dokterku API v2",
 *         description="Medical Clinic Management System API v2",
 *         @OA\Contact(
 *             email="admin@dokterku.com"
 *         )
 *     ),
 *     @OA\Server(
 *         url="/api/v2",
 *         description="API v2 Server"
 *     ),
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="sanctum",
 *             type="http",
 *             scheme="bearer",
 *             bearerFormat="JWT",
 *             description="Laravel Sanctum token authentication"
 *         ),
 *         @OA\Schema(
 *             schema="User",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *             @OA\Property(property="username", type="string", example="johndoe"),
 *             @OA\Property(property="role", type="string", example="paramedis"),
 *             @OA\Property(property="phone", type="string", example="+628123456789"),
 *             @OA\Property(property="avatar", type="string", format="uri", example="https://example.com/avatar.jpg"),
 *             @OA\Property(property="is_active", type="boolean", example=true),
 *             @OA\Property(property="created_at", type="string", format="date-time"),
 *             @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true)
 *         )
 *     )
 * )
 */
class BaseApiController extends Controller
{
    /**
     * API version
     */
    protected string $version = '2.0';

    /**
     * Generate a unique request ID
     */
    protected function generateRequestId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Create a standardized success response
     */
    protected function successResponse(
        $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge([
                'version' => $this->version,
                'timestamp' => now()->toISOString(),
                'request_id' => $this->generateRequestId(),
            ], $meta)
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Create a standardized error response
     */
    protected function errorResponse(
        string $message = 'Error',
        int $statusCode = 400,
        $errors = null,
        string $errorCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'meta' => [
                'version' => $this->version,
                'timestamp' => now()->toISOString(),
                'request_id' => $this->generateRequestId(),
            ]
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Create a validation error response
     */
    protected function validationErrorResponse(
        $errors,
        string $message = 'The given data was invalid.'
    ): JsonResponse {
        return $this->errorResponse($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Create an unauthorized response
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthenticated'
    ): JsonResponse {
        return $this->errorResponse($message, 401, null, 'UNAUTHORIZED');
    }

    /**
     * Create a forbidden response
     */
    protected function forbiddenResponse(
        string $message = 'Access denied for this resource'
    ): JsonResponse {
        return $this->errorResponse($message, 403, null, 'FORBIDDEN');
    }

    /**
     * Create a not found response
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->errorResponse($message, 404, null, 'NOT_FOUND');
    }

    /**
     * Create a paginated response
     */
    protected function paginatedResponse(
        $paginatedData,
        string $message = 'Success'
    ): JsonResponse {
        $meta = [
            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem(),
                'has_more_pages' => $paginatedData->hasMorePages(),
                'next_page_url' => $paginatedData->nextPageUrl(),
                'prev_page_url' => $paginatedData->previousPageUrl(),
            ]
        ];

        return $this->successResponse(
            $paginatedData->items(),
            $message,
            200,
            $meta
        );
    }

    /**
     * Get authenticated user
     */
    protected function getAuthenticatedUser()
    {
        return auth('sanctum')->user();
    }

    /**
     * Check if user has required role
     */
    protected function hasRole(string $role): bool
    {
        $user = $this->getAuthenticatedUser();
        return $user && $user->hasRole($role);
    }

    /**
     * Check if user has any of the required roles
     */
    protected function hasAnyRole(array $roles): bool
    {
        $user = $this->getAuthenticatedUser();
        return $user && $user->hasAnyRole($roles);
    }

    /**
     * Validate required role and return error if not authorized
     */
    protected function requireRole(string $role): ?JsonResponse
    {
        if (!$this->hasRole($role)) {
            return $this->forbiddenResponse("Access denied. Required role: {$role}");
        }
        return null;
    }

    /**
     * Validate any required roles and return error if not authorized
     */
    protected function requireAnyRole(array $roles): ?JsonResponse
    {
        if (!$this->hasAnyRole($roles)) {
            $rolesList = implode(', ', $roles);
            return $this->forbiddenResponse("Access denied. Required roles: {$rolesList}");
        }
        return null;
    }

    /**
     * Handle rate limit exceeded
     */
    protected function rateLimitExceededResponse(): JsonResponse
    {
        return $this->errorResponse(
            'Rate limit exceeded. Please try again later.',
            429,
            null,
            'RATE_LIMIT_EXCEEDED'
        );
    }

    /**
     * Handle device not registered
     */
    protected function deviceNotRegisteredResponse(): JsonResponse
    {
        return $this->errorResponse(
            'Device not registered for this user',
            403,
            null,
            'DEVICE_NOT_REGISTERED'
        );
    }

    /**
     * Handle GPS spoofing detection
     */
    protected function gpsSpoofingDetectedResponse(): JsonResponse
    {
        return $this->errorResponse(
            'GPS manipulation detected',
            403,
            null,
            'GPS_SPOOFING_DETECTED'
        );
    }
}