<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkLocation;
use App\Models\Pegawai;
use App\Models\AssignmentHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class SmartWorkLocationAssignmentService
{
    /**
     * World-class intelligent assignment algorithm
     */
    public function intelligentAssignment(User $user): array
    {
        Log::info("🧠 Smart assignment initiated for user: {$user->name}");
        
        // Get user profile data
        $pegawai = $user->pegawai;
        $currentRole = $user->role?->name;
        
        // Get all active work locations
        $workLocations = WorkLocation::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
        
        if ($workLocations->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active work locations available',
                'code' => 'NO_LOCATIONS'
            ];
        }
        
        // Smart scoring algorithm
        $scoredLocations = $workLocations->map(function ($location) use ($user, $pegawai, $currentRole) {
            $score = $this->calculateAssignmentScore($location, $user, $pegawai, $currentRole);
            return [
                'location' => $location,
                'score' => $score['total'],
                'reasons' => $score['reasons'],
                'confidence' => $score['confidence']
            ];
        });
        
        // Sort by highest score
        $bestMatch = $scoredLocations->sortByDesc('score')->first();
        
        if ($bestMatch['score'] > 0) {
            // Assign the best match
            $result = $this->assignLocationToUser($user, $bestMatch['location'], $bestMatch['reasons']);
            
            // Log the assignment
            $this->logAssignment($user, $bestMatch['location'], $bestMatch);
            
            return array_merge($result, [
                'assignment_score' => $bestMatch['score'],
                'confidence_level' => $bestMatch['confidence'],
                'match_reasons' => $bestMatch['reasons'],
                'algorithm' => 'Smart AI-like Assignment v1.0'
            ]);
        }
        
        return [
            'success' => false,
            'message' => 'No suitable location found based on user profile',
            'code' => 'NO_SUITABLE_MATCH',
            'available_locations' => $workLocations->count()
        ];
    }
    
    /**
     * Advanced scoring algorithm with multiple factors
     */
    private function calculateAssignmentScore(WorkLocation $location, User $user, $pegawai, string $currentRole): array
    {
        $score = 0;
        $reasons = [];
        $confidence = 'low';
        
        // Factor 1: Unit Kerja Match (Weight: 40%)
        if ($pegawai && $pegawai->unit_kerja && $location->unit_kerja) {
            if (strtolower($pegawai->unit_kerja) === strtolower($location->unit_kerja)) {
                $score += 40;
                $reasons[] = "Perfect unit kerja match: {$pegawai->unit_kerja}";
                $confidence = 'high';
            } elseif (stripos($location->unit_kerja, $pegawai->unit_kerja) !== false || 
                      stripos($pegawai->unit_kerja, $location->unit_kerja) !== false) {
                $score += 25;
                $reasons[] = "Partial unit kerja match: {$pegawai->unit_kerja} ↔ {$location->unit_kerja}";
                $confidence = 'medium';
            }
        }
        
        // Factor 2: Role-Location Type Match (Weight: 30%)
        $roleLocationMap = [
            'dokter' => ['main_office', 'branch_office'],
            'paramedis' => ['main_office', 'branch_office', 'mobile_location'],
            'non_paramedis' => ['main_office', 'branch_office'],
            'admin' => ['main_office'],
            'manajer' => ['main_office', 'branch_office'],
            'bendahara' => ['main_office'],
            'petugas' => ['main_office', 'branch_office']
        ];
        
        if (isset($roleLocationMap[$currentRole]) && 
            in_array($location->location_type, $roleLocationMap[$currentRole])) {
            $score += 30;
            $reasons[] = "Role-location compatibility: {$currentRole} → {$location->location_type}";
        }
        
        // Factor 3: Location Capacity and Activity (Weight: 20%)
        $currentAssignments = User::where('work_location_id', $location->id)->count();
        $optimalCapacity = 50; // Configurable
        
        if ($currentAssignments < $optimalCapacity * 0.8) {
            $capacityScore = 20 - ($currentAssignments / $optimalCapacity * 20);
            $score += $capacityScore;
            $reasons[] = "Good capacity utilization: {$currentAssignments}/{$optimalCapacity} users";
        }
        
        // Factor 4: Location Features Match (Weight: 10%)
        if ($location->strict_geofence && in_array($currentRole, ['dokter', 'paramedis'])) {
            $score += 5;
            $reasons[] = "Strict geofencing suitable for medical staff";
        }
        
        if ($location->require_photo && in_array($currentRole, ['dokter', 'paramedis'])) {
            $score += 5;
            $reasons[] = "Photo verification suitable for medical staff";
        }
        
        // Confidence level calculation
        if ($score >= 70) $confidence = 'very_high';
        elseif ($score >= 50) $confidence = 'high';
        elseif ($score >= 30) $confidence = 'medium';
        
        return [
            'total' => $score,
            'reasons' => $reasons,
            'confidence' => $confidence
        ];
    }
    
    /**
     * Assign location to user with comprehensive logging
     */
    public function assignLocationToUser(User $user, WorkLocation $location, array $reasons = []): array
    {
        try {
            $previousLocationId = $user->work_location_id;
            
            // Update user
            $user->work_location_id = $location->id;
            $user->save();
            
            // Clear user caches
            $this->clearUserCaches($user);
            
            // Create assignment history
            AssignmentHistory::create([
                'user_id' => $user->id,
                'work_location_id' => $location->id,
                'previous_work_location_id' => $previousLocationId,
                'assigned_by' => auth()->id(),
                'assignment_method' => 'smart_algorithm',
                'assignment_reasons' => $reasons,
                'assignment_score' => array_sum(array_map(function($reason) {
                    return preg_match('/\d+/', $reason, $matches) ? (int)($matches[0] ?? 0) : 0;
                }, $reasons)),
                'metadata' => [
                    'user_role' => $user->role?->name,
                    'user_unit_kerja' => $user->pegawai?->unit_kerja,
                    'location_type' => $location->location_type,
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
            Log::info("✅ Smart assignment successful", [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'location_id' => $location->id,
                'location_name' => $location->name,
                'reasons' => $reasons
            ]);
            
            return [
                'success' => true,
                'message' => "Successfully assigned {$user->name} to {$location->name}",
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role?->name
                    ],
                    'location' => [
                        'id' => $location->id,
                        'name' => $location->name,
                        'address' => $location->address,
                        'type' => $location->location_type,
                        'unit_kerja' => $location->unit_kerja
                    ],
                    'assignment_date' => now()->toISOString()
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Assignment failed", [
                'user_id' => $user->id,
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Assignment failed: ' . $e->getMessage(),
                'code' => 'ASSIGNMENT_ERROR'
            ];
        }
    }
    
    /**
     * Bulk intelligent assignment for multiple users
     */
    public function bulkIntelligentAssignment(Collection $users): array
    {
        $results = [
            'total' => $users->count(),
            'successful' => 0,
            'failed' => 0,
            'assignments' => [],
            'errors' => []
        ];
        
        foreach ($users as $user) {
            $result = $this->intelligentAssignment($user);
            
            if ($result['success']) {
                $results['successful']++;
                $results['assignments'][] = $result;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'user' => $user->name,
                    'error' => $result['message']
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get assignment recommendations for a user
     */
    public function getAssignmentRecommendations(User $user): array
    {
        $workLocations = WorkLocation::where('is_active', true)->get();
        $pegawai = $user->pegawai;
        $currentRole = $user->role?->name;
        
        $recommendations = $workLocations->map(function ($location) use ($user, $pegawai, $currentRole) {
            $score = $this->calculateAssignmentScore($location, $user, $pegawai, $currentRole);
            return [
                'location' => $location,
                'score' => $score['total'],
                'reasons' => $score['reasons'],
                'confidence' => $score['confidence'],
                'recommendation_level' => $this->getRecommendationLevel($score['total'])
            ];
        })->sortByDesc('score');
        
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $currentRole,
                'unit_kerja' => $pegawai?->unit_kerja
            ],
            'recommendations' => $recommendations->values()->toArray(),
            'top_recommendation' => $recommendations->first(),
            'generated_at' => now()->toISOString()
        ];
    }
    
    /**
     * Get recommendation level based on score
     */
    private function getRecommendationLevel(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'very_good';
        if ($score >= 40) return 'good';
        if ($score >= 20) return 'fair';
        return 'poor';
    }
    
    /**
     * Clear user-related caches
     */
    private function clearUserCaches(User $user): void
    {
        $cacheKeys = [
            "user_work_location_{$user->id}",
            "paramedis_dashboard_stats_{$user->id}",
            "dokter_dashboard_stats_{$user->id}",
            "attendance_status_{$user->id}",
            "user_profile_{$user->id}"
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
    
    /**
     * Log assignment with detailed information
     */
    private function logAssignment(User $user, WorkLocation $location, array $assignmentData): void
    {
        Log::info("🎯 Smart Assignment Completed", [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role?->name,
                'unit_kerja' => $user->pegawai?->unit_kerja
            ],
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'type' => $location->location_type,
                'unit_kerja' => $location->unit_kerja
            ],
            'assignment' => [
                'score' => $assignmentData['score'],
                'confidence' => $assignmentData['confidence'],
                'reasons' => $assignmentData['reasons']
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Get assignment analytics
     */
    public function getAssignmentAnalytics(): array
    {
        $totalUsers = User::count();
        $usersWithLocation = User::whereNotNull('work_location_id')->count();
        $usersWithoutLocation = $totalUsers - $usersWithLocation;
        
        $locationDistribution = WorkLocation::withCount('users')
            ->where('is_active', true)
            ->get()
            ->map(function ($location) {
                return [
                    'location' => $location->name,
                    'users_count' => $location->users_count,
                    'utilization_percentage' => $location->users_count > 0 ? 
                        round(($location->users_count / 50) * 100, 1) : 0 // Assuming 50 is optimal
                ];
            });
        
        return [
            'overview' => [
                'total_users' => $totalUsers,
                'users_with_location' => $usersWithLocation,
                'users_without_location' => $usersWithoutLocation,
                'assignment_coverage' => round(($usersWithLocation / $totalUsers) * 100, 1)
            ],
            'location_distribution' => $locationDistribution,
            'generated_at' => now()->toISOString()
        ];
    }
}