<?php

namespace App\Models;

use App\Enums\TelegramNotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TelegramSetting extends Model
{
    protected $fillable = [
        'role',
        'user_id',
        'user_name',
        'role_type',
        'chat_id',
        'notification_types',
        'is_active',
    ];

    protected $casts = [
        'notification_types' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getValidationRules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(['petugas', 'bendahara', 'admin', 'manajer', 'dokter', 'paramedis', 'non_paramedis'])],
            'user_id' => ['nullable', 'exists:users,id'],
            'user_name' => ['nullable', 'string', 'max:255'],
            'role_type' => ['nullable', 'string', Rule::in(['general', 'specific_user'])],
            'chat_id' => ['required', 'numeric', 'digits_between:1,15', 'unique:telegram_settings,chat_id'],
            'notification_types' => ['nullable', 'array'],
            'notification_types.*' => [Rule::in(array_column(TelegramNotificationType::cases(), 'value'))],
            'is_active' => ['boolean'],
        ];
    }

    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        if ($ignoreId) {
            $rules['chat_id'] = ['required', 'numeric', 'digits_between:1,15', 'unique:telegram_settings,chat_id,'.$ignoreId];
        }

        return $rules;
    }

    public static function getAvailableNotificationTypes(): array
    {
        return TelegramNotificationType::getAllOptions();
    }

    public static function getRoleNotifications(string $role): array
    {
        $notifications = TelegramNotificationType::getForRole($role);
        $result = [];

        foreach ($notifications as $notification) {
            $result[$notification->value] = $notification->label();
        }

        return $result;
    }

    public function hasNotificationType(string $type): bool
    {
        return in_array($type, $this->notification_types ?? []);
    }

    public function getFormattedNotificationTypes(): array
    {
        $types = $this->notification_types ?? [];
        $formatted = [];

        foreach ($types as $type) {
            if ($enum = TelegramNotificationType::tryFrom($type)) {
                $formatted[] = $enum->label();
            }
        }

        return $formatted;
    }

    public static function checkChatIdDuplicate(string $chatId, ?int $ignoreId = null): bool
    {
        $query = self::where('chat_id', $chatId);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Get display name for the setting (role + user name if specific user)
     */
    public function getDisplayName(): string
    {
        if ($this->role_type === 'specific_user' && $this->user_name) {
            return ucfirst($this->role).' - '.$this->user_name;
        }

        return ucfirst($this->role);
    }

    /**
     * Get available users for a specific role
     */
    public static function getAvailableUsersForRole(string $role): array
    {
        if (! in_array($role, ['dokter', 'paramedis', 'non_paramedis'])) {
            return [];
        }

        $users = User::whereHas('role', function ($query) use ($role) {
            $query->where('name', $role);
        })->where('is_active', true)->get();

        $result = [];
        foreach ($users as $user) {
            $result[$user->id] = $user->name.' ('.($user->username ?? $user->email).')';
        }

        return $result;
    }

    /**
     * Check if a user already has a telegram setting
     */
    public static function userHasSetting(int $userId, string $role): bool
    {
        return self::where('user_id', $userId)
            ->where('role', $role)
            ->exists();
    }
}
