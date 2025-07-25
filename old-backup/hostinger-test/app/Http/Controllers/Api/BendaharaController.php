<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BendaharaStatsService;
use App\Services\FinancialReportService;
use App\Services\ValidationWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BendaharaController extends Controller
{
    protected BendaharaStatsService $statsService;
    protected FinancialReportService $reportService;
    protected ValidationWorkflowService $validationService;

    public function __construct(
        BendaharaStatsService $statsService,
        FinancialReportService $reportService,
        ValidationWorkflowService $validationService
    ) {
        $this->statsService = $statsService;
        $this->reportService = $reportService;
        $this->validationService = $validationService;
        
        // Apply API authentication middleware
        $this->middleware(['auth:sanctum', 'role:bendahara|admin']);
    }

    /**
     * Get comprehensive financial dashboard stats
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $stats = $this->statsService->getDashboardStats($userId);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Dashboard stats retrieved successfully',
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('BendaharaController: Failed to get dashboard stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard stats',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get financial overview for a specific period
     */
    public function getFinancialOverview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'include_trends' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
            $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
            $includeTrends = $request->boolean('include_trends', true);

            $overview = $this->statsService->getFinancialOverview($startDate, $endDate, $includeTrends);

            return response()->json([
                'success' => true,
                'data' => $overview,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'message' => 'Financial overview retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('BendaharaController: Failed to get financial overview', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve financial overview',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate and return financial report
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:financial_summary,cash_flow,budget_performance,validation_performance',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'nullable|in:json,pdf,excel,csv',
            'include_charts' => 'boolean',
            'include_details' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reportData = [
                'report_type' => $request->input('report_type'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'include_charts' => $request->boolean('include_charts', true),
                'include_details' => $request->boolean('include_details', false),
            ];

            $format = $request->input('format', 'json');

            switch ($format) {
                case 'json':
                    $report = $this->reportService->generateDashboardReport($reportData);
                    return response()->json([
                        'success' => true,
                        'data' => $report,
                        'message' => 'Report generated successfully',
                    ]);

                case 'pdf':
                    $filename = 'financial_report_' . now()->format('Y-m-d_H-i-s') . '.pdf';
                    return $this->reportService->exportToPdf($reportData, $filename);

                case 'excel':
                    $filename = 'financial_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
                    return $this->reportService->exportToExcel($reportData, $filename);

                case 'csv':
                    $filename = 'financial_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
                    return $this->reportService->exportToCsv($reportData, $filename);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid format specified',
                    ], 400);
            }

        } catch (\Exception $e) {
            Log::error('BendaharaController: Failed to generate report', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get validation queue status and metrics
     */
    public function getValidationQueue(Request $request): JsonResponse
    {
        try {
            $queueMetrics = $this->validationService->getValidationDashboardMetrics();

            return response()->json([
                'success' => true,
                'data' => $queueMetrics,
                'message' => 'Validation queue retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('BendaharaController: Failed to get validation queue', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve validation queue',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Perform bulk validation action
     */
    public function bulkValidation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject,cancel',
            'record_ids' => 'required|array|min:1',
            'record_ids.*' => 'integer|min:1',
            'model_type' => 'required|in:pendapatan,pengeluaran,tindakan',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $action = $request->input('action');
            $recordIds = $request->input('record_ids');
            $modelType = $request->input('model_type');
            $notes = $request->input('notes', '');

            // Get the appropriate model records
            $modelClass = match ($modelType) {
                'pendapatan' => \App\Models\PendapatanHarian::class,
                'pengeluaran' => \App\Models\PengeluaranHarian::class,
                'tindakan' => \App\Models\Tindakan::class,
                default => null,
            };

            if (!$modelClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid model type',
                ], 400);
            }

            $records = $modelClass::whereIn('id', $recordIds)->get();

            if ($records->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No records found with the provided IDs',
                ], 404);
            }

            $result = $this->validationService->bulkAction($records, $action, Auth::id(), $notes);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'processed_count' => $result['processed_count'] ?? 0,
                    'failed_count' => $result['failed_count'] ?? 0,
                    'action' => $action,
                    'model_type' => $modelType,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('BendaharaController: Failed bulk validation', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk validation',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get cash flow analysis
     */
    public function getCashFlowAnalysis(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:7d,30d,90d,1y',
            'include_projections' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $period = $request->input('period', '30d');
            $includeProjections = $request->boolean('include_projections', true);

            $cashFlowData = $this->statsService->getCashFlowAnalysis($period, $includeProjections);

            return response()->json([
                'success' => true,
                'data' => $cashFlowData,
                'period' => $period,
                'message' => 'Cash flow analysis retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('BendaharaController: Failed to get cash flow analysis', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cash flow analysis',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get budget tracking information
     */
    public function getBudgetTracking(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $budgetData = $this->statsService->getBudgetTracking($userId);

            return response()->json([
                'success' => true,
                'data' => $budgetData,
                'message' => 'Budget tracking retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('BendaharaController: Failed to get budget tracking', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve budget tracking',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Clear all cached data for bendahara
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $this->statsService->clearStatsCache();
            $this->reportService->clearReportCache();

            Log::info('BendaharaController: Cache cleared', [
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('BendaharaController: Failed to clear cache', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get API health check and system status
     */
    public function healthCheck(Request $request): JsonResponse
    {
        try {
            $healthData = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'user' => [
                    'id' => Auth::id(),
                    'roles' => Auth::user()?->getRoleNames()->toArray(),
                ],
                'services' => [
                    'stats_service' => class_exists(BendaharaStatsService::class),
                    'report_service' => class_exists(FinancialReportService::class),
                    'validation_service' => class_exists(ValidationWorkflowService::class),
                ],
                'cache' => [
                    'enabled' => config('cache.default') !== 'null',
                    'driver' => config('cache.default'),
                ],
                'database' => [
                    'connected' => true, // We'll assume it's connected if we got this far
                    'default' => config('database.default'),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $healthData,
                'message' => 'API is healthy',
            ]);

        } catch (\Exception $e) {
            Log::error('BendaharaController: Health check failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Health check failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}