<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use App\Rules\ConsistentRoleAssignmentRule;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    /**
     * Handle automatic role assignment based on source module with validation
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // DEBUG: Log form data yang diterima dengan detail lebih lengkap
        \Log::info('CreateUser: Form data received', [
            'data_keys' => array_keys($data),
            'username' => $data['username'] ?? 'NOT_SET',
            'name' => $data['name'] ?? 'NOT_SET',
            'email' => $data['email'] ?? 'NOT_SET',
            'email_type' => gettype($data['email'] ?? null),
            'email_empty_check' => empty($data['email']),
            'email_trim_check' => isset($data['email']) ? trim($data['email']) === '' : 'EMAIL_NOT_SET',
            'all_request_data' => request()->all(),
            'full_data' => $data
        ]);
        
        $source = $data['source'] ?? request()->get('source');
        $employeeType = $data['employee_type'] ?? null;
        $relatedRecordId = request()->get('related_record_id');
        
        // Email is now optional - user can be created with username only
        // Handle ConvertEmptyStringsToNull middleware effect
        if (isset($data['email']) && !is_null($data['email']) && !empty($data['email'])) {
            // Ensure email is trimmed if provided
            $data['email'] = trim($data['email']);
            
            // Validate email format if provided
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'email' => 'Format email tidak valid. Masukkan alamat email yang benar.'
                ]);
            }
        } else {
            // Email is empty/null - set to null for proper database handling
            $data['email'] = null;
        }
        
        // Validate role consistency if role_id is already set and source is provided
        if (!empty($data['role_id']) && !empty($source)) {
            $validator = new ConsistentRoleAssignmentRule($source, $employeeType, $relatedRecordId);
            
            try {
                $validator->validate('role_id', $data['role_id'], function($message) {
                    throw ValidationException::withMessages(['role_id' => $message]);
                });
            } catch (ValidationException $e) {
                // Re-throw with proper format for Filament
                throw $e;
            }
        }
        
        // Auto-assign role based on source if not already set
        if (empty($data['role_id'])) {
            $data['role_id'] = $this->getAutoAssignedRoleId($source, $employeeType);
            
            // Only require role if we can't auto-assign and no role was manually selected
            if (empty($data['role_id'])) {
                throw ValidationException::withMessages([
                    'role_id' => 'Silakan pilih role untuk user ini.'
                ]);
            }
        }
        
        // Final validation after role assignment (only if source is provided)
        if (!empty($source)) {
            $finalValidator = new ConsistentRoleAssignmentRule($source, $employeeType, $relatedRecordId);
            $finalValidator->validate('role_id', $data['role_id'], function($message) {
                throw ValidationException::withMessages(['role_id' => $message]);
            });
        }
        
        // Remove temporary fields before saving
        unset($data['source'], $data['employee_type']);
        
        // DEBUG: Log final data yang akan disimpan
        \Log::info('CreateUser: Final data to save', [
            'data_keys' => array_keys($data),
            'username' => $data['username'] ?? 'NOT_SET',
            'name' => $data['name'] ?? 'NOT_SET',
            'email' => $data['email'] ?? 'NOT_SET',
            'has_password' => isset($data['password']) ? 'YES' : 'NO',
            'final_data' => $data
        ]);
        
        return $data;
    }
    
    /**
     * Override the create method to add more debugging and protection
     */
    protected function handleRecordCreation(array $data): Model
    {
        // DEBUG: Log final data before actual creation
        \Log::info('CreateUser: Final data before Eloquent create', [
            'data_keys' => array_keys($data),
            'email_value' => $data['email'] ?? 'NOT_SET',
            'email_type' => gettype($data['email'] ?? null),
            'email_var_dump' => var_export($data['email'] ?? null, true),
            'name_value' => $data['name'] ?? 'NOT_SET',
            'username_value' => $data['username'] ?? 'NOT_SET',
            'complete_data' => $data
        ]);
        
        // Email is now optional - log for debugging but allow null values
        \Log::info('CreateUser: Email validation', [
            'email_isset' => isset($data['email']),
            'email_value' => $data['email'] ?? 'NULL',
            'email_is_null' => is_null($data['email'] ?? 'UNSET'),
            'username' => $data['username'] ?? 'NO_USERNAME'
        ]);
        
        // Ensure email is properly trimmed if provided, otherwise keep as null
        if (isset($data['email']) && !is_null($data['email']) && $data['email'] !== '') {
            $data['email'] = trim($data['email']);
        }
        
        return static::getModel()::create($data);
    }
    
    /**
     * Auto-assign role based on source and employee type
     */
    private function getAutoAssignedRoleId(?string $source, ?string $employeeType): ?int
    {
        // Return null if no source is provided (manual role selection)
        if (empty($source)) {
            return null;
        }
        
        if ($source === 'dokter') {
            return Role::where('name', 'dokter')->first()?->id;
        }
        
        if ($source === 'pegawai') {
            if ($employeeType === 'paramedis') {
                return Role::where('name', 'paramedis')->first()?->id;
            } else {
                // Default non-paramedis to petugas role
                return Role::where('name', 'petugas')->first()?->id;
            }
        }
        
        return null;
    }
    
    /**
     * Redirect based on the source after creation
     */
    protected function getRedirectUrl(): string
    {
        $source = request()->get('source');
        
        return match($source) {
            'dokter' => $this->getResource()::getUrl('index') . '?source=dokter&created=1',
            'pegawai' => $this->getResource()::getUrl('index') . '?source=pegawai&created=1',
            'staff_management' => '/admin/pegawais?user_created=1',
            default => $this->getResource()::getUrl('index')
        };
    }
    
    /**
     * Get the page title based on source
     */
    public function getTitle(): string
    {
        $source = request()->get('source');
        
        return match($source) {
            'dokter' => 'Buat Akun Dokter',
            'pegawai' => 'Buat Akun Pegawai',
            'staff_management' => 'Buat Akun User (Petugas/Bendahara/Pegawai)',
            default => 'Buat User Baru'
        };
    }
    
    /**
     * Show success notification with role information
     */
    protected function getCreatedNotification(): ?\Filament\Notifications\Notification
    {
        $source = request()->get('source');
        $user = $this->getRecord();
        
        // DEBUG: Log user yang berhasil dibuat
        \Log::info('CreateUser: User created successfully', [
            'user_id' => $user->id,
            'username' => $user->username ?: 'EMPTY',
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name,
            'is_active' => $user->is_active
        ]);
        
        $message = match($source) {
            'dokter' => "Akun dokter '{$user->name}' berhasil dibuat dengan role 'Dokter'",
            'pegawai' => "Akun pegawai '{$user->name}' berhasil dibuat dengan role '{$user->role->display_name}'",
            'staff_management' => "Akun user '{$user->name}' berhasil dibuat dengan role '{$user->role->display_name}' untuk manajemen pegawai",
            default => "User '{$user->name}' berhasil dibuat"
        };
        
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title('Akun Berhasil Dibuat')
            ->body($message);
    }
}
