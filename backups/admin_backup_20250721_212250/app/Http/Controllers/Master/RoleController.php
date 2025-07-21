<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::active()->get();
        return response()->json($roles);
    }

    public function show(Role $role)
    {
        return response()->json($role);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'permissions' => 'array',
        ]);

        $role = Role::create($validated);
        return response()->json($role, 201);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'is_active' => 'boolean',
        ]);

        $role->update($validated);
        return response()->json($role);
    }

    public function destroy(Role $role)
    {
        $role->update(['is_active' => false]);
        return response()->json(['message' => 'Role deactivated successfully']);
    }
}