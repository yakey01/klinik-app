<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('role')->latest()->paginate(10);
        return view('settings.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::active()->get();
        return view('settings.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'role_id' => 'required|exists:roles,id',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'nip' => 'nullable|string|max:50',
            'no_telepon' => 'nullable|string|max:20',
            'tanggal_bergabung' => 'required|date',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => Hash::make($request->password),
            'nip' => $request->nip,
            'no_telepon' => $request->no_telepon,
            'tanggal_bergabung' => $request->tanggal_bergabung,
            'is_active' => true,
        ]);

        return redirect()->route('settings.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $roles = Role::active()->get();
        return view('settings.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'nip' => 'nullable|string|max:50',
            'no_telepon' => 'nullable|string|max:20',
            'tanggal_bergabung' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'nip' => $request->nip,
            'no_telepon' => $request->no_telepon,
            'tanggal_bergabung' => $request->tanggal_bergabung,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('settings.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['success' => true, 'message' => 'Password berhasil direset.']);
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        
        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('settings.users.index')->with('success', "User berhasil {$status}.");
    }
}
