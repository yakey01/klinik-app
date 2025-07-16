<?php

/**
 * Migration Merge Validation Script
 * 
 * This script validates that merged migrations maintain database integrity
 */

namespace Database\Migrations\Validation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationMergeValidator
{
    private array $errors = [];
    private array $warnings = [];
    
    /**
     * Run all validation checks
     */
    public function validate(): array
    {
        $this->checkTableStructure();
        $this->checkForeignKeys();
        $this->checkIndexes();
        $this->checkMigrationHistory();
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
    
    /**
     * Check if table structures match expected schema
     */
    private function checkTableStructure(): void
    {
        $tables = [
            'users' => [
                'required_columns' => [
                    'id', 'name', 'email', 'role_id', 'username', 'nip',
                    'phone', 'address', 'pegawai_id'
                ],
                'required_nullable' => ['role_id', 'username', 'nip', 'pegawai_id']
            ],
            'pendapatan' => [
                'required_columns' => [
                    'tanggal_tindakan', 'nama_pasien', 'biaya_tindakan',
                    'catatan', 'status', 'is_aktif'
                ],
                'required_nullable' => ['tindakan_id', 'dokter_id', 'biaya_jasa']
            ],
            'tindakan' => [
                'required_columns' => [
                    'input_by', 'status_validasi', 'validated_by',
                    'validation_notes', 'validation_method'
                ],
                'required_nullable' => ['dokter_id', 'validated_by']
            ],
            'pegawais' => [
                'required_columns' => [
                    'nik', 'user_id', 'username', 'password', 'is_active'
                ],
                'required_nullable' => ['user_id', 'username']
            ],
            'attendances' => [
                'required_columns' => [
                    'user_device_id', 'device_name', 'check_in_latitude',
                    'check_in_longitude', 'check_in_address'
                ],
                'required_nullable' => ['user_device_id', 'device_name']
            ]
        ];
        
        foreach ($tables as $table => $requirements) {
            if (!Schema::hasTable($table)) {
                $this->errors[] = "Table '$table' does not exist";
                continue;
            }
            
            foreach ($requirements['required_columns'] as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $this->errors[] = "Column '$column' missing from table '$table'";
                }
            }
            
            // Check nullable constraints
            if (isset($requirements['required_nullable'])) {
                foreach ($requirements['required_nullable'] as $column) {
                    $columnInfo = DB::select("SHOW COLUMNS FROM $table WHERE Field = ?", [$column]);
                    if (!empty($columnInfo) && $columnInfo[0]->Null !== 'YES') {
                        $this->warnings[] = "Column '$column' in table '$table' should be nullable";
                    }
                }
            }
        }
    }
    
    /**
     * Check foreign key constraints
     */
    private function checkForeignKeys(): void
    {
        $expectedForeignKeys = [
            'users' => [
                ['column' => 'role_id', 'references' => 'roles', 'on_delete' => 'cascade'],
                ['column' => 'pegawai_id', 'references' => 'pegawais', 'on_delete' => 'cascade'],
                ['column' => 'default_work_location_id', 'references' => 'work_locations', 'on_delete' => 'set null']
            ],
            'tindakan' => [
                ['column' => 'pasien_id', 'references' => 'pasien', 'on_delete' => 'restrict'],
                ['column' => 'dokter_id', 'references' => 'dokters', 'on_delete' => 'restrict'],
                ['column' => 'input_by', 'references' => 'users', 'on_delete' => 'cascade'],
                ['column' => 'validated_by', 'references' => 'users', 'on_delete' => 'set null']
            ],
            'pegawais' => [
                ['column' => 'user_id', 'references' => 'users', 'on_delete' => 'cascade']
            ],
            'attendances' => [
                ['column' => 'user_device_id', 'references' => 'user_devices', 'on_delete' => 'cascade']
            ]
        ];
        
        foreach ($expectedForeignKeys as $table => $foreignKeys) {
            foreach ($foreignKeys as $fk) {
                $exists = DB::select("
                    SELECT * FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ? 
                    AND COLUMN_NAME = ? 
                    AND REFERENCED_TABLE_NAME = ?
                ", [$table, $fk['column'], $fk['references']]);
                
                if (empty($exists)) {
                    $this->errors[] = "Foreign key missing: $table.{$fk['column']} -> {$fk['references']}";
                }
            }
        }
    }
    
    /**
     * Check indexes
     */
    private function checkIndexes(): void
    {
        $expectedIndexes = [
            'users' => ['role_id', 'nip', 'is_active', 'pegawai_id'],
            'pendapatan' => ['tanggal_tindakan', 'status', 'is_aktif'],
            'tindakan' => ['status_validasi', 'validated_by', 'validated_at', 'requires_review'],
            'pegawais' => ['user_id', 'username', 'is_active'],
            'attendances' => ['user_device_id', 'device_id']
        ];
        
        foreach ($expectedIndexes as $table => $indexes) {
            $existingIndexes = DB::select("SHOW INDEXES FROM $table");
            $indexColumns = array_column($existingIndexes, 'Column_name');
            
            foreach ($indexes as $index) {
                if (!in_array($index, $indexColumns)) {
                    $this->warnings[] = "Index missing on $table.$index";
                }
            }
        }
    }
    
    /**
     * Check migration history integrity
     */
    private function checkMigrationHistory(): void
    {
        $mergedMigrations = [
            '2025_07_11_092700_enhance_users_table_complete',
            '2025_07_11_125444_enhance_pendapatan_table_complete',
            '2025_07_11_123000_enhance_tindakan_table_complete',
            '2025_07_11_233203_enhance_pegawais_table_complete',
            '2025_07_11_225513_create_gps_spoofing_system_tables',
            '2025_07_11_165455_enhance_attendances_table_complete'
        ];
        
        $oldMigrations = [
            '2025_07_11_092700_add_role_id_to_users_table',
            '2025_07_12_225550_add_username_to_users_table',
            '2025_07_15_070054_add_profile_settings_to_users_table',
            // ... etc
        ];
        
        $currentMigrations = DB::table('migrations')->pluck('migration')->toArray();
        
        // Check that old migrations are removed
        foreach ($oldMigrations as $old) {
            if (in_array($old, $currentMigrations)) {
                $this->warnings[] = "Old migration still in history: $old";
            }
        }
        
        // Check that new migrations exist
        foreach ($mergedMigrations as $new) {
            if (!in_array($new, $currentMigrations)) {
                $this->errors[] = "Merged migration missing from history: $new";
            }
        }
    }
}

// Run validation if executed directly
if (php_sapi_name() === 'cli') {
    $validator = new MigrationMergeValidator();
    $result = $validator->validate();
    
    echo "Migration Merge Validation Results\n";
    echo "==================================\n\n";
    
    if ($result['valid']) {
        echo "✓ All validations passed!\n";
    } else {
        echo "✗ Validation failed with " . count($result['errors']) . " errors\n";
    }
    
    if (!empty($result['errors'])) {
        echo "\nErrors:\n";
        foreach ($result['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    
    if (!empty($result['warnings'])) {
        echo "\nWarnings:\n";
        foreach ($result['warnings'] as $warning) {
            echo "  - $warning\n";
        }
    }
    
    exit($result['valid'] ? 0 : 1);
}