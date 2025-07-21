@php
    $employees = $this->getTableRecords();
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 p-6">
    @foreach($employees as $employee)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            {{-- Card Header --}}
            <div class="relative bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 p-4 text-white">
                <div class="absolute top-2 right-2">
                    @if($employee->aktif)
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            Nonaktif
                        </span>
                    @endif
                </div>
                
                {{-- Photo Section --}}
                <div class="flex flex-col items-center">
                    <div class="relative">
                        @if($employee->foto)
                            <img src="{{ Storage::url($employee->foto) }}" 
                                 alt="{{ $employee->nama_lengkap }}" 
                                 class="w-20 h-20 rounded-full border-4 border-white shadow-lg object-cover">
                        @else
                            <div class="w-20 h-20 rounded-full border-4 border-white shadow-lg bg-gray-300 flex items-center justify-center">
                                <svg class="w-10 h-10 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </div>
                        @endif
                        
                        {{-- Department Badge --}}
                        <div class="absolute -bottom-2 -right-2">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                {{ $employee->jenis_pegawai === 'Paramedis' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $employee->jenis_pegawai === 'Paramedis' ? '⚕️' : '💼' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="p-4 space-y-3">
                {{-- Name & NIK --}}
                <div class="text-center">
                    <h3 class="font-bold text-lg text-gray-900 dark:text-white leading-tight">
                        {{ $employee->nama_lengkap }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                        NIK: {{ $employee->nik }}
                    </p>
                </div>

                {{-- Job Details --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Jabatan:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->jabatan }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Jenis:</span>
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                            {{ $employee->jenis_pegawai === 'Paramedis' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                            {{ $employee->jenis_pegawai }}
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Gender:</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ $employee->jenis_kelamin === 'Laki-laki' ? '👨 L' : '👩 P' }}
                        </span>
                    </div>

                    @if($employee->tanggal_lahir)
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Umur:</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($employee->tanggal_lahir)->age }} tahun
                        </span>
                    </div>
                    @endif
                </div>

                {{-- Card Status --}}
                @php
                    $hasCard = \App\Models\EmployeeCard::where('pegawai_id', $employee->id)->exists();
                    $fi-card = \App\Models\EmployeeCard::where('pegawai_id', $employee->id)->first();
                @endphp
                
                <div class="border-t pt-3">
                    @if($hasCard)
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Status Kartu:</span>
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                ✅ Ada Kartu
                            </span>
                        </div>
                        @if($fi-card)
                        <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
                            Kartu: {{ $fi-card->fi-card_number }}
                        </div>
                        @endif
                    @else
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Status Kartu:</span>
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">
                                ⚠️ Belum Ada
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Card Actions --}}
            <div class="px-4 pb-4">
                <div class="flex gap-2">
                    @if(!$hasCard)
                        {{-- Create Card Button --}}
                        <button 
                            wire:click="createEmployeeCard({{ $employee->id }})"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-2 px-3 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1">
                            🆔 Buat Kartu
                        </button>
                    @else
                        {{-- View Card Button --}}
                        <a href="/admin/employee-fi-cards/{{ $fi-card->id }}" 
                           target="_blank"
                           class="flex-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-2 px-3 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1">
                            👁️ Lihat Kartu
                        </a>
                    @endif
                    
                    {{-- View Details Button --}}
                    <button 
                        wire:click="viewEmployeeDetails({{ $employee->id }})"
                        class="flex-1 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium py-2 px-3 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1">
                        👤 Detail
                    </button>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Empty State --}}
@if($employees->isEmpty())
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.196-2.196M17 20H7m10 0v-2c0-5.523-4.477-10-10-10s-10 4.477-10 10v2m10 0H7m0 0v-2a3 3 0 015.196-2.196M7 20v-2m6-8a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada pegawai</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mulai dengan menambahkan pegawai baru.</p>
    </div>
@endif