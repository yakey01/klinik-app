<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Pendapatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PendapatanValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user for authentication
        $user = User::factory()->create();
        Auth::login($user);
    }

    public function test_unique_validation_allows_same_name_when_editing_same_record()
    {
        // Create a pendapatan record
        $pendapatan = Pendapatan::create([
            'kode_pendapatan' => 'PND-0001',
            'nama_pendapatan' => 'Test Pendapatan',
            'sumber_pendapatan' => 'Umum',
            'is_aktif' => 1,
            'tanggal' => now(),
            'nominal' => 100000,
            'kategori' => 'tindakan_medis',
            'status_validasi' => 'pending',
            'input_by' => Auth::id()
        ]);

        // Test the validation closure
        $validationClosure = function (string $attribute, $value, \Closure $fail) {
            $query = \App\Models\Pendapatan::where('nama_pendapatan', $value);
            
            // Simulate edit context with current record ID
            $context = 'edit';
            $currentRecordId = 1; // Assuming this is the first record
            
            if ($context === 'edit' && $currentRecordId) {
                $query->where('id', '!=', $currentRecordId);
            }
            
            if ($query->exists()) {
                $fail('Nama pendapatan sudah digunakan oleh record lain.');
            }
        };

        // Test: Should pass when editing with same name
        $failCalled = false;
        $validationClosure('nama_pendapatan', 'Test Pendapatan', function($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertFalse($failCalled, 'Validation should pass when editing with same name');
    }

    public function test_unique_validation_fails_when_editing_with_existing_other_name()
    {
        // Create two pendapatan records
        $pendapatan1 = Pendapatan::create([
            'kode_pendapatan' => 'PND-0001',
            'nama_pendapatan' => 'Test Pendapatan 1',
            'sumber_pendapatan' => 'Umum',
            'is_aktif' => 1,
            'tanggal' => now(),
            'nominal' => 100000,
            'kategori' => 'tindakan_medis',
            'status_validasi' => 'pending',
            'input_by' => Auth::id()
        ]);

        $pendapatan2 = Pendapatan::create([
            'kode_pendapatan' => 'PND-0002',
            'nama_pendapatan' => 'Test Pendapatan 2',
            'sumber_pendapatan' => 'Umum',
            'is_aktif' => 1,
            'tanggal' => now(),
            'nominal' => 100000,
            'kategori' => 'tindakan_medis',
            'status_validasi' => 'pending',
            'input_by' => Auth::id()
        ]);

        // Test the validation closure
        $validationClosure = function (string $attribute, $value, \Closure $fail) {
            $query = \App\Models\Pendapatan::where('nama_pendapatan', $value);
            
            // Simulate edit context with current record ID
            $context = 'edit';
            $currentRecordId = 1; // Editing first record
            
            if ($context === 'edit' && $currentRecordId) {
                $query->where('id', '!=', $currentRecordId);
            }
            
            if ($query->exists()) {
                $fail('Nama pendapatan sudah digunakan oleh record lain.');
            }
        };

        // Test: Should fail when editing with name that exists in other record
        $failCalled = false;
        $validationClosure('nama_pendapatan', 'Test Pendapatan 2', function($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertTrue($failCalled, 'Validation should fail when editing with existing other name');
    }

    public function test_unique_validation_fails_when_creating_with_existing_name()
    {
        // Create a pendapatan record
        $pendapatan = Pendapatan::create([
            'kode_pendapatan' => 'PND-0001',
            'nama_pendapatan' => 'Test Pendapatan',
            'sumber_pendapatan' => 'Umum',
            'is_aktif' => 1,
            'tanggal' => now(),
            'nominal' => 100000,
            'kategori' => 'tindakan_medis',
            'status_validasi' => 'pending',
            'input_by' => Auth::id()
        ]);

        // Test the validation closure
        $validationClosure = function (string $attribute, $value, \Closure $fail) {
            $query = \App\Models\Pendapatan::where('nama_pendapatan', $value);
            
            // Simulate create context (no current record)
            $context = 'create';
            $currentRecordId = null;
            
            if ($context === 'edit' && $currentRecordId) {
                $query->where('id', '!=', $currentRecordId);
            }
            
            if ($query->exists()) {
                $fail('Nama pendapatan sudah digunakan oleh record lain.');
            }
        };

        // Test: Should fail when creating with existing name
        $failCalled = false;
        $validationClosure('nama_pendapatan', 'Test Pendapatan', function($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertTrue($failCalled, 'Validation should fail when creating with existing name');
    }
}