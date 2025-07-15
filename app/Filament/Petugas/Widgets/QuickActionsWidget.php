<?php

namespace App\Filament\Petugas\Widgets;

use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Exception;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.quick-actions-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected array $actionDefinitions = [
        'add_patient' => [
            'label' => 'Tambah Pasien',
            'icon' => 'heroicon-o-user-plus',
            'color' => 'primary',
            'route' => 'filament.petugas.resources.pasiens.create',
            'permission' => 'create_pasien',
        ],
        'add_procedure' => [
            'label' => 'Input Tindakan',
            'icon' => 'heroicon-o-hand-raised',
            'color' => 'success',
            'route' => 'filament.petugas.resources.tindakans.create',
            'permission' => 'create_tindakan',
        ],
        'add_income' => [
            'label' => 'Input Pendapatan',
            'icon' => 'heroicon-o-arrow-trending-up',
            'color' => 'warning',
            'route' => 'filament.petugas.resources.pendapatan-harians.create',
            'permission' => 'create_pendapatan_harian',
        ],
        'add_expense' => [
            'label' => 'Input Pengeluaran',
            'icon' => 'heroicon-o-arrow-trending-down',
            'color' => 'danger',
            'route' => 'filament.petugas.resources.pengeluaran-harians.create',
            'permission' => 'create_pengeluaran_harian',
        ],
        'daily_report' => [
            'label' => 'Laporan Harian',
            'icon' => 'heroicon-o-calendar-days',
            'color' => 'gray',
            'route' => 'filament.petugas.resources.jumlah-pasien-harians.create',
            'permission' => 'create_jumlah_pasien_harian',
        ],
        'view_patients' => [
            'label' => 'Lihat Semua Pasien',
            'icon' => 'heroicon-o-users',
            'color' => 'info',
            'route' => 'filament.petugas.resources.pasiens.index',
            'permission' => 'view_pasien',
        ],
    ];

    public function getActions(): array
    {
        try {
            $actions = [];
            $user = Auth::user();
            
            if (!$user) {
                Log::warning('QuickActionsWidget: No authenticated user');
                return [];
            }
            
            foreach ($this->actionDefinitions as $actionKey => $actionConfig) {
                try {
                    // Check if route exists
                    if (!Route::has($actionConfig['route'])) {
                        Log::warning('QuickActionsWidget: Route not found', [
                            'action' => $actionKey,
                            'route' => $actionConfig['route']
                        ]);
                        continue;
                    }
                    
                    // Check user permission (basic check - you might want to use a more robust permission system)
                    if (isset($actionConfig['permission']) && method_exists($user, 'can')) {
                        if (!$user->can($actionConfig['permission'])) {
                            continue;
                        }
                    }
                    
                    $actions[] = Action::make($actionKey)
                        ->label($actionConfig['label'])
                        ->icon($actionConfig['icon'])
                        ->color($actionConfig['color'])
                        ->size(ActionSize::Large)
                        ->url(route($actionConfig['route']))
                        ->openUrlInNewTab(false);
                        
                } catch (Exception $e) {
                    Log::error('QuickActionsWidget: Failed to create action', [
                        'action' => $actionKey,
                        'error' => $e->getMessage(),
                        'user_id' => $user->id,
                    ]);
                }
            }
            
            return $actions;
            
        } catch (Exception $e) {
            Log::error('QuickActionsWidget: Failed to get actions', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            return [];
        }
    }

    public function getUserGreeting(): string
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return 'Selamat datang!';
            }
            
            $hour = now()->hour;
            
            $greeting = match (true) {
                $hour < 12 => 'Selamat pagi',
                $hour < 17 => 'Selamat siang',
                default => 'Selamat malam',
            };
            
            return $greeting . ', ' . $user->name . '!';
            
        } catch (Exception $e) {
            Log::error('QuickActionsWidget: Failed to get user greeting', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            return 'Selamat datang!';
        }
    }

    public function getWorkflowTips(): array
    {
        try {
            $user = Auth::user();
            $userRole = $user && method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() : 'petugas';
            
            $baseTips = [
                'Mulai hari dengan memeriksa jadwal pasien',
                'Input data pasien sesegera mungkin setelah registrasi',
                'Catat semua tindakan medis yang dilakukan',
                'Update pendapatan dan pengeluaran di akhir hari',
                'Periksa laporan harian sebelum menutup shift',
            ];
            
            // Add role-specific tips
            $roleTips = match ($userRole) {
                'supervisor' => [
                    'Review dan approve validasi yang pending',
                    'Monitor performa tim dan berikan feedback',
                ],
                'admin' => [
                    'Backup data system secara berkala',
                    'Monitor system health dan performance',
                ],
                default => [
                    'Pastikan semua input data sudah benar sebelum submit',
                    'Gunakan fitur bulk operation untuk efisiensi',
                ]
            };
            
            return array_merge($baseTips, $roleTips);
            
        } catch (Exception $e) {
            Log::error('QuickActionsWidget: Failed to get workflow tips', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            return [
                'Mulai hari dengan memeriksa jadwal pasien',
                'Input data pasien sesegera mungkin setelah registrasi',
                'Catat semua tindakan medis yang dilakukan',
                'Update pendapatan dan pengeluaran di akhir hari',
                'Periksa laporan harian sebelum menutup shift',
            ];
        }
    }
    
    public function getViewData(): array
    {
        try {
            return [
                'actions' => $this->getActions(),
                'greeting' => $this->getUserGreeting(),
                'tips' => $this->getWorkflowTips(),
                'user' => Auth::user(),
                'last_updated' => now()->format('d/m/Y H:i'),
            ];
            
        } catch (Exception $e) {
            Log::error('QuickActionsWidget: Failed to get view data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            return [
                'actions' => [],
                'greeting' => 'Selamat datang!',
                'tips' => [],
                'user' => null,
                'last_updated' => now()->format('d/m/Y H:i'),
                'error' => 'Gagal memuat data widget',
            ];
        }
    }
}