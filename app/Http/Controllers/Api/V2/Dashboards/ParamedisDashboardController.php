<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Paramedis Dashboard",
 *     description="Dashboard endpoints for Paramedis role"
 * )
 */
class ParamedisDashboardController extends BaseDashboardController
{
    /**
     * @OA\Get(
     *     path="/api/v2/dashboards/paramedis",
     *     summary="Get Paramedis dashboard data",
     *     tags={"Paramedis Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Paramedis dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dashboard data retrieved"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user_info",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Dr. John Doe"),
     *                     @OA\Property(property="role", type="string", example="paramedis"),
     *                     @OA\Property(property="specialty", type="string", example="Perawat Umum")
     *                 ),
     *                 @OA\Property(
     *                     property="attendance",
     *                     type="object",
     *                     @OA\Property(property="this_month", type="integer", example=22),
     *                     @OA\Property(property="present_days", type="integer", example=20),
     *                     @OA\Property(property="late_days", type="integer", example=2),
     *                     @OA\Property(property="attendance_rate", type="number", example=90.9)
     *                 ),
     *                 @OA\Property(
     *                     property="jaspel",
     *                     type="object",
     *                     @OA\Property(property="this_month", type="integer", example=12500000),
     *                     @OA\Property(property="pending", type="integer", example=2400000),
     *                     @OA\Property(property="approved", type="integer", example=10100000)
     *                 ),
     *                 @OA\Property(
     *                     property="today_attendance",
     *                     type="object",
     *                     nullable=true
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied - not a paramedis")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // Validate user role
        if ($roleCheck = $this->requireRole('paramedis')) {
            return $roleCheck;
        }

        $user = $this->getAuthenticatedUser();
        
        // Get dashboard data
        $attendanceStats = $this->getAttendanceStats($user->id);
        $todayAttendance = $this->getTodayAttendance($user->id);
        $jaspelStats = $this->getJaspelStats($user->id);

        return $this->successResponse([
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role?->name,
                'specialty' => $user->specialty ?? 'Perawat Umum',
                'avatar' => $user->avatar_url,
            ],
            'attendance' => $attendanceStats,
            'jaspel' => $jaspelStats,
            'today_attendance' => $todayAttendance,
            'quick_actions' => $this->getQuickActions('paramedis'),
            'recent_activities' => $this->getRecentActivities($user->id),
        ], 'Dashboard data retrieved');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/dashboards/paramedis/schedule",
     *     summary="Get Paramedis schedule",
     *     tags={"Paramedis Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Schedule data retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function schedule(Request $request): JsonResponse
    {
        if ($roleCheck = $this->requireRole('paramedis')) {
            return $roleCheck;
        }

        $user = $this->getAuthenticatedUser();

        // TODO: Implement actual schedule logic
        $mockSchedule = [
            'today' => [
                'shift' => 'Morning Shift',
                'start_time' => '08:00',
                'end_time' => '16:00',
                'location' => 'Klinik Utama',
                'patients_assigned' => 12,
            ],
            'this_week' => [
                ['date' => '2025-07-15', 'shift' => 'Morning', 'status' => 'scheduled'],
                ['date' => '2025-07-16', 'shift' => 'Morning', 'status' => 'scheduled'],
                ['date' => '2025-07-17', 'shift' => 'Evening', 'status' => 'scheduled'],
                ['date' => '2025-07-18', 'shift' => 'Off', 'status' => 'off'],
                ['date' => '2025-07-19', 'shift' => 'Morning', 'status' => 'scheduled'],
            ],
            'upcoming_changes' => [
                [
                    'date' => '2025-07-20',
                    'change' => 'Shift change from Morning to Evening',
                    'requested_by' => 'Manager',
                    'status' => 'pending_approval',
                ],
            ],
        ];

        return $this->successResponse($mockSchedule, 'Schedule data retrieved');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/dashboards/paramedis/performance",
     *     summary="Get Paramedis performance metrics",
     *     tags={"Paramedis Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Performance metrics retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function performance(Request $request): JsonResponse
    {
        if ($roleCheck = $this->requireRole('paramedis')) {
            return $roleCheck;
        }

        $user = $this->getAuthenticatedUser();
        
        $attendanceStats = $this->getAttendanceStats($user->id);
        
        // TODO: Implement actual performance metrics
        $performanceMetrics = [
            'attendance_score' => $attendanceStats['attendance_rate'],
            'punctuality_score' => rand(85, 98),
            'patient_satisfaction' => rand(90, 100),
            'monthly_ranking' => rand(1, 20),
            'total_paramedis' => 45,
            'badges_earned' => [
                ['name' => 'Perfect Attendance', 'date' => '2025-07-01'],
                ['name' => 'Patient Care Excellence', 'date' => '2025-06-15'],
            ],
            'improvement_areas' => [
                'Time management during busy hours',
                'Documentation completeness',
            ],
        ];

        return $this->successResponse($performanceMetrics, 'Performance metrics retrieved');
    }

    /**
     * Get recent activities for paramedis
     */
    private function getRecentActivities(int $userId): array
    {
        // TODO: Implement actual recent activities logic
        return [
            [
                'type' => 'attendance',
                'message' => 'Check-in berhasil',
                'time' => '08:15:30',
                'date' => now()->format('Y-m-d'),
            ],
            [
                'type' => 'jaspel',
                'message' => 'Jaspel bulan Juni telah disetujui',
                'time' => '14:30:00',
                'date' => now()->subDay()->format('Y-m-d'),
            ],
            [
                'type' => 'schedule',
                'message' => 'Jadwal shift minggu depan telah diperbarui',
                'time' => '10:00:00',
                'date' => now()->subDays(2)->format('Y-m-d'),
            ],
        ];
    }
}