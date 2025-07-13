@extends('layouts.settings')

@section('title', 'Edit User')

@section('content')
<div class="bg-gray-800 rounded-lg shadow">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-700">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-semibold text-gray-100">Edit User: {{ $user->name }}</h1>
            <a href="{{ route('settings.users.index') }}" 
               class="text-gray-400 hover:text-gray-300">
                ← Kembali
            </a>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('settings.users.update', $user) }}" class="p-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300">Nama Lengkap *</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $user->name) }}"
                           required
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-300">Username *</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="{{ old('username', $user->username) }}"
                           required
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 @error('username') border-red-500 @enderror">
                    @error('username')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300">Email *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="{{ old('email', $user->email) }}"
                           required
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="role_id" class="block text-sm font-medium text-gray-300">Role *</label>
                    <select id="role_id" 
                            name="role_id" 
                            required
                            class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 @error('role_id') border-red-500 @enderror">
                        <option value="">Pilih Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" 
                                {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                {{ $role->display_name ?? $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <div>
                    <label for="nip" class="block text-sm font-medium text-gray-300">NIP</label>
                    <input type="text" 
                           id="nip" 
                           name="nip" 
                           value="{{ old('nip', $user->nip) }}"
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 @error('nip') border-red-500 @enderror">
                    @error('nip')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="no_telepon" class="block text-sm font-medium text-gray-300">No. Telepon</label>
                    <input type="text" 
                           id="no_telepon" 
                           name="no_telepon" 
                           value="{{ old('no_telepon', $user->no_telepon) }}"
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 @error('no_telepon') border-red-500 @enderror">
                    @error('no_telepon')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal_bergabung" class="block text-sm font-medium text-gray-300">Tanggal Bergabung *</label>
                    <input type="date" 
                           id="tanggal_bergabung" 
                           name="tanggal_bergabung" 
                           value="{{ old('tanggal_bergabung', $user->tanggal_bergabung?->format('Y-m-d')) }}"
                           required
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 @error('tanggal_bergabung') border-red-500 @enderror">
                    @error('tanggal_bergabung')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                               class="rounded bg-gray-700 border-gray-600 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-300">User Aktif</span>
                    </label>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- User Info -->
        <div class="mt-6 pt-6 border-t border-gray-700">
            <h3 class="text-lg font-medium text-gray-100 mb-4">Informasi User</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-400">Dibuat:</span>
                    <span class="text-gray-100">{{ $user->created_at->format('d M Y H:i') }}</span>
                </div>
                <div>
                    <span class="text-gray-400">Terakhir Update:</span>
                    <span class="text-gray-100">{{ $user->updated_at->format('d M Y H:i') }}</span>
                </div>
                <div>
                    <span class="text-gray-400">Status:</span>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                        {{ $user->is_active ? 'bg-green-800 text-green-100' : 'bg-red-800 text-red-100' }}">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="mt-6 pt-6 border-t border-gray-700 flex justify-between">
            <div>
                <button type="button" 
                        onclick="showResetPasswordModal({{ $user->id }}, '{{ $user->name }}')"
                        class="px-4 py-2 text-sm font-medium text-yellow-300 bg-yellow-800 rounded-md hover:bg-yellow-700">
                    Reset Password
                </button>
            </div>
            
            <div class="space-x-3">
                <a href="{{ route('settings.users.index') }}" 
                   class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-700">
                    Batal
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Update User
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
            <form id="resetPasswordForm" method="POST">
                @csrf
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-gray-100">Reset Password</h3>
                    <p class="text-sm text-gray-400 mt-1">Reset password untuk: <span id="userName"></span></p>
                </div>
                
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300">Password Baru</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Min. 8 karakter, harus ada huruf besar dan angka</p>
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-300">Konfirmasi Password</label>
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               required
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-700 flex justify-end space-x-3">
                    <button type="button" 
                            onclick="hideResetPasswordModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-700">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showResetPasswordModal(userId, userName) {
    document.getElementById('userName').textContent = userName;
    document.getElementById('resetPasswordForm').action = `/settings/users/${userId}/reset-password`;
    document.getElementById('resetPasswordModal').classList.remove('hidden');
}

function hideResetPasswordModal() {
    document.getElementById('resetPasswordModal').classList.add('hidden');
    document.getElementById('resetPasswordForm').reset();
}

// Handle form submission with AJAX
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const actionUrl = this.action;
    
    fetch(actionUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideResetPasswordModal();
            alert('Password berhasil direset!');
        } else {
            alert('Terjadi kesalahan: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
});
</script>
@endsection