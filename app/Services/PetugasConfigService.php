<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Exception;

class PetugasConfigService
{
    protected int $cacheMinutes = 60; // Cache for 1 hour
    
    /**
     * Get navigation groups configuration
     */
    public function getNavigationGroups(): array
    {
        try {
            return Cache::remember('petugas_navigation_groups', now()->addMinutes($this->cacheMinutes), function () {
                return [
                    'dashboard' => [
                        'label' => '🏠 Dashboard',
                        'icon' => 'heroicon-o-home',
                        'collapsible' => true,
                        'sort' => 1,
                    ],
                    'data_entry' => [
                        'label' => '📊 Data Entry',
                        'icon' => 'heroicon-o-document-text',
                        'collapsible' => true,
                        'sort' => 2,
                    ],
                    'financial' => [
                        'label' => '💰 Financial',
                        'icon' => 'heroicon-o-currency-dollar',
                        'collapsible' => true,
                        'sort' => 3,
                    ],
                    'patient_care' => [
                        'label' => '🤒 Patient Care',
                        'icon' => 'heroicon-o-heart',
                        'collapsible' => true,
                        'sort' => 4,
                    ],
                    'patient_management' => [
                        'label' => '👥 Manajemen Pasien',
                        'icon' => 'heroicon-o-user-group',
                        'collapsible' => true,
                        'sort' => 5,
                    ],
                    'daily_transactions' => [
                        'label' => '📊 Transaksi Harian',
                        'icon' => 'heroicon-o-chart-bar',
                        'collapsible' => true,
                        'sort' => 6,
                    ],
                ];
            });
        } catch (Exception $e) {
            Log::error('PetugasConfigService: Failed to get navigation groups', [
                'error' => $e->getMessage(),
            ]);
            
            return $this->getDefaultNavigationGroups();
        }
    }
    
    /**
     * Get form field configurations
     */
    public function getFormFields(): array
    {
        try {
            return Cache::remember('petugas_form_fields', now()->addMinutes($this->cacheMinutes), function () {
                return [
                    'gender_options' => [
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ],
                    'shift_options' => [
                        'Pagi' => 'Pagi (07:00 - 15:00)',
                        'Sore' => 'Sore (15:00 - 23:00)',
                        'Malam' => 'Malam (23:00 - 07:00)',
                    ],
                    'status_options' => [
                        'aktif' => 'Aktif',
                        'tidak_aktif' => 'Tidak Aktif',
                        'pending' => 'Pending',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ],
                    'validation_status_options' => [
                        'pending' => 'Menunggu Validasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'revision' => 'Perlu Revisi',
                    ],
                    'priority_options' => [
                        'low' => 'Rendah',
                        'medium' => 'Sedang',
                        'high' => 'Tinggi',
                        'urgent' => 'Mendesak',
                    ],
                ];
            });
        } catch (Exception $e) {
            Log::error('PetugasConfigService: Failed to get form fields', [
                'error' => $e->getMessage(),
            ]);
            
            return $this->getDefaultFormFields();
        }
    }
    
    /**
     * Get resource configurations
     */
    public function getResourceConfigs(): array
    {
        try {
            return Cache::remember('petugas_resource_configs', now()->addMinutes($this->cacheMinutes), function () {
                return [
                    'pasien' => [
                        'navigation_label' => '👤 Pasien',
                        'model_label' => 'Pasien',
                        'plural_model_label' => 'Input Pasien',
                        'navigation_icon' => 'heroicon-o-user-plus',
                        'navigation_group' => 'patient_management',
                        'navigation_sort' => 1,
                        'table_heading' => '👥 Daftar Pasien',
                        'table_description' => 'Kelola data pasien dengan mudah dan efisien',
                        'record_prefix' => 'RM',
                        'record_format' => 'RM-{year}-{sequence}',
                    ],
                    'pendapatan_harian' => [
                        'navigation_label' => '💰 Pendapatan Harian',
                        'model_label' => 'Pendapatan Harian',
                        'plural_model_label' => 'Pendapatan Harian',
                        'navigation_icon' => 'heroicon-o-arrow-trending-up',
                        'navigation_group' => 'daily_transactions',
                        'navigation_sort' => 1,
                        'table_heading' => '💰 Pendapatan Harian Saya',
                        'table_description' => 'Kelola pendapatan harian Anda dengan mudah dan efisien',
                    ],
                    'pengeluaran_harian' => [
                        'navigation_label' => '💸 Pengeluaran Harian',
                        'model_label' => 'Pengeluaran Harian',
                        'plural_model_label' => 'Pengeluaran Harian',
                        'navigation_icon' => 'heroicon-o-arrow-trending-down',
                        'navigation_group' => 'daily_transactions',
                        'navigation_sort' => 2,
                        'table_heading' => '💸 Pengeluaran Harian Saya',
                        'table_description' => 'Kelola pengeluaran harian Anda dengan mudah dan efisien',
                    ],
                    'tindakan' => [
                        'navigation_label' => '🏥 Tindakan',
                        'model_label' => 'Tindakan',
                        'plural_model_label' => 'Tindakan Medis',
                        'navigation_icon' => 'heroicon-o-hand-raised',
                        'navigation_group' => 'patient_care',
                        'navigation_sort' => 1,
                        'table_heading' => '🏥 Tindakan Medis',
                        'table_description' => 'Kelola tindakan medis dengan mudah dan efisien',
                    ],
                    'jumlah_pasien_harian' => [
                        'navigation_label' => '📊 Laporan Harian',
                        'model_label' => 'Jumlah Pasien Harian',
                        'plural_model_label' => 'Laporan Harian',
                        'navigation_icon' => 'heroicon-o-calendar-days',
                        'navigation_group' => 'data_entry',
                        'navigation_sort' => 1,
                        'table_heading' => '📊 Laporan Jumlah Pasien Harian',
                        'table_description' => 'Kelola laporan harian dengan mudah dan efisien',
                    ],
                ];
            });
        } catch (Exception $e) {
            Log::error('PetugasConfigService: Failed to get resource configs', [
                'error' => $e->getMessage(),
            ]);
            
            return $this->getDefaultResourceConfigs();
        }
    }
    
    /**
     * Get validation rules and thresholds
     */
    public function getValidationConfig(): array
    {
        try {
            return Cache::remember('petugas_validation_config', now()->addMinutes($this->cacheMinutes), function () {
                return [
                    'auto_approval_thresholds' => [
                        'tindakan' => 100000, // Auto approve below 100k
                        'pendapatan_harian' => 500000, // Auto approve below 500k
                        'pengeluaran_harian' => 200000, // Auto approve below 200k
                    ],
                    'validation_required_fields' => [
                        'tindakan' => ['jenis_tindakan_id', 'pasien_id', 'tanggal_tindakan', 'tarif'],
                        'pendapatan_harian' => ['pendapatan_id', 'nominal', 'tanggal_input'],
                        'pengeluaran_harian' => ['pengeluaran_id', 'nominal', 'tanggal_input'],
                    ],
                    'approval_levels' => [
                        'tindakan' => ['supervisor', 'manager'],
                        'pendapatan_harian' => ['supervisor'],
                        'pengeluaran_harian' => ['supervisor', 'manager'],
                    ],
                ];
            });
        } catch (Exception $e) {
            Log::error('PetugasConfigService: Failed to get validation config', [
                'error' => $e->getMessage(),
            ]);
            
            return $this->getDefaultValidationConfig();
        }
    }
    
    /**
     * Get UI configuration
     */
    public function getUIConfig(): array
    {
        try {
            return Cache::remember('petugas_ui_config', now()->addMinutes($this->cacheMinutes), function () {
                return [
                    'colors' => [
                        'primary' => 'rgb(102, 126, 234)',
                        'secondary' => 'rgb(118, 75, 162)',
                        'success' => 'rgb(16, 185, 129)',
                        'warning' => 'rgb(251, 189, 35)',
                        'danger' => 'rgb(239, 68, 68)',
                        'info' => 'rgb(58, 191, 248)',
                    ],
                    'icons' => [
                        'loading' => 'heroicon-o-arrow-path',
                        'success' => 'heroicon-o-check-circle',
                        'error' => 'heroicon-o-x-circle',
                        'warning' => 'heroicon-o-exclamation-triangle',
                        'info' => 'heroicon-o-information-circle',
                    ],
                    'pagination' => [
                        'per_page_options' => [10, 25, 50, 100],
                        'default_per_page' => 25,
                    ],
                    'date_formats' => [
                        'display' => 'd/m/Y',
                        'display_with_time' => 'd/m/Y H:i',
                        'input' => 'Y-m-d',
                        'input_with_time' => 'Y-m-d H:i:s',
                    ],
                ];
            });
        } catch (Exception $e) {
            Log::error('PetugasConfigService: Failed to get UI config', [
                'error' => $e->getMessage(),
            ]);
            
            return $this->getDefaultUIConfig();
        }
    }
    
    /**
     * Get configuration by key
     */
    public function getConfig(string $key, $default = null)
    {
        try {
            $configs = [
                'navigation_groups' => $this->getNavigationGroups(),
                'form_fields' => $this->getFormFields(),
                'resource_configs' => $this->getResourceConfigs(),
                'validation_config' => $this->getValidationConfig(),
                'ui_config' => $this->getUIConfig(),
            ];
            
            return $configs[$key] ?? $default;
        } catch (Exception $e) {
            Log::error('PetugasConfigService: Failed to get config', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return $default;
        }
    }
    
    /**
     * Clear configuration cache
     */
    public function clearCache(): bool
    {
        try {
            $keys = [
                'petugas_navigation_groups',
                'petugas_form_fields',
                'petugas_resource_configs',
                'petugas_validation_config',
                'petugas_ui_config',
            ];
            
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('PetugasConfigService: Failed to clear cache', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Get default navigation groups (fallback)
     */
    protected function getDefaultNavigationGroups(): array
    {
        return [
            'dashboard' => ['label' => '🏠 Dashboard', 'icon' => 'heroicon-o-home', 'collapsible' => true, 'sort' => 1],
            'data_entry' => ['label' => '📊 Data Entry', 'icon' => 'heroicon-o-document-text', 'collapsible' => true, 'sort' => 2],
            'financial' => ['label' => '💰 Financial', 'icon' => 'heroicon-o-currency-dollar', 'collapsible' => true, 'sort' => 3],
            'patient_care' => ['label' => '🤒 Patient Care', 'icon' => 'heroicon-o-heart', 'collapsible' => true, 'sort' => 4],
        ];
    }
    
    /**
     * Get default form fields (fallback)
     */
    protected function getDefaultFormFields(): array
    {
        return [
            'gender_options' => ['L' => 'Laki-laki', 'P' => 'Perempuan'],
            'shift_options' => ['Pagi' => 'Pagi', 'Sore' => 'Sore'],
            'status_options' => ['aktif' => 'Aktif', 'tidak_aktif' => 'Tidak Aktif'],
        ];
    }
    
    /**
     * Get default resource configs (fallback)
     */
    protected function getDefaultResourceConfigs(): array
    {
        return [
            'pasien' => [
                'navigation_label' => '👤 Pasien',
                'model_label' => 'Pasien',
                'navigation_icon' => 'heroicon-o-user-plus',
            ],
        ];
    }
    
    /**
     * Get default validation config (fallback)
     */
    protected function getDefaultValidationConfig(): array
    {
        return [
            'auto_approval_thresholds' => [
                'tindakan' => 100000,
                'pendapatan_harian' => 500000,
                'pengeluaran_harian' => 200000,
            ],
        ];
    }
    
    /**
     * Get default UI config (fallback)
     */
    protected function getDefaultUIConfig(): array
    {
        return [
            'colors' => [
                'primary' => 'rgb(102, 126, 234)',
                'success' => 'rgb(16, 185, 129)',
                'warning' => 'rgb(251, 189, 35)',
                'danger' => 'rgb(239, 68, 68)',
            ],
            'pagination' => [
                'per_page_options' => [10, 25, 50, 100],
                'default_per_page' => 25,
            ],
        ];
    }
}