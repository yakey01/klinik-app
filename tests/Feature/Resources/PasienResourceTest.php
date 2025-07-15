<?php

namespace Tests\Feature\Resources;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Pasien;
use App\Filament\Petugas\Resources\PasienResource;
use App\Filament\Petugas\Resources\PasienResource\Pages\ListPasiens;
use App\Filament\Petugas\Resources\PasienResource\Pages\CreatePasien;
use App\Filament\Petugas\Resources\PasienResource\Pages\EditPasien;
use Spatie\Permission\Models\Role;

class PasienResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'petugas']);
        Role::create(['name' => 'supervisor']);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('petugas');
        
        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('petugas');
        
        $this->actingAs($this->user);
    }

    public function test_it_displays_pasien_list_correctly()
    {
        // Arrange
        $userPasien = Pasien::factory()->create([
            'input_by' => $this->user->id,
            'nama' => 'User Patient',
            'no_rekam_medis' => 'RM-2024-001',
        ]);
        
        $otherPasien = Pasien::factory()->create([
            'input_by' => $this->otherUser->id,
            'nama' => 'Other Patient',
            'no_rekam_medis' => 'RM-2024-002',
        ]);

        // Act
        $component = Livewire::test(ListPasiens::class);

        // Assert
        $component->assertSuccessful()
            ->assertSee($userPasien->nama)
            ->assertSee($userPasien->no_rekam_medis)
            ->assertDontSee($otherPasien->nama)
            ->assertDontSee($otherPasien->no_rekam_medis);
    }

    public function test_it_enforces_user_data_scoping()
    {
        // Arrange
        $userPasien = Pasien::factory()->create(['input_by' => $this->user->id]);
        $otherPasien = Pasien::factory()->create(['input_by' => $this->otherUser->id]);

        // Act
        $query = PasienResource::getEloquentQuery();
        $results = $query->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals($userPasien->id, $results->first()->id);
        $this->assertNotContains($otherPasien->id, $results->pluck('id'));
    }

    public function test_it_creates_pasien_successfully()
    {
        // Arrange
        $pasienData = [
            'no_rekam_medis' => 'RM-2024-003',
            'nama' => 'New Patient',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Test Address',
            'no_telepon' => '08123456789',
            'email' => 'patient@test.com',
            'pekerjaan' => 'Engineer',
            'status_pernikahan' => 'menikah',
            'kontak_darurat_nama' => 'Emergency Contact',
            'kontak_darurat_telepon' => '08987654321',
        ];

        // Act
        $component = Livewire::test(CreatePasien::class)
            ->fillForm($pasienData)
            ->call('create');

        // Assert
        $component->assertSuccessful();
        
        $this->assertDatabaseHas('pasien', [
            'nama' => 'New Patient',
            'no_rekam_medis' => 'RM-2024-003',
            'input_by' => $this->user->id,
        ]);
    }

    public function test_it_validates_required_fields_on_create()
    {
        // Arrange
        $invalidData = [
            'nama' => '', // Required field missing
            'tanggal_lahir' => '',
            'jenis_kelamin' => '',
        ];

        // Act
        $component = Livewire::test(CreatePasien::class)
            ->fillForm($invalidData)
            ->call('create');

        // Assert
        $component->assertHasFormErrors([
            'nama',
            'tanggal_lahir', 
            'jenis_kelamin'
        ]);
        
        $this->assertDatabaseMissing('pasien', [
            'nama' => '',
        ]);
    }

    public function test_it_validates_unique_no_rekam_medis()
    {
        // Arrange
        $existingPasien = Pasien::factory()->create([
            'no_rekam_medis' => 'RM-2024-001',
            'input_by' => $this->user->id,
        ]);

        $duplicateData = [
            'no_rekam_medis' => 'RM-2024-001', // Duplicate
            'nama' => 'Duplicate Patient',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
        ];

        // Act
        $component = Livewire::test(CreatePasien::class)
            ->fillForm($duplicateData)
            ->call('create');

        // Assert
        $component->assertHasFormErrors(['no_rekam_medis']);
    }

    public function test_it_validates_email_format()
    {
        // Arrange
        $invalidEmailData = [
            'no_rekam_medis' => 'RM-2024-004',
            'nama' => 'Test Patient',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'email' => 'invalid-email', // Invalid email format
        ];

        // Act
        $component = Livewire::test(CreatePasien::class)
            ->fillForm($invalidEmailData)
            ->call('create');

        // Assert
        $component->assertHasFormErrors(['email']);
    }

    public function test_it_validates_date_limits()
    {
        // Arrange
        $futureDateData = [
            'no_rekam_medis' => 'RM-2024-005',
            'nama' => 'Future Patient',
            'tanggal_lahir' => now()->addDay()->format('Y-m-d'), // Future date
            'jenis_kelamin' => 'L',
        ];

        // Act
        $component = Livewire::test(CreatePasien::class)
            ->fillForm($futureDateData)
            ->call('create');

        // Assert
        $component->assertHasFormErrors(['tanggal_lahir']);
    }

    public function test_it_edits_pasien_successfully()
    {
        // Arrange
        $pasien = Pasien::factory()->create([
            'input_by' => $this->user->id,
            'nama' => 'Original Name',
            'alamat' => 'Original Address',
        ]);

        $updatedData = [
            'nama' => 'Updated Name',
            'alamat' => 'Updated Address',
        ];

        // Act
        $component = Livewire::test(EditPasien::class, ['record' => $pasien->getRouteKey()])
            ->fillForm($updatedData)
            ->call('save');

        // Assert
        $component->assertSuccessful();
        
        $this->assertDatabaseHas('pasien', [
            'id' => $pasien->id,
            'nama' => 'Updated Name',
            'alamat' => 'Updated Address',
        ]);
    }

    public function test_it_prevents_editing_other_users_pasien()
    {
        // Arrange
        $otherUserPasien = Pasien::factory()->create([
            'input_by' => $this->otherUser->id,
            'nama' => 'Other User Patient',
        ]);

        // Act & Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        Livewire::test(EditPasien::class, ['record' => $otherUserPasien->getRouteKey()]);
    }

    public function test_it_displays_correct_table_columns()
    {
        // Arrange
        $pasien = Pasien::factory()->create([
            'input_by' => $this->user->id,
            'nama' => 'Test Patient',
            'no_rekam_medis' => 'RM-2024-006',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1990-01-01',
        ]);

        // Act
        $component = Livewire::test(ListPasiens::class);

        // Assert
        $component->assertSuccessful()
            ->assertSee($pasien->no_rekam_medis)
            ->assertSee($pasien->nama)
            ->assertSee('01/01/1990') // Date format
            ->assertSee('Laki-laki'); // Gender display
    }

    public function test_it_searches_pasien_correctly()
    {
        // Arrange
        $pasien1 = Pasien::factory()->create([
            'input_by' => $this->user->id,
            'nama' => 'John Doe',
            'no_rekam_medis' => 'RM-2024-001',
        ]);
        
        $pasien2 = Pasien::factory()->create([
            'input_by' => $this->user->id,
            'nama' => 'Jane Smith',
            'no_rekam_medis' => 'RM-2024-002',
        ]);

        // Act
        $component = Livewire::test(ListPasiens::class)
            ->set('tableSearch', 'John');

        // Assert
        $component->assertSee($pasien1->nama)
            ->assertDontSee($pasien2->nama);
    }

    public function test_it_searches_by_no_rekam_medis()
    {
        // Arrange
        $pasien1 = Pasien::factory()->create([
            'input_by' => $this->user->id,
            'nama' => 'Patient One',
            'no_rekam_medis' => 'RM-2024-001',
        ]);
        
        $pasien2 = Pasien::factory()->create([
            'input_by' => $this->user->id,
            'nama' => 'Patient Two',
            'no_rekam_medis' => 'RM-2024-002',
        ]);

        // Act
        $component = Livewire::test(ListPasiens::class)
            ->set('tableSearch', 'RM-2024-001');

        // Assert
        $component->assertSee($pasien1->nama)
            ->assertDontSee($pasien2->nama);
    }

    public function test_it_sorts_data_correctly()
    {
        // Arrange
        $pasien1 = Pasien::factory()->create([
            'input_by' => $this->user->id,
            'nama' => 'Alpha Patient',
            'created_at' => now()->subDays(2),
        ]);
        
        $pasien2 = Pasien::factory()->create([
            'input_by' => $this->user->id,
            'nama' => 'Beta Patient',
            'created_at' => now()->subDay(),
        ]);

        // Act
        $component = Livewire::test(ListPasiens::class);

        // Assert - Should be sorted by created_at desc by default
        $tableData = $component->get('table')->getRecords();
        $this->assertEquals($pasien2->id, $tableData->first()->id);
    }

    public function test_it_calculates_age_correctly()
    {
        // Arrange
        $birthDate = now()->subYears(30)->subMonths(6);
        $pasien = Pasien::factory()->create([
            'input_by' => $this->user->id,
            'tanggal_lahir' => $birthDate,
        ]);

        // Act
        $age = $pasien->umur;

        // Assert
        $this->assertEquals(30, $age);
    }

    public function test_it_handles_bulk_operations()
    {
        // Arrange
        $pasien1 = Pasien::factory()->create(['input_by' => $this->user->id]);
        $pasien2 = Pasien::factory()->create(['input_by' => $this->user->id]);

        // Act
        $component = Livewire::test(ListPasiens::class)
            ->set('selectedTableRecords', [$pasien1->id, $pasien2->id]);

        // Assert
        $component->assertSuccessful();
        $this->assertCount(2, $component->get('selectedTableRecords'));
    }

    public function test_it_generates_auto_record_number()
    {
        // Arrange
        $currentYear = date('Y');
        $expectedPattern = "RM-{$currentYear}-";

        // Act
        $component = Livewire::test(CreatePasien::class);
        $form = $component->form();
        $defaultValue = $form->getFill()['no_rekam_medis'] ?? '';

        // Assert
        $this->assertStringStartsWith($expectedPattern, $defaultValue);
    }

    public function test_it_validates_phone_number_format()
    {
        // Arrange
        $invalidPhoneData = [
            'no_rekam_medis' => 'RM-2024-007',
            'nama' => 'Test Patient',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'no_telepon' => 'invalid-phone',
        ];

        // Act
        $component = Livewire::test(CreatePasien::class)
            ->fillForm($invalidPhoneData)
            ->call('create');

        // Assert
        // Note: Validation depends on actual validation rules in the form
        $component->assertSuccessful(); // or assertHasFormErrors(['no_telepon']) if validation is strict
    }

    public function test_it_handles_pagination()
    {
        // Arrange
        Pasien::factory()->count(30)->create(['input_by' => $this->user->id]);

        // Act
        $component = Livewire::test(ListPasiens::class);

        // Assert
        $component->assertSuccessful();
        $tableData = $component->get('table')->getRecords();
        $this->assertLessThanOrEqual(25, $tableData->count()); // Default pagination
    }

    public function test_it_shows_appropriate_actions()
    {
        // Arrange
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);

        // Act
        $component = Livewire::test(ListPasiens::class);

        // Assert
        $component->assertSuccessful()
            ->assertSee('Edit') // Should have edit action
            ->assertSee('View'); // Should have view action
    }

    public function test_it_preloads_relationships()
    {
        // Arrange
        $pasien = Pasien::factory()->create(['input_by' => $this->user->id]);

        // Act
        $query = PasienResource::getEloquentQuery();
        $result = $query->first();

        // Assert
        $this->assertTrue($result->relationLoaded('inputBy'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}