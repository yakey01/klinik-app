<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'email_verified_at' => $this->email_verified_at,
            
            // Formatted fields
            'role_name' => $this->safeGet('role.name'),
            'is_verified' => !is_null($this->email_verified_at),
            
            // Relationships
            'role' => $this->whenLoaded('role'),
        ]);
    }

    /**
     * Transform for minimal view (public info only)
     */
    public function toArrayMinimal(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role_name' => $this->safeGet('role.name'),
        ];
    }
}