@extends('layouts.settings')

@section('title', 'Tambah User')

@section('content')
<div class="bg-gray-800 rounded-lg shadow">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-700">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-semibold text-gray-100">Tambah User Baru</h1>
            <a href="{{ route('settings.users.index') }}" 
               class="text-gray-400 hover:text-gray-300">
                ← Kembali
            </a>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('settings.users.store') }}" class="p-6">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300">Nama Lengkap *</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}"
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
                           value="{{ old('username') }}"
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
                           value="{{ old('email') }}"
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
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
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
                           value="{{ old('nip') }}"
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
                           value="{{ old('no_telepon') }}"
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
                           value="{{ old('tanggal_bergabung', date('Y-m-d')) }}"
                           required
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 @error('tanggal_bergabung') border-red-500 @enderror">
                    @error('tanggal_bergabung')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Password Section -->
        <div class="mt-6 pt-6 border-t border-gray-700">
            <h3 class="text-lg font-medium text-gray-100 mb-4">Pengaturan Password</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300">Password *</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                    <p class="text-xs text-gray-400 mt-1">Min. 8 karakter, harus ada huruf besar dan angka</p>
                    @error('password')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300">Konfirmasi Password *</label>
                    <input type="password" 
                           id="password_confirmation" 
                           name="password_confirmation" 
                           required
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="mt-6 pt-6 border-t border-gray-700 flex justify-end space-x-3">
            <a href="{{ route('settings.users.index') }}" 
               class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-700">
                Batal
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                Simpan User
            </button>
        </div>
    </form>
</div>
@endsection