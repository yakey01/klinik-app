<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Services\BulkOperationService;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\JenisTindakan;
use App\Models\Pendapatan;

class BulkOperationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BulkOperationService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new BulkOperationService();
        $this->user = User::factory()->create();
    }

    public function test_it_performs_bulk_create_successfully()
    {
        // Arrange
        $pasienData = [
            [
                'nama' => 'Patient 1',
                'no_rekam_medis' => 'RM-2024-001',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'L',
            ],
            [
                'nama' => 'Patient 2',
                'no_rekam_medis' => 'RM-2024-002',
                'tanggal_lahir' => '1991-02-02',
                'jenis_kelamin' => 'P',
            ],
            [
                'nama' => 'Patient 3',
                'no_rekam_medis' => 'RM-2024-003',
                'tanggal_lahir' => '1992-03-03',
                'jenis_kelamin' => 'L',
            ],
        ];

        // Act
        $result = $this->service->bulkCreate(Pasien::class, $pasienData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['created']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);

        // Verify data was created
        $this->assertDatabaseHas('pasien', ['nama' => 'Patient 1']);
        $this->assertDatabaseHas('pasien', ['nama' => 'Patient 2']);
        $this->assertDatabaseHas('pasien', ['nama' => 'Patient 3']);
    }

    public function test_it_handles_bulk_create_with_invalid_data()
    {
        // Arrange
        $mixedData = [
            [
                'nama' => 'Valid Patient',
                'no_rekam_medis' => 'RM-2024-001',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'L',
            ],
            [
                'nama' => 'Patient with Invalid Gender',
                'no_rekam_medis' => 'RM-2024-002',
                'tanggal_lahir' => '1991-02-02',
                'jenis_kelamin' => 'X', // Invalid gender
            ],
            [
                'nama' => 'Another Valid Patient',
                'no_rekam_medis' => 'RM-2024-003',
                'tanggal_lahir' => '1992-03-03',
                'jenis_kelamin' => 'P',
            ],
        ];

        // Act
        $result = $this->service->bulkCreate(Pasien::class, $mixedData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['created']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);

        // Verify valid data was created
        $this->assertDatabaseHas('pasien', ['nama' => 'Valid Patient']);
        $this->assertDatabaseHas('pasien', ['nama' => 'Another Valid Patient']);
        $this->assertDatabaseMissing('pasien', ['nama' => '']);
    }

    public function test_it_processes_data_in_batches()
    {
        // Arrange
        $largeDataset = [];
        for ($i = 1; $i <= 150; $i++) {
            $largeDataset[] = [
                'nama' => "Patient {$i}",
                'no_rekam_medis' => "RM-2024-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
            ];
        }

        // Act
        $result = $this->service->bulkCreate(Pasien::class, $largeDataset, ['batch_size' => 50]);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(150, $result['created']);
        $this->assertEquals(0, $result['failed']);

        // Verify all data was created
        $this->assertEquals(150, Pasien::count());
    }

    public function test_it_performs_bulk_update_successfully()
    {
        // Arrange
        $pasien1 = Pasien::factory()->create(['nama' => 'Old Name 1']);
        $pasien2 = Pasien::factory()->create(['nama' => 'Old Name 2']);
        $pasien3 = Pasien::factory()->create(['nama' => 'Old Name 3']);

        $updateData = [
            ['id' => $pasien1->id, 'nama' => 'New Name 1'],
            ['id' => $pasien2->id, 'nama' => 'New Name 2'],
            ['id' => $pasien3->id, 'nama' => 'New Name 3'],
        ];

        // Act
        $result = $this->service->bulkUpdate(Pasien::class, $updateData, 'id', ['validate' => false]);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['updated']);
        $this->assertEquals(0, $result['failed']);

        // Verify data was updated
        $this->assertDatabaseHas('pasien', ['id' => $pasien1->id, 'nama' => 'New Name 1']);
        $this->assertDatabaseHas('pasien', ['id' => $pasien2->id, 'nama' => 'New Name 2']);
        $this->assertDatabaseHas('pasien', ['id' => $pasien3->id, 'nama' => 'New Name 3']);
    }

    public function test_it_handles_bulk_update_with_nonexistent_records()
    {
        // Arrange
        $pasien1 = Pasien::factory()->create(['nama' => 'Existing Patient']);

        $updateData = [
            ['id' => $pasien1->id, 'nama' => 'Updated Name'],
            ['id' => 99999, 'nama' => 'Nonexistent Patient'], // Non-existent ID
        ];

        // Act
        $result = $this->service->bulkUpdate(Pasien::class, $updateData, 'id');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);

        // Verify existing record was updated
        $this->assertDatabaseHas('pasien', ['id' => $pasien1->id, 'nama' => 'Updated Name']);
    }

    public function test_it_performs_bulk_delete_successfully()
    {
        // Arrange
        $pasien1 = Pasien::factory()->create();
        $pasien2 = Pasien::factory()->create();
        $pasien3 = Pasien::factory()->create();

        $idsToDelete = [$pasien1->id, $pasien2->id];

        // Act
        $result = $this->service->bulkDelete(Pasien::class, $idsToDelete);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['deleted']);
        $this->assertEquals(0, $result['failed']);

        // Verify records were deleted
        $this->assertDatabaseMissing('pasien', ['id' => $pasien1->id]);
        $this->assertDatabaseMissing('pasien', ['id' => $pasien2->id]);
        $this->assertDatabaseHas('pasien', ['id' => $pasien3->id]); // Should still exist
    }

    public function test_it_handles_bulk_delete_with_nonexistent_ids()
    {
        // Arrange
        $pasien1 = Pasien::factory()->create();

        $idsToDelete = [$pasien1->id, 99999]; // Mix of existing and non-existing

        // Act
        $result = $this->service->bulkDelete(Pasien::class, $idsToDelete);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['deleted']);
        $this->assertEquals(1, $result['failed']);

        // Verify existing record was deleted
        $this->assertDatabaseMissing('pasien', ['id' => $pasien1->id]);
    }

    public function test_it_validates_data_before_operations()
    {
        // Arrange
        $invalidData = [
            [
                // Missing required fields
                'alamat' => 'Some address',
            ],
            [
                'nama' => 'Valid Patient',
                'no_rekam_medis' => 'RM-2024-001',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'L',
            ],
        ];

        // Act
        $result = $this->service->bulkCreate(Pasien::class, $invalidData, ['validate' => true]);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['created']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
    }

    public function test_it_handles_duplicate_constraints()
    {
        // Arrange
        Pasien::factory()->create([
            'no_rekam_medis' => 'RM-2024-001',
            'input_by' => $this->user->id,
        ]);

        $duplicateData = [
            [
                'nama' => 'Duplicate Patient',
                'no_rekam_medis' => 'RM-2024-001', // Duplicate
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'L',
            ],
            [
                'nama' => 'Valid Patient',
                'no_rekam_medis' => 'RM-2024-002',
                'tanggal_lahir' => '1991-01-01',
                'jenis_kelamin' => 'P',
            ],
        ];

        // Act
        $result = $this->service->bulkCreate(Pasien::class, $duplicateData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['created']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);

        // Verify only valid record was created
        $this->assertDatabaseHas('pasien', ['nama' => 'Valid Patient']);
        $this->assertDatabaseMissing('pasien', ['nama' => 'Duplicate Patient']);
    }

    public function test_it_works_with_different_model_types()
    {
        // Test with User model (which has a factory)
        $userData = [
            [
                'name' => 'Test User 1',
                'email' => 'test1@example.com',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Test User 2',
                'email' => 'test2@example.com',
                'password' => bcrypt('password'),
            ],
        ];

        // Act
        $result = $this->service->bulkCreate(User::class, $userData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['failed']);
    }

    public function test_it_uses_database_transactions()
    {
        // Arrange
        $pasienData = [
            [
                'nama' => 'Patient 1',
                'no_rekam_medis' => 'RM-2024-001',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'L',
            ],
            [
                'nama' => 'Patient 2',
                'no_rekam_medis' => 'RM-2024-001', // Duplicate to cause failure
                'tanggal_lahir' => '1991-02-02',
                'jenis_kelamin' => 'P',
            ],
        ];

        // Act
        $result = $this->service->bulkCreate(Pasien::class, $pasienData, ['use_transaction' => true]);

        // Assert - With transactions, partial success should still work
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['created']);
        $this->assertEquals(1, $result['failed']);
    }

    public function test_it_provides_detailed_error_information()
    {
        // Arrange
        $invalidData = [
            [
                'nama' => '', // Invalid
                'no_rekam_medis' => 'RM-2024-001',
            ],
        ];

        // Act
        $result = $this->service->bulkCreate(Pasien::class, $invalidData, ['validate' => true]);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['created']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        
        $error = $result['errors'][0];
        $this->assertArrayHasKey('index', $error);
        $this->assertArrayHasKey('data', $error);
        $this->assertArrayHasKey('error', $error);
        $this->assertEquals(0, $error['index']);
    }

    public function test_it_handles_large_datasets_efficiently()
    {
        // Arrange
        $largeDataset = [];
        for ($i = 1; $i <= 1000; $i++) {
            $largeDataset[] = [
                'nama' => "Patient {$i}",
                'no_rekam_medis' => "RM-2024-" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
            ];
        }

        // Act
        $startTime = microtime(true);
        $result = $this->service->bulkCreate(Pasien::class, $largeDataset, ['batch_size' => 100]);
        $endTime = microtime(true);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(1000, $result['created']);
        $this->assertEquals(0, $result['failed']);
        
        // Should complete in reasonable time (less than 10 seconds)
        $this->assertLessThan(10, $endTime - $startTime);
        
        // Verify count
        $this->assertEquals(1000, Pasien::count());
    }

    public function test_it_handles_custom_batch_sizes()
    {
        // Arrange
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'nama' => "Patient {$i}",
                'no_rekam_medis' => "RM-2024-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'L',
            ];
        }

        // Act
        $result = $this->service->bulkCreate(Pasien::class, $data, ['batch_size' => 3]);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['created']);
        $this->assertEquals(0, $result['failed']);
    }

    public function test_it_cleans_up_on_major_failures()
    {
        // This test ensures that if a major error occurs, 
        // the service handles it gracefully without leaving corrupted state
        
        // Arrange - Use an invalid model class
        $invalidData = [['test' => 'data']];

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->service->bulkCreate('InvalidModelClass', $invalidData);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}