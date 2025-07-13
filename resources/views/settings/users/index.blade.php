@extends('layouts.settings')

@section('title', 'Manajemen User')

@section('content')
<div class="bg-gray-800 rounded-lg shadow">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-700">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-semibold text-gray-100">Manajemen User</h1>
            <a href="{{ route('settings.users.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Tambah User
            </a>
        </div>
    </div>

    <!-- Users Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                        User
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                        Username
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                        Role
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                        Bergabung
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
                @forelse($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gray-600 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-300">
                                            {{ substr($user->name, 0, 2) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-100">{{ $user->name }}</div>
                                    <div class="text-sm text-gray-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            {{ $user->username }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $user->role?->name === 'admin' ? 'bg-red-800 text-red-100' : 
                                   ($user->role?->name === 'dokter' ? 'bg-blue-800 text-blue-100' : 'bg-gray-700 text-gray-300') }}">
                                {{ $user->role?->display_name ?? $user->role?->name ?? 'No Role' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $user->is_active ? 'bg-green-800 text-green-100' : 'bg-red-800 text-red-100' }}">
                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            {{ $user->tanggal_bergabung ? $user->tanggal_bergabung->format('d M Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('settings.users.edit', $user) }}" 
                               class="text-blue-400 hover:text-blue-300">Edit</a>
                            
                            <button type="button" 
                                    onclick="showResetPasswordModal({{ $user->id }}, '{{ $user->name }}')"
                                    class="text-yellow-400 hover:text-yellow-300">
                                Reset Password
                            </button>
                            
                            <form method="POST" 
                                  action="{{ route('settings.users.toggle-status', $user) }}" 
                                  class="inline"
                                  onsubmit="return confirmAction('Yakin ingin {{ $user->is_active ? 'menonaktifkan' : 'mengaktifkan' }} user ini?')">
                                @csrf
                                <button type="submit" 
                                        class="{{ $user->is_active ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300' }}">
                                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-400">
                            Tidak ada data user
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-700">
            {{ $users->links() }}
        </div>
    @endif
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
            location.reload();
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