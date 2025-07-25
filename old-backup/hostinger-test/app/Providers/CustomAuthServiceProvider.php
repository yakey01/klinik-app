<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Auth::provider('custom_eloquent', function ($app, array $config) {
            return new CustomEloquentUserProvider($app['hash'], $config['model']);
        });
    }
}

class CustomEloquentUserProvider extends EloquentUserProvider
{
    /**
     * Store for virtual users (pegawai logins)
     */
    protected static $virtualUsers = [];
    
    /**
     * Store virtual user for session persistence
     */
    public static function storeVirtualUser($id, Authenticatable $user): void
    {
        static::$virtualUsers[$id] = $user;
    }
    
    /**
     * Retrieve user by ID, including virtual users
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        // Check virtual users first
        if (isset(static::$virtualUsers[$identifier])) {
            return static::$virtualUsers[$identifier];
        }
        
        // Fall back to database lookup
        return parent::retrieveById($identifier);
    }
    
    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) || 
            (count($credentials) === 1 && str_contains($this->firstCredentialKey($credentials), 'password'))) {
            return null;
        }

        // Handle email or username login
        if (isset($credentials['email'])) {
            $identifier = $credentials['email'];
            
            // Try to find user by email first, then by username
            $query = $this->newModelQuery();
            $query->where('email', $identifier)->orWhere('username', $identifier);
            
            $user = $query->first();
            
            // Debug logging
            \Illuminate\Support\Facades\Log::info('Debug: CustomEloquentUserProvider retrieveByCredentials', [
                'identifier' => $identifier,
                'found_user' => $user ? 'YES' : 'NO',
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
                'user_username' => $user ? $user->username : null,
            ]);
            
            return $user;
        }

        // For other credential types, use the default behavior
        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if (str_contains($key, 'password')) {
                continue;
            }

            if (is_array($value) || $value instanceof \Arrayable) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    /**
     * Get the first key from the credential array.
     */
    protected function firstCredentialKey(array $credentials): string
    {
        foreach ($credentials as $key => $value) {
            return $key;
        }

        return '';
    }
}