<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Dokter Dashboard",
 *     description="Dashboard endpoints for Dokter role"
 * )
 */
class DokterDashboardController extends BaseDashboardController
{
    /**
     * @OA\Get(
     *     path="/api/v2/dashboards/dokter",
     *     summary="Get Dokter dashboard data",
     *     tags={"Dokter Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dokter dashboard data retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied - not a dokter")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if ($roleCheck = $this->requireRole('dokter')) {
            return $roleCheck;
        }

        $user = $this->getAuthenticatedUser();
        
        $attendanceStats = $this->getAttendanceStats($user->id);
        $todayAttendance = $this->getTodayAttendance($user->id);
        $jaspelStats = $this->getJaspelStats($user->id);

        return $this->successResponse([
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role?->name,
                'specialty' => $user->specialty ?? 'Dokter Umum',
                'license_number' => $user->license_number ?? null,
                'avatar' => $user->avatar_url,
            ],
            'today_schedule' => $this->getTodaySchedule($user->id),
            'patient_summary' => $this->getPatientSummary($user->id),
            'jaspel' => $jaspelStats,
            'attendance' => $attendanceStats,
            'today_attendance' => $todayAttendance,
            'quick_actions' => $this->getQuickActions('dokter'),
            'recent_activities' => $this->getRecentActivities($user->id),
        ], 'Dokter dashboard data retrieved');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/dashboards/dokter/patients",
     *     summary="Get today's patient list for doctor",
     *     tags={"Dokter Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Patient list retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function patients(Request $request): JsonResponse
    {
        if ($roleCheck = $this->requireRole('dokter')) {
            return $roleCheck;
        }

        $user = $this->getAuthenticatedUser();

        // TODO: Implement actual patient data logic
        $mockPatients = [
            'scheduled_today' => [
                [
                    'id' => 1,
                    'name' => 'John Doe',
                    'age' => 35,
                    'appointment_time' => '09:00',
                    'status' => 'waiting',
                    'chief_complaint' => 'Demam dan batuk',
                    'visit_type' => 'consultation',
                ],
                [
                    'id' => 2,
                    'name' => 'Jane Smith',
                    'age' => 28,
                    'appointment_time' => '09:30',
                    'status' => 'in_progress',
                    'chief_complaint' => 'Kontrol rutin',
                    'visit_type' => 'follow_up',
                ],
                [
                    'id' => 3,
                    'name' => 'Bob Wilson',
                    'age' => 45,
                    'appointment_time' => '10:00',
                    'status' => 'scheduled',
                    'chief_complaint' => 'Nyeri dada',
                    'visit_type' => 'consultation',
                ],
            ],
            'completed_today' => 3,
            'remaining_today' => 9,
            'next_appointment' => [
                'time' => '10:00',
                'patient_name' => 'Bob Wilson',
                'estimated_duration' => '30 minutes',
            ],
        ];

        return $this->successResponse($mockPatients, 'Patient list retrieved');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/dashboards/dokter/procedures",
     *     summary="Get today's procedures for doctor",
     *     tags={"Dokter Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Procedures list retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function procedures(Request $request): JsonResponse
    {
        if ($roleCheck = $this->requireRole('dokter')) {
            return $roleCheck;
        }

        $user = $this->getAuthenticatedUser();

        // TODO: Implement actual procedure data logic
        $mockProcedures = [
            'scheduled_today' => [
                [
                    'id' => 1,
                    'patient_name' => 'Alice Brown',
                    'procedure' => 'Minor Surgery',
                    'scheduled_time' => '14:00',
                    'estimated_duration' => '60 minutes',
                    'status' => 'scheduled',
                    'preparation_notes' => 'Pasien puasa 8 jam',
                ],
                [
                    'id' => 2,
                    'patient_name' => 'Charlie Davis',
                    'procedure' => 'Wound Care',
                    'scheduled_time' => '15:30',
                    'estimated_duration' => '30 minutes',
                    'status' => 'scheduled',
                    'preparation_notes' => null,
                ],
            ],
            'completed_today' => 1,
            'total_procedures_month' => 45,
            'procedure_success_rate' => 98.5,
        ];

        return $this->successResponse($mockProcedures, 'Procedures list retrieved');
    }

    /**
     * Get today's schedule for doctor
     */
    private function getTodaySchedule(int $userId): array
    {
        // TODO: Implement actual schedule logic
        return [
            'shift' => 'Full Day',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'location' => 'Klinik Utama',
            'patients_scheduled' => 12,
            'procedures_scheduled' => 3,
            'break_time' => [
                'start' => '12:00',
                'end' => '13:00',
            ],
        ];
    }

    /**
     * Get patient summary for doctor
     */
    private function getPatientSummary(int $userId): array
    {
        // TODO: Implement actual patient summary logic
        return [
            'today' => [
                'total_scheduled' => 12,
                'completed' => 3,
                'in_progress' => 1,
                'waiting' => 8,
                'no_show' => 0,
            ],
            'this_month' => [
                'total_patients' => 245,
                'new_patients' => 67,
                'follow_up_patients' => 178,
                'satisfaction_rating' => 4.8,
            ],
            'patient_demographics' => [
                'age_groups' => [
                    '0-18' => 25,
                    '19-35' => 35,
                    '36-50' => 30,
                    '51+' => 10,
                ],
                'common_conditions' => [
                    'Respiratory infections' => 20,
                    'Diabetes management' => 15,
                    'Hypertension' => 12,
                    'Routine checkup' => 25,
                ],
            ],
        ];
    }

    /**
     * Get recent activities for doctor
     */
    private function getRecentActivities(int $userId): array
    {
        return [
            [
                'type' => 'patient',
                'message' => 'Menyelesaikan konsultasi dengan John Doe',
                'time' => '08:45:00',
                'date' => now()->format('Y-m-d'),
            ],
            [
                'type' => 'jaspel',
                'message' => 'Jaspel bulan Juni telah disetujui: Rp 15.500.000',
                'time' => '16:30:00',
                'date' => now()->subDay()->format('Y-m-d'),
            ],
            [
                'type' => 'procedure',
                'message' => 'Berhasil menyelesaikan minor surgery',
                'time' => '14:30:00',
                'date' => now()->subDay()->format('Y-m-d'),
            ],
            [
                'type' => 'schedule',
                'message' => 'Jadwal praktek minggu depan telah diperbarui',
                'time' => '10:00:00',
                'date' => now()->subDays(2)->format('Y-m-d'),
            ],
        ];
    }
}