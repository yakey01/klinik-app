<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaceRecognition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;

class FaceRecognitionController extends Controller
{
    /**
     * Register face for user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'face_image' => 'required|image|max:2048',
                'algorithm' => 'nullable|string|in:dlib,opencv,facenet,arcface',
            ]);

            $user = $request->user();

            // Check if user already has face recognition
            $existingFace = FaceRecognition::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if ($existingFace) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has active face recognition'
                ], 400);
            }

            // Store face image
            $imagePath = $request->file('face_image')->store('face-recognition', 'public');
            
            // Generate face encoding (placeholder - replace with actual face recognition service)
            $faceData = FaceRecognition::generateEncoding($imagePath);
            
            // Create face recognition record
            $faceRecognition = FaceRecognition::create([
                'user_id' => $user->id,
                'face_image_path' => $imagePath,
                'face_encoding' => $faceData['encoding'],
                'face_landmarks' => $faceData['landmarks'],
                'confidence_score' => $faceData['confidence'],
                'encoding_algorithm' => $request->algorithm ?? 'dlib',
                'is_active' => true,
                'is_verified' => false,
                'metadata' => [
                    'uploaded_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Face registration successful',
                'data' => [
                    'face_id' => $faceRecognition->id,
                    'confidence_score' => $faceRecognition->confidence_score,
                    'requires_verification' => !$faceRecognition->is_verified
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Face registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Verify face for attendance
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'face_image' => 'required|image|max:2048',
                'threshold' => 'nullable|numeric|min:0|max:1',
            ]);

            $user = $request->user();
            $threshold = $request->threshold ?? 0.7;

            // Get user's registered face
            $registeredFace = FaceRecognition::where('user_id', $user->id)
                ->where('is_active', true)
                ->where('is_verified', true)
                ->first();

            if (!$registeredFace) {
                return response()->json([
                    'success' => false,
                    'message' => 'No verified face recognition found for user'
                ], 404);
            }

            // Store verification image temporarily
            $verificationImagePath = $request->file('face_image')->store('face-verification', 'public');
            
            // Generate encoding for verification image
            $verificationData = FaceRecognition::generateEncoding($verificationImagePath);
            
            // Compare face encodings
            $isMatch = FaceRecognition::compareFaces(
                $registeredFace->face_encoding,
                $verificationData['encoding'],
                $threshold
            );

            $confidenceScore = $verificationData['confidence'];

            // Clean up temporary verification image
            \Storage::disk('public')->delete($verificationImagePath);

            return response()->json([
                'success' => true,
                'message' => 'Face verification completed',
                'data' => [
                    'is_match' => $isMatch,
                    'confidence_score' => $confidenceScore,
                    'threshold_used' => $threshold,
                    'registered_face_id' => $registeredFace->id,
                    'verification_result' => $isMatch ? 'VERIFIED' : 'NOT_VERIFIED'
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Face verification failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user's face recognition status
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $faceRecognition = FaceRecognition::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$faceRecognition) {
                return response()->json([
                    'success' => true,
                    'message' => 'No face recognition found',
                    'data' => [
                        'has_face_recognition' => false,
                        'is_verified' => false,
                        'can_use_face_attendance' => false
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Face recognition status',
                'data' => [
                    'has_face_recognition' => true,
                    'is_verified' => $faceRecognition->is_verified,
                    'can_use_face_attendance' => $faceRecognition->is_verified && $faceRecognition->is_active,
                    'confidence_score' => $faceRecognition->confidence_score,
                    'algorithm' => $faceRecognition->encoding_algorithm,
                    'registered_at' => $faceRecognition->created_at,
                    'verified_at' => $faceRecognition->verified_at
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get face recognition status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update face recognition
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'face_image' => 'required|image|max:2048',
            ]);

            $user = $request->user();

            $faceRecognition = FaceRecognition::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$faceRecognition) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active face recognition found'
                ], 404);
            }

            // Delete old image
            if ($faceRecognition->face_image_path) {
                \Storage::disk('public')->delete($faceRecognition->face_image_path);
            }

            // Store new image
            $imagePath = $request->file('face_image')->store('face-recognition', 'public');
            
            // Generate new encoding
            $faceData = FaceRecognition::generateEncoding($imagePath);
            
            // Update face recognition
            $faceRecognition->update([
                'face_image_path' => $imagePath,
                'face_encoding' => $faceData['encoding'],
                'face_landmarks' => $faceData['landmarks'],
                'confidence_score' => $faceData['confidence'],
                'is_verified' => false, // Require re-verification
                'verified_at' => null,
                'verified_by' => null,
                'metadata' => array_merge($faceRecognition->metadata ?? [], [
                    'updated_at' => now(),
                    'ip_address' => $request->ip()
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Face recognition updated successfully',
                'data' => [
                    'face_id' => $faceRecognition->id,
                    'confidence_score' => $faceRecognition->confidence_score,
                    'requires_verification' => true
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Face recognition update failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
