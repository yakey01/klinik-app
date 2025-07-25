<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class UserConstraintHandler
{
    /**
     * Handle database constraint violations for user creation/update
     */
    public static function handleConstraintViolation(QueryException $e, Request $request, $isUpdate = false, $userId = null)
    {
        $errorMessage = $e->getMessage();
        
        // Check for NIP constraint violation
        if (str_contains($errorMessage, 'UNIQUE constraint failed: users.nip')) {
            $nip = $request->input('nip');
            $existingUser = User::where('nip', $nip)
                ->when($userId, function($query) use ($userId) {
                    return $query->where('id', '!=', $userId);
                })
                ->first();
                
            if ($existingUser) {
                throw ValidationException::withMessages([
                    'nip' => "NIP '{$nip}' sudah digunakan oleh user '{$existingUser->name}' (Username: {$existingUser->username}). Silakan gunakan NIP yang berbeda."
                ]);
            }
        }
        
        // Check for username constraint violation
        if (str_contains($errorMessage, 'UNIQUE constraint failed: users.username')) {
            $username = $request->input('username');
            $existingUser = User::where('username', $username)
                ->when($userId, function($query) use ($userId) {
                    return $query->where('id', '!=', $userId);
                })
                ->first();
                
            if ($existingUser) {
                throw ValidationException::withMessages([
                    'username' => "Username '{$username}' sudah digunakan oleh user '{$existingUser->name}'. Silakan gunakan username yang berbeda."
                ]);
            }
        }
        
        // Check for email constraint violation
        if (str_contains($errorMessage, 'UNIQUE constraint failed: users.email')) {
            $email = $request->input('email');
            $existingUser = User::where('email', $email)
                ->when($userId, function($query) use ($userId) {
                    return $query->where('id', '!=', $userId);
                })
                ->first();
                
            if ($existingUser) {
                throw ValidationException::withMessages([
                    'email' => "Email '{$email}' sudah digunakan oleh user '{$existingUser->name}'. Silakan gunakan email yang berbeda."
                ]);
            }
        }
        
        // Generic constraint violation message
        throw ValidationException::withMessages([
            'general' => 'Terjadi konflik data. Pastikan semua field unik (NIP, username, email) tidak bentrok dengan data yang sudah ada.'
        ]);
    }
    
    /**
     * Pre-validate user data to prevent constraint violations
     */
    public static function preValidateUserData(Request $request, $userId = null)
    {
        $errors = [];
        
        // Check NIP
        if ($request->filled('nip')) {
            $existingUser = User::where('nip', $request->input('nip'))
                ->when($userId, function($query) use ($userId) {
                    return $query->where('id', '!=', $userId);
                })
                ->first();
                
            if ($existingUser) {
                $errors['nip'] = "NIP '{$request->input('nip')}' sudah digunakan oleh user '{$existingUser->name}' (Username: {$existingUser->username}). Silakan gunakan NIP yang berbeda.";
            }
        }
        
        // Check Username
        if ($request->filled('username')) {
            $existingUser = User::where('username', $request->input('username'))
                ->when($userId, function($query) use ($userId) {
                    return $query->where('id', '!=', $userId);
                })
                ->first();
                
            if ($existingUser) {
                $errors['username'] = "Username '{$request->input('username')}' sudah digunakan oleh user '{$existingUser->name}'. Silakan gunakan username yang berbeda.";
            }
        }
        
        // Check Email
        if ($request->filled('email')) {
            $existingUser = User::where('email', $request->input('email'))
                ->when($userId, function($query) use ($userId) {
                    return $query->where('id', '!=', $userId);
                })
                ->first();
                
            if ($existingUser) {
                $errors['email'] = "Email '{$request->input('email')}' sudah digunakan oleh user '{$existingUser->name}'. Silakan gunakan email yang berbeda.";
            }
        }
        
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}