<?php

namespace App\Filament\Resources\JadwalJagaResource\Pages;

use App\Filament\Resources\JadwalJagaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJadwalJagas extends ListRecords
{
    protected static string $resource = JadwalJagaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulk_schedule')
                ->label('🎯 Atur Jadwal Shift')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->tooltip('Pilih hingga 5 pegawai sekaligus untuk satu shift')
                ->modal()
                ->modalWidth('7xl')
                ->form([
                    \Filament\Forms\Components\Section::make('Pilih Tanggal & Shift')
                        ->schema([
                            \Filament\Forms\Components\DatePicker::make('tanggal_jaga')
                                ->label('Tanggal Jaga')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->minDate(now())
                                ->reactive(),
                            \Filament\Forms\Components\Select::make('shift_template_id')
                                ->label('Template Shift')
                                ->options(function () {
                                    return \App\Models\ShiftTemplate::all()
                                        ->mapWithKeys(fn ($shift) => [
                                            $shift->id => $shift->nama_shift . ' (' . $shift->jam_masuk . ' - ' . $shift->jam_pulang . ')'
                                        ]);
                                })
                                ->required()
                                ->searchable()
                                ->reactive(),
                            \Filament\Forms\Components\Select::make('jenis_tugas')
                                ->label('Jenis Tugas')
                                ->options([
                                    'pendaftaran' => 'Pendaftaran',
                                    'pelayanan' => 'Pelayanan',
                                    'dokter_jaga' => 'Dokter Jaga'
                                ])
                                ->required()
                                ->reactive()
                                ->helperText('Pilih jenis tugas untuk menentukan pegawai yang tersedia'),
                        ])->columns(3),
                        
                    \Filament\Forms\Components\Section::make('Pegawai yang Sudah Terjadwal')
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('existing_schedule')
                                ->label('')
                                ->content(function (callable $get) {
                                    $tanggal = $get('tanggal_jaga');
                                    $shiftId = $get('shift_template_id');
                                    
                                    if (!$tanggal || !$shiftId) {
                                        return '⏳ Pilih tanggal dan shift untuk melihat jadwal yang sudah ada.';
                                    }
                                    
                                    $existingSchedules = \App\Models\JadwalJaga::where('tanggal_jaga', $tanggal)
                                        ->where('shift_template_id', $shiftId)
                                        ->with(['pegawai', 'shiftTemplate'])
                                        ->get();
                                        
                                    if ($existingSchedules->isEmpty()) {
                                        return '✅ Belum ada pegawai yang terjadwal untuk shift ini.';
                                    }
                                    
                                    $html = '<div class=\"space-y-2\">';
                                    $html .= '<div class=\"text-sm font-medium text-gray-700 dark:text-gray-300 mb-3\">Pegawai yang sudah terjadwal (' . $existingSchedules->count() . ' orang):</div>';
                                    
                                    foreach ($existingSchedules as $schedule) {
                                        $html .= '<div class=\"flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border\">';
                                        $html .= '<div class=\"flex items-center space-x-3\">';
                                        $html .= '<div class=\"w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium\">' . substr($schedule->pegawai->name, 0, 2) . '</div>';
                                        $html .= '<div class=\"font-medium text-gray-900 dark:text-gray-100\">' . $schedule->pegawai->name . '</div>';
                                        $html .= '</div>';
                                        $html .= '<span class=\"text-xs px-2 py-1 bg-green-100 text-green-800 rounded\">' . $schedule->status_jaga . '</span>';
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '</div>';
                                    return new \Illuminate\Support\HtmlString($html);
                                })
                        ])
                        ->visible(fn (callable $get) => $get('tanggal_jaga') && $get('shift_template_id')),
                        
                    \Filament\Forms\Components\Section::make('Pilih Pegawai Baru (Maksimal 5)')
                        ->schema([
                            \Filament\Forms\Components\CheckboxList::make('pegawai_ids')
                                ->label('📋 Pilih Pegawai dari Manajemen Admin')
                                ->descriptions(function (callable $get) {
                                    $tanggal = $get('tanggal_jaga');
                                    $shiftId = $get('shift_template_id');
                                    $jenisTugas = $get('jenis_tugas');
                                    
                                    if (!$tanggal || !$shiftId || !$jenisTugas) {
                                        return [];
                                    }
                                    
                                    // Get comprehensive staff data based on task type
                                    if ($jenisTugas === 'dokter_jaga') {
                                        // Get from Dokter management
                                        $availableStaff = \App\Models\Dokter::where('aktif', true)->get();
                                    } else {
                                        // Get from Pegawai management for pendaftaran and pelayanan
                                        $availableStaff = \App\Models\Pegawai::where('aktif', true)
                                            ->whereIn('jenis_pegawai', ['Paramedis', 'Non-Paramedis'])
                                            ->get();
                                    }
                                    
                                    // Exclude staff already scheduled for this SAME shift
                                    $excludeStaffIds = \App\Models\JadwalJaga::where('tanggal_jaga', $tanggal)
                                        ->where('shift_template_id', $shiftId)
                                        ->pluck('pegawai_id');
                                    
                                    $descriptions = [];
                                    foreach ($availableStaff as $staff) {
                                        // Use staff record ID directly
                                        $staffId = $jenisTugas === 'dokter_jaga' ? 'dokter_' . $staff->id : 'pegawai_' . $staff->id;
                                        
                                        // Show appropriate description based on task type
                                        if ($jenisTugas === 'dokter_jaga') {
                                            $descriptions[$staffId] = "🩺 " . $staff->jabatan_display . " • SIP: " . $staff->nomor_sip;
                                        } elseif ($jenisTugas === 'pendaftaran') {
                                            $descriptions[$staffId] = "📝 " . $staff->jabatan . " • " . $staff->jenis_pegawai . " • NIK: " . $staff->nik;
                                        } else { // pelayanan
                                            $descriptions[$staffId] = "🏥 " . $staff->jabatan . " • " . $staff->jenis_pegawai . " • NIK: " . $staff->nik;
                                        }
                                    }
                                    
                                    return $descriptions;
                                })
                                ->options(function (callable $get) {
                                    $tanggal = $get('tanggal_jaga');
                                    $shiftId = $get('shift_template_id');
                                    $jenisTugas = $get('jenis_tugas');
                                    
                                    if (!$tanggal || !$shiftId || !$jenisTugas) {
                                        return [];
                                    }
                                    
                                    // Get comprehensive staff data based on task type
                                    if ($jenisTugas === 'dokter_jaga') {
                                        // Get from Dokter management
                                        $availableStaff = \App\Models\Dokter::where('aktif', true)->get();
                                    } else {
                                        // Get from Pegawai management for pendaftaran and pelayanan
                                        $availableStaff = \App\Models\Pegawai::where('aktif', true)
                                            ->whereIn('jenis_pegawai', ['Paramedis', 'Non-Paramedis'])
                                            ->get();
                                    }
                                    
                                    // Exclude staff already scheduled for this SAME shift
                                    $excludeStaffIds = \App\Models\JadwalJaga::where('tanggal_jaga', $tanggal)
                                        ->where('shift_template_id', $shiftId)
                                        ->pluck('pegawai_id');
                                    
                                    $options = [];
                                    foreach ($availableStaff as $staff) {
                                        // Use staff record ID directly  
                                        $staffId = $jenisTugas === 'dokter_jaga' ? 'dokter_' . $staff->id : 'pegawai_' . $staff->id;
                                        
                                        // Show all active staff for the selected task type
                                        $options[$staffId] = $staff->nama_lengkap;
                                    }
                                    
                                    return $options;
                                })
                                ->columns(2)
                                ->gridDirection('row')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    // Limit to 5 selections
                                    if (is_array($state) && count($state) > 5) {
                                        $limited = array_slice($state, 0, 5);
                                        $set('pegawai_ids', $limited);
                                        
                                        \Filament\Notifications\Notification::make()
                                            ->warning()
                                            ->title('Maksimal 5 pegawai')
                                            ->body('Anda hanya bisa memilih maksimal 5 pegawai per shift.')
                                            ->send();
                                    }
                                })
                                ->helperText(function (callable $get) {
                                    $selected = $get('pegawai_ids') ?? [];
                                    $count = is_array($selected) ? count($selected) : 0;
                                    $remaining = 5 - $count;
                                    $jenisTugas = $get('jenis_tugas');
                                    
                                    $source = match($jenisTugas) {
                                        'dokter_jaga' => 'Manajemen Dokter',
                                        'pendaftaran' => 'Manajemen Pegawai (Pendaftaran)',
                                        'pelayanan' => 'Manajemen Pegawai (Pelayanan)',
                                        default => 'Manajemen Pegawai'
                                    };
                                    
                                    if ($count === 0) {
                                        return "💡 Data diambil dari {$source}. Pilih pegawai yang akan bertugas (maksimal 5 orang)";
                                    }
                                    
                                    if ($remaining > 0) {
                                        return "✅ {$count} pegawai dipilih dari {$source}. Tersisa {$remaining} slot.";
                                    }
                                    
                                    return "🔥 Shift penuh! 5 pegawai sudah dipilih dari {$source}.";
                                }),
                        ])
                        ->visible(fn (callable $get) => $get('tanggal_jaga') && $get('shift_template_id') && $get('jenis_tugas')),
                ])
                ->action(function (array $data): void {
                    if (empty($data['pegawai_ids'])) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Gagal!')
                            ->body('Pilih minimal 1 pegawai untuk dijadwalkan.')
                            ->send();
                        return;
                    }

                    $created = 0;
                    $skipped = 0;
                    
                    foreach ($data['pegawai_ids'] as $staffId) {
                        // Parse the staff ID to get the actual staff record
                        if (str_starts_with($staffId, 'dokter_')) {
                            $actualId = str_replace('dokter_', '', $staffId);
                            $dokter = \App\Models\Dokter::find($actualId);
                            
                            if (!$dokter) {
                                $skipped++;
                                continue;
                            }
                            
                            // Try to get existing User, or create a temporary schedule entry
                            $user = $dokter->user;
                            if (!$user) {
                                // For demo purposes, let's use a placeholder approach
                                // In production, you'd want to create User accounts or handle this differently
                                $skipped++;
                                continue;
                            }
                            
                            $pegawaiId = $user->id;
                            $peran = 'Dokter';
                            $staffName = $dokter->nama_lengkap;
                            $unitKerja = 'Dokter Jaga';
                            
                        } elseif (str_starts_with($staffId, 'pegawai_')) {
                            $actualId = str_replace('pegawai_', '', $staffId);
                            $pegawai = \App\Models\Pegawai::find($actualId);
                            
                            if (!$pegawai) {
                                $skipped++;
                                continue;
                            }
                            
                            // Try to get existing User, or create a temporary schedule entry
                            $user = $pegawai->user;
                            if (!$user) {
                                // For demo purposes, let's use a placeholder approach
                                $skipped++;
                                continue;
                            }
                            
                            $pegawaiId = $user->id;
                            $peran = $pegawai->jenis_pegawai === 'Paramedis' ? 'Paramedis' : 'NonParamedis';
                            $staffName = $pegawai->nama_lengkap;
                            
                            // Map jenis_tugas to unit_kerja for backward compatibility
                            $unitKerja = match($data['jenis_tugas']) {
                                'pendaftaran' => 'Pendaftaran',
                                'pelayanan' => 'Pelayanan',
                                default => 'Pelayanan'
                            };
                        } else {
                            $skipped++;
                            continue; // Invalid format
                        }

                        // Check if already exists to prevent duplicates
                        $exists = \App\Models\JadwalJaga::where('tanggal_jaga', $data['tanggal_jaga'])
                            ->where('shift_template_id', $data['shift_template_id'])
                            ->where('pegawai_id', $pegawaiId)
                            ->exists();

                        if (!$exists) {
                            \App\Models\JadwalJaga::create([
                                'tanggal_jaga' => $data['tanggal_jaga'],
                                'shift_template_id' => $data['shift_template_id'],
                                'pegawai_id' => $pegawaiId,
                                'unit_kerja' => $unitKerja,
                                'unit_instalasi' => $unitKerja, // Keep for backward compatibility
                                'peran' => $peran,
                                'status_jaga' => 'Aktif',
                                'keterangan' => 'Bulk schedule - ' . $staffName . ' (' . $data['jenis_tugas'] . ') - ' . now()->format('d/m/Y H:i'),
                            ]);
                            $created++;
                        }
                    }

                    if ($created > 0) {
                        $message = $created . ' pegawai berhasil dijadwalkan.';
                        if ($skipped > 0) {
                            $message .= ' ' . $skipped . ' pegawai dilewati (belum memiliki akun User).';
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Berhasil!')
                            ->body($message)
                            ->send();
                    } else {
                        $message = 'Tidak ada pegawai yang bisa dijadwalkan.';
                        if ($skipped > 0) {
                            $message .= ' ' . $skipped . ' pegawai dilewati karena belum memiliki akun User.';
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Tidak ada perubahan')
                            ->body($message)
                            ->send();
                    }
                }),
            Actions\CreateAction::make()
                ->label('Tambah Individual')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
